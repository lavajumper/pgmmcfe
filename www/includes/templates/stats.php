<div class="block withsidebar">

	<div class="block_head">
		<div class="bheadl"></div>
		<div class="bheadr"></div>

		<h2>Welcome, <?php if($cookieValid) { echo $userInfo->username; } else { echo "Guest"; } ?></h2>
	</div>		<!-- .block_head ends -->




	<div class="block_content">

		<div class="sidebar">
			<?php include ("includes/leftsidebar.php"); ?>
		</div>		<!-- .sidebar ends -->




		<div class="sidebar_content">

		<div class="block" style="clear:none;">
                 <div class="block_head">
                  <div class="bheadl"></div>
                  <div class="bheadr"></div>
                  <h2>Server Stats</h2>
                 </div>

		<div class="block_content" style="padding:10px;">
		<p>
<?php
$BTC_per_block = $settings->getsetting("block_reward");

$litecoinController = new LitecoinClient($rpcType, $rpcUsername, $rpcPassword, $rpcHost);

$difficulty = $litecoinController->query("getdifficulty");

// START SERVER STATS ************************************************

echo "<table class=\"\" width='50%' style='font-size:14px;'>";

	$hashrate = $settings->getsetting('currenthashrate');
	$show_hashrate = round($hashrate / 1000,3);

echo "<tr><td class=\"leftheader\">Pool Hash Rate</td><td>". number_format($show_hashrate, 3) . " Mhash/s</td></tr>";

//	$results = db_query("SELECT (1 - (SUM(stale_share_count)/SUM(share_count))) * 100 AS efficiency FROM webusers") or sqlerr(__FILE__, __LINE__);
//	$row = db_fetch_object($results);

//echo "<tr><td class=\"leftheader\">Pool Efficiency</td><td><span class=\"green\">". number_format($row->efficiency, 2) . "%</span></td></tr>";

	$res = db_query("SELECT count(webusers.id) FROM webusers WHERE hashrate > 0") or sqlerr(__FILE__, __LINE__);
	$row = db_fetch_array($res);
	$users = $row[0];

//echo "<tr><td class=\"leftheader\">Current Users Mining</td><td>" . number_format($users) . "</td></tr>";
echo "<tr><td class=\"leftheader\">Current Total Miners</td><td>" . number_format($settings->getsetting('currentworkers')) . "</td></tr>";

	$current_block_no = $litecoinController->query("getblockcount");

echo "<tr><td class=\"leftheader\">Current Block</td><td><a href=\"" . $current_block_no . "\" target='_new'>";
echo number_format($current_block_no) . "</a></td></tr>";

	$show_difficulty = round($difficulty, 2);

echo "<tr><td class=\"leftheader\">Current Difficulty</th><td><a href=\"" target='_new'>" . number_format($show_difficulty) . "</a></td></tr>";

	$result = db_query("SELECT n.blocknumber, n.confirms, n.timestamp FROM winning_shares w, networkblocks n WHERE w.blocknumber = n.blocknumber ORDER BY w.blocknumber DESC LIMIT 1");

	$show_time_since_found = false;
	$time_last_found;

        if ($resultrow = db_fetch_object($result)) {

                $found_block_no = $resultrow->blockcount;
                $confirm_no = $resultrow->confirms;

echo "<tr><td class=\"leftheader\">Last Block Found</td><td><a href=\"" . $found_block_no . "\" target='_new'>" . number_format($found_block_no) . "</a></td></tr>";

                $time_last_found = $resultrow->timestamp;

                $show_time_since_found = true;
        }


echo "</table>";



?>

<li>Please login or create an account to see FULL server stats.</li>
<li>These stats are also available in JSON format <a href="/api" target="_api">HERE</a></li>
</p>
                </div>          <!-- nested block ends -->
                <div class="bendl"></div>
                <div class="bendr"></div>
                </div>



		</div>		<!-- .sidebar_content ends -->

	</div>		<!-- .block_content ends -->

	<div class="bendl"></div>
	<div class="bendr"></div>

</div>		<!-- .block ends -->
