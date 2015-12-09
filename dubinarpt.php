<?php
    require("globals.php5");
	define(PG_SIZE,50);
    require("cookies.php5");
	$mesg = '';
	if( $UUID && $ACCESS >= 50 ) try {
		// it shows summary - always, and details if requested.
		$db = db_career();
		$opers = array();
		// inter-db joins are not possible
		$result = $db->query("select uid,username,access,status from operators");
		if( !$result || !$result->num_rows ) throw new Exception('Who am I? Who are you?',__LINE__);
		while( $oper = $result->fetch_object() )
			$opers[$oper->uid] = $oper;
		$opers[0] = '(unknown)';
		$result->free();
		//$resdb = db_career();
			// summary first
			$sql = 'select uid_saved,sum(inactive) as cnt, sum(1-status) as dup from physicians'
				." where inactive=1 or status = 0 group by uid_saved order by uid_saved";
			$YearRes = $db->query($sql);
			if( !$YearRes ) throw new Exception(DEBUG?$db->error.": $sql":'Can not get summary stats',__LINE__);
		// ok: $opers and results.
		// details: for user and just updated or rejected
		$detail = $_REQUEST['u'];
		$action = $_REQUEST['a'];
		if( $action == 'go' && is_numeric($detail) ) {
			//if( $opers[$detail]->access > $ACCESS ) throw new Exception('Access Denied',__LINE__);
			$dup = $_REQUEST['dup'];
			$wher = $dup?'status=0':'inactive=1';
			if( !$dyear || !is_numeric($dyear) ) $dyear = $ResYears[0];
			$db->query("delete from custlistsus where owneruid = $UUID and listid=249");
			$sql = "insert into custlistsus select $UUID,ph_id,249 from physicians"
				." where uid_saved = $detail and $wher";
			$docs = $db->query($sql);
			if( !$docs ) throw new Exception(DEBUG?$db->error." : $sql":'Can not get into details',__LINE__);
			if( ! $db->affected_rows ) throw new Exception(DEBUG?$db->error." : $sql":'No modified records',__LINE__);
			$verboz = ($dup?'Inactive':'Disabled').' records, last updated by '.
					$opers[$detail]->username;
			$_SESSION['verboz249'] = $verboz;
			header("Location: results.php?id=249&pg=0");
			exit;
		}
		
	}
	catch(Exception $e) {
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
	}
	$style = new OperPage('Suspended or Inactive Records',$UUID,'reports','dubinarpt');
	$style->Output();

	if( $UUID ) {
			if( $ACCESS < 50 ) echo '<h1>Access Denied</h1>';
			else {
?>
              <h1>Suspended or Inactive records</h1>
              <?php 
			if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
              <p>This report shows all records that were marked as Suspended or Inactive. Click on a number to see relevant records. </p>
              <table width="80%" border="1" cellpadding="1" cellspacing="0">
                <tr>
                  <th width="50%">User</th>
                  <th width="25%">Suspended</th>
                  <th width="25%">Inactive</th>
                </tr>
<?php 
		$totalu = 0; $totald = 0;
		for( $i=0; $YearRes && $i < $YearRes->num_rows; $i++ ) {
			list($uidmod,$cnt,$dups) = $YearRes->fetch_row();
?>
                <tr>
                  <td><?php 
				  	if( $uidmod ) {
						if( !$opers[$uidmod]->status ) echo "<em>";
						echo $opers[$uidmod]->username; 
						if( !$opers[$uidmod]->status ) echo "</em>";
					}
					else echo "Self-registered";
					?></td>
                  <td><?php 
				  	if( $dups && $opers[$uidmod]->access <= $ACCESS ) echo "<a href='dubinarpt.php?u=$uidmod&a=go&dup=1#rec'>";
				  	echo $dups; $totald += $dups; 
				  	if( $dups && $opers[$uidmod]->access <= $ACCESS ) echo "</a>";
					?></td>
                  <td><?php 
				  	if( $cnt && $opers[$uidmod]->access <= $ACCESS ) echo "<a href='dubinarpt.php?u=$uidmod&a=go#rec'>";
				  	echo $cnt; $totalu += $cnt; 
				  	if( $cnt && $opers[$uidmod]->access <= $ACCESS ) echo "</a>";
					?></td>
                </tr>
<?php 	}
		$YearRes->free();
?>
                <tr>
                  <td>Totals</td>
                  <td><?php echo $totald; ?></td>
                  <td><?php echo $totalu; ?></td>
                </tr>
              </table>
<?php 
			} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
