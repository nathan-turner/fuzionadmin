<?php
    require("globals.php5");
    require("cookies.php5");
	$mesg = '';
	$today = time();
	$exp = $_REQUEST['exp'];
	$sort = $_REQUEST['sort'];
	$page = $_REQUEST['page'];
	$num_exp = 0;
	$num_exp1 = 0;
	$num_exp2= 0;
	if( $UUID && $ACCESS && $ACCESS >= 200 ) try {
		$db = db_clients();
		$sortex = '';
		if( $sort == 'A' ) $sortex = ' order by acct';
		elseif( $sort == 'M' ) $sortex = ' order by email';
		elseif( $sort == 'N' ) $sortex = ' order by firstname, lastname';
		elseif( $sort == 'C' ) $sortex = ' order by company';
		elseif( $sort == 'S' ) $sortex = ' order by state, city';
		elseif( $sort == 'E' ) $sortex = ' order by exp_date';
		$resfrom = 0;
		if( $page && is_numeric($page) ) $resfrom = $page;
		$sql = "select uid, acct, email, firstname, lastname, company, city, state, exp_date, (case when status=0 then 'inactive' when exp_date < curdate() then 'expired' else 'active' end) as statut from clients where master_acct = 1 and status > 0 $sortex LIMIT $resfrom,100";
		$result = $db->query($sql);
		if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not execute query',__LINE__);
	}
	catch(Exception $e) {
		$mesg = 'Attention: '.$e->getMessage().' ('.$e->getCode().')<br>';
		//unset($oper);
	}
	$style = new OperPage('Customer Statistics',$UUID,'reports','customerstats');
	$style->Output();

	if( $UUID ) {
			if( $ACCESS < 200 ) echo '<h1>Access Denied</h1>';
			else {
?>
              <h1>Customer Statistics &amp; Expiration Report</h1>
              <?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
?>
			  <table cellspacing="0" cellpadding="1" style="width:100% ">
                <tr>
                  <th><a href="customerstats.php?sort=A&exp=<?php echo $exp; ?>">ID/Account#</a></th>
                  <th><a href="customerstats.php?sort=M&exp=<?php echo $exp; ?>">Email</a></th>
                  <th><a href="customerstats.php?sort=N&exp=<?php echo $exp; ?>">Name</a></th>
                  <th><a href="customerstats.php?sort=C&exp=<?php echo $exp; ?>">Company</a></th>
                  <th><a href="customerstats.php?sort=S&exp=<?php echo $exp; ?>">City, State</a></th>
                  <th>Status</th>
				  <th><a href="customerstats.php?sort=E&exp=<?php echo $exp; ?>">Exp. Date</a></th>
                </tr>
<?php 
		$totals = $result->num_rows;
		for( $i=0; $i < $totals; $i++ ) {
			// uid, acct, email, firstname, lastname, company, city, state, statut, exp_date
			$row = $result->fetch_object();
			$d = split("-",$row->exp_date);
			$date_seconds = mktime(0,0,0,$d[1],$d[2],$d[0]);
			if(     $today            > $date_seconds) $num_exp ++;
			elseif(($today + 2592000) > $date_seconds) $num_exp1 ++;
			elseif(($today + 5184000) > $date_seconds) $num_exp2 ++;
			if( $exp == 'exp2' && ($today + 5184000) <= $date_seconds ) continue;
			if( $exp == 'exp1' && ($today + 2592000) <= $date_seconds ) continue;
			if( $exp == 'exp' && $today <= $date_seconds ) continue;
?>
                <tr>
                  <td><a href="custedit.php?cid=<?php echo $row->uid; ?>"><?php echo "$row->uid / $row->acct"; ?></a></td>
                  <td><?php echo stripslashes($row->email); ?></td>
                  <td><?php echo stripslashes("$row->firstname $row->lastname"); ?></td>
                  <td><?php echo stripslashes($row->company); ?></td>
                  <td><?php echo stripslashes("$row->city, $row->state"); ?></td>
                  <td><?php echo $row->statut; ?></td>
				  <td><?php echo $row->exp_date; ?></td>
                </tr>
<?php 
		} // for (iteration)
			if( $result->num_rows >= 100 ) {
?>
			   <tr><td colspan="7"><form action="customerstats.php" method="get" name="nextf">
			   More Available: <input type="submit" value="Next Page" name="nextp">
                    <input name="page" type="hidden" value="<?php echo $resfrom+100; ?>">
                    <input name="sort" type="hidden" value="<?php echo $sort; ?>">
                    <input name="exp" type="hidden" value="<?php echo $exp; ?>">
			   </form>
			   </td></tr>
<?php
			} // 100
?>
        </table>     
<?php 	
		
?>			  			
			<p>Click on links below to filter accounts based on that criteria.</p>
			<table style="width:auto ">
			  	<tr>
					<td><a href="customerstats.php?sort=<?php echo $sort; ?>&exp=">Accounts on this page</a></td>
					<td align="right"><?php echo $totals; ?></td>
				</tr>
			  	<tr>
					<td><a href="customerstats.php?sort=<?php echo $sort; ?>&exp=exp">Expired accounts</a></td>
					<td align="right"><?php echo $num_exp; ?></td>
				</tr>
				<tr>
					<td><a href="customerstats.php?sort=<?php echo $sort; ?>&exp=exp1">Accounts that expire within 1 month</a></td>
					<td align="right"><?php echo $num_exp1; ?></td>
				</tr>
				<tr>
					<td><a href="customerstats.php?sort=<?php echo $sort; ?>&exp=exp2">Accounts that expire within 2 months</a></td>
					<td align="right"><?php echo $num_exp2; ?></td>
				</tr>
</table>
			<p>Click here for <a href="customeract.php?sort=<?php echo $sort; ?>&exp=">Customer Activity Report</a></p> 
<?php		} // ACCESS
		} // UUID
		else showLoginForm(); 
		$style->ShowFooter();
?>