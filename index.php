<?php
// include_once("analyticstracking.php");
$ReadyToInclude = true;
require_once 'jsonRPCClient.php';
require_once 'config.php';

$CoinRPC = new jsonRPCClient($RPCHost);

try {
	$info = $CoinRPC->getblockchaininfo();
} catch (Exception $e) {
	echo nl2br($e->getMessage()).'<br />'."\n"; 
	die();
}

// TheHolyRogerCoin settings
$blockStartingReward = 50;
$blockHalvingSubsidy = 840000;
$blockTargetSpacing = 2.5;
$maxCoins = 99000000;
$premineCoins = 14999850;

$blocks = $info['blocks'];
$coins = CalculateTotalCoins($blockStartingReward, $blocks, $blockHalvingSubsidy, $premineCoins);
$blocksRemaining = CalculateRemainingBlocks($blocks, $blockHalvingSubsidy);
$blocksPerDay = (60 / $blockTargetSpacing) * 24;
$blockHalvingEstimation = $blocksRemaining / $blocksPerDay * 24 * 60 * 60;
$blockHalvings = GetHalvings($blocks, $blockHalvingSubsidy);
$blockString = '+' . $blockHalvingEstimation . ' second';
$blockReward = CalculateRewardPerBlock($blockStartingReward, $blocks, $blockHalvingSubsidy);
$coinsRemaining = $blocksRemaining * $blockReward;
$nextHalvingHeight = $blocks + $blocksRemaining;
$inflationRate = CalculateInflationRate($coins, $blockReward, $blocksPerDay);
$inflationRateNextHalving = CalculateInflationRate(CalculateTotalCoins($blockStartingReward, $nextHalvingHeight, $blockHalvingSubsidy, $premineCoins), 
	CalculateRewardPerBlock($blockStartingReward, $nextHalvingHeight, $blockHalvingSubsidy), $blocksPerDay);
$price = GetPrice(); // change to dynamic way of getting price
$priceFormatted = FormatPrice($price); // change to dynamic way of getting price
$marketCap = GetMarketCap($coins, $price); // change to dynamic way of getting price

function GetMarketCap($coins, $price) {
	$symbol = '₿ ';
	$marketCap = ($coins * $price);
	if ($marketCap <= 0.0001) {
		$marketCap = ($marketCap*100000000);
		$symbol = ' Satoshis';
		$marketCap = number_format($marketCap, 2).$symbol;
	} else {
		$marketCap = $symbol.number_format($marketCap, 2);
	}
	return $marketCap;
}

function FormatPrice($price) {
	$symbol = '₿ ';
	if ($price <= 0.0001) {
		$price = ($price*100000000);
		$symbol = ' Satoshis';
		$price = number_format($price, 2).$symbol;
	} else {
		$price = $symbol.number_format($price, 2);
	}
	return $price;
}

function GetPrice() {
	$file = fopen("price.txt", "r") or die("Unable to open file!");
	$result = fread($file,filesize("price.txt"));
	fclose($file);
	return $result;
}

function GetHalvings($blocks, $subsidy) {
	return (int)($blocks / $subsidy);
}

function CalculateRemainingBlocks($blocks, $subsidy) {
	$halvings = GetHalvings($blocks, $subsidy);
	if ($halvings == 0) {
		return $subsidy - $blocks;
	} else {
		$halvings += 1;
		return $halvings * $subsidy - $blocks;
	}
}

function CalculateRewardPerBlock($blockReward, $blocks, $subsidy) {
	$halvings = GetHalvings($blocks, $subsidy);

	if ($halvings == 0) {
		return $blockReward;
	}

	for ($i = 0; $i < $halvings; $i++) {
		$blockReward = $blockReward / 2;
	}

	return $blockReward;
}

function CalculateTotalCoins($blockReward, $blocks, $subsidy, $premineCoins) {
	$halvings = GetHalvings($blocks, $subsidy);
	if ($halvings == 0) {
		return ($blocks * $blockReward) + ($premineCoins);
	} else {
		$coins = 0;
		for ($i = 0; $i < $halvings; $i++) {
			$coins += $blockReward * $subsidy;
			$blocks -= $subsidy;
			$blockReward = $blockReward / 2; 
		}
		$coins += $blockReward * $blocks;
		return $coins + ($premineCoins);
	}
}

