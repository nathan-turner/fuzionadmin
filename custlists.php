<?php
    require("globals.php5");
    require("cookies.php5");
    // $UUID <> 0 if auth
	// Form:
	//		action_0
	//		desc_ID, action_ID, name_ID
	//		desc_new, action_new (0 or 10), name_new
	// YEAR is deprecated. Shadow is eliminated.
	$mesg = '';
	if( $UUID ) {
		$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
		if( !empty($strippost['uid_new']) && is_numeric($strippost['uid_new']) )
			$newuid = $strippost['uid_new'];
		else $newuid = $UUID;
		$newname = $strippost['name_new'];
		$newdesc = $strippost['desc_new'];
		$newsource = $strippost['source_new']; if( !$newsource ) $newsource = 0;
		if($_POST['action_new'] == 10 && isset($newname) ) { // Create new list
			try {
				$newl = new CustList($newname,2005,$newdesc,0,0,$newuid);
				$db = $newl->cdb;
			}
			catch(Exception $e) {
				$mesg = 'Request to create list failed: '.
						$e->getMessage().' ('.$e->getCode().')<br>';
			}
		} // action 10
		elseif($_POST['action_new'] == 11 && isset($newname) ) { // Create new list & copy year from 0
			try {
				$newl = new CustList($newname,2005,$newdesc);
				$db = $newl->cdb;
				$trglid = $newl->id;
				$srclid = $newsource;
			}
			catch(Exception $e) {
				$mesg = 'Request to create list failed: '.
						$e->getMessage().' ('.$e->getCode().')<br>';
			}
		} // action 11
		$resdb = db_career(); //year is deprecated
		if( !isset($db) ) $db = db_clients();
		$opers = array();
		$result = $db->query("select uid,username,access,status from operators order by status desc, username");
		if( $result && $result->num_rows ) 
			while( $oper = $result->fetch_object() )
				if( $oper->access <= $ACCESS ) $opers[$oper->uid] = $oper;
		$result->free();
		$newaccess = $opers[$newuid]->access;
		foreach( $strippost as $key => $value ) {
			if( strpos($key,'ction_') && is_numeric(substr($key,7)) ) {
				$lid = substr($key,7);
				// process clear/save actions here, store copy from/to for later
				if( $lid && $value == 3 ) { // clear
					// custlists: owneruid listid memberuid
					try {
						$result = $db->query("delete custlistsus where owneruid = $newuid and listid = $lid");
						$result = $resdb->query("delete custlistsus where owneruid = $newuid and listid = $lid"); // delete from db and resdb both
						$newlid = $lid;
						for( $i = $lid-1; $i > 9; $i-- ) {
							$result = $db->query("select listid from custlistdesc where listid = $i and uid = $newuid");
							if( !$result || !$result->num_rows ) { $newlid = $i; break; }
							$result->free();
						}
						$result = $db->query("update custlistdesc set description = 'CLEAR', name = 'empty-$newlid', listid = $newlid where uid = $newuid and listid = $lid");
					}
					catch(Exception $e) {
						$mesg = "Request to clear list $lid failed: ".
								$e->getMessage().' ('.$e->getCode().')<br>';
					}
				}
				elseif( $lid && $value == 4 ) { // save
					$newname = $strippost["name_$lid"];
					$newdesc = $strippost["desc_$lid"];
					if( isset($newname) ) {
						$newname = substr(addslashes(trim($strippost["name_$lid"])),0,50);
						if( strlen($newname) == 50 ) $newname{49} = '-';
						$newdesc = substr(addslashes(trim($strippost["desc_$lid"])),0,255);
						if( strlen($newdesc) == 255 ) $newdesc{254} = '-';
						try {
							$result = $db->query("update custlistdesc set description = '$newdesc', name = '$newname' where uid = $newuid and listid = $lid");
						}
						catch(Exception $e) {
							$mesg = "Request to save list $lid failed: ".
								$e->getMessage().' ('.$e->getCode().')<br>';
						}
					}
				}
				elseif( $lid && $value == 6 ) { // share
						try {
							$result = $db->query("update custlistdesc set shared = 0 where uid = $newuid and listid = $lid and acct = 0");
						}
						catch(Exception $e) {
							$mesg = "Request to share list $lid failed: ".
								$e->getMessage().' ('.$e->getCode().')<br>';
						}
				}
				elseif( $lid && $value == 7 ) { // unshare
						try {
							$result = $db->query("update custlistdesc set shared = $newaccess where uid = $newuid and listid = $lid and acct = $ACCT");
						}
						catch(Exception $e) {
							$mesg = "Request to un-share list $lid failed: ".
								$e->getMessage().' ('.$e->getCode().')<br>';
						}
				}
				elseif( $lid && $value == 8 && $newuid != $UUID ) { // borrow
						try {
							$result = $db->query("select * from custlistdesc where uid = $newuid and listid = $lid");
							if( !$result || $result->num_rows == 0 ) throw new Exception('Can not get description to borrow',__LINE__);
							$desc = $result->fetch_object();
							$newl = new CustList($desc->name,2005,$desc->description,0,0);
							$tid = $newl->id;
							$sql = "insert into custlistsus select $UUID,memberuid,$tid from custlistsus where owneruid=$newuid and listid=$lid";
							$result = $resdb->query($sql);
						}
						catch(Exception $e) {
							$mesg = "Request to borrow list $lid failed: ".
								$e->getMessage().' ('.$e->getCode().')<br>';
						}
				}
				elseif( $lid && $value == 2 ) { // target
					$trglid = $lid;
				}
				elseif( $value == 1 ) { // source
					$srclid = $lid;
				}
				elseif( $lid && $value == 5 ) { // delete
					// custlists: owneruid listid memberuid
					try {
						$result = $resdb->query("delete from custlistsus where owneruid = $newuid and listid = $lid"); // delete from db and resdb both
						$result = $db->query("delete from custlistdesc where uid = $newuid and listid = $lid");
					}
					catch(Exception $e) {
						$mesg = "Request to delete list $lid failed: ".
								$e->getMessage().' ('.$e->getCode().')<br>';
					}
				} // elseif
			}		// if action
		}		// foreach
		// do copy lists
		if( isset($srclid,$trglid) && $trglid ) {
			if( $srclid == 0 || $srclid > 128 ) {
				// copy from saved search / year, append to target.
						$sql = "insert into custlistsus select $newuid,memberuid,$trglid from custlistsus where owneruid=$newuid and listid=$srclid";
						$result = $resdb->query($sql);
			}
			else {
				// copy from source, append to target. year must be the same.
						$sql = "insert into custlistsus select $newuid,memberuid,$trglid from custlistsus where owneruid=$newuid and listid=$srclid";
						$result = $resdb->query($sql);
			}
		}

	} // UUID
	$style = new OperPage('Custom Lists',$UUID,'index','custlists');
	$quirc = 'script>';
	$scrip = "<script language=\"javascript\"><!--\nfunction selsave(oid) {\n".
		"	var sel=document.getElementById(\"action_\"+oid);\n".
		"	sel.value = 4;\n	return true;\n}\n//--></$quirc";
	$style->Output($scrip);
	if( $UUID ) {
?>
              <h1>Manage custom lists</h1>
<?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
              <p>This section allows you to mavage saved lists of records - Search results, Assigned lists, etc. Here you can create new list name, clear list, copy one list into another, rename, share, change description, or go to the list contents.</p>
              <form action="custlists.php" method="post" name="editlists">
			  <p>Select User: <select id="uid_new" name="uid_new" size="1">
			  <?php 
			  		foreach($opers as $oper) {
						if( $oper->status )
							echo "<option value='$oper->uid' ".($oper->uid == $newuid?'selected':'').">$oper->username</option>";
						else
							echo "<option value='$oper->uid' ".($oper->uid == $newuid?'selected':'').">* $oper->username</option>";
					}
			  ?>
			  </select> <input type="submit" name="Select" value="Select"></p>
			  <table width="90%" align="left">
                <tbody>
                  <tr valign="top">
                    <th width="14%" align="left">List ID</th>
                    <th width="32%" align="left">Description</th>
<!--                    <td style="border:1px solid;" align="left"># of records</td>-->
                    <th width="18%" align="left">Action</th>
                    <th width="19%" align="left">New name</th>
                  </tr>
                  <tr valign="top">
                    <td align="left">0&nbsp;Automatic</td>
                    <td align="left">
					<a href="results.php?id=0&peek=&y=2005">Results of your last search</a> </td>
                    <td align="left"><select name="action_0" size="1">
                      <option value="0" selected>-none-</option>
                      <option value="1">Copy From</option>
                    </select></td>
                    <td align="left">&nbsp;<br></td>
                  </tr>
<?php
	// get custlistdesc
	try {
		if ( !isset($db) ) $db = db_clients();
		if ( !isset($resdb) ) $resdb = db_career(); //year is deprecated
		$result = $db->query("select * from custlistdesc where uid = $newuid ".
			($UUID == $newuid || $ACCESS == 500?'':" and shared < $ACCESS ").
			($ACCESS < 500?' and listid < 128 ':'')."order by listid");
		for($i=0; $result && $i < $result->num_rows; $i++) {
			$row = $result->fetch_object();
?>
                  <tr valign="top">
                    <td align="left"><?php echo $row->listid; ?>&nbsp;
					<a href="results.php?id=<?php echo $row->listid; ?>&peek=<?php echo $newuid; ?>&y=<?php echo $row->year; ?>"><?php echo stripslashes($row->name); ?></a></td>
                    <td align="left"><textarea name="desc_<?php echo $row->listid; ?>" cols="35" id="desc_<?php echo $row->listid; ?>" onChange="selsave('<?php echo $row->listid; ?>')"><?php echo stripslashes($row->description); ?></textarea>
                      </td>
                    <td align="left"><select name="action_<?php echo $row->listid; ?>" size="1" id="action_<?php echo $row->listid; ?>">
                      <option value="0" selected>-none-</option>
                      <option value="1">Copy: Source</option>
                      <option value="2">Copy: Target</option>
                      <option value="4">Save changes</option>
<?php
					if( $newuid != $UUID ) echo '<option value="8">Borrow</option>';
					if( !$row->shared ) echo '<option value="7">Un-Share</option>';
					else echo '<option value="6">Share</option>';
?>
                      <option value="3">Clear</option>
                      <option value="5">Delete</option>
                                                                                </select>
                      <br></td>
                    <td align="left"><input name="name_<?php echo $row->listid; ?>" type="text" id="name_<?php echo $row->listid; ?>" maxlength="50" value="<?php echo stripslashes($row->name); ?>" onChange="selsave('<?php echo $row->listid; ?>')"></td>
                  </tr>
<?php 
		}
		$result->free();
	}
	catch(Exception $e) {
		echo '<tr><td style="border:1px solid;" align="center" colspan="5">Error: '.
			$e->getMessage().' ('.$e->getCode().')</td></tr>';
	}
?>
                  <tr valign="top">
                    <td align="left">New list </td>
                    <td align="left"><textarea name="desc_new" cols="35" id="desc_new"></textarea></td>
                    <td align="left"><select name="action_new" size="1">
                      <option value="0" selected>-none-</option>
                      <option value="10">Create</option>
                    </select></td>
                    <td align="left"><input name="name_new" type="text" id="name_new" maxlength="50"></td>
                  </tr>
                </tbody>
              </table>
              <br clear="all">
              <input type="submit" name="Submit" value="Submit"> 
              <input type="reset" name="Reset" value="Reset">
              <br>
              </p></form>
              <p>Select Action for the list(s) and press Submit. You can select one list as Copy source and another as Copy target to copy records between lists. You can not copy into automatic lists.<br>
              </p>
<?php	}
		else showLoginForm(); // UUID
	$style->ShowFooter();
?>
