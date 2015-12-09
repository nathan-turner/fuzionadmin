<?php
	// final 5/4/7 SL
    require("globals.php5");
    require("cookies.php5");
	// $UUID <> 0 if auth
	$mesg = ''; $success = false; unset($uid);
	if( $UUID && $ACCESS && $ACCESS >= 200 ) try {
		$db = db_career();
		$uid = $_REQUEST['cid'];
		if( !$uid || !is_numeric($uid) ) throw new Exception('Customer ID required',__LINE__);
		$client = new Customer($db,$uid);
		if( $client->is_oper() ) throw new Exception('This is <strong>NOT</strong> a customer',$uid);
		$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
		extract($strippost,EXTR_SKIP);
		if( isset($submit) ) { // form processing
			$passA = trim($_POST['password1']);  $passB = trim($_POST['password2']);
			if( empty($email) || empty($firstname) || empty($lastname)
				|| empty($title) || empty($company) || empty($city) 
				|| empty($state) || $state=='--' || empty($phone)
				|| empty($subaccts) || !is_numeric($subaccts) )
				throw new Exception('Required fields are missing in the form',__LINE__);
			// ok? no, more checks
			if( !valid_email($email) ) throw new Exception('Email address is invalid',__LINE__);
			if( !empty($maildrop) && !valid_email($maildrop) ) throw new Exception('CC address is invalid',__LINE__);
			if( !empty($passA) && !empty($passB) ) {
				if( strlen($passA) < 6 )
					throw new Exception('Password can not be shorter than six symbols',6);
				if( $passA == $email || $passA == $firstname || $passa == $lastname )
					throw new Exception('Password can not be the same as customer name or email',8);
				if( $passA != $passB )
					throw new Exception('Passwords entered are not the same',7);
				$client->password = sha1(stripslashes($passA));
			}
			$client->firstname = $firstname; $client->lastname = $lastname;
			$client->email = $email;
			$client->maildrop = $maildrop;
			$client->phone = $phone; $client->fax = preg_replace('/[^0-9]/','',$fax);
			$client->title = $title; $client->subaccts = $subaccts; 
			$client->mrmsdr = $mrmsdr; $client->company = $company; $client->city = $city;
			$client->state = $state; $client->zip = $zip;
			$client->addr1 = $addr1; $client->addr2 = $addr2;
			//echo $client->on_trial;
			if($on_trial==1){				
				// update sub-accounts, too
				//echo "update clients set on_trial=1 where acct=$client->acct";
				$sql = "update clients set on_trial=1 where acct=$client->acct";
				$db->query($sql);
				//$client->on_trial = $on_trial;
			}
			if($on_trial==0 && $client->on_trial!=0){				
				// update sub-accounts, too				
				$sql = "update clients set on_trial=0 where acct=$client->acct";
				$db->query($sql);
				//$client->on_trial = $on_trial;
			}
			$subscription = 1; // always have search!!
			if( $massmail ) $subscription |= 16;
			if( $phycar ) $subscription |= 8;
			if( $export ) $subscription |= 4;
			if( $notifications ) $subscription |= 32;
			if( $notifications2 ) $subscription |= 64;
			if( $notifications3 ) $subscription |= 128;
			//if( $practice ) $subscription |= 2;
			if( $training ) $subscription |= 2; 
			$subsql = ''; $subspc = '';
			if( $client->subscription != $subscription ) $subsql .= ($subsql?', ':'')."subscription=$subscription";
			$client->subscription = $subscription;
			if( $client->specs != $cli_specs ) $subsql .= ($subsql?', ':'')."specs=".chknul($cli_specs);
			$client->specs = $cli_specs;
			if( is_numeric($opplimit) && $client->opplimit != $opplimit ) $subsql .= ($subsql?', ':'')."opplimit=$opplimit";
			if( is_numeric($opplimit) ) $client->opplimit = $opplimit;
			if( is_numeric($emaillimit) && $client->emaillimit != $emaillimit ) $subsql .= ($subsql?', ':'')."emaillimit=$emaillimit";
			if( is_numeric($emaillimit) ) $client->emaillimit = $emaillimit;
			//$mesg = $cli_specs;
			if( $exp_date != $client->exp_date ) {
				$subsql .= ($subsql?', ':'')."exp_date='$exp_date'";
				$subspc .= ($subspc?', ':'')."exp_date='$exp_date'";
			}
			$client->exp_date = $exp_date; 
			$sttus = $active?1:0;
			if( $client->status != $sttus && $client->master_acct ) { 
				$subsql .= ($subsql?', ':'')."status=$sttus";
			}
			$deact = ($client->status && !$sttus); 	// deactivation flag
			$client->status = $sttus;
			if( $subsql ) {
				// update sub-accounts, too
				$sql = "update clients set $subsql where acct=$client->acct";
				$db->query($sql);
			}
			// all good so far
			$client->save_user($db);
			$success = true;
			$note_text = substr(trim(strip_tags($note_text)),0,254);
			if( strlen($note_text) == 254 ) $note_text .= '-';
			if( !empty($note_text) ) { // save note
				$sql = "insert into notes (uid,shared,note,res_id,year) values ($UUID,".
					($note_shared?0:$ACCESS).",'$note_text',$uid, 666)";
				$result = $db->query($sql);
				if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not save notes. But the record was saved.',__LINE__);
			}
			if( $subspc ) {
				$sql = "update locations set $subspc,l_usermod=$UUID where l_acct=$client->acct and status in (1,2)";
				$db->query($sql);
				$sql = "update opportunities set $subspc,o_usermod=$UUID where o_acct=$client->acct and status in (1,8)";
				$db->query($sql);
			}
			if( $deact ) {
				$sql = $client->master_acct?"delete custlistsus from custlistsus, clients where owneruid = uid and acct = $client->acct": "delete from custlistsus where owneruid = $uid";
				$db->query($sql);
				$sqll = $client->master_acct?"l_acct=$client->acct":"l_uid=$uid";
				$sqlo = $client->master_acct?"o_acct=$client->acct":"o_uid=$uid";
				$sql = "delete ratings from ratings, opportunities where r_oid = oid and $sqlo";
				$db->query($sql); // ignore result for now
				$sql = "delete ratingop from ratingop, opportunities where roid = oid and $sqlo";
				$db->query($sql); // ignore result for now
				$sql = "update applications,opportunities set applications.status=0 where opid=oid and $sqlo";
				$db->query($sql); // ignore result for now
				$sql = "update locations set status=0,l_usermod=$UUID where $sqll and status=1";
				$db->query($sql);
				$sql = "update opportunities set status=0,o_usermod=$UUID where $sqlo and status=1";
				$db->query($sql);
			}
			elseif( $unexpire ) {
				$client->unexpire_user($db, ($unexpire == 2));
			}
			/*$email = stripslashes($client->email); 
			$firstname = stripslashes($client->firstname); $lastname = stripslashes($client->lastname);
			$title = stripslashes($client->title); $company = stripslashes($client->company);
			$addr1 = stripslashes($client->addr1); $addr2 = stripslashes($client->addr2);
			$city = stripslashes($client->city); $zip = stripslashes($client->zip); 
			$subscription = $client->subscription; $specs = $client->specs;
			$passA = ''; $passB = '';*/
		}
		//else
		if( $client ) { // initial form setup
			$email = stripslashes($client->email); 
			$maildrop = stripslashes($client->maildrop); 
			$active = $client->status; $mrmsdr = $client->mrmsdr;
			$firstname = stripslashes($client->firstname); $lastname = stripslashes($client->lastname);
			$title = stripslashes($client->title); $company = stripslashes($client->company);
			$phone = $client->phone; $fax = $client->fax; 
			$addr1 = stripslashes($client->addr1); $addr2 = stripslashes($client->addr2);
			$city = stripslashes($client->city); $state = $client->state;
			$zip = stripslashes($client->zip); 
			$exp_date = $client->exp_date; $subaccts = $client->subaccts;
			$subscription = $client->subscription;
			$specs = $client->specs;
			$on_trial = $client->on_trial;
			$passA = ''; $passB = '';
			// sub-accounts
			if( isset($ubmit) ) { // subaccount status form
			  if( !empty($setmaster) && is_numeric($setmaster) ) {
			  		// master account override
					$sql = "update clients set master_acct=1,subaccts=$subaccts,editedby=$UUID,editeddt=now() where uid=$setmaster and acct=$client->acct and status=1";
					$db->query($sql);
					if( $db->affected_rows ) {
						$sql = "update clients set master_acct=0,subaccts=1,editedby=$UUID,editeddt=now() where uid!=$setmaster and acct=$client->acct and master_acct=1";
						$db->query($sql);
						header("Location: custedit.php?cid=$setmaster");
						exit;
					}
					else throw new Exception('Can not change master account.',__LINE__);
			  }
			  foreach( $strippost as $skey => $sval )
				if( strpos($skey,'ubst_') ) {
					$suid = substr($skey, 6); if( !is_numeric($suid) ) continue;
					$nsta = $sval == 1? 1:0;
					if( $nsta ) { // activate
						$sql = "update clients set status=1,editedby=$UUID,editeddt=now() where status=0 and uid=$suid and acct=$client->acct and master_acct=0";
						$db->query($sql);
					}
					else { // deactivate
						$sql = "delete from custlistsus where owneruid = $suid";
						$db->query($sql);
						$sqlo = "o_uid=$suid";
						$sql = "delete ratings from ratings, opportunities where r_oid = oid and $sqlo";
						$db->query($sql); // ignore result for now
						$sql = "delete ratingop from ratingop, opportunities where roid = oid and $sqlo";
						$db->query($sql); // ignore result for now
						$sql = "update applications,opportunities set applications.status=0 where opid=oid and $sqlo";
						$db->query($sql); // ignore result for now
						//$sql = "update locations set status=0,l_usermod=$UUID where l_uid=$suid and status=1";
						//$db->query($sql);
						$sql = "update opportunities set status=0,o_usermod=$UUID where $sqlo and status=1";
						$db->query($sql);
						$sql = "update clients set status=0,editedby=$UUID,editeddt=now() where status=1 and uid=$suid and acct=$client->acct and master_acct=0";
						$db->query($sql);
					}
				}
			}
			$sql = "select uid from clients where acct=$client->acct and master_acct = 0 order by status desc";
			$subres = $db->query($sql);
			if( !$subres ) throw new Exception(DEBUG?"$db->errror : $sql":'Can not run subaccounts query',__LINE__);
			$subuid = $uid;
			if( $subres->num_rows )
				while( list($suid) = $subres->fetch_row() ) $subuid .= ','.$suid;
			$subres->free();
			$sql = "select uid, email, firstname, lastname, phone, status, lastlogdate from clients where acct=$client->acct and master_acct = 0 order by status desc, firstname";
			$subres = $db->query($sql);
			if( !$subres ) throw new Exception(DEBUG?"$db->errror : $sql":'Can not run subaccounts query',__LINE__);
		}
	}
	catch(Exception $e) {
		$mesg = 'Attention: '.$e->getMessage().' ('.$e->getCode().')<br>';
		//unset($oper);
	}
	$style = new OperPage('Edit Customer',$UUID,'admin','custsearch');
	$scrip = '<script type="text/javascript" src="calendarDateInput.js"></script>'."\r\n".
			 '<script language="JavaScript" type="text/JavaScript">';
	$scrip .= <<<HereScrip

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

