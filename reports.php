<?php
    require("globals.php5");
    require("cookies.php5");
	$style = new OperPage('Reports',$UUID,'reports','');
	$style->Output();

	if( $UUID ) {
?>
              <h1>Reports</h1>
              <p>Assorted reports, Statistics, Quality Control features and Data Export. Some reports are available only to certain access levels.</p>
              <ul>
                <li><a href="hotdocs.php">PhysicianCareer.com</a> weekly report</li>
                <li><a href="residentstats.php">Physician Database</a> Statistics by specialty</li>
				<li><a href="review.php">Review List</a></li>
				
<?php 
	if( $ACCESS >= 200 ) {
?>
                <li style="margin-top: 6px "><a href="customerstats.php">Customers Statistics</a> &amp; Expiration, <a href="customeract.php">Customer Activity</a> Report</li>
                <li><a href="export.php">Data Export</a> tools</li>
				<li>Job board <a href="jopprpt.php">Opportunities</a> Report</li>
				<li><a href="nexthotdoc.php">Tomorrow's HotDoc List</a></li>
<?php 
	}
	if( $ACCESS >= 50 ) {
?>
                <li style="margin-top: 6px "><a href="managerrpts.php">DB Manager Reports</a> are on a separate page</li>
<?php 
	}
	if( $ACCESS >= 450 ) echo '<li style="margin-top: 6px "><a href="iplog.php">Login IP Log</a></li>';
?>
              </ul>
			  <br/>
			  <br/>
			  <ul>
			  <li><a href="reverse_search.php" target="_blank">Send Reverse Search Email</a></li>
			  </ul>
<?php	}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>