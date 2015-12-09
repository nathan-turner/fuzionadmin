<?php
    require("globals.php5");
    require("cookies.php5");
	$mesg = '';
	$today = time();
	$num_exp = 0;
	$num_exp1 = 0;
	$num_exp2= 0;
	$page = $_REQUEST['page'];
	$sort = $_REQUEST['sort'];
	if( $UUID && $ACCESS && $ACCESS >= 200 ) try {
		$db = db_clients();
		$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
		extract($strippost,EXTR_SKIP);
		$sortex = '';
		if( $sort == 'A' ) $sortex = ' order by c.acct, c.uid';
		elseif( $sort == 'N' ) $sortex = ' order by c.firstname, c.lastname';
		elseif( $sort == 'C' ) $sortex = ' order by c.company';
		elseif( $sort == 'S' ) $sortex = ' order by c.state, c.city';
		elseif( $sort == 'E' ) $sortex = ' order by c.exp_date';
		else $sortex = ' order by lastlogdate';
		$resfrom = 0;
		if( $page && is_numeric($page) ) $resfrom = $page;
		$wher = '';
		if( isset($submitto) ) { // form processing
			// uid, acct, lastname, company, email, phone, city, state, status
			if( $acct && is_numeric($acct) ) $wher = "and c.acct=$acct ";
			if( $firstname ) $wher .= "and c.firstname = '$firstname' ";
			if( $lastname ) $wher .= "and c.lastname = '$lastname' ";
			if( $company && strlen($company) >= 3 ) $wher .= "and c.company like '$company%' ";
			$phone = preg_replace('/[^0-9]/','',$phone);
			if( $phone && strlen($phone) >= 3 ) $wher .= "and c.phone like '$phone%' ";
			if( $city ) $wher .= "and c.city = '$city' ";
			if( $state && $state != '--' ) $wher .= "and c.state = '$state' ";
			if( $email && strlen($email) >= 3 ) $wher .= "and (c.email like '$email%' or c.email like '%$email') ";
			/*if( $status == 1 ) $wher .= "and status = 1 ";
			elseif( $status == 2 ) $wher .= "and status = 0 ";
			elseif( $status == 3 ) $wher .= "and exp_date < curdate() ";*/
			if( $uid && is_numeric($uid) ) $wher = "and c.uid=$uid "; // uid is exclusive
		}
		$sql = "select c.uid, c.acct, c.firstname, c.lastname, c.company, c.city, c.state, c.exp_date, c.lastlogdate, c.master_acct, (case when c.status=0 then 'inactive' when c.exp_date < curdate() then 'expired' else 'active' end) as statut from clients c where c.status > 0 $wher $sortex LIMIT $resfrom,100";
		$result = $db->query($sql);
		if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not execute query',__LINE__);
		$acctsa = array();
		while( $cli = $result->fetch_object() ) {
			if( !$cli->master_acct ) $acctsa[] = $cli->acct;
		}
		$result->data_seek(0);
		$accts = implode(",",$acctsa);
		$massa = array();
		if( $accts ) {
			$sql = "select uid,acct from clients where master_acct = 1 and acct in ($accts)";
			$res0 = $db->query($sql);
			while( list($u0,$a0) = $res0->fetch_row() ) {
				$massa[$a0] = $u0;
			}
			$res0->free();
		}
	}
	catch(Exception $e) {
		$mesg = 'Attention: '.$e->getMessage().' ('.$e->getCode().')<br>';
		//unset($oper);
	}
	$style = new OperPage('Customer Statistics',$UUID,'reports','customerstats');
	$style->Output();

	if( $UUID ) {
			if( $ACCESS < 200 ) echo '<h1>Access Denied</h1>';
			else {
?>
              <h1>Customer Activity Report</h1>
			  <p>First, click on the column name to set the sort order. Then, use the search form at the botton of the page to limit your search. </p>
              <?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
			  <table cellspacing="0" cellpadding="1" style="width:100% ">
                <tr>
                  <th><a href="customeract.php?sort=A&exp=<?php echo $exp; ?>">ID/Account#</a></th>
                  <th><a href="customeract.php?sort=N&exp=<?php echo $exp; ?>">Name</a></th>
                  <th><a href="customeract.php?sort=C&exp=<?php echo $exp; ?>">Company</a></th>
                  <th><a href="customeract.php?sort=S&exp=<?php echo $exp; ?>">City, State</a></th>
                  <th class="onscreen">Status</th>
				  <th class="onscreen"><a href="customeract.php?sort=E&exp=<?php echo $exp; ?>">Exp. Date</a></th>
                  <th><a href="customeract.php?sort=L&exp=<?php echo $exp; ?>">Last Login</a></th>
                </tr>
<?php 
		$totals = $result->num_rows;
		for( $i=0; $i < $totals; $i++ ) {
			// uid, acct, firstname, lastname, company, city, state, statut, exp_date, lastlogdate, master_acct, mumster
			$row = $result->fetch_object();
			$date_seconds = 0;
			if( $row->lastlogdate ) {
			   $d = split("-",$row->lastlogdate);
			   $date_seconds = mktime(0,0,0,$d[1],$d[2],$d[0]);
			   if( ($today - 5184000 - 5184000 - 5184000) > $date_seconds) $num_exp1 ++;
			   else $num_exp2 ++;
			   if( $exp == 'exp' ) continue;
			}
			else {
				$num_exp ++;
				if( $exp == 'exp2' || $exp == 'exp1' ) continue;
			}
			if( $exp == 'exp2' && $date_seconds && ($today - 5184000 - 5184000 - 5184000) > $date_seconds ) continue;
			if( $exp == 'exp1' && $date_seconds && ($today - 5184000 - 5184000 - 5184000) <= $date_seconds ) continue;
?>
                <tr>
                  <td><a href="custedit.php?cid=<?php echo $row->master_acct?$row->uid:$massa[$row->acct]; ?>"><?php echo "$row->uid / $row->acct"; ?></a>
				  </td>
                  <td><?php echo stripslashes("$row->firstname $row->lastname"); ?></td>
                  <td><?php echo stripslashes($row->company); ?></td>
                  <td><?php echo stripslashes("$row->city, $row->state"); ?></td>
                  <td class="onscreen"><?php echo $row->statut; ?></td>
				  <td class="onscreen"><?php echo $row->exp_date; ?></td>
                  <td align="center"><?php echo $row->lastlogdate; ?></td>
                </tr>
<?php 
		} // for (iteration)
			if( $result->num_rows >= 100 ) {
?>
			   <tr><td colspan="7"><form action="customeract.php" method="get" name="nextf">
			   More Available: <input type="submit" value="Next Page" name="nextp">
                    <input name="page" type="hidden" value="<?php echo $resfrom+100; ?>">
                    <input name="sort" type="hidden" value="<?php echo $sort; ?>">
                    <input name="exp" type="hidden" value="<?php echo $exp; ?>">
                    <input name="uid" type="hidden" value="<?php echo $uid; ?>">
                    <input name="acct" type="hidden" value="<?php echo $acct; ?>">
                    <input name="lastname" type="hidden" value="<?php echo $lastname; ?>">
                    <input name="email" type="hidden" value="<?php echo $email; ?>">
                    <input name="city" type="hidden" value="<?php echo $city; ?>" >
                    <input name="state" type="hidden" value="<?php echo $state; ?>" >
                    <input name="company" type="hidden" value="<?php echo $company; ?>">
                    <input name="phone" type="hidden" value="<?php echo $phone; ?>">
			   </form>
			   </td></tr>
<?php
			} // 100
?>
        </table>     
<?php 	
		
?>			  			
			<p>Use the form below to filter accounts and to limit your report based on various criteria</p>
			<table style="width:auto ">
			  	<tr>
					<td>Accounts and subaccounts on this page</td>
					<td align="right"><?php echo $totals; ?></td>
				</tr>
			  	<tr>
					<td>Never Logged in</td>
					<td align="right"><?php echo $num_exp; ?></td>
				</tr>
				<tr>
					<td>Did not log in for 6 months</td>
					<td align="right"><?php echo $num_exp1; ?></td>
				</tr>
				<tr>
					<td>Active during last 6 months</td>
					<td align="right"><?php echo $num_exp2; ?></td>
				</tr>
</table>
			  
			        <div id="formdiv" class="onscreen">
                      <form action="customeract.php" method="post" name="formcs" id="formcs">
					  	<input name="sort" type="hidden" value="<?php echo $sort; ?>">
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
                            <td>City:</td>
                            <td><input name="city" type="text" id="city" value="<?php echo $city; ?>" maxlength="100"></td>
                            <td>State:</td>
                            <td><?php echo showStateList($db,$state); ?></td>
                          </tr>
                          <tr>
                            <td>Company**:</td>
                            <td><input name="company" type="text" id="company" value="<?php echo $company; ?>" size="35" maxlength="120"></td>
                            <td>Phone***:</td>
                            <td><input name="phone" type="text" id="phone" value="<?php echo $phone; ?>" maxlength="16"></td>
                          </tr>
                          <tr>
                            <td>Filter:</td>
                            <td><label>
                              <input name="exp" type="radio" <?php if( !$exp ) echo "checked"; ?> value="">
          All</label>
                    &nbsp;
                              <label title="Never Logged in">
                              <input name="exp" type="radio" <?php if( $exp == "exp" ) echo "checked"; ?> value="exp">
          Never</label>
                    &nbsp;
                              <label title="Did not log in for 6 months">
                              <input name="exp" type="radio" <?php if( $exp == "exp1" ) echo "checked"; ?> value="exp1">
          6 Mo </label>                    &nbsp;
                              <label title="Active during last 6 months">
                              <input name="exp" type="radio" <?php if( $exp == "exp2" ) echo "checked"; ?> value="exp2">
          Active</label></td>
                            <td>&nbsp;</td>
                            <td><input name="submitto" type="submit" id="submit" value="Search">
&nbsp;&nbsp;
          <input type="reset" name="Reset" value="Reset"></td>
                          </tr>
                        </table>
                      </form>
</div>
	        			  <p>* partial email is accepted, too: user name part or  domain part.<br>
			    ** partial info is accepted, 3 symbols minimum<br>
			    *** area code at least </p>
			<p>Click here for <a href="customerstats.php?sort=<?php echo $sort; ?>&exp=">Customer Statistics &amp; Expiration Report</a></p> 
<?php		} // ACCESS
		} // UUID
		else showLoginForm(); 
		$style->ShowFooter();
?>