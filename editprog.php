<?php
	// Ready 5/4/7 SL
    require("globals.php5");
	define(PG_SIZE,90);
    require("cookies.php5");
	$mesg = '';
	$page= $_REQUEST['page'];
	$statesel= $_POST['statesel']; 
	$cities = $_POST['cities'];
	$likes = $_POST['likes'];
	$yer = $_REQUEST['y']; // not used for access - popup indicator only
	if( empty($statesel) ) $statesel = $_REQUEST['st'];
	if( empty($cities) ) $cities = $_REQUEST['ci'];
	if( empty($likes) ) $likes = urldecode($_REQUEST['li']);
	if( !$page || !is_numeric($page) ) $page = 0;
	if( $UUID ) try {
		$resdb = db_resident(2007);
		if( $_POST['submit'] && $ACCESS >=50 ) {
			$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
			extract($strippost,EXTR_SKIP);
			if( !is_numeric($prog_id) || strlen($prog_id) != 6 ) throw new Exception('Program ID is invalid',__LINE__);
			$phone = preg_replace('/[^0-9]/','',$phone);
			if( $editf )
				$sql = "update resprograms set program=".chknul($program).",addr1=".chknul($addr1)
				.",city=".chknul($city).",state='$state',zip=".chknul($zip).",dir_name=".chknul($dir_name)
				.",phone=".chknul($phone).",uid_mod=$UUID where prog_id='$prog_id'";
			elseif( $ACCESS < 400 ) throw new Exception('Insufficient Access Level',__LINE__);
			else $sql = "insert into resprograms values ('$prog_id',".chknul($program).",".chknul($addr1)
				.",".chknul($city).",'$state',".chknul($zip).",".chknul($dir_name).","
				.chknul($phone).",$UUID,NULL)";
			$result = $resdb->query($sql);
			if( !$result ) throw new Exception(DEBUG?"$resdb->error : %sql":'Could not add/edit program',__LINE__);
			$resdb = db_amalist();
			$result = $resdb->query($sql);
			if( !$result ) throw new Exception(DEBUG?"$resdb->error : %sql":'Could not add/edit AMA program',__LINE__);
		} // update done, now to select
		$wher = '';
		if( !empty($statesel) ) $wher = " where state >= '$statesel'";
		if( !empty($cities) ) $wher .= ($wher?' and ':' where ')." city like '$cities%'";
		if( !empty($likes) ) $wher .= ($wher?' and ':' where ')." program like '%$likes%'";
		$result = $resdb->query("select count(*) from resprograms $wher");
		if( !$result ) throw new Exception(DEBUG?"$resdb->error : $wher":'Can not execute query',__LINE__);
		list($totalcount) = $result->fetch_row();
		if( $page > $totalcount ) $page = 0;
		$result = $resdb->query("select * from resprograms $wher order by state,city,program LIMIT $page, ".PG_SIZE);
		if( !$result || !$result->num_rows ) $mesg = 'Something wrong. 0 programs found.';
		if( $result->num_rows < PG_SIZE ) $lastpage = true;

	}
	catch(Exception $e) {
		$mesg = 'Error found: '.$e->getMessage().' ('.$e->getCode().')';
	}
	$style = new OperPage('Residency Programs',$UUID,'admin','editprog');
	$scrip = '';
	if( $ACCESS >=50 ) $scrip .= '<script type="text/javascript" src="areas.js"></script>';
	$scrip .= <<<HereScriptA

