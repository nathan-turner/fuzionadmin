<?php
    require("globals.php5");
    require("cookies.php5");
    // $UUID <> 0 if auth
	$mesg = '';
	if( $UUID ) { // stats are available to everyone
		/*if( $_POST['year'] && is_numeric($_POST['year']) ) $yer = $_POST['year'];
		else $yer = $ResYears[0];*/
		$yer=2005;
		try {
			$nocommit = $_POST['nocommit']?' and status = 0':'';
			$inactiv = $_POST['inactiv']?'':' and inactive = 0';
			$updated = $_POST['updated']?' and pending = 1':'';
			$checkin = $_POST['checkin']?' and pending = 0':'';
			$hanging = $_POST['hanging']?' and pending = 2':'';
			$crit = $nocommit.$updated.$checkin.$inactiv.$hanging;
		// the big one.
		$sql = 'SELECT sp_name, count(ph_id) AS residents, Sum(if(pref_region is null,0,1)) AS pregion, Sum(pref_stopen) AS pstopen, Sum(If(pref_states is null,0,1)) AS pstates, Sum(If(pref_city is null,0,1)) AS pcity, Sum(If(pref_commu2 is null,0,1)) AS commsize, Sum(If(homephone is null,0,1)) AS hphone, Sum(If(officephone is null,0,1)) AS ophone, Sum(If(cellphone is null,0,1)) AS cphone, Sum(If(email is null Or email_bounces != 0,0,1)) AS emails, Sum(If(addr1 is null and addr2 is null and ofaddr1 is null and ofaddr2 is null,0,1)) AS addr, Sum(If(school is null,0,1)) AS mschool, Sum(amg) AS namg, Sum(If(visa_status is null,0,1)) AS visa, Sum(checkin) AS checkins '.
	"FROM specialties INNER JOIN physicians ON specialties.sp_code = physicians.spec WHERE dup=0 $crit GROUP BY sp_name ORDER BY sp_name";
		// wow. thanks to MS Access for creating the prototype
				$resdb = db_career();
			$result = $resdb->query($sql);
			if( !$result || !$result->num_rows ) throw new Exception(DEBUG?$resdb->error:'No Statistics at all',__LINE__);
		}
		catch(Exception $e) {
			$mesg = 'Search failed: '.$e->getMessage().' ('.$e->getCode().')<br>';
		}
	} // UUID
	$style = new OperPage('Physician Database Statistics',$UUID,'reports','residentstats');
	$style->Output();

	if( $UUID ) {
?>
              <h1>Physician Database Statistics</h1>
<?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
              <form name="form1" method="post" action="residentstats.php">
              <p class="onscreen">
			 <label><input name="nocommit" type="checkbox" value="1" <?php echo $nocommit?' checked':'' ?>> 
			 Inactive only</label>
			 ,&nbsp;
			 <label><input name="inactiv" type="checkbox" value="1" <?php echo $inactiv||$yer==1?'':' checked' ?>> 
			 Include Suspended </label>
			 ,&nbsp;
			 <label><input name="hanging" type="checkbox" id="hanging" value="1" <?php echo $hanging?' checked':'' ?>> Incomplete only</label>,&nbsp; 
			 <label><input name="updated" type="checkbox" id="updated" value="1" <?php echo $updated?' checked':'' ?>> Completed only</label>,&nbsp; 
             <label><input name="checkin" type="checkbox" id="checkin" value="1" <?php echo $checkin?' checked':'' ?>> 
             Verified only</label>
&nbsp;                
<input type="submit" name="Submit" value="Select">
              </p>
			  </form>
              <div id="statdiv">
              <table style="width: 100%"  border="1" cellspacing="0" cellpadding="1">
                <tr>
                  <th>Specialty</td>
                  <th title="Number">#</th>
                  <th title="With Preferred Region">Region</th>
                  <th title="With Preferred State">State</th>
                  <th title="No Preferred State/Region">Open</th>
                  <th title="With Preferred City">City</th>
                  <th title="With Preferred Community Type">Comm</th>
                  <th title="With Home Phone">H.Ph</th>
                  <th title="With Office Phone">O.Ph</th>
                  <th title="With Cell Phone">C.Ph</th>
                  <th title="With Email">Email</th>
                  <th title="With Address">Addr</th>
                  <th title="With Med School Info">Sch</th>
                  <th>AMG</th>
                  <th title="With Visas">Visa</th>
                  <th>Public</th>
                </tr>
<?php 
		$totals = array('residents' => 0,'pregion' => 0,'pstopen' => 0,'pstates' => 0,'pcity' => 0,
		'commsize' => 0,'hphone' => 0,'ophone' => 0,'cphone' => 0,'emails' => 0,'addr' => 0,
		'mschool' => 0,'namg' => 0,'visa' => 0,'checkins' => 0);
	while( $result && ($row = $result->fetch_assoc()) ) {
?>
                <tr>
                  <td><?php echo $row['sp_name']; ?></td>
                  <td title="Number"><?php echo $row['residents']; $totals['residents'] += $row['residents']; ?></td>
                  <td title="With Preferred Region"><?php echo $row['pregion']; $totals['pregion'] += $row['pregion']; ?></td>
                  <td title="With Preferred State"><?php echo $row['pstates']; $totals['pstates'] += $row['pstates']; ?></td>
                  <td title="No Preferred State/Region"><?php echo $row['pstopen']; $totals['pstopen'] += $row['pstopen']; ?></td>
                  <td title="With Preferred City"><?php echo $row['pcity']; $totals['pcity'] += $row['pcity']; ?></td>
                  <td title="With Preferred Community Type"><?php echo $row['commsize']; $totals['commsize'] += $row['commsize']; ?></td>
                  <td title="With Home Phone"><?php echo $row['hphone']; $totals['hphone'] += $row['hphone']; ?></td>
                  <td title="With Office Phone"><?php echo $row['ophone']; $totals['ophone'] += $row['ophone']; ?></td>
                  <td title="With Cell Phone"><?php echo $row['cphone']; $totals['cphone'] += $row['cphone']; ?></td>
                  <td title="With Email"><?php echo $row['emails']; $totals['emails'] += $row['emails']; ?></td>
                  <td title="With Address"><?php echo $row['addr']; $totals['addr'] += $row['addr']; ?></td>
                  <td title="With Med School Info"><?php echo $row['mschool']; $totals['mschool'] += $row['mschool']; ?></td>
                  <td title="AMG"><?php echo $row['namg']; $totals['namg'] += $row['namg']; ?></td>
                  <td title="With Visas"><?php echo $row['visa']; $totals['visa'] += $row['visa']; ?></td>
                  <td title="Public"><?php echo $row['checkins']; $totals['checkins'] += $row['checkins']; ?></td>
                </tr>
<?php 
	} // while
?>
                <tr>
                  <td>Totals</td>
                  <td title="Total Number"><?php echo $totals['residents']; ?></td>
                  <td title="Total With Preferred Region"><?php echo $totals['pregion']; ?></td>
                  <td title="Total With Preferred State"><?php echo $totals['pstates']; ?></td>
                  <td title="Total No Preferred State/Region"><?php echo $totals['pstopen']; ?></td>
                  <td title="Total With Preferred City"><?php echo $totals['pcity']; ?></td>
                  <td title="Total With Preferred Community Type"><?php echo $totals['commsize']; ?></td>
                  <td title="Total With Home Phone"><?php echo $totals['hphone']; ?></td>
                  <td title="Total With Office Phone"><?php echo $totals['ophone']; ?></td>
                  <td title="Total With Cell Phone"><?php echo $totals['cphone']; ?></td>
                  <td title="Total With Email"><?php echo $totals['emails']; ?></td>
                  <td title="Total With Address"><?php echo $totals['addr']; ?></td>
                  <td title="Total With Med School Info"><?php echo $totals['mschool']; ?></td>
                  <td title="Total AMG"><?php echo $totals['namg']; ?></td>
                  <td title="Total With Visas"><?php echo $totals['visa']; ?></td>
                  <td title="Total Public"><?php echo $totals['checkins']; ?></td>
                </tr>
              </table>
		  </div>
<?php	
	}
	else showLoginForm(); // UUID
	$style->ShowFooter();
?>
