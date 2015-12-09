<?php
    require("globals.php5");
    require("cookies.php5");
    // $UUID <> 0 if auth
	$redir = ''; $result = true; $mesg = '';
	$ooid = $_REQUEST['oid'];
	$uacct = $_REQUEST['acct'];
	$usid = $_REQUEST['cid'];
	$masid = $_REQUEST['mas'];
	$oopdate = $_REQUEST['update'];
	if( !is_numeric($usid) ) $usid = $UUID;
	if( !is_numeric($masid) ) $masid = 0;
	if( !is_numeric($uacct) ) $uacct = 0;
	if( !is_numeric($ooid) ) $ooid = 0;
	if( $UUID && ($ACCESS >= 200) ) try {
		$db = db_career();
		$client = $db->query("select firstname, lastname, company from clients where uid=$usid");
		if( $client ) list($cfirst,$clast,$cco) = $client->fetch_row();
		else throw new Exception(DEBUG?"{$db->error}: $sql":'Can not replace answers', __LINE__);
		$client->free();
		if( $_POST['submit'] || $_POST['submit2'] || $_POST['submit3'] ) {
			// do stuff.
			if( $ooid ) { // the modify opp form
				$description = $_POST['description']; // may contain double quotes
				$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
			    extract($strippost,EXTR_SKIP);
				$opp = new Opportunity($db,$ooid);
				// all fields
				if ($opp->o_facility == $o_facility) $ofacnc = true; else $opp->o_facility = $o_facility;
				$opp->o_name = $o_name;
				if ($opp->o_city == $o_city) $ocitnc = true; else $opp->o_city = $o_city;
				if ($opp->o_state == $o_state) $ostanc = true; elseif( $o_state != '--' ) $opp->o_state = $o_state;
				$opp->show_state = $show_state?1:0;
				$opp->o_zip = $o_zip;
				$pref_practice = '';
				for( $i = 1; $i<=9; $i++ )
					if( $strippost["pref_practice_$i"] ) $pref_practice .= $strippost["pref_practice_$i"].',';
				$opp->practice_type = $pref_practice;
				$pref_commu = '';
				for( $i = 1; $i<=3; $i++ )
					if( $strippost["pref_commu_$i"] ) 
						$pref_commu .= ($pref_commu?',':'').$strippost["pref_commu_$i"];
				$opp->o_commu2 = $pref_commu; // 2!!!
				/*
				$pref_amen = '';
				for( $i = 1; $i<=5; $i++ )
					if( $strippost["pref_amen_$i"] ) $pref_amen .= ($pref_amen?',':'').$strippost["pref_amen_$i"];
				$opp->o_amen = $pref_amen;
				$pref_school = '';
				for( $i = 1; $i<=3; $i++ )
					if( $strippost["pref_school_$i"] ) 
						$pref_school .= ($pref_school?',':'').$strippost["pref_school_$i"];
				$opp->o_school = $pref_school;
				*/

				$triggerst = ''; // there should be restrictions/triggers on changing status. to do
				if( $opp->status == 1 && ($status == 0 || $status == 4) ) $triggerst = 'deactivate';
				//if( $opp->status != 1 && $status == 1 ) $triggerst = 'reactivate';
				if( $opp->status != 1 && $status == 1 ){ 
					$triggerst = 'reactivate'; 
					$avail_date = date("Y-m-d"); 
				}
				$opp->status= $status; 

				$opp->o_underserved = $o_underserved? 1:0;
				$opp->description = $description;
				if( $specialty != '---' ) {
					if( $opp->specialty != $specialty ) $opp->specswap();
					$opp->specialty = $specialty;
				}
				else {
					$result = false;
					$mesg = "No Specialty selected! Please assign a specialty.";
				}
				/*$bcbe = '';
				if( $o_bcbe_1 ) $bcbe = $o_bcbe_2?'BB':'BC';
				elseif( $o_bcbe_2 ) $bcbe = 'BE';
				$opp->o_bcbe = $bcbe;
				*/
				$opp->avail_date = $avail_date;
				/*
				$opp->o_salaryhour = $o_salaryhour? 1:0;
				$opp->o_salarymin = $o_salarymin;
				$opp->o_salarymax = $o_salarymax;
				*/
				$opp->o_salaryother = $o_salaryother;
				$opp->partnership = $partnership? 1:0;
				$opp->partner_other = $partner_other;
				$opp->buy_in = $buy_in? 1:0;
				$opp->bonus_sign = $bonus_sign? 1:0;
				$opp->bonus_prod = $bonus_prod? 1:0;
				$opp->relocation = $relocation? 1:0;
				$opp->vacation_wks = $vacation_wks;
				$opp->benefits = $benefits;
				$opp->malpractice = $malpractice;
				/*
				$opp->consider_j1 = $consider_j1? 1:0;
				$opp->consider_amg = $consider_amg? 1:0;
				$opp->residents = $residents? 1:0;
				$opp->practicing = $practicing? 1:0;
				*/
				$opp->notifications = $notifications? 1:0;
				if( $o_locator && is_numeric($o_locator) && $o_locator != $opp->o_lid ) {
					$opp->o_lid = $o_locator;
					$sqlloc = "select l_facility, l_city, l_state from locations where (l_uid = $usid or l_acct = $uacct) and status = 1 and l_id = $o_locator";
					$locs = $db->query($sqlloc);
					if( $locs && $locs->num_rows ) {
						$aloc = $locs->fetch_object();
						if( $ofacnc ) $opp->o_facility = $aloc->l_facility;
						if( $ocitnc ) $opp->o_city = $aloc->l_city;
						if( $ostanc ) $opp->o_state = $aloc->l_state;
					}
					$locmove = true;
				}
				
				if( valid_email($o_email) ) $opp->o_email = $o_email;
				if( !empty($o_contact) ) $opp->o_contact = $o_contact;
				$opp->o_phone = $o_phone;
				$opp->o_fax = $o_fax;
				$opp->o_title = $o_title;

				$opp->save();
				if( $triggerst ) $opp->$triggerst();
				if( $locmove ) {
					unset($opp);
					$opp = new Opportunity($db,$ooid);
				}
				
				if( $_POST['submit2'] || $_POST['submit3'] ) { 
					//$redir = '1; URL=specific.php?oid='.$ooid;
					$redir = "specificadmin.php?oid=$ooid&acct=$uacct&cid=$usid&mas=$masid";
					//$mesg = "Saving your opportunity, please wait a moment... If automatic redirection would not work, please <a href=\"specific.php?oid=$ooid\">click here to proceed</a>.";
				}
			}
			elseif( is_numeric($_POST['o_location']) && $_POST['spec'] && $_POST['spec'] != '---' ) { // no ooid = assume it was the create opp form
				// o_name, o_location, spec
				$opp = new Opportunity($db,0,$_POST['o_location'],$usid,$uacct);
				$opp->o_name = $_POST['o_name'];
				$opp->specialty = $_POST['spec'];
				//$opp->save();
				$db->query("update opportunities set specialty = '$opp->spec', o_name = ".chknul(addslashes($opp->o_name))." where oid = $opp->oid");
				$ooid = $opp->oid;
				$opp->specswap();
			}
		}
		// no master override yet, may be later
		if( !$ooid ) {	// list all what we have here
			if( $oopdate === "yes" ) {
				$sql = "update opportunities set o_datemod=now(), avail_date=now() where status= 1 and o_uid = $usid";
				$result = $db->query($sql); // ignore result
				$mesg = $db->affected_rows? 'Jobs were refreshed successfully': 'Nothing to Refresh';
				if( !$result ) throw new Exception(DEBUG?"$db->error: $sql":'Can not refresh opportunities',__LINE__);
			}
			if($_GET["sort"]!='')
				$sql = "select oid, o_name, o_facility, o_city, o_state, specialty, o_datemod, date_added from opportunities where status = 1 and o_uid = $usid order by ".$_GET["sort"]."";
			else
				$sql = "select oid, o_name, o_facility, o_city, o_state, specialty, o_datemod, date_added from opportunities where status = 1 and o_uid = $usid order by o_state, o_city, o_facility, o_name";
			$sqlhis = "select oid, o_name, o_facility, o_city, o_state, specialty, status, o_datemod, date_added from opportunities where status != 1 and o_uid = $usid order by status, o_datemod desc, o_state, o_city, o_facility, o_name";
			$opps = $db->query($sql);
			if( !$opps ) throw new Exception(DEBUG?"$db->error: $sql":'Can not list opportunities',__LINE__);
		}
		elseif( !$opp ) $opp = new Opportunity($db,$ooid);

	}
	catch(Exception $e) {
		$result = false;
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
	}
	if( $result && $redir ) {
		header("Location: $redir");
		exit;
	}
	$style = new OperPage('Opportunities',$UUID,'admin','opportunities');
	$scrpt = "<script type=\"text/javascript\" src=\"calendarDateInput.js\"></script>
		<script type=\"text/javascript\" src=\"/ckeditor/ckeditor.js\"></script>
		<style type=\"text/css\">
