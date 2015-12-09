<?php
    require("globals.php5");
    require("cookies.php5");
	define(PAGESIZE, 50); // 2 is for test
	$mesg = '';
	$today = time();
	$num_exp = 0;
	$num_exp1 = 0;
	$num_exp2= 0;
	$sort = $_REQUEST['sort'];
	$page = $_REQUEST['page'];
	if( !is_numeric($page) ) $page = 0;
	if( $UUID && $ACCESS && $ACCESS >= 200 ) try {
		$db = db_clients();
		$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
		extract($strippost,EXTR_SKIP);
		$sortex = '';
		if( $sort == 'F' ) $sortex = ' order by o_facility, o_state, o_city, specialty, o_acct, o_uid';
		elseif( $sort == 'L' ) $sortex = ' order by o_state, o_city, o_facility, specialty, o_acct, o_uid';
		elseif( $sort == 'S' ) $sortex = ' order by specialty, o_state, o_city, o_facility, o_acct, o_uid';
		else $sortex = ' order by o_acct, o_uid, specialty, o_state, o_city, o_facility';
		$wher = ''; $wher2 = '';
		if( isset($submitto) || $page1 ) { // form processing
			// uid, acct, lastname, company, email, phone, city, state, status
			if( $acct && is_numeric($acct) ) $wher = "and c.acct=$acct ";
			if( $firstname ) $wher .= "and c.firstname = '$firstname' ";
			if( $lastname ) $wher .= "and c.lastname = '$lastname' ";
			if( $company && strlen($company) >= 3 ) $wher .= "and c.company like '$company%' ";
			if( $spec && $spec != '---' ) $wher2 .= "and specialty='$spec' ";
			if( $city && strlen($city) >= 3 ) $wher2 .= "and o_city like '$city%' ";
			elseif( $city ) $wher2 .= "and o_city = '$city' "; // city name of 2 letters, like Jo or Ah ;)
			if( $state && $state != '--' ) $wher2 .= "and o_state = '$state' ";
			if( $email && strlen($email) >= 3 ) $wher .= "and (c.email like '$email%' or c.email like '%$email') ";
			if( $uid && is_numeric($uid) ) $wher = "and c.uid=$uid "; // uid is exclusive
		}
		$sql = 'select c.uid, c.acct, c.firstname, c.lastname, c.company, c.master_acct, '.
				'd.uid AS mumster from clients c JOIN clients d ON ( c.acct = d.acct AND d.master_acct =1 ) where c.status > 0 '.$wher;
		$result = $db->query($sql);
		$uids = '';
		$clients = array();
		if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not execute query',__LINE__);
		while( $client = $result->fetch_assoc() ) {
			$clients[$client["uid"]] = $client;
			$uids .= $client["uid"].',';
		}
		$result->free();
		$result = NULL; $totalop = 0;
		$resdb = db_career();
		if( $uids ) {
			$wher2 .= "and o_uid in ($uids 0) ";
			$result = $resdb->query("select count(*) from opportunities where status = 1");
			list($totalop) = $result->fetch_row();
			$result->free();
			if( $wher2 ) {
				$result = $resdb->query("select count(*) from opportunities where status = 1 $wher2");
				list($totalfi) = $result->fetch_row();
				$result->free();
			}
			else $totalfi = $totalop;
			$sql = 'select o_uid, o_acct, specialty, o_city, o_state, o_lid, oid, o_facility, description from opportunities where status=1 '.$wher2.$sortex." LIMIT $page,".PAGESIZE;
			$result = $resdb->query($sql);
			if( !$result ) throw new Exception(DEBUG?"$resdb->error : $sql":'Can not execute query',__LINE__);
			//$totop = $result->num_rows;
		}
		else throw new Exception('No matching client records found',__LINE__);
	}
	catch(Exception $e) {
		$mesg = 'Attention: '.$e->getMessage().' ('.$e->getCode().')<br>';
		//unset($oper);
	}
	$style = new OperPage('Job board report',$UUID,'reports','customerstats');
	$scrip = <<<TryMe
<script language="JavaScript" type="text/JavaScript"><!--

function nextpage(pg) {
	var thepage = document.getElementById("thepage");
	var thepage1 = document.getElementById("thepage1");
	var thepage2 = document.getElementById("thepage2");
	var page = parseInt(thepage2.value) + pg;
	thepage.value = page;
	thepage1.value = 1;
	var theform = document.getElementById("formcs");
	//	alert(page);
	theform.submit();
	//document.forms.formcs.submit();
	return true;
}