function moveOptions(theSelFrom, theSelTo)
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
	
	// Add the selected text/values in reverse order.
	// This will add the Options to the 'to' Select
	// in the same order as they were in the 'from' Select.
	for(i=selectedCount-1; i>=0; i--)
	{
		addOption(theSelTo, selectedText[i], selectedValues[i]);
	}
	
	return true;
}

function submitform(theForm) {
     var i;
     var PSta=theForm.cli_specs;
	 PSta.value = "";
     SelList=theForm.speclist;
     for(i=0; i < SelList.options.length && i < 5; i++) {
	 	  if( i == 0 ) {
		  	PSta.value = SelList.options[i].value;
		  }
		  else {
		    PSta.value += "," + SelList.options[i].value;
		  }
	 }
	// alert(PSta.value);
	 return true;
}
HereScrip;
	$scrip .= '</script>';
	$style->Output($scrip);
	if( $UUID ) {
			if( $ACCESS < 200 ) echo '<h1>Access Denied</h1>';
			else {
?>


              <h1>Edit Customer #<?php echo $uid; ?></h1>
              <?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
	if( $success ) echo '<h3 id="warning_msg">Operation completed successfully.</h3>';


?>
			  <p>This account has <?php echo $subres&&$subres->num_rows?$subres->num_rows:'no'; ?> sub-accounts. <?php if ( $subres&&$subres->num_rows ) { ?><a href="#suba">Click here</a> to see the list.<?php } ?></p>
			  <div id="formdiv">
			  <form action="custedit.php" method="post" name="formed" id="formed" onSubmit="return submitform(this);">
                <table style="width: 100%">
                  <tr>
                    <td>Email:</td>
                    <td colspan="2"><input name="email" type="text" id="email" value="<?php echo $email; ?>" size="50" maxlength="100"></td>
					<td><label><input type="checkbox" name="massmail" value="1" <?php echo $subscription&16?'checked':'' ?>>&nbsp;Exclude from Mass&nbsp;Email</label></td>
                    <td>Status:</td>
                    <td><label>
                      <input name="active" type="radio" value="1" <?php echo $active?'checked':''; ?>>
      Active</label>
                        <br />
                        <label>
                        <input name="active" type="radio" value="0" <?php echo $active?'':'checked'; ?>>
      Inactive</label></td>
                  </tr>
                  <tr>
                    <td>Master CC: </td>
                    <td colspan="2"><input name="maildrop" type="text" id="maildrop" value="<?php echo $maildrop; ?>" size="50" maxlength="100"> 
                      <em>(can be the same as above, or blank)</em> </td>
                    <td><input type="checkbox" name="on_trial" value="1" <?php if($on_trial==1){ echo "checked"; } ?> />&nbsp;On Trial</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td><input name="mrmsdr" type="radio" value="Mr" 
					      <?php echo $mrmsdr==='Mr'||!$mrmsdr?'checked':'' ?>>
      Mr&nbsp;&nbsp;
      <label>
      <input name="mrmsdr" type="radio" <?php echo $mrmsdr==='Ms'?'checked':'' ?> value="Ms">
      Ms</label>
&nbsp;
      <label>
      <input name="mrmsdr" type="radio" <?php echo $mrmsdr==='Dr'?'checked':'' ?> value="Dr">
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
                    <td colspan="5">Line 1*:
                        <input name="addr1" type="text" id="addr1" value="<?php echo $addr1; ?>" size="55" maxlength="120">
                        <br />
      Line 2*:
      <input name="addr2" type="text" id="addr2" value="<?php echo $addr2; ?>" size="55" maxlength="120"></td>
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
                    <td><?php echo $client?$client->acct:''; ?></td>
                    <td>Expiration Date: </td>
                    <td><script language="javascript">DateInput('exp_date', false, 'YYYY-MM-DD', '<?php echo $exp_date?$exp_date:date('Y-m-d',time()+3600*24*365); ?>');</script></td>
                    <td># of users: </td>
                    <td><input name="subaccts" type="text" id="subaccts" value="<?php echo $subaccts?$subaccts:1; ?>" size="12" maxlength="3" title="max number of users (sub-accounts)"></td>
                  </tr>
                  <tr>
                    <td colspan="2"><div align="right"><em>If the account got expired already, then to renew it: 1. set Status as Active, 2. put new Expiration Date, and 3. select from these options: </em></div></td>
                    <td><div align="right">Un-Expire Jobs: </div></td>
                    <td colspan="3"><input name="unexpire" type="radio" value="0" checked>
                    No 
                      <input name="unexpire" type="radio" value="1">
                    For this master 
                    <input name="unexpire" type="radio" value="2">
                    For whole account</td>
                  </tr>

                  <tr>
                    <td>Password*:</td>
                    <td><input name="password1" type="password" id="password1" value="<?php echo $passA; ?>" maxlength="40"></td>
                    <td>Repeat Password*:</td>
                    <td><input name="password2" type="password" id="password2" value="<?php echo $passB; ?>" maxlength="40"></td>
                    <td><input name="cid" type="hidden" id="uid" value="<?php echo $uid; ?>"></td>
					<td>&nbsp;</td>
                  </tr>
                <tr>
                  <td style="border-top: thin solid #003399">Last Login IP: </td>
                  <td style="border-top: thin solid #003399"><?php echo isset($client)?$client->lastlogip:'&nbsp;'; ?></td>
                  <td style="border-top: thin solid #003399" colspan="2"><?php echo isset($client) && $client->lastlogip? gethostbyaddr($client->lastlogip):'&nbsp;'; ?></td>
                  <td style="border-top: thin solid #003399">Last Login: </td>
                  <td style="border-top: thin solid #003399"><?php echo isset($client)?$client->lastlogdate:'&nbsp;'; ?></td>
                </tr>
                  <tr>
                    <td colspan="2" style="border-top: thin solid #003399; font-weight:bold">Database Restrictions</td>
                    <td style="border-top: thin solid #003399;">Subscriptions:</td>
                    <td colspan="3" style="border-top: thin solid #003399;"><label><input type="checkbox" name="training" value="3" <?php echo $subscription&2?'checked':'' ?>>&nbsp;All Specialties</label> &nbsp;
					<label><input type="checkbox" name="export" value="1" <?php echo $subscription&4?'checked':'' ?>>&nbsp;Export</label> &nbsp;
                    <label><input type="checkbox" name="phycar" value="1" <?php echo $subscription&8?'checked':'' ?>>
					Job&nbsp;Board</label>  <br>
					
                      <label><input type="checkbox" name="notifications" value="1" <?php echo $subscription&32?'checked':'' ?>>
                      No&nbsp;Newsletter</label>
                      

				</td>
				
                  </tr>
                  <tr>
                    <td colspan="2">Client updates: <label><input type="checkbox" name="notifications2" value="1" <?php echo $subscription&64?'checked':'' ?> title="Daily if unchecked">
                      Weekly</label>
&nbsp;					  <label><input type="checkbox" name="notifications3" value="1" <?php echo $subscription&128?'checked':'' ?> title="All if unchecked">
                      JB&nbsp;specialties&nbsp;only</label></td>
                    <td >Job posts: </td>
                    <td colspan="3"><input type="text" size="6" name="opplimit" value="<?php echo isset($client)?$client->opplimit:0; ?>"> (0 = unlimited)</td>
                  <tr>
                    <td colspan="2">&nbsp;</td>
                    <td >Email limit: </td>
                    <td colspan="3"><input type="text" size="6" name="emaillimit" value="<?php echo isset($client)?$client->emaillimit:0; ?>"> (0 = unlimited)</td>
                  <tr>
                    <td>Specialty: <br>
                      (up to 5)</td>
                    <td colspan="3"><?php echo showSpecList($db,$client->specs,'specs',3); ?></td>
                    <td align="center" colspan="2"><input name="add" type="button" id="add"
				onClick="moveOptions(specs, speclist)" value="   Add   " />
                        <br />
                        <br />
                        <input name="remove" type="button" id="remove"
				onClick="moveOptions(speclist, specs)" value="Remove" />
                    <br><br>
					Select specialties on the left and click Add.</td>
				<tr>
					<td align="center">Specialties<br>
					Allowed:</td>
					<td colspan="3" valign="left"><input type="hidden" name="cli_specs" id="cli_specs" value="<?php echo $client->specs; ?>">
                        <select name="speclist" id="speclist" size="5" multiple>
                          <?php
						  		if( $client->specs ) {
									$spc = explode(',',$client->specs); //$spc = str_split($client->specs,'3');  
									$count = 0;
									foreach( $spc as $sp ) {
										if( $count++ == 5 ) break;
										$cli_sp = $SpecList2[$sp];
										echo "<option value='$sp'>$cli_sp ($sp)</option>";
										if( $count++ == 4 ) break;
									}
								}
						?>
                        </select>
                    </td>
					<td align="center" colspan="2" style="border: thin solid #003399;"><input name="submit" type="submit" id="submit" value="Submit">&nbsp;&nbsp;&nbsp;<input type="reset" name="Reset" value="Reset"></td>
					
                  </tr>
               
                  <tr>
                    <td style="border-top: thin solid #003399; ">New Note:</td>
                    <td style="border-top: thin solid #003399;"><label>
                      <input name="note_shared" type="radio" value="1" checked>
      Shared</label>
&nbsp;
      <label>
      <input name="note_shared" type="radio" value="0">
      Restricted</label>
      <br>
      <span class="style2">Shared notes are open for all employees, but not to clients. Restricted notes are invisible to users with lower access levels. 255 symbols max.</span></td>
                    <td colspan="4" style="border-top: thin solid #003399;"><textarea name="note_text" cols="55" rows="4" id="note_text"></textarea></td>
                  </tr>
                </table>
			  </form>
		      </div>
			  <p>* Optional field</p>
			  <h3>Job Board functions</h3>
			  <p>Manage their locations: <a href="locationsadmin.php?cid=<?php echo "$uid&acct=$client->acct&mas=$uid"; ?>">click here</a>. Manage their opportunities: <a href="opportunadmin.php?cid=<?php echo "$uid&acct=$client->acct&mas=$uid"; ?>">click here</a>. For subbaccounts, see in the table below.</p>
			  <?php if($ACCESS >= 200){ ?>
			  <p><a href="refresh_jobs.php?cid=<?php echo "$uid&acct=$client->acct&mas=$uid"; ?>">Refresh all account opportunities</a></a>
			  <p><a href="custemail2.php?acct=<?php echo "$client->acct"; ?>">Email list for account</a></a>
			  <?php } ?>
			  <table width="80%"  border="1" cellpadding="2" cellspacing="0">
                <tr>
                  <th>Date/Time</th>
                  <th>User</th>
                  <th>Sub-Acc.</th>
                  <th>Notes</th>
                </tr>
<?php 
	try {
		if( !isset($db) ) $db = db_clients();
		if( !$uid ) $uid = 0;
		if( !$subuid ) $subuid = $uid; // how??
		$sql = "select note_id,date_format(dt,'%c/%e/%y %T') as datetim,n.uid,username,note, shared, res_id from notes n join operators u on n.uid = u.uid where year = 666 and res_id in ($subuid) and shared <= $ACCESS order by dt desc";
		$result = $db->query($sql);
		$firstnote = true; unset($_SESSION['delete_note']);
		if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not retrieve notes',__LINE__);
		for( $i = 0; $i < $result->num_rows; $i++ ) {
			$row = $result->fetch_assoc();
?>
                <tr>
                  <td><?php echo $row['datetim']; 
				  	if( $firstnote ) {
						if( $row['uid'] == $UUID || $ACCESS == 500 ) {
							$_SESSION['delete_note_str'] = "cid=$uid";
							$_SESSION['delete_note'] = $row['note_id'];
				  ?>
				  <a href="delnote.php" title="Delete Note"><img src="images/b_drop.png" width="16" height="16" border="0" align="absbottom" alt="X" title="Delete Note"></a><?php 
						}
						$firstnote = false;
				  	}
				  ?>
				  </td>
                  <td <?php if( $row['uid'] == $UUID ) echo ' style="text-weight: bold"'; ?>
				  ><?php echo $row['username']; ?>&nbsp;<?php 
				  	if( $row['shared'] ) echo ' <span style="font-size: 8pt" title="Restricted">&reg;</span>'; ?>
				  </td>
                  <td><?php echo $row['res_id']; ?></td>
                  <td><?php echo strip_tags(stripslashes($row['note'])); ?></td>
                </tr>
<?php 
		} // for
	}
	catch(Exception $e) {
		echo "<tr><td colspan=3>Problem accessing notes: ".$e->getMessage().' ('.$e->getCode().
			")</td></tr>";
	}
?>
              </table>
			  <hr>
			  <h3><a name="suba"></a>Sub-Accounts:</h3>
<?php 
			if( $subres && $subres->num_rows ) {
?>
			  <form action="custedit.php" method="post" name="subform">
			  <table width="90%"  border="0" cellspacing="0" cellpadding="1">
<?php 
				while( list($suid,$sema,$sfn,$sln,$spho,$ssta,$lld) = $subres->fetch_row() ) {
?>
                <tr>
                  <td><a href="custedit.php?cid=<?php echo $suid; ?>"><?php echo $suid; ?></a></td>
                  <td><?php echo stripslashes($sfn).' '.stripslashes($sln); ?><br/><small><?php echo $lld; ?></small></td>
                  <td><?php echo stripslashes($sema); ?></td>
                  <td><?php echo formatphone($spho); ?></td>
                  <td><label><input name="subst_<?php echo $suid; ?>" type="radio" value="1" <?php echo $ssta?'checked':''; ?>>
                    Active</label> &nbsp;
                    <label><input name="subst_<?php echo $suid; ?>" type="radio" value="2" <?php echo $ssta?'':'checked'; ?>>
                    Inactive</label> <label><input name="setmaster" type="radio" value="<?php echo $suid; ?>">
                    Set&nbsp;master</label> </td>
				  <td><a href="opportunadmin.php?cid=<?php echo "$suid&acct=$client->acct&mas=$uid"; ?>">Opportunities</a></td>
                </tr>
<?php 			} // while ?>
				<tr><td colspan="4"><input name="cid" type="hidden" id="cid" value="<?php echo $uid; ?>"></td><td colspan="2"><input name="ubmit" type="submit" id="ubmit" value="Set status"> 
				  &nbsp;&nbsp; <input type="reset" name="Reset" value="Revert"></td>
				</tr>
              </table>
			  </form>
<?php
			  } // subres
				else echo '<p>No Sub-accounts!</p>';
			} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
