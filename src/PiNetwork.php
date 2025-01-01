<?php

namespace Get2\A2uphp;

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;

use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\FeeBumpTransactionBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\TimeBounds;

use GuzzleHttp\Client; 

class PiNetwork{
	private $api_key;
	private $walletPrivateSeed;

	private $httpClient;
    private $currentPayment;

	public function __construct($api_key, $walletPrivateSeed)
	{
		$this->api_key = $api_key;
		$this->walletPrivateSeed = $walletPrivateSeed;

		$this->httpClient = new Client([
            'base_uri' => "https://api.minepi.com",
            'exceptions' => false,
            'verify' => false
        ]);
	}

    public function getHorizonClient($network)
    {
        $serverUrl = $network === "Pi Network" ? "https://api.mainnet.minepi.com" : "https://api.testnet.minepi.com";
        $sdk = new StellarSDK($serverUrl);
        return $sdk;
    }

	public function createPayment($paymentData)
	{
        try {
    		$rep = $this->httpClient->request('POST', '/v2/payments', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Key '.$this->api_key
                ],
                'json' => $paymentData
            ]);
            $body = $rep->getBody();
            $body_obj = json_decode($body, false, 512, JSON_UNESCAPED_UNICODE);
            return $body_obj->identifier;
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
	}

    public function getPayment($paymentId)
    {
        $rep = $this->httpClient->request('GET', '/v2/payments/'.$paymentId, [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Key '.$this->api_key
            ],
        ]);
        $body = $rep->getBody();
        $body_obj = json_decode($body, false, 512, JSON_UNESCAPED_UNICODE);
        return $body_obj;
    }

    public function submitPayment(string $paymentId)
    {
        if (!$this->currentPayment || $this->currentPayment->identifier !== $paymentId) {
            $this->currentPayment = $this->getPayment($paymentId);
            $txid = $this->currentPayment->transaction->txid ?? null;
            if ($txid) {
                throw new \Exception(json_encode([
                    'message' => 'This payment already has a linked txid',
                    'paymentId' => $paymentId,
                    'txid' => $txid
                ]));
            }
        }
        $amount = $this->currentPayment->amount;
        $destination = $this->currentPayment->to_address;
        $network = $this->currentPayment->network;

        $sdk = $this->getHorizonClient($network);

        ///////////////////////////////////////////////////////
        $responseFeeStats = $sdk->requestFeeStats();
        //$feeCharged = $response->getFeeCharged();
        $feeCharged = $responseFeeStats->getLastLedgerBaseFee();
        ///////////////////////////////////////////////////////

        $senderKeyPair = KeyPair::fromSeed($this->walletPrivateSeed);

        // Load sender account data from the stellar network.
        $sender = $sdk->requestAccount($senderKeyPair->getAccountId());

        /*$minTime = 1641803321;
        $maxTime = 1741803321;
        $timeBounds = new TimeBounds((new \DateTime)->setTimestamp($minTime), (new \DateTime)->setTimestamp($maxTime));*/

        $paymentOperation = (new PaymentOperationBuilder($destination,Asset::native(), $amount))->build();
        $transaction = (new TransactionBuilder($sender))
            ->addOperation($paymentOperation)
            ->setMaxOperationFee($feeCharged)
            ->addMemo(Memo::text($this->currentPayment->identifier))
            //->setTimeBounds($timeBounds)
            ->build();
        // Sign and submit the transaction
        $transaction->sign($senderKeyPair, new Network($network));
        $response = $sdk->submitTransaction($transaction);

        if (!$response->isSuccessful()) {
            //throw new \Exception('Transaction submission failed: ' . json_encode($response->getExtras()));
            return [
                'status' => false,
                'message' => json_encode($response->getExtras())
            ];
        }

        return $response->getHash();
    }

    public function completePayment($paymentId, $txid)
    {
        try {
            $rep = $this->httpClient->request('POST', '/v2/payments/'.$paymentId.'/complete', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Key '.$this->api_key
                ],
                'json' => ['txid' => $txid],
            ]);
            $body = $rep->getBody();
            $body_obj = json_decode($body, false, 512, JSON_UNESCAPED_UNICODE);
            return $body_obj;
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function cancelPayment($paymentId)
    {
        try {
            $rep = $this->httpClient->request('POST', '/v2/payments/'.$paymentId.'/cancel', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Key '.$this->api_key
                ],
            ]);
            $body = $rep->getBody();
            $body_obj = json_decode($body, false, 512, JSON_UNESCAPED_UNICODE);
            return $body_obj;
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function incompletePayments(): Array
    {
        $rep = $this->httpClient->request('GET', '/v2/payments/incomplete_server_payments', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Key '.$this->api_key
            ],
        ]);
        $body = $rep->getBody();
        $body_obj = json_decode($body, false, 512, JSON_UNESCAPED_UNICODE);
        return $body_obj->incomplete_server_payments;
    }

    public function cancelAllIncompletePayments()
    {
        try {
            $incompletePayments = $this->incompletePayments();
            if (is_array($incompletePayments)) {
                foreach ($incompletePayments as $key => $value) {
                    $this->cancelPayment($value->identifier);
                }
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

?>