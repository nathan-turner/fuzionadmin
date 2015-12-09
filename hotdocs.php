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
	if( empty($d1) ) $d1 = date("Y-m-d",strtotime("$d2 - 7 day"));
	else $d1 = date("Y-m-d",strtotime($d1));
	$page = $_REQUEST['pg'];
	$spec = $_REQUEST['spec'];
	if( !$page || !is_numeric($page) ) $page = 0;
	if( $UUID ) try {
		$db = db_career();
		$cksub =  "((iv_date between '$d1' and date_add('$d2',interval 1 day)) or (reg_date between '$d1' and date_add('$d2',interval 1 day)))"; 
		if( $spec && $spec !== '---' ) $cksub .= " and spec = '$spec'";
		$ordsub = " order by iv_date, reg_date";
		$sort = "  iv_date, reg_date";
		if($_GET["sort"]!=''){
			$sort = urldecode($_GET["sort"]);
			$ordsub = " order by ".urldecode($_GET["sort"]).", reg_date";
		}
		
		$sql = "select count(*) from physicians where inactive=0 and $cksub";
		$result = $db->query($sql);
		if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not get the report',__LINE__);
		list($totalcount) = $result->fetch_row();
		$result->free();
		$sql = 'select ph_id,fname,lname,spec,iv_date,reg_date,last_save,pending,checkin,status,pstatus,source,phg_source from physicians'
			." where $cksub and inactive=0 $ordsub LIMIT $page, ".PG_SIZE;
		$docs = $db->query($sql);
		if( !$docs ) throw new Exception(DEBUG?"$db->error : $sql":'Can not get the report',__LINE__);
		if( $docs->num_rows < PG_SIZE ) $lastpage = true;
	}
	catch(Exception $e) {
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
	}
	$style = new OperPage('PhysicianCareer Production Report',$UUID,'reports','managerrpts');
	$scrip2 = "<script type=\"text/javascript\" src=\"calendarDateInput.js\"></script>\n";
	$style->Output($scrip2);
 
 	if( $UUID ) {
			if( $ACCESS < 1 ) echo '<h1>Access Denied</h1>';
			else {
?>
              <h1>PhysicianCareer Production Report</h1>
<?php 
			if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
	<form action="hotdocs.php" method="get" name="datef">
		<table><tr>
		<td>From:</td><td> <script language="javascript">DateInput('d1', false, 'YYYY-MM-DD', '<?php echo $d1; ?>');</script></td>
		<td>To:</td><td><script language="javascript">DateInput('d2', false, 'YYYY-MM-DD', '<?php echo $d2; ?>');</script></td>
		</tr><tr>
		<td>Specialty:</td><td colspan="2"><?php echo $db?showSpecList($db,$spec):'ERROR'; ?></td>
		<td align="center"><input type="submit" name="submit" value="Select"></td>
		</tr>
		</table>
	</form>
<?php		if( $docs ) { ?>
              <p>Total results: <?php echo $totalcount; ?>. Results shown are from <?php echo $page+1; ?> to <?php echo $page+$docs->num_rows; ?>.</p>
              <table width="90%" >
                <tr>
                  <th align="center">Registered</th>
                  <th align="center"><a href="?sort=iv_date">Processed</a></th>
                  <th><a href="?sort=ph_id">ID#</a></th>
                  <th><a href="?sort=lname">Name</a></th>
                  <th align="center"><a href="?sort=spec">Specialty</a></th>
                  <th abbr="Practicing, Resident, Fellow" align="center"><a href="?sort=pstatus">PRF</a></th>
                  <th><a href="?sort=pstatus">Ver.</a></th>
                  <th><a href="?sort=source">Source</a></th>
                </tr>
<?php
		$pstu = array('Unspecified','Resident','Fellow','In Practice','Future Fellow');
		$pends = array('Verified','Pending','Unverified');
		$sorc = array('&nbsp;','Search engine','Email campaign','Our Rep.','Recommended by other','Recommended by director','Newsletter','Journal Ad','Internet Ad','Other Self-R.', 20 => 'PHG Database','Job Board','Email Blast','Web Page','Cold Call','Self Register', 29 => 'Other source');

		for( $i=0; $i < $docs->num_rows; $i++ ) {
			$doc = $docs->fetch_object();
?>
                <tr>
                  <td align="center"><?php echo $doc->reg_date?date('Y-m-d',strtotime($doc->reg_date)):''; ?>&nbsp;</td>
                  <td align="center"><?php echo $doc->iv_date?date('Y-m-d',strtotime($doc->iv_date)):''; ?>&nbsp;</td>
                  <td><?php echo "<a href='showdocpc.php?id=$doc->ph_id&lid=0&y=2005&pos=0' target='showdoc'>&nbsp;$doc->ph_id&nbsp;</a>"; ?></td>
                  <td><?php echo stripslashes($doc->fname).' '.stripslashes($doc->lname); ?></td>
                  <td align="center"><?php echo $doc->spec; ?></td>
                  <td align="center"><?php echo $pstu[$doc->pstatus]; ?></td>
                  <td><?php echo $pends[$doc->pending]; ?></td>
                  <td><?php if( $doc->source == 20 ) echo "$doc->phg_source (PHG)";
				  	else echo $sorc[$doc->source]; ?></td>
                </tr>
<?php
		}
		$docs->free();
?>
                <tr>
                  <td bgcolor="#E8E8EC"><?php
		if( $page ) echo "<a href='hotdocs.php?pg=".($page-PG_SIZE)."&d1=$d1&d2=$d2&sort=$sort'>Prev</a>";
		else echo '&nbsp;';
				  ?></td>
                  <td colspan="6" bgcolor="#E8E8EC" align="center"><?php 
				  if( $mesg ) echo $mesg;
				  else  // show navigation
				  	for( $i = 0,$j = 1; $i < $totalcount; $i += PG_SIZE, $j++ ) 
						echo $i == $page? "$j ":"<a href='hotdocs.php?pg=$i&d1=$d1&d2=$d2&sort=$sort'>$j</a> ";
				  ?></td>
                  <td align="right" bgcolor="#E8E8EC"><?php
		if( !$lastpage ) echo "<a href='hotdocs.php?pg=".($page+PG_SIZE)."&d1=$d1&d2=$d2&sort=$sort'>Next</a>";
		else echo '&nbsp;';
				  ?></td>
                </tr>
              </table>
              <p>Registered date is when they registered on their own. Processed date is when they were entered into the database, or interviewed, or verified. </p>
              <?php
			  // summary by source
				$sql = "select source,count(*) as cnt1 from physicians where $cksub and inactive=0 group by source order by cnt1 desc";
				$src = $db->query($sql);
				if( !$src ) echo DEBUG?"$db->error : $sql":'Can not get the summary '.__LINE__;
				$srcar = array();
				$i = 0; $sum1 = 0;
				while( $src1 = $src->fetch_object() ) {
					if( $i < 5 ) $srcar[] = $src1; else $suml += $src1->cnt1;
					$i++;
				}
				$src->free();
				$sql = "select phg_source,count(*) as cnt2 from physicians where $cksub and inactive=0 and phg_source is not null group by phg_source order by cnt2 desc";
				//echo $sql;
				$phgsrc = $db->query($sql);
				if( !$phgsrc ) echo DEBUG?"$db->error : $sql":'Can not get PHG summary '.__LINE__;
				$phgar = array();
				$j = 0; $sum2 = 0;
				while( $src2 = $phgsrc->fetch_object() ) {
					if( $j < 5 ) $phgar[] = $src2; else $sum2 += $src2->cnt2;
					$j++;
				}
				$phgsrc->free();
				$sorc[0] = 'N/A';
?>
<table style="text-align:center; width: 600px"><thead><th colspan="2">Top Sources</th><th colspan="2">Top PHG Sources</th></thead>
<tbody>
<?php 			for( $x = 0; $x < 5; $x++ ) { ?>
<tr><td><?php echo $x<=$i?$sorc[$srcar[$x]->source]:' '; ?></td><td><?php echo $x<=$i?$srcar[$x]->cnt1:' '; ?></td>
<td><?php echo $x<=$j?$phgar[$x]->phg_source:' '; ?></td><td><?php echo $x<=$j?$phgar[$x]->cnt2:' '; ?></td></tr>
<?php 			} ?>
<tr><td style="border-top:1px dotted #363;">All other sources:</td><td style="border-top:1px dotted #363;"><?php echo $sum1; ?></td><td style="border-top:1px dotted #363;">Other PHG sources:</td><td style="border-top:1px dotted #363;"><?php echo $sum2; ?></td></tr>
</tbody>
</table>
<?php
				} // docs
			} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>