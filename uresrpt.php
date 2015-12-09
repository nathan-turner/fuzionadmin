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
			$opers[$oper->uid] = $oper;
		$result->free();
		//$YearRes = array();
		//$resdb0 = db_career();
		//array_push($ResYears, 2006);
		//foreach( $ResYears as $yer ) {
		// summary first
			$sql = "select uid_saved,count(ph_id) as cnt from physicians where uid_saved is not null and pending = 1 and inactive = 0 group by uid_saved order by uid_saved";
			$YearRes = $db->query($sql);
			if( !$YearRes ) throw new Exception(DEBUG?$db->error.": $sql":'Can not get summary stats',__LINE__);
		//}
		// ok: $opers and results.
		// details: for user and just updated or rejected
		$detail = $_REQUEST['u'];
		if( $detail && is_numeric($detail) ) {
			if( $opers[$detail]->access > $ACCESS ) throw new Exception('Access Denied',__LINE__);
			//$dyear = $_REQUEST['y'];
			//if( !$dyear || !is_numeric($dyear) ) $dyear = $ResYears[0];
			// lid 247
			$db->query("delete from custlistsus where owneruid = $UUID and listid=247");
			$sql = "insert into custlistsus select $UUID,ph_id,247 from physicians"
				." where uid_saved = $detail and pending = 1 and inactive = 0";
			$docs = $db->query($sql);
			if( !$docs ) throw new Exception(DEBUG?$db->error." : $sql":'Can not get into details',__LINE__);
			if( ! $db->affected_rows ) throw new Exception('No modified records',__LINE__);
			$verboz = 'Records, last updated by '.
					$opers[$detail]->username;
			$_SESSION['verboz247'] = $verboz;
			header("Location: results.php?id=247&pg=0&y=2005");
			exit;
		}
		
	}
	catch(Exception $e) {
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
	}
	$style = new OperPage('Pending Physician Records',$UUID,'reports','managerrpts');
	$style->Output();

	if( $UUID ) {
			if( $ACCESS < 50 ) echo '<h1>Access Denied</h1>';
			else {
?>
              <h1>Pending Physician Records</h1>
              <?php 
			if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
              <p>This report shows all records that were newly entered by data entry, but were not marked as Completed. Click on a number to see relevant records. </p>
              <?php 
		//foreach( $ResYears as $yer ) {
			//echo "<h3>".($yer==2006?"Practicing":"Year $yer")."</h3>";
?>
              <table width="80%" border="1" cellpadding="1" cellspacing="0">
                <tr>
                  <th>User</th>
                  <th>Modified</th>
                </tr>
<?php 
		$totalu = 0;
		for( $i=0; $YearRes && $i < $YearRes->num_rows; $i++ ) {
			list($uidmod,$cnt) = $YearRes->fetch_row();
?>
                <tr>
                  <td><?php 
				  	if( !$opers[$uidmod]->status ) echo "<em>";
				  	echo $opers[$uidmod]->username; 
				  	if( !$opers[$uidmod]->status ) echo "</em>";
					?></td>
                  <td><?php 
				  	if( $cnt && $opers[$uidmod]->access <= $ACCESS ) echo "<a href='uresrpt.php?u=$uidmod&y=$yer#rec'>";
				  	echo $cnt; $totalu += $cnt; 
				  	if( $cnt && $opers[$uidmod]->access <= $ACCESS ) echo "</a>";
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
//		} // foreach
			} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
