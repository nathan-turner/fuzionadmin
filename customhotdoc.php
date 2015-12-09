<?php
    require("globals.php5");
    require("cookies.php5");
	
	if( $UUID && $ACCESS && $ACCESS >= 200 ) try {
		
		$db = db_clients();
		if(isset($_POST["submitbtn"]))
		{
			//echo var_dump($_POST["spec"]);
			
			foreach($_POST["spec"] as $k=>$v)
			{
				$specs[] = "'".$v."'";
			}
			$spec = implode(',',$specs); 
			//echo $spec;
			
			$region="";
			switch($_POST["pcregion"])
			{
				case "New England":
					$region="'CT','ME','MA','NH','RI','VT'";
					break;
				case "Northeast":
					$region="'NJ','NY','PA'";
					break;
				case "Midwest":
					$region="'IL','IN','IA','KS','MO','NE','OH'";
					break;
				case "Upper Midwest":
					$region="'MI','MN','ND','SD','WI'";
					break;
				case "Mid Atlantic":
					$region="'DE','DC','MD','VA','WV'";
					break;
				case "South":
					$region="'AL','AR','FL','GA','KY','LA','MS','NC','SC','TN'";
					break;
				case "Southwest":
					$region="'AZ','NM','OK','TX'";
					break;
				case "Mountain":
					$region="'CO','ID','MT','NV','UT','WY'";
					break;
				case "West/Pacific NW":
					$region="'AK','CA','HI','OR','WA'";
					break;
			}
			$sort=$_GET["sort"];
			if($region!="")
			{
				if($sort=='')
				{
				$sql="SELECT ph_id, case when checkin=1 then fname else concat(left(fname,1),'.') end as fna, case when checkin=1 then lname else concat(left(lname,1),'.') end as lna, mddo, avail_date, spec, sp_name, pref_stopen, pref_states, pref_region, pref_commu2, as_new, last_save FROM physicians JOIN specialties ON spec = sp_code 
				WHERE inactive=0 AND pending=0 and status=1 and as_new != 2 and spec IN(".$spec.") and (pref_states IN(".$region.") OR ofstate IN(".$region.") OR state IN(".$region.") OR ofstate IN(".$region.") OR fel_state IN(".$region.") OR fel2_state IN(".$region.") OR res_state IN(".$region.")  )
				and (last_save BETWEEN  date_sub(NOW(), interval 1 MONTH) AND NOW()  OR reg_date BETWEEN  date_sub(NOW(), interval 1 MONTH) AND NOW() OR date_mod BETWEEN  date_sub(NOW(), interval 1 MONTH) AND NOW() )
				ORDER BY  date_mod desc"; //last mod - make last save sortable - add check for verified
				}
				else{
					$sql="SELECT ph_id, case when checkin=1 then fname else concat(left(fname,1),'.') end as fna, case when checkin=1 then lname else concat(left(lname,1),'.') end as lna, mddo, avail_date, spec, sp_name, pref_stopen, pref_states, pref_region, pref_commu2, as_new, last_save FROM physicians JOIN specialties ON spec = sp_code 
				WHERE inactive=0 AND pending=0 and status=1 and as_new != 2 and spec IN(".$spec.") and (pref_states IN(".$region.") OR ofstate IN(".$region.") OR state IN(".$region.") OR ofstate IN(".$region.") OR fel_state IN(".$region.") OR fel2_state IN(".$region.") OR res_state IN(".$region.")  )
				and (last_save BETWEEN  date_sub(NOW(), interval 1 MONTH) AND NOW()  OR reg_date BETWEEN  date_sub(NOW(), interval 1 MONTH) AND NOW() OR date_mod BETWEEN  date_sub(NOW(), interval 1 MONTH) AND NOW() )
				ORDER BY $sort desc";
				//echo $sql;
				}
				
				//echo $sql;
				$result = $db->query($sql);
				if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not execute query',__LINE__);
			}
			
		}
		
		/*$db = db_clients();
		
		$sql = "SELECT ph_id, case when checkin=1 then fname else concat(left(fname,1),'.') end as fna, case when checkin=1 then lname else concat(left(lname,1),'.') end as lna, mddo, avail_date, spec, sp_name, pref_stopen, pref_states, pref_region, pref_commu2, as_new, last_save FROM physicians JOIN specialties ON spec = sp_code WHERE last_save 
BETWEEN curdate() AND date_add(curdate() , INTERVAL 1 day)
AND pending=0 AND physicians.status=1 and inactive=0 and as_new != 2
    ORDER BY  lname";*/
		
	}
	catch(Exception $e) {
		$mesg = 'Attention: '.$e->getMessage().' ('.$e->getCode().')<br>';
		//unset($oper);
	}
	$style = new OperPage('Custom Doc List',$UUID,'reports','customerstats');
	$style->Output();

	if( $UUID ) {
			if( $ACCESS < 200 ) echo '<h1>Access Denied</h1>';
			else {
?>
              <h1>Custom Doc List </h1>
			  <p>Edit parameters below and submit to generate a list for clients.</p>
              <?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>

<style>
.customtbl { width: 90%; }
.customtbl td { border: 1px solid; padding: 2px; }
</style>
<form action="" method="post">
<label><strong>Specialty</strong></label><br/>	
<?php echo str_replace('name="spec"', 'name="spec" size="8" multiple', showSpecList($db,'', 'spec[]', 1)); ?>
<br/><br/>
<label><strong>Region</strong></label><br/>
<select style="vertical-align:top" id="pcregion" name="pcregion">
		<option value="">--Select Region--</option>
		<option value="New England">New England</option>
		<option value="Northeast">Northeast</option>	
		<option value="Midwest">Midwest</option>
		<option value="Upper Midwest">Upper Midwest</option>
		<option value="Mid Atlantic">Mid Atlantic</option>
		<option value="South">South</option>
		<option value="Southwest">Southwest</option>
		<option value="Mountain">Mountain</option>
		<option value="West/Pacific NW">West/Pacific NW</option>
	</select>
	

<br/><br/>
	
<input type="submit" value="Submit" name="submitbtn" />
</form>
	
<?php 
		$totals = $result->num_rows;
		if($totals>0){
?>
<table class="customtbl">
				<tr>
				  <th></th>
                  <th>ID</th>
                  <th>Name</th>
				  <th>Spec</th>
				  <th>Pref. States</th>
				  <th><a href="?sort=last_save">Last Save</a></th>
				  
                </tr>
<?php
		}
		for( $i=0; $i < $totals; $i++ ) {
			
			$row = $result->fetch_object();
?>			
			
            
			<tr>
				  <td style="border: 1px solid; padding: 2px;" ><?php echo $i+1; ?></td>
                  <td style="border: 1px solid; padding: 2px;"><a href="http://physiciancareer.com/employers/showdoc/?lid=2&id=<?php echo $row->ph_id; ?>"><?php echo $row->ph_id; ?></a></td>
				  <td style="border: 1px solid; padding: 2px;"><?php echo $row->fna; ?> <?php echo $row->lna; ?></td>
				  <td style="border: 1px solid; padding: 2px;"><?php echo $row->spec." - ".$row->sp_name; ?></td>
				  <td style="border: 1px solid; padding: 2px;"><?php echo $row->pref_states; ?></td>
				  <td style="border: 1px solid; padding: 2px;"><?php echo $row->last_save; ?></td>
				  
                </tr>			
				
				<?php //echo $row->last_save; ?>
<?php 
		} // for (iteration)
if($totals>0){			
?>
</table> 
<?php
}		
?>			  			
			
<?php		} // ACCESS
		} // UUID
		else showLoginForm(); 
		$style->ShowFooter();
?>