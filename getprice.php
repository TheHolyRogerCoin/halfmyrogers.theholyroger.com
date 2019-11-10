<?php
$data = file_get_contents('https://altmarkets.io/api/v2/tickers/rogerbtc');
$price = json_decode($data, true);
$coinPrice = (float)$price["ticker"]["last"];

if ($price <= 1.0) {
	die(); 
}

$file = "price.txt";
$fh = fopen($file, 'w') or die("can't open file");
fwrite($fh, $coinPrice);
fclose($fh);
?>
