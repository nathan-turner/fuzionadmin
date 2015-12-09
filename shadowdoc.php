<?php
	// this is for data entry to process (unshadow) dostors from shadow lists.
	// possible actions are - save, accept, reject. it uses AMAres class.
	// if accepted, it goes to showdoc with some back-lnk.
    require("globals.php5");
	define(DESTY,2011); // destination year
    require("cookies.php5");
    // $UUID <> 0 if auth
	// params: 	$_REQUEST['id'], $_REQUEST['lid'], $_REQUEST['y'], $_REQUEST['pos']
	$docid = $_REQUEST['id'];
	$lid = $_REQUEST['lid'];
	$yer = $_REQUEST['y'];
	$pos = $_REQUEST['pos'];
	$peek = $_REQUEST['peek'];
	$very= $_REQUEST['ck']; // true = show checkins
	$cook_lid = $lid.$peek;
	if( $pos && is_numeric($pos) ) setcookie("pos_$cook_lid",$pos,time()+3600*24*15);
	// $yer is not that important here, because here in shadows it's always year one.
	if( $UUID && $ACCESS == 500 && isset($lid,$docid) ) try {
		$db = db_clients();
		$peekuid = $UUID;
		if( $peek && is_numeric($peek) && $peek != $UUID ) {
			// check if peek is allowed: lists have same ACCT and (you are MASTER or list is shared)
			if( !isset($db) ) $db = db_clients();
			$sql = "select acct,shared from custlistdesc where listid = $lid and uid = $peek and year=1";
			$result = $db->query($sql);
			if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not find list',__LINE__);
			list($pact,$psha) = $result->fetch_row();
			$result->free();
			if( $pact == $ACCT && ($psha < $ACCESS) ) $peekuid = $peek;
			else throw new Exception('You can not peek into this list',__LINE__);
			$peekarg = "&peek=$peek";
		}
		if( is_numeric($lid) && $lid ) {
			if( $pos ) {
				$sql = "select memberuid from custlists where owneruid = $peekuid and listid = $lid LIMIT ".($pos-1).', 3';
				$result = $db->query($sql);
				list($preved) = $result->fetch_row();
				$result->fetch_row(); // skip current
				list($nextid) = $result->fetch_row();
			}
			else {
				$sql = "select memberuid from custlists where owneruid = $peekuid and listid = $lid LIMIT 0, 2";
				$result = $db->query($sql);
				$result->fetch_row(); // skip current
				list($nextid) = $result->fetch_row();
			}
			$result->free();
			//$db->close(); no more
		}
		// got $preved and $nextid
		$ama = db_amalist();
		$doc = new AMAres($ama,$docid);
		if( $doc->uid_mod ) {
			$result = $db->query("select username from operators where uid = $doc->uid_mod");
			list($usermod) = $result->fetch_row();
			$result->free();
		}

		// now process form buttons
		if( !$doc->accept && !$doc->reject ) { // normal case
			if( isset($_POST['submit']) ) {
				// fname midname lname addr1 addr2 city state zip phone spec
				$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
				$fname = $strippost['fname']; $midname = $strippost['midname'];	$lname = $strippost['lname'];
				$addr1 = $strippost['addr1']; $addr2 = $strippost['addr2'];
				$city = $strippost['city'];	$state = $strippost['state']; $zip = $strippost['zip'];
				$phone = $strippost['phone'];	$spec = $strippost['spec'];
				$doc->ho = $strippost['ho'] == 2? 2: 1;
				if( !empty($fname) ) $doc->fname = $fname;
				$doc->midname = $midname;
				if( !empty($lname) ) $doc->lname = $lname;
				$doc->addr1 = $addr1; $doc->addr2 = $addr2;
				$doc->city = $city; $doc->zip = $zip;
				if( !empty($state) ) $doc->state = $state;
				$doc->phone = $phone;
				if( !empty($spec) ) $doc->spec = $spec;
				$doc->save_res($ama);
				$note_text = substr(trim($strippost['note_text']),0,254);
				if( strlen($note_text) == 254 ) $note_text .= '-';
				if( !empty($note_text) ) { // save note
					$sql = "insert into notes (uid,shared,note,res_id,year) values ($UUID,".
						($strippost['note_shared']?0:$ACCESS).",'$note_text',$docid, 1)";
					$result = $db->query($sql);
					if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not save notes. But the record was saved.',__LINE__);
				}
				$saved = true;
				// now call back list
				$cblid = 0;
				if( $strippost['callback'] === 'NEW' ) {
					$clist = new CustList('Call-back - '.date('D'),1,"Call-backs for shadow list# $lid, created on ".date('r'));
					$cblid = $clist->id;
	 			}
				elseif( $strippost['callback'] === 'OLD' ) {
					$cblid = $strippost['cbsel'];
					if( empty($cblid) || !is_numeric($cblid) ) $cblid = 0;
				}
				if( $cblid ) {
					$sql = "insert into custlists values($UUID,$docid,$cblid)";
					$db->query($sql);
				}
			}
			elseif( isset($_POST['accept']) ) {
				$res = db_resident(DESTY);
				$yer=DESTY;
				$doc->accept($ama,$res,$yer); 
				// move notes
				$sql = "update notes set year = ".DESTY." where res_id = $docid and year = 1";
				$db->query($sql);
				$redir = "showdoc.php?id=$docid&lid=$lid&ck=$very$peekarg&pos=$pos&shadow=1&next=$nextid&prev=$preved&y=".DESTY;
			}
			elseif( isset($_POST['reject']) ) {
				$doc->reject($ama);
				$redir = "shadowdoc.php?id=$nextid&lid=$lid&ck=$very$peekarg&y=$yer&pos=".($pos+1);
			}
		} // not already accepted-rejected
	}
	catch(Exception $e) {
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
		unset($result);
	}
	$style = new OperPage('Shadow Profile',$UUID,'residents','shadow',($redir?"2; URL=$redir":''));
	$style->Output();
	
	if( $UUID ) {
?>
              <h1>Edit Profile <?php if( $saved ) echo '(Saved)'; ?></h1>
<?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
	if( $doc ) {
		if( $doc->accept ) echo "<h2 id='warning_msg'>Already Accepted! Changes would not be saved!</h2>";
		if( $doc->reject ) echo "<h2 id='warning_msg'>Already Rejected! Changes would not be saved!</h2>";
?>
              <div id="formdiv"><form name="form1" method="post" action="shadowdoc.php">
                <p>ID#: <?php echo $doc->uid; if( $lid < 128 ) echo " <a href='results.php?id=$lid&ck=$very$peekarg&y=$yer'>back to the list</a>"; ?>
                  <input name="id" type="hidden" id="id" value="<?php echo $doc->uid; ?>">
                  <input name="lid" type="hidden" id="lid" value="<?php echo $lid; ?>">
                  <input name="y" type="hidden" id="y" value="<?php echo $yer; ?>">
                  <input name="pos" type="hidden" id="pos" value="<?php echo $pos; ?>">
                  <input name="peek" type="hidden" id="peek" value="<?php echo $peek; ?>">
                  <input name="ck" type="hidden" id="ck" value="<?php echo $very; ?>">
(Last saved on <?php echo formatDateMod($doc->date_mod); ?> by <?php echo $usermod; ?>)</p>
                  <table style="width:100% " border="0">
                    <tr> 
                      <td>First Name:</td>
                      <td><input name="fname" type="text" id="fname" value="<?php echo $doc->fname; ?>" maxlength="50"></td>
                      <td>Middle:</td>
                      <td><input name="midname" type="text" id="midname" value="<?php echo $doc->midname; ?>" maxlength="30"></td>
                      <td>Last name:</td>
                      <td><input name="lname" type="text" id="lname" value="<?php echo $doc->lname; ?>" maxlength="50"></td>
                    </tr>
                    <tr> 
                      <td>Address Line 1:</td>
                      <td colspan="5"><input name="addr1" type="text" id="addr1" value="<?php echo $doc->addr1; ?>" size="50" maxlength="100"> 
                        &nbsp;&nbsp;Line 2: 
                        <input name="addr2" type="text" id="addr2" value="<?php echo $doc->addr2; ?>" size="45" maxlength="100"></td>
                    </tr>
                    <tr> 
                      <td>City:</td>
                      <td><input name="city" type="text" id="city" value="<?php echo $doc->city; ?>" maxlength="30"></td>
                      <td>State:</td>
                      <td><?php echo showStateList($ama,$doc->state); ?></td>
                      <td>Zip:</td>
                      <td><input name="zip" type="text" id="zip" value="<?php echo $doc->zip; ?>" maxlength="10"></td>
                    </tr>
                    <tr>
                      <td>Address is: </td>
                      <td align="right"><label><input name="ho" type="radio" value="2" <?php if( $doc->ho == 2 ) echo 'checked';?> >
                        Home</label> or 
                        <label><input name="ho" type="radio" value="1" <?php if( $doc->ho == 1 ) echo 'checked';?> >
Office address</label></td>
                      <td>&nbsp;</td>
                      <td colspan="3">&nbsp;</td>
                    </tr>
                    <tr> 
                      <td>Phone</td>
                      <td><input name="phone" type="text" id="phone" value="<?php echo $doc->phone; ?>" maxlength="16" onChange="checkPhoEx(phone,'phone',state,'state')"> <img src="images/bquestion.png" alt="?" width="16" height="16" border="0" align="absmiddle" title="Check Area code/State match" onClick="checkPhoEx(phone,'phone',state,'state')"></td>
                      <td>Specialty:</td>
                      <td colspan="3"><?php echo showSpecList($ama,$doc->spec); ?></td>
                    </tr>
                    <tr> 
                      <td>Med school:</td>
                      <td colspan="3"><?php echo $doc->sch_name; ?></td>
                      <td><?php echo $doc->sch_amg?'AMG':'IMG'; ?></td>
                      <td><?php echo $doc->sch_loc; ?></td>
                    </tr>
                    <tr> 
                      <td style="border-bottom: thin solid; border-left: thin solid; border-top: thin solid ">Res program: </td>
                      <td colspan="2" style="border-bottom: thin solid; border-top: thin solid "><?php echo $doc->res_name; ?></td>
                      <td style="border-bottom: thin solid; border-top: thin solid "><?php echo $doc->res_city; ?></td>
                      <td style="border-bottom: thin solid; border-top: thin solid "><?php echo $doc->res_state; ?></td>
                      <td style="border-bottom: thin solid; border-right: thin solid; border-top: thin solid "><?php echo $doc->res_phone; ?>&nbsp;</td>
                    </tr>
                    <tr> 
                      <td> 
                        <?php if( $pos && $preved ) echo "<a href='shadowdoc.php?id=$preved&lid=$lid&ck=$very$peekarg&y=$yer&pos=".($pos-1)."'>Prev</a>";
							  else echo '&nbsp;'; ?>
                      </td>
                      <td><input name="submit" type="submit" id="submit" value="Save"> 
                        &nbsp;&nbsp; <input type="reset" name="Reset" value="Reset"></td>
                      <td>&nbsp;</td>
                      <td><input name="accept" type="submit" id="accept" value="Accept">
                        &nbsp; &nbsp; <input name="reject" type="submit" id="reject" value="Reject"> 
                      </td>
                      <td>&nbsp;</td>
                      <td> 
                        <?php if( $nextid ) echo "<a href='shadowdoc.php?id=$nextid&lid=$lid&ck=$very$peekarg&y=$yer&pos=".($pos+1)."'>Next</a>";
							  else echo '&nbsp;'; ?>
                      </td>
                    </tr>
<?php 
	// call-back
	try {
		if( !isset($db) ) $db = db_clients();
		$sql = "select listid,name from custlistdesc where uid=$UUID and year=1 and listid between 10 and 128 order by listid desc";
		$rescb = $db->query($sql);
		if( $rescb && $rescb->num_rows ) $cblists = true;
	}
	catch(Exception $e) {
		echo "<tr><td colspan=6>Problem accessing lists: ".$e->getMessage().' ('.$e->getCode().
			")</td></tr>";
	}
?>
                    <tr>
                      <td>&nbsp;</td>
                      <td>Also, add this doctor to my call-back list: </td>
                      <td><label><input name="callback" type="radio" value="NEW" id="cbnew">
                      New</label></td>
                      <td colspan="3"><input name="callback" type="radio" value="OLD" id="cbold">
                        <select name="cbsel" id="cbsel" onChange="selCB()" onClick="selCB()">
<?php 
		while( $cblists && (list($listid,$listname) = $rescb->fetch_row()) )
			echo "<option value='$listid'>$listname</option>";
?>
                          </select>
                      <label><input name="callback" type="radio" value="no" id="cbno" checked>
                      No, thanks </label></td>
                    </tr>
                    <tr>
                      <td style="border-top: thin solid #003399; ">New Note:</td>
                      <td style="border-top: thin solid #003399;"><label><input name="note_shared" type="radio" value="1" checked>
                        Shared</label>                        &nbsp; <label><input name="note_shared" type="radio" value="0">
                        Restricted</label><br>
                      <span class="style2">Shared notes are open for all employees, but not to clients. Restricted notes are invisible to users with lower access levels. 255 symbols max.</span></td>
                      <td colspan="4" style="border-top: thin solid #003399;"><textarea name="note_text" cols="65" rows="4" id="note_text"></textarea></td>
                    </tr>
                  </table>
                </form></div>
              <p><br />
              Save button saves notes and address/name changes, but neither accepts nor rejects the record. Reset button cancels form field changes. Press Accept if you speak to the doctor and he/she accepts the offer to be included into our database. You will be redirected to the full form to proceed with the interview. Press Reject if doctor wants to be removed or is unreachable or non-existant.</p>
              <hr>
              <table width="80%"  border="1" cellpadding="2" cellspacing="0">
                <tr>
                  <td><span class="style1">Date/Time</span></td>
                  <td><span class="style1">User</span></td>
                  <td><span class="style1">Notes</span></td>
                </tr>
<?php 
	try {
		if( !isset($db) ) $db = db_clients();
		$sql = "select note_id,date_format(dt,'%c/%e/%y %T') as datetim,n.uid,username,note,shared from notes n join operators u on n.uid = u.uid where year = 1 and res_id = $docid and shared <= $ACCESS order by dt desc";
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
							$_SESSION['delete_note_str'] = "id=$docid&lid=$lid&ck=$very$peekarg&y=$yer&pos=$pos";
							$_SESSION['delete_note'] = $row['note_id'];
				  ?>
				  <a href="delnote.php" title="Delete Note"><img src="images/b_drop.png" width="16" height="16" border="0" align="absbottom" alt="X" title="Delete Note"></a><?php 
						}
						$firstnote = false;
				  	}
				  ?>
				  </td>
                  <td <?php if( $row['uid'] == $UUID ) echo ' class="style3"'; ?>
				  ><?php echo $row['username']; ?>&nbsp;<?php 
				  	if( $row['shared'] ) echo ' <span class="style4" title="Restricted">&reg;</span>'; ?>
				  </td>
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
              <p>&nbsp;</p>
              <?php
			  } // doc
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>