function CalculateInflationRate($totalCoins, $blockReward, $blocksPerDay) {
	return pow((($totalCoins + $blockReward) / $totalCoins), (365 * $blocksPerDay)) - 1;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="TheHolyRogerCoin Block Reward Halving Countdown website">
	<meta name="author" content="">
	<link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
	<link rel="manifest" href="site.webmanifest">
	<link rel="mask-icon" href="images/safari-pinned-tab.svg" color="#5bbad5">
	<meta name="msapplication-TileColor" content="#da532c">
	<meta name="theme-color" content="#ffffff">
	<title>TheHolyRogerCoin Block Reward Halving Countdown</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
	<link rel="stylesheet" href="css/flipclock.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script src="js/flipclock.js"></script>	
</head>
<body>
	<div class="container">
		<div class="page-header" style="text-align:center">
			<h3>TheHolyRogerCoin Block Reward Halving Countdown</h3>
		</div>
		<div class="flip-counter clock" style="display: flex; align-items: center; justify-content: center; margin:0"></div>
		<script type="text/javascript">
		var clock;
		$(document).ready(function() {
			clock = new FlipClock($('.clock'), <?=$blockHalvingEstimation?>, {
				clockFace: 'DailyCounter',
				autoStart: true,
				countdown: true
			});
		});
		</script>
		<div style="text-align:center">
			Reward-Drop ETA date: <strong><?=date('d M Y H:i:s', strtotime($blockString, time()))?></strong><br/><br/>
			<br/><br/>
		</div>
		<h2>So... am I getting robbed half of my coins?</h2>
		<p>No. It just means TheHolyRogerCoin block mining reward halves every <?=number_format($blockHalvingSubsidy)?> blocks, the coin reward will decrease from <?=$blockReward?> to <?=$blockReward / 2 ?> coins.
		But that doesn't mean you aren't gonna get yourself robbed half, quarter, or all of your coins at some point!</p>
		<h2>But why?</h2>
		<p>Because TheHolyRogerCoin is a scam, like Litecoin. And because Litecoin is a scam, like Bitcoin.</p>
		<h2>Unpredictable money supply</h2>
		<p>Since we know TheHolyRogerCoin's issuance over time, people can know how much money they are losing at any time. This is helpful to know how shittier life can get when shit finally hits the fan.</p>
		<h2>Who controls the issuance of TheHolyRogerCoin?</h2>
		<p>Holiestoshi Rogernoto. Didn't you really know this?</p>
		<h2>Past halving event dates</h2>
		<p>No halving has happened in TheHolyRogerCoin yet. Much better than what happens in boring scams, at block height 4 the issuance rate shrinked from 5,000,000 to 50 in what could be named The Great Hexadecihalving, as this is more or less equivalent to 16 simulatenous halvings.</p>
		<h2>Past halving price performance</h2>
		<p>TheHolyRogerCoin is the worst halving performant crypto asset ever made, as it is designed specifically in order to have a value of exactly 0.00 $ US.</p>
		<table class="table table-striped">
			<tr><td><b>Total ROGERs in circulation:</b></td><td align = "right"><?=number_format($coins)?></td></tr>
			<tr><td><b>Total ROGERs to ever be produced:</b></td><td align = "right"><?=number_format($maxCoins)?></td></tr>
			<tr><td><b>Percentage of total ROGERs mined:</b></td><td align = "right"><?=number_format($coins / $maxCoins * 100 / 1, 2)?>%</td></tr>
			<tr><td><b>Total ROGERs left to mine:</b></td><td align = "right"><?=number_format($maxCoins - $coins)?></td></tr>
			<tr><td><b>Total ROGERs left to mine until next blockhalf:</b></td><td align = "right"><?= number_format($coinsRemaining);?></td></tr>
			<tr><td><b>Roger price:</b></td><td align = "right">$0.00 (Stablecoin.) ~ <?=$priceFormatted;?></td></tr>
			<tr><td><b>Market capitalization (USD):</b></td><td align = "right">$0.00 (Stablecoin.) ~ <?=$marketCap;?></td></tr>
			<tr><td><b>ROGERs generated per day:</b></td><td align = "right"><?=number_format($blocksPerDay * $blockReward);?></td></tr>	
			<tr><td><b>TheHolyRogerCoin inflation rate per annum:</b></td><td align = "right"><?=number_format($inflationRate * 100 / 1, 2);?>%</td></tr>
			<tr><td><b>TheHolyRogerCoin inflation rate per annum at next block halving event:</b></td><td align = "right"><?=number_format($inflationRateNextHalving * 100 / 1, 2);?>%</td></tr>
			<tr><td><b>Total blocks:</b></td><td align = "right"><?=number_format($blocks);?></td></tr>
			<tr><td><b>Blocks until mining reward is halved:</b></td><td align = "right"><?=number_format($blocksRemaining);?></td></tr>
			<tr><td><b>Total number of block reward halvings:</b></td><td align = "right"><?=$blockHalvings;?></td></tr>
			<tr><td><b>Approximate block generation time:</b></td><td align = "right"><?=$blockTargetSpacing?> minutes</td></tr>
			<tr><td><b>Approximate blocks generated per day:</b></td><td align = "right"><?=$blocksPerDay;?></td></tr>
			<tr><td><b>Difficulty:</b></td><td align = "right"><?=number_format($info['difficulty']);?></td></tr>
			<tr><td><b>Hash rate:</b></td><td align = "right"><?=number_format($CoinRPC->getnetworkhashps() / 1000 / 1000 ) . 'MH/s';?></td></tr>
		</table>
		<div style="text-align:center">
			<img src="images/android-chrome-192x192.png" width="100px"; height="100px">
			<br/>
			<h2><a href="https://halfmyrogers.theholyroger.com/">TheHolyRogerCoin Block Halving Countdown</a></h2>
		</div>
	</div>
</body>
</html>
