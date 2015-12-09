<?php
    require("globals.php5");
    require("cookies.php5");
    // $UUID <> 0 if auth
	// normal: ?id=$docid&lid=$lid&y=$yer&pos=$pos
	// params: 	$_REQUEST['id']
	$docid = $_REQUEST['id'];

	if( $UUID && $ACCESS ) try {
	  if( empty($docid) && isset($_POST['submit']) ) { // CREATE NEW
			$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
			extract($strippost,EXTR_SKIP);
	  		if( empty($fname) || empty($lname) ) throw new Exception('First &amp; Last name are required',__LINE__);
			if( empty($email) ) throw new Exception('Email address is required',__LINE__);
			$resdb = db_career();
			$nodb = db_notes();
			if( !is_numeric($source) ) $source = 0;
			// we need to insert only name, to get an ID.
			// everything else will be updated by the next block below
			$fname = addslashes($fname); $lname = addslashes($lname); $email = addslashes($email);
			$sql = "insert into physicians (fname,lname,email,pending,avail_date,data_entry,source) values ('$fname','$lname','$email',2,curdate(),now(),$source)";
			$result = $resdb->query($sql);
			if( !$result ) throw new Exception(DEBUG?"$resdb->error : $sql":'Can not insert, may be email address is already registered',__LINE__);
			$docid = $resdb->insert_id;
			$sql = "insert into gestapo (phid,opid,action) values ($docid,$UUID,'RESNEW')";
			$result = $nodb->query($sql);
	  }
	  elseif( empty($docid) && isset($_POST['submit2']) ) { // import
			if( empty($_POST['import']) ) throw new Exception('Import Data is missing',__LINE__);
			$imp = simplexml_load_string(stripslashes($_POST['import']));
			if( !$imp ) throw new Exception('Import failed',__LINE__);
			$resdb = db_career();
			$nodb = db_notes();
			$phg = chknul(addslashes($imp->phg_id));	// *
			$fname = addslashes(trim($imp->fname));
			$lname = addslashes(trim($imp->lname));
			$mddo = addslashes(trim($imp->mddo));
			$addr1 = addslashes(trim($imp->addr1));
			$addr2 = addslashes(trim($imp->addr2));
			$city = addslashes(trim($imp->city));
			$state = addslashes(trim($imp->state));
			$zip = addslashes(trim($imp->zip));
			$ofaddr1 = addslashes(trim($imp->ofaddr1));
			$ofaddr2 = addslashes(trim($imp->ofaddr2));
			$ofcity = addslashes(trim($imp->ofcity));
			$ofstate = addslashes(trim($imp->ofstate));
			$ofzip = addslashes(trim($imp->ofzip));
			$homephone = addslashes(trim($imp->homephone));
			$cellphone = addslashes(trim($imp->cellphone));
			$officephone = addslashes(trim($imp->officephone));
			$officeext = addslashes(trim($imp->officeext));
			$email = addslashes(trim($imp->email));
			$spec = addslashes(trim($imp->spec));
			$spec_2nd = chknul(addslashes(trim($imp->spec_2nd))); // *
			$school = chknul(addslashes(trim($imp->school)));	// *
			$amg = $imp->amg? 1:0;	// *
			$bcbe = chknul(addslashes(trim($imp->bcbe)));	// *
			$bcbe_year = chknul(addslashes(trim($imp->bcbe_year)));	// *
			$licensed = chknul(addslashes(trim($imp->licensed)));	// *
			$pstatus = addslashes(trim($imp->pstatus));	// *
			if( empty($pstatus) ) $pstatus = 0;
			$visa_status = chknul(addslashes(trim($imp->visa_status)));	// *
			$avail_date = chknul(addslashes(trim($imp->avail_date)));	// *
			if( $avail_date == 'NULL' ) $avail_date = 'curdate()';
			$pref_region = chknul(addslashes(trim($imp->pref_region)));	// *
			$pref_states = addslashes(trim($imp->pref_states));	// *
			if( !$pref_states || $pref_states == '--' ) { $pref_stopen = 1; $pref_states = 'NULL'; }
			else { $pref_stopen = 0; $pref_states = "'$pref_states'";}
			$languages = chknul(addslashes(trim($imp->languages)));	// *
			$phg_source = chknul(addslashes(trim($imp->phg_source)));	// *
	  		if( empty($fname) || empty($lname) ) throw new Exception('First &amp; Last name are required',__LINE__);
			if( empty($email) ) throw new Exception('Email address is required',__LINE__);

			$sql = "insert into physicians (fname,lname,email,pending,avail_date,data_entry,spec_2nd,school,amg,bcbe,bcbe_year, licensed, pstatus, visa_status,pref_region,pref_states,languages,res_id,`year`,`source`,phg_source) values ('$fname','$lname','$email',2,$avail_date,now(), $spec_2nd, $school, $amg, $bcbe, $bcbe_year, $licensed, $pstatus, $visa_status,$pref_region,$pref_states,$languages,$phg,2000,20,$phg_source)";
			$result = $resdb->query($sql);
			if( !$result ) throw new Exception(DEBUG?"$resdb->error : $sql":'Can not insert, may be email address is already registered',__LINE__);
			$docid = $resdb->insert_id;
			$sql = "insert into gestapo (phid,opid,action) values ($docid,$UUID,'IMPORT')";
			$result = $nodb->query($sql);
	  }
	  if( $docid ) {
		if( !isset($resdb) ) $resdb = db_career();
		$doc = new Physician($resdb,$docid);
		
		// now process form buttons
		if( isset($_POST['submit']) || isset($_POST['submit2']) || isset($_POST['savest']) ) {
			// fname midname lname addr1 addr2 city state zip phone spec ...
			$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
			extract($strippost,EXTR_SKIP);
			if( $iv_date <= date('Y-m-d') ) $doc->iv_date = $iv_date;
			$doc->dup = 0; 
			$doc->inactive = 0; $doc->status = 1;
			if( !empty($fname) ) $doc->fname = $fname;
			$doc->midname = $midname; $doc->mddo = $mddo; $doc->title = $title;
			if( !empty($lname) ) $doc->lname = $lname;
			$doc->addr1 = $addr1; $doc->addr2 = $addr2;
			$doc->city = $city; $doc->zip = $zip;
			if( !empty($state) ) $doc->state = $state;
			$doc->ofaddr1 = $ofaddr1; $doc->ofaddr2 = $ofaddr2;
			$doc->ofcity = $ofcity; $doc->ofzip = $ofzip;
			if( !empty($ofstate) ) $doc->ofstate = $ofstate;
			$doc->homephone = $homephone;	$doc->cellphone = $cellphone;
			$doc->officephone = $officephone; $doc->officeext = $officeext;
			if( !empty($spec) ) $doc->spec = $spec;
			$doc->email = $email;
			$newp = randomkeys(rand(6,12));
			$doc->password = sha1($newp);

			$doc->save_res();
			$saved = true;
			if( !isset($nodb) ) {
				$nodb = db_notes();
				$sql = "insert into gestapo (phid,opid,action) values ($doc->ph_id,$UUID,'RESNEW EDIT')";
				$result = $nodb->query($sql);
			}
			$redir = "showdocpc.php?id=$docid&lid=0";
		}
	  }
	  if( !isset($resdb) ) $resdb = db_career();

	}
	catch(Exception $e) {
		$mesg = 'Request failed: '.$e->getMessage().' ('.$e->getCode().')<br>';
		unset($result);
	}
	if( !$docid ) $docid = 0;
	$style = new OperPage('Add New Physician',$UUID,'residents','resnew',($redir?"2; URL=$redir":''));
	$scrip = <<<HereScript
