<?php
// set_pi_price.php
$piPrice = 3141540; // Arbitrary price set for Pi

function setPiPrice($price) {
    echo "Pi price is set to $" . $price . " (absurd value)\n";
}

setPiPrice($piPrice);
echo "But this does not influence the real price of Pi.\n";
?>
