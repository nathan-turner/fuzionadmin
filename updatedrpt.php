<?php
    require("globals.php5");
	define(PG_SIZE,50);
    require("cookies.php5");
    // $UUID <> 0 if auth
	$mesg = '';
	if( $UUID && $ACCESS >= 50 ) try {
		// it shows summary - always, and details if requested.
		$db = db_career();
		$opers = array();
		// inter-db joins are not possible
		$result = $db->query("select uid,username,access,status from operators");
		if( !$result || !$result->num_rows ) throw new Exception('Who am I? Who are you?',__LINE__);
		while( $oper = $result->fetch_object() )
			$opers[$oper->uid] = $oper->username;
		$opers[0] = 'Self-Registered';
		$result->free();
		//$resdb0 = db_career();
		// details: for user and just updated or rejected
		$detail = $_REQUEST['u'];
		$action = $_REQUEST['a'];
		if( $action == 'go' && is_numeric($detail) ) {
			/*if( $opers[$detail]->access > $ACCESS ) throw new Exception('Access Denied',__LINE__);*/
			$page = $_REQUEST['pg'];
			if( !$page || !is_numeric($page) ) $page = 0;
			$targ = $_REQUEST['to'];
			if( !$targ || !is_numeric($targ) ) unset($targ);
			$sql = "select count(*) from physicians where uid_saved = $detail and pending=1 and  status=1 and inactive=0 "; 
			$res = $db->query($sql);
			if( $res ) list($totalcount) = $res->fetch_row();
			else $totalcount = 0;
			if( $totalcount ) { // create list id 250
				$verboz = "Pending Records from ".$opers[$detail];
				$db->query("delete from custlistsus where listid = 250 and owneruid = $UUID");
				$db->query("delete from custlistdesc where listid = 250 and uid = $UUID");
				$db->query("insert into custlistdesc values ($UUID,250,2005,'"
					.$verboz." for verification','verify',0,$ACCESS,NULL)");
				$sql = "insert into custlistsus select $UUID,ph_id,250 from physicians"
					." where uid_saved = $detail and pending=1 and  status=1 and inactive=0"; 
				$docs = $db->query($sql);
				if( !$docs ) throw new Exception(DEBUG?$db->error." : $sql":'Can not get into details',__LINE__);
				if( ! $db->affected_rows ) throw new Exception('No pending records?!!',__LINE__);
				$_SESSION['verboz250'] = $verboz;
				$_SESSION['verification'] = true; // lid must be 250 also
				$redir = $targ?"showdocpc.php?id=$targ&lid=250&ck=&y=2005&pos=":"results.php?id=250&pg=0&y=2005";
				header("Location: $redir");
				exit;
			}
			elseif( $targ ) {
				$redir = "showdocpc.php?id=$targ&lid=0&y=2005&pos=";
				header("Location: $redir");
				exit;
			}
		}
		// summary first
		$sql = 'select uid_saved,count(ph_id) as cnt from physicians'
			." where pending=1 and status=1 and inactive=0  group by uid_saved order by uid_saved";
		$YearRes = $db->query($sql);
		if( !$YearRes ) throw new Exception(DEBUG?$db->error.": $sql":'Can not get summary stats',__LINE__);
		// ok: $opers and results.
	}
	catch(Exception $e) {
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
	}
	$style = new OperPage('Pendings Report',$UUID,'reports','updatedrpt');
	$style->Output();
	
	if( $UUID ) {
			if( $ACCESS < 50 ) echo '<h1>Access Denied</h1>';
			else {
?>
			  <h2>Verification/Pendings Report</h2>
			  <p>This report shows all the records that were marked as Interview Completed, <em>'Pending'</em>. Before our customers can see them, a Database Manager have to verify data quality. Please browse through the records in the list and either Verify them, or send them to data entry by checking Need more info checkbox. You can correct other fields as well, if necessary. Click on a number in summary table to see relevant records.</p>
              <p><em><strong>Note</strong></em>: Your goal is to ZERO all numbers in following tables by either approving - verifying, or by rejecting - sending to get more info. Pending records expire in 30 days. </p>
              <?php 
			if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
              <table width="80%" border="1" cellpadding="1" cellspacing="0">
                <tr>
                  <td width="70%" bgcolor="#CCCCCC">User</td>
                  <td width="30%" bgcolor="#CCCCCC">Completed</td>
                </tr>
<?php 
		$totalu = 0;
		for( $i=0; $YearRes && $i < $YearRes->num_rows; $i++ ) {
			list($uidmod,$cnt) = $YearRes->fetch_row();
?>
                <tr>
                  <td><?php 
						echo $opers[$uidmod]; 
					?></td>
                  <td><?php 
				  	/*if( $opers[$uidmod]->access <= $ACCESS )*/ echo "<a href='updatedrpt.php?u=$uidmod&a=go#rec'>";
				  	echo $cnt; $totalu += $cnt; 
				  	/*if( $opers[$uidmod]->access <= $ACCESS )*/ echo "</a>";
					?></td>
                </tr>
<?php 	}
		$YearRes->free();
?>
                <tr>
                  <td>Total</td>
                  <td><?php echo $totalu; ?></td>
                </tr>
              </table>
<?php 
			} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