<script type="text/javascript" src="calendarDateInput.js"></script>
<script type="text/javascript" src="reg1.js"></script>
<script type="text/javascript" src="areas.js"></script>
<script language="JavaScript" type="text/JavaScript"><!--

function showpage(page) {
    document.getElementById("formpg_1").style.display = page==1?"block":"none";
    document.getElementById("formpg_2").style.display = page==2?"block":"none";
    document.getElementById("formpg_3").style.display = page==3?"block":"none";
    document.getElementById("formpg_4").style.display = page==4?"block":"none";
    document.getElementById("formpage").value = page;
  //  document.getElementById("formpagespan").innerText = page;
  //  *** seems not to work in all browsers

	return true;
}

var subwind;

function checkemail() {
	// runs a check in a separate window.
	var email = document.getElementById("email").value;
	if( email != '' ) {
		subwind = window.open("checkemail.php?id=$docid&e="+encodeURI(email),
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

function checkPhoEx2(phoinp,pholabel,stsel,stlabel) {
	var pho = document.getElementById(phoinp);
	var sta = document.getElementById(stsel);
	return checkPhoEx(pho,pholabel,sta,stlabel);
}

// -->
</script>
<style type="text/css">
<!--
.style1 {color: #333333}
.style2 {font-size: 10px}
-->
</style>
HereScript;
	$style->Output($scrip);
	
	if( $UUID ) {
?>

              <h1>Add New Physician <span id="savedhead" style="visibility:<?php echo $saved?'visible':'hidden'; ?>">(Saved)</span></h1>
              <?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
              <div id="formdiv">
			  <form name="form1" method="post" action="resnew.php">
                <p>ID#: <?php echo $doc?$doc->uid:'New'; ?>.
                  <input name="id" type="hidden" id="id" value="<?php echo $doc?$doc->uid:0; ?>">
                </p>
                    <table width="90%"  border="0">
                      <tr> 
                        <td><strong>First Name*</strong>:</td>
                        <td><input name="fname" type="text" id="fname" value="<?php echo $doc?$doc->fname:''; ?>" maxlength="50"></td>
                        <td>Middle:</td>
                        <td><input name="midname" type="text" id="midname" value="<?php echo $doc?$doc->midname:''; ?>" maxlength="30"></td>
                        <td><strong>Last name</strong>*:</td>
                        <td><input name="lname" type="text" id="lname" value="<?php echo $doc?$doc->lname:''; ?>" maxlength="50"></td>
                      </tr>
                      <tr> 
                        <td><strong>Degree*</strong>:</td>
                        <td><input name="mddo" type="text" id="mddo" value="<?php echo $doc?$doc->mddo:''; ?>" size="10" maxlength="4">
                          MD/DO</td>
                        <td>Title:</td>
                        <td colspan="3"><input name="title" type="text" id="title" value="<?php echo $doc?$doc->title:''; ?>" maxlength="50" title="Enter additional titles: PhD, FACS, FACOG, ...
Please don't put MD or DO here."></td>
                      </tr>
                      <tr> 
                        <td>Address Line 1:</td>
                        <td colspan="5"><input name="addr1" type="text" id="addr1" value="<?php echo $doc?$doc->addr1:''; ?>" size="50" maxlength="100"> 
                          &nbsp;&nbsp;Line 2: 
                          <input name="addr2" type="text" id="addr2" value="<?php echo $doc?$doc->addr2:''; ?>" size="45" maxlength="100"></td>
                      </tr>
                      <tr> 
                        <td>City:</td>
                        <td><input name="city" type="text" id="city" value="<?php echo $doc?$doc->city:''; ?>" maxlength="30"></td>
                        <td>State:</td>
                        <td><?php echo showStateList($resdb,$doc?$doc->state:'--'); ?></td>
                        <td>Zip:</td>
                        <td><input name="zip" type="text" id="zip" value="<?php echo $doc?$doc->zip:''; ?>" maxlength="10"></td>
                      </tr>
                      <tr> 
                        <td bgcolor="#E0E0FF">Office Address:</td>
                        <td colspan="5" bgcolor="#E0E0FF"><input name="ofaddr1" type="text" id="ofaddr1" value="<?php echo $doc?$doc->ofaddr1:''; ?>" size="50" maxlength="50"> 
                          &nbsp;&nbsp;Line 2: 
                          <input name="ofaddr2" type="text" id="ofaddr2" value="<?php echo $doc?$doc->ofaddr2:''; ?>" size="45" maxlength="50"></td>
                      </tr>
                      <tr> 
                        <td bgcolor="#E0E0FF">City:</td>
                        <td bgcolor="#E0E0FF"><input name="ofcity" type="text" id="ofcity" value="<?php echo $doc?$doc->ofcity:''; ?>" maxlength="30"></td>
                        <td bgcolor="#E0E0FF">State:</td>
                        <td bgcolor="#E0E0FF"><?php echo showStateList($resdb,$doc?$doc->ofstate:'--','ofstate'); ?></td>
                        <td bgcolor="#E0E0FF">Zip:</td>
                        <td bgcolor="#E0E0FF"><input name="ofzip" type="text" id="ofzip" value="<?php echo $doc?$doc->ofzip:''; ?>" maxlength="10"></td>
                      </tr>
                      <tr> 
                        <td>Home Phone:</td>
                        <td><input name="homephone" type="text" id="homephone" value="<?php echo $doc?$doc->homephone:''; ?>" size="12" maxlength="16" onChange="checkPhoEx(homephone,'Home Phone',state,'Home State')">
                        <span id="chkemail" style="cursor:hand" title="Check Area code/State match" onClick="checkPhoEx2('homephone','Home Phone','state','Home State')"> <img src="images/bquestion.png" alt="?" width="16" height="16" border="0" align="absmiddle" title="Check Area code/State match" ></span></td>
                        <td>Office:</td>
                        <td><input name="officephone" type="text" id="officephone" value="<?php echo $doc?$doc->officephone:''; ?>" size="12" maxlength="16" onChange="checkPhoEx(officephone,'Office Phone',ofstate,'Office State')">
                          Ext: 
                          <input name="officeext" type="text" id="officeext" value="<?php echo $doc?$doc->officeext:''; ?>" size="6" maxlength="10">
                          <span id="chkemail" style="cursor:hand" title="Check Area code/State match" onClick="checkPhoEx2('officephone','Office Phone','ofstate','Office State')"> <img src="images/bquestion.png" alt="?" width="16" height="16" border="0" align="absmiddle" title="Check Area code/State match" ></span></td>
                        <td>Cell:</td>
                        <td><input name="cellphone" type="text" id="cellphone" value="<?php echo $doc?$doc->cellphone:''; ?>" size="16" maxlength="16"></td>
                      </tr>
                      <tr> 
                        <td><strong>Email</strong>*: </td>
                        <td><input name="email" type="text" id="email" value="<?php echo $doc?$doc->email:''; ?>" size="30" maxlength="128" onChange="checkemail(1)"> 
                          <span id="chkemail" onClick="checkemail(1)" style="cursor:hand" title="Check for validity">&nbsp;<img src="images/bquestion.png" alt="?" width="16" height="16" border="0" align="absmiddle"></span></td>
						  <td><strong>Specialty</strong>*:</td>
                        <td colspan="3">                    
                    <?php echo showSpecList($resdb,$doc->spec); ?>
</td>
                      </tr>
                      <tr valign="bottom"> 
                        <td>Source: 
                        </td>
                        <td><?php $source = $doc?$doc->source:0; ?><select name="source" id="source" size="1">     
                        <option value="0" <?php echo $source?'':'selected'; ?>> </option>
                        <option value="20" <?php echo $source==20?'selected':''; ?>>PHG Database</option>
                        <option value="21" <?php echo $source==21?'selected':''; ?>>Job Board</option>
                        <option value="22" <?php echo $source==22?'selected':''; ?>>Email Blast</option>
                        <option value="23" <?php echo $source==23?'selected':''; ?>>Web Page</option>
                        <option value="24" <?php echo $source==24?'selected':''; ?>>Cold Call</option>
						<option value="29" <?php echo $source==29?'selected':''; ?>>Other source</option>
						</select></td>
                        <td>&nbsp;</td>
                        <td colspan="2"><input name="submit" type="submit" id="submit1" value="Save &amp; Continue"></td>
                        <td><input name="reset" type="reset" value="Reset"> 
                        </td>
                      </tr>
                      <tr valign="bottom">
                        <td>Import from PHG: </td>
                        <td colspan="4"><textarea name="import" cols="60" id="import"></textarea></td>
                        <td><input name="submit2" type="submit" id="submit2" value="Import"></td>
                      </tr>
                    </table>
				<div id="formpg_4" style="display:<?php echo $formpage==4?'block':'none'; ?>">
				<p>The information was saved. Please continue entering more information. The form will show up in a moment. Or, click <a href="<?php echo $redir; ?>">here</a> to proceed. </p>
				</div>
				</form>
			  </div>
              
              <?php
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>