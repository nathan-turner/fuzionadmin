<?php
    require("globals.php5");
	//set_time_limit(0);
	//ignore_user_abort(true);
	//define(MAXMSGS,100); // max emails per list
    require("cookies.php5");
    // $UUID <> 0 if auth
	$mesg = '';
	$style = new OperPage('Emailing',$UUID,'admin','custemail');
	///// JavaScriplet below
	$scrip = <<<TryMe

function readydone() {
	var h3a = document.getElementById("pleasewait");
	var h3b = document.getElementById("doneid");
	h3b.style.display = "block";
	h3a.style.display = "none";
	return true;
}

TryMe;
	$scrip2 = "<script language=\"JavaScript\" type=\"text/JavaScript\"><!--\n".$scrip.
		"// -->\n</script>";
	$style->Output($scrip2);
	if( $UUID ) {
?>
        <h1>Sending Email</h1>
        <h3 id="pleasewait">Please wait <img src="images/sending.gif" width="88" height="31" alt="hold on" border="0"></h3>
		<h3 id="doneid" style="display: none">Done!</h3>
		<p>Please hold while your list is being processed. It can take several minutes, depending on the server load.</p>
		<p>
<?php
	  try {
		if( isset($_POST['sendit']) ) {
			//$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
			$strippost = $_POST;
			extract($strippost,EXTR_SKIP);
			$preg = '/\\ssrc="data:image[^"]*(?:"|$)/';
			$q = stripslashes($msgbody);
			$msgbody = addslashes(preg_replace($preg,'',$q));
			if( $_SESSION['cemail_preview'] === 'yes' ) {
				unset($_SESSION['cemail_preview']);
				$subj = stripslashes(strip_tags(nl2br($subj)));
				$hedr = "From: \"$USER->firstname $USER->lastname\" <$USER->email>\r\n";
				$hedr .= 'Date: '.date('r')."\r\nOrganization: Fuzion Health Group\r\nMIME-Version: 1.0\r\n";
				$mimebo = "----=_NextPart_000_000C_01C7836B.".dechex(time());
				$hedr .= "Content-Type: multipart/alternative;\r\n  boundary=\"$mimebo\"\r\n";
				$hedr .= "Precedence: normal\r\nX-Originator: $UUID/$REMOTE_ADDR\r\nX-Mailer: FuzionHG Maillist Processor ".phpversion();
				$signex = "\n\n---\n$USER->firstname $USER->lastname\n";
				if( !$ACCESS ) $signex .= "$USER->title\n$USER->company\n$USER->phone\n";
				else $signex .= "PhysicianCareer.com\n";
				$signex .= "$USER->email\n";
				$signexht = "<p>\r\n<hr />\r\n$USER->firstname $USER->lastname<br />\r\n";
				if( !$ACCESS ) $signexht .= "$USER->title<br />\r\n$USER->company<br />\r\n$USER->phone<br />\r\n";
				else $signexht .= "PhysicianCareer.com<br>\r\n";
				$signexht .= "$USER->email</p>\r\n";
				$numsent = 0;
				// One copy is for user himself
				$email = stripslashes($USER->email); // O'Hara@yahoo.com is valid? not really
				$fname = stripslashes($USER->firstname); // K'neal
				$lname = stripslashes($USER->lastname); // O'Hara
				$rcpt = "\"$fname $lname\" <$email>";
				$msgbodyex = str_replace('FIRSTNAME',$fname,html_entity_decode(strip_tags(stripslashes($msgbody))));
				$msgbodyex = str_replace('LASTNAME',$lname,$msgbodyex);
				$msgbodyex = str_replace("\n.", "\n..", $msgbodyex);
				$msgbodyex = preg_replace('/&#\d+;/', '-', $msgbodyex);
				$msgbodyex = iconv("UTF-8", "us-ascii//TRANSLIT",wordwrap($msgbodyex, 67));
				if( $sign ) $msgbodyex .= $signex;
				$msgbodyexht = str_replace('FIRSTNAME',$fname,stripslashes($msgbody));
				$msgbodyexht = str_replace('LASTNAME',$lname,$msgbodyexht);
				$msgbodyexht = str_replace("\n.", "\n..", $msgbodyexht);
				if( $sign ) $msgbodyexht .= $signexht;

				$msg = <<<HereEmail

This is a multipart message in MIME format.

--$mimebo
Content-Type: text/plain;
	charset="us-ascii"
Content-Transfer-Encoding: 7bit

$msgbodyex

--$mimebo
Content-Type: text/html;
	charset="UTF-8"
Content-Transfer-Encoding: 8bit

$msgbodyexht

--$mimebo--

HereEmail;

				echo htmlspecialchars($rcpt,ENT_COMPAT | ENT_HTML5,'UTF-8').' &hellip;';
				if( !mail($rcpt,$subj,$msg,$hedr) )
					throw new Exception('Email to '.htmlspecialchars($rcpt,ENT_COMPAT | ENT_HTML5,'UTF-8').' failed.',__LINE__);
				echo "here you are!<br />\n";
				if( empty($testrun) ) { // debug
					// the rest is for clients
					$db = db_career();
					$sql = "select firstname,lastname,email from clients where status = 1 and exp_date > curdate() and subscription & 16 = 0";
					$exresult = $db->query($sql);
					while( list($fname, $lname, $email) = $exresult->fetch_row() ) {
						$email = stripslashes($email); // O'Hara@yahoo.com is valid? not really
						if( !valid_email($email) ) continue;
						$fname = stripslashes($fname); // K'neal
						$lname = stripslashes($lname); // O'Hara
						$rcpt = "\"$fname $lname\" <$email>";
						$msgbodyex = str_replace('FIRSTNAME',$fname,strip_tags(stripslashes($msgbody)));
						$msgbodyex = str_replace('LASTNAME',$lname,$msgbodyex);
						$msgbodyex = preg_replace('/&#\d+;/', '-', $msgbodyex);
						$msgbodyex = iconv("UTF-8", "us-ascii//TRANSLIT",wordwrap($msgbodyex, 67));
						$msgbodyex = str_replace("\n.", "\n..", $msgbodyex);
						if( $sign ) $msgbodyex .= $signex;
						$msgbodyexht = str_replace('FIRSTNAME',$fname,stripslashes($msgbody));
						$msgbodyexht = str_replace('LASTNAME',$lname,$msgbodyexht);
						$msgbodyexht = wordwrap($msgbodyexht, 78);
						$msgbodyexht = str_replace("\n.", "\n..", $msgbodyexht);
						if( $sign ) $msgbodyexht .= $signexht;

						$msg = <<<HereEmail

This is a multipart message in MIME format.

--$mimebo
Content-Type: text/plain;
	charset="us-ascii"
Content-Transfer-Encoding: 7bit

$msgbodyex

--$mimebo
Content-Type: text/html;
	charset="UTF-8"
Content-Transfer-Encoding: 8bit

$msgbodyexht

--$mimebo--

HereEmail;
						echo htmlspecialchars($rcpt,ENT_COMPAT | ENT_HTML5,'UTF-8').' &hellip;';
						if( !mail($rcpt,$subj,$msg,$hedr) )
							echo ' Email to '.htmlspecialchars($rcpt,ENT_COMPAT | ENT_HTML5,'UTF-8').' failed!<br />\n';
						else { $numsent++; echo "ok!<br />\n"; }
					}
					$exresult->free();
					unset($exresult);
				} // testrun
				if( $numsent || $testrun ) {
					$ss = $numsent <= 1?' was':'s were';
					$okmesg = "Success! $numsent email$ss sent. The last recipient was ".htmlspecialchars($rcpt,ENT_COMPAT | ENT_HTML5,'UTF-8');
				}
				else $mesg = '0 valid email addresses found!';
			} 
			else throw new Exception('Duplicate check triggered! This email was already sent. Press Preview again if you want to repeat this mailing.',__LINE__);
		}
	}
	catch(Exception $e) {
		$mesg = 'Request failed: '.$e->getMessage().' ('.$e->getCode().')<br>';
		$numsent = 0;
	}
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
	if( $okmesg ) echo "<h3 id='warning_msg'>$okmesg</h3>";
?><script language="JavaScript" type="text/JavaScript">
readydone();
</script>
<?php
		if( $okmesg ) echo '<p>The email was sent to all available email addresses from your list.</p>';
?>
<form action="custemail.php" method="post" name="form2send" id="form2send">
<input type="hidden" name="testrun" id="testrun2" value="<?php echo $testrun?'1':''; ?>">
<input name="subj" type="hidden" id="subj" value="<?php echo htmlspecialchars(stripslashes($subj),ENT_COMPAT | ENT_HTML5,'UTF-8'); ?>">
<input name="msgbody" id="msgbody" type="hidden" value="<?php echo htmlspecialchars(stripslashes($msgbody),ENT_COMPAT | ENT_HTML5,'UTF-8'); ?>">
<input name="sign" type="hidden" id="sign" value="<?php echo $sign?'1':''; ?>">
<button name="getback" type="submit" id="getback" 
value="Preview">Click to return to Preview page</button>
</form>

        <?php 	
	  } // UUID
	  else showLoginForm();
	$style->ShowFooter();
?>
