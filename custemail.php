<?php
	// fixed - done - 3/29/07 - SL
    require("globals.php5");
    require("cookies.php5");
	// $UUID <> 0 if auth
	$mesg = '';
	$result = false;
	if( $UUID && $ACCESS && $ACCESS >= 200 ) try {
		if( isset($_POST['export']) ) {
			$db = db_clients();
			$sql = "select uid,firstname,lastname,email from clients where status = 1 and exp_date > curdate()";
			$exresult = $db->query($sql);
		}
		elseif( isset($_POST['preview']) ) {
			//$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
			$strippost = $_POST;
			extract($strippost,EXTR_SKIP);
			$preg = '/\\ssrc="data:image[^"]*(?:"|$)/';
			$q = stripslashes($msgbody);
			$msgbody = addslashes(preg_replace($preg,'',$q));
			$_SESSION['cemail_preview'] = 'yes';
		}
		elseif( isset($_POST['getback']) ) {
			//$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
			$strippost = $_POST;
			extract($strippost,EXTR_SKIP);
		}
	}
	catch(Exception $e) {
		$mesg = 'Request failed: '.$e->getMessage().' ('.$e->getCode().')<br>';
	}
	$style = new OperPage('Email Customers',$UUID,'admin','custemail');
	///// JavaScriplet below
	$scrip = <<<TryMe
	
var subwind;
var listed;

function poptest() {
  subwind = window.open("","emails","menubar=0,scrollbars=yes,toolbar=0,width=450,location=0,height=400");
  setTimeout("popshow()",60);
  return true;
}

function popshow() {
        var d = subwind.document;
        d.write("<html><head><title>Email List</title></head><body><p>",
				listed,
                "</p></body></html>");
        d.close();
        subwind.focus();
}

function copyToCLP() {
	var elist = document.getElementById("emdiv");
	var list1 = elist.innerHTML;
	if( list1.length > 4 && list1.charAt(list1.length - 1) == '>' )
	    list1 = list1.substr(0,list1.length - 2); // kill 1/2 of <br>
	if( list1.length > 2 ) listed = list1.substr(0,list1.length - 2);
	else listed = "Nothing found";
	poptest();
	return true;
}

