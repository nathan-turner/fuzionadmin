<?php
    require("globals.php5");
	require("cookies.php5");
	// $UUID <> 0 if auth
	$mesg = '';
	if( isset($_POST['submit']) ) try {
		$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
		extract($strippost,EXTR_SKIP);
		$wher = '';
		//if( empty($allyears) && empty($year) ) throw new Exception('No year selected',__LINE__);
		$verboz = '';
		//else $verboz = $year==2006?'Practicing; ':"Grad. $year; ";
		if( $ACCESS != 500 ) unset($custwher,$custjoin,$custexcl);
		if( $res_id && is_numeric($res_id) ) { // res_id search is exclusive
			$wher = "ph_id = $res_id";
			$verboz = "ID is $res_id; ";
		}
		elseif( $custexcl && $custwher ) {
			$wher = ($_POST['custwher']);
			$verboz = ($custwher);
		}
		else {
			// lname, fname, spec, state, city, zip, phone, email, program, program_2, res_year, res2_year, res2_spec
			if( $fname ) {
			    $q = addslashes($fname);
				$wher = "fname like '$q%'";
				$verboz .= "First name $fname; "; }
			if( $lname ) {
			    $q = addslashes($lname);
				$wher .= ($wher?' and ':'')."lname like '$q%'";
				$verboz .= "Last name $lname; "; }
			if( $midname ) {
			    $q = addslashes($midname);
				$wher .= ($wher?' and ':'')."midname like '$q%'";
				$verboz .= "Middle name $midname; "; }
			if( $mddo ) {
			    $q = addslashes($mddo);
				$wher .= ($wher?' and ':'')."mddo='$q'";
				$verboz .= $mddo.'s; '; }
			if( $dup ) {
				$wher .= ($wher?' and ':'')."status=0";
				$verboz .= 'Inactive; '; }
			else $wher .= ($wher?' and ':'')."status=1";
			if( $commited ) {
				$wher .= ($wher?' and ':'')."checkin=1";
				$verboz .= 'Public; '; }
			if( $nocheckin ) {
				$wher .= ($wher?' and ':'')."checkin=0";
				$verboz .= 'Private; '; }
			if( $inactive ) {
				$wher .= ($wher?' and ':'')."inactive=1";
				$verboz .= 'Suspended; '; }
			else $wher .= ($wher?' and ':'')."inactive=0";
			if( $addr1 && strlen($addr1) >=3 ) {
			    $q = addslashes($addr1);
				$wher .= ($wher?' and ':'')."(addr1 like '%$q%' or addr2 like '%$q%' or ofaddr1 like '%$q%' or ofaddr2 like '%$q%')";
				$verboz .= "Street address like $addr1; "; }
			else unset($addr1);
			if( $city ) {
			    $q = addslashes($city);
				$wher .= ($wher?' and ':'')."(city like '$q%' or ofcity like '$q%')";
				$verboz .= "City is $city; "; }
			if( isset($zip) && strlen($zip)>=3 ) {
			    $q = addslashes($zip);
				$wher .= ($wher?' and ':'')."(zip like '$q%' or ofzip like '$q%')";
				$verboz .= "ZIP is $zip; "; }
			else unset($zip);
			if( $state && $state != '--' ) {
				$wher .= ($wher?' and ':'')."(state = '$state' or ofstate = '$state')";
				$verboz .= "State is $state; "; }
			elseif( $no_state ) {
				$wher .= ($wher?' and ':'')."state = '--'";
				$verboz .= "No State known; "; }
			$phone2 = preg_replace('/[^0-9]/','',$phone);
			if( isset($phone2) && strlen($phone2)>=3 ) {
				$wher .= ($wher?' and ':'').
			  "(homephone like '$phone2%' or cellphone like '$phone2%' or officephone like '$phone2%')";
				$verboz .= "Phone like $phone2; "; }
			else { unset($phone);
				if( $phoneavail == 1 ) {
					$wher .= ($wher?' and ':'').
				  "(homephone is not null or cellphone is not null or officephone is not null)";
					$verboz .= "Phone is avail.; "; }
				elseif( $phoneavail == 3 ) {
					$wher .= ($wher?' and ':'').
				  "(homephone is not null or cellphone is not null)";
					$verboz .= "Home Phones; "; }
				elseif( $phoneavail == 2 ) {
					$wher .= ($wher?' and ':'').
				  "(homephone is null and cellphone is null and officephone is null)";
					$verboz .= "No Phone is avail.; "; }
			}
			if( $email ) {
			    $q = addslashes($email);
				$wher .= ($wher?' and ':'')."(email like '$q%' or email like '%$q')";
				$verboz .= "Email like $email; "; }
			if( $email_bounces == 1 ) {
				$wher .= ($wher?' and ':'')."email_bounces=1";
				$verboz .= "Email bounces; "; }
			if( $email_bounces == 2 ) {
				$wher .= ($wher?' and ':'')."email_bounces=0";
				$verboz .= "Email not bounces; "; }

			if( $spec && $spec != '---' ) {
				$wher .= ($wher?' and ':'')."spec='$spec'";
				$verboz .= "Spec. is $spec; "; }
			if( $spec_add && $spec_add != '---' ) {
				$wher .= ($wher?' and ':'')."(fel_spec = '$spec_add' or res_spec = '$spec_add' or fel2_spec = '$spec_add' or res2_spec = '$spec_add')";
				$verboz .= "2nd spec. is $spec_add; "; }
			if( $status && is_numeric($status) ) {
				$wher .= ($wher?' and ':'')."pstatus=$status";
				$verboz .= "Status is $status; "; }
			if( $spec_2nd ) {
			    $q = addslashes($spec_2nd);
				$wher .= ($wher?' and ':'')."spec_2nd like '%$q%'";
				$verboz .= "Sub-spec. is $spec_2nd; "; }
			if( $school && strlen($school) >=3 ) {
			    $q = addslashes($school);
				$wher .= ($wher?' and ':'')."school like '%$q%'";
				$verboz .= "School is $school; "; }
			else unset($school);
			if( $sch_loc ) {
			    $q = addslashes($sch_loc);
				$wher .= ($wher?' and ':'')."sch_loc like '$q%'";
				$verboz .= "School is in $sch_loc; "; }
			if( $sch_state && $sch_state != '--' ) {
				$wher .= ($wher?' and ':'')."sch_state = '$sch_state'";
				$verboz .= "School is in $sch_state; "; }
			if( $fel_state && $fel_state != '--' ) {
				$wher .= ($wher?' and ':'')."(res_state = '$fel_state' or res2_state = '$fel_state' or fel_state = '$fel_state' or fel2_state = '$fel_state')";
				$verboz .= "Res/Fel.prog. is in $fel_state; "; }
			if( $sch_year && is_numeric($sch_year) ) {
				$wher .= ($wher?' and ':'')."sch_year>=$sch_year";
				$verboz .= "Med.school grad. $sch_year; "; }
			elseif( $nosch_year ) {
				$wher .= ($wher?' and ':'')."sch_year is null";
				$verboz .= "NO Med.school year; "; }
			if( $res_year && is_numeric($res_year) ) {
				$wher .= ($wher?' and ':'')."(res_year=$res_year or res2_year=$res_year)";
				$verboz .= "Residency grad. $res_year; "; }
			elseif( $nores_year ) {
				$wher .= ($wher?' and ':'')."res_year is null";
				$verboz .= "NO residency year; "; }
			if( $fel_year && is_numeric($fel_year) ) {
				$wher .= ($wher?' and ':'')."(fel_year=$fel_year or fel2_year=$fel_year)";
				$verboz .= "Fellowship grad. $fel_year; "; }
			elseif( $nofel_year ) {
				$wher .= ($wher?' and ':'')."fel_year is null";
				$verboz .= "NO Fellowship year; "; }
			if( $amg == 1 ) {
				$wher .= ($wher?' and ':'')."amg=1";
				$verboz .= "AMGs; "; }
			if( $amg == 2 ) {
				$wher .= ($wher?' and ':'')."amg=0";
				$verboz .= "IMGs; "; }
			if( $program && strlen($program) >=3  ) {
			    $q = addslashes($program);
				$wher .= ($wher?' and ':'')."(program like '%$q%' or program_2 like '%$q%')";
				$verboz .= "Res. program is $program; "; }
			if( $fellow && strlen($fellow) >=3  ) {
			    $q = addslashes($fellow);
				$wher .= ($wher?' and ':'')."(fellowship like '%$q%' or fellow_2 like '%$q%')";
				$verboz .= "Fellowship is $fellow; "; }
			if( $avail_date && $avail_date > ((date('Y')-1).'-01-01') ) {
				$wher .= ($wher?' and ':'')."avail_date <= '$avail_date'";
				$verboz .= "avail. on or before $avail_date; "; }
			if( $bcbe === 'BC' ) {
				$wher .= ($wher?' and ':'')."bcbe='bc'";
				$verboz .= "BCs; "; }
			if( $bcbe === 'BE' ) {
				$wher .= ($wher?' and ':'')."bcbe='be'";
				$verboz .= "BEs; "; }
			if( $visa_status ) {
			    $q = addslashes($visa_status);
				$wher .= ($wher?' and ':'')."visa_status like '$q%'";
				$verboz .= "Visa $visa_status; "; }
			if( $citizen == 1 ) {
				$wher .= ($wher?' and ':'')."citizen=1";
				$verboz .= "US citizens; "; }
			if( $citizen == 2 ) {
				$wher .= ($wher?' and ':'')."citizen=2";
				$verboz .= "US residents; "; }
			if( $citizen == 3 ) {
				$wher .= ($wher?' and ':'')."citizen in (0,4)";
				$verboz .= "US non-residents; "; }
			if( $birth_state && $birth_state != '--' ) {
				$wher .= ($wher?' and ':'')."birth_state = '$birth_state'";
				$verboz .= "Home state is $birth_state; "; }
			if( $licensed && $licensed != '--' ) {
				$wher .= ($wher?' and ':'')."licensed like '%$licensed%'";
				$verboz .= "Licensed in $licensed; "; }
			if( $marital_status === 'S' ) {
				$wher .= ($wher?' and ':'')."marital_status='s'";
				$verboz .= "Single; "; }
			if( $marital_status === 'M' ) {
				$wher .= ($wher?' and ':'')."marital_status='m'";
				$verboz .= "Married; "; }
			if( $spouse ) {
			    $q = addslashes($spouse);
				$wher .= ($wher?' and ':'')."spouse like '%$q%'";
				$verboz .= "Spouse $spouse; "; }
			if( $spouse_prof ) {
			    $q = addslashes($spouse_prof);
				$wher .= ($wher?' and ':'')."spouse_prof like '%$q%'";
				$verboz .= "Spouse is $spouse_prof; "; }
			if( $pref_city ) {
			    $q = addslashes($pref_city);
				$wher .= ($wher?' and ':'')."pref_city like '%$q%'";
				$verboz .= ($geo?$andor:'')."pref.city is $pref_city"; }
			$pref_commu = 0; $vpref_commu = '';
			if( $pref_commu_1 === 'S' ) { $pref_commu += 1; $vpref_commu = 'small'; }
			if( $pref_commu_2 === 'C' ) { $pref_commu += 2; $vpref_commu .= ($pref_commu?',':'').'midsize'; }
			if( $pref_commu_3 === 'M' ) { $pref_commu += 4; $vpref_commu .= ($pref_commu?',':'').'metro'; }
			if( $pref_commu ) {
				$wher .= ($wher?' and ':'')."pref_commu2 & $pref_commu != 0";
				$verboz .= "Community size: $vpref_commu; "; }
			if( $pref_practice === 'SSG' ) {
				$wher .= ($wher?' and ':'')."pref_practice like '%SSG%'";
				$verboz .= "Pref.practice: $pref_practice; "; }
			if( $pref_practice === 'MSG' ) {
				$wher .= ($wher?' and ':'')."pref_practice like '%MSG%'";
				$verboz .= "Pref.practice: $pref_practice; "; }
			if( $pref_practice === 'Solo' ) {
				$wher .= ($wher?' and ':'')."pref_practice like '%Solo%'";
				$verboz .= "Pref.practice: $pref_practice; "; }
			if( $pref_practice === 'Acad' ) {
				$wher .= ($wher?' and ':'')."pref_practice like '%Acad%'";
				$verboz .= "Pref.practice: Academics; "; }
			if( $pref_practice === 'Hosp' ) {
				$wher .= ($wher?' and ':'')."pref_practice like '%Hosp%'";
				$verboz .= "Pref.practice: Hospital; "; }
			if( $pref_practice === 'Pub' ) {
				$wher .= ($wher?' and ':'')."pref_practice like '%Pub%'";
				$verboz .= "Pref.practice: Pub.Health; "; }
			if( $pref_practice === 'Rural' ) {
				$wher .= ($wher?' and ':'')."pref_practice like '%Rural%'";
				$verboz .= "Pref.practice: $pref_practice; "; }
			if( $pref_practice === 'Locum' ) {
				$wher .= ($wher?' and ':'')."pref_practice like '%Locum%'";
				$verboz .= "Pref.practice: $pref_practice; "; }
			if( $pref_practice === 'ER' ) {
				$wher .= ($wher?' and ':'')."pref_practice like '%ER%'";
				$verboz .= "Pref.practice: $pref_practice; "; }
			if( $pref_states && $pref_states != '--' ) {
				$wher .= ($wher?' and ':'')."FIND_IN_SET('$pref_states',pref_states)>0";
				$verboz .= "Pref.state: $pref_states; "; }
			elseif( $pref_stopen ) {
				$wher .= ($wher?' and ':'')."pref_stopen=1";
				$verboz .= "Pref.states: OPEN; "; }
			if( $pref_region ) {
				$wher .= ($wher?' and ':'')."FIND_IN_SET('$pref_region',pref_region)>0";
				$verboz .= "Pref.regions selected; "; }
			elseif( $pref_regopen ) {
				$wher .= ($wher?' and ':'')."FIND_IN_SET('0',pref_region)>0";
				$verboz .= "Pref.regions: OPEN; "; }
			if( $languages && strlen($languages) >=3 ) {
			    $q = addslashes($languages);
				$wher .= ($wher?' and ':'')."languages like '%$q%'";
				$verboz .= "Languages: $languages; "; }
			if( $interv == 0 ) {
				$wher .= ($wher?' and ':'')."pending=0";
				$verboz .= "Verified; "; }
			if( $interv == 1 ) {
				$wher .= ($wher?' and ':'')."pending=1";
				$verboz .= "Interview completed; "; }
			if( $interv == 2 ) {
				$wher .= ($wher?' and ':'')."pending=2";
				$verboz .= "Interview NOT completed; "; }
			if( $contact_pref && is_numeric($contact_pref) ) $wher .= ($wher?' and ':'')."contact_pref=$contact_pref";
			// wow, it was a lot
			if( $custwher ) {
				$wher .= ($wher?' and ':'').($_POST['custwher']);
				$verboz .= ($custwher);
			}
			//if( DEBUG ) $mesg="All Right, the criteria is: $wher";
		}
		// allyears, year
		$totres = SearchRes(NULL,$wher,$custjoin);
		if( !$totres ) throw new Exception(DEBUG?"Nothing where $wher":'Nothing Found!',0);
		else setcookie('resy',2005,time()+3600*24*7);
		if( $totres == 1 ) {
			$resdb = db_career();
			$result= $resdb->query("select memberuid from custlistsus where owneruid=$UUID and listid=0");
			if( !$result ) throw new Exception('Can not fetch record number!',__LINE__);
			list($docid) = $result->fetch_row();
			$redir = "showdocpc.php?lid=0&pos=0&id=$docid";
		}
		else $redir = "results.php?id=0";
		if( $verboz ) $_SESSION['verboz'] = $verboz; // lid 0 verbose descr
		$okmesg = "$totres results found. One monent, please. You will be redirected <a href='$redir'>here</a>.";
	}
	catch(Exception $e) {
		$mesg = 'Search failed: '.$e->getMessage().' ('.$e->getCode().')<br>';
	}
	if( !isset($resdb) ) $resdb = db_career();
	
	$style = new OperPage('Advanced Search',$UUID,'residents','ressearch',($redir?"2; URL=$redir":''));
	///// JavaScriplet below
	$scrip = <<<TryMe
