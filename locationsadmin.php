<?php
  require("globals.php5");
  require("cookies.php5");
  // $UUID <> 0 if auth
  $mesg = ''; $next=false;
  $redir="";
  $result = true; 
	$uacct = $_REQUEST['acct'];
	$usid = $_REQUEST['cid'];
	$masid = $_REQUEST['mas'];
	if( !is_numeric($usid) ) $usid = $UUID;
	if( !is_numeric($masid) ) $masid = $UUID;
	if( !is_numeric($uacct) ) $uacct = 0;

	if( $UUID && $ACCESS >= 200 ) try {
		$db = db_career();
		$client = $db->query("select firstname, lastname, company from clients where uid=$usid");
		if( $client ) list($cfirst,$clast,$cco) = $client->fetch_row();
		$client->free();
		$l_id = intval($_REQUEST['l_id']);

		if( $_POST['submit'] ) { // add new
			$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
			extract($strippost,EXTR_SKIP);
			
			// any state & city updated
			if( !empty($city) && $state && $state != '--' ) {
				$next = true; $city = addslashes($city); $state = addslashes($state);
				$sql ="insert into locations (l_city,l_state,l_uid,l_acct,exp_date) values('$city','$state',$usid,$uacct,NULL)";
				$result = $db->query($sql);
				if( !$result ) throw new Exception(DEBUG?"$db->error: $sql":'Can not insert locations',__LINE__);
				$l_id = $db->insert_id; 
				$locations = new PCLocation ($db,$l_id);
			}
		}
		elseif( $_POST['done'] || $_POST['upload_pic_0'] || $_POST['upload_pic_1'] || $_POST['upload_pic_2'] || 
			   $_POST['upload_pic_3'] || $_POST['upload_pic_4'] || $_POST['del_pic_0'] || $_POST['del_pic_1'] || $_POST['del_pic_2'] || 
			   $_POST['del_pic_3'] || $_POST['del_pic_4'] ) { // update one
			$locations = new PCLocation ($db,$l_id);
			$l_description = $_POST['l_description']; // allow dbl quotes here
			$l_commdescr = $_POST['l_commdescr'];	  // ... and here
			$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
			extract($strippost,EXTR_SKIP);
			$next=false;
			//$locations = new PCLocation ($db,$l_id);
			
			if( $l_state != '--' ) $locations->l_state= $l_state;
			if( !empty($l_city) ) $locations->l_city = $l_city;
			$locations->l_zip= $l_zip;
			if( $locations->status != $l_status ) {
				$locations->status = $l_status;
				if( $l_status == 1 ) $locations->reactivate($opp_too);
				if( $l_status == 0 ) $locations->deactivate();
			}
			$locations->l_facility = $l_facility;
			
			/*
			$practicetype ='';
			if (isset($practicetype_1)) $practicetype .= $practicetype_1.','; 
			if (isset($practicetype_2)) $practicetype .= $practicetype_2.','; 
			if (isset($practicetype_3)) $practicetype .= $practicetype_3.','; 
			if (isset($practicetype_4)) $practicetype .= $practicetype_4.','; 
			if (isset($practicetype_5)) $practicetype .= $practicetype_5.','; 
			if (isset($practicetype_6)) $practicetype .= $practicetype_6.','; 
			if (isset($practicetype_7)) $practicetype .= $practicetype_7.','; 
			if (isset($practicetype_8)) $practicetype .= $practicetype_8.','; 
			if (isset($practicetype_9)) $practicetype .= $practicetype_9.','; 
			
			if( !empty($practicetype) ) $locations->practicetype = $practicetype; // it's a varchar, not a set
			// to modify else $mesg = "no practice type";
			*/
			
			/*$l_amen = '';
			if( isset($l_amen_1)) $l_amen .= $l_amen_1;			
			if (isset($l_amen_2)) $l_amen .= ($l_amen?',':'').$l_amen_2;
			if (isset($l_amen_3)) $l_amen .= ($l_amen?',':'').$l_amen_3;
			if (isset($l_amen_4)) $l_amen .= ($l_amen?',':'').$l_amen_4;
			if (isset($l_amen_5)) $l_amen .= ($l_amen?',':'').$l_amen_5;
			$locations->l_amen = $l_amen;
			
			$l_school = '';
			if (isset($l_school_1)) $l_school .= $l_school_1;
			if (isset($l_school_2)) $l_school .= ($l_school?',':'').$l_school_2;
			if (isset($l_school_3)) $l_school .= ($l_school?',':'').$l_school_3;
			$locations->l_school = $l_school;*/
			
			$l_commu2 = '';
			if( isset($l_commu2_1)) $l_commu2 .= $l_commu2_1;			
			if (isset($l_commu2_2)) $l_commu2 .= ($l_commu2?',':'').$l_commu2_2;
			if (isset($l_commu2_3)) $l_commu2 .= ($l_commu2?',':'').$l_commu2_3;
			$locations->l_commu2 = $l_commu2; // 2!!!
			
			$locations->l_description = $l_description;
			$locations->l_commdescr = $l_commdescr;
			$locations->save();
		} // done submit
		elseif( $_REQUEST['view_pic'] ) {
			$locations = new PCLocation($db,$l_id);
			$nom = intval($_REQUEST['view_pic'])-1;
			$locations->showpic($nom);
			exit;
		}
		elseif( $_REQUEST['action'] === 'update' ) { // return from the picture upload form
			$next = true;
			$locations = new PCLocation ($db,$l_id);
		}
		// all pre-processing done
		if( $_POST['upload_pic_0'] || $_POST['upload_pic_1'] || $_POST['upload_pic_2'] || 
			   $_POST['upload_pic_3'] || $_POST['upload_pic_4'] ) {
		       if( !isset($locations) ) $locations = new PCLocation ($db,$l_id);
			  // $locations->save();
		       if( $_POST['upload_pic_0']) $upload_pic = 1 ;
               if( $_POST['upload_pic_1']) $upload_pic = 2 ;
               if( $_POST['upload_pic_2']) $upload_pic = 3 ;
			   if( $_POST['upload_pic_3']) $upload_pic = 4 ;
			   if( $_POST['upload_pic_4']) $upload_pic = 5 ;
			   $next= true;
		} 
		elseif( $_POST['done'] && !empty($_POST['del_pics']) ) {
		       if( !isset($locations) ) $locations = new PCLocation ($db,$l_id);
		       if( strpos($_POST['del_pics'],"0,")!==false ) $locations->delpic(0);
               if( strpos($_POST['del_pics'],"1,")!==false ) $locations->delpic(1);
               if( strpos($_POST['del_pics'],"2,")!==false ) $locations->delpic(2);
			   if( strpos($_POST['del_pics'],"3,")!==false ) $locations->delpic(3);
			   if( strpos($_POST['del_pics'],"4,")!==false ) $locations->delpic(4);
			   //$locations->delpic($del_pic);
			   $next= true;
		} // upload_pic_0
		if ($_POST['uppic']) {
			$next =true;
			$numpic = $_REQUEST['num_pic'];
			$locations = new PCLocation ($db,$l_id);   
			if (is_uploaded_file($_FILES['userfile']['tmp_name']) ) {
				$file_size = $_FILES['userfile']['size'];
		    	$file_type = $_FILES['userfile']['type'];
				if( substr($file_type,0,6) != 'image/' ) throw new Exception ('Pictures only, please',__LINE__);
				if( !$file_size || $file_size > 65535 ) $needresize = true;
			
				if( !$db ) $db = db_career();
				list($iw, $ih, $itype) = getimagesize($_FILES['userfile']['tmp_name']);
				$maxwid = $numpic==4? 360:520;
				if( $needresize || $iw > $maxwid || $ih > 520 ) {
					if( $itype == IMAGETYPE_JPEG ) $im = @imagecreatefromjpeg($_FILES['userfile']['tmp_name']);
					else if( $itype == IMAGETYPE_PNG ) $im = @imagecreatefrompng($_FILES['userfile']['tmp_name']);
					else if( $itype == IMAGETYPE_GIF ) $im = @imagecreatefromgif($_FILES['userfile']['tmp_name']);
					else throw new Exception ('Unsupported image type! Please select JPG, PNG or GIF picture.',__LINE__);
					
					if( !$im ) throw new Exception ('Create from file failed!',__LINE__);
					
					// $newx, $newy
					if( $iw > $ih ) { $newx = 360; $newy = intval($ih*360.0/$iw); } else { $newy = 360; $newx = intval($iw*360.0/$ih); }
					
					if( $itype == IMAGETYPE_JPEG ) {
						$im2 = imagecreatetruecolor($newx,$newy); 
						imagecopyresampled($im2, $im, 0, 0, 0, 0, $newx, $newy, $iw, $ih);
					}
					else {
						$im2 = imagecreate($newx,$newy);
						//imagecopyresampled($im2, $im, 0, 0, 0, 0, $newx, $newy, $iw, $ih);
						imagecopyresized($im2, $im, 0, 0, 0, 0, $newx, $newy, $iw, $ih);
					}
					
					// start buffering
					ob_start();
					// output jpeg (or any other chosen) format & quality
					if( $itype == IMAGETYPE_JPEG ) imagejpeg($im2, NULL, 65);
					if( $itype == IMAGETYPE_PNG ) imagepng($im2,NULL); // imagepng($im,NULL,6);
					if( $itype == IMAGETYPE_GIF ) imagegif($im2,NULL);
					// capture output to string
					$filedata = ob_get_contents();
					$file_size = strlen($filedata);
					// end capture
					ob_end_clean();
					
					// be tidy; free up memory
					imagedestroy($im); imagedestroy($im2);
				} // needresize
				else {
					$fp = fopen($_FILES['userfile']['tmp_name'], "rb");
					if (!feof($fp)) {
						// Make the data mysql insert safe
						$filedata = fread($fp, 65535);
					}
					fclose($fp);
				} // else needresize
			    $locations->setpic($numpic,$filedata,$file_size,$file_type);
  	     	} // uploaded_file
	    } // POST Uppic
		if( !$next ) {
			$locat = $db->query("select l_id,l_facility,l_city,l_state,status,l_uid, l_datemod from locations where l_uid = $usid or l_acct = $uacct order by status desc,l_city,l_state");
			$totals = $locat? $locat->num_rows: 0;
	  	} // next
	} // uuid
	catch (Exception $e) {
		$result = false;
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
	}
			
