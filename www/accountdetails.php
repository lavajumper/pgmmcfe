<?php

include ("includes/templates/header.php");

if(!$cookieValid) {
	header('Location: /');
	exit;
}
//Execute the following based on what $_POST["act"] is set to
$returnError = "";
$goodMessage = "";
if (isset($_POST["act"])) {
	$act = $_POST["act"];
	$inputAuthPin = db_real_escape_string(hash("sha256", $_POST["authPin"].$salt));


	//Check if authorization pin has been inputted correctly
	if($inputAuthPin == $authPin && $act){
		if($act == "cashOut"){

			//Get user's balance and send it to set address;
			//Does user have any money in their balance
			if($currentBalance > 0.1){
				$litecoinController = new LitecoinClient($rpcType, $rpcUsername, $rpcPassword, $rpcHost);

				//Send $currentBalance to $paymentAddress
				//Validate that a $paymentAddress has been set & is valid before sending
				if (!empty($paymentAddress)) {
					$isValidAddress = $litecoinController->validateaddress($paymentAddress);
				}

				if($isValidAddress){
					//Subtract TX feee
					$currentBalance = $currentBalance - 0.1;
					//Send money//
					if($litecoinController->sendtoaddress($paymentAddress, $currentBalance)) {
						$paid = 0;
						$result = db_query("SELECT COALESCE(paid,'0') as paid FROM accountbalance WHERE userid=".$userId);
						if ($resultrow = db_fetch_object($result)) $paid = $resultrow->paid + $currentBalance;

						//Reduce balance amount to zero & make a ledger entry
						db_query("UPDATE accountbalance SET balance = 0, paid = '".$paid."' WHERE userid = ".$userId);

						db_query("INSERT INTO ledger (userid, transtype, amount, sendaddress) ".
									" VALUES ".
									"($userId, 'Debit_MP', $currentBalance, '$paymentAddress')");

						$goodMessage = "You have successfully sent ".$currentBalance." to the following address:".$paymentAddress;
						//Set new variables so it appears on the page flawlessly
						$currentBalance = 0;
					}else{
						$returnError = "Commodity failed to send. Contact site support immediately.";
					}
				}else{
					$returnError = "Invalid or missing Litecoin payment address.";
				}
			}else{
				$returnError = "You have no money in your account.";
			}
		}


		if($act == "updateDetails"){
			//Update user's details
			$newTimeZone = db_real_escape_string($_POST["tz"]);
			$newSendAddress = db_real_escape_string($_POST["paymentAddress"]);
			$newDonatePercent = db_real_escape_string($_POST["donatePercent"]);
			$newPayoutThreshold = $_POST["payoutThreshold"];
			
			
			// check and force thresholds on donate percent and payout triggers
			$newPayoutThreshold = min(200000, max(0, floatval($newPayoutThreshold)));
			if ($newPayoutThreshold < 1 || is_nan($newPayoutThreshold) ) { $newPayoutThreshold = 0; }
			$newDonatePercent = min(100, max(0, floatval($newDonatePercent)));

			$updateSuccess1 = db_query("UPDATE accountbalance SET sendaddress = '".$newSendAddress."', threshold = '".$newPayoutThreshold."' WHERE userid = ".$userId);

			if (!is_nan($newDonatePercent))
				$updateSuccess2 = db_query("UPDATE webusers SET donate_percent='".$newDonatePercent."' WHERE id = ".$userId);
			else
				$returnError = "Donation % must be numeric.";
			
			$updateSuccess3 = db_query("UPDATE webusers SET tz = '".$newTimeZone."' WHERE id = ".$userId);
			
			if($updateSuccess1 && $updateSuccess2 && $updateSuccess3){
				$goodMessage = "Account details are now updated.";
				$paymentAddress = $newSendAddress;
				$donatePercent = $newDonatePercent;
				$payoutThreshold = $newPayoutThreshold;
				$userInfo->tz = $newTimeZone;
			}
		}

		if($act == "updatePassword"){
			//Update password
			$oldPass = hash("sha256", db_real_escape_string($_POST["currentPassword"]).$salt);
			$newPass = db_real_escape_string($_POST["newPassword"]);
			$newPassConfirm = db_real_escape_string($_POST["newPassword2"]);

			//If hash $oldPass is the same as the DB already hashed password continue you with the password change
			if($oldPass == $hashedPass){
				//Check if new password is valid
				if($newPass != "" && strlen($newPass) > 6){
					//Change the password only if $newPass == $newPassConfirm
					if($newPass == $newPassConfirm){
						//Update hashed password
						$newHashedPass = hash("sha256", $newPass.$salt);
						$passchangeSuccess = db_query("UPDATE webusers SET pass = '".$newHashedPass."' WHERE id = ".$userId);
						if($passchangeSuccess){
							$goodMessage = "Password successfully changed.";
						}else{
							$returnError = "Database Failure - Unable to change password";
						}
					}else if($newPass != $newPassConfirm){
						$returnError = "The \"New Password\" and \"New Password Repeat\" fields must match";
					}
				}else{
					$returnError = "Your new password is not valid, Must be longer then 6 characters";
				}

			}else if($oldPass != $hashedPass){
				//Typed in password dosent match DB password
				$returnError = "You must type in the correct current password before you can set a new password.";
			}
		}


	}else if($inputAuthPin != $authPin && $act){
		$returnError = "Authorization Pin is Invalid!";
	}

}

