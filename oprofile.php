<?php
    require("globals.php5");
    require("cookies.php5");
    // $UUID <> 0 if auth
	$mesg = '';
	if( $UUID && $_POST['oldpass'] && $_POST['newpass1'] ) {
		$newp = $_POST['newpass1'];
		if( $newp === $_POST['newpass2'] ) {
		 try {
			$db = db_clients();
			$USER->change_password($db, $_POST['oldpass'], $newp );
		 }
		 catch(Exception $e) {
		 	$mesg = 'Error: '.$e->getMessage().' ('.$e->getCode().')';
		 }
		} else $mesg = 'New password entered on line 1 is different from line 2. Please try again.';
	}
	$style = new OperPage('Your Profile',$UUID,'index','oprofile');
	$style->Output();
	
	if( $UUID ) {
?>
            <h1><?php echo $USER->firstname." ".$USER->lastname; ?></h1>
              <p>View your own profile and access level. Change password.<br>
              </p><form action="oprofile.php" method="post" name="chpass">
              <table width="80%"  border="0">
                <tr>
                  <td width="21%">User name: </td>
                  <td width="79%" class="tdborder3399"><?php echo $USER->username; ?></td>
                </tr>
                <tr>
                  <td>Email:</td>
                  <td class="tdborder3399"><?php echo $USER->email; ?></td>
                </tr>
                <tr>
                  <td>Access level: </td>
                  <td class="tdborder3399"><?php echo $ACCESS.' '.AccessDesc($ACCESS); ?></td>
                </tr>
                <tr>
                  <td>Old Password: </td>
                  <td><input name="oldpass" type="password" id="oldpass" size="40" maxlength="40"></td>
                </tr>
                <tr>
                  <td>New password: </td>
                  <td><input name="newpass1" type="password" id="newpass1" size="40" maxlength="40"></td>
                </tr>
                <tr>
                  <td>New Password again: </td>
                  <td><input name="newpass2" type="password" id="newpass2" size="40" maxlength="40"></td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                  <td><input type="submit" name="Submit" value="Submit"></td>
                </tr>
              </table>
              </form>
<?php
			if( $mesg ) {
				echo "<p id='error_msg'>$mesg</p>";
			}	
		}
		else showLoginForm(); // UUID
	    $style->ShowFooter();
?>
