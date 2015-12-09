<?php
    require("globals.php5");
	define(PG_SIZE,50);
    require("cookies.php5");
    // $UUID <> 0 if auth
	$mesg = '';
	$d1 = $_REQUEST["d1"];
	$d2 = $_REQUEST["d2"];
	if( empty($d2) ) $d2 = date("Y-m-d");
	else $d2 = date("Y-m-d",strtotime($d2));
	if( empty($d1) ) $d1 = date("Y-m-d",strtotime("$d2 - 1 day"));
	else $d1 = date("Y-m-d",strtotime($d1));
	$checkin = $_REQUEST["ck"]; // 1 0
	$page = $_REQUEST['pg'];
	if( !$page || !is_numeric($page) ) $page = 0;
	if( $UUID && $ACCESS >= 50 ) try {
		$db = db_career();
		switch ($checkin) {
			case 0:
				$cksub = "lastlogdate between '$d1' and date_add('$d2',interval 1 day)"; 
				$ordsub = " order by lastlogdate";
				break;
			case 1:
				$cksub =  "iv_date between '$d1' and date_add('$d2',interval 1 day)"; 
				$ordsub = " order by iv_date";
				break;
			case 2:
				$cksub =  "reg_date between '$d1' and date_add('$d2',interval 1 day)"; 
				$ordsub = " order by reg_date";
				break;
			case 3:
				$cksub =  "last_save between '$d1' and date_add('$d2',interval 1 day)"; 
				$ordsub = " order by last_save";
				break;
			default:
				$cksub =  "last_save between '$d1' and date_add('$d2',interval 1 day)"; 
				$ordsub = " order by last_save";
				break;
		}
		$sql = "select count(*) from physicians where inactive=0 and $cksub";
		$result = $db->query($sql);
		if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not get the report',__LINE__);
		list($totalcount) = $result->fetch_row();
		$result->free();
		$sql = 'select ph_id,fname,lname,spec,lastlogdate,iv_date,reg_date,last_save,checkin,status,pstatus,pending from physicians'
			." where $cksub and inactive=0 $ordsub LIMIT $page, ".PG_SIZE;
		$docs = $db->query($sql);
		if( !$docs ) throw new Exception(DEBUG?"$db->error : $sql":'Can not get the report',__LINE__);
		if( $docs->num_rows < PG_SIZE ) $lastpage = true;
	}
	catch(Exception $e) {
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
	}
	$style = new OperPage('Physician Career Report',$UUID,'reports','managerrpts');
	$scrip2 = "<script type=\"text/javascript\" src=\"calendarDateInput.js\"></script>\n";
	$style->Output($scrip2);
 
 	if( $UUID ) {
			if( $ACCESS < 50 ) echo '<h1>Access Denied</h1>';
			else {
?>
              <h1>Physician Career Report</h1>
<?php 
			if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
	<form action="pcarrpt.php" method="get" name="datef">
		<table><tr>
		<td>Report since:</td><td> <script language="javascript">DateInput('d1', false, 'YYYY-MM-DD', '<?php echo $d1; ?>');</script></td>
		<td rowspan="2">Select by:</td>
		<td><input type="radio" name="ck" value="0" <?php if( !$checkin ) echo "checked"; ?> /> Login date</td>
		</tr><tr>
		<td>Report until:</td><td><script language="javascript">DateInput('d2', false, 'YYYY-MM-DD', '<?php echo $d2; ?>');</script></td>
		<td><input type="radio" name="ck" value="1" <?php if( $checkin==1 ) echo "checked"; ?> /> Verified date</td>
		</tr><tr>
		<td colspan="3" align="center" rowspan="2"><input type="submit" name="submit" value="Select"></td>
		<td><input type="radio" name="ck" value="2" <?php if( $checkin==2 ) echo "checked"; ?> /> Registration date</td>
		</tr><tr>
		<td><input type="radio" name="ck" value="3" <?php if( $checkin==3 ) echo "checked"; ?> /> Last save date</td>
		</tr>
		</table>
	</form>
<?php		if( $docs ) { ?>
              <p>Total results: <?php echo $totalcount; ?>. Results shown are from <?php echo $page+1; ?> to <?php echo $page+$docs->num_rows; ?>.</p>
              <table width="90%" >
                <tr>
                  <th align="center">Log In</th>
                  <th align="center">Verified</th>
                  <th align="center">Registration</th>
                  <th align="center">Last Save</th>
                  <th>ID#</th>
                  <th>Name</th>
                  <th align="center">Specialty</th>
                  <th abbr="Practicing, Resident, Fellow" align="center">PRF</th>
                  <th>Status</th>
                </tr>
<?php
		$pstu = array('Unspecified','Resident','Fellow','In Practice','Future Fellow');

		for( $i=0; $i < $docs->num_rows; $i++ ) {
			$doc = $docs->fetch_object();
?>
                <tr>
                  <td align="center"><?php echo $doc->lastlogdate?date('Y-m-d',strtotime($doc->lastlogdate)):''; ?>&nbsp;</td>
                  <td align="center"><?php echo $doc->iv_date?date('Y-m-d',strtotime($doc->iv_date)):''; ?>&nbsp;</td>
                  <td align="center"><?php echo $doc->reg_date?date('Y-m-d',strtotime($doc->reg_date)):''; ?>&nbsp;</td>
                  <td align="center"><?php echo $doc->last_save; ?></td>
                  <td><?php echo "<a href='showdocpc.php?id=$doc->ph_id&lid=0&pos=0' target='showdoc'>&nbsp;$doc->ph_id&nbsp;</a>"; ?></td>
                  <td><?php echo stripslashes($doc->fname).' '.stripslashes($doc->lname); ?></td>
                  <td align="center"><?php echo $doc->spec; ?></td>
                  <td align="center"><?php echo $pstu[$doc->pstatus]; ?></td>
                  <td><?php echo $doc->status?($doc->checkin?'Public':'Private'):'Inactive'; ?>
				  <?php echo $doc->pending==1? ', V.pending':''; ?></td>
                </tr>
<?php
		}
		$docs->free();
?>
                <tr>
                  <td bgcolor="#E8E8EC"><?php
		if( $page ) echo "<a href='pcarrpt.php?pg=".($page-PG_SIZE)."&ck=$checkin&d1=$d1&d2=$d2'>Prev</a>";
		else echo '&nbsp;';
				  ?></td>
                  <td colspan="5" bgcolor="#E8E8EC" align="center"><?php 
				  if( $mesg ) echo $mesg;
				  else  // show navigation
				  	for( $i = 0,$j = 1; $i < $totalcount; $i += PG_SIZE, $j++ ) 
						echo $i == $page? "$j ":"<a href='pcarrpt.php?pg=$i&ck=$checkin&d1=$d1&d2=$d2'>$j</a> ";
				  ?></td>
                  <td align="right" bgcolor="#E8E8EC"><?php
		if( !$lastpage ) echo "<a href='pcarrpt.php?pg=".($page+PG_SIZE)."&ck=$checkin&d1=$d1&d2=$d2'>Next</a>";
		else echo '&nbsp;';
				  ?></td>
                </tr>
              </table>
              <p>&nbsp;</p>
<?php
				} // docs
			} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>