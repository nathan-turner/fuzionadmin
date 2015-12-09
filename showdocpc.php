<?php
    require("globals.php5");
    require("cookies.php5");
    // $UUID <> 0 if auth
	// normal: ?id=$docid&lid=$lid&y=$yer&pos=$pos
	// params: 	$_REQUEST['id'], $_REQUEST['lid'], $_REQUEST['y'], $_REQUEST['pos']
	// shadow mode:
	// shadow: ?id=$docid&lid=$lid&pos=$pos&shadow=1&next=$nextid&prev=$preved&y=".DESTY
	// params: 	$_REQUEST['id'], $_REQUEST['lid'], $_REQUEST['y'], $_REQUEST['pos']
	//			$_REQUEST['shadow'], $_REQUEST['next'], $_REQUEST['prev']
	//			no cookies in shadows
	$formpage = 1;
	$docid = $_REQUEST['id'];
	$lid = $_REQUEST['lid'];
	$yer = 2005; // must be 2005
	$pos = $_REQUEST['pos'];
	$peek = $_REQUEST['peek'];
	$very= $_REQUEST['ck']; // true = show checkins
	//$verification = $_SESSION['verification'] && $lid == 250;
	//if( $lid != 250 ) unset($_SESSION['verification']);
	//$cook_lid = $lid? $lid.$peek: $yer.$peek;
	$newt = time();

	if( $UUID && $ACCESS && $docid ) try {
	 	$nodb = db_notes();
		$peekuid = $UUID;
		/*if( $peek && is_numeric($peek) && $peek != $UUID ) {
			// check if peek is allowed: lists have same ACCT and (you are MASTER or list is shared)
			if( !isset($db) ) $db = db_career();
			$sql = "select acct,shared from custlistdesc where listid = $lid and uid = $peek";
			$result = $db->query($sql);
			if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not find list',__LINE__);
			list($pact,$psha) = $result->fetch_row();
			$result->free();
			if( $pact == $ACCT && ($psha < $ACCESS) ) $peekuid = $peek;
			else throw new Exception('You can not peek into this list',__LINE__);
			$peekarg = "&peek=$peek";
		}*/
	  $yerex = ''; //is_numeric($yer)? " and resyear=$yer":''; // !!	
	  	$db = db_career();
		if( is_numeric($lid) && $lid ) {
			if( $pos ) {
				$sql = "select memberuid from custlistsus where owneruid = $peekuid and listid = $lid  LIMIT ".($pos-1).', 3';
				$result = $db->query($sql);
				list($preved) = $result->fetch_row();
				$result->fetch_row(); // skip current
				list($nextid) = $result->fetch_row();
			}
			else {
				$sql = "select memberuid from custlistsus where owneruid = $peekuid and listid = $lid LIMIT 0, 2";
				$result = $db->query($sql);
				$result->fetch_row(); // skip current
				list($nextid) = $result->fetch_row();
			}
			$result->free();
			//$db->close();
		} // lid
		else { // lid = 0
			//$resdb = db_resident($yer);
			if( $pos ) {
				$sql = "select memberuid from custlistsus where listid = 0 and owneruid = $peekuid LIMIT ".($pos-1).', 3';
				$result = $db->query($sql);
				list($preved) = $result->fetch_row();
				$result->fetch_row(); // skip current
				list($nextid) = $result->fetch_row();
			}
			else {
				$sql = "select memberuid from custlistsus where listid = 0 and owneruid = $peekuid LIMIT 0, 2";
				$result = $db->query($sql);
				$result->fetch_row(); // skip current
				list($nextid) = $result->fetch_row();
			}
			$result->free();
		}
	  // got $preved and $nextid, which can be NULL/false/unset
	  // $db is set now
		if( !isset($db) ) $db = db_career();
		$doc = new Physician($db,$docid);
		
		$hashlink = 'http://physiciancareer.com/log-in/?i='.$doc->ph_id
		.'&d='.$newt.'&mm='. sha1($doc->ph_id.$doc->fname.$doc->lname.$newt.'Please let me in, I got this email - this is the secret code!');
		$hashlinkQP = 'http://physiciancareer.com/log-in/?i=3D'.$doc->ph_id
		.'&d=3D'.$newt.'&mm=3D'. sha1($doc->ph_id.$doc->fname.$doc->lname.$newt.'Please let me in, I got this email - this is the secret code!');
		// now process form buttons
		if( isset($_POST['submit']) ||  isset($_POST['submitcv']) || isset($_POST['savest']) ) {
			$action = $doc->reg_date? 'UPDATE':'SAVE';
			// fname midname lname addr1 addr2 city state zip phone spec ...
			unset($formpage);
			$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
			extract($strippost,EXTR_SKIP);
		// readonly check block. NB: indent is wrong
			if( $fname ) $doc->fname = $fname; else $mesg .= "no first name; ";
			if( $lname ) $doc->lname = $lname; else $mesg .= "no last name; ";
			$doc->midname = $midname;
			$doc->mddo = $mddo;
			$doc->title = $title;
			$doc->addr1 = $addr1;
			$doc->addr2 = $addr2;
			$doc->city = $city;
			if( $state && $state != '--' ) $doc->state = $state; else $mesg .= "no home state; ";
			if( $zip ) $doc->zip = $zip; //else $mesg .= "no zip code; ";
			$doc->ofaddr1 = $ofaddr1;
			$doc->ofaddr2 = $ofaddr2;
			$doc->ofcity = $ofcity;
			$doc->ofstate = $ofstate;
			$doc->ofzip = $ofzip;
			$doc->homephone = $homephone;
			$doc->cellphone = $cellphone;
			$doc->officephone = $officephone;
			$doc->officeext = $officeext;
		    if ( $_POST['passwd1'] !== $_POST['passwd2'] ) {
				$passwd1 = $passwd2 = '';
				throw new Exception ('The passwords you entered do not match. Please try again.',__LINE__);
			}
			if ( !empty($passwd1) && strlen($passwd1) < 4 ) {
				$passwd1 = $passwd2 = '';
				throw new Exception ("Password is too short. Please enter something longer.",__LINE__);
			}
			$passwd1 = $passwd2 = $_POST['passwd1']; // reset to original in case some weirdo uses double-quotas in pwd

			// change it now if not empty
			if( !empty($passwd1) ) $doc->change_password($db,NULL,$passwd1);
			//if( $validpas ) { // only if valid password entered
				if( !empty($secret_q) && !empty($secret_a) ) {
					$sqa = strtolower($secret_a);
					$doc->secret_q = $secret_q; $doc->secret_a = $secret_a;
				}
			//}
			if( $spec != '---' && $spec ) {
				if( $doc->spec != $spec ) { $doc->specswap(); $action .= ' SPECSWAP'; }
				$doc->spec = $spec; 
			}
			else $mesg .= "no specialty; ";
			$doc->pstatus = $pstatus;
			$doc->spec_2nd = $spec_2nd;
			$doc->school = $school;
			$doc->sch_loc = $sch_loc;
			$doc->amg = $amg?1:0;
			if( !empty($sch_state) ) {
				$doc->sch_state = $sch_state;
				if( $sch_state !== '--' ) $doc->amg = 1;
			}
			if( $sch_year && is_numeric($sch_year) ) $doc->sch_year = $sch_year;
			else $doc->sch_year = NULL;  
			
			if( $program ) $doc->program = trim($program); else $mesg .= "no res.program name; ";
			$doc->program_2 = trim($program_2);
			$doc->res_city = $res_city; $doc->res2_city = $res2_city;
			if( $res_spec && $res_spec !== '---' ) $doc->res_spec = $res_spec; else $mesg .= "no residency specialty; ";
			$doc->res2_spec = $res2_spec;
			$doc->res_state = $res_state; $doc->res2_state = $res2_state;
			if( $res_year && is_numeric($res_year) ) $doc->res_year = $res_year;
			else { $doc->res_year = NULL; $mesg .= "no residency grad. year; "; }
			if( $res2_year && is_numeric($res2_year) ) $doc->res2_year = $res2_year;
			else $doc->res2_year = NULL;
			
			$doc->fellowship = trim($fellowship);  $doc->fel_city = $fel_city;
			$doc->fellow_2 = trim($fellow_2); $doc->fel2_city = $fel2_city;
			$doc->fel_state = $fel_state; $doc->fel2_state = $fel2_state;
			$doc->fel_city = $fel_city; $doc->fel2_city = $fel2_city;
			$doc->fel_spec = $fel_spec; $doc->fel2_spec = $fel2_spec;
			if( $fel_year && is_numeric($fel_year) ) $doc->fel_year = $fel_year;
			else $doc->fel_year = NULL;
			if( $fel2_year && is_numeric($fel2_year) ) $doc->fel2_year = $fel2_year;
			else $doc->fel2_year = NULL;
			
			$doc->licensed = $licensed;
			$doc->avail_date = $avail_date;
			if(date('Y-m-d')>$avail_date) //not in future
				$doc->avail_date = date('Y-m-d');
			//if( substr($avail_date,0,4) < date('Y') ) $mesg .= "avail.date is old; ";
			$doc->visa_status =$visa_status;
			$doc->citizen = $citizen?($citizen==1?1:($citizen==2?2:4)):0;
			
			$doc->bcbe =$bcbe;
			if( $bcbe_year && is_numeric($bcbe_year) ) $doc->bcbe_year = $bcbe_year;
			else $doc->bcbe_year = NULL;
			
			$doc->marital_status = $marital_status=='X'?NULL:$marital_status;
			$doc->spouse = $spouse;
			$doc->spouse_prof = $spouse_prof;
			$doc->spouse_spec = $spouse_spec;
			$doc->spouse_state = $spouse_state;
			$doc->birth_state = $birth_state;
			$doc->children = $children?1:0;
			  
			$doc->pref_city = $pref_city;
			  
			/*$pref_school = '';
			if (isset($pref_school_1)) $pref_school .= $pref_school_1;
			if (isset($pref_school_2)) $pref_school .= ($pref_school?',':'').$pref_school_2;
			if (isset($pref_school_3)) $pref_school .= ($pref_school?',':'').$pref_school_3;
			$doc->pref_school = $pref_school;*/
 
			$pref_commu = '';
			if (isset($pref_commu_1)) $pref_commu .= ($pref_commu?',':'').$pref_commu_1;
			if (isset($pref_commu_2)) $pref_commu .= ($pref_commu?',':'').$pref_commu_2;
			if (isset($pref_commu_3)) $pref_commu .= ($pref_commu?',':'').$pref_commu_3;
			$doc->pref_commu2 = $pref_commu; // 2!!!
			
			$pref_region = '';
			if (isset($pref_region_1)) $pref_region .= '1'; 
			if (isset($pref_region_2)) $pref_region .= ($pref_region?',':'').'2'; 
			if (isset($pref_region_3)) $pref_region .= ($pref_region?',':'').'3'; 
			if (isset($pref_region_4)) $pref_region .= ($pref_region?',':'').'4'; 
			if (isset($pref_region_5)) $pref_region .= ($pref_region?',':'').'5'; 
			if (isset($pref_region_6)) $pref_region .= ($pref_region?',':'').'6'; 
			if (isset($pref_region_7)) $pref_region .= ($pref_region?',':'').'7'; 
			if (isset($pref_region_8)) $pref_region .= ($pref_region?',':'').'8'; 
			if (isset($pref_region_9)) $pref_region .= ($pref_region?',':'').'9'; 
			if (isset($pref_region_0)) $pref_region .= ($pref_region?',':'').'0'; 
			$doc->pref_region = $pref_region;
			
			$doc->salary_other = $salary_other; 
			
		if( $pref_states == 'AK,AL,AR,AZ,CA,CO,CT,DC,DE,FL,GA,HI,IA,ID,IL,IN,KS,KY,LA,MA,MD,ME,MI,MN,MO,MS,MT,NC,ND,NE,NH,NJ,NM,NV,NY,OH,OK,OR,PA,PR,RI,SC,SD,TN,TX,UT,VA,VT,WA,WI,WV,WY' ) {
			// trying to fool-proof
			$pref_stopen = 1;
			$pref_states = '';
		}
			$doc->pref_stopen = $pref_stopen?1:0;
		    if( !empty( $pref_states ) || $pref_stopen ) $doc->pref_states = $pref_states; 
			else $mesg .= "no preferred states; ";

			$pref_practice ='';
			if (isset($pref_practice_1)) $pref_practice .= $pref_practice_1.','; 
			if (isset($pref_practice_2)) $pref_practice .= $pref_practice_2.','; 
			if (isset($pref_practice_3)) $pref_practice .= $pref_practice_3.','; 
			if (isset($pref_practice_4)) $pref_practice .= $pref_practice_4.','; 
			if (isset($pref_practice_5)) $pref_practice .= $pref_practice_5.','; 
			if (isset($pref_practice_6)) $pref_practice .= $pref_practice_6.','; 
			if (isset($pref_practice_7)) $pref_practice .= $pref_practice_7.','; 
			if (isset($pref_practice_8)) $pref_practice .= $pref_practice_8.','; 
			if (isset($pref_practice_9)) $pref_practice .= $pref_practice_9.','; 
			
		    if( !empty($pref_practice) ) $doc->pref_practice = $pref_practice; // it's a varchar, not a set
			else $mesg .= "no preferred practice type; ";
                                     		 
		    $doc->hobbies = $hobbies;
		    $doc->other_pref = $other_pref;
			if( is_numeric($contact_pref) ) $doc->contact_pref = $contact_pref; // else ignore
			if( is_numeric($source) && $source >= 20 ) $doc->source = $source; // else ignore
			if( !$doc->source ) $mesg .= "no source selected; ";
		
			$doc->languages = $languages;
			$doc->newsletter = $newsletter?1:0;
			$doc->campaigns = $campaigns?1:0;
			$doc->notifications = $notifications?($notifications==1?1:2):0;
			$doc->reason_leaving = $reason_leaving;

			$doc->inactive = $inactive?1:0; 
			$doc->checkin = $checkin?1:0; 
			$doc->noemail = $noemail?1:0; 
			$doc->as_new = is_numeric($as_new)? $as_new:0;
			$doc->email_bounces = $email_bounces?1:0; 
			//$doc->interviewing = $nointer?0:1; 
			if( !$doc->status && !$nostatus && $doc->pending == 1 ) $doc->set_pending(); // set pending on reactivation
			
			$doc->status = $nostatus?0:1; 
			if( valid_email(trim($email)) ) $doc->email = trim($email);
			else { $mesg .= 'Email address is invalid, not updated.'; $pending=2; $doc->pending=2; }
			if( !empty($email) && valid_email(trim($email)) ) $doc->noemail = 0;
			$trigger = 0;
			if( $doc->pending == 2 && $pending == 1 && empty($mesg) ) { // IC
				$doc->iv_date = date('Y-m-d');
				$doc->pending = 1;
				$doc->set_pending();
				$action .= ' PEND';
			}
			if( $doc->pending != $pending ) { // verification or other actions
				if( $ACCESS >=50 && !$pending && $doc->pending == 1 && empty($mesg) ) { // verify
					$doc->unset_pending();
					$trigger=1;
					$doc->pending = 0;
					if( !$doc->iv_date ) $doc->iv_date = date('Y-m-d');
					$action .= ' VERIFY';
				}
				if( $pending == 2 ) { $doc->unset_pending(); $doc->pending = 2; $action .= ' UNPEND'; } // kick back to data entry
				if( $pending == 1 ) {
				    $doc->pending = 1;
				    $doc->set_pending();
				    $action .= ' REPEND';
				}
			}

			$doc->save_res($trigger);
			if( $mesg ) {
				$result = false;
				$mesg = "Please correct the following before proceeding: $mesg";
				$action .= ' WARN';
			} 
			elseif( $trigger || $resendemail ) {
	// email update *****************************
			$email0 = stripslashes($email); // stripslashes($email? $email: $email_2nd); 
			if( valid_email($email0) ) {
				$action .= ' EMAIL';
				// $newp. 
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
				$msgbodyex = <<<HereEmail

This is a multipart message in MIME format.

------=_NextPart_000_000C_01C7836B.F0749520
Content-Type: text/plain;
	charset="us-ascii"
Content-Transfer-Encoding: 7bit


Dear Dr. $lname:

Welcome to www.physiciancareer.com!  This website is designed to allow
you to review active practice opportunities from across the United States.

You may have recently updated a physician profile, or we talked to you about your
practice preferences. In an ongoing effort to ensure that your information is
accurate and up-to-date, we are providing you with access to your profile for review. 

Please click on the following link to log in to your profile to confirm or update
your information. Doing so ensures that you will be contacted regarding opportunities
that best meet your specified preferences.


$hashlink
User name: $email0

Please do not share this link with others, because it provides access to your profile.

Thank you for taking time to review your profile and we wish you the best in locating
a new practice opportunity that meets your needs.

Please contact us by replying to this message if you have questions or concerns.

Thanks again,
PhysicianCareer.com support
http://physiciancareer.com



------=_NextPart_000_000C_01C7836B.F0749520
Content-Type: text/html;
	charset="us-ascii"
Content-Transfer-Encoding: quoted-printable

<html>
<head>
<META HTTP-EQUIV=3D"Content-Type" CONTENT=3D"text/html; =
charset=3Dus-ascii">
</head>
<body lang=3DEN-US link=3D"#9D454F" vlink=3D"#814E95">
<p>Dear Dr. $lname:<br /></p>

<p>Welcome to <a href=3D"http://physiciancareer.com/">www.physiciancareer.com=
</a>! This website is designed to allow you to review active practice =
opportunities from across the United States.</p>

<p>You may have recently updated a physician profile, or we talked to you about your =
practice preferences. In an ongoing effort to ensure that your information is =
accurate and up-to-date, we are providing you with access to your profile for =
review.</p>

<p>Please click on the following link to log in to your profile to confirm or =
update your information. Doing so ensures that you will be contacted regarding =
opportunities that best meet your specified preferences.</p>

<p><a href=3D"$hashlinkQP"
target=3D"_blank">$hashlinkQP</a><br>
User name: $email0</p>

<p>Please do not share this link with others, because it provides access =
to your profile.</p>

<p>Thank you for taking time to review your profile and we wish you the best =
in locating a new practice opportunity that meets your needs.</p>

<p>Please contact us by replying to this message if you have questions or concerns.</p>

<p>Thanks again,<br />
PhysicianCareer.com support<br />
<a
href=3D"http://physiciancareer.com/">physiciancareer.com</a></p>

</body>
</html>

------=_NextPart_000_000C_01C7836B.F0749520--

HereEmail;
					// let's send
					$subj = 'Your physician profile on PhysicianCareer.com';
					if( !mail($rcpt,$subj,$msgbodyex,$hedr) ) {
						$action .= 'FAIL';
						$sql = "insert into gestapo (phid,opid,action) values ($doc->ph_id,$UUID,'$action')";
						$resges = $nodb->query($sql);
						throw new Exception('Profile Email to '.htmlspecialchars($rcpt).' failed. The record itself was saved.',__LINE__);
					}
			}
		}
		// email welcome
		elseif( isset($_POST['submit']) ) {
			$email0 = stripslashes($email); // stripslashes($email? $email: $email_2nd); 
			if( valid_email($email0) ) {
				$action .= ' PHGCV';
				// $newp. 
				$hedr = "From: \"Tom Broxterman\" <help@physiciancareer.com>\r\n";
				$hedr .= 'Date: '.date('r')."\r\nPrecedence: normal\r\n";
				$hedr .= "Organization: PhysicianCareer.com\r\nMIME-Version: 1.0\r\n".
					"Content-Type: multipart/alternative;\r\n  boundary=\"----=_NextPart_000_000D_01C7836B.F0749520\"\r\n".
					"Content-Language: en-us\r\n";
				$hedr .= "X-Originator: $UUID/$REMOTE_ADDR\r\nX-Mailer: FuzionHG Mail Processor ".
					phpversion();
				$fname = stripslashes($fname); // K'neal
				$lname = stripslashes($lname); // O'Hara
				$rcpt = "\"$fname $lname\" <$email0>";
				$msgbodyex = <<<HereEmail2

This is a multipart message in MIME format.

------=_NextPart_000_000D_01C7836B.F0749520
Content-Type: text/plain;
	charset="us-ascii"
Content-Transfer-Encoding: 7bit


Dear Dr. $lname:

Pinnacle Heath Group, a full-service physician recruiting firm recently 
acquired a copy of your CV. Our recruiters are always looking for 
physicians who might be interested in new opportunities. We also stay 
abreast of the latest trends in healthcare and utilize up-to-date tools 
and integrated approaches to physician recruiting and healthcare 
staffing. 

One of our most effective recruiting tools is our online community of 
physicians and employers, PhysicianCareer.com, a powerfully built job 
board and physician database. PhysicianCareer.com is sold to in-house 
recruiters (not sold to outside firms) who need sophisticated matching 
functionality to find the right physician candidate for their practice 
opportunity. 

By including your CV in PhysicianCareer.com we are able to make your CV 
available to employers that have subscribed to our site, in the hopes of 
finding the ideal candidates for their open practice opportunities. 

In the near future, you will receive an email confirming your 
PhysicianCareer.com registration. This email will contain a link that 
will allow you to view your profile and make any desired changes and/or 
updates. You will also have the ability to specify your preferences on 
where you are interested in working and how you would like to be 
contacted by potential employers. If you are no longer looking for 
employment opportunities, you will also have the option of making your 
account inactive. 

Visit PhysicianCareer.com today and start looking at the list of 
available practice opportunities. Once you are registered, you won't 
have to do the searching - employers will contact you when they have an 
opportunity that meets your specified preferences. 

If you have any questions or want additional information, please contact 
us at help@physiciancareer.com <mailto:help@physiciancareer.com> .

Sincerely,


Tom Broxterman
 
PhysicianCareer.com <http://physiciancareer.com>
Atlanta, GA
800-789-6684 - Main
404-591-4256 - Direct


------=_NextPart_000_000D_01C7836B.F0749520
Content-Type: text/html;
	charset="us-ascii"
Content-Transfer-Encoding: quoted-printable

<html>
<head>
<META HTTP-EQUIV=3D"Content-Type" CONTENT=3D"text/html; =
charset=3Dus-ascii">
</head>
<body lang=3DEN-US link=3D"#9D454F" vlink=3D"#814E95">
<p>Dear Dr. $lname:<br /></p>

<p>Pinnacle Heath Group, a full-service physician recruiting firm recently =
acquired a copy of your CV. Our recruiters are always looking for =
physicians who might be interested in new opportunities. We also stay =
abreast of the latest trends in healthcare and utilize up-to-date tools =
and integrated approaches to physician recruiting and healthcare =
staffing. </p>

<p>One of our most effective recruiting tools is our online community of =
physicians and employers, <a href=3D"http://physiciancareer.=
com/">PhysicianCareer.com</a>, a powerfully built job =
board and physician database. PhysicianCareer.com is sold to in-house =
recruiters (not sold to outside firms) who need sophisticated matching =
functionality to find the right physician candidate for their practice =
opportunity. </p>

<p>By including your CV in PhysicianCareer.com we are able to make your CV =
available to employers that have subscribed to our site, in the hopes of =
finding the ideal candidates for their open practice opportunities. </p>

<p>In the near future, you will receive an email confirming your =
PhysicianCareer.com registration. This email will contain a link that =
will allow you to view your profile and make any desired changes and/or =
updates. You will also have the ability to specify your preferences on =
where you are interested in working and how you would like to be =
contacted by potential employers. <b>If you are no longer looking for =
employment opportunities, you will also have the option of making your =
account inactive.</b> </p>

<p>Visit <a href=3D"http://physiciancareer.com/">PhysicianCareer.com</a> =
today and start looking at the list of =
available practice opportunities. Once you are registered, you won't =
have to do the searching - employers will contact you when they have an =
opportunity that meets your specified preferences. </p>

<p>If you have any questions or want additional information, please contact =
us at <a href=3D"mailto:help@physiciancareer.com">help@physiciancareer.com</a>.</p>

<p>Sincerely,</p>

<p>Tom Broxterman<br />
<br />
<a href=3D"http://physiciancareer.com/">PhysicianCareer.com</a><br />
Atlanta, GA<br />
800-789-6684 - Main<br />
404-591-4256 - Direct<br />
</p>

</body>
</html>

------=_NextPart_000_000D_01C7836B.F0749520--

HereEmail2;
					// let's send
					$subj = 'Regarding your CV';
					if( empty($note_text) ) $note_text = "Email request sent: $subj";
					if( !mail($rcpt,$subj,$msgbodyex,$hedr) ) {
						$action .= 'FAIL';
						$sql = "insert into gestapo (phid,opid,action) values ($doc->ph_id,$UUID,'$action')";
						$resges = $nodb->query($sql);
						throw new Exception('PHG CV Email to '.htmlspecialchars($rcpt).' failed. The record itself saved.',__LINE__);
					}
			}
		}
		elseif( isset($_POST['submitcv']) ) {
			$email0 = stripslashes($email); // stripslashes($email? $email: $email_2nd); 
			if( valid_email($email0) ) {
				$action .= ' PCCV';
				// $newp. 
				$hedr = "From: \"Tom Broxterman\" <help@physiciancareer.com>\r\n";
				$hedr .= 'Date: '.date('r')."\r\nPrecedence: normal\r\n";
				$hedr .= "Organization: PhysicianCareer.com\r\nMIME-Version: 1.0\r\n".
					"Content-Type: multipart/alternative;\r\n  boundary=\"----=_NextPart_000_000D_01C7836B.F0749520\"\r\n".
					"Content-Language: en-us\r\n";
				$hedr .= "X-Originator: $UUID/$REMOTE_ADDR\r\nX-Mailer: FuzionHG Mail Processor ".
					phpversion();
				$fname = stripslashes($fname); // K'neal
				$lname = stripslashes($lname); // O'Hara
				$rcpt = "\"$fname $lname\" <$email0>";
				$msgbodyex = <<<HereEmail3

This is a multipart message in MIME format.

------=_NextPart_000_000D_01C7836B.F0749520
Content-Type: text/plain;
	charset="us-ascii"
Content-Transfer-Encoding: 7bit


Dear Dr. $lname:

Pinnacle Heath Groupis a national physician recruitment firm that has helped place 
over a thousand physicians in new opportunities.  One of our most effective recruiting 
tools is our online community of physicians and employers, PhysicianCareer.com, 
an industry leading job board and physician database that has been successfully 
linking physicians and employers since 2007. We recently received information 
indicating that you might be looking for a new position, but in order to help with 
your search, we ask that you send us your CV and a description of your job preferences. 

By including your CV in PhysicianCareer.com we are able to make your CV available to 
employers that have subscribed to our site, in the hopes of finding the ideal 
candidates for their open practice opportunities.  You will be contacted directly - 
only by Pinnacle Health Group or by employers regarding potential opportunities.  
We are dedicated to following industry standards and your information is not sold 
to outside search firms.  

Visit PhysicianCareer.com today and start looking at the list of available practice 
opportunities. Once you are registered, you won't have to do the searching - employers 
will contact you when they have an opportunity that meets your specified preferences.

If you have any questions or want additional information, please contact us at 
help@physiciancareer.com <mailto:help@physiciancareer.com> .

Sincerely,


Tom Broxterman
 
PhysicianCareer.com <http://physiciancareer.com>
Atlanta, GA
800-789-6684 - Main
404-591-4256 - Direct


------=_NextPart_000_000D_01C7836B.F0749520
Content-Type: text/html;
	charset="us-ascii"
Content-Transfer-Encoding: quoted-printable

<html>
<head>
<META HTTP-EQUIV=3D"Content-Type" CONTENT=3D"text/html; =
charset=3Dus-ascii">
</head>
<body lang=3DEN-US link=3D"#9D454F" vlink=3D"#814E95">
<p>Dear Dr. $lname:<br /></p>

<p>Pinnacle Heath Groupis a national physician recruitment firm that has helped place =
over a thousand physicians in new opportunities.  One of our most effective recruiting =
tools is our online community of physicians and employers, PhysicianCareer.com, =
an industry leading job board and physician database that has been successfully =
linking physicians and employers since 2007. We recently received information =
indicating that you might be looking for a new position, but in order to help with =
your search, <strong>we ask that you send us your CV and a description of your job =
preferences.</strong></p>

<p>By including your CV in <a href=3D"http://physiciancareer.=
com/">PhysicianCareer.com</a>, we are able to make your CV available to =
employers that have subscribed to our site, in the hopes of finding the ideal =
candidates for their open practice opportunities.  You will be contacted directly - =
only by Pinnacle Health Group or by employers regarding potential opportunities.  =
We are dedicated to following industry standards and your information is not sold =
to outside search firms.</p>

<p>Visit <a href=3D"http://physiciancareer.com/">PhysicianCareer.com</a> =
today and start looking at the list of =
available practice opportunities. Once you are registered, you won't =
have to do the searching - employers will contact you when they have an =
opportunity that meets your specified preferences. </p>

<p>If you have any questions or want additional information, please contact us at =
<a href=3D"mailto:help@physiciancareer.com">help@physiciancareer.com</a>.</p>

<p>Sincerely,</p>

<p>Tom Broxterman<br />
<br />
<a href=3D"http://physiciancareer.com/">PhysicianCareer.com</a><br />
Atlanta, GA<br />
800-789-6684 - Main<br />
404-591-4256 - Direct<br />
</p>

</body>
</html>

------=_NextPart_000_000D_01C7836B.F0749520--

HereEmail3;
					// let's send
					$subj = 'Your CV and preferences';
					if( empty($note_text) ) $note_text = "Email request sent: $subj";
					if( !mail($rcpt,$subj,$msgbodyex,$hedr) ) {
						$action .= 'FAIL';
						$sql = "insert into gestapo (phid,opid,action) values ($doc->ph_id,$UUID,'$action')";
						$resges = $nodb->query($sql);
						throw new Exception('PC CV Email to '.htmlspecialchars($rcpt).' failed. The record itself was saved.',__LINE__);
					}
			}
		}
			$note_text = substr(trim($note_text),0,254);
			if( strlen($note_text) == 254 ) $note_text .= '-';
			if( !empty($note_text) ) $action .= ' NOTES';
			$sql = "insert into gestapo (phid,opid,action) values ($doc->ph_id,$UUID,'$action')";
			$resges = $nodb->query($sql);
			if( !empty($note_text) ) { // save note
				$sql = "insert into notes (uid,shared,note,res_id) values ($UUID,0,'$note_text',$docid)";
				$result = $nodb->query($sql);
				if( !$result ) throw new Exception(DEBUG?"$nodb->error : $sql":'Can not save notes. But the record was saved.',__LINE__);
			}
			$saved = true;
		}
		
		//DELETE CV
		if($_GET["delete"]=="cv"){
		
		$id=urldecode($_GET["id"]);
		if($id>0){
			$sql = "delete from cvs where cv_ph_id=$id LIMIT 1";
			//echo $sql;
			$resges = $nodb->query($sql);
			echo "<p style='color:red'>CV DELETED</p>";
		}
		}
	}
	catch(Exception $e) {
		$mesg = 'Request failed: '.$e->getMessage().' ('.$e->getCode().')<br>';
		unset($result);
	}
	$style = new OperPage('Physician Profile',$UUID,'residents','','');
	if( empty($formpage) ) $formpage = 1;


 