$scrip = <<<TryMe
<script type="text/javascript" src="/ckeditor/ckeditor.js"></script>
<script language="JavaScript" type="text/JavaScript"><!--

function confirmDel(theLink, theFile) {
    // Confirmation is not required if browser is Opera (crappy js implementation)
    if (typeof(window.opera) != 'undefined') {
        theLink.href += '&action=del';
        return true;
    }

    var is_confirmed = confirm('Are you sure you want to deactivate\\n"' + theFile + '"');
    if (is_confirmed) {
        if ( typeof(theLink.href) != 'undefined' ) {
            theLink.href += '&action=del';
        }
    }

    return is_confirmed;
}

function opptoocheck() {
	var opt1 = document.getElementById("stat_1");
	var opt3 = document.getElementById("stat_2");
	var opp2 = document.getElementById("opp_too");
	if( opt1.selected || opt3.selected ) { opp2.checked = false; opp2.disabled = false; }
	else { opp2.checked = true; opp2.disabled = true; }
	return true;
}

function delpic(inp) {
	//alert(inp);
	var img = document.getElementById("img_pic_"+inp);
	img.src = "images/pic_drop.png";
	var hid = document.getElementById("del_pics");
	hid.value += inp+",";
	return true;
}

// -->
</script>
TryMe;

	$style = new OperPage('Locations',$UUID,'admin','locations',$redir);
	$style->Output($scrip);
    if( $UUID ) {
		// print_r($_POST); // DEBUG
?>
        <h1>Manage Locations for <?php echo "<a href=\"custedit.php?cid=$masid\">$cfirst $clast</a> ($cco)"; ?></h1>
<?php
		if( $mesg ) echo "<p id='".($result?'warning':'error')."_msg'>$mesg</p>"; 
?>
		
<?php 	
	    if( $ACCESS >= 200 ) {
		    if (!$next) {

?>		   
               <p> You can add and modify their locations here. Changing options on location doesn&rsquo;t automatically change each opportunity. Changing pictures or descriptions on the location does indeed automatically change each opportunity listed under that location.Locations are shared among all sub-accounts; entries created by other members of their team are marked with asterisk (<span style="color: green">*</span>). To manage their opportunities, go to <a href="opportunadmin.php?cid=<?php echo "$usid&acct=$uacct&mas=$masid"; ?>">this page</a>. </p>
               <h3>Create new location</h3>
               <form  method="post" action="locationsadmin.php">
			<input name="cid" type="hidden" value="<?php echo $usid; ?>">
			<input name="mas" type="hidden" value="<?php echo $masid; ?>">
			<input name="acct" type="hidden" value="<?php echo $uacct; ?>">
               <table cellspacing="0">
                  <tr>
                  <td align = right>City:</td>
                  <td><input type="text" name="city" size="25" maxlength="30"/></td>
                  </tr>
        
                  <tr>
                   <td align = right>State: </td>
                   <td><?php echo showStateList($db,$state,'state')?></td>
                  </tr>
                  <tr valign = "bottom">
                  <td></td>
                  <td> <input name="submit" type="submit" value="Create" /> </td>
                 </tr>
              </table>
              </form>
           <h3>List of current locations</h3>
<?php      if( $totals ) {
              // display all locations already updated
?> 
		  <table width="80%" border="1" cellpadding="1" cellspacing="0"><thead>
	      <th>Facility</th><th>City</th><th>State</th><th>Status</th><th>Last Mod.</th></thead>
	      <tbody>
<?php 
 	      while( $row = $locat->fetch_object() ) {
?>
	     <tr>
		<td><a href="locationsadmin.php?l_id=<?php echo $row->l_id."&cid=$usid&acct=$uacct&mas=$masid"; ?>&action=update"><?php echo $row->l_facility?$row->l_facility:'(no name)'; ?></a><?php if( $row->l_uid != $usid ) echo "<span style='color: green' title='Shared location'>*</span>"; ?></td>
		<td><?php echo $row->l_city; ?></td>
        <td><?php echo $row->l_state; ?></td>
        <td><?php echo $row->status==1?"Active":"Inactive"; ?></td>
		<td style="width:170px"><?php echo $row->l_datemod; ?></td>
	</tr>
 <?php 
  } // while
?>
	</tbody>
   
	</table>
<?php		    
	      } else echo "<p>They have not created any locations yet!</p>";
        } // $next (first page)
		else {
		  
		   if ($locations) {
		   	echo "<h2>".htmlspecialchars($locations->l_facility,ENT_COMPAT | ENT_HTML5,'UTF-8')."<br />$locations->l_city, $locations->l_state</h2>";
		    //$locations = new PCLocation ($db,$l_id);
		      if ( ($upload_pic>=1) && ($upload_pic<=5)) {
			      $upload_pic--;
?>			     <p>Upload a picture for the above location. It can be a company logo, small snapshot of a facility, landscape, cityscape, icon, etc. Large pictures (with file sizes of over 64 Kb) will be automatically resized to maximum width or height of 256 pixels, whichever is bigger. Very large pictures with resolution of 8 Megapixels or more may fail to upload, in that case you can use image manipulation software, such as <a href="http://picasa.google.com/" target="_blank" title="Free photo editing software from Google">Picasa</a>&trade; (free), to resize your image to a smaller resolution. We also suggest you to change compression ratio or &quot;quality factor&quot; of your image files to 50%, &quot;medium&quot;. If you don't want your pictures to automatically resize, please make sure the file you are about to upload is smaller than 64 Kb. </p>
<p>Please do not upload any pictures that are copyright-protected and for which you don&rsquo;t have a permission to publish. Select JPEG, GIF or PNG image on your computer. Or, <a href="locationsadmin.php?l_id=<?php echo $l_id."&cid=$usid&acct=$uacct&mas=$masid"; ?>&action=update">click here to return</a> to the location.</p>
<form method="post" action="locationsadmin.php" enctype="multipart/form-data">
				 <input name="l_id" value="<?php echo $l_id; ?>" type="hidden">
				<input name="cid" type="hidden" value="<?php echo $usid; ?>">
				<input name="mas" type="hidden" value="<?php echo $masid; ?>">
				<input name="acct" type="hidden" value="<?php echo $uacct; ?>">
				 <input name="num_pic" value="<?php echo $upload_pic; ?>" type="hidden">
				 Picture file name <input type="file" name="userfile" size="40">
	<input type="submit" name="uppic" value="Upload">
    <input type="hidden" name="MAX_FILE_SIZE" value="6553600" />
</form>
    <?php

			  } else {
			     //$locations = new PCLocation ($db,$l_id);
?>
		  <p> Here is where you fill out the information to describe a location for their opportunities. To make it easy on you, when you create an opportunity or multiple opportunities for each location, most of this information will automatically be inserted.
Changing options on a location doesn&rsquo;t automatically change each opportunity. Changing pictures or descriptions on the location does indeed automatically change each opportunity listed under that location.</p>
		  <div id="formdiv">
		    <form name="theform" method="post" action="locationsadmin.php">
			<input type="hidden" name="l_id" value="<?php echo $l_id; ?>">
			<input name="cid" type="hidden" value="<?php echo $usid; ?>">
			<input name="mas" type="hidden" value="<?php echo $masid; ?>">
			<input name="acct" type="hidden" value="<?php echo $uacct; ?>">
            <table>
              <tr>
                <td>ID#:</td>
                <td> 
                   <?php echo $l_id; ?>, Expires: <?php echo $locations->exp_date; ?>
                </td>
              </tr>
              <tr>
                <td>Facility:</td>
                <td> 
                   <input type="text" name="l_facility" id="l_facility" value="<?php echo htmlspecialchars($locations->l_facility,ENT_COMPAT | ENT_HTML5,'UTF-8'); ?>" size="50" maxlength="100"/>
                </td>
              </tr>
              <tr>     
                <td>City*:</td>
                <td>
                <input type="text" name="l_city" id="l_city" value="<?php echo $locations->l_city; ?>" size="25" maxlength="50"/>
                </td>
              </tr>
              <tr>
                   <td >State*: </td>
                <td><?php echo showStateList($db,$locations->l_state,'l_state'); ?></td>
              </tr>
              <tr>     
                <td>Zip:</td>
                <td>
                <input type="text" name="l_zip" id="l_zip" value="<?php echo $locations->l_zip; ?>" size="10" maxlength="10"/>
                </td>
              </tr> 
              <tr>
                <td>Status:</td>
                <td><select name="l_status" id="l_status" onChange="opptoocheck()">
                 
 				<option id="stat_0" value="0" <?php if( $locations->status == 0 ) echo 'selected'; ?>>Inactive</option>
				<option id="stat_1" value="1" <?php if( $locations->status == 1 ) echo 'selected'; ?>>Active</option>
				<option id="stat_2" value="2" <?php if( $locations->status == 2 ) echo 'selected'; ?>>Expired</option>
                   </select> &nbsp;
				   <label><input name="opp_too" type="checkbox" value="1" id="opp_too"> 
				   Also adjust status of all related opportunities</label>
                </td>
              </tr>
              <tr>
              <td bgcolor="#E0E0FF">Facility Description:</td>
              <td bgcolor="#E0E0FF"> <textarea name="l_description" cols="70" rows="8" id="l_description"><?php echo ($locations->l_description); ?></textarea>				<script type="text/javascript">
				//<![CDATA[
					CKEDITOR.replace( 'l_description' );
				//]]>
				</script>
</td>
              </tr>
              <tr>
              <td> Community size:           
              </td>
              <td>
			     <label><input name="l_commu2_1" type="checkbox" value="S" title="Small/Rural"  <?php echo strpos($locations->l_commu2,'S')!==false?'checked':''; ?>>
                 Small </label>
                 <label><input name="l_commu2_2" type="checkbox" value="C" title="Medium" <?php echo strpos($locations->l_commu2,'C')!==false?'checked':''; ?>> 
                 Medium</label>
                <label><input name="l_commu2_3" type="checkbox" value="M" title="Metro Area/Big city" <?php echo strpos($locations->l_commu2,'M')!==false?'checked':''; ?>>
                Metro</label></td>
              </tr>
              <tr>
              <td>Community Description:</td>
              <td> <textarea name="l_commdescr" cols="70" rows="8" id="l_commdescr"><?php echo ($locations->l_commdescr); ?></textarea>
				<script type="text/javascript">
				//<![CDATA[
					CKEDITOR.replace( 'l_commdescr' );
				//]]>
				</script></td>
              </tr>
            <tr align="center" valign="bottom">
               <td colspan="2"><input name="done" type="submit" id="done" value="Save &amp; Return" /></td>
           </tr> 
<?php     for( $i = 0; $i <= 4; $i++ ) {
?>           <tr valign="middle"> 
			<td>Picture <?php echo $i<4?$i+1:'for Company Logo';  ?>:</td>
			<td>
            <?php if( $locations->getpicsize($i) ) {  
				?>
				<input type="submit" name="upload_pic_<?php echo $i; ?>" value="Replace" />
				<a href="locationsadmin.php?l_id=<?php echo $l_id; ?>&amp;view_pic=<?php echo $i+1; ?>" target="_blank">
				<img src="locationsadmin.php?l_id=<?php echo $l_id; ?>&amp;view_pic=<?php echo $i+1; ?>" title="Click to view" alt="Uploaded image" border="1" width="100" id="img_pic_<?php echo $i; ?>" align="absmiddle" /></a>
				<button type="button" name="del_pic_<?php echo $i; ?>" id="del_pic_<?php echo $i; ?>" value="<?php echo $i; ?>" onClick="delpic(<?php echo $i; ?>)"><img src="images/b_drop.png" border="0" title="Delete" />Discard</button>
				<?php
			} else echo "<input type=\"submit\" name=\"upload_pic_".$i."\" value=\"Upload\" />";
			 ?> </td>             
           </tr>
<?php 	  } //for ?>
           
           </table>
		   <input type="hidden" name="del_pics" id="del_pics" value="" />
           </form>
          </div>
<?php
          } // upload
		} // locations
		} // next
?>		
		        <p>&nbsp;</p>
<?php 		} // ACCESS
			else echo "<p>Access Denied</p>";
		} // UUID
	  else showLoginForm();
	$style->ShowFooter();
?>
