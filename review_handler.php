<?php
    require("globals.php5");
    require("cookies.php5");
    // $UUID <> 0 if auth
	if( $UUID && $ACCESS && isset($_POST["movebtn"])) try {
		$phids = implode(",", $_POST["phid"]);
	
		$totalcount = 0; $resdb = db_career();
		$sql = 'INSERT INTO physicians_no_res
(
  ph_id,
  res_id,
  year,
  dup,
  inactive,
  iv_date,
  checkin,
  status,
  pstatus,
  uid_mod,
  date_mod,
  fname,
  midname,
  lname,
  title,
  mddo,
  addr1,
  addr2,
  city,
  state,
  zip,
  ofaddr1,
  ofaddr2,
  ofcity,
  ofstate,
  ofzip,
  ho,
  homephone,
  cellphone,
  officephone,
  officeext,
  email,
  email_bounces,
  spec,
  spec_2nd,
  school ,
  sch_loc ,
  sch_state ,
  amg ,
  sch_year ,
  fellowship ,
  fel_state ,
  fel_city ,
  fel_spec ,
  fel_year ,
  program ,
  res_state ,
  res_city ,
  res_spec ,
  res_year ,
  program_2 ,
  res2_state ,
  res2_city ,
  res2_spec ,
  res2_year ,
  fellow_2 ,
  fel2_state ,
  fel2_city ,
  fel2_spec ,
  fel2_year ,
  avail_date ,
  licensed ,
  visa_status ,
  citizen ,
  birth_state ,
  bcbe ,
  bcbe_year ,
  pref_region ,
  pref_states ,
  pref_stopen ,
  pref_city ,
  pref_commu2 ,
  pref_practice ,
  marital_status ,
  children ,
  spouse ,
  spouse_prof ,
  spouse_spec ,
  spouse_state ,
  languages ,
  hobbies ,
  contact_pref ,
  interviewing ,
  reason_leaving ,
  other_pref ,
  salary_other ,
  password ,
  newsletter ,
  notifications ,
  campaigns ,
  secret_q ,
  secret_a ,
  lastlogdate ,
  lastlogip ,
  email_confirm ,
  iv_complete ,
  noemail ,
  reg_date ,
  pending ,
  last_save ,
  uid_saved ,
  source ,
  data_entry ,
  as_new ,
  phg_source) select * from physicians'
				." where pending=2 and inactive=0 and ph_id IN(".$phids.")";
	if($phids!=''){
		$result = $resdb->query($sql);
		if( !$result ) throw new Exception(DEBUG?$resdb->error.": $sql":'Can not get review list',__LINE__);
		
		//$sql = "update physicians_no_res SET pending=1 WHERE ph_id IN(".$phids.")";
		$sql = "update physicians SET pending=1, inactive=1 WHERE ph_id IN(".$phids.")";
		$result = $resdb->query($sql);
		if( !$result ) throw new Exception(DEBUG?$resdb->error.": $sql":'Can not update',__LINE__);
		
		$sql = "delete from physicians WHERE ph_id IN(".$phids.")";
		$result = $resdb->query($sql);
		if( !$result ) throw new Exception(DEBUG?$resdb->error.": $sql":'Can not delete',__LINE__);
		//$totalcount = $result->num_rows;
		// ok: results.
		//echo $sql;
	}
		header("location: review.php \n");
		
	}
	catch(Exception $e) {
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
	}
	
	if( $UUID && $ACCESS && isset($_POST["deletebtn"])) try {
		$phids = implode(",", $_POST["phid"]);
	
		$totalcount = 0; $resdb = db_career();
		
		if($phids!=''){				
		$sql = "delete from physicians WHERE ph_id IN(".$phids.")";
		$result = $resdb->query($sql);
		if( !$result ) throw new Exception(DEBUG?$resdb->error.": $sql":'Can not delete',__LINE__);
		
		}
		header("location: review.php \n");
	}
	catch(Exception $e) {
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
	}
	//echo var_dump($_POST);
?>