<?php
	// ready 3/27/07 - SL
    require("globals.php5");
    require("cookies.php5");
/*  user id <> 0 if auth

	OK, this is LOGIN/PASSWORD RESET form. parameters are:
	$_POST['username'],  $_POST['password'],  $_POST['remember'],  $_POST['lostpass']
	LOGIN:
	when UUID = 0 - it is normal process, proceed with login, then meta-redirect to home.
	when UUID <> 0 - may be they want to relogin under another name.
	  if they have not posted uname/pass show warning, but allow.
	  if they posted uname/pass, "logout", clear cookies, then login as usual
	LOSTPASS:
	 when UUID = 0 - ok, reset.
	 when UUID <> 0 and they posted lostpass, so, it's weird but let them do it.
*/
	$result = true; $redir = false;
	$mesg = '';
	$redirurl = '2; URL=index.php';
	if( isset($_POST['lostpass']) && $_POST['lostpass'] ) {
		// reset password
		try {
			$db = db_clients();
			$user = new User($db,0,$_POST['lostpass']);
			$user->reset_password($db);
			$mesg = 'New password was sent to registered email address';
		}
		catch(Exception $e) {
			$result = false;
			$mesg = 'Request failed: '.$e->getMessage().' ('.$e->getCode().')';
		}
	}
	elseif( isset($_POST['username'],$_POST['password']) 
		&& $_POST['username'] && $_POST['password'] != '' ) {
		// login requested
		try {
			$db = db_clients();
			$user = new User($db,0,$_POST['username']);
			$olduser = $UUID;
			$expdate = $user->exp_date;
			$today = date('Y-m-d');
			if( !$expdate ) $expdate = $today; // null exp date - ok
			if( $user && $user->status && $user->is_oper()
				&& $expdate >= $today
				&& $user->password == sha1(stripslashes($_POST['password'])) && (!EMERGENCY || $_SESSION['access']==500) ) { // success
				if( $olduser ) logout(0,!$_POST['remember']);
				$UUID = $user->uid;			$_SESSION['user_id'] = $UUID;
				$ACCESS = $user->access; 	$_SESSION['access'] = $ACCESS;
				$ACCT = $user->acct;		$_SESSION['acct'] = $ACCT;
				$MASTER = $user->master_acct;	$_SESSION['master_acct'] = $MASTER;
				$user->setlastlogin($db);
				$_SESSION['userobj'] = clone $user;
				if( isset($_POST['remember']) && $_POST['remember'] ) {
					setcookie('uid',$UUID,time()+3600*24*7); 
					setcookie('uha',md5($user->password.$REMOTE_ADDR.$user->email.$UUID),time()+3600*24*7);
				}
				if( $_POST['Referal'] ) {
					$redirurl = '2; URL='.$_POST['Referal'];
					$redirman = $_POST['Referal'];
				}
				else $redirman = 'index.php';
				$mesg = 'Login successful. You will be redirected to another page in few seconds.'.
					"Or, <a href='$redirman'>click here</a> to proceed.";
				$redir = true;
			}
			else {
				$result = false;
				$mesg = 'Login failed. Password is incorrect or access denied.';
			}
		}
		catch(Exception $e) {
			$result = false;
			$mesg = 'Login failed: '.$e->getMessage().' ('.$e->getCode().')';
		}
	}
	elseif( $UUID ) {
		$mesg = 'You are already logged in as '.$USER->firstname.' '
			.$USER->lastname.'. You can re-login as another user now';
	}
	$style = new OperPage('Login',$UUID,'index','login',($redir?$redirurl:NULL));
	$scrip = <<<TryMe

var subwind;

function poptest() {
  subwind = window.open("","emailcheck","menubar=0,toolbar=0,width=350,resizable=0,location=0,height=300");
  setTimeout("popshow()",60);
  document.formx.popte.disabled = true;
  return true;
}

function popshow() {
	var d = subwind.document;
	d.write("<html><head><title>Success</title></head><body><h1>Success</h1><p>Test Passed</p>",
		"<p><a href='javascript:window.close()'>Close Window</a></p></body></html>");
	d.close();
	subwind.focus();
}

TryMe;
	$scrip2 = "<script language=\"JavaScript\" type=\"text/JavaScript\"><!--\n".$scrip."// -->\n</script>\n";
	$style->Output($scrip2);
?> 
        <h3>User Login</h3>
        <?php
	if( $mesg ) echo "<p id='".($result?'warning':'error')."_msg'>$mesg</p>";
?>
        <p>Please use your assigned user name and password to login. If you forgot 
          your user name, please try the user part of your email address, or  ask your manager.</p>
        <p>If you are unable to login (this page shows that you are logged in successfully and then redirects you back to the login form), please verify your browser's privacy settings. This site uses cookies to handle login information. Please adjust your browser settings to allow cookies, or make an exception for this site only. You can click on your browser name link to see sample cookie settings for <a href="http://fuzionhg.com/images/mozilla-cookie-help.jpg" title="Sample settings - Mozilla" target="_blank">Mozilla</a>, <a href="http://fuzionhg.com/images/firefox-cookie-help.jpg" title="Sample settings - Firefox" target="_blank">Firefox</a> and <a href="http://fuzionhg.com/images/ie-cookie-help.jpg" title="Sample settings - IE" target="_blank">Internet Explorer</a>, that will work.</p>
        <div id="formdiv"> 
          <form name="form1" method="post" action="login.php">
            <p> User name: <input name="username" type="text" maxlength="120" size="20">
			Password: <input name="password" type="password" id="password" maxlength="40" size="20">
			&nbsp;<input name="remember" type="checkbox" id="remember" checked value="1">&nbsp;Remember me on this computer&nbsp;
			<input type="submit" name="Submit" value="&nbsp;Log In&nbsp;">
          </form>
        </div>
        <h3>Can not remember your password?</h3>
        <p>To reset your password, fill in the following form, and new system-generated 
          password will be sent to your registered email address. You can change 
          this password later, when you log in to your profile.<br clear="all">
        </p>
        <div id="formdiv2"> 
          <form name="form2" method="post" action="login.php">
            <p>User name: 
              <input name="lostpass" type="text" maxlength="120">
              <input type="submit" name="Submit" value="Reset Password">
          </form>
        </div>
        <br clear="all">
        <p class="tdborder3399"><strong><em>Note</em></strong>: Javascript support required. If you disabled javascript in your browser, please enable it for this site. This site can show popups with important notices. If you use any popup blockers, please put this site into the 'Allow List'. You can test both Javascript and Popup features below. </p>
              <form name="formx" method="post" action="login.php">
                <label>JS & Popup Test: 
                <input name="popte" type="text" id="popte" onFocus="poptest()" value="Click here to test. A window should pop up." size="50">
                <input type="button" name="Button" value="Try again" onClick="popte.disabled = false;">
</label>
              </form>
<?php
	$style->ShowFooter();
?>
