<?php
    require("globals.php5");
	//define(PG_SIZE,50);
    //require("cookies.php5");
require 'vendor/autoload.php';
//require 'conn.php';
//Dotenv::load(__DIR__);
$sendgrid_username = 'physiciancareer'; //$_ENV['mfollowell'];
$sendgrid_password = '7853Pinn816!'; //$_ENV['Phg3356!'];

$emailtop=
'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>PhysicianCareer.com - Update your profile so we can match you to the perfect opportunity!</title>
<style>
.emailtable { border: 1px solid #5990c8; border-collapse: collapse; font-family: arial; text-align:center; font-size: 11px; margin-left:auto; margin-right:auto; width: 100%;}
.emailtable td, .emailtable tr { border: 1px solid #5990c8; }
.emailtable td { padding: 2px; }
</style>
</head>

<body>
<table width="690" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td bgcolor="#FFFFFF"><table width="650" border="0" align="center" cellpadding="0" cellspacing="0">
      <tr>
        <td width="334"><div align="left"><font face="Palatino Linotype, Book Antiqua, Palatino, serif" size="7" color="#5a90c8" style="line-height:40px;"><strong>On the hunt for a new position?<br />
          </strong></font><font face="Palatino Linotype, Book Antiqua, Palatino, serif" size="5" color="#5a90c8" style="line-height:27px;">Update your profile on <a href="http://www.physiciancareer.com" target="_blank" style="text-decoration:none; color:#0F4766;">Physician</a><a href="http://www.physiciancareer.com" target="_blank" style="text-decoration:none; color:#609941;">Career</a><a href="http://www.physiciancareer.com" target="_blank" style="text-decoration:none; color:#0F4766;">.com</a> <br />
            so we can match you to the perfect opportunity! </font></div></td>
        <td width="316"><a href="http://physiciancareer.com"><img src="http://physiciancareer.com/wp-content/uploads/2015/04/201404emailtodocs.jpg" height="300" align="right" /></a></td>
      </tr>
    </table>
      <table width="650" border="0" align="center" cellpadding="0" cellspacing="0">
        <tr></tr>
        <tr>
          <td colspan="3" bgcolor="#eaeef7">&nbsp;</td>
        </tr>
        <tr>
          <td width="20" bgcolor="#eaeef7">&nbsp;</td>
          <td width="611" bgcolor="#eaeef7"><font face="Arial, Helvetica, sans-serif" size="2">Below are some jobs that might be of interest.  We have based this on the information listed in your profile.  Should this be incorrect, please update your information, so that we can better match your needs with a posted job opportunity.   Should you not be looking for a new job, let us know, and we can disable your profile, and reduce unwanted emails.  <br />
            <br />
            Should any of these jobs be of interest to you, please click on the specific job link below for more information, or log onto our web site to view all of our jobs at <a target="_blank" style="text-decoration:none; color:#5a90c8;" href="http://physiciancareer.com/physicians/practice-opportunities/">http://physiciancareer.com/physicians/practice-opportunities/</a> 
			<br />
			<br/><a href="http://physiciancareer.com/physicians/physician-login/" target="_blank" style="text-decoration:none; color:#5a90c8;"><strong>Update your profile now and get noticed!</strong></a></font></td>
          <td width="20" bgcolor="#eaeef7">&nbsp;</td>
        </tr>
        <tr>
          <td width="20" bgcolor="#eaeef7">&nbsp;</td>
          <td bgcolor="#eaeef7">&nbsp;</td>
          <td width="20" bgcolor="#eaeef7">&nbsp;</td>
        </tr>
        <tr>
          <td colspan="3">&nbsp;</td>
        </tr>
      </table>';
$emailbottom='<br/><br/>
      <table width="650" border="0" align="center" cellpadding="0" cellspacing="0">
        <tr></tr>
        <tr>
          <td><div align="center">
            <table width="550" border="0" align="center" cellpadding="0" cellspacing="0">
              <tr>
                <td width="550"><div align="center"><a href="http://physiciancareer.com/" target="_blank"><img src="http://physiciancareer.com/wp-content/uploads/2013/11/20131107aspremail2.png" border="0"/></a><font face="Trebuchet MS, Arial, Helvetica, sans-serif" size="2"><br />
                  <img src="http://physiciancareer.com/wp-content/uploads/2012/05/pctomfooter.jpg"/></font></div></td>
              </tr>
              <tr>
                <td><div align="center"><img src="http://pinnaclehealthgroup.com/wp-content/uploads/2013/01/newsletterspace.png" width="200" height="10" /><br />
                  <a target="_blank" href="https://www.facebook.com/PhysicianCareer"><img src="http://physiciancareer.com/wp-content/uploads/2012/06/64facebook.png" alt="Be our friend at Facebook and get the latest from us!" width="40" border="0"/></a>&nbsp;&nbsp;&nbsp;<a target="_blank" href="http://twitter.com/PhysCareer"><img src="http://physiciancareer.com/wp-content/uploads/2012/06/64twitter.png" alt="Twitter" width="40" border="0"/></a>&nbsp;&nbsp;&nbsp;<a target="_blank" href="http://physiciancareer.com/feed/"><img src="http://physiciancareer.com/wp-content/uploads/2012/06/64rss.png" alt="RSS" width="40" border="0"/></a>&nbsp;&nbsp;&nbsp;<a target="_blank" href="http://www.linkedin.com/company/physiciancareer-com"><img src="http://pinnaclehealthgroup.com/wp-content/uploads/2012/08/linkedin.png" alt="LinkedIn" width="40" border="0"/></a><font face="Arial, Helvetica, sans-serif" size="2"></font></div></td>
              </tr>
              <tr>
                <td><div align="center"><font face="Arial, Helvetica, sans-serif" size="1"><em><img src="http://pinnaclehealthgroup.com/wp-content/uploads/2013/01/newsletterspace.png" width="200" height="10" border="0" /><a href="http://physiciancareer.com/physicians/practice-opportunities/" title="View our practice opportunities!" target="_blank" style="text-decoration:none; color:#015172;"><br />
                  <strong>Click here to view our practice opportunities!</strong></a></em></font></div></td>
              </tr>
              <tr>
                <td><div align="center"> <img src="http://pinnaclehealthgroup.com/wp-content/uploads/2013/01/newsletterspace.png" width="200" height="10" border="0"/><br />
                  <font face="Arial, Helvetica, sans-serif" size="2"><em><a href="http://www.physiciancareer.com" style="text-decoration:none; color:#015172;">PhysicianCareer.com</a> supports the <a href="http://www.woundedwarriorproject.org/" target="_blank" style="text-decoration:none; color:#015172;">&quot;Wounded Warrior Project&quot;</a><br />
                    which provides programs and services to severely injured <br />
                    service members of the United States Armed Forces. </em></font><img src="http://pinnaclehealthgroup.com/wp-content/uploads/2013/01/newsletterspace.png" width="400" height="10" border="0"/></div></td>
              </tr>
              <tr>
                <td><div align="center"> <a href="http://www.aspr.org/displaycommon.cfm?an=1&amp;subarticlenbr=847" target="_blank"><img src="http://physiciancareer.com/wp-content/uploads/2013/03/2013030506.png" border="0" /></a> &nbsp; <a href="https://www.suntrust.com/PeopleFinder/cj.kemp" target="_blank"><img src="http://physiciancareer.com/wp-content/uploads/2014/09/STMC.jpg" width="140" height="139" border="0" /></a> </div></td>
              </tr>
              <tr>
                <td>&nbsp;</td>
              </tr>
            </table>
          </div></td>
        </tr>
      </table></td>
  </tr>
</table>
<table style="font-size: 12px; line-height: 20px; font-family: Arial, sans-serif; color: #555555;" align="center" cellpadding="0" cellspacing="0" width="570">
  <tbody>
    <tr>
      <td width="938" align="right" valign="top" style="font-size: 9px; text-align:center; line-height: 15px; font-family: Arial, sans-serif; color: #000000;"><strong>Email not displaying correctly? <a href="http://physiciancareer.com/wp-content/uploads/2015/04/2015-04-PC.com-Email-to-Docs-Web.html" title="View the email in your browser" target="_blank" style="color:#990000; text-decoration: none;">View it in your browser.</a><br />
        <a href="http://physiciancareer.com/physicians/practice-opportunities/" title="View our practice opportunities!" target="_blank" style="color:#5a90c8; text-decoration: none;">Click here to view our practice opportunities!</a><br />
        <img src="http://pinnaclehealthgroup.com/wp-content/uploads/2013/01/newsletterspace.png" width="200" height="7" /></strong></td>
      <!-- spacer -->
    </tr>
  </tbody>
</table>
<table width="650" border="0" align="center" cellpadding="0" cellspacing="0">
</table>
</body>
</html>
';
	
	//NEED TO PUT IN BOUNCE LIST ETC BEFORE LAUNCH
	
	
		$db = db_career();		
		
		$regions = array();		
		// "New England":
		$regions[]="'CT','ME','MA','NH','RI','VT'";				
		// "Northeast":
		$regions[]="'NJ','NY','PA'";				
		// "Midwest":
		$regions[]="'IL','IN','IA','KS','MO','NE','OH'";				
		// "Upper Midwest":
		$regions[]="'MI','MN','ND','SD','WI'";				
		// "Mid Atlantic":
		$regions[]="'DE','DC','MD','VA','WV'";				
		// "South":
		$regions[]="'AL','AR','FL','GA','KY','LA','MS','NC','SC','TN'";				
		// "Southwest":
		$regions[]="'AZ','NM','OK','TX'";				
		// "Mountain":
		$regions[]="'CO','ID','MT','NV','UT','WY'";				
		// "West/Pacific NW":
		$regions[]="'AK','CA','HI','OR','WA'";
			
		$region_arr = array();		
		$region_arr[] = array('CT','ME','MA','NH','RI','VT');
		$region_arr[] = array('NJ','NY','PA');
		$region_arr[] = array('IL','IN','IA','KS','MO','NE','OH');
		$region_arr[] = array('MI','MN','ND','SD','WI');
		$region_arr[] = array('DE','DC','MD','VA','WV');
		$region_arr[] = array('AL','AR','FL','GA','KY','LA','MS','NC','SC','TN');
		$region_arr[] = array('AZ','NM','OK','TX');
		$region_arr[] = array('CO','ID','MT','NV','UT','WY');
		$region_arr[] = array('AK','CA','HI','OR','WA');
		
		/*$result = $db->query('select reg_id, reg_name from regions');
		if( $result && $result->num_rows )  {
			while( list($code,$name) = $result->fetch_row() ) {
				$Regions[$code] = $name;
			}
			$result->free(); unset($result);
		}*/
		
		$interval = 60;  //days exclude last 2 weeks
		$limit = 15; //15 jobs
		
		/*$opps = array();
		
		$sql = "SELECT o_state , specialty, sp_name, COUNT( * ) as num_jobs
FROM opportunities
JOIN specialties ON specialty = sp_code
WHERE specialty != ''
AND opportunities.status =1
AND notifications =1
AND o_datemod
BETWEEN date_add( NOW( ) , INTERVAL -$interval
DAY )
AND date_add( NOW( ) , INTERVAL -14
DAY )
AND o_acct <>18805
GROUP BY specialty, o_state";
		$result = $db->query($sql);
		if( $result && $result->num_rows ) {
			while( $opp = $result->fetch_object() ) {
				
				$opps[]=array("state"=>$opp->o_state, "spec"=>$opp->specialty, "specialty"=>$opp->sp_name, "num"=>$opp->num_jobs);
			}			
		}*/
		
		
		foreach($regions as $key=>$val) //each region
		{
			//echo $key." ".$val."<br/>";
			//echo $val."<br/>";
			foreach($region_arr[$key] as $i=>$v) //each state in region
			{
				//echo $i." ".$v."<br/>";
			}
		}
		
		
		$jobs = array();
		
		$sql = "SELECT oid, o_name, o_city, o_state , specialty, sp_name, o_facility, description, o_acct
FROM opportunities AS o
JOIN specialties ON specialty = sp_code
join clients as c on c.acct=o_acct
WHERE specialty != ''
AND o.status =1
AND o.notifications =1
AND o_datemod
BETWEEN date_add( NOW( ) , INTERVAL -$interval
DAY )
AND now( )
AND o_acct <>18805
AND on_trial=0 AND o_state<>'' AND o_state<>'--' AND specialty<>'' AND specialty<>'--'
group by oid
order by o_state,  specialty,  o_datemod DESC, rand()
";

		/*$sql = "SELECT oid, o_name, o_city, o_state , specialty, sp_name, o_facility, description, o_acct
FROM opportunities AS o
JOIN specialties ON specialty = sp_code
join clients as c on c.acct=o_acct
WHERE specialty != ''
AND o.status =1
AND o.notifications =1
AND o_datemod
BETWEEN date_add( NOW( ) , INTERVAL -$interval
DAY )
AND now( )
AND o_acct <>18805
AND (c.exp_date >=
date_add( NOW( ) , INTERVAL 14
DAY ) OR o_acct=10239 ) AND o_state<>'' AND o_state<>'--' AND specialty<>'' AND specialty<>'--'
group by oid
order by o_state,  specialty,  o_datemod DESC, rand()
";*/
		
		/*$sql = "SELECT oid, o_name, o_city, o_state , specialty, sp_name, o_facility, description
FROM opportunities
JOIN specialties ON specialty = sp_code
WHERE specialty != ''
AND opportunities.status =1
AND notifications =1
AND o_datemod
BETWEEN date_add( NOW( ) , INTERVAL -$interval
DAY )
AND now( )
AND o_acct <>18805
order by o_state,  o_datemod DESC";*/
		$result = $db->query($sql);
		if( $result && $result->num_rows ) {
			while( $opp = $result->fetch_object() ) {
				/*echo $opp->o_state;
				echo " ";
				echo $opp->specialty;
				echo " ";
				echo $opp->sp_name;
				echo "<br/>";*/
				//echo $opp->o_name."<br/>";
				$jobs[]=array("id"=>$opp->oid, "name"=>$opp->o_name, "city"=>$opp->o_city, "state"=>$opp->o_state, "spec"=>$opp->specialty, "specialty"=>$opp->sp_name, "facility"=>$opp->o_facility, "description"=>$opp->description, "acct"=>$opp->o_acct);
			}			
		}
		
$sorted_jobs_reg = array();
$sorted_jobs_spec = array();

/*foreach($jobs as $kk=>$j) //jobs by spec used for non-region searches
{
	$sorted_jobs_spec[$j["spec"]][]=array($j["id"],$j["name"],$j["spec"],$j["state"],$j["city"],$j["facility"],$j["desciption"],$j["acct"]);
}*/
		
foreach($jobs as $kk=>$j)
{
	
	//echo $j["id"]." ".$j["name"]." ".$j["spec"]." ".$j["state"]."<br/>";
	foreach($region_arr as $o=>$r)
	{
		if(in_array($j["state"],$r)){ //state is in this region
			//echo $j["state"]." ".var_dump($r)." ".$o."<br/>";
			//$sorted_jobs_spec[$j["spec"]][]=array($j["id"],$j["name"],$j["spec"],$j["state"]);
			//$sorted_jobs_reg[$o][] = $sorted_jobs_spec[$j["spec"]];
			$sorted_jobs_reg[$o][$j["spec"]][] = array("id"=>$j["id"],"name"=>$j["name"],"spec"=>$j["spec"],"state"=>$j["state"],"city"=>$j["city"],"facility"=>$j["facility"],"description"=>$j["desciption"],"acct"=>$j["acct"]);
		}
	}
}

//echo var_dump($sorted_jobs_reg);
//echo var_dump($sorted_jobs_reg[1]["IM"][0]);

/*
foreach($region_arr as $o=>$r)
{
	//echo $regions[$o]."<br/><br/>";
	
	foreach($sorted_jobs_reg[$o] as $i=>$g) //per spec
	{
		//echo $i."  --SPEC--<br/>";
		
		foreach($g as $key=>$val) //per job
		{
			//echo $key." ".$val["name"]."<br/>";
		}
	}
}*/

$docs = array();
$sorted_docs_reg = array();
//$open_docs = array();
//$docsarr = array();
//select ph_id,fname,lname,email,state,spec, pref_region, pref_states from physicians AS p where status=1 and inactive=0 and NOT ISNULL(spec) and spec<>'' and spec<>'--' and email_bounces=0  
//and (SELECT COUNT(*) FROM bounces WHERE email=p.email)=0

//$sql = "select ph_id,fname,lname,email,state,spec, pref_region, pref_states from physicians AS p where status=1 and inactive=0 and NOT ISNULL(spec) and spec<>'' and spec<>'--' and email_bounces=0  ";
$sql = "select ph_id,fname,lname,p.email,state,spec, pref_region, pref_states
from physicians AS p 
LEFT JOIN bounces as b ON b.emails=p.email
LEFT JOIN bounces2 as b2 ON b2.email=p.email
LEFT JOIN unsubscribes AS u ON u.email=p.email
where status=1 and inactive=0 and NOT ISNULL(spec) and spec<>'' and spec<>'--' and email_bounces=0  
and ISNULL(b.emails)
and ISNULL(b2.email)
and ISNULL(u.email) ";

	$result = $db->query($sql);
		if( $result && $result->num_rows ) {
			
			while( $opp = $result->fetch_array() ) {
					
				//$docsarr[] = $opp;
				//echo var_dump($opp);
				if($opp["email"]!='' && $opp["spec"]!='' && $opp["spec"]!='--' ){ //if none, reject
					$docregions=array();
					if($opp["pref_region"]!='')//by region
					{	
						$regsarr = explode(',',$opp["pref_region"]);
						foreach($regsarr as $key=>$val)
						{
							//region
							if($val>0){
								$o=$val-1;
								$docregions[]=$o;
								//echo $o." ".$opp["spec"]." ".$opp["email"]." $$$$<br/>";
								/////$sorted_docs_reg[$o][$opp["spec"]][] = array("ph_id"=>$opp["ph_id"], "fname"=>$opp["fname"], "lname"=>$opp["lname"], "email"=>$opp["email"], "spec"=>$opp["spec"], "state"=>$opp["state"]);
								//$sorted_docs_reg[$o][$opp["spec"]][] = array("ph_id"=>$opp["ph_id"], "email"=>$opp["email"], "spec"=>$opp["spec"], "state"=>$opp["state"]);
							
							}
							elseif($val==0)
							{
								//echo " ".$opp["spec"]." ".$opp["email"]." $$$$--OPEN--<br/>";								
								$sorted_docs_reg[$opp["spec"]]["OPEN"][] = array("ph_id"=>$opp["ph_id"],  "email"=>$opp["email"], "spec"=>$opp["spec"], "state"=>$opp["state"]);
							
							}
						}
						unset($regsarr);
						
					}
					if($opp["pref_states"]!='')
					{
						$regionstr="";
						$statesarr = explode(',',$opp["pref_states"]);
						foreach($statesarr as $key=>$val)
						{
							
							foreach($regions as $o=>$r)
							{
								if(in_array($val,$region_arr[$o])&& !in_array($o,$docregions)){ //if state in region and not already added to string/array
									$docregions[]=$o;
									//$regionstr.=$o.",";
									//echo $o." ".$opp["spec"]." ".$opp["email"]." $*$*$*$<br/>";
									//$sorted_docs_reg[$o][$opp["spec"]][] = array("ph_id"=>$opp["ph_id"], "fname"=>$opp["fname"], "lname"=>$opp["lname"], "email"=>$opp["email"], "spec"=>$opp["spec"], "state"=>$opp["state"]);
									//break;
								}
							}
						}					
						
					}
					$regionstr=implode(",",$docregions);
					//echo $regionstr." ".$opp["spec"]." ".$opp["email"]." $*$*$*$<br/>";
					$sorted_docs_reg[$opp["spec"]][$regionstr][] = array("ph_id"=>$opp["ph_id"], "email"=>$opp["email"], "spec"=>$opp["spec"], "state"=>$opp["state"]);
							
					//if(in_array($opp["state"],$region_arr[$o]))
						//$sorted_docs_reg[$o][$opp["spec"]][] = array("ph_id"=>$opp["ph_id"], "fname"=>$opp["fname"], "lname"=>$opp["lname"], "email"=>$opp["email"], "spec"=>$opp["spec"], "state"=>$opp["state"]);
					unset($docregions);
				}
			}	//end while		
		}


/*
foreach($regions as $o=>$r)
{
	//$sql = "select ph_id,fname,lname,email,state,spec from physicians where status=1 and inactive=0 and  email_bounces=0 and state in (".$r.") ";
	$sql = "select ph_id,fname,lname,email,state,spec, pref_region, pref_states from physicians where status=1 and inactive=0 and  email_bounces=0  ";
	$result = $db->query($sql);
		if( $result && $result->num_rows ) {
			//while( $opp = $result->fetch_object() ) {
			while( $opp = $result->fetch_array() ) {	
				if($opp["email"]!='' && $opp["spec"]!='' && $opp["spec"]!='--' && $opp["state"]!='' && $opp["state"]!='--'){
					//$docs[]=array("ph_id"=>$opp["ph_id"], "fname"=>$opp["fname"], "lname"=>$opp["lname"], "email"=>$opp["email"], "spec"=>$opp["spec"], "state"=>$opp["state"]);
					if(in_array($opp["state"],$region_arr[$o]))
						$sorted_docs_reg[$o][$opp["spec"]][] = array("ph_id"=>$opp["ph_id"], "fname"=>$opp["fname"], "lname"=>$opp["lname"], "email"=>$opp["email"], "spec"=>$opp["spec"], "state"=>$opp["state"]);
		
				}
			}			
		}					
}*/

/*
$sorted_docs_reg = array();

foreach($docs as $i=>$d)
{	
	foreach($region_arr as $o=>$r)
	{
		if(in_array($d["state"],$r)){ //state is in this region
			
			//echo $d["fname"];
			$sorted_docs_reg[$o][$j["spec"]][] = array("ph_id"=>$d["ph_id"],"fname"=>$d["fname"],"lname"=>$d["lname"],"email"=>$d["email"],"spec"=>$d["spec"],"state"=>$d["state"]);
		}
	}
}*/


$emails = array();
///construct
foreach($sorted_docs_reg as $s=>$d) //by spec
{
	//echo $s." %%<br/>"; //spec
	foreach($d as $regs=>$val) //by doc 
	{
		$emails = array();
		echo " <br/>".$regs." <br/>"; //regions
		foreach($val as $k=>$v) //v is doc vals
		{
			echo " - ".$v["email"]." ".$v["spec"]."<br/>";
			$emails[]=$v["email"];
		}
		$resarr = explode(',',$regs);
		$c=0;
		$acct=0;
		$oldacct="";
		$emtable="";
		foreach($resarr as $id=>$r) //foreach region in list
		{
			echo " = ".$r."<br/>"; //region at a time
			//foreach($sorted_jobs_reg[$o] as $i=>$g) //per spec $i
			//{
				//echo "-|-".$g."<br/>";
				
				//foreach($g as $key=>$job) //per job
				foreach($sorted_jobs_reg[$r][$s] as $key=>$job) //per job
				{
					if($oldacct!=$job["acct"]) //if acct is diff
					{								
						$acct=0;
					}
					if($c<$limit && $acct<5){ //5 per acct
						$emtable.='<tr><td>'.$job["city"].', '.$job["state"].'</td><td>'.$job["facility"].'</td><td>'.$job["name"].'</td><td><a href="http://physiciancareer.com/physicians/job/?oid='.$job["id"].'">View Opportunity</a></td></tr>';
						//echo $key." ".$job["name"]." - ".$c."<br/>";
						$c++;
					}	
					
					$acct++;
					$oldacct=$job["acct"];
				}
				
			//}
		}
		echo "<table>".$emtable."</table>";
		
		
		if(count($emails)>0){
			if($emtable!='')
				$table = "<table class='emailtable'>".$emtable."</table>";
			else
				$table = "";
			
			if($table!=''){
			//if((($regs==="0,1,5" || $regs==="8,4")&& $s=="FP") || ($emails=='tbroxterman@physiciancareer.com'||$emails=='mberg@physiciancareer.com')){
			$subject="Job Opportunities in your Specialty from PhysicianCareer";
//$to = "nturner@phg.com"; //$_ENV['TO'];
$from="info@physiciancareer.com";
//$tos[]="nturner@phg.com";
$tos=$emails; //only LIVE
/*
$sendgrid = new SendGrid($sendgrid_username, $sendgrid_password, array("turn_off_ssl_verification" => true));
$email = new SendGrid\Email();
$email->setTos($tos)->
setFrom($from)->
setSubject($subject)->
setText('please view HTML version in your email client')->
setHtml($emailtop.$table.$emailbottom)->
//addSubstitution("%yourname%", array("Mr. Owl"))->
//addSubstitution("%how%", array("Owl"))->
addHeader('X-Sent-Using', 'SendGrid-API')->
addHeader('X-Transport', 'web');//->

$response = $sendgrid->send($email);*/
var_dump($response);
echo "YESSS";

			}

			echo "#############SEND---+++++++++++++++";
			//send email
		}
		
		unset($emails);
		unset($tos);
	} //
} //end by spec



/*
foreach($region_arr as $o=>$r)
{
	echo "<br/><br/>".$regions[$o]." @@@@@@@<br/><br/>";
	
	foreach($sorted_jobs_reg[$o] as $i=>$g) //per spec
	{
		echo "<br/><br/>"."********* ".$c."  --SPEC-- ".$i."<br/>";
		
		$c=0;
		$table="";
		foreach($g as $key=>$val) //per job
		{
			if($c<=$limit)
				$table.='<tr><td>'.$val["city"].', '.$val["state"].'</td><td>'.$val["facility"].'</td><td>'.$val["name"].'</td><td><a href="http://physiciancareer.com/physicians/job/?oid='.$val["id"].'">View Opportunity</a></td></tr>';
				//echo $key." ".$val["name"]." - ".$c."<br/>";
			$c++;
		}
		$table = '<table style="border:1px solid black">'.$table.'</table>';
		echo $table;
		//$emails = array();
		foreach($sorted_docs_reg[$o][$i] as $s=>$d)
		{
			echo $d["ph_id"].": ".$d["email"].",";
			$emails[]=$d["email"]; //add email
		}
		
		if(count($emails)>0)
			echo "#############SEND---+++++++++++++++";
			//send email
		
		//unset($emails);
	}	
	
}*/

//now do OPEN
//sorted_jobs_spec
foreach($sorted_docs_reg["OPEN"] as $s=>$d)
{
	//echo $s." ".$d["email"]."%%<br/>";
	foreach($d as $key=>$val)
	{
		//echo $key." ".$val["email"]."<br/>";
	}
}



$subject="test physician email";
$to = "nturner@phg.com"; //$_ENV['TO'];
$from="info@physiciancareer.com";
$tos[]="nturner@phg.com";

/*
$sendgrid = new SendGrid($sendgrid_username, $sendgrid_password, array("turn_off_ssl_verification" => true));
$email = new SendGrid\Email();
$email->setTos($tos)->
setFrom($from)->
setSubject($subject)->
setText('please view HTML version in your email client')->
setHtml($emailtop.$table.$emailbottom)->
//addSubstitution("%yourname%", array("Mr. Owl"))->
//addSubstitution("%how%", array("Owl"))->
addHeader('X-Sent-Using', 'SendGrid-API')->
addHeader('X-Transport', 'web');//->

$response = $sendgrid->send($email);*/
var_dump($response);



?>
             
<?php 
		if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
		//$style->ShowFooter();
?>