$scrpt =<<<TryMe
<script type="text/javascript" src="calendarDateInput.js"></script>
<script type="text/javascript" src="reg1.js"></script>
<script type="text/javascript" src="areas.js"></script>
<script language="JavaScript" type="text/JavaScript"><!--

var bropen = 0;

var subwind;

function showregions() {
	subwind = window.open("regions.php",
			"regions","menubar=0,toolbar=0,width=450,resizable=0,location=0,height=400,scrollbars=yes");
	setTimeout("subwind.focus()",60);
}

function checkemail() {
	// runs a check in a separate window.
	var email = document.getElementById("email").value;
	if( email != '' ) {
		subwind = window.open("checkemail.php?id=$doc->uid&y=2005&e="+encodeURI(email),
			"emailcheck","menubar=0,toolbar=0,width=350,resizable=0,location=0,height=300");
		setTimeout("subwind.focus()",60);
	}
	//else alert("Email is blank, which is OK");
}

function chsahe() {
	document.getElementById("savedhead").style.visibility = "hidden";
	return true;
}

setTimeout("chsahe()",2000);

//var NS4 = (navigator.appName == "Netscape" && parseInt(navigator.appVersion) < 5);


function checkPhoEx2(phoinp,pholabel,stsel,stlabel) {
	var pho = document.getElementById(phoinp);
	var sta = document.getElementById(stsel);
	return checkPhoEx(pho,pholabel,sta,stlabel);
}

