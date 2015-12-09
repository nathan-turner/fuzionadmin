<?php
    require("globals.php5");
	define(PG_SIZE,30);
    require("cookies.php5");
    // $UUID <> 0 if auth
	// params: 	$_REQUEST['id'], $_REQUEST['y'], $_REQUEST['pg']
	$lid = $_REQUEST['id'];
	$yer = '2005'; // unused
	$page= $_REQUEST['pg'];
	//$very= $_REQUEST['ck']; // true = show checkins
	$peek = $_REQUEST['peek'];
	if( $peek == $UUID ) unset($peek);
	$cook_lid = $lid? $lid.$peek: $yer.$peek;
	if( !isset($_REQUEST['pg']) || !is_numeric($page) ) {
		if( $_COOKIE["pos_$cook_lid"] ) {
			//$mesg = $_COOKIE["pos_$lid"]." cookie $lastpos";
			$page = $_COOKIE["pos_$cook_lid"] - ($_COOKIE["pos_$cook_lid"] % PG_SIZE); 
			$lastpos = $_COOKIE["pos_$cook_lid"] % PG_SIZE;
		} else $page = 0;
	}
	else setcookie("pos_$cook_lid",$page,time()+3600*24*15); // set new cookie
	// pos_1,2,... cookie: stores last position in the list. if no page is given, it tries to show
	// that page and hilite position. $page, $lastpos. If lastpos is not set, there was no cookie.
	// the cookie itself is set in showdoc.php, and here, if page is set
	if( !$lid && !$peek ) {
		$verboz = stripslashes($_SESSION['verboz']);
		if( $verboz ) setcookie('verboz',htmlspecialchars($verboz),time()+3600*24*7);
		else $verboz = stripslashes($_COOKIE['verboz']);
	}
	if ( is_numeric($lid) && $lid > 128 ) $verboz = stripslashes($_SESSION['verboz'.$lid]);
	if( $UUID && $ACCESS && isset($lid) ) try {
		$db = db_career();
		$show = 'showdocpc.php';
		$redfld = 'status'; $redtitle = 'Private Record';
		$greenfld = 'checkin'; $greentitle = 'Public Record';
		$peekuid = $UUID;
		if( $peek && is_numeric($peek) && $peek != $UUID ) {
			// check if peek is allowed: lists have same ACCT and (you are MASTER or list is shared)
			$sql = "select acct,shared from custlistdesc where listid = $lid and uid = $peek";
			$result = $db->query($sql);
			if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not find list',__LINE__);
			list($pact,$psha) = $result->fetch_row();
			$result->free();
			if( $pact == $ACCT && ($psha < $ACCESS) ) $peekuid = $peek;
			else throw new Exception('You can not peek into this list',__LINE__);
			$peekarg = "&peek=$peek";
		}
		//$yerex = is_numeric($yer)? " and resyear=$yer":'';
		if( !is_numeric($lid) ) $lid = 0;
		if( $lid && ($lid <= 128 || $lid == 250)) {
			$sql = "select name,description from custlistdesc where listid = $lid and uid = $peekuid";
			$result = $db->query($sql);
			if( !$result ) throw new Exception(DEBUG?"$db->error : $sql":'Can not find list',__LINE__);
			list($nver,$boze) = $result->fetch_row();
			$result->free();
			$vlid = $peek?"$lid/$peek":$lid;
			$verboz = "$boze<br>List #$vlid &ldquo;$nver&rdquo;";
		}
		$seekid = $_REQUEST['seek'];
		if( !$seekid || !is_numeric($seekid) ) unset($seekid);

		$result = $db->query("select count(*) from  custlistsus where owneruid = $peekuid and listid = $lid");
		list($totalcount) = $result->fetch_row();
		if( $page > $totalcount ) $page = 0;
		if( $seekid ) {
			$result = $db->query("select count(*) from  custlistsus where owneruid = $peekuid and listid = $lid and memberuid < $seekid");
			list($seekres) = $result->fetch_row();
			$page = $seekres - ($seekres % PG_SIZE); 
			$lastpos = $seekres % PG_SIZE;
		}
		$result = $db->query("select * from physicians inner join custlistsus on (ph_id = memberuid and owneruid = $peekuid and listid = $lid) left outer join pendings on ph_id = phid LIMIT $page, ".PG_SIZE);
		//echo "select * from physicians inner join custlistsus on (ph_id = memberuid and owneruid = $peekuid and listid = $lid) left outer join pendings on ph_id = phid LIMIT $page, ".PG_SIZE;
		//$result = $db->query("select * from physicians inner join custlistsus on (ph_id = memberuid and owneruid = $peekuid and listid = $lid) left outer join pendings on ph_id = phid WHERE (SELECT COUNT(*) FROM physicians_no_res AS r WHERE r.ph_id=ph_id)=0 LIMIT $page, ".PG_SIZE);
/*select * from physicians AS p inner join custlistsus on (ph_id = memberuid and owneruid = 1 and listid = 250) left outer join pendings on ph_id = phid 
LEFT JOIN physicians_no_res AS r ON r.ph_id=p.ph_id
WHERE (SELECT COUNT(*) FROM physicians_no_res AS r WHERE r.ph_id=ph_id)=0
LIMIT 0, 30 */		
		$very = true;

		if( $result->num_rows < PG_SIZE ) $lastpage = true;
	}
	catch(Exception $e) {
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
		unset($result);
	}
	$style = new OperPage('Results',$UUID,'residents','ressearch');
	$scrip = <<<HereStyle
