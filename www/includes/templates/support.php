<div class="block withsidebar">

	<div class="block_head">
		<div class="bheadl"></div>
		<div class="bheadr"></div>

                <h2>Welcome,
                <?php
                if($cookieValid) {

                        echo $userInfo->username . " ";

                        $account_type = 0;
                        $account_type = account_type($userInfo->id);

                        if ($account_type == 9) {
                                $account_type = "<b>Early-Adopter</b>: <b>0%</b> Pool Fee";
                        } else {
                                $account_type = "<b>Active Account</b>: <b>" .$settings->getsetting("sitepercent"). "%</b> Pool Fee";
                        }

                        echo "<font size='1px'>" .$account_type."</font> ";
                        echo "<font size='1px'><i>(You are <a href='/osList'>donating</a> <b></i>" .antiXss($donatePercent)."%</b> <i>of your earnings)</i></font>";
                } else {
                        echo "Guest";
                }
                ?>
                </h2>
	</div>		<!-- .block_head ends -->




	<div class="block_content">

		<div class="sidebar">
			<?php include ("includes/leftsidebar.php"); ?>
		</div>		<!-- .sidebar ends -->


		<div class="sidebar_content" id="sb1">

                <div class="block" style="clear:none;">
                 <div class="block_head">
                  <div class="bheadl"></div>
                  <div class="bheadr"></div>
                  <h2>Contact & Support</h2>
                 </div>

                 <div class="block_content" style="padding:10px;">
                 <p>
					
					<div><h2>Mining URL:</h2><h3>&nbsp &nbsp &nbsp --</h3><h3>&nbsp &nbsp &nbsp --</h3></div>
					<br/>
					<br/>
					<div>
						<h2>Suggestions? Complaints? Need Help? </h2>
							<h3>&nbsp &nbsp &nbsp Contact me on bitcointalk.org, I'm lavajumper</h3>
					</div>

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