function selCB() {                                                          
        document.getElementById("cbold").checked = true;                       
        return true;                                                            
}                                                                               

function submitform(theForm) {
     var i;
     var PSta=theForm.pref_states;
	 PSta.value = "";
     SelList=theForm.stateslist;
     for(i=0; i < SelList.options.length; i++) {
	 	  if( i == 0 ) {
		  	PSta.value = SelList.options[i].value;
		  }
		  else {
		    PSta.value += "," + SelList.options[i].value;
		  }
	 }
     var Lic=theForm.licensed;
	 Lic.value = "";
     var SelList=theForm.licenselist;
     for(i=0; i < SelList.options.length; i++) {
	 	  if( i == 0 ) {
		  	Lic.value = SelList.options[i].value;
		  }
		  else {
		    Lic.value += "," + SelList.options[i].value;
		  }
	 }
	 return true;
}

function expand(area){
	var listElementStyle=document.getElementById(area).style;
	if (listElementStyle.display=="none"){
		listElementStyle.display="block";
		bropen = 1;
	}else {
		listElementStyle.display="none";
		bropen = 0;
	}
}

function showhide(list,space){
	var listElementStyle=document.getElementById(list).style;
	if (listElementStyle.display=="none"){
		listElementStyle.display="block";
	}else {
		listElementStyle.display="none";
	}
	expand(space);
}