TryMe;
	$scrip2 = "<script type=\"text/javascript\" src=\"/ckeditor/ckeditor.js\"></script>\n<script language=\"JavaScript\" type=\"text/JavaScript\"><!--\n".$scrip.
		"// -->\n</script>";
	$style->Output($scrip2);
	if( $UUID ) {
			if( $ACCESS < 200 ) echo '<h1>Access Denied</h1>';
			else {
?>
        <h1>Email</h1>
        <p>You can send an email to all active customers. You can export their email addresses to your email program. Or, if you prefer, you can use the email feature on this page.</p>
        <h3>Export Email Addresses</h3>
<?php if( $exresult && $exresult->num_rows ) { ?>
		<p>Copy addresses from the frame below and paste them into your email program or list management software. You can open this list as a separate window, for your convenence.</p>
		<div id="emdiv" style="width: 440px; border: inset; height: 144px; padding: 6px; overflow: auto">
<?php 
			$delim = ', ';
			if( $_POST['delim'] === 'S' ) $delim = '; ';
			elseif( $_POST['delim'] === 'N' || $_POST['delim'] === 'X' ) $delim = '<br>';
			//$emails = '';
			while( list($cid,$fname,$lname,$email) = $exresult->fetch_row() ) {
					//$emails .= stripslashes("\"$fname $lname\" &amp;lt;$email&amp;gt;$delim ");
					if( $_POST['delim'] === 'X' ) echo stripslashes('"","","'.$fname.'","' .$lname. '","","","","","","","","","","' .$email. '","","","","","","","","",""' .$delim);
					else echo stripslashes("\"$fname $lname\" &lt;$email&gt;$delim");
			}
			//$emails = substr($emails,0,strlen($emails)-2);
?>
		</div>
		<p onClick='copyToCLP()'><u>Click here</u> to open this list in a pop-up window</p>
<?php } ?>
		<div id="formdiv">
        <form action="custemail.php" method="post" name="form1ex" id="form1ex">
          List Delimiter: 
          <label><input name="delim" type="radio" value="C" checked>
Comma</label> or <label><input name="delim" type="radio" value="S"> Semicolon</label> 
<label><input name="delim" type="radio" value="N"> New&nbsp;line</label>
<label><input name="delim" type="radio" value="X"> Newsletter</label>
<input name="export" type="submit" id="export" value="Export">    
        </form>
		</div>
        <h3>Send a message</h3>
<?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
	if( $okmesg ) echo "<h3 id='warning_msg'>$okmesg</h3>";
?>
		<p><em>Please note that your server connection expires in approx. 15 minutes unless you click on a link or a button. To avoid losing your work, please press</em> Preview <em>button from time to time while composing your message.</em></p>
        <form action="custemail.php" method="post" name="form2send" id="form2send">
          <table width="90%"  border="0" cellspacing="0" cellpadding="1">
            <tr>
              <td>Subject: </td>
              <td><input name="subj" type="text" id="subj" size="44" maxlength="120" value="<?php echo htmlspecialchars(stripslashes($subj),ENT_COMPAT | ENT_HTML5,'UTF-8'); ?>"></td>
            </tr>
            <tr>
              <td>Text Messsage*:</td>
              <td><textarea name="msgbody" cols="70" rows="8" id="msgbody"><?php echo (stripslashes($msgbody)); ?></textarea>
				<script type="text/javascript">
				//<![CDATA[
					CKEDITOR.replace( 'msgbody' );
				//]]>
				</script></td>
            </tr>
            <tr>
              <td>&nbsp;</td>
              <td><label><input name="sign" type="checkbox" id="sign" value="1" <?php echo $sign?'checked':''; ?>>
                Include Signature</label> &nbsp;&nbsp;&nbsp;
                <input name="preview" type="submit" id="preview" value="Preview"> 
                &nbsp; <input type="reset" name="Reset" value="Reset"></td>
            </tr>
          </table>
        </form>
		<p>*&nbsp; In the text of the message, words FIRSTNAME and LASTNAME (all caps) will be automatically replaced with client's first and last name, respectively. Preview text will appear below when you press Preview button.</p>
        <?php if( $preview ) { ?>
        <p>Please review your message below. If you want to make any changes, please do them in the above form and then press Preview button again. Press Send button when you are ready to send your message. It will be sent immediately and can not be revoked. </p>
		<div id="prediv" style="width: 740px; border: inset; height: 344px; padding: 6px; overflow: auto">
	        <table width="80%"  border="0" cellspacing="0" cellpadding="1" style="border-style:none ">
              <tr>
                <td>From:</td>
                <td>&quot;<?php echo "$USER->firstname $USER->lastname"; ?>&quot; &lt;<?php echo $USER->email; ?>&gt;</td>
              </tr>
              <tr>
                <td>To:</td>
                <td>&quot;John Smith&quot; &lt;email@address&gt;</td>
              </tr>
              <tr>
                <td>Subject:</td>
                <td><?php echo stripslashes($subj); ?></td>
              </tr>
              <tr>
                <td>Date:</td>
                <td><?php echo date('r'); ?></td>
              </tr>
            </table>
<?php 
		$msgbodyex = str_replace('FIRSTNAME','John',stripslashes($msgbody));
		$msgbodyex = str_replace('LASTNAME','Smith',$msgbodyex);
		//$msgbodyex = preg_replace('/&#\d+;/', '-', $msgbodyex);
		$msgbodyex = wordwrap($msgbodyex, 67);
		echo $msgbodyex;
		if( $sign ) {
			echo "<p>\r\n<hr />\r\n$USER->firstname $USER->lastname<br />\r\n";
			if( !$ACCESS ) echo "$USER->title<br />\r\n$USER->company<br />\r\n$USER->phone<br />\r\n";
			else echo "PhysicianCareer.com<br>\r\n";
			echo "$USER->email</p>\r\n";
		}
?>
			<hr>
		    <form action="custsendemail.php" method="post" name="form3send" id="form3send">
	        <p style="font-weight:bold">You are about to send this out. 
              <input name="testrun" type="checkbox" id="test1" value="1"> Send it to me ONLY as a test.<br />
	          <input name="sendit" type="submit" id="sendit" value="Send it now">
              <input name="subj" type="hidden" id="subj1" value="<?php echo htmlspecialchars(stripslashes($subj),ENT_COMPAT | ENT_HTML5,'UTF-8'); ?>">
              <input name="sign" type="hidden" id="sign1" value="<?php echo $sign; ?>">
              <input name="msgbody" type="hidden" id="msgbody1" value="<?php echo htmlspecialchars(stripslashes($msgbody),ENT_COMPAT | ENT_HTML5,'UTF-8'); ?>">
	        </p>
          </form>
		</div>
<?php 	} // preview
		} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
