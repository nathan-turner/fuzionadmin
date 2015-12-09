<?php
	// all fixed - done 3/27/07 - SL
    require("globals.php5");
    require("cookies.php5");
    // $UUID <> 0 if auth
	$style = new OperPage('Home',$UUID,'index','');
	$scrip = <<<TryMe

var subwind;

function poptest() {
  subwind = window.open("","emailcheck","menubar=0,toolbar=0,width=350,resizable=0,location=0,height=300");
  setTimeout("popshow()",60);
  document.form1.popte.disabled = true;
  return true;
}

function popshow() {
	var d = subwind.document;
	d.write("<html><head><title>Success</title></head><body><h1>Success</h1><p>Test Passed</p>",
		"<p><a href='javascript:window.close()'>Close Window</a></p></body></html>");
	d.close();
	subwind.focus();
}

TryMe;
	$scrip2 = "<script language=\"JavaScript\" type=\"text/JavaScript\"><!--\n".$scrip."// -->\n</script>\n";
	$style->Output($scrip2);
	if( $UUID ) {
?>
              <h1>Welcome<?php
		 if( isset($USER->logip) ) echo " back";
		 echo ', '.$USER->firstname.'!</h1>';
		 if( isset($USER->logip) ) { 
		 	echo "<p>Your last logon was at $USER->logdate from IP address $USER->logip. ".
				"Your current IP address is $REMOTE_ADDR. Please remember to log off when finished.</p>";
		 }
		 else $newuser = true;
		 ?>
              <p>Use the navigation bar above<?php echo $newuser?' to feel your way around here':' and common task links below'; ?>. You can view your profile and change your password <a href="oprofile.php">here</a>. You can see your custom lists <a href="custlists.php">here</a>.</p>
              <h3>Your access level is <?php echo AccessDesc($ACCESS); ?>.</h3>
<?php if( $newuser ) { ?>
              <p class="tdborder3399"><strong><em>Note</em></strong>: Javascript support required. If you disabled javascript in your browser, please enable it for this site. This site can show popups with important notices. If you use any popup blockers, please put this site into the 'Allow List'. You can test both Javascript and Popup features below. </p>
              <form name="form1" method="post" action="index.php">
                <label>JS & Popup Test: 
                <input name="popte" type="text" id="popte" onFocus="poptest()" value="Click here to test. A window should pop up." size="50">
                <input type="button" name="Button" value="Try again" onClick="popte.disabled = false;">
</label>
              </form>
<?php } // newuser
	else {	// not new user.
		echo '<p>Common Tasks:</p><ul>';
		if( !isset($db) ) $db = db_career();
		$sql = "select count(*) from notes where year = 486 and res_id = 1 and shared <= $ACCESS and dt >= '$USER->logdate'";
		$result = $db->query($sql);
		if( $result ) list($news) = $result->fetch_row();
		if( $ACCESS < 50 ) {
			//1-49: Follow-up report for user, review list
			// put here code to calculate number of records to follow-up and review - number only.
			// 1) follow_up is not null and follow_up <= curdate and uid_updated = $UUID
			// 2) updated=0 and checkin=0 and uid_updated = $UUID
		try {
			$totf = 0; $totu = 0;
			//foreach( $ResYears as $yer ) {
				$sql = 'select count(*) from physicians where pending=2 and inactive=0 ';
				$result = $db->query($sql);
				list($upd) = $result->fetch_row(); $totu += $upd;
				$result->free();
			//}
		}
		catch(Exception $e) {
			if( DEBUG ) echo '<p id="error_msg">'.$e->getMessage().'</p>';
		}
?>
              <li>Work with <a href="custlists.php">Assigned lists</a> and saved search results;</li>
              <li><a href="review.php">Review List</a> of records that did not pass verification<?php echo $totu?" <em>($totu pending)</em>":''; ?>.</li>
              <li><a href="news.php">Check Database News<?php if($news) { ?>
			  <img src="images/news.png" border="0" alt="(new)" title="<?php echo $news; ?> issues" />
			  <?php } ?></a>.</li>
              <?php	
		}
		elseif( $ACCESS < 200 ) {
			//50-199: Updated records, verification, new residents
			// 2) updated=0 and checkin=0 and uid_updated = $UUID
		try {
			$totf = 0; $totu = 0; $totr = 0;
			//foreach( $ResYears as $yer ) {
				$sql = 'select count(*) from physicians where pending=1 and  status=1 and inactive=0 ';
				$result = $db->query($sql);
				list($upd) = $result->fetch_row(); $totu += $upd;
				$result->free();
				$sql = 'select count(*) from physicians where pending=2 and inactive=0 ';
				$result = $db->query($sql);
				list($upd) = $result->fetch_row(); $totr += $upd;
				$result->free();
			//}
		}
		catch(Exception $e) {
			if( DEBUG ) echo '<p id="error_msg">'.$e->getMessage().'</p>';
		}
?>
              <li>Work with <a href="custlists.php">lists</a> and saved search results;</li>
              <li>Check and verify <a href="updatedrpt.php">Pending records</a><?php echo $totu?" <em>($totu pending)</em>":''; ?> and read notes;</li>
              <li><a href="review.php">Review List</a> of records that did not pass verification<?php echo $totr?" <em>($totr pending)</em>":''; ?>.</li>
              <li><a href="news.php">Check Database News<?php if($news) { ?>
			  <img src="images/news.png" border="0" alt="(new)" title="<?php echo $news; ?> issues" />
			  <?php } ?></a>.</li>
              <?php	
		}
		elseif( $ACCESS < 300 ) {
			//200-299: customer service
?>
              <li>Search and modify <a href="custsearch.php">Customer records</a>;</li>
              <li>Analyze <a href="reports.php">Reports</a>.</li>
              <li><a href="news.php">Check Database News<?php if($news) { ?>
			  <img src="images/news.png" border="0" alt="(new)" title="<?php echo $news; ?> issues" />
			  <?php } ?></a>.</li>
              <?php	
		}
		elseif( $ACCESS < 400 ) {
			//300-399: account manager
?>
              <li>Search and modify <a href="custsearch.php">Customer records</a>;</li>
              <li>Register <a href="custnew.php">new customers</a>;</li>
              <li>Analyze <a href="reports.php">Reports</a>.</li>
              <li><a href="news.php">Check Database News<?php if($news) { ?>
			  <img src="images/news.png" border="0" alt="(new)" title="<?php echo $news; ?> issues" />
			  <?php } ?></a>.</li>
              <?php	
		}
		else {
			//400-500: Administration, reports
			// 2) updated=0 and checkin=0 and uid_updated = $UUID
		try {
			$totf = 0; $totu = 0; $totr = 0;
			//foreach( $ResYears as $yer ) {
				$sql = 'select count(*) from physicians where pending=1 and  status=1 and inactive=0 ';
				$result = $db->query($sql);
				list($upd) = $result->fetch_row(); $totu += $upd;
				$result->free();
				$sql = 'select count(*) from physicians where pending=2 and inactive=0 ';
				$result = $db->query($sql);
				list($upd) = $result->fetch_row(); $totr += $upd;
				$result->free();
			//}
		}
		catch(Exception $e) {
			if( DEBUG ) echo '<p id="error_msg">'.$e->getMessage().'</p>';
		}
?>
              <li><a href="news.php">Check Database News<?php if($news) { ?>
			  <img src="images/news.png" border="0" alt="(new)" title="<?php echo $news; ?> issues" />
			  <?php } ?></a>.</li>
              <li>Analyze <a href="reports.php">Reports</a>;</li>
              <li>Add and modify <a href="editoper.php">users</a>;</li>
              <li>Search and modify <a href="custsearch.php">Customer records</a>;</li>
              <li>Register <a href="custnew.php">new customers</a>;</li>
              <li><a href="shadow.php">Search</a> and <a href="shadowass.php">Assign</a> new lists;</li>
              <li>Check and verify <a href="updatedrpt.php">Pending records</a><?php echo $totu?" <em>($totu pending)</em>":''; ?> and read notes;</li>
              <li><a href="review.php">Review List</a> of records that did not pass verification<?php echo $totr?" <em>($totr pending)</em>":''; ?>.</li>
              <?php	
		}
		echo '</ul>';
			} // newuser
		}
		else showLoginForm(); // UUID
	$style->ShowFooter();
?>
