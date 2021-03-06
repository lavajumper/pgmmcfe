<?php
/*
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//Check if the cookie is set, if so check if the cookie is valid

error_reporting(E_ERROR | E_WARNING | E_PARSE);
$block_reward=$settings->getsetting("block_reward");

if(isset($_COOKIE[$cookieName])){
	$cookieValid = false;
	$ip = $_SERVER['REMOTE_ADDR']; //Get Ip address for cookie validation
	$validateCookie	= new checkLogin();
	$cookieValid = $validateCookie->checkCookie(db_real_escape_string($_COOKIE[$cookieName]), $ip);
	//var_dump($validateCookie);
	$userId	= $validateCookie->returnUserId($_COOKIE[$cookieName]);

	//ensure userId is integer to prevent sql injection attack
	if (!is_numeric($userId)) {
		$userId = 0;
		exit;
	}

	//Get user information
	$userInfoQ = db_query("SELECT id, username, pin, pass, admin, email, share_count, stale_share_count, shares_this_round, hashrate, api_key, COALESCE(donate_percent, '0') as donate_percent, COALESCE(round_estimate, '0') as round_estimate, tz FROM webusers WHERE id = '".$userId."' LIMIT 1"); //
	$userInfo = db_fetch_object($userInfoQ);
	if (isset($userInfo->pin)) { $authPin = $userInfo->pin; }
	if (isset($userInfo->pass)) { $hashedPass = $userInfo->pass; }
	if (isset($userInfo->admin)) { $isAdmin = $userInfo->admin; }
	// if (isset($userInfo->share_count)) { $lifetimeUserShares = $userInfo->share_count - $userInfo->stale_share_count; }
	$lifetimeUserShares = $userInfo->share_count - $userInfo->stale_share_count;
	if (isset($userInfo->stale_share_count)) { $lifetimeUserInvalidShares = $userInfo->stale_share_count; }
	if (isset($userInfo->shares_this_round)) { $totalUserShares = $userInfo->shares_this_round; }
	if (isset($userInfo->hashrate)) { $currentUserHashrate = $userInfo->hashrate; }
	if (isset($userInfo->api_key)) { $userApiKey = $userInfo->api_key; }
	if (isset($userInfo->donate_percent)) { $donatePercent = $userInfo->donate_percent; }
	if (isset($userInfo->round_estimate)) { $userRoundEstimate = $userInfo->round_estimate; }
	if (isset($userInfo->tz)) { $userTz = $userInfo->tz; }
	if (isset($userInfo->email)) { $userEmail = $userInfo->email; }
	//Get current round share information, estimated total earnings
	//$currentSharesQ = db_query("SELECT username FROM pool_worker WHERE associatedUserId = '".$userId."'");
	$totalSharesQ = db_query("SELECT value FROM settings where setting='currentroundshares'");
	while ($totalOverallSharesR = db_fetch_array($totalSharesQ))
		$totalOverallShares = intval($totalOverallSharesR[0]);

	if (isset($totalUserShares)) {
		//Prevent divide by zero
		if($totalUserShares > 0 && $totalOverallShares > 0){
			$estimatedTotalEarnings = $totalUserShares/$totalOverallShares;
			$estimatedTotalEarnings *= 100; //The expected SXC to be given out
			$estimatedTotalEarnings = round($estimatedTotalEarnings, 8)." $totalUserShares::$totalOverallShares";
		}else{
			$estimatedTotalEarnings = "NoCalc";
		}
	}

	//Get Current balance
	$currentBalanceQ = db_query("SELECT balance, COALESCE(sendaddress,'') as sendaddress, threshold FROM accountbalance WHERE userid = '".$userId."' LIMIT 1");
	if ($currentBalanceObj = pg_fetch_object($currentBalanceQ)) {
		$currentBalance = $currentBalanceObj->balance;
		//Get payment address that is associated wit this user
		$paymentAddress = $currentBalanceObj->sendaddress;
		$payoutThreshold = $currentBalanceObj->threshold;
	} else {
		$currentBalance = 0;
		$paymentAddress = "";
		$payoutThreshold = 0;
	}
}
?>
