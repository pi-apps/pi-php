<?
class PiNetwork {
  var $baseUrl;
  var $headers = array();
  var $compression;
  var $apiKey;
  var $wallet;
  
  function __construct($apiKey= string, $walletPrivateSeed= string) {
    $this->compression='gzip';
    $this->apiKey=$apiKey;
    $this->wallet=$walletPrivateSeed;
    $this->baseUrl='https://api.minepi.com';
  }

  function get($EndPoint) {
    $url= $this->baseUrl . "/v2/payments/" . $EndPoint;  
    $process = curl_init($url);
    curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
    curl_setopt($process, CURLOPT_TIMEOUT, 30);
    curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);

    curl_setopt($process, CURLOPT_VERBOSE, false);
    curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($process, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($process, CURLOPT_SSL_VERIFYSTATUS, false);

    $return = curl_exec($process);
    curl_close($process);
    return $return;
  }

  function post($EndPoint,$data) {
    $url= $this->baseUrl . "/v2/payments/" . $EndPoint;  
    $process = curl_init($url);

    curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
    curl_setopt($process, CURLOPT_ENCODING , $this->compression);
    curl_setopt($process, CURLOPT_TIMEOUT, 30);
    curl_setopt($process, CURLOPT_POSTFIELDS, $data);
    curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);

    curl_setopt($process, CURLOPT_VERBOSE, false);
    curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($process, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($process, CURLOPT_SSL_VERIFYSTATUS, false);

    curl_setopt($process, CURLOPT_POST, 1);
    $return = curl_exec($process);
    curl_close($process);
    return $return;
  }
  
  function ApprovalPayment($data) {
    $this->headers[] = "Authorization: key ".$this->apiKey;
    return $this->post($data['paymentId'].'/approve', '');
  }
  
  function CancelPayment($data) {
    $this->headers[] = "Authorization: key ".$this->apiKey;
    return $this->post($data['paymentId'].'/cancel', '');
  }
  
  function CompletePayment($data) {
    $this->headers[] = "Authorization: key ".$this->apiKey;
    $this->headers[] = 'Content-Type: application/json';
    return $this->post($data['paymentId'].'/complete', '{"txid":"'.$data['txid'].'"}');
  }

  function InCompletePayment($data) {
    $this->headers[] = "Authorization: key ".$this->apiKey;
    $this->headers[] = 'Content-Type: application/json';
    return $this->post($data['identifier'].'/complete', '{"txid":"'.$data['transaction']['txid'].'"}');
  }
}
