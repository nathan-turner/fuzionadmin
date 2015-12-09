<?php
    require("globals.php5");
	
	define(HMA_ACCT,327);  /********** UPDATE if acct was changed *******/
	
    require("cookies.php5");
	$mesg = ''; $result = 1; $hmacct = HMA_ACCT;
	$testonly = true;
	if( $UUID && $ACCESS >= 200 ) try {
		// do stuff
		$db = db_career();
		if( $_POST['submit'] && $_POST['extype'] ) {
			// extype X = HMA file, K (400+) HMA link, M (400+) Map
			// userfile (X), filename (K)
			$progress = 'Initializing...<br>';
			$extype = $_POST['extype'];
			$testrun = $_POST['testrun'];
			$proceed = 0;
			if( $extype === 'M' && $ACCESS >= 200 ) {
				// map hmaspec to spec and sp_hma in db_client
				$hma = trim($_POST['hmaspec']);
				$spec = $_POST['spec'];
				if( !$hma || !$spec || $spec == '---' ) throw new Exception('No specialty entered or selected',__LINE__);
				$sql = "update specialties set sp_hma = '$hma' where sp_code = '$spec'";
				$result = $db->query($sql);
				if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Mapping problem',__LINE__);
				$progress .= ' Done!<br>';
			} // M
			elseif( $extype === 'X' ) {
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
				$testonly = ! $testrun; // testonly disables real run
				if( !$zml ) throw new Exception('File load failed',__LINE__);
				$sql = "select sp_code,sp_hma from specialties where sp_hma is not null";
				$res = $db->query($sql);
				if( !$res ) throw new Exception(DEBUG?"$db->error : $sql":'Problem with Specialties Table',__LINE__); 
				$hamspec = array();
				while( list($spc,$sph) = $res->fetch_row() ) { $hamspec[$sph] = $spc; }
				$res->free();
				if( !$testrun ) {
					$sql = "update importrac set jobflag=1 where jobacct=$hmacct";
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
					$sql = "update opportunities,importrac set status=4 where oid=jobopp and jobflag = 1 and jobacct = $hmacct";
					$res2 = $db->query($sql);
					if( !$res2 ) throw new Exception(DEBUG?"$db->error : $sql":'Problem with Job Tracking Table',__LINE__); 
					$sql = "delete from importrac where jobflag = 1 and jobacct = $hmacct";
					$db->query($sql);
				}
				$progress .= ' Done!<br>';
				$progress .= $testrun?'All jobs were tested! See results above.':($result?'All':'Not all').' jobs were imported!';
				if( $result ) $mesg = "Success!";
				if( $testonly && $testrun ) { 
					$mesg = 'FATAL errors were detected, Data run is impossible until they are fixed!';
					$progress .= '<br>' . $mesg;
				}
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


              <h1>HMA Data Import </h1>
              <?php 
				if( $mesg ) echo "<p id='".($result?'warning':'error')."_msg'>$mesg</p>"; 
?>
              <p>Use this page to import Customer's jobs from an external source into the job board. This page does not check Customer's account permissions or job posting limits, please check them yourself before doing import. The procedure will add new jobs or update jobs with identical ID#s for the same user.</p>
			  <div id="formdiv">
              <form action="import-hma.php" method="post" name="formex" id="formex" enctype="multipart/form-data">
			  <input type="hidden" name="MAX_FILE_SIZE" value="6553600" />
                <p>Select Import type: 
                  <br>
                  <input name="extype" type="radio" id="exfile" value="X"> HMA XML File:
				   <input type="file" name="userfile" size="40"><br>
<?php if( $ACCESS >= 200 ) { // cust serv - was admin ?>
<input name="extype" type="radio" id="exlink" value="K"> HMA XML Link 
URL: <input name="filename" type="text" id="filename" size="40" value="http://www.practicewithhealthmanagement.com/opportunities/pullForMDSearch"><br>
<hr />
<input name="extype" type="radio" id="exfile" value="M"> Map an HMA specialty to ours: <input type="text" name="hmaspec" value="">
 to <?php echo showSpecList($db); ?>
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
