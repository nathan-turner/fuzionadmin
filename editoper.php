<?php
    require("globals.php5");
    require("cookies.php5");
	$mesg = '';
	// $_POST['active'] == 1 means, strangely enough, to show INACTIVE opers
	$showactive = 1;
	unset($db,$username);
	if( isset($_POST['active']) ) {
		$showactive = $_POST['active']? 0: 1;
	}
	elseif( $UUID && $ACCESS && ($ACCESS >= 400 ||($ACCESS >= 50 && $ACCESS < 200)) && isset($_POST['username']) ) try {
		// u, p, e, fn, ln are required
		$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
		$username = trim($strippost['username']); $passA = trim($_POST['password1']);
		$passB = trim($_POST['password2']); $email = $strippost['email'];
		$uaccess = $_POST['access']; $fnm = $strippost['firstname'];
		$lnm = $strippost['lastname'];
		if( !is_numeric($uaccess) ) $uaccess = 1;
		if( $uaccess >= $ACCESS ) $uaccess = $ACCESS==500?500:$ACCESS-1;
		if( empty($username) || empty($passA) || empty($passB) || empty($email) || empty($fnm) || empty($lnm) )
			throw new Exception('All fields are required in the New User form',17);
		if( !valid_email($email) )
			throw new Exception('Please enter valid email address in the New User form',18);
		if( strlen($passA) < 6 )
			throw new Exception('Password can not be shorter than six symbols',6);
		if( $passA == $username )
			throw new Exception('Password can not be the same as the username',8);
		if( $passA != $passB )
			throw new Exception('Passwords are not the same',7);
		// all good so far
		$db = db_clients();
		$sql = "INSERT INTO operators (username, password, email, access, firstname, lastname, editedby, editeddt) VALUES ('$username', SHA1('$passA'), '$email', $uaccess, '$fnm', '$lnm', $UUID, NOW())";
		$result = $db->query($sql);
		if( !$result ) throw new Exception('Can not insert user. Possible duplicate username or email.',8);
		unset($username,$passA,$passB,$email,$uaccess,$fnm,$lnm); // success
	} // UUID & ACCESS
	catch(Exception $e) {
		$mesg = 'Request failed: '.$e->getMessage().' ('.$e->getCode().')<br>';
	}
	$style = new OperPage('Employee Roster',$UUID,'admin','editoper');
	$style->Output();
	
	if( $UUID ) {
			if( $ACCESS < 400 && ($ACCESS < 50 || $ACCESS >= 200) ) echo '<h1>Access Denied</h1>';
			else {
?>
              <h1>Edit Employees</h1>
<?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
              <p>New employee form and employee list. To see inactive (former or suspended) employees, click the radio button and then click Select. You can edit employee profile and access level if their access is lower than your own. Usernames and ID# are not editable.</p>
              <form action="editoper.php" method="post" name="formsel" id="formsel">
                Show <label><input name="active" type="radio" value="0"
				<?php if( $showactive ) echo ' checked'; ?> >
				Active</label> or <label><input name="active" type="radio" value="1"
				<?php if( !$showactive ) echo ' checked'; ?> > 
				Inactive</label> Employees: &nbsp;<input type="submit" name="Submit" value="Select">
              </form>
              <table width="90%"  border="0">
                <tr>
                  <td bgcolor="#CCCCCC" class="style1">ID &amp; username</td>
                  <td bgcolor="#CCCCCC" class="style1">Name</td>
                  <td bgcolor="#CCCCCC" class="style1">Access</td>
                  <td bgcolor="#CCCCCC" class="style1">Last Login Date </td>
                </tr>
<?php
	try {
		//if( !isset($db) )
		if( !isset($db) ) $db = db_clients();
		$sql = "SELECT uid, username, firstname, lastname, access, lastlogdate FROM operators WHERE status = $showactive";
		$result = $db->query($sql);
		while( list($uid,$unam,$fnam,$lnam,$acc,$logdate) = $result->fetch_row() ) {
?>
                <tr>
                  <td><?php echo "$uid ";
				  	if( $acc <= $ACCESS || $ACCESS == 500 ) echo "<a href='edittheoper.php?id=$uid'>$unam</a>";
					else echo $unam; ?></td>
                  <td><?php echo stripslashes($fnam).' '.stripslashes($lnam); ?></td>
                  <td><?php echo $acc.' '.AccessDesc($acc); ?></td>
                  <td><?php echo $logdate; ?></td>
                </tr>
<?php
		} // while
	}
	catch(Exception $e) {
		echo '<tr><td colspan=3 bgcolor="#FCCCCC">Failure: '.$e->getMessage().' ('.$e->getCode().')</td></tr>';
	}
?>
              </table>
              <h3>New User</h3>
              <div id="formdiv">
              <form action="editoper.php" method="post" name="formnew" id="formnew">
			  <table>
			    <tr>
                  <td>User:</td><td><input name="username" type="text" id="username" value="<?php echo $username; ?>" maxlength="15"></td>
				  <td>First Name:</td><td><input name="firstname" type="text" id="firstname" value="<?php echo $fnm; ?>" maxlength="50"></td>
				  <td>Last name:</td><td><input name="lastname" type="text" id="lastname" value="<?php echo $lnm; ?>" maxlength="50"></td>
				</tr><tr>
				  <td>Email:</td><td><input name="email" type="text" id="email" value="<?php echo $email; ?>" maxlength="100"></td>
				  <td>Access Level*:</td>
				  <td><input name="access" type="text" id="access" value="<?php echo $uaccess; ?>" maxlength="3"></td><td>&nbsp;</td><td>&nbsp;</td>
                </tr><tr>
				  <td>Password:</td><td><input name="password1" type="password" id="password1" value="<?php echo $passA; ?>" maxlength="40"></td>
				  <td>Repeat Password:</td><td><input name="password2" type="password" id="password2" value="<?php echo $passB; ?>" maxlength="40"></td><td>&nbsp;</td>
				  <td><input type="submit" name="Submit" value="Submit"> &nbsp;&nbsp;
				  <input type="reset" name="Reset" value="Reset"></td>
				</tr>
				</table>
              </form></div>
              <p>* Access level is number 0-500: 0 = none, 1-49 = data entry (user can add/edit residents), 50-199 = database manager (in addition to data entry access, user can see customer information, add employees, assign lists, and work with shadowed lists), 200-299 = customer support (user can edit customer's contact information), 300-399 = account manager (in addition to customer support level, user can add new customers and edit full customer's record), 400-499 = administrator (can add/edit users - employees),  500 = super administrator (no restrictions)</p>
              <?php		} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
