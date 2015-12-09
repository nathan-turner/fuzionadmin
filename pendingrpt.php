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
		// prepare list 250 just in case
			$sql = "select count(*) from physicians where pending=1 and  status=1 and inactive=0"; 
			$res = $db->query($sql);
			if( $res ) list($totalcount) = $res->fetch_row();
			else $totalcount = 0;
			if( $totalcount ) { // create list id 250
				$verboz = 'All Pending Records';
				$db->query("delete from custlistsus where listid = 250 and owneruid = $UUID");
				$db->query("delete from custlistdesc where listid = 250 and uid = $UUID");
				$db->query("insert into custlistdesc values ($UUID,250,2005,'$verboz for verification','verify',0,$ACCESS,NULL)");
				$sql = "insert into custlistsus select $UUID,ph_id,250 from physicians where pending=1 and status=1 and inactive=0"; 
				$docs = $db->query($sql);
				if( !$docs ) throw new Exception(DEBUG?$db->error." : $sql":'Can not create verification list',__LINE__);
				if( ! $db->affected_rows ) throw new Exception('No pending records?!!',__LINE__);
				$_SESSION['verboz250'] = $verboz;
				$_SESSION['verification'] = true; // lid must be 250 also
				//$redir = $targ?"showdocpc.php?id=$targ&lid=250&ck=&y=2005&pos=":"results.php?id=250&pg=0&y=2005";
			}
		// summary first
		$sql = "select ph_id,uid_saved,uidmod,fname,lname,spec,pdate from physicians join pendings on ph_id=phid where pending=1 and status=1 and inactive=0 order by pdate, uid_saved";
		$YearRes = $db->query($sql);
		if( !$YearRes ) throw new Exception(DEBUG?$db->error.": $sql":'Can not get pendings report',__LINE__);
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
			  <h2>Pendings Report by Date </h2>
			  <p>This report shows all the records that were marked as Interview Completed, <em>'Pending'</em>. Before our customers can see them, a Database Manager have to verify data quality. Please browse through the records in the list and either Verify them, or send them to data entry by checking Need more info checkbox. You can correct other fields as well, if necessary.</p>
              <p><strong><em>Note</em>: Pending records will expire and become inactive in 30 days.</strong> </p>
              <?php 
			if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
              <table width="80%" border="1" cellpadding="1" cellspacing="0">
                <tr>
                  <td bgcolor="#CCCCCC">Physician</td>
                  <td bgcolor="#CCCCCC">Spec</td>
                  <td bgcolor="#CCCCCC">Users</td>
                  <td bgcolor="#CCCCCC">Pending Since</td>
                </tr>
<?php 
		$totalu = 0;
		for( $i=0; $YearRes && $i < $YearRes->num_rows; $i++ ) {
			$pend = $YearRes->fetch_object(); //ph_id,uid_saved,uidmod,fname,lname,spec,pdate
?>
                <tr>
				  <td><?php echo "<a href='showdocpc.php?id=$pend->ph_id&lid=250&pos='>$pend->fname $pend->lname</a>"; ?></td>
				  <td><?php echo $pend->spec; ?></td>
                  <td><?php echo $opers[$pend->uid_saved]; if( $pend->uidmod != $pend->uid_saved ) echo '/'.$opers[$pend->uidmod]; ?></td>
                  <td><?php 
				  	$totalu ++; echo $pend->pdate;
					?></td>
                </tr>
<?php 	}
		$YearRes->free();
?>
                <tr>
                  <td colspan="3">Total</td>
                  <td><?php echo $totalu; ?></td>
                </tr>
              </table>
<?php 
			} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
