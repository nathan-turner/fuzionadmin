<?php
    require("globals.php5");
//	define(PG_SIZE,50);
    require("cookies.php5");
	$mesg = ''; $result = 1;
	$testonly = true; $testmode = $_REQUEST["lastmode"];
	if( $UUID && $ACCESS >= 200 ) try {
		// do stuff
		$db = db_career();
		if( ($_POST['verify'] || $_POST['submit']) && $_POST['custid'] ) {
			$custid = $_REQUEST['custid'];
			if( !$custid || !is_numeric($custid) ) throw new Exception('Customer ID required',__LINE__);
			$client = new Customer($db,$custid);
			if( $client->is_oper() ) throw new Exception('This is <strong>NOT</strong> a customer',$custid);
			if( !$client->status ) throw new Exception('This customer is <strong>NOT ACTIVE</strong>',$custid);
		}
		if( $client && $_POST['submit'] && $_POST['extype'] && $_POST['loco'] ) {
			// loco L loc, O opp
			// extype X = HMA file, K (400+) HMA link
			// userfile (X), filename (K)
			$progress = 'Initializing...<br>';
			$extype = $_POST['extype'];
			$testrun = $_POST['testrun'];
			$proceed = 0;
			if( $extype === 'X' ) {
				// X local file
				if (is_uploaded_file($_FILES['userfile']['tmp_name']) ) {
					$file_size = $_FILES['userfile']['size'];
					$file_type = $_FILES['userfile']['type'];
					if( substr($file_type,0,5) != 'text/' ) throw new Exception ('Text files only, please',__LINE__);
					//if( !$file_size || $file_size > 65535 ) $needresize = true;
					$filename = $_FILES['userfile']['tmp_name'];
					$zml = simplexml_load_file($filename);
					$proceed = 1;
				} // uploaded_file
				else throw new Exception('No File was selected',__LINE__); 
			} // X
			elseif( $extype === 'K' && $ACCESS >= 200 ) {
				// try from any URL
				// throw new Exception('Not implemented',__LINE__);
				$filename = stripslashes(trim(str_replace('"',"'",$_POST['filename'])));
				if( !$filename ) throw new Exception('No Link was submitted',__LINE__);
		        $ch = curl_init($filename); 
				curl_setopt($ch, CURLOPT_HEADER, 0);
		        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        		$output = curl_exec($ch); 
				if($output === false) throw new Exception('Curl error: ' . curl_error($ch),__LINE__);
		        curl_close($ch);      
				$zml = simplexml_load_string($output);
				$proceed = 1;
			} // K
			if( $proceed ) {
				if( $testmode != $_POST['loco'] ) $testrun = 1;
				$testonly = ! $testrun; // testonly disables real run
				if( !$zml ) throw new Exception('File load failed',__LINE__);
				
				if( $_POST['loco'] == 'L' ) {
					if( $testrun ) $testmode = 'L';
					if( $testmode != $_POST['loco'] )
					foreach($zml->location as $loco) {
					  try {
					  	$zip = addslashes(trim($loco->zip));
						$progress .= "Processing $zip... ";
						$sql = "select l_id from locations where l_acct = $client->acct and l_zip = '$zip'";
						$res = $db->query($sql);
						if( !$res ) throw new Exception(DEBUG?"$db->error : $sql":'Problem with duplicate check',__LINE__); 
						if( $res->num_rows ) { // dup
							list($locid) = $res->fetch_row(); // 1st one.
							$lo = new PCLocation($db,$locid);
						}
						else { // no dup: create new
							$city = addslashes($loco->city);
							$state = addslashes($loco->state);
							if( !empty($zip) && $state && $state != '--' ) {
								$sql ="insert into locations (l_city,l_state,l_uid,l_acct,l_zip,exp_date) values('$city','$state',$client->uid,$client->acct,'$zip',$client->exp_date)";
								$result = $db->query($sql);
								if( !$result ) throw new Exception(DEBUG?"$db->error: $sql":'Can not insert locations',__LINE__);
								$locid = $db->insert_id; 
								$lo = new PCLocation ($db,$locid);
							}
							else throw new Exception('Can not insert location: ZIP or STATE is missing',__LINE__);
						}
						if( $lo ) {
							$lo->l_facility = addslashes(htmlspecialchars( strip_tags($loco->facility)));
							$lo->l_city = addslashes($loco->city);
							$lo->l_state = addslashes($loco->state);
							$lo->l_commu2 = addslashes($loco->commu2);
							$lo->l_inderserved = $loco->underserved?1:0;
							$descr = addslashes("$loco->description");
							$lo->l_description = $descr;
							$commdescr = addslashes("$loco->commdescr");
							$lo->l_commdescr = $commdescr;
							$progress .= ' updating...';
							if( !$testrun ) { 
								$lo->save();
							} // testrun
						} // lo
						$progress .= ' Done!<br>';
				  }
				  catch(Exception $e) {
						if( $testrun ) {
							$progress .= " TEST FAILED: ".$e->getMessage().' ('.$e->getCode().')<br>';
							$mesg = "Errors detected: see below";
							$result = 0;
						}
						else //throw $e;
						{
							$progress .= " PROBLEM RECORD: ".$e->getMessage().' ('.$e->getCode().')<br>';
							$mesg = "Errors detected: see below";
							$result = 0;
						}
				  }
				} // loco
				$progress .= ' Done!<br>';
				$progress .= $testrun?'All locations were tested! See results above.':($result?'All':'Not all').' locations were imported!';
				if( $result ) $mesg = "Success!";
				if( $testonly && $testrun ) { 
					$mesg = 'FATAL errors were detected, Data run is impossible until they are fixed!';
					$progress .= '<br>' . $mesg;
				}


				} // loco L
				else { // loco O
					if( $testrun ) $testmode = 'O';


				if( !$testrun ) {
					$sql = "update importrac set jobflag=1 where jobacct=$client->acct";
					$db->query($sql);
				}
				foreach($zml->job as $job) {
				  try {
					$jid = "JOB " .addslashes($job->referencenumber);
					$progress .= "Processing $jid... ";
					$zip = addslashes(trim($job->postalcode));
					// $job->date, $job->title; $job->description, $job->city, $job->state,
					// $job->specialty, [jobtype] Permanent NOT USED, [practicetype] NOT USED
					// $job->contactprofile = client's email address
					$client = new Customer($db,0,addslashes($job->contactprofile));
					if( $client->is_oper() || $client->status != 1 ) throw new Exception("$job->contactprofile is <strong>NOT</strong> a customer",$uid);
					$spec = $hamspec["$job->specialty"];
					if( !$spec ) throw new Exception ("Warning: There is no mapping for specialty: $job->specialty, $jid, the job will be skipped",__LINE__);
					$title = addslashes("$job->title"); // a trick to unwrap CDATA
					$descr = addslashes("$job->description");
					// ok, let's check for dupes: account & ID# are the key
					$sql = "select oid from opportunities where o_acct = $client->acct and o_name = '$jid' and status=1";
					$res = $db->query($sql);
					if( !$res ) throw new Exception(DEBUG?"$db->error : $sql":'Problem with duplicate check',__LINE__); 
					if( $res->num_rows ) { // dup
						list($oppid) = $res->fetch_row(); // 1st one.
						$opp = new Opportunity($db,$oppid,0,$client->uid,$client->acct);
						$opp->o_city = addslashes($job->city);
						$opp->o_state = addslashes($job->state);
						$opp->o_zip = $zip;
						$opp->specialty = $spec;
						$opp->o_facility = htmlspecialchars( strip_tags($title) );
						$opp->description = $descr;

						$opp->o_email = addslashes($client->email);
						$opp->o_phone = addslashes($client->phone);
						$opp->o_fax = preg_replace('/[^0-9]/','',$client->fax);
						$opp->o_title = addslashes($client->title);
						$opp->o_contact = addslashes($client->firstname." ".$client->lastname);
						$progress .= ' updating...';
						if( !$testrun ) { 
							$opp->save();
							$sql = "update importrac set jobflag=0 where jobopp=$oppid and jobacct=$client->acct";
							$db->query($sql);
						} // testrun
					}
					else { // no dup: create new
						$res->free();
						$sql = "select l_id from locations where l_acct = $client->acct and status = 1 and l_zip = '$zip'";
						$res = $db->query($sql);
						if( !$res ) throw new Exception(DEBUG?"$db->error : $sql":'Problem with Locations Table',__LINE__); 
						if( !$res->num_rows ) { 
							$testonly = true;
							throw new Exception ("Fatal: There is no location for $jid: $zip",__LINE__);
						}
						list($locid) = $res->fetch_row();
						$res->free();
						if( !$testrun ) { 
							$opp = new Opportunity($db,0,$locid,$client->uid,$client->acct);
							$opp->o_city = addslashes($job->city);
							$opp->o_state = addslashes($job->state);
							$opp->o_zip = $zip;
							$opp->specialty = $spec;
							$opp->o_facility = htmlspecialchars( strip_tags($title) );
							$opp->description = $descr;
							$opp->o_name = $jid;
							$opp->o_email = addslashes($client->email);
							$opp->o_phone = addslashes($client->phone);
							$opp->o_fax = preg_replace('/[^0-9]/','',$client->fax);
							$opp->o_title = addslashes($client->title);
							$opp->o_contact = addslashes($client->firstname." ".$client->lastname);
							$progress .= ' creating...';
							$opp->save();
							$sql = "insert into importrac (jobid,jobacct,jobflag,jobopp) VALUES ('$jid',$client->acct,0,$opp->id) ON DUPLICATE KEY UPDATE jobflag=0";
							$db->query($sql);
						} // testrun
					} // dup check
					$progress .= ' Done!<br>';
				  }
				  catch(Exception $e) {
						if( $testrun ) {
							$progress .= " TEST FAILED: ".$e->getMessage().' ('.$e->getCode().')<br>';
							$mesg = "Errors detected: see below";
							$result = 0;
						}
						else //throw $e;
						{
							$progress .= " PROBLEM RECORD: ".$e->getMessage().' ('.$e->getCode().')<br>';
							$mesg = "Errors detected: see below";
							$result = 0;
						}
				  }
				} // job
				$progress .= 'Cleaning up...';
				if( !$testrun ) {
					$sql = "update opportunities,importrac set status=4 where oid=jobopp and jobflag = 1 and jobacct=$client->acct";
					$res2 = $db->query($sql);
					if( !$res2 ) throw new Exception(DEBUG?"$db->error : $sql":'Problem with Job Tracking Table',__LINE__); 
					$sql = "delete from importrac where jobflag = 1 and jobacct=$client->acct";
					$db->query($sql);
				}
				$progress .= ' Done!<br>';
				$progress .= $testrun?'All jobs were tested! See results above.':($result?'All':'Not all').' jobs were imported!';
				if( $result ) $mesg = "Success!";
				if( $testonly && $testrun ) { 
					$mesg = 'FATAL errors were detected, Data run is impossible until they are fixed!';
					$progress .= '<br>' . $mesg;
				}
				} // loco O
			} // proceed
		} // submit
	}
	catch(Exception $e) {
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
		$progress .= "Oops!";
		$result = 0;
	}
	unset($spec);
	$style = new OperPage('Data Import',$UUID,'reports','export');
	$style->Output();

	if( $UUID ) {
			if( $ACCESS < 200 ) echo '<h1>Access Denied</h1>';
			else {
?>


              <h1>Data Import </h1>
<?php 
				if( $mesg ) echo "<p id='".($result?'warning':'error')."_msg'>$mesg</p>"; 
?>
              <p>Use this page to import Customer's jobs from an external source into the job board. This page does not check Customer's account permissions or job posting limits, please check them yourself before doing import. The procedure will add new jobs or update jobs with identical ID#s for the same user.</p>
			  <div id="formdiv">
              <form action="import-xml.php" method="post" name="formex" id="formex" enctype="multipart/form-data">
			  <input type="hidden" name="MAX_FILE_SIZE" value="6553600" />
			  <input type="hidden" name="lastmode" value="<?php echo $testmode; ?>" />
			  	<p>Enter Customer ID#: <input name="custid" type="text" size="15" maxlength="15" value="<?php echo $custid; ?>"> 
			  	  <input type="submit" name="verify" value="Verify">
<?php if( $client ) echo "<br>$client->firstname $client->lastname, $client->company, $client->email";
?>
			  	</p>
                <p>Select Import type: 
                  <input name="loco" type="radio" value="L"> 
                  Locations or 
                  <input name="loco" type="radio" value="O"> 
                Opportunities           </p>
                <p>                  <input name="extype" type="radio" id="exfile" value="X">
                  XML File on your computer:
                  <input type="file" name="userfile" size="40">
                   <br>
                  <?php if( $ACCESS >= 200 ) { // cust serv - was admin ?>
                  <input name="extype" type="radio" id="exlink" value="K">
                  XML File Link 
URL:
                  <input name="filename" type="text" id="filename" size="40" value="">
                  <br>
<?php } ?><hr />
<input type="radio" checked name="testrun" value="1"> TEST RUN: To Validate Input&nbsp; &nbsp; &nbsp; &nbsp;
<?php if( !$testonly ) { ?>
<input type="radio" name="testrun" value="0"> DATA RUN: To Import<br>
<?php } ?>
<input type="submit" name="submit" value="Import">
                </p>
              </form>
		</div>
		<p><em>First time, run import through <strong>TEST RUN</strong>, then if no errors were reported, select <strong>DATA RUN</strong> and run import again.</em></p>
		<code><?php echo $progress; ?></code>
              <?php
			} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