?>

<div class="block withsidebar">

<?include("includes/templates/headerbar.php");?>

        <div class="block_content">

                <div class="sidebar">
                        <?php include ("includes/leftsidebar.php"); ?>
                </div>          <!-- .sidebar ends -->


                <div class="sidebar_content">

<?php
//Display Error and Good Messages(If Any)
if ($goodMessage) { echo "<div class=\"message success\"><p>".antiXss($goodMessage)."</p></div>"; }
if ($returnError) { echo "<div class=\"message errormsg\"><p>".antiXss($returnError)."</p></div>"; }
?>

	<!-- Account details -->
                <div class="block" style="clear:none;">
                 <div class="block_head">
                  <div class="bheadl"></div>
                  <div class="bheadr"></div>
                  <h2>Account Details</h2>
                 </div>
                 <div class="block_content" style="padding:10px;">
		<form action="/accountdetails" method="post"><input type="hidden" name="act" value="updateDetails">
		<table>
			<tr><td>Username: </td><td><?php echo antiXss($userInfo->username);?></td></tr>
			<tr><td>User Id: </td><td><?php echo antiXss($userId); ?></td></tr>
			<tr><td>Time Zone: </td><td><?php echo(get_tz_selector("tz",$userInfo->tz)); ?></td></tr>
			<tr><td><a href="api?api_key=<?php echo antiXss($userApiKey) ?>" style="color: blue" target="_blank">API</a> Key: </td><td><h6><font size="1"><?php echo antiXss($userApiKey); ?></font></h6></td></tr>
			<tr><td>Payment Address: </td><td><input type="text" name="paymentAddress" value="<?php echo antiXss($paymentAddress)?>" size="40"></td></tr>
			<tr><td>Donation %: </td><td><input type="text" name="donatePercent" value="<?php echo antiXss($donatePercent);?>" size="4"><font size="1"> [donation amount in percent (example: 0.5)]</font></td></tr>
			<tr><td>Automatic Payout Threshold: </td><td valign="top"><input type="text" name="payoutThreshold" value="<?php echo antiXss($payoutThreshold);?>" size="9" maxlength="6"> <font size="1">[1-200000 SXC. Set to '0' for no auto payout]</font></td></tr>
			<tr><td>4 digit PIN: </td><td><input type="password" name="authPin" size="4" maxlength="4"><font size="1"> [The 4 digit PIN you chose when registering]</font></td></tr>
			<tr><td>Total SXC Collected: </td><td><?php echo antiXss(getTotalPaidToUser($userId));?></td></tr>
		</table>
		<input type="submit" class="submit long" value="Update Settings"></form>
                </div>          <!-- nested block ends -->
                <div class="bendl"></div>
                <div class="bendr"></div>
                </div>


	<!-- Cash Out -->
                <div class="block" style="clear:none;">
                 <div class="block_head">
                  <div class="bheadl"></div>
                  <div class="bheadr"></div>
                  <h2>Cash Out</h2>
                 </div>
                 <div class="block_content" style="padding:10px;">
		<ul><li><font color="">Please note: a 0.1 sxc transaction will apply when processing "On-Demand" manual payments</font></li></ul>
		<form action="/accountdetails" method="post">
		<input type="hidden" name="act" value="cashOut">
		<table>
			<tr><td>Account Balance: &nbsp;&nbsp;&nbsp;</td><td><?php echo antiXss($currentBalance); ?> SXC</td></tr>
			<tr><td>Payout to: </td><td><h6><?php echo antiXss($paymentAddress); ?></h6></td></tr>
			<tr><td>4 digit PIN: </td><td><input type="password" name="authPin" size="4" maxlength="4"></td></tr>
		</table>
		<input type="submit" class="submit mid" value="Cash Out"></form>
                </div>          <!-- nested block ends -->
                <div class="bendl"></div>
                <div class="bendr"></div>
                </div>

	<!-- Change password -->
                <div class="block" style="clear:none;">
                 <div class="block_head">
                  <div class="bheadl"></div>
                  <div class="bheadr"></div>
                  <h2>Change Password</h2>
                 </div>
		<div class="block_content" style="padding:10px;">
		<ul><li><font color="">Note: You will be redirected to login on successful completion of a password change</font></li></ul>
		<form action="/accountdetails" method="post"><input type="hidden" name="act" value="updatePassword">
		<table>
			<tr><td>Current Password: </td><td><input type="password" name="currentPassword"></td></tr>
			<tr><td>New Password: </td><td><input type="password" name="newPassword"></td></tr>
			<tr><td>New Password Repeat: </td><td><input type="password" name="newPassword2"></td></tr>
			<tr><td>4 digit PIN: </td><td><input type="password" name="authPin" size="4"	maxlength="4"></td></tr>
		</table>
		<input type="submit" class="submit long" value="Change Password"></form>
                </div>          <!-- nested block ends -->
                <div class="bendl"></div>
                <div class="bendr"></div>
                </div>

          </div>          <!-- .sidebar_content ends -->

        </div>          <!-- .block_content ends -->

   <div class="bendl"></div>
   <div class="bendr"></div>

</div>          <!-- .block ends -->

<?php include ("includes/templates/footer.php");?>
