<?php
  //LIFEPOINT
  require("globals.php5");
	
	define(HPP_ACCT,202);	
	//define(HPP_ACCT,1234);  /********** UPDATE if acct was changed *******/
	define(HPP_UID,10444); //dwina mullins
	//define(HPP_UID,10003);  /********** UPDATE if master user was changed ********/
	
    require("cookies.php5");
	
  require_once 'Classes/PHPExcel.php';
  require_once 'Classes/PHPExcel/IOFactory.php';

  error_reporting(E_ALL);
  define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');
  
if(isset($_POST["submit"]))
{
  //print "trying to upload";

  $fileName = basename($_FILES['uploadedfile']['name']);
  $target_path = "tmp/";
  $target_path .= $fileName;

  if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) 
  {
	$hmacct = HPP_ACCT;
	//get db stuff
	$db = db_career();
	$sql = "select sp_code,sp_name from specialties";
				$res = $db->query($sql);
				if( !$res ) throw new Exception(DEBUG?"$db->error : $sql":'Problem with Specialties Table',__LINE__); 
				$hamspec = array();
				while( list($spc,$sph) = $res->fetch_row() ) { $hamspec[$sph] = $spc; }
				$res->free();
				$sql = "select st_code,st_name from states";
				$res = $db->query($sql);
				if( !$res ) throw new Exception(DEBUG?"$db->error : $sql":'Problem with States Table',__LINE__); 
				$hamstadt = array();
				while( list($spc,$sph) = $res->fetch_row() ) { $hamstadt[$sph] = $spc; }
				$res->free();
				
	$sql = "delete from opportunities where o_acct='".HPP_ACCT."' AND o_uid ='".HPP_UID."' ";
	$res = $db->query($sql);
	//$res->free();
	
	$client = new Customer($db,HPP_UID);
	
    //print "Upload Successful";  
    chmod($target_path, 0644);

    //$objReader = new PHPExcel_Reader_Excel2007(); 
	//$objReader = PHPExcel_IOFactory::createReader('Excel2007');
	$objReader = PHPExcel_IOFactory::createReader('Excel5');
    $objReader->setReadDataOnly(true);
    $objPHPExcel = $objReader->load($target_path);
	$ar = array();
	
	foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
	//echo 'Worksheet - ' , $worksheet->getTitle() , EOL;

	foreach ($worksheet->getRowIterator() as $row) {
		//echo '    Row number - ' , $row->getRowIndex() , EOL;
		
		$rownumber = $row->getRowIndex();

		$cellIterator = $row->getCellIterator();
		$cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
		foreach ($cellIterator as $cell) {
			if (!is_null($cell) /*&& $rownumber>1*/) {
				//echo '        Cell - ' , $cell->getCoordinate() , ' - ' , $cell->getCalculatedValue() , EOL;
				//echo $cell->getCoordinate() , ' - ' , $cell->getCalculatedValue() , EOL;
				$ar[$rownumber][] = $cell->getCalculatedValue();
			}
		}
		//echo EOL;
	}
	}
	//var_dump($ar);
	
	for($i=1; $i<=count($ar); $i++)
	{
		$spec2="";
	foreach($ar[$i] as $key=>$val)
	{
		//if($i>1)
			//echo $key."-".$val.EOL;
		
	}
		if($i>1)
		{
			$jid = "JOB {" .addslashes($ar[$i][0])."}";
			$facility = $ar[$i][2];
			$city = $ar[$i][3];
			$state = $ar[$i][4];
			$spec = $ar[$i][5];		
			switch($spec)
			{
				case "ENT":
					$spec="Otolaryngology";
					break;
				case "FP-OB":
					$spec="Family Practice";
					break;
				case "Hematology & Oncology":
					$spec="Hematology/Oncology";
					break;
				case "Medicine/Pediatrics":
					$spec="Internal Medicine/Pediatrics";
					break;
				case "Obstetrics/GYN":
					$spec="Obstetrics and Gynecology"; // 	Gynecology
					break;
				case "Pulmonology":
					$spec="Pulmonary Critical Care Medicine";
					break;
				case "Thoracic Surgery":
					$spec="Thoracic and Cardiac Surgery";
					break;
				case "Cardiology":
					$spec="Cardiovascular Diseases";
					break;
				case "Cardiology - Interventional":
					$spec="Interventional Cardiology";
					break;
				case "Cardiology - Invasive":
					$spec="Cardiovascular Diseases";
					break;
				case "Cardiology - Noninvasive":
					$spec="Cardiovascular Diseases";
					break;
				case "CardioThoracic Surgery":
					$spec="Thoracic and Cardiac Surgery";
					break;
				case "Orthopaedic Surgery - Spine":
					$spec="Spine Surgery";
					break;
				case "Rehab / Phys Med":
					$spec="Physical Medicine and Rehabilitation";
					break;
				case "Rehab/Phys Medicine":
					$spec="Physical Medicine and Rehabilitation";
					break;
				case "PM&R":
					$spec="Physical Medicine and Rehabilitation";
					break;
				case "Podiatric Medicine":
					$spec="Podiatry";
					break;
				case "Podiatric Medicine":
					$spec="Podiatry";
					break;
				
			}
			try{
				@$spec2 = $spec == 'Emergency Medicine'?'EM':($spec == 'Hospitalist Medicine'?'HOS':$hamspec["$spec"]);
				//echo $spec2.EOL;
			}catch(Exception $e){
				echo $e->getMessage().' ('.$e->getCode().')<br>';
			}
			if(!$spec2)
				echo "No specialty mapping for ".$spec.". Will be skipped.";
			$title=$ar[$i][7];
			if($title==''||$title==null)
				$title = $spec2." at ".$facility;
			$description = $ar[$i][8];
			$link = "http://www.lifepointhospitals.com/serving-communities/our-communities/"; //hard-coded
			$contact = $ar[$i][9];
			$phone = $ar[$i][11];
			$email = $ar[$i][12];
			
			//enter database
			$acity = addslashes($city); $astate = addslashes($state);
			$sql = "select l_id from locations where l_acct = $client->acct and status = 1 and l_facility = '$facility' and l_city = '$acity' and l_state = '$astate'";
			$res = $db->query($sql);
			if( !$res ) throw new Exception(DEBUG?"$db->error : $sql":'Problem with Locations Table',__LINE__); 
			if( !$res->num_rows ) { // create new location
				$ldescr = addslashes($link);
				$cdescr = addslashes($link);
				$sql ="insert into locations (l_facility,l_city,l_state,l_uid,l_acct,l_description,l_commdescr,exp_date) values('$facility','$acity','$astate',$client->uid,$client->acct,'$ldescr','$cdescr',ADDDATE(NOW(), INTERVAL 1 YEAR))";
				$result = $db->query($sql);
				if( !$result ) throw new Exception(DEBUG?"$db->error: $sql":'Can not insert locations',__LINE__);
				$locid = $db->insert_id; 
			}
			else list($locid) = $res->fetch_row();
			$res->free();
			//create opp
			@$opp = new Opportunity($db,0,$locid,$client->uid,$client->acct);
							$opp->o_name = $jid;

							$opp->o_city = ($acity);
							$opp->o_state = ($astate);
							$opp->specialty = $spec2;
							$opp->o_facility = strip_tags($facility) ;
							$opp->description = $description;
							$opp->exp_date = $client->exp_date;
							$opp->practice_type = $spec2 == 'HOS'?'Hosp':($spec == 'EM'?'ER':'');
							//if(stripos('Part-Time',$jde->status) !== false ) $opp->practice_type .= ($opp->practice_type?',':'').'Locum';
							
							//$jco = $job->jobContact;
							$opp->o_email = addslashes($email);
							$opp->o_phone = addslashes($phone);
							$opp->o_contact = addslashes(htmlspecialchars( strip_tags($contact)));

							//$progress .= ' creating...';
							@$opp->save();
							$sql = "insert into importrac (jobid,jobacct,jobflag,jobopp) VALUES ('$jid',$client->acct,0,$opp->id) ON DUPLICATE KEY UPDATE jobflag=0";
							$db->query($sql);
		}
	//echo EOL;
	/*echo $client->acct;
	echo "-";
	echo $client->uid;
	echo EOL;*/
	}
	
    print "jobs added!";
  }
 }
?>
<strong>Lifepoint Import</strong>
<form action="test.php" method="post" enctype="multipart/form-data">
<label for="file">Filename:</label>
<input type="file" name="uploadedfile" id="file"><br>
<input type="submit" name="submit" value="Submit">
</form>