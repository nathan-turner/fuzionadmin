<?php
    require("globals.php5");
    require("cookies.php5");
    // $UUID <> 0 if auth
	$redir = ''; $result = true; $mesg = '';
	$ooid = $_REQUEST['oid'];
	$uacct = $_REQUEST['acct'];
	$usid = $_REQUEST['cid'];
	$masid = $_REQUEST['mas'];
	$oopdate = $_REQUEST['update'];
	if( !is_numeric($usid) ) $usid = $UUID;
	if( !is_numeric($masid) ) $masid = 0;
	if( !is_numeric($uacct) ) $uacct = 0;
	if( !is_numeric($ooid) ) $ooid = 0;
	if( $UUID && $uacct>0 && ($ACCESS >= 200) ) try {
		$db = db_career();
		$client = $db->query("select firstname, lastname, company from clients where acct=$uacct");
		if( $client ) list($cfirst,$clast,$cco) = $client->fetch_row();
		else throw new Exception(DEBUG?"{$db->error}: $sql":'Can not replace answers', __LINE__);
		$client->free();
		
		
		// no master override yet, may be later
		if( !$ooid ) {	// list all what we have here
			if( $oopdate === "yes"  && $uacct!="") {
				$sql = "update opportunities set o_datemod=now(), avail_date=now() where status= 1 and o_acct = $uacct";
				$result = $db->query($sql); // ignore result
				$mesg = $db->affected_rows? 'Jobs were refreshed successfully': 'Nothing to Refresh';
				if( !$result ) throw new Exception(DEBUG?"$db->error: $sql":'Can not refresh opportunities',__LINE__);
			}
			if($_GET["sort"]!='')
				$sql = "select oid, o_name, o_facility, o_city, o_state, specialty, o_datemod, date_added from opportunities where status = 1 and o_acct = $uacct order by ".$_GET["sort"]."";
			else
				$sql = "select oid, o_name, o_facility, o_city, o_state, specialty, o_datemod, date_added from opportunities where status = 1 and o_acct = $uacct order by o_state, o_city, o_facility, o_name";
			$sqlhis = "select oid, o_name, o_facility, o_city, o_state, specialty, status, o_datemod, date_added from opportunities where status != 1 and o_acct = $uacct order by status, o_datemod desc, o_state, o_city, o_facility, o_name";
			$opps = $db->query($sql);
			if( !$opps ) throw new Exception(DEBUG?"$db->error: $sql":'Can not list opportunities',__LINE__);
		}
		//elseif( !$opp ) $opp = new Opportunity($db,$ooid);

	}
	catch(Exception $e) {
		$result = false;
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
	}
	if( $result && $redir ) {
		header("Location: $redir");
		exit;
	}
	$style = new OperPage('Opportunities',$UUID,'admin','opportunities');
	$scrpt = "<script type=\"text/javascript\" src=\"calendarDateInput.js\"></script>
		<script type=\"text/javascript\" src=\"/ckeditor/ckeditor.js\"></script>
		<style type=\"text/css\">
<!--
.style1 {
	color: #000099;
	font-weight: bold;
	font-style: italic;
}
#maincontent p.nodescript {
	color: black; 
}
-->
</style>";

	$style->Output($scrpt);
    if( $UUID ) {
		if( $mesg ) echo "<p id='".($result?'warning':'error')."_msg'>$mesg</p>"; 
		if( $ACCESS >= 200 ) {

	$stus = array('On Hold','Active','Placed','N/A','Deleted','N/A','N/A','N/A','Expired');

	// ooid ?>
        <h1>REFRESH Opportunities for <?php echo "$cco"; ?></h1>
        <p style="display:none" class="nodescript">You can refresh clients&rsquo; opportunities here. To manage their locations, go to <a href="locationsadmin.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid"; ?>">this page</a>.</p>
        
<?php 
		$sqlloc = "select l_id, l_facility, l_city, l_state, l_uid from locations where (l_uid = $usid or l_acct = $uacct) and status = 1 order by l_facility, l_state, l_city";
		//$locs = $db->query($sqlloc);
		
		//else echo "<p class='nodescript'>They have not entered any locations. You can enter one or more <a href=\"locationsadmin.php?cid=$usid&acct=$uacct&mas=$masid\">locations</a> for them.</p>";
?>
        <h3>Refresh Opportunities</h3>
<?php	if( $opps && $opps->num_rows ) { ?>
        	<p class="nodescript">Below is the list of all their active opportunities. Click here to refresh their jobs: <a href="refresh_jobs.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid&update=yes"; ?>">REFRESH</a></p>
			<table><thead><tr><th><a href="refresh_jobs.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid"; ?>&sort=o_name">Label</a></th><th><a href="refresh_jobs.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid"; ?>&sort=o_facility">Facility</a></th><th><a href="refresh_jobs.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid"; ?>&sort=o_city">City</a></th><th><a href="refresh_jobs.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid"; ?>&sort=o_state">State</a></th><th><a href="refresh_jobs.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid"; ?>&sort=specialty">Specialty</a></th><th><a href="refresh_jobs.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid"; ?>&sort=o_datemod">Last Edited</a></th><th><a href="opportunadmin.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid"; ?>&sort=date_added">Added</a></th></tr></thead><tbody>
<?php		while( $opp = $opps->fetch_object() ) { ?>
				<tr>
					<td><a href="opportunadmin.php?oid=<?php echo $opp->oid."&cid=$usid&acct=$uacct&mas=$masid"; ?>"><?php echo $opp->o_name?$opp->o_name:'(unlabelled)'; ?></a></td>
					<td><?php echo $opp->o_facility; ?></td>
					<td><?php echo $opp->o_city; ?></td>
					<td><?php echo $opp->o_state; ?></td>
					<td><?php echo $opp->specialty; ?></td>
					<td><?php echo $opp->o_datemod; ?></td>
					<td><?php echo $opp->date_added; ?></td>
				</tr>
<?php		} ?>
			</tbody></table>
<?php
			$opps->close();
	 	} else echo "<p class='nodescript'>They have no active opportunities. You can create one for them using the above form.</p>";
		$opps = $db->query($sqlhis);
?>
        
<?php 		} // ooid
			} // ACCESS
			else echo "<p class='nodescript'>Access Denied</p>";
		
	  //else showLoginForm();
	$style->ShowFooter();
?>
