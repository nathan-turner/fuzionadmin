<?php
    require("globals.php5");
    //require("cookies.php5");
	
	$email0="nturner@phg.com";
	$fname ="Nathan";
	$lname="Turner";
	if( valid_email($email0) ) {
		
		$hedr = "From: \"Physician Career\" <help@physiciancareer.com>\r\n";
				$hedr .= 'Date: '.date('r')."\r\nPrecedence: normal\r\n";
				$hedr .= "Organization: PhysicianCareer.com\r\nMIME-Version: 1.0\r\n".
					"Content-Type: multipart/alternative;\r\n  boundary=\"----=_NextPart_000_000C_01C7836B.F0749520\"\r\n".
					"Content-Language: en-us\r\n";
				$hedr .= "X-Originator: $UUID/$REMOTE_ADDR\r\nX-Mailer: FuzionHG Mail Processor ".
					phpversion();
				$fname = stripslashes($fname); // K'neal
				$lname = stripslashes($lname); // O'Hara
				$rcpt = "\"$fname $lname\" <$email0>";
				$msgbodyex = "test email from pc";
				
				$hedr2 = "From: tbroxterman@physiciancareer.com \r\n";
				
		$subj = 'Your physician profile on PhysicianCareer.com';
		mail($email0,$subj,$msgbodyex,$hedr2);
					/*if( !mail($rcpt,$subj,$msgbodyex,$hedr) ) {
						$action .= 'FAIL';
						
						throw new Exception('Profile Email to '.htmlspecialchars($rcpt).' failed. The record itself was saved.',__LINE__);
					}*/
		
	}
	
	$header="From: help@physiciancareer.com \r\n";
	$email0="nturner@phg.com";
	$subj = 'test email';
	$msgbodyex = "test email from pc";
	mail($email0,$subj,$msgbodyex,$header);
?>