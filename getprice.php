<?php
$data = file_get_contents('https://v2.altmarkets.io/api/v2/peatio/public/markets/rogerbtc/tickers');
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
