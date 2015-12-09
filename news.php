<?php	
	   require("globals.php5");
    require("cookies.php5");
	if( $UUID && $ACCESS == 500 && isset($_POST['Submit']) ) {
		// notes
		$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
		extract($strippost,EXTR_SKIP);
		$note_text = substr(trim(strip_tags($note_text)),0,254);
		if( strlen($note_text) == 254 ) $note_text .= '-';
		if( is_numeric($note_shared) ) $note_shared = $note_shared > 500? 500: $note_shared;
		else $note_shared = 0;
		if( !empty($note_text) ) { // save note
			$db = db_clients();
			$sql = "insert into notes (uid,shared,note,res_id,year) values ($UUID,$note_shared,'$note_text',1,486)";
			$result = $db->query($sql);
			if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not save news',__LINE__);
		}
	}
	$style = new OperPage('News',$UUID,'links','');
	$style->Output();
	if( $UUID ) {
?>
<h1>Database News</h1>
              <table cellpadding="2" cellspacing="0">
                <tr>
                  <th>Date/Time</th>
                  <th>User</th>
                  <th>News</th>
                </tr>
<?php 
	try {
		if( !isset($db) ) $db = db_clients();
		$sql = "select note_id,date_format(dt,'%c/%e/%y %T') as datetim,n.uid,username,note, shared, res_id from notes n join operators u on n.uid = u.uid where year = 486 and res_id = 1 and shared <= $ACCESS order by dt desc";
		$result = $db->query($sql);
		$firstnote = true; unset($_SESSION['delete_note']);
		if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not retrieve news',__LINE__);
		for( $i = 0; $i < $result->num_rows; $i++ ) {
			$row = $result->fetch_assoc();
?>
                <tr>
                  <td><?php echo $row['datetim']; 
				  	if( $firstnote ) {
						if( $row['uid'] == $UUID || $ACCESS == 500 ) {
							$_SESSION['delete_note_str'] = "";
							$_SESSION['delete_note'] = $row['note_id'];
				  ?>
				  <a href="delnote.php" title="Delete Note"><img src="images/b_drop.png" width="16" height="16" border="0" align="absbottom" alt="X" title="Delete Note"></a><?php 
						}
						$firstnote = false;
				  	}
				  ?>
				  </td>
                  <td <?php if( $row['uid'] == $UUID ) echo ' style="text-weight: bold"'; ?>
				  ><?php echo $row['username']; ?>&nbsp;<?php 
				  	if( $row['shared'] ) echo ' <span style="font-size: 8pt" title="Restricted">&reg;</span>'; ?>
				  </td>
                  <td><?php echo strip_tags(stripslashes($row['note'])); ?></td>
                </tr>
<?php 
		} // for
	}
	catch(Exception $e) {
		echo "<tr><td colspan=3>Problem accessing news: ".$e->getMessage().' ('.$e->getCode().
			")</td></tr>";
	}
?>
              </table>
<?php if( $ACCESS == 500 ) { ?>
	<form name="newsf" method="post" action="news.php">
	 <table>
	                   <tr>
                    <td style="border-top: thin solid #003399; ">Add  News:</td>
                    <td style="border-top: thin solid #003399;"><label>
      Access: <input name="note_shared" type="text" value="0"></label>
      <br>
      <span class="style2">255 symbols max.</span></td>
                    <td style="border-top: thin solid #003399;"><textarea name="note_text" cols="55" rows="4" id="note_text"></textarea></td>
                  </tr>
	                   <tr>
	                     <td>&nbsp;</td>
	                     <td>&nbsp;</td>
	                     <td align="right"><input type="submit" name="Submit" value="Submit">
                         &nbsp;&nbsp;
                         <input type="reset" name="Reset" value="Reset"></td>
       </tr>
                </table>
		<p>Access: 0 = shared, 1-49 = data entry, 50-199 = database manager, 200-299 = customer support, 300-399 = account manager, 400-499 = administrator, 500 = super administrator (you).</p>
</form>
<?php
		} // 500
	} // UUID
    $style->ShowFooter();
?>