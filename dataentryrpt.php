<?php
    require("globals.php5");
	define(PG_SIZE,50);
    require("cookies.php5");
    // $UUID <> 0 if auth
	$mesg = '';
	$d1 = $_REQUEST["d1"];
	$d2 = $_REQUEST["d2"];
	$grp = $_REQUEST["grp"];
	$nuo = $_REQUEST["nuo"];
	if( empty($d2) ) $d2 = date("Y-m-d");
	else $d2 = date("Y-m-d",strtotime($d2));
	if( empty($d1) ) {
		$d1 = date("Y-m-d",strtotime("$d2 - 1 day"));
		$nuo = 1; // initial
		$grp = 1;
	}
	else $d1 = date("Y-m-d",strtotime($d1));
	$usid = $_REQUEST["usid"]; 
	$phid = $_REQUEST["phid"];
	$page = $_REQUEST['pg'];
	if( !$page || !is_numeric($page) ) $page = 0;
	if( $UUID && $ACCESS >= 400 ) try {
		$db = db_career();
		$opers = array(); $oplist = '<option value="0"> All </option>';
		$result = $db->query("select uid,firstname,lastname,username,access,status from operators where status=1 order by firstname,lastname"); // and access <= $ACCESS
		if( !$result || !$result->num_rows ) throw new Exception('Who am I? Who are you?',__LINE__);
		while( $oper = $result->fetch_object() ) {
			$opers[$oper->uid] = $oper->username;
			$ssel = $oper->uid == $usid?'selected':'';
			$oplist .= "<option value='$oper->uid' $ssel>$oper->firstname $oper->lastname ($oper->username)</option>";
		}
		$opers[0] = '(no one)';
		$result->free();
		$cksub = '';
		$ordsub = $grp?' order by opid':' order by tst';
		if( $usid && is_numeric($usid) ) {
			$cksub = "opid = $usid and "; 
		}
		if( $phid && is_numeric($phid) ) {
			$cksub = "phid = $phid and $cksub"; 
		}
		if( !$phid && !usid ) {
			$cksub = ""; 
			$ordsub = " order by phid,tst";
		}
		if( $nuo ) $cksub = "action in ('RESNEW','IMPORT') and $cksub";
		$cksub .= "tst between '$d1' and date_add('$d2',interval 1 day)";
		$sql = $grp?"select x.opid, count(*) as cnt from (select distinct opid,phid from gestapo where $cksub) as x group by x.opid":"select opid,phid,tst,action from gestapo where $cksub";
		$sql .= "$ordsub LIMIT $page, ".PG_SIZE;
		$nodb = db_notes();
		$result = $nodb->query($sql);
		if( !$result ) throw new Exception(DEBUG?"$nodb->error : $sql":'Can not get the report',__LINE__);
		$totalcount = $result->num_rows; // not total here
		if( $totalcount < PG_SIZE ) $lastpage = true;
	}
	catch(Exception $e) {
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
	}
	$style = new OperPage('Data Entry Report',$UUID,'reports','managerrpts');
	$scrip2 = "<script type=\"text/javascript\" src=\"calendarDateInput.js\"></script>\n";
	$style->Output($scrip2);
 
 	if( $UUID ) {
			if( $ACCESS < 400 ) echo '<h1>Access Denied</h1>';
			else {
?>
              <h1>Data Entry Report</h1>
<?php 
			if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
	<form action="dataentryrpt.php" method="get" name="datef">
		<table><tr>
		<td>Date from:</td><td> <script language="javascript">DateInput('d1', false, 'YYYY-MM-DD', '<?php echo $d1; ?>');</script></td>
		<td>User:</td>
		<td><select name="usid"><?php echo $oplist; ?></select></td>
		</tr><tr>
		<td>Date to:</td><td><script language="javascript">DateInput('d2', false, 'YYYY-MM-DD', '<?php echo $d2; ?>');</script></td>
		<td>Phys ID#</td>
		<td><input type="text" name="phid" value="<?php echo $phid; ?>" /></td>
		</tr><tr>
		<td colspan="3" align="center" rowspan="2"><input type="submit" name="submit" value="Select"></td>
		<td><input type="checkbox" name="grp" value="1" <?php if( $grp ) echo "checked"; ?> /> Summary view</td>
		</tr><tr>
		<td><input type="checkbox" name="nuo" value="1" <?php if( $nuo ) echo "checked"; ?> /> New docs only</td>
		</tr>
		</table>
	</form>
<?php		if( $result ) { ?>
              <table width="90%" >
                <tr>
                  <th align="center">User</th>
<?php 			if( $grp ) { ?>
                  <th align="center">Number of docs</th>
<?php 			} else { ?>
                  <th align="center">PH ID#</th>
                  <th align="center">Date and Time</th>
                  <th>Action</th>
<?php 			} ?>
                </tr>
<?php

		for( $i=0; $i < $totalcount; $i++ ) {
			$rec = $result->fetch_object();
?>
                <tr>
                  <td align="center"><?php echo $opers[$rec->opid]; ?></td>
<?php 			if( $grp ) { ?>
                  <td align="center"><?php echo $rec->cnt; ?></td>
<?php 			} else { ?>
                  <td align="center"><?php echo "<a href='showdocpc.php?id=$rec->phid&lid=0&pos=0' target='showdoc'>&nbsp;$rec->phid&nbsp;</a>"; ?></td>
                  <td align="center"><?php echo $rec->tst; ?></td>
                  <td><?php echo $rec->action; ?></td>
<?php 			} ?>
                </tr>
<?php
		}
		$result->free();
?>
                <tr>
                  <td bgcolor="#E8E8EC"><?php
		if( $page ) echo "<a href='dataentryrpt.php?pg=".($page-PG_SIZE)."&nuo=$nuo&d1=$d1&d2=$d2&grp=$grp&usid=$usid&phid=$phid'>Prev</a>";
		else echo '&nbsp;';
				  ?></td>
                  <td colspan="5" bgcolor="#E8E8EC" align="center"><?php 
				  if( $mesg ) echo $mesg;
				  else  echo '&nbsp;';
				  ?></td>
                  <td align="right" bgcolor="#E8E8EC"><?php
		if( !$lastpage ) echo "<a href='dataentryrpt.php?pg=".($page+PG_SIZE)."&nuo=$nuo&d1=$d1&d2=$d2&grp=$grp&usid=$usid&phid=$phid'>Next</a>";
		else echo '&nbsp;';
				  ?></td>
                </tr>
              </table>
<?php
				} // docs
			} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>