<!--
.style1 {
	color: #000099;
	font-weight: bold;
	font-style: italic;
}
#maincontent p.nodescript {
	color: black; 
}
-->
</style>";

	$style->Output($scrpt);
    if( $UUID ) {
		if( $mesg ) echo "<p id='".($result?'warning':'error')."_msg'>$mesg</p>"; 
		if( $ACCESS >= 200 ) {

	$stus = array('On Hold','Active','Placed','N/A','Deleted','N/A','N/A','N/A','Expired');
	if( $ooid ) {
?>
        <h2>Modify the opportunity for <?php $masl = $masid? "custedit.php?cid=$masid":'#'; echo "<a href=\"$masl\">$cfirst $clast</a> ($cco)"; ?></h2>
		<p class="nodescript">Please make sure contact information below is entered and correct.</p>
		<div id="formdiv">
		<form name="modform" method="post" action="opportunadmin.php">
			<input name="oid" type="hidden" value="<?php echo $ooid; ?>">
			<input name="cid" type="hidden" value="<?php echo $usid; ?>">
			<input name="mas" type="hidden" value="<?php echo $masid; ?>">
			<input name="acct" type="hidden" value="<?php echo $uacct; ?>">
			<table>
			<tr><td>ID#:</td><td colspan="3"><?php echo $ooid; ?>, Expires: <?php echo $opp->exp_date; ?></td>
			</tr>
			<tr>
			  <td>Location:</td>
			  <td colspan="3">
<?php 
		$sqlloc = "select l_id, l_facility, l_city, l_state, l_uid from locations where (l_uid = $usid or l_acct = $uacct) and status = 1 order by l_facility, l_state, l_city";
		$locs = $db->query($sqlloc);
		if( $locs && $locs->num_rows ) {
?>
			<select name="o_locator" size="1">
<?php 		while( $aloc = $locs->fetch_object() ) { ?>
				<option <?php if( $opp->o_lid == $aloc->l_id ) echo 'selected'; ?> value="<?php echo $aloc->l_id; ?>"><?php echo "$aloc->l_facility, $aloc->l_city, $aloc->l_state"; 
					if( $aloc->l_uid != $usid ) echo '*'; ?></option>
<?php 		} ?>
			</select>
<?php 	} // locs
		else echo "<p class='nodescript'>Can not find any active locations. This must be an error. Try again.</p>";
?> <br>
<span class="smallprint">If you change Location, then Facility, City and State fields below would be updated automatically.</span>
			  </td>
			  </tr>
			<tr>
			<td>ID/Label:</td>
			<td colspan="2"><input name="o_name" type="text" value="<?php echo $opp->o_name; ?>" size="40" maxlength="50"></td>
			<td align="right"><input name="submit3" type="submit" value="Next&gt;&gt;"></td>
			</tr>
			<tr>
			<td>Specialty:</td><td colspan="3"><?php echo showSpecList($db,$opp->specialty,'specialty'); ?></td>
			</tr>
			<tr>
			<td>Status:</td><td><select name="status" size="1">
				<option value="0" <?php if( $opp->status == 0 ) echo 'selected'; ?>>On Hold</option>
				<option value="1" <?php if( $opp->status == 1 ) echo 'selected'; ?>>Active</option>
				<option value="2" <?php if( $opp->status == 2 ) echo 'selected'; ?>>Placed</option>
				<option value="4" <?php if( $opp->status == 4 ) echo 'selected'; ?>>Deleted</option>
				<option value="8" <?php if( $opp->status == 8 ) echo 'selected'; ?>>Expired</option>
			</select></td>
			<td>Facility:</td><td><input name="o_facility" type="text" value="<?php echo $opp->o_facility; ?>" size="40" maxlength="100"></td>
			</tr>
			<tr>
			<td>City:</td><td><input name="o_city" type="text" maxlength="50" value="<?php echo $opp->o_city; ?>"></td>
			<td>State:</td><td><?php echo showStateList($db,$opp->o_state,"o_state"); ?> <br>
			    <input name="show_state" type="checkbox" value="1" <?php if( $opp->show_state ) echo 'checked'; ?>> Show state to job seekers
			</td>
			</tr>
			<tr><td>Zip:</td><td><input name="o_zip" type="text" maxlength="5" value="<?php echo $opp->o_zip; ?>"></td>
			<td>Underserved area:</td>
			<td><input name="o_underserved" type="checkbox" value="1" <?php if( $opp->o_underserved ) echo 'checked'; ?>>
			Yes</td>
			</tr>
			<tr>
			<td valign="top">Opportunity Description:</td>
			<td colspan="3"><textarea name="description" id="description1" cols="60" rows="8"><?php echo ($opp->description); ?></textarea>							<script type="text/javascript">
				//<![CDATA[
					CKEDITOR.replace( 'description1' );
				//]]>
				</script>
</td>
			</tr>
			<tr>
			<td >Practice Type: </td>
			<td colspan="3"><label><input name="pref_practice_1" type="checkbox" id="pref_practice_1" value="SSG" <?php echo stripos($opp->practice_type,'SSG')!==false?'checked':''; ?>>
			Single-spec. gr.</label> &nbsp;
			<label><input name="pref_practice_2" type="checkbox" id="pref_practice_2" value="MSG" <?php echo stripos($opp->practice_type,'MSG')!==false?'checked':''; ?>>
			Multi-spec. gr.</label> &nbsp;
			<label><input name="pref_practice_3" type="checkbox" id="pref_practice_3" value="Solo" <?php echo stripos($opp->practice_type,'Solo')!==false?'checked':''; ?>>
			Solo</label> &nbsp;
			<label><input name="pref_practice_4" type="checkbox" id="pref_practice_4" value="Hosp" <?php echo stripos($opp->practice_type,'Hosp')!==false?'checked':''; ?>>
			Hospital Emp.</label><br>
			<label><input name="pref_practice_5" type="checkbox" id="pref_practice_5" value="Acad" <?php echo stripos($opp->practice_type,'Acad')!==false?'checked':''; ?>>
			Academic</label> &nbsp;
			<label><input name="pref_practice_6" type="checkbox" id="pref_practice_6" value="Locum" <?php echo stripos($opp->practice_type,'Locum')!==false?'checked':''; ?>>
			Locums</label> &nbsp;
			<label><input name="pref_practice_7" type="checkbox" id="pref_practice_7" value="Pub" <?php echo stripos($opp->practice_type,'Pub')!==false?'checked':''; ?>>
			Pub.Health</label> &nbsp;
			<label><input name="pref_practice_8" type="checkbox" id="pref_practice_8" value="Rural" <?php echo stripos($opp->practice_type,'Rural')!==false?'checked':''; ?>>
			Rural&nbsp;HC</label> &nbsp;
			<label><input name="pref_practice_9" type="checkbox" id="pref_practice_9" value="ER" <?php echo stripos($opp->practice_type,'ER')!==false?'checked':''; ?>>
			ER/Urgent</label></td>
			</tr><tr>
			<td>Community:</td>
			<td><label><input name="pref_commu_1" type="checkbox" id="pref_commu_1" value="S" title="Small/Rural" <?php echo strpos($opp->o_commu2,'S')!==false?'checked':''; ?>>
			Small</label>&nbsp;
			<label><input name="pref_commu_2" type="checkbox" id="pref_commu_2" value="C" title="Medium" <?php echo strpos($opp->o_commu2,'C')!==false?'checked':''; ?> >
			Medium</label>&nbsp; 
			<label><input name="pref_commu_3" type="checkbox" id="pref_commu_3" value="M" title="Metro Area/Big city" <?php echo strpos($opp->o_commu2,'M')!==false?'checked':''; ?>>
			Metro</label>
			</td>
			<td>Available Date:</td>
			<td> 
			  <script language="javascript">DateInput('avail_date', false, 'YYYY-MM-DD', '<?php echo $opp->avail_date?$opp->avail_date:date("Y-m-d"); ?>');</script></td>
			</tr>
			<tr>
			  <td valign="top">Compensation (<em>public</em>):</td>
			  <td colspan="3"><input name="o_salaryother" type="text" id="o_salaryother" title="Other Compensation or Salary Details:" value="<?php echo $opp->o_salaryother; ?>" size="30" maxlength="100"> 
		      </td>
			  </tr>
		<tr>
		<td>Partnership:</td><td><input name="partnership" type="checkbox" value="1" <?php if( $opp->partnership ) echo 'checked'; ?>>
		Available</td>
		<td>Partnership details:</td><td><input name="partner_other" type="text" maxlength="50" value="<?php echo $opp->partner_other; ?>"></td>
		</tr>
		<tr>
		<td>Buy-In:</td><td><input name="buy_in" type="checkbox" value="1" <?php if( $opp->buy_in ) echo 'checked'; ?>> Yes</td>
		<td>Relocation paid:</td><td><input name="relocation" type="checkbox" value="1" <?php if( $opp->relocation ) echo 'checked'; ?>>
		Yes</td>
		</tr>
		<tr>
		<td>Signing bonus:</td><td><input name="bonus_sign" type="checkbox" value="1" <?php if( $opp->bonus_sign ) echo 'checked'; ?>>
		Available</td>
		<td>Production bonus:</td><td><input name="bonus_prod" type="checkbox" value="1" <?php if( $opp->bonus_prod ) echo 'checked'; ?>>
		Available</td>
		</tr>
		<tr>
		<td>Vacation (weeks/details):</td><td><input name="vacation_wks" type="text" maxlength="50" value="<?php echo $opp->vacation_wks; ?>"></td>
		<td>Malpractice:</td><td><input name="malpractice" type="text" maxlength="50" value="<?php echo $opp->malpractice; ?>"></td>
		</tr>
		<tr>
		<td>Other benefits:</td><td colspan="3"><input name="benefits" type="text" value="<?php echo $opp->benefits; ?>" size="60" maxlength="100"></td>
		</tr>
		<tr>
		<td>Contact name:</td><td><input name="o_contact" type="text" maxlength="60" value="<?php echo htmlspecialchars($opp->o_contact,ENT_COMPAT | ENT_HTML5,'UTF-8'); ?>"></td>
		<td>Email notifications:</td><td><input name="notifications" type="checkbox" value="1" <?php if( $opp->notifications ) echo 'checked'; ?>> Send</td>
		</tr>
		<tr>
		<td>Title:</td><td><input name="o_title" type="text" maxlength="80" value="<?php echo htmlspecialchars($opp->o_title,ENT_COMPAT | ENT_HTML5,'UTF-8'); ?>"></td>
		<td>Contact Email:</td><td><input name="o_email" type="text" value="<?php echo htmlspecialchars($opp->o_email,ENT_COMPAT | ENT_HTML5,'UTF-8'); ?>" size="40" maxlength="128"> 
		  (<em>hidden</em>) </td>
		</tr>
		<tr>
		<td>Contact Phone:</td><td><input name="o_phone" type="text" maxlength="20" value="<?php echo $opp->o_phone; ?>"></td>
		<td>Contact Fax:</td><td><input name="o_fax" type="text" maxlength="16" value="<?php echo $opp->o_fax; ?>"></td>
		</tr>
		<tr>
		<td><input name="submit" type="submit" value="Save"></td><td></td><td><input type="reset" name="Reset" value="Reset"></td><td align="right"><input name="submit2" type="submit" value="Next&gt;&gt;"></td>
		</tr>
		</table>
		</form>
		</div>
		<h2>Opportunity Preview</h2>
		<p class="nodescript">Below you can see their job posting with all information entered so far. To refresh the preview, click &quot;Save&quot; button above. </p>
        <div id="formdiv2" style="border: medium solid #3399FF; font-family: Tahoma,Verdana,Arial,Helvetica,sans-serif; line-height: 1.3;  font-size: 11pt;">        
          <table style=" 	border-color: #363; border-style: dotted; border-width: 1px; border-spacing: 0;">
            <tr>
        <td colspan="2"><strong><?php echo $opp->o_facility?$opp->o_facility: "A great facility"; ?></strong></td>
        <td colspan="7" rowspan="3"><?php if ($opp->l_pic4type) { ?>
	<img src="locationsadmin.php?l_id=<?php echo $opp->o_lid; ?>&amp;view_pic=5" style="vertical-align: text-top;" alt="Company Logo" border="1" align="absmiddle" /> 
<?php	} ?> </td>
            </tr>
            <tr>
              <td >City:</td>
              <td ><?php echo $opp->o_city; ?></td>
            </tr>
            <tr>
              <td >State:</td>
              <td ><?php echo $opp->show_state? $opp->o_state: 'USA'; ?></td>
            </tr>
            <tr>
              <td >Specialty:</td>
              <td  colspan="9"><?php echo $SpecList2[$opp->specialty]; ?></td>
            </tr>
            <tr>
              <td valign="top" >Opportunity Description:</td>
              <td  colspan="9"><?php echo ($opp->description); ?></td>
            </tr>
	<?php if( $opp->practice_type ) { ?>
		<tr>
		<td >Practice Type: </td>
            <?php if (stripos($opp->practice_type,'SSG')!==false) {?> <td >Single spec.gr.</td><?php }
			if (stripos($opp->practice_type,'MSG')!==false) {?> <td >Multi spec.gr.</td><?php }
			if (stripos($opp->practice_type,'Solo')!==false) {?> <td >Solo</td><?php }
			if (stripos($opp->practice_type,'Hosp')!==false) {?> <td >Employee</td><?php }
			if (stripos($opp->practice_type,'Acad')!==false) {?> <td >Academic</td><?php }
			if (stripos($opp->practice_type,'Locum')!==false) {?> <td >Locums</td><?php }
			if (stripos($opp->practice_type,'Pub')!==false) {?> <td >Pub. Health</td><?php }
			if (stripos($opp->practice_type,'Rural')!==false) {?> <td >Rural HC</td><?php }
			if (stripos($opp->practice_type,'ER')!==false) {?> <td >ER/Urgent</td><?php }?>
          </tr>
	<?php } // practice type ?>
            <tr>
			<td >Available Date:</td>
			<td  ><?php echo $opp->avail_date?$opp->avail_date:date("Y-m-d"); ?></td>
			</tr>
	<?php if( $opp->o_salaryother ) { ?>
			<tr>

   <td >
    Compensation:
      </td><td  colspan="9"><?php echo $opp->o_salaryother; ?></td>
			</tr>
		<?php } // salary other ?>
		
        <?php if( $opp->partnership ) {?>
       <tr> 
		<td >Partnership:</td><td ><?php echo 'Available';?></td>
		<td >Partnership details:</td><td  colspan="7"><?php echo $opp->partner_other; ?></td>
        
		</tr>
        <?php }?>
		<tr>
        <?php if( $opp->buy_in ) {?>
		<td >Buy-In:</td><td >Yes</td>
         <?php }
		 if( $opp->relocation ) {?>                             
		<td >Relocation paid:</td><td >Yes</td>
        <?php }?>  
		</tr>
		<tr>
        <?php if( $opp->bonus_sign ) {?>
		<td >Signing bonus:</td><td >Available</td>
        <?php }
		if( $opp->bonus_prod ) {?>
		<td >Production bonus:</td><td >Available</td>
        <?php }?>  
	
		</tr>
		<tr>
        <?php if( $opp->vacation_wks ) {?>
		<td >Vacation (weeks/details):</td><td ><?php echo $opp->vacation_wks; ?></td>
        <?php }?>  
        <?php if( $opp->malpractice ) {?>
		<td >Malpractice:</td><td ><?php echo $opp->malpractice; ?></td>
        <?php }?>  
		</tr>
        <?php if( $opp->benefits ) {?>
		<tr>
		<td >Other benefits:</td><td  colspan="9"><?php echo $opp->benefits; ?></td>
		</tr>
        <?php }?>  
        <?php if( $opp->l_description ) {?>
            <tr>
              <td  valign="top">Facility Description:</td>
              <td  colspan="9"><?php echo ($opp->l_description); ?></td>
            </tr>
           <tr>
          <tr>
              <td valign="top">&nbsp;</td>
              <td colspan="9">&nbsp;</td>
          </tr>
        <?php }?>  
        <?php if( $opp->l_commdescr ) {?>
            <tr>
              <td  valign="top">Community Description:</td>
              <td  colspan="9"><?php echo ($opp->l_commdescr); ?></td>
            </tr>
        <?php }?>  
        <?php if( $opp->o_commu2 ) {?>
		<tr>
			<td >Community Size:</td>
            <?php if (strpos($opp->o_commu2,'S')!==false) {?> <td >Small/Rural</td><?php }
			if (strpos($opp->o_commu2,'C')!==false) {?> <td >Medium</td><?php }
			if (strpos($opp->o_commu2,'M')!==false) {?> <td >Metro Area/Big city</td><?php }?>
			</tr>
        <?php }?>  
			<tr valign="middle">
              <td >Pictures:</td>
              <td  colspan="5"><?php     
             if ($opp->l_pic0type) {   
?>
                  <img src="locationsadmin.php?l_id=<?php echo $opp->o_lid; ?>&amp;view_pic=1" border="1" align="absmiddle" style="border-color:	#99CC33;" /> &nbsp;
                  <?php        }
			     
             if ($opp->l_pic1type) {   
?>
                  <img src="locationsadmin.php?l_id=<?php echo $opp->o_lid; ?>&amp;view_pic=2" alt="Uploaded image" border="1" style="border-color:	#99CC33;" align="absmiddle" /> &nbsp;
                  <?php        }
			  
             if ($opp->l_pic2type) { 
?>
                  <img src="locationsadmin.php?l_id=<?php echo $opp->o_lid; ?>&amp;view_pic=3" alt="Uploaded image" border="1" style="border-color:	#99CC33;" align="absmiddle" /> &nbsp;
                  <?php        } 	
			  
             if ($opp->l_pic3type) {   
?>
                  <img src="locationsadmin.php?l_id=<?php echo $opp->o_lid; ?>&amp;view_pic=4" alt="Uploaded image" border="1" style="border-color:	#99CC33;" align="absmiddle" /> &nbsp;
                  <?php        } 	  ?>
              </td>
            </tr>
            <tr>
              <td >Contact name:</td>
              <td  colspan="9"><?php echo htmlspecialchars($opp->o_contact?$opp->o_contact:"Physician Recruiter",ENT_COMPAT | ENT_HTML5,'UTF-8'); ?></td>
            </tr>
            <tr>
              <td >Title:</td>
              <td  colspan="9"><?php echo htmlspecialchars($opp->o_title,ENT_COMPAT | ENT_HTML5,'UTF-8'); ?></td>
            <tr>
              <td >Contact Phone:</td>
              <td ><?php echo $opp->o_phone; ?></td>
              <td >Contact Fax:</td>
              <td ><?php echo $opp->o_fax; ?></td>
            </tr>
          </table>
        </div>
<?php } else { // ooid ?>
        <h1>Manage Opportunities for <?php echo "<a href=\"custedit.php?cid=$masid\">$cfirst $clast</a> ($cco)"; ?></h1>
        <p class="nodescript">You can manage clients&rsquo; opportunities here. To manage their locations, go to <a href="locationsadmin.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid"; ?>">this page</a>.</p>
        <h3>Add new opportunity</h3>
<?php 
		$sqlloc = "select l_id, l_facility, l_city, l_state, l_uid from locations where (l_uid = $usid or l_acct = $uacct) and status = 1 order by l_facility, l_state, l_city";
		$locs = $db->query($sqlloc);
		if( $locs && $locs->num_rows ) {
		//print_r($_REQUEST);
?>
        <p class="nodescript"> For each location, they may have several opportunities and the same or different specialties. Create, label and click to build each opportunity:</p>
		<div id="formdiv">
		<form name="addform" method="post" action="opportunadmin.php">
			<input name="cid" type="hidden" value="<?php echo $usid; ?>">
			<input name="mas" type="hidden" value="<?php echo $masid; ?>">
			<input name="acct" type="hidden" value="<?php echo $uacct; ?>">
			<table>
			<tr><td>Label:</td><td><input name="o_name" type="text" maxlength="50" value=""> 
			  (<em>internal ID, invisible to candidates</em>)</td>
			</tr>
			<tr><td>Location:</td><td><select name="o_location" size="1">
<?php 		while( $aloc = $locs->fetch_object() ) { ?>
				<option value="<?php echo $aloc->l_id; ?>"><?php echo "$aloc->l_facility, $aloc->l_city, $aloc->l_state"; 
					if( $aloc->l_uid != $usid ) echo '*'; ?></option>
<?php 		} ?>
			</select></td></tr>
			<tr><td>Specialty:</td><td><?php echo showSpecList($db,$_POST['spec']); ?></td></tr>
			<tr><td>&nbsp;</td><td><input name="submit" type="submit" value="Create"></td></tr>
			</table>
		</form>
		</div>
<?php 	} // locs
		else echo "<p class='nodescript'>They have not entered any locations. You can enter one or more <a href=\"locationsadmin.php?cid=$usid&acct=$uacct&mas=$masid\">locations</a> for them.</p>";
?>
        <h3>Modify Opportunities</h3>
<?php	if( $opps && $opps->num_rows ) { ?>
        	<p class="nodescript">Below is the list of all their active opportunities. Click here to refresh their jobs: <a href="opportunadmin.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid&update=yes"; ?>">REFRESH</a></p>
			<table><thead><tr><th><a href="opportunadmin.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid"; ?>&sort=o_name">Label</a></th><th><a href="opportunadmin.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid"; ?>&sort=o_facility">Facility</a></th><th><a href="opportunadmin.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid"; ?>&sort=o_city">City</a></th><th><a href="opportunadmin.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid"; ?>&sort=o_state">State</a></th><th><a href="opportunadmin.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid"; ?>&sort=specialty">Specialty</a></th><th><a href="opportunadmin.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid"; ?>&sort=o_datemod">Last Edited</a></th><th><a href="opportunadmin.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid"; ?>&sort=date_added">Added</a></th></tr></thead><tbody>
<?php		while( $opp = $opps->fetch_object() ) { ?>
				<tr>
					<td><a href="opportunadmin.php?oid=<?php echo $opp->oid."&cid=$usid&acct=$uacct&mas=$masid"; ?>"><?php echo $opp->o_name?$opp->o_name:'(unlabelled)'; ?></a></td>
					<td><?php echo $opp->o_facility; ?></td>
					<td><?php echo $opp->o_city; ?></td>
					<td><?php echo $opp->o_state; ?></td>
					<td><?php echo $opp->specialty; ?></td>
					<td><?php echo $opp->o_datemod; ?></td>
					<td><?php echo $opp->date_added; ?></td>
				</tr>
<?php		} ?>
			</tbody></table>
<?php
			$opps->close();
	 	} else echo "<p class='nodescript'>They have no active opportunities. You can create one for them using the above form.</p>";
		$opps = $db->query($sqlhis);
?>
        <h3>History</h3>
<?php	if( $opps && $opps->num_rows ) { ?>
	        <p class="nodescript">The list of all their cancelled, held or filled searches:</p>
			<table><thead><tr><th>Label</th><th>Facility</th><th>City</th><th>State</th><th>Specialty</th><th>Status</th><th>Modified</th><th>Added</th></tr></thead><tbody>
<?php		while( $opp = $opps->fetch_object() ) { ?>
				<tr>
					<td><a href="opportunadmin.php?oid=<?php echo $opp->oid."&cid=$usid&acct=$uacct&mas=$masid"; ?>"><?php echo $opp->o_name?$opp->o_name:'(unlabelled)'; ?></a></td>
					<td><?php echo $opp->o_facility; ?></td>
					<td><?php echo $opp->o_city; ?></td>
					<td><?php echo $opp->o_state; ?></td>
					<td><?php echo $opp->specialty; ?></td>
					<td><?php echo $stus[$opp->status]; ?></td>
					<td><?php echo $opp->o_datemod; ?></td>
					<td><?php echo $opp->date_added; ?></td>
				</tr>
<?php		} ?>
			</tbody></table>
<?php
			$opps->close();
	 	} else echo "<p class='nodescript'>They have no inactive opportunities.</p>";
		$opps = $db->query($sqlhis);
?>
<?php 		} // ooid
			} // ACCESS
			else echo "<p class='nodescript'>Access Denied</p>";
		} // UUID
	  else showLoginForm();
	$style->ShowFooter();
?>
