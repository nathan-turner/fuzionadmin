<?php
    require("globals.php5");
    require("cookies.php5");
	// $UUID <> 0 if auth
	$mesg = ''; $success = false;
	if( $UUID && $ACCESS && $ACCESS >= 300 ) try {
		$db = db_clients(); // db is required below for state list
		$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
		extract($strippost,EXTR_SKIP);
		if( isset($submit) ) { // form processing
			$passA = trim($_POST['password1']);  $passB = trim($_POST['password2']);
			if( empty($email) || empty($firstname) || empty($lastname)
				|| empty($title) || empty($company) || empty($city) 
				|| empty($state) || $state=='--' || empty($phone) || empty($title) 
				|| empty($acct) || !is_numeric($acct) 
				|| empty($subaccts) || !is_numeric($subaccts) 
				|| empty($passA) || empty($passB) )
				throw new Exception('Required fields are missing in the form',__LINE__);
			// ok? no, more checks
			if( !valid_email($email) ) throw new Exception('Email address is invalid',__LINE__);
			$res = $db->query("select uid from clients where email = '$email' or acct = $acct");
			if( $res->num_rows )
				throw new Exception('This account number or email address is already in the database',__LINE__);
			$res->free();
			if( strlen($passA) < 6 )
				throw new Exception('Password can not be shorter than six symbols',6);
			if( $passA == $email || $passA == $firstname || $passa == $lastname )
				throw new Exception('Password can not be the same as customer name or email',8);
			if( $passA != $passB )
				throw new Exception('Passwords entered are not the same',7);
			$email = addslashes($email); $firstname = addslashes($firstname); $lastname = addslashes($lastname);
			$sql = "insert into clients (email,firstname,lastname) values ('$email','$firstname','$lastname')";
			$res = $db->query($sql);
			if( !$res ) throw new Exception(DEBUG?"$db->error : $sql":'Can not insert',__LINE__);
			$uid = $db->insert_id;
			$client = new Customer($db,$uid);
			$client->status = $active?1:0; $client->password = sha1(stripslashes($passA));
			$client->phone = $phone; $client->fax = preg_replace('/[^0-9]/','',$fax);
			$client->title = $title;
			$client->mrmsdr = $mrmsdr; $client->company = $company; $client->city = $city;
			$client->state = $state; $client->zip = $zip;
			$client->addr1 = $addr1; $client->addr2 = $addr2;
			$client->acct = $acct; $client->subaccts = $subaccts; 
			$client->exp_date = $exp_date; 
			$client->subscription = 3; $client->master_acct = 1;
			// all good so far
			$client->save_user($db);
			$success = true;
		}
	}
	catch(Exception $e) {
		$mesg = 'Attention: '.$e->getMessage().' ('.$e->getCode().')<br>';
		//unset($oper);
	}
	$style = new OperPage('New Customer',$UUID,'admin','custnew',($success? "1; URL=custedit.php?cid=$uid": NULL));
	$style->Output('<script type="text/javascript" src="calendarDateInput.js"></script>');
	if( $UUID ) {
			if( $ACCESS < 300 ) echo '<h1>Access Denied</h1>';
			else {
?>
              <h1> New customer</h1>
              <?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
	if( $success ) echo '<h3 id="warning_msg">Operation completed successfully.</h3>';
?>
              <div id="formdiv">
                <form action="custnew.php" method="post" name="formed" id="formed">
                  <table style="width: 100%">
                    <tr>
                      <td>Email:</td>
                      <td colspan="3"><input name="email" type="text" id="email" value="<?php echo $email; ?>" size="50" maxlength="100"></td>
                      <td>Status:</td>
                      <td><label><input name="active" type="radio" value="1" checked>
Active</label><br />
  <label>
  <input name="active" type="radio" value="0">
Inactive</label></td>
                    </tr>
                    <tr>
                      <td>&nbsp;</td>
                      <td><input name="mrmsdr" type="radio" value="Mr" 
					      <?php echo $mrmsdr==='Mr'||!$mrmsdr?'checked':'' ?>>
                        Mr&nbsp;&nbsp; <label><input name="mrmsdr" type="radio" <?php echo $mrmsdr==='Ms'?'checked':'' ?> value="Ms">
                        Ms</label>&nbsp; <label><input name="mrmsdr" type="radio" <?php echo $mrmsdr==='Dr'?'checked':'' ?> value="Dr">
                        Dr</label></td>
                      <td>First Name:</td>
                      <td><input name="firstname" type="text" id="firstname" value="<?php echo $firstname; ?>" maxlength="60"></td>
                      <td>Last name:</td>
                      <td><input name="lastname" type="text" id="lastname" value="<?php echo $lastname; ?>" maxlength="60"></td>
                    </tr>
                    <tr>
                      <td>Title:</td>
                      <td><input name="title" type="text" id="title" value="<?php echo $title; ?>" maxlength="50"></td>
                      <td>Company:</td>
                      <td colspan="3"><input name="company" type="text" id="company" value="<?php echo $company; ?>" size="50" maxlength="120"></td>
                    </tr>
                    <tr>
                      <td>Phone:</td>
                      <td><input name="phone" type="text" id="phone" value="<?php echo $phone; ?>" maxlength="16"></td>
                      <td>Fax*:</td>
                      <td><input name="fax" type="text" id="fax" value="<?php echo $fax; ?>" maxlength="16"></td>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                    </tr>
                    <tr>
                      <td>Address</td>
                      <td colspan="5">Line 1*: <input name="addr1" type="text" id="addr1" value="<?php echo $addr1; ?>" size="45" maxlength="120"><br />
                      Line 2*: 
                      <input name="addr2" type="text" id="addr2" value="<?php echo $addr2; ?>" size="45" maxlength="120"></td>
                    </tr>
                    <tr>
                      <td>City:</td>
                      <td><input name="city" type="text" id="city" value="<?php echo $city; ?>" maxlength="100"></td>
                      <td>State:</td>
                      <td><?php echo showStateList($db,$state); ?></td>
                      <td>Zip*:</td>
                      <td><input name="zip" type="text" id="zip" value="<?php echo $zip; ?>" size="12" maxlength="10"></td>
                    </tr>
                    <tr>
                      <td>Account #:</td>
                      <td><input name="acct" type="text" id="acct" value="<?php echo $acct; ?>" maxlength="11"></td>
                      <td>Expiration Date: </td>
                      <td><script language="javascript">DateInput('exp_date', true, 'YYYY-MM-DD', '<?php echo date('Y-m-d',time()+3600*24*365); ?>');</script></td>
                      <td># of users: </td>
                      <td><input name="subaccts" type="text" id="subaccts" value="<?php echo $subaccts?$subaccts:1; ?>" size="12" maxlength="3" title="max number of users (sub-accounts)"></td>
                    </tr>
                    <tr>
                      <td>Password:</td>
                      <td><input name="password1" type="password" id="password1" value="<?php echo $passA; ?>" maxlength="40"></td>
                      <td>Repeat Password:</td>
                      <td><input name="password2" type="password" id="password2" value="<?php echo $passB; ?>" maxlength="40"></td>
                      <td>&nbsp;</td>
                      <td><input name="submit" type="submit" id="submit" value="Submit">
&nbsp;&nbsp;
          <input type="reset" name="Reset" value="Reset"></td>
                    </tr>
                  </table>
                </form>
              </div>
			  <p>* Optional field</p>
              <?php		} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