function trigger2() {
	var r2ger = document.getElementById("res2trigger");
	var r2tab = document.getElementById("res2table");
	r2ger.style.display = "none";
	r2tab.style.display = "block";
	return true;
}

function addOption(theSel, theText, theValue)
{
	var newOpt = new Option(theText, theValue);
	var selLength = theSel.length;
	theSel.options[selLength] = newOpt;
}

function deleteOption(theSel, theIndex)
{	
	var selLength = theSel.length;
	if(selLength>0)
	{
		theSel.options[theIndex] = null;
	}
}

function moveOptions(theSelFrom, theSelTo, checkReg)
{
//	var theSelFrom = document.getElementById(SelFrom);
//	var theSelTo   = document.getElementById(SelTo);
	
	var selLength = theSelFrom.length;
	var selectedText = new Array();
	var selectedValues = new Array();
	var selectedCount = 0;
	
	
	var i;
	
	// Find the selected Options in reverse order
	// and delete them from the 'from' Select.
	for(i=selLength-1; i>=0; i--)
	{
		if(theSelFrom.options[i].selected)
		{
			selectedText[selectedCount] = theSelFrom.options[i].text;
			selectedValues[selectedCount] = theSelFrom.options[i].value;
			deleteOption(theSelFrom, i);
			selectedCount++;
		}
	}
	
	// Add the selected text/values in reverse orser.
	// This will add the Options to the 'to' Select
	// in the same orser as they were in the 'from' Select.
	for(i=selectedCount-1; i>=0; i--)
	{
		addOption(theSelTo, selectedText[i], selectedValues[i]);
	}
	
	return true;
}

function GetYY(ob) {
	var YY;
	if( ob.tagName == "BODY" ) {
	   YY = ob.offsetTop;
	} else {
	   YY = ob.offsetTop + GetYY(ob.offsetParent);
	}
	return YY;
}

function hidepop(oid) {
	var div = document.getElementById(oid);
	div.style.visibility = "hidden";
}