<style type="text/css">
<!--
.style1 {color: #333333}
.style2 {
	color: #333333;
	border-top: thin solid #3399CC;
	border-right: thin none #3399CC;
	border-bottom: thin solid #3399CC;
	border-left: thin none #3399CC;
}
-->
</style>
HereStyle;
	$style->Output($scrip);
	
	if( $UUID ) {
?>
              <h1>Results - <?php echo $yer?CustListYear($yer):'Mixed'; ?></h1>
<?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
	if( $verboz ) echo "<p id='warning_msg'>$verboz</p>"
?>
              <p>Total results: <?php echo $totalcount; ?>. Results shown are from <?php echo $page+1; ?> to <?php echo $page+$result->num_rows; ?>.</p>
			  <form name="seekf" method="get" action="results.php">
			    <p>Find an ID# in this list:
			      <input name="seek" type="text"> <input name="seekbutt" type="submit" value="Seek"></p>
			  <input name="id" type="hidden" value="<?php echo $lid; ?>">
			  <input name="ck" type="hidden" value="<?php echo $very; ?>">
			  <input name="peek" type="hidden" value="<?php echo $peek; ?>">
			  <input name="pg" type="hidden" value="<?php echo $page; ?>">
			  </form>
              <table>
                <tr>
                  <th width="30">ID#</th>
                  <th width="45">Name</th>
                  <th width="42">Flags</th>
                  <th width="28">City</th>
                  <th width="20">State</th>
                  <th width="25">Spec.</th>
                  <th width="35">Self Reg.</th>
                  <th width="36">Review Date</th>
                  <th width="35">Pending</th>
                </tr>
<?php
		for( $i=0; $result && $i < $result->num_rows; $i++ ) {
			$doc = $result->fetch_object();
			$docid = $doc->ph_id;
			//if( $doc->$greenfld && !$very ) continue; // hide verified/accepted
			$hilit = '';
			if( isset($lastpos) && $lastpos == $i ) $hilit = ' class="style2"';
?>
                <tr>
                  <td<?php echo $hilit; ?>><?php 
				  	if( $doc->$greenfld ) { ?><img src="images/greencheck.gif" width="30" height="20"
					title="<?php echo $greentitle; ?>">
					<?php
					}
					elseif( $doc->$redfld ) { ?><img src="images/redcheck.gif" width="30" height="20"
					title="<?php echo $redtitle; ?>">
					<?php
					}
					else { ?><img src="images/nocheck.gif" width="30" height="10">
					<?php
					}
				  	echo $docid; 
					?></td>
                  <td<?php echo $hilit; ?>><?php echo "<a href='$show?id=$docid&lid=$lid&ck=$very$peekarg&pos=".($page+$i)."'>".stripslashes($doc->fname).' '.stripslashes($doc->lname).'</a>'; ?></td>
				  <td<?php echo $hilit; ?>><?php 
				  		//regular: home addr, office addr, email, homephone, officephone, cellphone
							if( $doc->email || $doc->email_2nd ) echo "<img src='images/mail.png' title='Email Address' alt='E' border=0 />";
							if( $doc->addr1 || $doc->addr2 ) echo "<img src='images/house.png' title='Home Address' alt='H' border=0 />";
							if( $doc->ofaddr1 || $doc->ofaddr2 ) echo "<img src='images/office.png' title='Office Address' alt='O' border=0 style='margin-right: 1px' />";
							if( $doc->homephone ) echo "<img src='images/hphone.png' title='Home Phone' alt='HP' border=0 />";
							if( $doc->officephone ) echo "<img src='images/ophone.png' title='Office Phone' alt='OP' border=0 style='margin-left: 1px' />";
							if( $doc->cellphone ) echo "<img src='images/cphone.png' title='Cell Phone' alt='CP' border=0 />";
				   ?></td>
                  <td<?php echo $hilit; ?>><?php echo stripslashes($doc->city); ?></td>
                  <td<?php echo $hilit; ?>><?php echo $doc->state; ?></td>
                  <td<?php echo $hilit; ?>><?php echo $doc->spec; ?></td>
                  <td<?php echo $hilit; ?>><?php echo substr($doc->reg_date,0,10); ?>&nbsp;</td>
                  <td<?php echo $hilit; ?>><?php echo $doc->iv_date; ?>&nbsp;</td>
                  <td<?php echo $hilit; ?>><?php echo $doc->pdate; ?>&nbsp;</td>
                </tr>
<?php
		}
		//array_push($ResYears,2006);
?>
                <tr>
                  <td bgcolor="#E8E8EC"><?php
		if( $page ) echo "<a href='results.php?id=$lid&ck=$very$peekarg&pg=".($page-PG_SIZE)."'>Prev</a>";
		else echo '&nbsp;';
				  ?></td>
                  <td colspan="7" bgcolor="#E8E8EC" align="center"><?php 
				  if( $mesg ) echo $mesg;
				  else  // show navigation
				  	for( $i = 0,$j = 1; $i < $totalcount; $i += PG_SIZE, $j++ ) 
						echo $i == $page? "$j ":"<a href='results.php?id=$lid&ck=$very$peekarg&pg=$i'>$j</a> ";
				  ?></td>
                  <td align="right" bgcolor="#E8E8EC"><?php
		if( !$lastpage ) echo "<a href='results.php?id=$lid&ck=$very$peekarg&pg=".($page+PG_SIZE)."'>Next</a>";
		else echo '&nbsp;';
				  ?></td>
                </tr>
              </table>
		<p>
			  <?php 
			  	if( (!$lid || $lid > 128) && !$peek && $yer ) {
			  ?>
              <form name="formsave" method="post" action="custlists.php">
                <input name="year_new" type="hidden" id="year_new" value="<?php echo $yer; ?>">
                <input name="desc_new" type="hidden" id="desc_new" value="<?php echo "$verboz Saved on ".date('c'); ?>">
                <input name="source_new" type="hidden" id="source_new" value="<?php echo $lid; ?>">              
                <input name="action_new" type="hidden" id="action_new" value="11">              
                <p>Quick Save Results: 
                  <input name="name_new" type="text" id="name_new" value="<?php echo 'saved-'.time(); ?>" maxlength="50">
                  <input name="submit" type="submit" id="submit" value="Save">
</p>
              </form>
              <?php	
			  } // lid
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
