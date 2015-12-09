<?php
	// fixed - done - 3/29/07 - SL
    require("globals.php5");
    require("cookies.php5");
	// $UUID <> 0 if auth
	$mesg = '';
	$result = false;
	if( $UUID && $ACCESS && $ACCESS >= 200 ) try {
		$db = db_clients(); // db is required below for state list
		$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
		extract($strippost,EXTR_SKIP);
		if( isset($submitto) || isset($nextp) ) { // form processing
			// uid, acct, lastname, company, email, phone, city, state, status
			$wher = '';
			$masto = $lomasters?0:1;
			$resfrom = 0;
			if( $page && is_numeric($page) ) $resfrom = $page;
			if( $acct && is_numeric($acct) ) $wher = "and c.acct=$acct ";
			if( $firstname ) {
			    $q = addslashes($lirstname);
				$wher .= "and c.firstname = '$q' ";
			}
			if( $lastname ) {
			    $q = addslashes($lastname);
				$wher .= "and c.lastname = '$q' ";
			}
			if( $company && strlen($company) >= 3 ) {
			    $q = addslashes($company);
				$wher .= "and c.company like '$q%' ";
			}
			$phone = preg_replace('/[^0-9]/','',$phone);
			if( $phone && strlen($phone) >= 3 ) $wher .= "and c.phone like '$phone%' ";
			if( $city ) {
			    $q = addslashes($city);
				$wher .= "and c.city = '$q' ";
			}
			if( $state && $state != '--' ) $wher .= "and c.state = '$state' ";
			if( $email && strlen($email) >= 3 ) {
			    $q = addslashes($email);
				$wher .= "and (c.email like '$q%' or c.email like '%$q') ";
			}
			if( $status == 1 ) $wher .= "and c.status = 1 ";
			elseif( $status == 2 ) $wher .= "and c.status = 0 ";
			elseif( $status == 3 ) $wher .= "and c.exp_date < curdate() ";
			if( $uid && is_numeric($uid) ) $wher = "and c.uid=$uid "; // uid is exclusive
			if( empty($wher) ) throw new Exception('Please specify search criteria',__LINE__);
			$sql = 'select c.uid as cuid, c.acct, c.email, c.firstname, c.lastname, c.company, c.city, c.state, '.
				"(case when c.status=0 then 'inactive' when c.exp_date < curdate() then 'expired' else 'active' end) as statut, m.uid from clients c join clients m on (c.acct=m.acct and m.master_acct = 1) where c.master_acct = $masto $wher LIMIT $resfrom,100";
			$result = $db->query($sql); 
			//$mesg = $sql; // debug
			if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not execute query',__LINE__);
		}
	}
	catch(Exception $e) {
		$mesg = 'Attention: '.$e->getMessage().' ('.$e->getCode().')<br>';
		//unset($oper);
	}
	$style = new OperPage('Browse Customers',$UUID,'admin','custsearch');
	$style->Output();
	if( $UUID ) {
			if( $ACCESS < 200 ) echo '<h1>Access Denied</h1>';
			else {
			  if( $result ) {
?>
              <h2>Search Results</h2>
			  <p><a href="#searchform">Refine your search</a></p>
<?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
			  <table cellspacing="0" cellpadding="1">
                <tr>
                  <th>ID# / Account#</th>
                  <th>Email</th>
                  <th>Name</th>
                  <th>Company</th>
                  <th>City, State</th>
                  <th>Status</th>
                </tr>
<?php 
			for( $i=0; $i < $result->num_rows; $i++ ) {
				// uid, acct, email, firstname, lastname, company, city, state, statut
				$row = $result->fetch_object();
?>
                <tr>
                  <td><a href="custedit.php?cid=<?php echo $row->uid; ?>"><?php echo "$row->cuid / $row->acct"; ?></a></td>
                  <td><?php echo stripslashes($row->email); ?></td>
                  <td><?php echo stripslashes("$row->firstname $row->lastname"); ?></td>
                  <td><?php echo stripslashes($row->company); ?></td>
                  <td><?php echo stripslashes("$row->city, $row->state"); ?></td>
                  <td><?php echo $row->statut; ?></td>
                </tr>
<?php 
			} // for
			if( $result->num_rows >= 100 ) {
?>
			   <tr><td colspan="6"><form action="custsearch.php" method="post" name="nextf">
			   More Available: <input type="submit" value="Next Page" name="nextp">
                    <input name="uid" type="hidden" value="<?php echo $uid; ?>">
                    <input name="page" type="hidden" value="<?php echo $resfrom+100; ?>">
                    <input name="acct" type="hidden" value="<?php echo $acct; ?>">
                    <input name="lastname" type="hidden" value="<?php echo $lastname; ?>">
                    <input name="email" type="hidden" value="<?php echo $email; ?>">
                    <input name="city" type="hidden" value="<?php echo $city; ?>" >
                    <input name="state" type="hidden" value="<?php echo $state; ?>" >
                    <input name="company" type="hidden" value="<?php echo $company; ?>">
                    <input name="phone" type="hidden" value="<?php echo $phone; ?>">
                    <input name="status" type="hidden" value="<?php echo $status; ?>">
                    <input name="lomasters" type="hidden" value="<?php echo $lomasters; ?>">
			   </form>
			   </td></tr>
<?php
			} // 100
?>
              </table>
<?php		  } ?>
              <h1><a name="searchform"></a>Customer Search</h1>
<?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
              <p>Search Customers by ID#, Account#, Name, Address, Email, Phone Number, Company. Edit contact information, change email, password, and account expiration date, suspend or activate accounts. See sub-accounts for this account.</p>
			  <div id="formdiv">
			  <form action="custsearch.php" method="post" name="formcs" id="formcs">
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
                    <td>Status:</td>
                    <td><label><input name="status" type="radio" value="1">
                      Active</label>&nbsp; <label><input name="status" type="radio" value="2">
                      Inactive</label>&nbsp; <label><input name="status" type="radio" value="3">
                      Expired</label></td>
                    <td><input name="lomasters" type="checkbox" id="lomasters" title="Subaccounts only" value="1"></td>
                    <td><input name="submitto" type="submit" id="submit" value="Search"> 
                      &nbsp;&nbsp; <input type="reset" name="Reset" value="Reset"></td>
                  </tr>
                </table>
		      </form>
			  </div>
			  <p>* partial email is accepted, too: user name part or  domain part.<br>
			    ** partial info is accepted, 3 symbols minimum<br>
			    *** area code at least </p>

<?php		} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
