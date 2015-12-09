<?php
    require("globals.php5");
	define(PG_SIZE,50);
    require("cookies.php5");
    // $UUID <> 0 if auth
	$mesg = '';
	if( $UUID && $ACCESS >= 450 ) try {
		// report period is 1 week (7 days) by default, possible values are 1,2 weeks, 1-12 months
		$tframe = 7; $unit = 'DAY';
		$page = $_REQUEST['pg']; 
		if( !$page || !is_numeric($page) ) $page = 0;
		if( $_REQUEST['tframe'] === 'biweek' ) $tframe = 14;
		if( $_REQUEST['tframe'] === 'yester' ) $tframe = 1;
		elseif( $_REQUEST['tframe'] === 'months' ) {
			$unit = 'MONTH'; $tframe = 1;
			if( $_REQUEST['months'] && is_numeric($_REQUEST['months']) ) $tframe = $_REQUEST['months'];
		}
		$ltd = $_REQUEST['ltd'];
		if( !$ltd || !is_numeric($ltd) ) unset($ltd);
		// do stuff
		$db = db_clients();
		$sql = 'SELECT count(*) FROM iplogscust '
		   . "WHERE logdate >= date_sub(curdate(),INTERVAL $tframe $unit)"
		   . ($ltd?" AND uid = '$ltd'":'');
		$result = $db->query($sql);
		list($numnotes) = $result->fetch_row();
		$result->free(); unset($result);
		if( $numnotes ) {
			$sql = 'select i.uid,IFNULL(u.username,c.email) as user,date_format(i.logdate,\'%c/%e/%y %T\') as ndt, i.ip from'
		       . ' iplogscust i left outer join operators u on i.uid=u.uid left outer join'
		       . " clients c on i.uid=c.uid where logdate >= date_sub(curdate(),INTERVAL $tframe $unit) "
			   . ($ltd?" AND i.uid = '$ltd'":'')." order by ndt desc LIMIT $page, ".PG_SIZE;
			$result = $db->query($sql);
			if( !$result ) throw new Exception(DEBUG?"$db->error : $sql": 'Can not run log query',__LINE__);
			$numnotes2 = $result->num_rows;
			if( $numnotes2 < PG_SIZE ) $lastpage = true;
		}
		else $lastpage = true;

	}
	catch(Exception $e) {
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
	}
	$style = new OperPage('IP Log',$UUID,'reports','');
	$style->Output();

	if( $UUID ) {
			if( $ACCESS < 450 ) echo '<h1>Access Denied</h1>';
			else {
?>
              <h1>Login IP Log </h1>
              <?php 
			if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
              <form name="form1" method="post" action="iplog.php">
                <p>Report Period: 
				  <label><input name="tframe" type="radio" value="yester" <?php echo $_REQUEST['tframe']==='yester'?' checked':''; ?> id="yester">
Since Yesterday</label>&nbsp; 
                  <label><input name="tframe" type="radio" value="week" <?php echo !$_REQUEST['tframe'] || $_REQUEST['tframe']==='week'?' checked':''; ?> id="aweek">
One week</label>&nbsp; 
<label><input name="tframe" type="radio" value="biweek" <?php echo $_REQUEST['tframe']==='biweek'?' checked':''; ?> id="biweek"> 
Two week</label>s&nbsp; 
<input name="tframe" type="radio" id="months" value="months" <?php echo $_REQUEST['tframe']==='months'?' checked':''; ?>>
<select name="months" id="months" onFocus="selMonths()" onChange="selMonths()">
  <option <?php echo $_REQUEST['months']==1?' selected':''; ?>>1</option>
  <option <?php echo $_REQUEST['months']==2?' selected':''; ?>>2</option>
  <option <?php echo $_REQUEST['months']==3?' selected':''; ?>>3</option>
  <option <?php echo $_REQUEST['months']==4?' selected':''; ?>>4</option>
  <option <?php echo $_REQUEST['months']==5?' selected':''; ?>>5</option>
  <option <?php echo $_REQUEST['months']==6?' selected':''; ?>>6</option>
  <option <?php echo $_REQUEST['months']==7?' selected':''; ?>>7</option>
  <option <?php echo $_REQUEST['months']==8?' selected':''; ?>>8</option>
  <option <?php echo $_REQUEST['months']==9?' selected':''; ?>>9</option>
  <option <?php echo $_REQUEST['months']==10?' selected':''; ?>>10</option>
  <option <?php echo $_REQUEST['months']==11?' selected':''; ?>>11</option>
  <option <?php echo $_REQUEST['months']==12?' selected':''; ?>>12</option>
</select> 
month(s)<br>
Limit by UID:
<input name="ltd" type="text" id="ltd" value="<?php echo $ltd; ?>" maxlength="10">
&nbsp;&nbsp;
                  <input type="submit" name="Submit" value="Select">
                  <input name="pg" type="hidden" value="0">
                </p>
              </form>
              <p>Total records in the range: <?php echo $numnotes; ?>. Records shown are from <?php echo $page+1; ?> to <?php echo $page+$numnotes2; ?>.</p>
              <table width="80%"  border="0">
                <tr>
                  <td bgcolor="#CCCCCC"><span class="style1">ID#</span></td>
                  <td bgcolor="#CCCCCC"><span class="style1">User</span></td>
                  <td bgcolor="#CCCCCC"><span class="style1">Date</span></td>
                  <td bgcolor="#CCCCCC"><span class="style1">IP Address</span></td>
                  <td bgcolor="#CCCCCC"><span class="style1">Host Name</span></td>
                </tr>
<?php
		for( $i=0; $result && $i < $result->num_rows; $i++ ) {
			list($nuid,$nuser,$ndt,$nip) = $result->fetch_row();
?>
                <tr>
                  <td><?php echo $nuid; ?></td>
                  <td><?php echo $nuser; ?></td>
                  <td><?php echo $ndt; ?></td>
                  <td><?php echo $nip; ?></td>
                  <td><?php echo gethostbyaddr($nip); ?></td>
                </tr>
<?php
		}
?>
                <tr>
                  <td bgcolor="#E8E8EC"><?php
		if( $page ) echo '<a href="iplog.php?tframe='.$_REQUEST['tframe'].'&months='.$_REQUEST['months'].'&pg='.($page-PG_SIZE).'&ltd='.$_REQUEST['ltd'].'">Prev</a>';
		else echo '&nbsp;';
				  ?></td>
                  <td colspan="3" bgcolor="#E8E8EC" align="center"><?php 
				  if( $mesg ) echo $mesg;
				  else  // show navigation
				  	for( $i = 0,$j = 1; $i < $numnotes; $i += PG_SIZE, $j++ ) 
						echo $i == $page? "$j ":'<a href="iplog.php?tframe='.$_REQUEST['tframe'].'&months='.$_REQUEST['months'].'&pg='.$i.'&ltd='.$_REQUEST['ltd'].'">'.$j.'</a> ';
				  ?></td>
                  <td align="right" bgcolor="#E8E8EC"><?php
		if( !$lastpage ) echo '<a href="iplog.php?tframe='.$_REQUEST['tframe'].'&months='.$_REQUEST['months'].'&pg='.($page+PG_SIZE).'&ltd='.$_REQUEST['ltd'].'">Next</a>';
		else echo '&nbsp;';
				  ?></td>
                </tr>
              </table>
<?php		} // ACCESS
		}
		else showLoginForm(); // UUID
		 $style->ShowFooter();
?>