var subwind;

function showregions() {
	subwind = window.open("regions.php",
			"regions","menubar=0,toolbar=0,width=450,resizable=0,location=0,height=400,scrollbars=yes");
	setTimeout("subwind.focus()",60);
}

TryMe;
	$scrip2 = "<script language=\"JavaScript\" type=\"text/JavaScript\"><!--\n".$scrip.
		"// -->\n</script>\n<script type=\"text/javascript\" src=\"calendarDateInput.js\"></script>\n";
	$style->Output($scrip2);
	
?> 
<?php	if( $UUID ) {
?>
              <h1>Advanced search form</h1>
              <p>Basic Search form is available <a href="pcsearch.php">here</a>.</p>
              <?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
	if( $okmesg ) echo "<p id='warning_msg'>$okmesg</p>";?>
<div id="formdiv">
			  <form name="form1" method="post" action="ressearch.php">

                    <table style="width:100%"  border="0">
                    <tr>
                      <td>ID#: </td>
                      <td colspan="2" class="tdborder3399" bgcolor="#CCCCCC">
                        <input name="res_id" type="text" id="res_id" size="15" maxlength="10">
                       <input name="submit" type="submit" id="submit4" value="Search"></td>
                      <td>&nbsp;</td>
                      <td>&nbsp;
                      </td>
                      <td>&nbsp;</td>
                      </tr>
                      <tr> 
                        <td>First Name:</td>
                        <td><input name="fname" type="text" id="fname" value="<?php echo $fname; ?>" maxlength="50"></td>
                        <td>Middle:</td>
                        <td><input name="midname" type="text" id="midname" value="<?php echo $midname; ?>" maxlength="30"></td>
                        <td>Last name:</td>
                        <td><input name="lname" type="text" id="lname" value="<?php echo $lname; ?>" maxlength="50"></td>
                      </tr>
                      <tr> 
                        <td>Degree:</td>
                        <td><input name="mddo" type="text" id="mddo" value="<?php echo $mddo; ?>" size="10" maxlength="4">
                          MD/DO</td>
                        <td colspan="2"><label>
                          <input name="interv" type="radio" value="2" 
					     <?php echo $interv==2? 'checked':''; ?> >
                          Not Interviewed</label>  
						<label>
                          <input name="interv" type="radio" value="1" 
					     <?php echo $interv==1? 'checked':''; ?> >
                          Interviewed</label> <label>
                          <input name="interv" type="radio" value="0" 
					     <?php echo $interv==0? 'checked':''; ?> >
                          Verified</label></td>
                        <td colspan="2"><label>
                          <input name="dup" type="checkbox" id="dup" value="1" 
					     <?php echo $dup? 'checked':''; ?> >
                          Inactive</label> <label>
                          <input name="commited" type="checkbox" id="commited" value="1" 
					     <?php echo $commited? 'checked':''; ?> >
                          Public</label> <br>
						<label>
                          <input name="inactive" type="checkbox" id="inactive" value="1" 
					     <?php echo $inactive? 'checked':''; ?> >
                          Suspended</label>
						  <label>
                          <input name="nocheckin" type="checkbox" id="nocheckin" value="1" 
					     <?php echo $nocheckin? 'checked':''; ?> >
                          Private</label></td>
                      </tr>
                      <tr> 
                        <td>Address*:</td>
                        <td colspan="5"><input name="addr1" type="text" id="addr1" value="<?php echo $addr1; ?>" size="50" maxlength="100">                          </td>
                      </tr>
                      <tr> 
                        <td>City:</td>
                        <td><input name="city" type="text" id="city" value="<?php echo $city; ?>" maxlength="30"></td>
                        <td>State:</td>
                        <td><?php echo showStateList($resdb,$state); ?><br>
							<label><input name="no_state" type="checkbox" id="no_state" value="1">
                       	No State Info</label></td>
                        <td>Zip*:</td>
                        <td><input name="zip" type="text" id="zip" value="<?php echo $zip; ?>" maxlength="10" size="12" ></td>
                      </tr>
                      <tr> 
                        <td>Phone*:</td>
                        <td><input name="phone" type="text" id="phone" value="<?php echo $phone; ?>" size="16" maxlength="16"></td>
                        <td>&nbsp;</td>
                        <td colspan="3"><label><input name="phoneavail" type="radio" value="1">
                          With Any Phone</label> 
						  <label><input name="phoneavail" type="radio" value="3">
                          Home Phone</label>
                          <!--br-->
			 <label><input name="phoneavail" type="radio" value="2">
                          No Phone is known</label> </td>
                      </tr>
                      <tr> 
                        <td>Email: </td>
                        <td colspan="2"><input name="email" type="text" id="email" value="<?php echo $email; ?>" size="30" maxlength="128">                          </td>
                        <td colspan="2"><label><input name="email_bounces" type="radio" id="email_bounces" value="1">
                          Email Bounces</label>
                          <!--br-->
<label><input name="email_bounces" type="radio" id="email_bounces" value="2">
                          Email is Good</label>
                        </td>
                        <td align="right"><input name="submit" type="submit" id="submit1" value="Search"></td>
                      </tr>
                    </table>
<hr />
                  <table style="width:100%" border="0">
                    <tr> 
                      <td>Main specialty:</td>
                      <td colspan="3"><?php echo showSpecList($resdb,$spec); ?></td>
                      <td>Status:</td>
                      <td><select name="status" id="status">
                        <option value="0" <?php echo $status?'':'selected'; ?>>&nbsp;</option>
                        <option value="1" <?php echo $status==1?'selected':''; ?>>Resident</option>
                        <option value="2" <?php echo $status==2?'selected':''; ?>>Fellow</option>
                        <!--<option value="4" <?php echo $status==4?'selected':''; ?>>Future Fellow</option>-->
                        <option value="3" <?php echo $status==3?'selected':''; ?>>In Practice</option>
                      </select></td>
                    </tr>
                    <tr>
                      <td>2<sup>nd</sup> specialty:</td>
                      <td colspan="3"><?php echo showSpecList($resdb,$spec_add,'spec_add'); ?></td>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                    </tr>
                    <tr> 
                      <td>Specialty Interest:</td>
                      <td colspan="2"><input name="spec_2nd" type="text" id="spec_2nd" value="<?php echo $spec_2nd; ?>" size="35" maxlength="50" title="Secondary specialty or subspecialty"></td>
                      <td colspan="3">Med School*: 
                        <input name="school" type="text" id="school" value="<?php echo $school; ?>" size="45" maxlength="100"> 
                      </td>
                    </tr>
                    <tr> 
                      <td>Sch. Location:</td>
                      <td><input name="sch_loc" type="text" id="sch_loc" value="<?php echo $sch_loc; ?>" maxlength="50">                        </td>
                      <td>Sch.State:</td>
                      <td><?php echo showStateList($resdb,$sch_state,'sch_state'); ?></td>
                      <td>Sch.Year later than:</td>
                      <td><input name="sch_year" type="text" id="sch_year" value="<?php echo $sch_year; ?>" size="10" maxlength="4">
                        <br />
                      <label>year is blank:<input name="nosch_year" type="checkbox" id="nosch_year" value="1" <?php echo $nosch_year?'checked':''; ?>></label> </td>
                    </tr>
                    <tr>
                      <td>School is: </td>
                      <td><label><input name="amg" type="radio" value="1">
USA</label> 
  <label><input name="amg" type="radio" value="2">
  Foreign</label></td>
                      <td>Res program*:</td>
                      <td><input name="program" type="text" id="program" value="<?php echo $program; ?>" maxlength="100"></td>
                      <td colspan="2">Fellowship*: 
                        <input name="fellow" type="text" id="fellow" value="<?php echo $fellow; ?>" maxlength="100"></td>
                    </tr>
                    <tr>
                      <td>Res.Year:</td>
                      <td><input name="res_year" type="text" id="res_year" value="<?php echo $res_year; ?>" size="10" maxlength="4">
                      <br>  
                      <label>year is blank:<input name="nores_year" type="checkbox" id="nores_year" value="1" <?php echo $nores_year?'checked':''; ?>></label></td>
                      <td>Res/Fel.State:</td>
                      <td><?php echo showStateList($resdb,$fel_state,'fel_state'); ?></td>
                      <td>Fel. Year:</td>
                      <td><input name="fel_year" type="text" id="fel_year" value="<?php echo $fel_year; ?>" size="10" maxlength="4">
                        <br />
                      <label>year is blank:<input name="nofel_year" type="checkbox" id="nofel_year" value="1" <?php echo $nofel_year?'checked':''; ?>></label></td>
                    </tr>
                    <tr> 
                      <td>Available on or before:</td>
                      <td title="Date in the past years means 'No Criteria Selected'"><script language="javascript">DateInput('avail_date', false, 'YYYY-MM-DD', '<?php echo $avail_date?$avail_date:'2000-01-01'; ?>');</script></td>
                      <td>BC/BE:</td>
                      <td><label><input name="bcbe" type="radio" value="BC">
                        BC</label> 
                        <label><input name="bcbe" type="radio" value="BE"> 
                        BE</label> 
                      </td>
                      <td>Visa Status:</td>
                      <td><input name="visa_status" type="text" id="visa_status" value="<?php echo $visa_status; ?>" size="10" maxlength="3">
                      J1,&nbsp;H1,&nbsp;O1</td>
                    </tr>
                    <tr> 
                      <td>US Citizen: </td>
                      <td><label><input name="citizen" type="radio" value="1" <?php echo $citizen==1?'checked':''; ?>>
                        Yes</label> 
                        <label><input name="citizen" type="radio" value="2" <?php echo $citizen==2?'checked':''; ?>>
                        Perm.Res</label><br>
                        <label><input name="citizen" type="radio" value="3" <?php echo $citizen==3?'checked':''; ?>>
                        No/Other</label></td>
                      <td title="If born in the USA">Home State:</td>
                      <td colspan="2" title="If born in the USA"><?php echo showStateList($resdb,$birth_state,'birth_state'); ?></td>
                      <td>&nbsp;</td>
                    </tr>
                    <tr>
                      <td>&nbsp;</td>
                      <td>&nbsp;</td>
                        <td>Licensed: </td>
                      <td colspan="2"><?php echo showStateList($resdb,$licensed,'licensed'); ?></td>
                      <td align="right"><input name="submit" type="submit" id="submit2" value="Search"></td>
                    </tr>
                  </table>
<hr />
                <table style="width:100%" border="0" >
                  <tr>
                    <td>Marital Status: </td>
                    <td><label>
                      <input name="marital_status" type="radio" value="S">
      Single</label>
                        <label>
                        <input name="marital_status" type="radio" value="M">
      Married</label></td>
                    <td>Spouse:</td>
                    <td><input name="spouse" type="text" id="spouse" value="<?php echo $spouse; ?>" size="25" maxlength="60"></td>
                    <td colspan="2" title="Spouse profession">Sp.Prof.:
                        <input name="spouse_prof" type="text" id="spouse_prof" value="<?php echo $spouse_prof; ?>" maxlength="50" title="Spouse profession"></td>
                  </tr>
                  <tr>
                    <td>Preferred City:</td>
                    <td><input name="pref_city" type="text" id="pref_city" value="<?php echo $pref_city; ?>" maxlength="100"></td>
                    <td>Pref. Community:</td>
                    <td><label>
                    <input name="pref_commu_1" type="checkbox" id="pref_commu_1" value="S" title="Small/Rural" <?php echo $pref_commu_1==='S'?'checked':''; ?>>
Small</label>
<label>
<input name="pref_commu_2" type="checkbox" id="pref_commu_2" value="C" title="Medium" <?php echo $pref_commu_2==='C'?'checked':''; ?>>
Medium</label>
<label>
<input name="pref_commu_3" type="checkbox" id="pref_commu_3" value="M" title="Metro Area/Big city" <?php echo $pref_commu_3==='M'?'checked':''; ?>>
Metro</label></td>
                    <td>Pref. 
Practice Type: </td>
                    <td><select name="pref_practice" size="1">
                      <option value="">&nbsp;</option>
                      <option value="SSG" <?php echo $pref_practice=='SSG'?'selected':''; ?>> SSG</option>
                      <option value="MSG" <?php echo $pref_practice=='MSG'?'selected':''; ?>> MSG</option>
                      <option value="Solo" <?php echo $pref_practice=='Solo'?'selected':''; ?>> Solo</option>
                      <option value="Hosp" <?php echo $pref_practice=='Hosp'?'selected':''; ?>> Hosp.Emp.</option>
                      <option value="Acad" <?php echo $pref_practice=='Acad'?'selected':''; ?>> Academic</option>
                      <option value="Locum" <?php echo $pref_practice=='Locum'?'selected':''; ?>> Locum Tenens</option>
                      <option value="Pub" <?php echo $pref_practice=='Pub'?'selected':''; ?>> Public Health</option>
                      <option value="Rural" <?php echo $pref_practice=='Rural'?'selected':''; ?>> Rural HC</option>
                      <option value="ER" <?php echo $pref_practice=='ER'?'selected':''; ?>> ER/Urgent</option>
                    </select></td>
                  </tr>
                  <tr>
                    <td>Preferred State:</td>
                    <td><?php echo showStateList($resdb,$pref_states,'pref_states'); ?>&nbsp; </td>
                    <td><label>
                      <input name="pref_stopen" type="checkbox" id="pref_stopen" value="1" <?php echo $pref_stopen?'checked':''; ?>>
      OPEN</label></td>
                    <td colspan="2" valign="middle">Pref. Region:&nbsp;
                        <select name="pref_region" id="pref_region">
                          <option value="0">&nbsp;</option>
<?php 
			$sql = "select reg_id,reg_name from regions where reg_id != 0 order by reg_id";
			$regs = $resdb->query($sql);
			if( $regs ) while( list($reg_id,$reg_name) = $regs->fetch_row() ) {
?>
                          <option value="<?php echo $reg_id; ?>"><?php echo $reg_name; ?></option>
<?php 		}
?>
                        </select>
                    </td>
                    <td><input name="pref_regopen" type="checkbox" id="pref_regopen" value="1" <?php echo $pref_regopen?'checked':''; ?>>
      OPEN</td>
                  </tr>
                  <tr>
                    <td>Contact Pref: </td>
                    <td><select name="contact_pref" id="contact_pref">
                        <option value="0" <?php echo $contact_pref?'':'selected'; ?>>No preference</option>
                        <option value="1" <?php echo $contact_pref==1?'selected':''; ?>>Home Phone</option>
                        <option value="2" <?php echo $contact_pref==2?'selected':''; ?>>Office</option>
                        <option value="3" <?php echo $contact_pref==3?'selected':''; ?>>Cell Phone</option>
                        <option value="4" <?php echo $contact_pref==4?'selected':''; ?>>Email</option>
                        <option value="5" <?php echo $contact_pref==5?'selected':''; ?>>Pager</option>
                        <option value="6" <?php echo $contact_pref==6?'selected':''; ?>>Postal Mail</option>
                      </select>
                    </td>
                    <td>Languages*:</td>
                    <td><input name="languages" type="text" value="<?php echo $languages; ?>"></td>
                    <td>&nbsp;</td>
                    <td align="right"><input name="submit" type="submit" id="submit3" value="Search"></td>
                  </tr>
<?php if( $ACCESS == 500 ) { ?>
                  <tr>
                    <td>Custom Criteria: </td>
                    <td colspan="3" title="You know what is supposed to be here, right?"><textarea name="custwher" cols="55" rows="3" id="custwher"><?php echo stripslashes($_POST['custwher']); ?></textarea><br>
				 <label><input name="custexcl" type="checkbox" id="custexcl" <?php if( $custexcl ) echo 'checked '; ?> value="1">
                    Ignore all other selections</label> </td>
                    <td colspan="2">Joins: 
                    <input name="custjoin" type="text" id="custjoin" value="<?php echo $custjoin; ?>"></td>
                  </tr>
<?php } // 500 ?>
                </table>
			  </form>
			  </div>
			  <p>* Three symbols minimum for a search term.</p>
              <?php
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
