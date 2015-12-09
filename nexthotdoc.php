<?php
    require("globals.php5");
    require("cookies.php5");
	
	if( $UUID && $ACCESS && $ACCESS >= 200 ) try {
		$db = db_clients();
		
		$sql = "SELECT ph_id, case when checkin=1 then fname else concat(left(fname,1),'.') end as fna, case when checkin=1 then lname else concat(left(lname,1),'.') end as lna, mddo, avail_date, spec, sp_name, pref_stopen, pref_states, pref_region, pref_commu2, as_new, last_save FROM physicians JOIN specialties ON spec = sp_code WHERE last_save 
BETWEEN curdate() AND date_add(curdate() , INTERVAL 1 day)
AND pending=0 AND physicians.status=1 and inactive=0 and as_new != 2
    ORDER BY  lname";
		$result = $db->query($sql);
		if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not execute query',__LINE__);
	}
	catch(Exception $e) {
		$mesg = 'Attention: '.$e->getMessage().' ('.$e->getCode().')<br>';
		//unset($oper);
	}
	$style = new OperPage('Tomorrow\'s HotDoc List',$UUID,'reports','customerstats');
	$style->Output();

	if( $UUID ) {
			if( $ACCESS < 200 ) echo '<h1>Access Denied</h1>';
			else {
?>
              <h1>Tomorrow's HotDoc List </h1>
			  <p>Below report provides an estimate of the docs that should appear on the HotDoc report for tomorrow.</p>
              <?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
			  <table  cellpadding="1" style="width:100% ">
                <tr>
				  <th></th>
                  <th>ID</th>
                  <th>Name</th>
				  <th>Spec</th>
				  <th>Pref. States</th>
				  <th>Last Save</th>
				  
                </tr>
<?php 
		$totals = $result->num_rows;
		for( $i=0; $i < $totals; $i++ ) {
			
			$row = $result->fetch_object();
			
?>
                <tr>
				  <td><?php echo $i+1; ?></td>
                  <td><a href="showdocpc.php?id=<?php echo $row->ph_id; ?>"><?php echo $row->ph_id; ?></a></td>
				  <td><?php echo $row->fna; ?><?php echo $row->lna; ?></td>
				  <td><?php echo $row->spec." - ".$row->sp_name; ?></td>
				  <td><?php echo $row->pref_states; ?></td>
				  <td><?php echo $row->last_save; ?></td>
				  
                </tr>
<?php 
		} // for (iteration)
			
?>
			   
<?php
			
?>
        </table>     
<?php 	
		
?>			  			
			
<?php		} // ACCESS
		} // UUID
		else showLoginForm(); 
		$style->ShowFooter();
?>