<?php
    require("globals.php5");
    require("cookies.php5");
    // $UUID <> 0 if auth
	if( $UUID && $ACCESS ) try {
		$sort = addslashes(urldecode($_GET["sort"]));
		$totalcount = 0; $resdb = db_career();
		if($sort!=''){
			$sql = 'select ph_id,fname,lname,spec,city,state from physicians'
				." where pending=2 and inactive=0 order by ".$sort."";
		}
		else{
		$sql = 'select ph_id,fname,lname,spec,city,state from physicians'
				." where pending=2 and inactive=0";
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
              <h1>Review List - <?php echo $totalcount; ?> record<?php echo $totalcount==1?'':'s'; ?></h1>
<?php 
			if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
              <p>This is the list of records which were newly entered, or marked Interview Completed, or Self-Registered, but Database Manager decided that information is incorrect or missing or insufficient and sent them back to you for review. You can correct the record, add notes, or add more information if available, and then submit record to the manager by marking it Completed  and pressing Save. Click on the name to edit doctor's record (new window will pop up).</p>
			  <p><a href="review_non_res.php">Review non-residency physicians</a></p>
			  <form action="review_handler.php" method="post">
              <table>
                <tr>
				  <th> </th>
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
                  <td><input type="checkbox" name="phid[]" value="<?php echo $doc->ph_id; ?>" /></td>
				  <td><?php echo $doc->ph_id; ?></td>
                  <td><?php echo "<a href='showdocpc.php?id=$doc->ph_id&lid=252&y=2005&pos=0' target='showdoc'>".stripslashes($doc->fname).' '.stripslashes($doc->lname).'</a>'; ?></td>
                  <td><?php echo stripslashes($doc->city); ?></td>
                  <td><?php echo $doc->state; ?></td>
                  <td><?php echo $doc->spec; ?></td>
                </tr>
<?php
			} // for
			$YearRes->free();
?>
              </table>
			  <br/><br/>
			  <input type="submit" value="Add to No Residency List" name="movebtn" />
			  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			  <input type="submit" value="PERMANENTLY DELETE" name="deletebtn" />
			  </form>
              <p>&nbsp;</p>
<?php
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