// -->
</script>
TryMe;

	$style->Output($scrip);

	if( $UUID ) {
			if( $ACCESS < 200 ) echo '<h1>Access Denied</h1>';
			else {
				if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
				//print_r($_POST);
?>
              <h1>Job Board Activity Report</h1>
			  <p>Only Active Opportunities are shown. First, click on the column name to set the sort order. Then use the search form to filter your search. To start over, set new sort order again by clicking on the column name.</p>
			        <div id="formdiv" class="onscreen">
                      <form action="jopprpt.php" method="post" name="formcs" id="formcs">
					  	<input name="sort" type="hidden" value="<?php echo $sort; ?>">
					  	<input name="page" id="thepage" type="hidden" value="0">
					  	<input name="page1" id="thepage1" type="hidden" value="0">
					  	<input name="page2" id="thepage2" type="hidden" value="<?php echo $page; ?>">
                        <table width="80%"  border="0" cellspacing="0" cellpadding="1">
                          <tr>
                            <td>ID#:</td>
                            <td><input name="uid" type="text" id="uid" value="<?php echo $uid; ?>" maxlength="11"></td>
                            <td>Account#:</td>
                            <td><input name="acct" type="text" id="acct" value="<?php echo $acct; ?>" maxlength="11"></td>
                          </tr>
                          <tr>
                            <td>Last Name: </td>
                            <td><input name="lastname" type="text" id="lastname" value="<?php echo $lastname; ?>" size="35" maxlength="60"></td>
                            <td>Email*:</td>
                            <td><input name="email" type="text" id="email" value="<?php echo $email; ?>" size="35" maxlength="120"></td>
                          </tr>
                          <tr>
                            <td>Opp. City**:</td>
                            <td><input name="city" type="text" id="city" value="<?php echo $city; ?>" maxlength="100"></td>
                            <td>Opp. State:</td>
                            <td><?php echo showStateList($resdb,$state); ?></td>
                          </tr>
                          <tr>
                            <td>Company**:</td>
                            <td><input name="company" type="text" id="company" value="<?php echo $company; ?>" size="35" maxlength="120"></td>
                            <td>Specialty:</td>
                            <td><?php echo showSpecList($resdb,$spec,'spec'); ?></td>
                          </tr>
                          <tr>
                            <td>&nbsp;</td>
                            <td>* partial email is accepted, too: user name part or  domain part.<br>
			    ** partial info is accepted, 3 symbols minimum</td>
                            <td>&nbsp;</td>
                            <td><input name="submitto" type="submit" id="submitto" value="Search">
&nbsp;&nbsp;
          <input type="reset" name="Reset" value="Reset"></td>
                          </tr>
                        </table>
                      </form>
</div>
	        			  <p>Showing opportunities <?php echo $page+1; ?> to <?php echo $page+PAGESIZE > $totalfi? $totalfi:$page+PAGESIZE; ?>. Total active opportunities: <?php echo $totalop; if( $totalfi != $totalop ) echo "; Filtered number: $totalfi <!-- $wher2 -->"; ?>.</p>
			  <table cellspacing="0" cellpadding="1" style="width:100% ">
                <tr>
                  <th><a href="jopprpt.php?sort=A">ID/Account#</a></th>
                  <th>Name</th>
                  <th>Company</th>
                  <th><a href="jopprpt.php?sort=S">Specialty</a></th>
                  <th><a href="jopprpt.php?sort=F">Facility</a></th>
                  <th>Description</th>
				  <th><a href="jopprpt.php?sort=L">Location</a></th>
                </tr>
<?php 
		if( $result ) $totals = $result->num_rows; else $totals = 0;
		for( $i=0; $i < $totals; $i++ ) {
			// clients: uid, acct, firstname, lastname, company, master_acct, mumster
			// row: o_uid, o_acct, specialty, o_city, o_state, o_lid, oid, o_facility, description
			$row = $result->fetch_object();
?>
                <tr>
                  <td><a href="custedit.php?cid=<?php echo $clients[$row->o_uid]["mumster"]; ?>"><?php echo "$row->o_uid / $row->o_acct"; ?></a>
				  </td>
                  <td><?php echo stripslashes($clients[$row->o_uid]["firstname"].' '.$clients[$row->o_uid]["lastname"]); ?></td>
                  <td><?php echo stripslashes($clients[$row->o_uid]["company"]); ?></td>
                  <td align="center"><a href="opportunadmin.php?oid=<?php echo "$row->oid&cid=$row->o_uid&acct=$row->o_acct"; ?>">&nbsp;<?php echo $row->specialty; ?>&nbsp;</a></td>
                  <td><?php echo stripslashes($row->o_facility); ?></td>
				  <td><?php echo htmlspecialchars(substr(stripslashes($row->description),0,65)).'&hellip;'; ?></td>
                  <td><a href="locationsadmin.php?l_id=<?php echo "$row->o_lid&cid=$row->o_uid&acct=$row->o_acct"; ?>&action=update"><?php echo stripslashes("$row->o_city, $row->o_state"); ?></a></td>
                </tr>
<?php 
		} // for (iteration)
?>
        <tr><td><?php if( $page ) { ?>
			<input type="button" name="prev" onClick="nextpage(-<?php echo PAGESIZE; ?>)" value="Previous <?php echo PAGESIZE; ?>">
		<?php } ?>
		&nbsp;</td><td colspan="5">&nbsp;</td><td align="right"><?php if( $page+PAGESIZE < $totalfi ) { ?>
			<input type="button" name="next" onClick="nextpage(<?php echo PAGESIZE; ?>)" value="Next <?php echo PAGESIZE; ?>">
		<?php } ?>
		&nbsp;</td></tr>
		</table>     
		  
<?php		} // ACCESS
		} // UUID
		else showLoginForm(); 
		$style->ShowFooter();
?>