<style type="text/css"><!--
.fakea {
	cursor: hand;
	color: #0033CC;
}
.style1 {color: #333333}
.style2 {
	color: #333333;
	border-top: thin solid #3399CC;
	border-right: thin none #3399CC;
	border-bottom: thin solid #3399CC;
	border-left: thin none #3399CC;
}
-->
</style>
<script language="JavaScript" type="text/JavaScript"><!--
HereScriptA;
	if( $ACCESS >= 400 ) {
		$scrip .= <<<HereScriptB

function newprogram() {
   document.getElementById('formdiv').style.display = 'block';
   document.getElementById('newspan').style.display = 'none';
   document.getElementById('prog_id').readonly = false;
   return true;
}
HereScriptB;
	}
	if( $ACCESS >=50 ) {
		$scrip .= <<<HereScriptC

function editprogram(prog_id,state,city,program,addr1,zip,phone,dir_name) {
   document.getElementById('editf').value = '1';
   document.getElementById('prog_id').value = prog_id;
   document.getElementById('prog_id').readOnly = true;
   //document.getElementById('state').value = state;
   document.newform.state.value = state;
   document.getElementById('city').value = city;
   document.getElementById('program').value = program;
   document.getElementById('addr1').value = addr1;
   document.getElementById('zip').value = zip;
   document.getElementById('phone').value = phone;
   document.getElementById('dir_name').value = dir_name;
   document.getElementById('formdiv').style.display = 'block';
HereScriptC;
		if( $ACCESS >=400 ) $scrip .= "   document.getElementById('newspan').style.display = 'none';\r\n";
		$scrip .= <<<HereScriptD
   document.getElementById('program').scrollIntoView();
   document.getElementById('program').focus();
   return true;
}
HereScriptD;
	} // ACCESS >= 50
	$scrip .= "// -->\r\n</script>\r\n";

	$style->Output($scrip);

	if( $UUID ) {
?>
<h1>Residency Programs</h1>
<form action="editprog.php" method="post" name="stateform">
<p>Total programs: <?php echo $totalcount; ?>. Shown are from <?php echo $page+1; ?> to <?php echo $page+$result->num_rows; ?>.<br>
Filter programs by name: <input name="likes" type="text" value="<?php echo stripslashes($likes); ?>" size="30" maxlength="30">, and/or<br>
Jump to programs in city: <input name="cities" type="text" value="<?php echo $cities; ?>" size="15" maxlength="30"> 
and/or state: 
<?php echo showStateList($resdb,$statesel,'statesel'); ?> <input name="stsel" type="submit" value="Go!"></p>
</form>

<table width="90%" border="0">
<tr bgcolor="#CCCCCC">
<td class="style1">ID</td>
<td class="style1">State</td>
<td class="style1">City</td>
<td class="style1">Program</td>
<td class="style1">Address</td>
<td class="style1">Zip</td>
<td class="style1">Phone</td>
<td class="style1">Director</td>
</tr>
<?php
		if( $mesg ) echo "<tr><td colspan=8 style='color:red; text-align: center'>$mesg</td></tr>";
		else while( $row = $result->fetch_assoc() ) {
			extract($row);
?>
<tr>
<td <?php 
	if( $ACCESS >=50 ) {
?>onClick="editprogram(<?php
   echo "'$prog_id','$state','".addslashes($city)."','".addslashes($program).
   		"','".addslashes($addr1)."','$zip','$phone','".addslashes($dir_name)."'";
  ?>)" class="fakea"<?php 
  	}
  ?>>
  <?php echo $prog_id; ?></td>
<td><?php echo $state; ?></td>
<td><?php echo stripslashes($city); ?>&nbsp;</td>
<td><?php echo stripslashes($program); ?></td>
<td><?php echo stripslashes($addr1); ?>&nbsp;</td>
<td><?php echo $zip; ?>&nbsp;</td>
<td><?php echo $phone; ?>&nbsp;</td>
<td><?php echo stripslashes($dir_name); ?>&nbsp;</td>
</tr>
<?php
		}
		$result->free();
		$lik=urlencode(stripslashes($likes));
?>
<tr>
 <td bgcolor="#E8E8EC"><?php
		if( $page ) echo "<a href='editprog.php?y=$yer&st=$statesel&li=$lik&ci=$cities&page=".($page-PG_SIZE)."'>Prev</a>";
		else echo '&nbsp;';
 ?></td>
 <td colspan="6" bgcolor="#E8E8EC" align="center"><?php 
		for( $i = 0,$j = 1; $i < $totalcount; $i += PG_SIZE, $j++ ) 
			echo $i == $page? "$j ":"<a href='editprog.php?y=$yer&st=$statesel&li=$lik&ci=$cities&page=$i'>$j</a> ";
 ?></td>
 <td align="right" bgcolor="#E8E8EC"><?php
		if( !$lastpage ) echo "<a href='editprog.php?y=$yer&st=$statesel&li=$lik&ci=$cities&page=".($page+PG_SIZE)."'>Next</a>";
		else echo '&nbsp;';
 ?></td>
</tr>
</table>
<?php if( $ACCESS >=400 ) { ?>
<div id="newspan" onClick="newprogram()" class="fakea">Quick Add Program</div>
<?php } 
	  if( $ACCESS >=50 ) { 
?>
<div id="formdiv" style="display:none"><br />
<form action="editprog.php" method="post" name="newform" onSubmit="return checkPhoEx(phone,'Phone',state,'State');">
  <input name="y" type="hidden" id="y" value="<?php echo $yer; ?>">
  <input name="st" type="hidden" id="st" value="<?php echo $statesel; ?>">
  <input name="ci" type="hidden" id="ci" value="<?php echo $cities; ?>">
  <input name="likes" type="hidden" id="li" value="<?php echo $likes; ?>">
  <input name="editf" type="hidden" id="editf" value="0">
  <input name="page" type="hidden" id="page" value="<?php echo $page; ?>">
  <table width="90%"  border="0">
    <tr>
      <td>ID:</td>
      <td><input name="prog_id" type="text" id="prog_id" size="10" maxlength="6"></td>
      <td>Name:</td>
      <td><input name="program" type="text" id="program" size="35" maxlength="100"></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      </tr>
    <tr>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>Address:</td>
      <td><input name="addr1" type="text" id="addr1" size="35" maxlength="100"></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>City:</td>
      <td><input name="city" type="text" id="city" maxlength="50"></td>
      <td>State:</td>
      <td><?php echo showStateList($resdb); ?></td>
      <td>Zip:</td>
      <td><input name="zip" type="text" id="zip" size="15" maxlength="10"></td>
      </tr>
    <tr>
      <td>Phone:</td>
      <td><input name="phone" type="text" id="phone" maxlength="16" onChange="checkPhoEx(phone,'Phone',state,'State')"></td>
      <td>Director:</td>
      <td><input name="dir_name" type="text" id="dir_name" size="35" maxlength="50"></td>
      <td>&nbsp;</td>
      <td align="right"><input type="submit" name="submit" value="Submit"></td>
      </tr>
  </table>
</form></div>

<?php
	} // access 50
			if( $yer ) echo '<p><br /><a href="javascript:window.close()">Close window</a></p>';
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
