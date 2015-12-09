<?php
    require("globals.php5");
	define(PG_SIZE,50);
    require("cookies.php5");
    // $UUID <> 0 if auth
	$mesg = '';
	$mode = $_REQUEST['mode']; // very or int or both
	// 7 days, 4 weeks, 12 months, years
	$range = $_REQUEST['range'];
	$rx = $_REQUEST['x'];
	if( !$rx || !is_numeric($rx) ) $rx = 0;
	if( $UUID && $ACCESS >= 50 ) try {
		// it shows summary - always, and details if requested.
		$db = db_clients();
		$opers = array();
		// inter-db joins are not possible
		$result = $db->query("select uid,firstname,lastname,username,access,status from operators");
		if( !$result || !$result->num_rows ) throw new Exception('Who am I? Who are you?',__LINE__);
		while( $oper = $result->fetch_object() )
			$opers[$oper->uid] = $oper->username;
		$opers[0] = 'Self-Registered';
		$result->free();
		//$resdb0 = db_career();
		// mode
		$modex = ' and pending=1 '; $vmodex = 'Pending (not verified)';
		if( $mode == 'very' ) { $modex = ' and pending=0 ';  $vmodex = 'Verified'; }
		elseif( $mode == 'both' ) { $modex = ' and pending<=1 ';  $vmodex = 'Both Completed or Verified'; }
		elseif( $mode == 'undo' ) { $modex = ' and pending=2 ';  $vmodex = 'New or Un-Verified'; }
		// range
		$rangex = ''; $rangf = ', 1 as range'; $vrange = 'Overall';
		$rangr = ' group by uid_saved, range order by uid_saved, range';
		$detex = '';
		$weekd = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');
		$monn = array('Mon','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
		if( $range == 'Y' ) {
			$rangf = ', year(iv_date) as range'; $vrange = 'Annual';
			$detex = " and year(iv_date)=$rx ";
			$hilite = date('Y');
		}
		elseif( $range == 'M' ) { // 1 year back
			$rangf = ', month(iv_date) as range'; $vrange = 'Monthly';
			$rangex = ' and iv_date > date_sub(curdate(), interval 1 year)';
			$detex = " and month(iv_date)=$rx ";
			$hilite = date('n');
		}
		elseif( $range == 'W' ) { // 4 weeks back
			$rangf = ', cast((to_days(curdate())-to_days(iv_date))/7 as signed) as range'; $vrange = 'Weekly';
			$rangex = ' and iv_date > date_sub(curdate(), interval 1 month)';
			$detex = " and cast((to_days(curdate())-to_days(iv_date))/7 as signed)=$rx ";
			$hilite = 0;
		}
		elseif( $range == 'D' ) { // 1 week back
			$rangf = ', weekday(iv_date) as range'; $vrange = 'Daily';
			$rangex = ' and iv_date > date_sub(curdate(), interval 7 day)';
			$detex = " and weekday(iv_date)=$rx ";
			$hilite = date('N')-1;
		}
		// details: for user and just updated or rejected
		$detail = $_REQUEST['u'];
		$action = $_REQUEST['a'];
		if( $action == 'go' && is_numeric($detail) ) {
			//if( $opers[$detail]->access > $ACCESS ) throw new Exception('Access Denied',__LINE__);
			// lid 244
			$db->query("delete from custlistsus where owneruid = $UUID and listid=244");
			$sql = "insert into custlistsus select $UUID,ph_id,244 from physicians"
				." where uid_saved = $detail and inactive=0 $modex $rangex $detex";
			$docs = $db->query($sql);
			if( !$docs ) throw new Exception(DEBUG?$db->error." : $sql":'Can not get into details',__LINE__);
			if( ! $db->affected_rows ) throw new Exception(DEBUG?$db->error." : $sql":'No modified records',__LINE__);
			if( $range == 'Y' ) $strr = $rx;
			elseif( $range == 'M' ) $strr = $monn[$rx];
			elseif( $range == 'W' ) $strr = date('n/d',time()-$rx*7*24*3600);
			elseif( $range == 'D' ) $strr = $weekd[$rx];
			else $strr = "total";
			$verboz = $vmodex.' records, last completed by '.
					$opers[$detail]." - $vrange ($strr)";
			$_SESSION['verboz244'] = $verboz; 
			header("Location: results.php?id=244&ck=1&y=2005");
			exit;
		}
		$sql = "select uid_saved$rangf,count(ph_id) as cnt from physicians"
				." where inactive=0 $modex $rangex $rangr";
		$res = $db->query($sql);
		if( !$res ) throw new Exception(DEBUG?$db->error.": $sql":'Can not get summary stats',__LINE__);
		// ok: $opers and results.
	}
	catch(Exception $e) {
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
	}
	$style = new OperPage("$vmodex Records Summary",$UUID,'reports','managerrpts');
	$sty = "<style type=\"text/css\"><!--\r\n.hilitee {\r\n	background-color:#CFEEDD;\r\n}\r\n-->\r\n</style>\r\n";
	$style->Output($sty);
 
	if( $UUID ) {
			if( $ACCESS < 50 ) echo '<h1>Access Denied</h1>';
			else {
?>
              <h1><?php echo "$vrange $vmodex"; ?> Records Summary</h1>
<?php 
			if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
            <p class="onscreen">This report shows all records that were <?php echo $vmodex; ?>, per data entry, during specified period. Click on a number below to see relevant records.</p>
            <p class="onscreen">Select report here: 
            <?php 
			if( $mode ) echo "<a style='text-decoration:underline' href='summarpt.php?mode=&range=$range'>Interview Completed (not verified) Summary</a>";
			if( $mode != 'very' ) echo ($mode?', ':'')."<a style='text-decoration:underline' href='summarpt.php?mode=very&range=$range'>Verified Summary</a>";

			if( $mode != 'both' ) echo ", <a style='text-decoration:underline' href='summarpt.php?mode=both&range=$range'>Interview Completed (verified or not)</a>";
			if( $mode != 'undo' ) echo ", <a style='text-decoration:underline' href='summarpt.php?mode=undo&range=$range'>Un-Verified Summary</a>";
?>			</p>
            <p class="onscreen">Select period here: 
            <?php 
			if( $range != 'D' ) echo "<a style='text-decoration:underline' href='summarpt.php?mode=$mode&range=D'>Last 7 days</a>, ";
			else echo "Last 7 days (current), ";
			if( $range != 'W' ) echo "<a style='text-decoration:underline' href='summarpt.php?mode=$mode&range=W'>Last 4-5 weeks</a>, ";
			else echo "Last 4-5 weeks (current), ";
			if( $range != 'M' ) echo "<a style='text-decoration:underline' href='summarpt.php?mode=$mode&range=M'>Last 12 months</a>, ";
			else echo "Last 12 months (current), ";
			if( $range != 'Y' ) echo "<a style='text-decoration:underline' href='summarpt.php?mode=$mode&range=Y'>Annual Totals</a></p>";
			else echo "Annual Totals (current)</p>";
		$totalu = 0; $olduid = 0; $minr = 9999; $maxr = 0;
		// arrays my friends
		$rows = array();
		for( $i=0; $i < $res->num_rows; $i++ ) {
			$row = $res->fetch_assoc();
			$r = $row['range'];
			if( $r > $maxr ) $maxr = $r;
			if( $r < $minr && !is_null($r) ) $minr = $r;
			$rows[$i] = $row;
		}
		$res->free();
?>
              <table border=1 cellpadding="1" cellspacing="0">
                <tr>
                  <th>User</th>
<?php
		if( $range == 'Y' ) echo "<th>N/A</th>";
		for( $j=$minr; $j <= $maxr; $j++ ) {
			if( $range == 'Y' ) $strr = $j;
			elseif( $range == 'M' ) $strr = $monn[$j];
			elseif( $range == 'W' ) $strr = date('n/d',time()-$j*7*24*3600);
			elseif( $range == 'D' ) $strr = $weekd[$j];
			else $strr = "Data";
			echo "<th>$strr</th>";
		}
?>                  
                  <th align="right">Total</th>
                </tr>
<?php
		$olduid = -1; $yerfactor = $range == 'Y'? 1: 0;
		foreach( $rows as $row ) {
			$uidmod = $row['uid_saved']; $r = $row['range']; $cnt = $row['cnt'];
			//if( !$opers[$uidmod]->status ) continue; // active emps only
			if( $olduid != $uidmod ) {
				if( $olduid != -1 ) {
					while( $oldr < $maxr ) {
						$oldr++; echo "<td".($oldr==$hilite?' class="hilitee"':'').">&nbsp;</td>"; 
					}
					echo "<td align='right'>$totalr</td></tr>\r\n";
				}
				$olduid = $uidmod;
				echo '<tr><td>';
						echo is_null($uidmod)?'Unknown':$opers[$uidmod]; 
			  	//if( !$opers[$uidmod]->status ) echo "<em>";
				//echo $opers[$uidmod]->firstname.' '.$opers[$uidmod]->lastname;
			  	//if( !$opers[$uidmod]->status ) echo "</em>";
				echo '</td>';
				$totalr = 0; $oldr = $minr-1-$yerfactor;
			}
			if( !is_null($r) )  while( $r > $oldr+1 ) { // null makes sense only for Y range
				$oldr++; echo "<td".($oldr==$hilite?' class="hilitee"':'').">&nbsp;</td>"; 
			}
			if( !is_null($r) ) $oldr = $r; else $oldr = $minr-1;
			echo "<td".($oldr==$hilite?' class="hilitee"':'').">";
			echo "<a href='summarpt.php?u=$uidmod&a=go&mode=$mode&range=$range&x=$r'>";
			echo "$cnt"; $totalu += $cnt; $totalr += $cnt; 
			echo "</a>";
			?></td>
<?php 	}
		if( $olduid ) {
			while( $oldr < $maxr ) { $oldr++; echo "<td".($oldr==$hilite?' class="hilitee"':'').">&nbsp;</td>"; }
			echo "<td align='right'>$totalr</td></tr>\r\n";
		}
?>
                <tr>
                  <td>Total</td>
                  <td align="right" colspan="<?php echo $maxr-$minr+2+$yerfactor; ?>"><?php echo $totalu; ?></td>
                </tr>
              </table>
<?php 
		echo "<p><span class='smalcap'>".date('r')."</span></p>";
			} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
           
