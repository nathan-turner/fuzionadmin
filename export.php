<?php
    require("globals.php5");
	define(PG_SIZE,50);
    require("cookies.php5");
	$mesg = '';
	if( $UUID && $ACCESS >= 200 ) try {
		// do stuff
		$db = db_career();
		if( $_POST['submit'] && $_POST['extype'] ) {
			// extype C (300+) masters 1/0, E exyear1, D (400+) exyear2, L exlist (=lid)
			// filename (optional)
			$filename = stripslashes(trim(str_replace('"',"'",$_POST['filename'])));
			if( empty($filename) ) $filename = 'export-'.time().'.csv';
			if( strpos($filename,'.') === false ) $filename .= '.csv'; // trivial check
			$extype = $_POST['extype'];
			if( $extype === 'C' && $ACCESS >= 300 ) {
				// customers raw data dump
				$sql = 'select `uid`, `email`, `firstname`, `lastname`, `mrmsdr`, `phone`, `fax`, `title`, `company`, `addr1`, `addr2`, `city`, `state`, `zip`, `acct`, `master_acct`, `subaccts`, `exp_date`, `status`, `subscription`, `specs`, `tc_agreed` from clients';
				if( $_POST['masters'] ) $sql .= ' where `master_acct` = 1';
				$res = $db->query($sql);
				if( $res && $res->num_rows ) {
					header('Content-type: text/plain');
					header('Content-Disposition: attachment; filename="'.$filename.'"');
					$firstline = true;
					for( $i=0; $i < $res->num_rows; $i++ ) {
						// export
						if( $firstline ) {
							$firstline = false;
							$row = $res->fetch_assoc();
							$firstElem = true;
							foreach( $row as $key => $value ) {
								if( $firstElem ) $firstElem = false;
								else echo ',';
								echo '"'.stripslashes($key).'"';
							}
							echo "\r\n"; // windows style is always ok
						}
						else $row = $res->fetch_row();
						$firstElem = true;
						foreach( $row as $value ) {
							if( $firstElem ) $firstElem = false;
							else echo ',';
							$value = str_replace("\r\n"," ",$value);
							if( $key == "zip" || $key == "ofzip" ) echo "\"'".stripslashes($value).'"';
							else echo '"'.stripslashes($value).'"';
						}
						echo "\r\n"; // windows style is always ok
					}
					$res->free();
					exit;
				} else throw new Exception('No customers',__LINE__);
			} // C
			/* *******************************************************************  TOTAL REWRITE */
			elseif( $extype === 'E' ) {
				// E exyear1, D (400+) exyear2, L exlist (=lid)
				// export active verified docs
				$extypee = $_POST['extypee']; // (M)ail* or (C)ontact or Full
				$ofaddr = $_POST['ofaddr'];
				$homaddr = ',addr1,`addr2`, city, state, zip ';
				$adrcond = '(addr1 is not null or addr2 is not null) and ';
				if( $ofaddr ) {
					$ofaddr = ',`ofaddr1`, `ofaddr2`, `ofcity`,`ofstate`,`ofzip` ';
					if( $_POST['ofnohome'] ) {
						$homaddr = '';
						$adrcond = '(ofaddr1 is not null or ofaddr2 is not null) and ';
					}
					else $adrcond = '(addr1 is not null or ofaddr1 is not null or addr2 is not null or ofaddr2 is not null) and ';
				} 
				else $ofaddr = '';

				//$resdb = db_career();
				$addsql = "";
				//if( !$_POST['committed'] ) $addsql .= " and interviewing=1 ";
				if( !$_POST['inactives'] ) $addsql .= " and status=1 ";
				if( !$_POST['checkins'] ) $addsql .= " and checkin=1 ";
				if( !$_POST['subscribed'] ) $addsql .= " and campaigns=1 ";
				if( !$_POST['already'] ) $addsql .= " and lastlogdate is null ";
				//if( $_POST['yerr'] && is_numeric($_POST['yerr']) ) $addsql .= " and `year`=".$_POST['yerr'];

				if( ($ACCESS >= 400) && $extypee === 'F' ) 
					$sql = "select `ph_id`,`fname`,`midname`,`lname`,`mddo`, d.addr1, `addr2`, d.city, d.state, d.zip, `ofaddr1`, `ofaddr2`, `ofcity`,`ofstate`,`ofzip`, `homephone`, `cellphone`, `officephone`, `officeext`, `email`, `spec`, `spec_2nd`, `school`, `sch_loc`, `sch_state`, `amg`, `sch_year`, `program`, res_city, `res_state`, `res_year`, `res_spec`, `fellowship`,`fel_city`, `fel_state`, `fel_spec`, `fel_year`, `avail_date`, `licensed`, `visa_status`, case `citizen` when 1 then 'US citizen' when 2 then 'perm.res.' end as us_citizen, `birth_state`, `bcbe`, `pref_region`, `pref_states`, `pref_stopen`, `pref_city`, `pref_commu2` as preferred_community, `pref_practice`, `marital_status`, `children`, `spouse`, `spouse_prof`, `spouse_spec`, `spouse_state`,`languages`, `hobbies`, `ct_pref` as contact_preference, `reason_leaving`, `other_pref` from physicians d join cont_pref on contact_pref = ct_id where inactive=0 and dup=0";
				elseif( $extypee === 'C' ) // Full Contact Info
					$sql = "select ph_id, spec, fname, midname, lname, mddo$homaddr$ofaddr, homephone, cellphone, officephone, officeext, CASE WHEN email_bounces=1 THEN null ELSE email END as email_addr from physicians where inactive=0 and dup=0 $addsql";
				else // Mail Merge
					$sql = "select ph_id, spec, fname, midname, lname, mddo$homaddr$ofaddr from physicians where $adrcond inactive=0 and dup=0  $addsql";
				
				$res = $db->query($sql);

				if( $res && $res->num_rows ) {
					header('Content-type: text/plain');
					header('Content-Disposition: attachment; filename="'.$filename.'"');
					$firstline = true;
					for( $i=0; $i < $res->num_rows; $i++ ) {
						// export
						if( $firstline ) {
							$firstline = false;
							$row = $res->fetch_assoc();
							$firstElem = true;
							foreach( $row as $key => $value ) {
								if( $firstElem ) $firstElem = false;
								else echo ',';
								echo '"'.stripslashes($key).'"';
							}
							echo "\r\n"; // windows style is always ok
						}
						else $row = $res->fetch_row();
						$firstElem = true;
						foreach( $row as $value ) {
							if( $firstElem ) $firstElem = false;
							else echo ',';
							$value = str_replace("\r\n"," ",$value);
							//if( $key == "zip" || $key == "ofzip" ) echo "\"'".stripslashes($value).'"';
							//else 
								echo '"'.stripslashes($value).'"';
						}
						echo "\r\n"; // windows style is always ok
					}
					$res->free();
					exit;
				} else throw new Exception('No available records',__LINE__); // DEBUG?"$db->error : $sql":
			} // E
			/* ********************************************************* */
			elseif( $extype === 'L' ) {
				// L exlist (=lid)
				// export docs from custom list
				$lid = $_POST['exlist'];
				if( empty($lid) || !is_numeric($lid) ) throw new Exception('No list selected',__LINE__);
				//$lst = $db->query("select `year` from custlistdesc where uid=$UUID and listid=$lid and `year`!=1");
				//list($yer) = $lst->fetch_row();
				//$lst->free();
				//$resdb = db_resident(2007);
				$sql = 'select `ph_id`,`fname`,`midname`,`lname`,`mddo`, `addr1`, `addr2`, `city`, `state`, `zip`, `ofaddr1`, `ofaddr2`, `ofcity`,`ofstate`,`ofzip`, `homephone`, `cellphone`, `officephone`, `officeext`, `email`,`spec`, `spec_2nd`, `school`, `sch_loc`, `sch_state`, `amg`, `sch_year`, `program`, `res_city`, `res_state`, `res_spec`, `res_year`, `fellowship`,`fel_city`, `fel_state`, `fel_spec`, `fel_year`, `program_2`, `res2_city`, `res2_state`, `res2_spec`, `res2_year`, `fellow_2`,`fel2_city`, `fel2_state`, `fel2_spec`, `fel2_year`, `avail_date`, `licensed`, `visa_status`, case `citizen` when 1 then \'US citizen\' when 2 then \'perm.res.\' end as us_citizen, `birth_state`, `bcbe`, `pref_region`, `pref_states`, `pref_stopen`, `pref_city`, `pref_commu2` as preferred_community, `pref_practice`, `marital_status`, `children`, `spouse`, `spouse_prof`, `spouse_spec`, `spouse_state`,`languages`, `hobbies`, `ct_pref` as contact_preference, `reason_leaving`, `other_pref`, `newsletter`,`notifications`,`campaigns` from physicians join cont_pref on contact_pref = ct_id join custlistsus on (memberuid = ph_id) where '.
					"owneruid=$UUID and listid=$lid";
				$res = $db->query($sql);
				if( $res && $res->num_rows ) {
					header('Content-type: text/plain');
					header('Content-Disposition: attachment; filename="'.$filename.'"');
					$firstline = true;
					for( $i=0; $i < $res->num_rows; $i++ ) {
						// export
						if( $firstline ) {
							$firstline = false;
							$row = $res->fetch_assoc();
							$firstElem = true;
							foreach( $row as $key => $value ) {
								if( $firstElem ) $firstElem = false;
								else echo ',';
								echo '"'.stripslashes($key).'"';
							}
							echo "\r\n"; // windows style is always ok
						}
						else $row = $res->fetch_row();
						$firstElem = true;
						foreach( $row as $value ) {
							if( $firstElem ) $firstElem = false;
							else echo ',';
							$value = str_replace("\r\n"," ",$value);
							if( $key == "zip" || $key == "ofzip" ) echo "\"'".stripslashes($value).'"';
							else echo '"'.stripslashes($value).'"';
						}
						echo "\r\n"; // windows style is always ok
					}
					exit;
				} else throw new Exception('No available records',__LINE__); // DEBUG?"$db->error : $sql":
			} // L
			elseif( $extype === 'V'  && $ACCESS >=400 ) {
				// V committed inactives (not)checkins

				//$resdb = db_career();
				$addsql = "";
				if( !$_POST['inactives'] ) $addsql .= " and status=1 ";
				if( !$_POST['checkins'] ) $addsql .= " and checkin=1 ";
				if( !$_POST['already'] ) $addsql .= " and lastlogdate is null ";
				$sql = "select `ph_id`,`fname`,`lname`,`email` from physicians where `email` is not null and inactive=0 and email_bounces=0 and newsletter=1 $addsql";
				$res = $db->query($sql);
				if( $res && $res->num_rows ) {
					header('Content-type: text/plain');
					header('Content-Disposition: attachment; filename="'.$filename.'"');
					//$firstline = true;
					for( $i=0; $i < $res->num_rows; $i++ ) {
						// export
						$row = $res->fetch_object();
						$eml = $row->email;
						$newt = time();
						$hashlink = 'http://physiciancareer.com/login.php?i='.$row->ph_id
						.'&d='.$newt.'&m='. sha1($row->ph_id.$row->fname.$row->lname.$newt.'Please let me in, I got this email - this is the secret code!');
						echo stripslashes('"","","'.$row->fname.'","' .$row->lname. '","'.$hashlink.'","","","","","","","","","' .$eml. '","","","","","","","","",""' ."\r\n");
					}
					exit;
				} else throw new Exception('No available records',__LINE__); // DEBUG?"$db->error : $sql":
			} // V
			elseif( $extype === 'W'  && $ACCESS >=400 ) {
				// W committed inactives (not)checkins

				//$resdb = db_career();
				$addsql = "";
				if( !$_POST['inactives'] ) $addsql .= " and status=1 ";
				if( !$_POST['checkins'] ) $addsql .= " and checkin=1 ";
				if( !$_POST['already'] ) $addsql .= " and lastlogdate is null ";
				$sql = "select `fname`,`lname`,`email` from physicians where `email` is not null and inactive=0 and email_bounces=0 and newsletter=1 $addsql";
				$res = $db->query($sql);
				if( $res && $res->num_rows ) {
					header('Content-type: text/plain');
					header('Content-Disposition: attachment; filename="'.$filename.'"');
					//$firstline = true;
					for( $i=0; $i < $res->num_rows; $i++ ) {
						// export
						$row = $res->fetch_object();
						$eml = $row->email;
						$newt = time();
						echo stripslashes('"'.$row->lname.', ' .$row->fname. '" <'.$eml.'>'."\r\n");
					}
					exit;
				} else throw new Exception('No available records',__LINE__); // DEBUG?"$db->error : $sql":
			} // W
		}
		else {
			$sql = "select listid,name,description from custlistdesc where uid = $UUID and `year`=2005 order by name";
			$lists = $db->query($sql);
		}
	}
	catch(Exception $e) {
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
	}
	$style = new OperPage('Data Export',$UUID,'reports','export');
	$scrip = "<script language=\"JavaScript\" type=\"text/JavaScript\"><!--\r\nfunction selRadio(elem) {\r\n".
			 "	document.getElementById(elem).checked = true;\r\n	return true;\r\n}\r\n// -->\r\n</script>\r\n";
	$style->Output($scrip);

	if( $UUID ) {
			if( $ACCESS < 200 ) echo '<h1>Access Denied</h1>';
			else {
?>
              <h1>Data Export</h1>
<?php 
			if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
              <p>Export  Customer List, Export list for customers, and the whole Residents DB into a CSV file, depending of the access level.<br>
                Export residents from your custom lists (in the form of the Export List, choice two below)..</p>
			  <div id="formdiv">
              <form action="export.php" method="post" name="formex" id="formex">
                <p>Select Export type: 
                  <br>
<?php if( $ACCESS >= 300 ) { // acct manager
?>
                  <input name="extype" type="radio" id="excust" value="C">
Customers 
<input name="masters" type="checkbox" id="masters" value="1" checked onClick="selRadio('excust')"> 
only master accounts<br>
<?php }
?>
<input name="extype" type="radio" id="exsave" value="L"> 
Custom/Saved list: 
<select name="exlist" id="exlist"  onClick="selRadio('exsave')">
<?php 
	$clo = '';
	for( $i=0; $lists && $i < $lists->num_rows; $i++ ) {
		list($lid,$name,$desc) = $lists->fetch_row();
		$desc = substr($desc,0,40);
		$clo .= "<option value='$lid'>$name ($desc)</option>";
		echo "<option value='$lid'>$name ($desc)</option>";
	}
?>
</select>
<br>
<?php if( $ACCESS >= 400 ) { 
?>
<input name="extype" type="radio" id="exsave2" value="V"> 
List for PC Nudge:  (+options, see below)
<br> 
<input name="extype" type="radio" id="exsave2" value="W"> 
List for PC Newsletter: (+options, see below)
<br> 
<?php }
    if( $ACCESS >= 200 ) { // cust serv
?>
<input name="extype" type="radio" id="exexp" value="E"> 
List to send to a customer: (+options, see below)
<br>
      Export type: 
      <label><input name="extypee" type="radio" value="M" checked>
      Mail Merge</label> &nbsp; 
      <label><input name="extypee" type="radio" value="C">
      Contact Information</label>
<?php if( $ACCESS >= 400 ) { ?>
	  &nbsp; 
      <label><input name="extypee" type="radio" value="F">
      Full File</label>
<?php } ?>
      <br>
      Contact Info: 
      <input name="ofaddr" type="checkbox" id="ofaddr" value="1">
      Include Office Address 
      <input name="ofnohome" type="checkbox" id="ofnohome" value="1">
      Exclude Home Address
      <br>
      <br>
Options: &nbsp; &nbsp; &nbsp; 
<input name="inactives" type="checkbox" value="1"> +Inactive; <input name="checkins" type="checkbox" checked value="1"> +Private; <input name="already" type="checkbox" checked value="1"> 
+Logged-in; 
<input name="subscribed" type="checkbox"  value="1"> +Un-Subscribed
<br>
<br>
<?php } // cust serv
?>
Export File Name (optional): 
<input name="filename" type="text" id="filename">
.CSV&nbsp;&nbsp;
<input type="submit" name="submit" value="Export">
                </p>
              </form>
		</div>
		<p>Import HMA Jobs: <a href="import-hma.php">Click here</a>.</p>
		<p>Import HPP Jobs: <a href="import-hpp.php">Click here</a>.</p>
		<p>Import Providence Jobs: <a href="import-providence.php">Click here</a>.</p>
              <?php
			} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
