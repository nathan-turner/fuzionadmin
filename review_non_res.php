<?php
    require("globals.php5");
    require("cookies.php5");
    // $UUID <> 0 if auth
	if( $UUID && $ACCESS ) try {
		$sort = addslashes(urldecode($_GET["sort"]));
		$totalcount = 0; $resdb = db_career();
		if($sort!=''){
			$sql = 'select ph_id,fname,lname,spec,city,state from physicians_no_res'
				."  order by ".$sort."";
		}
		else{
		$sql = 'select ph_id,fname,lname,spec,city,state from physicians_no_res';
				
		}
		//echo $sql;
		$YearRes = $resdb->query($sql);
		if( !$YearRes ) throw new Exception(DEBUG?$resdb->error.": $sql":'Can not get review list',__LINE__);
		$totalcount = $YearRes->num_rows;
		// ok: results.
	}
	catch(Exception $e) {
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
	}
	$style = new OperPage('Review List',$UUID,'reports','');
	$style->Output();
	
	if( $UUID ) {
?>
              <h1>Physicians with no residency - <?php echo $totalcount; ?> record<?php echo $totalcount==1?'':'s'; ?></h1>
<?php 
			if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
              <p><a href="review.php">Back to Review List</a></p>
			  <form action="review_handler.php" method="post">
              <table>
                <tr>
				  
                  <th><a href="?sort=ph_id">ID#</a></th>
                  <th><a href="?sort=fname">Name</a></th>
                  <th><a href="?sort=city">City</a></th>
                  <th><a href="?sort=state">State</a></th>
                  <th><a href="?sort=spec">Specialty</a></th>
                </tr>
<?php
		for( $i=0; $i < $totalcount; $i++ ) {
			$doc = $YearRes->fetch_object(); 
?>
                <tr>
                  
				  <td><?php echo $doc->ph_id; ?></td>
                  <td><?php echo "".stripslashes($doc->fname).' '.stripslashes($doc->lname).''; ?></td>
                  <td><?php echo stripslashes($doc->city); ?></td>
                  <td><?php echo $doc->state; ?></td>
                  <td><?php echo $doc->spec; ?></td>
                </tr>
<?php
			} // for
			$YearRes->free();
?>
              </table>
			  
			  </form>
              <p>&nbsp;</p>
<?php
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
