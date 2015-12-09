<?php
    require("globals.php5");
    require("cookies.php5");
	$mesg = ''; $success = false;
	if( $UUID && $ACCESS && ($ACCESS >= 400 ||($ACCESS >= 50 && $ACCESS < 200)) ) try {
		$uid = $_REQUEST['id'];
		if( !$uid || !is_numeric($uid) ) throw new Exception('User id required',__LINE__);
		$db = db_clients();
		$oper = new Operator($db,$uid);
		if( $oper->access >= $ACCESS && $ACCESS != 500 ) throw new Exception('Your access level is less than required',$oper->access+1);
		if( isset($_POST['active']) ) { // form processing
			$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
			$oper->status = $_POST['active']?1:0; $passA = trim($_POST['password1']);
			$passB = trim($_POST['password2']); $email = $strippost['email'];
			$uaccess = $strippost['access']; $fnm = $strippost['firstname'];
			$lnm = $strippost['lastname'];
			$expd = $strippost['exp_date'];
			if( !is_numeric($uaccess) ) $uaccess = 1;
			if( $uaccess > $ACCESS ) $uaccess = $ACCESS;
			$oper->access = $uaccess;
			if( empty($email) || empty($fnm) || empty($lnm) )
				throw new Exception('Required fields are missing in the form',__LINE__);
			$oper->firstname = $fnm; $oper->lastname = $lnm;
			if( !valid_email($email) )
				throw new Exception('Please enter valid email address in the form',__LINE__);
			$oper->email = $email;
			if( !empty($passA) && !empty($passB) ) {
				if( strlen($passA) < 6 )
					throw new Exception('Password can not be shorter than six symbols',__LINE__);
				if( $passA == $oper->username )
					throw new Exception('Password can not be the same as the username',__LINE__);
				if( $passA != $passB )
					throw new Exception('Passwords entered are not the same',__LINE__);
				$oper->password = sha1(stripslashes($passA));
			}
			$oper->exp_date = $expd;
			// all good so far
			$oper->save_user($db);
			$success = true;
		}
	}
	catch(Exception $e) {
		$mesg = 'Attention: '.$e->getMessage().' ('.$e->getCode().')<br>';
		//unset($oper);
	}
	$style = new OperPage('Edit Employee',$UUID,'admin','editoper',($success?"2; URL=editoper.php":NULL));
	$style->Output();
	
	if( $UUID ) {
			if( $ACCESS < 400 && ($ACCESS < 50 || $ACCESS >= 200) ) echo '<h1>Access Denied</h1>';
			else {
?>

              <h1>Edit Employee</h1>
              <?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
	if( $success ) echo '<h3 id="warning_msg">Operation completed successfully.</h3>';
?>
              <div id="formdiv">
              <form action="edittheoper.php" method="post" name="formed" id="formed">
			  <table>
			    <tr>
                  <td>User:</td>
                  <td><?php echo isset($oper)? $oper->username: 'N/A'; ?></td>
				  <td>First Name:</td><td><input name="firstname" type="text" id="firstname" value="<?php echo isset($oper)? $oper->firstname: 'N/A'; ?>" maxlength="50"></td>
				  <td>Last name:</td><td><input name="lastname" type="text" id="lastname" value="<?php echo isset($oper)? $oper->lastname: 'N/A'; ?>" maxlength="50"></td>
				</tr><tr>
				  <td>Email:</td><td><input name="email" type="text" id="email" value="<?php echo isset($oper)? $oper->email: 'N/A'; ?>" maxlength="100"></td>
				  <td>Access Level**:</td><td><input name="access" type="text" id="access" value="<?php echo isset($oper)? $oper->access: 0; ?>" maxlength="3"></td>
				  <td>Status:</td>
				  <td><input name="active" type="radio" value="1" <?php echo isset($oper) && $oper->status? ' checked': ''; ?> >
				    Active 
				      <input name="active" type="radio" value="0" <?php echo isset($oper) && $oper->status? '': ' checked'; ?> >
				      Inactive</td>
                </tr><tr>
				  <td>Password*:</td>
				  <td><input name="password1" type="password" id="password1" value="" maxlength="40"></td>
				  <td>Repeat Password*:</td>
				  <td><input name="password2" type="password" id="password2" value="" maxlength="40"></td><td><input name="id" type="hidden" id="id" value="<?php echo $uid; ?>"></td>
				  <td><input type="submit" name="Submit" value="Submit"> &nbsp;&nbsp;
				  <input type="reset" name="Reset" value="Reset"></td>
				</tr>
<?php if( $ACCESS >= 400 ) { ?>
                <tr>
                  <td>Exp.Date:</td>
                  <td><input name="exp_date" type="text" id="exp_date" value="<?php echo isset($oper)? $oper->exp_date: ''; ?>"></td>
                  <td>(<em>YYYY-MM-DD</em>)</td>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                  <td>&nbsp;</td>
                </tr>
<?php } // access 400 ?>
                <tr>
                  <td>Last Login IP: </td>
                  <td><?php echo isset($oper)?$oper->lastlogip:'&nbsp;'; ?></td>
                  <td colspan="2"><?php echo isset($oper) && $oper->lastlogip? gethostbyaddr($oper->lastlogip):'&nbsp;'; ?></td>
                  <td>Last Login: </td>
                  <td><?php echo isset($oper)?$oper->lastlogdate:'&nbsp;'; ?></td>
                </tr>
				</table>
              </form></div>
              <p>* = optional field.</p>
              <p>** Access level is number 0-500: 0 = none, 1-49 = data entry (user can add/edit residents), 50-199 = database manager (in addition to data entry access, user can assign lists, add employees, and work with shadowed lists), 200-299 = customer support (user can also edit customer's contact information), 300-399 = account manager (in addition to customer support level, user can add new customers and edit full customer's record), 400-499 = administrator (can add/edit users - employees), 500 = super administrator (no restrictions).</p>
              <?php		} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>