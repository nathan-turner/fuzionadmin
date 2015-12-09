<?php
    require("globals.php5");
    require("cookies.php5");
    // $UUID <> 0 if auth
    $redir = ''; $result = true; $mesg = '';
	//$formpage = 1;
	$action = $_REQUEST['key']; 	// encoded file name
	$id = $_REQUEST['id'];
	$docid = $id;	// alias
	$lid = $_REQUEST['lid'];
	$yer = 2005; // must be 2005
	$pos = $_REQUEST['pos'];
	$peek = $_REQUEST['peek'];
	$very= $_REQUEST['ck']; // true = show checkins
	$showcv = 0;
	if( $action && is_numeric($id) ) try {
		//$dumbie = strpos($_SERVER['HTTP_USER_AGENT'],'MSIE');
		$refer = strpos($_SERVER['HTTP_REFERER'],'cvfile.php');
		$db = db_career();
		$doc = new Physician($db,$id); 
		$cv = $doc->getcv();
		if( !$cv ) throw new Exception("No CV # $id", __LINE__);
		// access 0 - public, 1 - to whom I applied only, 2 - private (me only)
		if( $cv->cv_status != 0 && !$ACCESS ) throw new Exception("Access Denied", __LINE__);
		// here, on this site status==1 effectively means the same as status==2
		$key = substr(md5($cv->filename.$doc->fname.$doc->lname),0,8);
		if( $key !== $action ) throw new Exception("Invalid Link", __LINE__);
		if( /*!$dumbie ||*/ $refer ) { // not MSIE or second pass
			$doc->sendcv($cv);
			exit;
		}
		else $showcv = $id;	// msie would not let save files from such links, we need to show some page first
	}
	catch(Exception $e) {
		$result = false;
		$mesg = 'Can not send CV: '.$e->getMessage().' ('.$e->getCode().')';
	}		
	$cvacc = 0;
	if( $UUID && $ACCESS && $docid ) try {
		if( !$db ) $db = db_career();
		if( !$doc ) $doc = new Physician($db,$docid);
		if( !$cv ) $cv = $doc->getcv();
		$key = substr(md5($cv->filename.$doc->fname.$doc->lname),0,8);
		if (isset($_POST['submit']) || isset($_POST['submit1']) || isset($_POST['submit2'])) {
			// do stuff
			if( $_REQUEST["permit"] === "yes" ) {
				if( is_uploaded_file($_FILES['userfile']['tmp_name']) ) {
						$file_size = $_FILES['userfile']['size'];
						if( !$file_size || $file_size > 2*1024*1024. ) throw new Exception ('File is too big. Size limit is 2Mb',__LINE__);
						if( $cv ) // replace
							unlink("$STORAGE_DIR/$cv->internal");
						$doc->savecv($_FILES['userfile']);
						$cv = $doc->getcv();
						$key = substr(md5($cv->filename.$doc->fname.$doc->lname),0,8);
				}
				$doc->setcvaccess($_POST['accss']);
				if( $cv ) $cv->cv_status = $_POST['accss'];
				if( $mesg ) {
					$result = false;
					$mesg = "Please correct the following before proceeding: $mesg";
				}
			}
			elseif( !isset($_POST['submit2']) ) throw new Exception ('You must have their permission to proceed',__LINE__);
			if( $result && (isset($_POST['submit1']) || isset($_POST['submit2']))) { 
				$redir = "showdocpc.php?id=$docid&lid=$lid&ck=$very$peekarg&pos=$pos";
				//$mesg = "CV updated, please wait a moment... If automatic redirection does not work, please <a href=\"index.php\">click here to proceed</a>.";
			}
		} // not submit
	}
	catch(Exception $e) {
		$result = false;
		$mesg = 'CV update problem: '.$e->getMessage().' ('.$e->getCode().')';
	}		
	if( $result && $redir ) {
		header("Location: $redir");
		exit;
	}
	
	//DELETE CV
		if($_GET["delete"]=="cv"){
		
		$id=urldecode($_GET["id"]);
		if($id>0){
			$db = db_career();
			$sql = "delete from cvs where cv_ph_id=$id LIMIT 1";
			//echo $sql;			
			$result = $db->query($sql);
			echo "<p style='color:red'>CV DELETED</p>";
		}
		}

	$style = new OperPage('Physician Profile',$UUID,'residents','','');
	$style->Output();
	if( $mesg ) echo "<p id='".($result?'warning':'error')."_msg'>$mesg</p>"; 
	if ($UUID) {
		echo "<h3>Dr. $doc->fname $doc->lname's CV</h3>";
?>


<?php
		if( $cv ) {
			$cvacc = $cv->cv_status;
?>

			<h3>A current C.V. was uploaded on <?php echo formatDateMod($cv->cv_datemod); ?>: <?php 
				echo "<a href=\"cvfile.php?key=$key&amp;id=$docid\">".$cv->filename.'</a>'; ?></h3>
<?php echo " (<a href='?id=$docid&delete=cv'>Click Here to Delete CV</a>)";?>				
<?php	} // cv ?>	
<p>Please obtain their updated C.V. in Microsoft&reg; Word (.DOC) or Adobe&reg; PDF format. Other formats are acceptable, but not recommended. File size should not exceed 2 MB.</p>
          
   <div id="formdiv" >
	<form method="post" action="cvfile.php" enctype="multipart/form-data">
                  <input name="id" type="hidden" id="id" value="<?php echo $docid; ?>">
                  <input name="lid" type="hidden" id="lid" value="<?php echo $lid; ?>">
                  <input name="pos" type="hidden" id="pos" value="<?php echo $pos; ?>">
                  <input name="peek" type="hidden" id="peek" value="<?php echo $peek; ?>">
                  <input name="ck" type="hidden" id="ck" value="<?php echo $very; ?>">
   <table  style="width: 90%"  border="0" cellspacing="0">
	   <td colspan="2">
	C.V. File: 
	    <input type="file" name="userfile" size="40"><br /><br />
	Share their CV as: <label><input type="radio" name="accss" value="0" <?php if( $cvacc == 0 ) echo "checked"; ?>> Public</label> 
		<label><input type="radio" name="accss" value="1" <?php if( $cvacc == 1 ) echo "checked"; ?>> Restricted</label> 
		<label><input type="radio" name="accss" value="2" <?php if( $cvacc == 2 ) echo "checked"; ?>> Private</label> 
	<input type="submit" name="submit" value="Upload/Update"><br>
	I have Dr. <?php echo "$doc->fname $doc->lname"; ?>'s permission to post CV online: 
	<input type="checkbox" name="permit" value="yes" /> Yes
	</td>
	</tr>
     <tr>
	 <td><input name="submit2" type="submit" id="submit2" value="&lt;&lt;Back" width="74" style="width:74px " /></td>
	 <td align="right">&nbsp;</td>
	 </tr>
	</table>
	</form>
</div>
<address>
<strong>Public</strong> access means that anybody who knows a special link to their C.V., and also our clients who post their opportunities on this web-site, can download and see it. <strong>Restricted</strong> access means that their C.V. is visible only to employers for whose opportunities they respond through the response form on our site. <strong>Private</strong> is visible to no one except us and a doctor. To change access level of already uploaded C.V., select new access level below and press <em>Upload/Update</em> button with <em>C.V. File</em> name left blank.
</address>
<?php 
	}
	elseif( $showcv ) {
?>
			<p>Dr. <?php echo $doc->lname; ?>'s C.V.: <?php 
				echo "<a href=\"cvfile.php?key=$action&amp;id=$showcv\">".$cv->filename.'</a> ('.intval((int)$cv->filesize/1024).' Kb, click on file name to download)'; ?></p>
			<address>
			<em>Security Notice</em>: Please note that the downloadable file was provided by above mentioned user. Fuzion Health Group and PhysicianCareer.com did not check it for viruses or other potential harmful content and do not take any responsibilities for consequences of downloading that file. By downloading the file, you express agreement with our service's <a href="TC.php">T&amp;C</a>. For your security, please always have a reputable anti-virus software active and updated.
			</address>
<?php
	}
	else showLoginForm(); // UUID
	$style->ShowFooter();
?>
