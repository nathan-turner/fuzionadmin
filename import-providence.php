<?php //used to import Providence's feed....
  
  require("globals.php5");
	
	//define(HPP_ACCT,1);	
	define(HPP_ACCT,198);  /********** UPDATE if acct was changed *******/
	//define(HPP_UID,10001); 
	define(HPP_UID,10239);  /********** UPDATE if master user was changed 19640*******/
	
    require("cookies.php5");
	
  require_once 'Classes/PHPExcel.php';
  require_once 'Classes/PHPExcel/IOFactory.php';

  error_reporting(E_ALL);
  define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');
  
if(isset($_POST["submit"]))
{
  //print "trying to upload";

  /*$fileName = basename($_FILES['uploadedfile']['name']);
  $target_path = "tmp/";
  $target_path .= $fileName;*/
  
  //$filename="http://practicewithus.com/home/pwu-rssfeed.dot";
  
$filename="http://vendorsprd.providence.org/PhysicianOpportunityXmlProxy/niche.aspx";

//$filename="http://vendorsor.providence.org/PhysicianOpportunityXmlProxy/Niche.aspx";

  $ch = curl_init($filename); 
				curl_setopt($ch, CURLOPT_HEADER, 0);
		        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        		$output = curl_exec($ch); 
				if($output === false) throw new Exception('Curl error: ' . curl_error($ch),__LINE__);
		        curl_close($ch);   

	$xml = simplexml_load_string($output);//simplexml_load_string(str_replace('&','&amp;',$output));
	

  //if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) 
  if($xml)
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
	
	//$sql = "delete from opportunities where o_acct='".HPP_ACCT."' AND  o_uid ='".HPP_UID."' ";
	if(HPP_ACCT!=""){
		$sql = "delete from opportunities where o_acct='".HPP_ACCT."' AND status=1 ";
		$res = $db->query($sql);
	}
	//$sql = "delete from locations where l_acct='".HPP_ACCT."' AND  l_uid ='".HPP_UID."' ";
	//$res = $db->query($sql);
	
	//$xml = simplexml_load_file($target_path);
	/*foreach($xml->Opportunity as $job) {
		echo $job->headline;
	}*/
	
	//echo var_dump($hamspec);
	$client = new Customer($db,HPP_UID);	
    
    //chmod($target_path, 0644);
$states = array(
'Alabama'=>'AL',
'Alaska'=>'AK',
'Arizona'=>'AZ',
'Arkansas'=>'AR',
'California'=>'CA',
'Colorado'=>'CO',
'Connecticut'=>'CT',
'Delaware'=>'DE',
'Florida'=>'FL',
'Georgia'=>'GA',
'Hawaii'=>'HI',
'Idaho'=>'ID',
'Illinois'=>'IL',
'Indiana'=>'IN',
'Iowa'=>'IA',
'Kansas'=>'KS',
'Kentucky'=>'KY',
'Louisiana'=>'LA',
'Maine'=>'ME',
'Maryland'=>'MD',
'Massachusetts'=>'MA',
'Michigan'=>'MI',
'Minnesota'=>'MN',
'Mississippi'=>'MS',
'Missouri'=>'MO',
'Montana'=>'MT',
'Nebraska'=>'NE',
'Nevada'=>'NV',
'New Hampshire'=>'NH',
'New Jersey'=>'NJ',
'New Mexico'=>'NM',
'New York'=>'NY',
'North Carolina'=>'NC',
'North Dakota'=>'ND',
'Ohio'=>'OH',
'Oklahoma'=>'OK',
'Oregon'=>'OR',
'Pennsylvania'=>'PA',
'Rhode Island'=>'RI',
'South Carolina'=>'SC',
'South Dakota'=>'SD',
'Tennessee'=>'TN',
'Texas'=>'TX',
'Utah'=>'UT',
'Vermont'=>'VT',
'Virginia'=>'VA',
'Washington'=>'WA',
'West Virginia'=>'WV',
'Wisconsin'=>'WI',
'Wyoming'=>'WY'
);
    
	$ar = array();
	//echo $states["Washington"];
	
	foreach($xml->Opportunity as $job) {
		//echo $job->facility;
		$jid = "JOB {" .addslashes($job->OpportunityId)."}";
		$jobid = "(JOB ID " .addslashes($job->JobNumber).")";
			$facility = addslashes($job->facility)." ".$jobid;
			$city = $job->city;
			$state = $states["$job->state"];
			//$state = $job->state;
			//echo $states["$job->state"];
			$spec = addslashes(trim($job->specialty));	
			switch($spec)
			{
				case "Pediatric Neurology":
					$spec="Child Neurology";
					break;
				case "Pulmonary Critical Care Medicine":
					$spec="Pulmonary Critical Care";
					break;
				case "Pulmonology":
					$spec="Pulmonary Disease";
					break;
					
				case "Surgery - Cardiothoracic":
					$spec="Surgery - Cardiothoracic";
					break;
				case "Pulmonary Critical Care":
					$spec="Pulmonary Critical Care Medicine";
					break;
				case "Pediatric Dev/Behavioral Specialist":
					$spec="Developmental-Behavioral Pediatrics";
					break;
				case "Family Medicine":
					$spec="Family Practice";
					break;
				case "Hospice & Palliative Medicine":
					$spec="Hospice and Palliative Medicine";
					break;
				case "Pediatric":
					$spec="Pediatrics";
					break;
				case "Immediate Care":
					$spec="Urgent Care Medicine";
					break;
				case "Urgent Care":
					$spec="Urgent Care Medicine";
					break;
				case "Cardiology":
					$spec="Cardiovascular Diseases";
					break;
				case "Orthopedics":
					$spec="Orthopaedic Surgery";
					break;
				case "Geriatric Medicine":
					$spec="Geriatric Medicine - IM";
					break;
				case "Hyperbaric Medicine":
					$spec="Undersea and Hyperbaric Medicine";
					break;
				case "Pediatric Hematology-Oncology":
					$spec="Pediatric Hematology/Oncology";
					break;
				case "Perinatology":
					$spec="Maternal and Fetal Medicine";
					break;
				case "Genetics":
					$spec="Medical Genetics";
					break;
				case "Psychiatry - Child and Adolescent":
					$spec="Child and Adolescent Psychiatry";
					break;
				case "Executive - Physician":
					$spec="Administration";
					break;
				case "Pain Management and Rehabilitative":
					$spec="Physical Medicine and Rehab - Interventional Pain Management";
					break;
				case "Physiatry":
					$spec="Physical Medicine and Rehabilitation";
					break;
				case "Obstetrics/Gynecology":
					$spec="Obstetrics and Gynecology";
					break;
				case "Neonatology":
					$spec="Neonatal-Perinatal Medicine";
					break;
				case "Cardiothoracic Surgery":
					$spec="Surgery - Cardiothoracic";
					break;
				case "Cardiovascular Surgery":
					$spec="Surgery - Cardiothoracic";
					break;
				/*case "Urogynecology":
					$spec="Urogynecology";
					break;*/
				case "Thoracic Surgery":
					$spec="Thoracic and Cardiac Surgery";
					break;
				case "Pulmonary/Critical Care/Intensivist":
					$spec="Pulmonary Critical Care";
					break;
				case "Academic":
					$spec="Academic";
					break;
				case "Surgery Specialties":
					$spec="General Surgery";
					break;
				case "Sports":
					$spec="Orthopedic Surgery – Sports Medicine";
					break;
				case "Cardiac Surgery":
					$spec="Surgery - Cardiothoracic";
					break;
				case "Pediatric - General":
					$spec="Pediatrics";
					break;
				case "Urgent Care":
					$spec="Urgent Care Medicine";
					break;
				case "Urgent Care – UCM":
					$spec="Urgent Care Medicine";
					break;
				case "Pediatric Hematology Oncology":
					$spec="Pediatric Hematology/Oncology";
					break;
				case "Surgery - cardiothoracic":
					$spec="Surgery - Cardiothoracic";
					break;
				case "Urogynecology":
					$spec="Urological Gynecology";
					break;
				case "Maternal Fetal Medicine": 
					$spec="Maternal and Fetal Medicine";
					break;
				case "Sports Medicine":
					$spec="Sports Medicine - FP";
					break;
				case "PMR interventional pain management":
					$spec="Physical Medicine and Rehab - Interventional Pain Management";
					break;
				case "Maternal-Fetal Medicine":
					$spec="Maternal and Fetal Medicine";
					break;
				case "Gynecology-Oncology":
					$spec="Gynecologic Oncology";
					break;
				case "Pulmonary Critical Care":
					$spec="Pulmonary Critical Care Medicine";
					break;
				case "Critical Care":
					$spec="Critical Care Medicine - IM";
					break;
				case "Neurology - MS":
					$spec="Neurology - Multiple Sclerosis";
					break;
				case "Pediatrics – General":
					$spec="Pediatrics";
					break;
				case "Palliative Care":
					$spec="Palliative Medicine";
					break;
				case "Pulmonary Disease – Critical Care":
					$spec="Pulmonary Critical Care Medicine";
					break;
				case "Pediatrics – General":
					$spec="Pediatrics";
					break;
				case "Pediatrics - General":
					$spec="Pediatrics";
					break;
				
				
			}	
			$spec = trim($spec);
			try{
				@$spec2 = $spec == 'Emergency Medicine'?'EM':($spec == 'Hospitalist Medicine'?'HOS':$hamspec["$spec"]);
				//echo $spec2.EOL;
			}catch(Exception $e){
				echo $e->getMessage().' ('.$e->getCode().')<br>';
			}
			if(!$spec2){
				echo "No specialty mapping for ".$spec.". Will be skipped.<br/>";
			}
			else
			{
			$title=$job->headline;
			if($title==''||$title==null)
				$title = $spec2." at ".$facility;
			//$title .= " ".$jobid;
			$description = html_entity_decode(addslashes($job->description));
			//$link = $job->entityWebaddress; //hard-coded
			$contact = $job->recruiter_name;
			$phone = $job->RecruiterPhone;
			//$phone='';
			$email = $job->recruiter_email;
			$ldescr = html_entity_decode(addslashes($job->facil_description));//$job->facil_description."<br/>"; //html_entity_decode($job->facil_description,ENT_QUOTES,"ISO-8859-1"); //html_entity_decode(($job->facil_description));
			//echo $ldescr;
			//enter database
			$acity = addslashes($city); $astate = addslashes($state);
			$sql = "select l_id from locations where l_acct = $client->acct and status = 1 and l_facility = '$facility' and l_city = '$acity' and l_state = '$astate'";
			
			$res = $db->query($sql);
			if( !$res ) throw new Exception(DEBUG?"$db->error : $sql":'Problem with Locations Table',__LINE__); 
			if( !$res->num_rows ) { // create new location
				//$ldescr = addslashes($link);
				$cdescr = html_entity_decode(addslashes($job->city_description));
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
							$opp->o_facility = $facility; //strip_tags($facility) ;
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
		
	}
	
    print "jobs added!";
  }
 }
?>
<h1>Providence Import</h1>
<form action="import-providence.php" method="post" enctype="multipart/form-data">

<input type="submit" name="submit" value="Run">
</form>