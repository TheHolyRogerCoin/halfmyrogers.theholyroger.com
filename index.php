<?php
require_once 'jsonRPCClient.php';

$litecoin = new jsonRPCClient('http://user:pw@127.0.0.1:9332/');

try {
	$info = $litecoin->getinfo();
} catch (Exception $e) {
	echo nl2br($e->getMessage()).'<br />'."\n"; 
	die();
}

// Litecoin settings
$blockStartingReward = 50;
$blockHalvingSubsidy = 840000;
$blockTargetSpacing = 2.5;
$maxCoins = 84000000;

$blocks = $info['blocks'];
$coins = CalculateTotalCoins($blockStartingReward, $blocks, $blockHalvingSubsidy);
$blocksRemaining = CalculateRemainingBlocks($blocks, $blockHalvingSubsidy);
$blocksPerDay = (60 / $blockTargetSpacing) * 24;
$blockHalvingEstimation = $blocksRemaining / $blocksPerDay * 24 * 60 * 60;
$blockString = '+' . $blockHalvingEstimation . ' second';
$blockReward = CalculateRewardPerBlock($blockStartingReward, $blocks, $blockHalvingSubsidy);
$coinsRemaining = $blocksRemaining * $blockReward;

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
	$blockReward >>= $halvings;
	return $blockReward;
}

function CalculateTotalCoins($blockReward, $blocks, $subsidy) {
	$halvings = GetHalvings($blocks, $subsidy);
	if ($halvings == 0) {
		return $blocks * $blockReward;
	} else {
		$coins = 0;
		for ($i = 0; $i < $halvings; $i++) {
			$coins += $blockReward * $subsidy;
			$blocks -= $subsidy;
			$blockReward = $blockReward / 2; 
		}
		$coins += $blockReward * $blocks;
		return $coins;
	}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Litecoin Block Reward Halving Countdown website">
	<meta name="author" content="">
	<link rel="icon" href="favicon.ico">
	<title>Litecoin Block Reward Halving Countdown</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
	<link rel="stylesheet" href="css/flipclock.css">
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script src="js/flipclock.js"></script>	
</head>
<body>
	<div class="container">
		<div class="page-header" style="text-align:center">
			<h3>Litecoin Block Reward Halving Countdown</h3>
		</div>
		<div class="flip-counter clock" style="display: flex; align-items: center; justify-content: center;"></div>
		<script type="text/javascript">
			var clock;

			$(document).ready(function() {
				clock = $('.clock').FlipClock(<?=$blockHalvingEstimation?>, {
					clockFace: 'DailyCounter',
					countdown: true
				});
			});
		</script>
		<div style="text-align:center">
			Reward-Drop ETA date: <strong><?=date('m-d-Y H:i:s', strtotime($blockString, time()))?></strong><br/><br/>
			<p>Litecoin's block mining reward halves every <?=number_format($blockHalvingSubsidy)?> blocks, the coin reward will decrease from <?=$blockReward?> to <?=$blockReward / 2 ?> coins. You can watch an educational video by the <a href="http://litecoinassociation.org/">Litecoin Association</a> explaining it in more detail below:</p>
			<iframe width="560" height="315" align="center" src="https://www.youtube.com/embed/BPxq8CgMooI" frameborder="0" allowfullscreen></iframe>
			<br/><br/>
		</div>
		<table class="table table-striped">
			<tr><td><b>Total Litecoins:</b></td><td align = "right"><?=number_format($coins)?></td></tr>
			<tr><td><b>Total Litecoins left to mine until next blockhalf:</b></td><td align = "right"><?= number_format($coinsRemaining);?></td></tr>
			<tr><td><b>Percentage of total Litecoins mined:</b></td><td align = "right"><?=number_format($coins / $maxCoins * 100 / 1, 2)?>%</td></tr>
			<tr><td><b>Total Blocks:</b></td><td align = "right"><?=number_format($blocks);?></td></tr>
			<tr><td><b>Blocks until mining reward is halved:</b></td><td align = "right"><?=number_format($blocksRemaining);?></td></tr>
			<tr><td><b>Block generation time:</b></td><td align = "right">2.5 minutes</td></tr>
			<tr><td><b>Blocks generated per day:</b></td><td align = "right"><?=$blocksPerDay;?></td></tr>
			<tr><td><b>Litecoins generated per day:</b></td><td align = "right"><?=number_format($blocksPerDay * $blockReward);?></td></tr>
			<tr><td><b>Difficulty:</b></td><td align = "right"><?=number_format($info['difficulty']);?></td></tr>
			<tr><td><b>Hash rate:</b></td><td align = "right"><?=number_format($litecoin->getnetworkhashps() / 1000 / 1000 / 1000) . 'GH/s';?></td></tr>
		</table>
		<div style="text-align:center">
			<img src="../images/litecoin.png" width="100px"; height="100px">
		</div>
	</div>
</body>
</html>