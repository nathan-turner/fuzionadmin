<?php
    require("globals.php5");
    require("cookies.php5");

	$style = new OperPage('DB Manager Reports',$UUID,'reports','managerrpts');
	$style->Output();

	if( $UUID ) {
?>
              <h1>DB Manager Reports</h1>
              <p>Assorted Quality Control reports.</p>
              <ul>
<?php 
	}
	if( $ACCESS >= 50 ) {
?>
                <li><a href="summarpt.php?mode=both&amp;range=W">Summary New or Updated Records Report</a></li>
                <!--<li><a href="summarpt.php?mode=very&amp;range=W">Summary Verified Report</a></li>-->
                <li style="margin-top: 6px "><a href="updatedrpt.php"><strong>Verification Report</strong></a> - Interview completed</li>
                <li><a href="pendingrpt.php">Pendings Report</a> - By date</li>
                <li><a href="review.php">Incomplete records</a> - Un-verified</li>
                <li><a href="dubinarpt.php">Suspended &amp; Inactive</a> records - Summary</li>
                <li><a href="pcarrpt.php">PhysicianCareer.com</a> records by date range</li>
                <li><a href="hotdocs.php">PhysicianCareer.com</a> weekly report</li>
<?php
	if( $ACCESS >= 400 ) echo '<li><a href="dataentryrpt.php">Data Entry</a> report</li>';
?>
              </ul>
<?php	}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>