function doshpop(obj) {
	var div = document.getElementById("shpop");
	var y = GetYY(obj);
	div.style.top = y-10 + "px";
	var lnk = document.getElementById("linkk");
	lnk.value = "$hashlink";
	div.style.visibility = "visible";
	return true;
}
// -->
</script>
<style type="text/css">
<!--
.style1 {color: #333333}
.style2 {font-size: 10px}
.popss {
		position:absolute; top:180px; left:570px; z-index:2; visibility: hidden;
		padding:0.5em; background: silver; border-style: ridge;
		border-width: 3px
}
-->
</style>


TryMe;
	$style->Output($scrpt);
	
	
	
	if( $UUID ) {
?>








              <h1><?php if( !$doc->pending ) echo 'Verified '; ?>Physician Profile <span id="savedhead" style="visibility:<?php echo $saved?'visible':'hidden'; ?>">(Saved)</span></h1>
              <?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
	if( $doc ) {
		if( $doc->inactive ) echo "<h2 id='warning_msg'>Inactive record. Subject to Clean-n-Sweep procedure.</h2>";
?>
	<div id="shpop" class="popss">
		Copy &amp; Paste Link: <input type="text" maxlength="100" value="" readonly="true" name="link" id="linkk" />
		<span style="font-style:italic; text-decoration:underline; color: blue;" onClick="hidepop('shpop')">close</span>
	</div>
              <div id="formdiv">
			  <form name="form1" method="post" action="showdocpc.php" onSubmit="return submitform(this);">
                <p>ID#: <?php echo "$doc->uid (PCAREER)"; if( $lid < 128 || $lid == 250 ) echo " <a href='results.php?id=$lid&ck=$very$peekarg&y=2005'>back to the list</a>";?>.
                  <input name="id" type="hidden" id="id" value="<?php echo $doc->uid; ?>">
                  <input name="lid" type="hidden" id="lid" value="<?php echo $lid; ?>">
                  <input name="pos" type="hidden" id="pos" value="<?php echo $pos; ?>">
                  <input name="peek" type="hidden" id="peek" value="<?php echo $peek; ?>">
                  <input name="ck" type="hidden" id="ck" value="<?php echo $very; ?>">
                  <input name="formpage" type="hidden" id="formpage" value="<?php echo $formpage; ?>">
                (Last saved on <?php echo $doc->last_save; ?>) <?php
	 $hashlink = 'http://physiciancareer.com/showdoctor/?pageid='.$docid.'C'.$UUID.'A'.
	 	substr(md5($docid.$doc->fname.'2005'.$doc->lname.'Go, go, baby doll'),0,15); 
	 echo "<a href=\"$hashlink\" target=\"_blank\">Profile Preview</a>";
	 /*if ( $doc->year && $doc->res_id ) echo " Resident record: <a href='showdoc.php?id=$doc->res_id&amp;y=$doc->year&amp;peek=$peek&amp;ck=$very&amp;lid=0'>Backlink</a>";*/
?></p>
			  <h3>Section 1 - Account</h3>
              <table >
             <tr> 
                 <td width="15%" ><strong>First Name</strong>*:</td>
                 <td ><input name="fname" type="text" id="fname" value="<?php echo $doc->fname; ?>" maxlength="50"></td>
                 <td><label>
                          <input name="inactive" type="checkbox" title="check to suspend" id="inactive" value="1" 
					     <?php echo $doc->inactive? 'checked':''; ?> >
                          Suspended</label></td>
             </tr>
               <tr>
               <td>Middle Name:</td>
               <td><input name="midname" type="text" id="midname" value="<?php echo $doc->midname; ?>" maxlength="30"></td>
                        <td>
						<em><?php echo $doc->pending==1? 'Verification Pending':''; ?></em></td>
               </tr>
               <tr>
               <td ><strong>Last name</strong>*:</td>
               <td ><input name="lname" type="text" id="lname" value="<?php echo $doc->lname; ?>" maxlength="50"></td>
               <td ><label><input name="checkin" type="radio" title="check to make public" id="checkin" value="1" 
					     <?php echo $doc->checkin? 'checked':''; ?> >
                          Public</label> &nbsp; <label><input name="checkin" type="radio" title="check to make private" id="checkin0" value="0" 
					     <?php echo $doc->checkin? '':'checked'; ?> >
                          Private</label></td>
               </tr>
              <tr> 
                 <td>Degree:</td>
                 <td><input name="mddo" type="text" id="mddo" value="<?php echo $doc->mddo; ?>" size="10" maxlength="4">
                          MD/DO</td>
                 <td><label><input name="nostatus" type="checkbox" title="check to de-activate" id="nostatus" value="1" 
					     <?php echo $doc->status? '':'checked'; ?> >
                          Inactive (Disabled) </label></td>
              </tr>
              <tr>
                 <td>Title:</td>
                 <td><input name="title" type="text" id="title" value="<?php echo $doc->title; ?>" maxlength="50" title="Additional titles: PhD, MBA, MPH, etc..."></td>
                 <td>Notify <label><input name="notifications" <?php echo $doc->notifications==1? 'checked':''; ?> type="radio" value="1">Twice</label>
				 	<label><input name="notifications" <?php echo $doc->notifications==2? 'checked':''; ?> type="radio" value="2">Weekly</label>
					<label><input name="notifications" <?php echo $doc->notifications? '':'checked'; ?> type="radio" value="0">None</label></td>
              </tr>
               <tr> 
                  <td bgcolor="#E0E0FF">Home Address: </td>
                  <td bgcolor="#E0E0FF"><input name="addr1" type="text" id="addr1" value="<?php echo $doc->addr1; ?>" size="44" maxlength="50"> 
                  </td>
                  <td bgcolor="#E0E0FF">&nbsp;</td>
               </tr>
                  <tr>
                  <td bgcolor="#E0E0FF" >Line 2:</td>
                    <td bgcolor="#E0E0FF" >       <input name="addr2" type="text" id="addr2" value="<?php echo $doc->addr2; ?>" size="44" maxlength="50"></td>
                      <td bgcolor="#E0E0FF" >&nbsp;</td>
                  </tr>
                      <tr> 
                        <td bgcolor="#E0E0FF" >City:</td>
                        <td bgcolor="#E0E0FF"><input name="city" type="text" id="city" value="<?php echo $doc->city; ?>" maxlength="30"></td>
                        <td bgcolor="#E0E0FF">&nbsp;</td>
                      </tr>
                        <tr>
                        <td bgcolor="#E0E0FF"><strong>State</strong>*:</td>
                        <td bgcolor="#E0E0FF"><?php echo showStateList($db,$doc->state,'state'); ?></td>
                        <td bgcolor="#E0E0FF">&nbsp;</td>
                        </tr>
                        <tr>
                        <td bgcolor="#E0E0FF"><strong>Zip</strong>*:</td>
                        <td bgcolor="#E0E0FF"><input name="zip" type="text" id="zip" value="<?php echo $doc->zip; ?>" size ="10"maxlength="10"></td>
                        <td bgcolor="#E0E0FF">&nbsp;</td>
               </tr>
               <tr> 
                       <td>Office Address:</td>
                        <td ><input name="ofaddr1" type="text" id="ofaddr1" value="<?php echo $doc->ofaddr1; ?>" size="44" maxlength="50"> </td>
                        <td >&nbsp;</td>
               </tr>
                        <tr>
                         <td >     Line 2: </td>
                    <td >  
                          <input name="ofaddr2" type="text" id="ofaddr2" value="<?php echo $doc->ofaddr2; ?>" size="44" maxlength="50"></td>
                      <td >&nbsp;</td>
                      </tr>
                      <tr> 
                        <td >Of. City:</td>
                        <td><input name="ofcity" type="text" id="ofcity" value="<?php echo $doc->ofcity; ?>" maxlength="30"></td>
                        <td>&nbsp;</td>
                      </tr>
                        <tr>
                        <td >Of. State:</td>
                        <td ><?php echo showStateList($db,$doc->ofstate,'ofstate'); ?></td>
                        <td >&nbsp;</td>
                        </tr>
                        <tr>
                        <td >Of. Zip:</td>
                        <td ><input name="ofzip" type="text" id="ofzip" value="<?php echo $doc->ofzip; ?>" size="10"maxlength="10"></td>
                        <td >&nbsp;</td>
                      </tr>
                  
                  <tr> 
                        <td bgcolor="#E0E0FF">Home Phone:</td>
                        <td bgcolor="#E0E0FF"><input name="homephone" type="text" id="homephone" value="<?php echo $doc->homephone; ?>" size="12" maxlength="16" onChange="checkPhoEx2('homephone','Home Phone','state','Home State')">
                        <span  style="cursor:hand" title="Check Area code/State match" onClick="checkPhoEx2('homephone','Home Phone','state','Home State')"> <img src="images/bquestion.png" alt="?" width="16" height="16" border="0" align="absmiddle" title="Check Area code/State" ></span></td>
                        <td bgcolor="#E0E0FF">&nbsp;</td>
                  </tr>
                        <tr>
                        <td  bgcolor="#E0E0FF">Office:</td>
                         <td bgcolor="#E0E0FF"><input name="officephone" type="text" id="officephone" value="<?php echo $doc->officephone; ?>" size="12" maxlength="16" onChange="checkPhoEx2('officephone','Office Phone','ofstate','Office State')">
                         
                          Ext: 
                          <input name="officeext" type="text" id="officeext" value="<?php echo $doc->officeext; ?>" size="4" maxlength="10"><span  style="cursor:hand" title="Check Area code/Office State match" onClick="checkPhoEx2('officephone','Office Phone','ofstate','Office State')"> <img src="images/bquestion.png" alt="?" width="16" height="16" border="0" align="absmiddle" title="Check Area code/Office State" ></span></td>
                          <td bgcolor="#E0E0FF">&nbsp;</td>
                        </tr>
                          <tr>
                        <td bgcolor="#E0E0FF">Cell:</td>
                        <td bgcolor="#E0E0FF" colspan="2"><input name="cellphone" type="text" id="cellphone" value="<?php echo $doc->cellphone; ?>" size="12" maxlength="16"> <?php 
							if( !$doc->homephone && !$doc->officephone && !$doc->cellphone )
								echo '<span style="color: red; font-style:italic"> Please specify at least one phone number</span>';
						?></td>                       
                        </tr>
             <tr>     
                 <td><strong>Email</strong>*: </td>
                 <td><input name="email" type="text" id="email" value="<?php echo $doc->email; ?>" size="50" onChange="checkemail(1)"> <span id="chkemail" onClick="checkemail(1)" style="cursor:hand" title="Check email for validity">&nbsp;<img src="images/bquestion.png" alt="?" width="16" height="16" border="0" align="absmiddle" title="Check email for validity"></span></td> 
                        <td><label><input name="campaigns" type="checkbox" id="campaigns" value="1" <?php echo $doc->campaigns?'checked':''; ?>> 
	     Subscribed</label> <label><input name="email_bounces" type="checkbox" id="email_bounces" value="1" <?php echo $doc->email_bounces?'checked':''; ?>> 
	     Bounces</label></td>
            </tr> 
            <tr>
                 <td> New Password: </td>     
                 <td> <input name="passwd1" type="password" id="passwd1" value="<?php echo stripslashes($passwd1); ?>" maxlength="40" size="20"> 
                 (leave blank if no change) </td>
                        <td rowspan="2" ><input type="button" name="prooflink" value="Copy-paste Link" onClick="doshpop(this);" title=" click, copy and paste,
 send this link by email
 to this doctor" />                          <strong><em>or</em></strong><br> 
                          <input name="resendemail" type="checkbox" value="1"> 
                                       Send profile email when you  Save </td>
           </tr>
                 <tr>
                 <td>Confirm New Password: </td>     
                 <td> <input name="passwd2" type="password" id="passwd2" value="<?php echo stripslashes($passwd2); ?>"  maxlength="40" size="20"> </td>
                </tr>
                 <tr>
                   <td>New Secret Question:</td>
                   <td colspan="2">
				   <select name="secret_q" size="1">
				   		<option></option>
				   		<optgroup label="Favorites">
				   		<option <?php if( $secret_q == "What is your favorite book?" ) echo 'selected'; ?>>What is your favorite book?</option>
				   		<option <?php if( $secret_q == "What is your favorite food?" ) echo 'selected'; ?>>What is your favorite food?</option>
				   		<option <?php if( $secret_q == "What is your favorite city?" ) echo 'selected'; ?>>What is your favorite city?</option>
						</optgroup><optgroup label="History">
				   		<option <?php if( $secret_q == "What was the name of your first dog (cat)?" ) echo 'selected'; ?>>What was the name of your first dog (cat)?</option>
				   		<option <?php if( $secret_q == "What was the make and model of your first car?" ) echo 'selected'; ?>>What was the make and model of your first car?</option>
				   		<option <?php if( $secret_q == "On which street you grew up?" ) echo 'selected'; ?>>On which street you grew up?</option>
						</optgroup><optgroup label="Family">
				   		<option <?php if( stripslashes($secret_q) == "What is your grandmother's middle name?" ) echo 'selected'; ?>>What is your grandmother's middle name?</option>
				   		<option <?php if( $secret_q == "Where your grandfather lived when you were young?" ) echo 'selected'; ?>>Where your grandfather lived when you were young?</option>
				   		<option <?php if( stripslashes($secret_q) == "What is your pet's name?" ) echo 'selected'; ?>>What is your pet's name?</option>
				   </optgroup>
				   </select>				    <br>
				   (leave blank if no change) 
				   </td>
                 </tr>
                 <tr>
                   <td>Answer to Secret Question:</td>
                   <td><input name="secret_a" type="text" id="secret_a" value="<?php echo stripslashes($secret_a); ?>" size="50" maxlength="50" ></td>
                        <td >&nbsp;</td>
                 </tr>
                      <tr valign="bottom"> 
                        <td> 
                          <?php if( $pos && $preved ) echo 
				"<a href='showdocpc.php?id=$preved&lid=$lid&ck=$very$peekarg&y=$2005&pos=".($pos-1)."'>Prev</a>";
							  else echo '&nbsp;'; ?>
                        </td>
                        <td align="center"><input name="savest" type="submit" id="submit3" value="Save" width="74" style="width:74px ">
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<input name="reset" type="reset"  width="74" style="width:74px " value="Reset"></td>
                        <td align="right"> 
                          <?php if( $nextid ) echo 
				"<a href='showdocpc.php?id=$nextid&lid=$lid&ck=$very$peekarg&y=2005&pos=".($pos+1)."'>Next</a>";
							  else echo '&nbsp;'; ?>
                        </td>
                      </tr>
                 
            </table>
			  <h3>Curriculum Vitae</h3>
			  <p>
<?php 
		$cv = $doc->getcv(0);
		if( $cv ) {
			echo "Curriculum Vitae is available on file. ";
			//echo " (<a href='?id=$docid&delete=cv'>Click Here to Delete CV</a>)";
		}
		
?>			  If you have their updated CV, <a href='cvfile.php<?php echo 
				"?id=$docid&lid=$lid&ck=$very$peekarg&y=2005&pos=$pos"; ?>'>click here to upload it</a>. <em><br>
Save the  changes before clickin'!</em></p>
			  
			  <h3>Section 2 - Profile</h3>
				<input type="hidden" name="licensed" id="licensed" value="<?php echo $doc->licensed; ?>">
                <table cellspacing="0">
                  <tr>
                    <td >Status:</td>
                    <td><select name="pstatus" id="pstatus">
                        <option value="1" <?php echo $doc->pstatus==1?'selected':''; ?>>Resident</option>
                        <option value="2" <?php echo $doc->pstatus==2?'selected':''; ?>>Fellow</option>
                        <option value="3" <?php echo $doc->pstatus==3?'selected':''; ?>>In Practice</option>
                        <!--<option value="4" <?php echo $doc->pstatus==4?'selected':''; ?>>Future Felllow</option>-->
                        <option value="0" <?php echo $doc->pstatus?'':'selected'; ?>>Unspecified</option>
                    </select></td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr>
                    <td><strong>Main Specialty</strong>*:</td>
                    <td colspan="2"><?php echo showSpecList($db,$doc->spec); ?></td>
                  </tr>
                  <tr>
                    <td>Specialty interest:</td>
                    <td><input name="spec_2nd" type="text" id="spec_2nd" value="<?php echo $doc->spec_2nd; ?>" size="35" maxlength="50" /></td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr>
                    <td title="Board Certified/Board Eligible">BC/BE:</td><td>
                      <label title="Board Certified">
                        <input name="bcbe" type="radio" value="BC" <?php echo $doc->bcbe=='BC'?'checked':''; ?> />
                        BC</label>
                        <label title="Board Eligible">
                        <input name="bcbe" type="radio" value="BE" <?php echo $doc->bcbe=='BE'?'checked':''; ?> />
                          BE</label>
                        <label>
                        <input name="bcbe" type="radio" value="" <?php echo $doc->bcbe!='BC'&&$doc->bcbe!='BE'?'checked':''; ?> />
                          None</label>;
					&nbsp;BC Year: 
					<input name="bcbe_year" type="text" id="bcbe_year" value="<?php echo $doc->bcbe_year; ?>" size="10" maxlength="4" title="when graduated" /></td>
                    <td>&nbsp;</td>
                  </tr>
                    <tr>
                    <td bgcolor="#E0E0FF">Medical School:</td>
                    <td bgcolor="#E0E0FF">
                      <input name="school" type="text" id="school" value="<?php echo $doc->school; ?>" size="50" maxlength="100" /></td>
                    <td bgcolor="#E0E0FF">&nbsp;</td>
                  </tr>
                  <tr>
                    <td bgcolor="#E0E0FF">School Location (City and Country):</td>
                    <td bgcolor="#E0E0FF"><input name="sch_loc" type="text" id="sch_loc" value="<?php echo $doc->sch_loc; ?>" maxlength="50" />
                        &nbsp;
                        <input name="amg" type="checkbox" id="amg" value="1" <?php echo $doc->amg?'checked':''; ?> />
                       in the USA</td>
                    <td bgcolor="#E0E0FF">&nbsp;</td>
                  </tr><tr>
                    <td bgcolor="#E0E0FF">School State:</td>
                    <td bgcolor="#E0E0FF"><?php echo showStateList($db,$doc->sch_state,'sch_state'); ?></td>
                    <td bgcolor="#E0E0FF">&nbsp;</td>
                  </tr>
                    <tr>
                    <td bgcolor="#E0E0FF">School Year:</td>
                    <td bgcolor="#E0E0FF"><input name="sch_year" type="text" id="sch_year" value="<?php echo $doc->sch_year; ?>" size="10" maxlength="4" title="when graduated" /></td>
                    <td bgcolor="#E0E0FF">&nbsp;</td>
                  </tr>
                  <tr>
                    <td ><strong>Residency Program Name</strong>*:</td>
                    <td ><input name="program" type="text" id="program" value="<?php echo $doc->program; ?>" size="50" maxlength="100" /></td>
                    
                    <td >&nbsp;</td>
                  </tr>
                  <tr>
                    <td >Residency City:</td>
                    <td ><input name="res_city" type="text" id="res_city" value="<?php echo $doc->res_city; ?>" size="15" maxlength="30" /></td>
                    <td >&nbsp;</td>
                  </tr>
                  <tr>
                    <td >Residency State:</td>
                    <td><?php echo showStateList($db,$doc->res_state,'res_state'); ?></td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr>
                    <td><strong>Residency Specialty</strong>*:</td>
                    <td colspan="2"><?php echo showSpecList($db,$doc->res_spec,'res_spec'); ?></td>
                  </tr>
                    <tr>
                    <td><strong>Residency Year</strong>*:</td>
                    <td><input name="res_year" type="text" id="res_year" value="<?php echo $doc->res_year; ?>" size="10" maxlength="4" title="resident year" /></td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr>
                    <td bgcolor="#E0E0FF">Fellowship:</td>
                    <td bgcolor="#E0E0FF"><input name="fellowship" type="text" id="fellowship" value="<?php echo $doc->fellowship; ?>" maxlength="100" /></td>
                    <td bgcolor="#E0E0FF">&nbsp;</td>
                  </tr>
                  <tr>
                    <td bgcolor="#E0E0FF">Fellowship City: </td>
                    <td bgcolor="#E0E0FF"><input name="fel_city" type="text" id="fel_city" value="<?php echo $doc->fel_city; ?>" size="15" maxlength="30" /></td>
                    <td bgcolor="#E0E0FF">&nbsp;</td>
                  </tr>
				  <tr>
                    <td bgcolor="#E0E0FF">Fellowship State:</td>
                    <td bgcolor="#E0E0FF"><?php echo showStateList($db,$doc->fel_state,'fel_state'); ?></td>
                    <td bgcolor="#E0E0FF">&nbsp;</td>
                  </tr>
                  
                  <tr>
                    <td bgcolor="#E0E0FF">Fellowship Specialty: </td>
                    <td bgcolor="#E0E0FF" colspan="2"><?php echo showSpecList($db,$doc->fel_spec,'fel_spec'); ?></td>
                  </tr>
                  <tr>
                    <td bgcolor="#E0E0FF">Fellowship Year:</td>
                    <td bgcolor="#E0E0FF"><input name="fel_year" type="text" id="fel_year" value="<?php echo $doc->fel_year; ?>" size="10" maxlength="4" title="will graduate" /></td>
                    <td bgcolor="#E0E0FF">&nbsp;</td>
                  </tr>
                  <tr>
                    <td colspan="3"><a id="res2trigger" onClick="trigger2();" href="#">More than one residency or fellowship? click here</a>
					<div id="res2table" style="display:none">
					<table style="width:100%; border-style:none" cellspacing="0">
					  <tr>
						<td>2nd Program:</td>
						<td><input name="program_2" type="text" id="program_2" value="<?php echo $doc->program_2; ?>" size="10" maxlength="100" /></td>
					</tr>
					  <tr>
						<td >2nd Residency City:</td>
						<td ><input name="res2_city" type="text" id="res_city" value="<?php echo $doc->res2_city; ?>" size="15" maxlength="30" /></td>
					  </tr>
					  <tr>
						<td >2nd Residency State:</td>
						<td><?php echo showStateList($db,$doc->res2_state,'res2_state'); ?></td>
					  </tr>
					  <tr>
						<td>2nd Residency Specialty:</td>
						<td><?php echo showSpecList($db,$doc->res2_spec,'res2_spec'); ?></td>
					  </tr>
					  <tr>
						<td>2nd Residency Year:</td>
						<td><input name="res2_year" type="text" id="res2_year" value="<?php echo $doc->res2_year; ?>" size="10" maxlength="4" title="2nd Resident Year" /></td>
					  </tr>
					  <tr>
						<td bgcolor="#E0E0FF">2nd Fellowship:</td>
						<td bgcolor="#E0E0FF"><input name="fellow_2" type="text" id="fellow_2" value="<?php echo $doc->fellow_2; ?>" maxlength="100" /></td>
					  </tr>
					  <tr>
						<td bgcolor="#E0E0FF">2nd Fellowship City:</td>
						<td bgcolor="#E0E0FF"><input name="fel2_city" type="text" id="fel2_city" value="<?php echo $doc->fel2_city; ?>" size="15" maxlength="30" /></td>
					  </tr>
					  <tr>
						<td bgcolor="#E0E0FF">2nd Fellowship State:</td>
						<td bgcolor="#E0E0FF"><?php echo showStateList($db,$doc->fel2_state,'fel2_state'); ?></td>
					  </tr>
					  <tr>
						<td bgcolor="#E0E0FF">2nd Fellowship Specialty: </td>
						<td bgcolor="#E0E0FF"><?php echo showSpecList($db,$doc->fel2_spec,'fel2_spec'); ?></td>
					  </tr>
					  <tr>
						<td bgcolor="#E0E0FF">2nd Fellowship Year:</td>
						<td bgcolor="#E0E0FF"><input name="fel2_year" type="text" id="fel2_year" value="<?php echo $doc->fel2_year; ?>" size="10" maxlength="4" title="2nd Fellowship Year" /></td>
					  </tr>
					</table></div>
					</td>
                  </tr>
                  <tr>
                    <td><strong>Availability Date</strong>*:</td>
                    <td><script language="javascript">DateInput('avail_date', false, 'YYYY-MM-DD', '<?php echo $doc->avail_date?$doc->avail_date:'2000-01-01'; ?>');</script></td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr>
                    <td >Immigration Status: </td>
                    <td>
                        <label>
                        <input name="citizen" type="radio" value="0" <?php echo $doc->citizen?'': 'checked'; ?> />
                          Unknown</label> 
					<label>
                      <input name="citizen" type="radio" value="1" <?php echo $doc->citizen==1?'checked':''; ?> />
                      US Citizen</label>
                        <label title="Permanent Resident = Green Card">
                        <input name="citizen" type="radio" value="2" <?php echo $doc->citizen==2?'checked':''; ?> />
                          Permanent Resident</label>
                        <br />
                        <label>
                        <input name="citizen" type="radio" value="4" <?php echo $doc->citizen==4?'checked':''; ?> />
                          Visa</label> 
                        (please specify visa type below)</td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr>
                    <td title="If born in the USA">Home (Birth) State:</td>
                    <td title="If born in the USA"><?php echo showStateList($db,$doc->birth_state,'birth_state'); ?></td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr>
                    <td>Visa Status:</td>
                    <td><input name="visa_status" type="text" id="visa_status" value="<?php echo $doc->visa_status; ?>" size="10" maxlength="3" /> 
                      e.g. J1, H1, F1</td>
                  
                    <td>&nbsp;</td>
                  </tr>
                  <tr>
                    <td bgcolor="#E0E0FF" valign="top">Licensed in:</td>
                    <td bgcolor="#E0E0FF" colspan="2">
						<table style="border-style:none; width:100%">
							<tr>

							<td width="42%" valign="middle" style="text-align: right"><select name="licenz" size="7" id="licenz" multiple>
								<?php
									foreach( $StateList as $stat => $stname ) {
										if( $stat == '--' || !(strpos($doc->licensed,$stat) === false) ) continue;
										echo "<option value='$stat'>$stname ($stat)</option>";
									}
								?>
							  </select>
							</td>
							<td width="16%" valign="middle" style="text-align:center"><input name="button" type="button"
						onClick="moveOptions(licenz, licenselist)" value="&gt;&gt;" />
								<br />
								<br />
								<input name="button2" type="button"
						onClick="moveOptions(licenselist, licenz)" value="&lt;&lt;" />
							</td>
							<td width="42%" valign="middle"><select name="licenselist" id="licenselist" size="7" multiple>
								<?php
										if( $doc->licensed ) {
											$lic = explode(',',$doc->licensed);
											foreach( $lic as $st ) {
												$lic_st = $StateList[trim($st)];
												echo "<option value='$st'>$lic_st ($st)</option>";
											}
										}
								?>
							  </select>
							</td>

							</tr>
						</table>
						<p style="margin-top:0 ">Pick states in the left list, then press [&gt;&gt;] button above to select Licensed states</p>
					</td>
                  </tr>
                      <tr valign="bottom"> 
                        <td> 
                          <?php if( $pos && $preved ) echo 
				"<a href='showdocpc.php?id=$preved&lid=$lid&ck=$very$peekarg&y=$2005&pos=".($pos-1)."'>Prev</a>";
							  else echo '&nbsp;'; ?>
                        </td>
                        <td align="center"><input name="savest" type="submit" id="submit4" value="Save" width="74" style="width:74px ">
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<input name="reset" type="reset"  width="74" style="width:74px " value="Reset"></td>
                        <td align="right"> 
                          <?php if( $nextid ) echo 
				"<a href='showdocpc.php?id=$nextid&lid=$lid&ck=$very$peekarg&y=2005&pos=".($pos+1)."'>Next</a>";
							  else echo '&nbsp;'; ?>
                        </td>
                      </tr>
              </table>
			  <h3>Section 3 - Preferences</h3>
				<input type="hidden" name="pref_states" id="pref_states" value="<?php echo $doc->pref_states; ?>">
   <table  border="0" cellspacing="0">
     <tr>
       <td valign="top">Compensation Preferences: (<em>public</em>) </td>
       <td ><input name="salary_other" type="text" id="salary_other" title="Compensation or Salary Preferences:" value="<?php echo $doc->salary_other; ?>" size="40" maxlength="120"> 
         (<em>this field can be visible to clients</em>) </td>
       <td valign="top">&nbsp;</td>
     </tr>
  <tr>
    <td valign="top" bgcolor="#E0E0FF"><strong>Preferred States</strong>*:</td>
    <td colspan="2" bgcolor="#E0E0FF"><table style="width:100%" border="0">
        <tr>
          <td width="42%" valign="middle" style="text-align: right"><select name="statez" size="7" id="statez" multiple>
              <?php
			  		$spousestate = showStateList($db,$doc->spouse_state,'spouse_state'); // to create $StateList array
					  		foreach( $StateList as $stat => $stname ) {
								if( $stat == '--' || !(strpos($doc->pref_states,$stat) === false) ) continue;
								echo "<option value='$stat'>$stname ($stat)</option>";
							}
						?>
            </select>
          </td>
          <td width="16%" valign="middle" style="text-align:center"><label>
            <input name="pref_stopen" type="checkbox" id="pref_stopen" value="1" <?php echo $doc->pref_stopen?'checked':''; ?>>
            OPEN</label>
              <br>
              <br>
              <input name="button3" type="button" id="button3" title="Add"
				onClick="moveOptions(statez, stateslist, true)" value=">>" />
              <br />
              <br />
              <input name="button4" type="button" id="button4" title="Remove"
				onClick="moveOptions(stateslist, statez, false)" value="<<" />
          </td>
          <td  width="42%" valign="middle"><select name="stateslist" id="stateslist" size="7" multiple>
              <?php
						  		if( $doc->pref_states ) {
									$sta = explode(',',$doc->pref_states);
									foreach( $sta as $st ) {
										$sta_st = $StateList[trim($st)];
										echo "<option value='$st'>$sta_st ($st)</option>";
									}
								}
						?>
            </select>
          </td>
        </tr>
      </table>
        <p style="margin-top:0; margin-bottom:0 ">Pick states in the left list, then press [&gt;&gt;]  button above to select Preferred states, or click OPEN if undecided </p></td>
  </tr>
  <tr>
    <td valign="top">Preferred Regions:<br>
        <span id="showregs" onClick="showregions()" style="cursor:hand" title="Show Regions and States">&nbsp;<img src="images/bquestion.png" alt="?" width="16" height="16" border="0" align="absmiddle" title="Show Regions"></span></td>
    <td style="border: thin solid" ><table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td bgcolor="#CCCCCC"><label>
            <input name="pref_region_0" type="checkbox" id="pref_region_0" value="1" <?php echo strpos($doc->pref_region,'0')!==false?'checked':''; ?>>
            <span style="color: #333333">OPEN</span></label></td>
          <td><label>
            <input name="pref_region_5" type="checkbox" id="pref_region_5" value="1" <?php echo strpos($doc->pref_region,'5')!==false?'checked':''; ?>>
            Mid Atlantic</label></td>
        </tr>
        <tr>
          <td><label>
            <input name="pref_region_1" type="checkbox" id="pref_region_1" value="1" <?php echo strpos($doc->pref_region,'1')!==false?'checked':''; ?>>
            New England</label></td>
          <td><label>
            <input name="pref_region_6" type="checkbox" id="pref_region_6" value="1" <?php echo strpos($doc->pref_region,'6')!==false?'checked':''; ?>>
            South</label></td>
        </tr>
        <tr>
          <td><label>
            <input name="pref_region_2" type="checkbox" id="pref_region_2" value="1" <?php echo strpos($doc->pref_region,'2')!==false?'checked':''; ?>>
            Northeast</label></td>
          <td><label>
            <input name="pref_region_7" type="checkbox" id="pref_region_7" value="1" <?php echo strpos($doc->pref_region,'7')!==false?'checked':''; ?>>
            Southwest</label></td>
        </tr>
        <tr>
          <td><label>
            <input name="pref_region_3" type="checkbox" id="pref_region_3" value="1" <?php echo strpos($doc->pref_region,'3')!==false?'checked':''; ?>>
            Midwest</label></td>
          <td><label>
            <input name="pref_region_8" type="checkbox" id="pref_region_8" value="1" <?php echo strpos($doc->pref_region,'8')!==false?'checked':''; ?>>
            Mountain</label></td>
        </tr>
        <tr>
          <td><label>
            <input name="pref_region_4" type="checkbox" id="pref_region_4" value="1" <?php echo strpos($doc->pref_region,'4')!==false?'checked':''; ?>>
            Upper Midwest </label></td>
          <td><label>
            <input name="pref_region_9" type="checkbox" id="pref_region_9" value="1" <?php echo strpos($doc->pref_region,'9')!==false?'checked':''; ?>>
            West </label></td>
        </tr>
    </table></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td valign="top" bgcolor="#E0E0FF"><strong>Preferred Practice Types</strong>*: </td>
    <td bgcolor="#E0E0FF"><label>
      <input name="pref_practice_1" type="checkbox" id="pref_practice_1" value="SSG" <?php echo stripos($doc->pref_practice,'SSG')!==false?'checked':''; ?>>
      Single-Specialty&nbsp;Group</label>
        <br>
        <label>
        <input name="pref_practice_2" type="checkbox" id="pref_practice_2" value="MSG" <?php echo stripos($doc->pref_practice,'MSG')!==false?'checked':''; ?>>
      Multi-Specialty&nbsp;Group</label>
        <br>
        <label>
        <input name="pref_practice_3" type="checkbox" id="pref_practice_3" value="Solo" <?php echo stripos($doc->pref_practice,'Solo')!==false?'checked':''; ?>>
      Solo&nbsp;Practice</label>
        <br>
        <label>
        <input name="pref_practice_4" type="checkbox" id="pref_practice_4" value="Hosp" <?php echo stripos($doc->pref_practice,'Hosp')!==false?'checked':''; ?>>
      Hospital&nbsp;Employee</label>
        <br>
        <label>
        <input name="pref_practice_5" type="checkbox" id="pref_practice_5" value="Acad" <?php echo stripos($doc->pref_practice,'Acad')!==false?'checked':''; ?>>
      Academic</label>
        <br>
        <label>
        <input name="pref_practice_6" type="checkbox" id="pref_practice_6" value="Locum" <?php echo stripos($doc->pref_practice,'Locum')!==false?'checked':''; ?>>
      Locum&nbsp;Tenens</label>
        <br>
        <label>
        <input name="pref_practice_7" type="checkbox" id="pref_practice_7" value="Pub" <?php echo stripos($doc->pref_practice,'Pub')!==false?'checked':''; ?>>
      Public&nbsp;Health</label>
        <br>
        <label>
        <input name="pref_practice_8" type="checkbox" id="pref_practice_8" value="Rural" <?php echo stripos($doc->pref_practice,'Rural')!==false?'checked':''; ?>>
      Rural&nbsp;Health&nbsp;Center</label>
        <br>
        <label>
        <input name="pref_practice_9" type="checkbox" id="pref_practice_9" value="ER" <?php echo stripos($doc->pref_practice,'ER')!==false?'checked':''; ?>>
      ER/Urgent&nbsp;Care</label></td>
    <td bgcolor="#E0E0FF">&nbsp;</td>
  </tr>
     <tr>
       <td>Preferred City:</td>
       <td><input name="pref_city" type="text" id="pref_city" value="<?php echo $doc->pref_city; ?>" size="40" maxlength="100"></td>
       <td>&nbsp;</td>
     </tr>
     <tr>
       <td>Preferred Community Size:</td>
       <td><label>
         <input name="pref_commu_1" type="checkbox" id="pref_commu_1" value="S" title="Small/Rural" <?php echo strpos($doc->pref_commu2,'S')!==false?'checked':''; ?> onClick="pref_commu_4.checked=false;">
      Small</label>
&nbsp;
      <label>
      <input name="pref_commu_2" type="checkbox" id="pref_commu_2" value="C" title="Medium" <?php echo strpos($doc->pref_commu2,'C')!==false?'checked':''; ?> onClick="pref_commu_4.checked=false;">
      Medium</label>
&nbsp;
      <label>
      <input name="pref_commu_3" type="checkbox" id="pref_commu_3" value="M" title="Metro Area/Big city" <?php echo strpos($doc->pref_commu2,'M')!==false?'checked':''; ?> onClick="pref_commu_4.checked=false;">
      Metro</label>
      <label>
      <input name="pref_commu_4" type="checkbox" id="pref_commu_4" value="0" title="No Preference" onClick="pref_commu_1.checked=true;pref_commu_2.checked=true;pref_commu_3.checked=true;">
      No&nbsp;Preference</label></td>
       <td>&nbsp;</td>
     </tr>
     <tr>
       <td bgcolor="#E0E0FF">Marital Status: </td>
       <td bgcolor="#E0E0FF"><label>
         <input name="marital_status" type="radio" value="S" <?php echo $doc->marital_status=='S'?'checked':''; ?>>
      Single</label>
           <label>
           <input name="marital_status" type="radio" value="M" <?php echo $doc->marital_status=='M'?'checked':''; ?>>
      Married</label>
	  <label>
           <input name="marital_status" type="radio" value="X" <?php echo ($doc->marital_status!='M' && $doc->marital_status!='S')?'checked':''; ?>>
      N/A</label></td>
       <td bgcolor="#E0E0FF">&nbsp;         </td>
     </tr>
     <tr>
       <td>Spouse's Name:</td>
       <td><input name="spouse" type="text" id="spouse" value="<?php echo $doc->spouse; ?>" size="20" maxlength="50"></td>
       <td>&nbsp;</td>
     </tr>
     <tr>
       <td>Spouse's Profession:</td>
       <td><input name="spouse_prof" type="text" id="spouse_prof" value="<?php echo $doc->spouse_prof; ?>" maxlength="50" title="Spouse profession" /></td>
       <td>&nbsp;</td>
     </tr>
     <tr>
       <td bgcolor="#E0E0FF" title="Spouse specialty, if physician">Spouse's Specialty, if physician:</td>
       <td colspan="2" bgcolor="#E0E0FF" title="Spouse specialty, if physician"><?php echo showSpecList($db,$doc->spouse_spec,'spouse_spec'); ?></td>
     </tr>
     <tr>
       <td bgcolor="#E0E0FF" title="If born in the USA">Spouse's Home/Birth state:</td>
       <td bgcolor="#E0E0FF"><?php echo showStateList($db,$doc->spouse_state,'spouse_state'); ?></td>
       <td bgcolor="#E0E0FF">&nbsp;</td>
     </tr>
     <tr>
       <td>Any Children?</td>
       <td><label>
         <input name="children" type="radio" value="1" <?php echo $doc->children?'checked':''; ?>>
      Yes</label>
&nbsp;
      <label>
      <input name="children" type="radio" value="0"  <?php echo $doc->children?'':'checked'; ?>>
      No</label></td>
       <td>&nbsp;</td>
     </tr>
  <tr bgcolor="#E0E0FF">
    <td>Spoken Languages:</td>
    <td><textarea name="languages" cols="40" rows="3" id="languages"><?php echo $doc->languages; ?></textarea></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td bgcolor="#E0E0FF">Hobbies, Interests:</td>
    <td bgcolor="#E0E0FF"><textarea name="hobbies" cols="40" rows="5" id="hobbies"><?php echo $doc->hobbies; ?></textarea></td>
    <td bgcolor="#E0E0FF">&nbsp;</td>
  </tr>
	<tr>
	   <td>Contact Preference: </td>
			  <td><select name="contact_pref" id="contact_pref" size="1">
				<option value="0" <?php echo $doc->contact_pref?'':'selected'; ?>>No preference</option>
				<option value="1" <?php echo $doc->contact_pref==1?'selected':''; ?>>Home Phone</option>
				<option value="2" <?php echo $doc->contact_pref==2?'selected':''; ?>>Office</option>
				<option value="3" <?php echo $doc->contact_pref==3?'selected':''; ?>>Cell Phone</option>
				<option value="4" <?php echo $doc->contact_pref==4?'selected':''; ?>>Email</option>
				<option value="5" <?php echo $doc->contact_pref==5?'selected':''; ?>>Pager</option>
				<option value="6" <?php echo $doc->contact_pref==6?'selected':''; ?>>Postal Mail</option>
			  </select>  
			  </td>
    <td>&nbsp;</td>
	</tr>
	<tr>
	  <td>Email Options:</td>
	  <td colspan="2"><label><input name="newsletter" type="checkbox" id="newsletter" value="1" <?php echo $doc->newsletter?'checked':''; ?>> 
	    Newsletters</label></td>
	  </tr>
  <tr>
    <td>Reason for leaving  current position:</td>
    <td><textarea name="reason_leaving" cols="40" rows="5" id="reason_leaving"><?php echo $doc->reason_leaving; ?></textarea></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td title="Comments are visible to clients">Other Comments:</td>
    <td><textarea name="other_pref" cols="40" rows="5" id="other_pref"><?php echo $doc->other_pref; ?></textarea></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td><strong>Source</strong>*:</td>
    <td><?php $source = $doc?$doc->source:0; 
		if( $source && $source < 20 ) {
			$srcar = array('','Search engine','Email campaign','Our Representative','Recommended by other physician','Recommended by program director','Newsletter','Journal Ad','Internet Ad','Other Self-R.'); // add more if needed
			echo $srcar[$source]; echo " <input name='source' type='hidden' value='$source'>";
		}
		else { ?>
			<select name="source" id="source" size="1">     
                        <option value="0" <?php echo $source?'':'selected'; ?>> </option>
						<option value="20" <?php echo $source==20?'selected':''; ?>>PHG Database</option>
                        <option value="21" <?php echo $source==21?'selected':''; ?>>Job Board</option>
                       <option value="22" <?php echo $source==22?'selected':''; ?>>Email Blast</option>
                        <option value="23" <?php echo $source==23?'selected':''; ?>>Web Page</option>
                        <option value="24" <?php echo $source==24?'selected':''; ?>>Cold Call</option>
                        <option value="25" <?php echo $source==25?'selected':''; ?>>Self Register</option>
                       <option value="29" <?php echo $source==29?'selected':''; ?>>Other source</option>
		</select><?php 	if( $doc->phg_source ) echo " PHG: $doc->phg_source"; 
		}
	?></td>
    <td>&nbsp;</td>
  </tr>
  <?php
  if( !isset($db) ) $db = db_career();
		$sql = "select username, firstname, lastname from operators where uid = $doc->uid_saved limit 1";
		$result = $db->query($sql);
		if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not retrieve operator',__LINE__);
		
		for( $i = 0; $i < $result->num_rows; $i++ ) {
			$row = $result->fetch_assoc();
			$last_saved_user = $row["username"];
		}
?>
  <tr>
    <td>New or Updated Release: </td>
    <td><label>
         <input name="as_new" type="radio" value="1" <?php echo $doc->as_new==1?'checked':''; ?>>
      New</label>
&nbsp;
      <label>
      <input name="as_new" type="radio" value="0"  <?php echo $doc->as_new?'':'checked'; ?>>
      NOT New</label>
&nbsp;
      <label>
      <input name="as_new" type="radio" value="2"  <?php echo $doc->as_new==2?'checked':''; ?>>
      Do not release</label><br />
	Record was created <?php echo $doc->reg_date? $doc->reg_date: $doc->data_entry; echo $doc->iv_date?';':'; Was not'; echo " Verified $doc->iv_date; Last saved $doc->last_save"; echo " by ".$last_saved_user; ?></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td><?php echo $doc->pending == 2?'Interview Complete':'Verification'; ?></td>
    <td><?php if( $doc->pending == 2 ) { // not IC
	?><label>
      <input type="radio" name="pending" value="1" /> Interview Complete</label> &nbsp; <label><input type="radio" checked name="pending" value="2" /> Not yet</label>
	<?php
	} elseif( $doc->pending ) { // IC or self-reg
	?><label></label><input type="radio" name="pending" value="0" /> Verify</label> &nbsp; <label><input type="radio" name="pending" checked value="1" />  Not yet</label> &nbsp; <label><input type="radio" name="pending" value="2" />  Incomplete</label>
	<?php
	} else { 
	?>
	<label><input type="radio" name="pending" value="0" <?php if($doc->pending==0){ echo "checked"; }?> /> <strong>Verified</strong></label> &nbsp; <label><input type="radio" name="pending" value="2" <?php if($doc->pending==2){ echo "checked"; }?> />  Incomplete</label>  <label><input type="radio" name="pending" <?php if($doc->pending==1){ echo "checked"; }?> value="1" />  Unverified</label>
	<?php
	} ?></td>
    <td>&nbsp;</td>
  </tr>
                      <tr valign="bottom"> 
                        <td> 
                          <?php if( $pos && $preved ) echo 
				"<a href='showdocpc.php?id=$preved&lid=$lid&ck=$very$peekarg&y=$2005&pos=".($pos-1)."'>Prev</a>";
							  else echo '&nbsp;'; ?>
                        </td>
                        <td align="center"><input name="savest" type="submit" id="submit2" value="Save" width="74" style="width:74px ">
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<input name="reset" type="reset"  width="74" style="width:74px " value="Reset">
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<input name="submit" type="submit" id="submit0" value="Send PHG CV Email" title="Regarding your CV... asks for permission to post it here">
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<input name="submitcv" type="submit" id="submit10" value="PC CV Request" title="Your CV and preferences... asks for new CV">
						</td>
                        <td align="right"> 
                          <?php if( $nextid ) echo 
				"<a href='showdocpc.php?id=$nextid&lid=$lid&ck=$very$peekarg&y=2005&pos=".($pos+1)."'>Next</a>";
							  else echo '&nbsp;'; ?>
                        </td>
                      </tr>
   </table>
			
<br>
<table border="0">
  <tr>
                      <td style="border-top: thin solid #003399; ">New Note:</td>
                      <td style="border-top: thin solid #003399;"><textarea name="note_text" cols="75" rows="4" id="note_text"></textarea></td>
  </tr>
</table>

  			    </form>
			  </div>
              <hr>              
              <table width="80%"  border="1" cellpadding="2" cellspacing="0">
                <tr>
                  <th>Date/Time</th>
                  <th>User</th>
                  <th>Notes</th>
                </tr>
<?php 
	try {
		if( !isset($db) ) $db = db_career();
		$opers = array();
		// inter-db joins are not possible
		$result = $db->query("select uid,username from operators");
		if( !$result || !$result->num_rows ) throw new Exception('Who am I? Who are you?',__LINE__);
		while( $oper = $result->fetch_object() )
			$opers[$oper->uid] = $oper->username;
		$result->free();
		$result = $db->query("select uid,email from clients");
		if( !$result || !$result->num_rows ) throw new Exception('What happened to clients?',__LINE__);
		while( $oper = $result->fetch_object() )
			$opers[$oper->uid] = $oper->email;
		$result->free();

		if( !isset($nodb) ) $nodb = db_notes();
		$sql = "select note_id,date_format(dt,'%c/%e/%y %T') as datetim,n.uid,note,shared from notes n where res_id = $docid and shared <= $ACCESS and acct = 0 order by dt desc";
		$result = $nodb->query($sql);
		$firstnote = true; unset($_SESSION['delete_note']); unset($_SESSION['delete_note_yr']);
		if( !$result ) throw new Exception(DEBUG?"$nodb->error : $sql":'Can not retrieve notes',__LINE__);
		for( $i = 0; $i < $result->num_rows; $i++ ) {
			$row = $result->fetch_assoc();
?>
                <tr>
                  <td><?php echo $row['datetim']; 
				  	if( $firstnote ) {
						if( $row['uid'] == $UUID || $ACCESS == 500 ) {
							$_SESSION['delete_note_str'] = 
								"id=$docid&lid=$lid&y=2005&pos=$pos";
							$_SESSION['delete_note'] = $row['note_id'];
							$_SESSION['delete_note_yr'] = 2005;
				  ?>
				  <a href="delnote.php" title="Delete Note"><img src="images/b_drop.png" width="16" height="16" border="0" align="absbottom" alt="X" title="Delete Note"></a><?php 
						}
						$firstnote = false;
				  	}
				  ?>
				  </td>
                  <td <?php if( $row['uid'] == $UUID ) echo ' class="style3"'; ?>
				  ><?php echo $row['uid'] == $UUID?"Me":$opers[$row['uid']]; ?>&nbsp;<?php 
				  	if( $row['shared'] ) echo ' <span class="style4" title="Restricted">&reg;</span>'; ?>
				  </td>
                  <td><?php echo htmlspecialchars(stripslashes($row['note'])); ?></td>
                </tr>
<?php 
		} // for
		$result->free();
		$sql = "select note_id,date_format(dt,'%c/%e/%y %T') as datetim,n.uid,note,shared from notes n where res_id = $docid and n.acct != 0 order by dt desc";
		$result = $nodb->query($sql);
		if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not retrieve customer notes',__LINE__);
		if( $result->num_rows ) echo "<tr><th colspan=3>Customers' Notes:</th></tr>";
		for( $i = 0; $i < $result->num_rows; $i++ ) {
			$row = $result->fetch_assoc();
?>
                <tr>
                  <td><?php echo $row['datetim']; 
				  	if( $firstnote ) {
						if( $row['uid'] == $UUID || $ACCESS == 500 ) {
							$_SESSION['delete_note_str'] = 
								"id=$docid&lid=$lid&ck=$very$peekarg&y=2005&pos=$pos";
							$_SESSION['delete_note'] = $row['note_id'];
							$_SESSION['delete_note_yr'] = 2005;
				  ?>
				  <a href="delnote.php" title="Delete Note"><img src="images/b_drop.png" width="16" height="16" border="0" align="absbottom" alt="X" title="Delete Note"></a><?php 
						}
						$firstnote = false;
				  	}
				  ?>
				  </td>
                  <td <?php if( $row['uid'] == $UUID ) echo ' class="style3"'; ?>
				  ><?php echo $opers[$row['uid']]; ?>&nbsp;<?php 
				  	if( !$row['shared'] ) echo ' <span class="style4" title="Private">&reg;</span>'; ?>
				  </td>
                  <td><?php echo htmlspecialchars(stripslashes($row['note'])); ?></td>
                </tr>
<?php 
		} // for 2
	}
	catch(Exception $e) {
		echo "<tr><td colspan=3>Problem accessing notes: ".$e->getMessage().' ('.$e->getCode().
			")</td></tr>";
	}
?>
              </table>
              <?php	
			  
			  } // doc
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
