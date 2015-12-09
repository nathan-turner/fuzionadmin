<?php
    require("globals.php5");
    require("cookies.php5");

    $redir = ''; $result = true; $mesg = '';
	//$formpage = 1;
	$ooid = $_REQUEST['oid'];
	$uacct = $_REQUEST['acct'];
	$usid = $_REQUEST['cid'];
	$masid = $_REQUEST['mas'];
	if( !is_numeric($usid) ) $usid = $UUID;
	if( !is_numeric($masid) ) $masid = $UUID;
	if( !is_numeric($uacct) ) $uacct = 0;
	if( !is_numeric($ooid) ) $ooid = 0;
	if ( $ooid && $UUID && $ACCESS >= 200 ) try {
		$db = db_career();

/*
	uses tables `questions`, `selections`, `opp_answers`.
	q_type:
		1 = binary checkboxen
		2 = binary rating (sets of 5 radio/check buttons)
		3 = radio or drop-down

	`questions` (
	  `qid` int(10) unsigned NOT NULL auto_increment,
	  `q_spec` char(3) NOT NULL default '',
	  `q_order` smallint(6) default NULL,
	  `p_question` varchar(200) NOT NULL default '',
	  `c_question` varchar(200) NOT NULL default '',
	  `q_type` tinyint(4) NOT NULL default '1',
	  `q_weight` smallint(6) NOT NULL default '1000',
	  
	  "select * from questions where q_spec = '$spec' order by q_order"
	  
	`selections` (
	  `sel_id` int(10) unsigned NOT NULL auto_increment,
	  `s_qid` int(10) unsigned NOT NULL default '0',
	  `sel` varchar(100) default NULL,
	  `s_val` int(20) unsigned NOT NULL default '0',
	  
	  "select * from selections where s_qid = $qid order by s_val"
	
	`opp_answers` (
	  `o_qid` int(10) unsigned NOT NULL default '0',
	  `o_oid` int(10) unsigned NOT NULL default '0',
	  `answer` int(20) unsigned NOT NULL default '0',
	  `o_qtype` tinyint(4) NOT NULL default '1',

	  "REPLACE opp_answers VALUES ($qid,$ooid,$answer,$qtype)"
	  "select answer,o_qtype from opp_answers where o_oid = $ooid and o_qid = $qid"
	  
	`ratingop` (
	  `roid` int(11) NOT NULL default '0',
	  `ro_spec` char(3) NOT NULL default '',
	  `ro1` bigint(20) unsigned NOT NULL default '0',
	  `ro2` bigint(20) unsigned NOT NULL default '0',
	  `ro3` bigint(20) unsigned NOT NULL default '0',
	  `ro4` bigint(20) unsigned NOT NULL default '0',
	  `ro5` bigint(20) unsigned NOT NULL default '0',
	  `ro6` bigint(20) unsigned NOT NULL default '0',
	  `roamen` set('B','A','M','R','S') default NULL,
	  `rosch` set('P','V','R') default NULL,
	  `rosalmin` int(11) NOT NULL default '0',
	  `rosalmax` int(11) NOT NULL default '0',
	  `rostate` set('AK','AL','AR','AZ','CA','CO','CT','DC','DE','FL','GA','HI','IA','ID','IL','IN','KS','KY','LA','MA','MD','ME','MI','MN','MO','MS','MT','NC','ND','NE','NH','NJ','NM','NV','NY','OH','OK','OR','PA','PR','RI','SC','SD','TN','TX','UT','VA','VT','WA','WI','WV','WY') default NULL,
	  `roqualif` set('IMG','J1','RF','P','BC','BE') default NULL,
	  `rocomm` set('S','C','M') default NULL,
	  
	  "UPDATE ratingop SET ro1=$answer1,ro2=$answer2,... WHERE roid = $ooid"

*/
		$opp = new Opportunity($db,$ooid);

	$spec = $opp->specialty;
	$quest = $db->query("select * from questions where q_spec = '$spec' order by q_order");
	if( !$quest ) throw new Exception(DEBUG?$this->db->error:'Can not fetch questions', __LINE__);
	if( $quest->num_rows && $opp->status == 1 ) {
				$cdb = db_clients();
				$client = $cdb->query("select firstname, lastname, company from clients where uid=$usid");
				if( $client ) list($cfirst,$clast,$cco) = $client->fetch_row();
				$client->free();

		if (isset($_POST['submit']) || isset($_POST['submit1']) || isset($_POST['submit2'])) {
				//  $mesg .= 'SUBMIT';
			  
			$postan = array(); // all answers
			foreach( $_POST as $key => $value ) {
				$expost = explode('_', $key);
				if( $expost[0] == 'q' && is_numeric($value) ) {
					if( isset($postan[$expost[1]]) ) $postan[$expost[1]] = bcadd($value,$postan[$expost[1]]);
					else $postan[$expost[1]] = $value;
					//  $mesg .= ' PAN '.$expost[1].'='.$postan[$expost[1]];
				}
			}
			$ros = array(); $ri = 0;
			while( $qu = $quest->fetch_object() ) {
				$qid = $qu->qid;
				$qtype = $qu->q_type;
				if( isset($postan[$qid]) || $qtype == 1 ) { // allow to not to check any boxen
					$ans = isset($postan[$qid])? $postan[$qid]: 0;
					$ros[$ri++] = $ans;
					$sql = "REPLACE opp_answers VALUES ($qid,$ooid,'$ans',$qtype)";
					$result = $db->query($sql);
					if( !$result ) throw new Exception(DEBUG?"{$db->error}: $sql":'Can not replace answers', __LINE__);
					//  $mesg .= " SQL $sql";
				}
				elseif( $qtype != 1 ) $mesg .= " Unanswered: &quot;$qu->c_question&quot;; ";
			}
			// ratingop here
			$rrr = '';
			for( $ri = 0; $ri < 6; $ri++ ) {
				if( !isset($ros[$ri]) ) $ros[$ri] = 0;
				$rrr .= ($rrr?',':'').'ro'.($ri+1).'='.$ros[$ri];
			}
			if( $rrr ) {
				$sql = "update ratingop set $rrr where roid = $ooid";
				$result = $db->query($sql);
				if( !$result ) throw new Exception(DEBUG?"{$db->error}: $sql":'Can not update aux answers', __LINE__);
				$opp->rerate();
			}
			if( $mesg ) {
				$result = false;
				$mesg = "Please correct the following before proceeding: $mesg";
			}
			if( $result ) { 
				$redir = isset($_POST['submit2'])?"opportunadmin.php?oid=$ooid&acct=$uacct&cid=$usid&mas=$masid":"opportunadmin.php?acct=$uacct&cid=$usid&mas=$masid";
				//$mesg = "Answers updated, please wait a moment... If automatic redirection does not work, please <a href=\"opportunities.php\">click here to proceed</a>.";
			}
			$quest->data_seek(0);
		} // not submit
		} // quest->num_rows
		else {
				$redir = "opportunadmin.php?acct=$uacct&cid=$usid&mas=$masid";
				//$mesg = "No specialty questions, skipping... If automatic redirection does not work, please <a href=\"opportunities.php\">click here to proceed</a>.";
		}
	}
	catch(Exception $e) {
		$result = false;
		$mesg = 'Answers update problem: '.$e->getMessage().' ('.$e->getCode().')';
	}		
	if( $result && $redir ) {
		header("Location: $redir");
		exit;
	}

	$style = new OperPage('Specialty Questions',$UUID,'admin','opportunities');
    $style->Output();
	if ($UUID) {
		if( $mesg ) echo "<p id='".($result?'warning':'error')."_msg'>$mesg</p>"; 
		if( $ACCESS >= 200 ) {
?>	
        <h1>Specialty Questions for <?php echo "<a href=\"custedit.php?cid=$masid\">$cfirst $clast</a> ($cco)"; ?></h1>
		<p> This continues the opportunity description and these are the specialty specific questions. Please fill out and click &quot;Next&raquo;&quot;</p>
   <div id="formdiv" >
   <form name="form" method="post" action="specificadmin.php">
				<input name="oid" type="hidden" value="<?php echo $ooid; ?>">
			<input name="cid" type="hidden" value="<?php echo $usid; ?>">
			<input name="mas" type="hidden" value="<?php echo $masid; ?>">
			<input name="acct" type="hidden" value="<?php echo $uacct; ?>">
   <table border="0" cellspacing="0" cellpadding="3">
     <tr>
       <td width="40%" valign="top">&nbsp;</td>
       <td >&nbsp;</td>
  <td valign="top"><input name="submit" type="submit" id="submit3" value="Next&gt;&gt;"></td>
     </tr>
<?php 
	if( $quest && $quest->num_rows )
		while( $qu = $quest->fetch_object() ) {
			if( $qu->q_type == 1 || $qu->q_type == 2 ) {
?>
  <tr>
    <td valign="top" colspan="3"><?php echo $qu->c_question.":"; ?></td></tr>
<?php } ?>
  <tr>
    <td valign="top" bgcolor="#E0E0FF"><?php 
		if( $qu->q_type == 1 || $qu->q_type == 2 ) echo "<em>(Select all options that are acceptable; please see the note below for an explanation)</em>";
		else echo $qu->c_question.": "; 
	?></td>
    <td bgcolor="#E0E0FF" colspan="2"><?php 
		$qid = $qu->qid;
		$result = $db->query("select answer,o_qtype from opp_answers where o_oid = $ooid and o_qid = $qid");
		if( $result && $result->num_rows ) list($answer,$qtyp) = $result->fetch_row();
		else {
			$qtyp = 0;
			// possible specialty change, delete old trail
			if( $qu->q_type != 1 ) $db->query("delete from opp_answers where o_oid = $ooid");
		}
		if( $qtyp != $qu->q_type ) { // default values
			if( $qu->q_type == 1 ) $answer = 65535;
			elseif( $qu->q_type == 2 ) $answer = "1152921504606846975"; //"148764065110560900";
			else $answer = 4;
		}
  		//echo $answer;

		$selset = $db->query("select * from selections where s_qid = $qid order by s_val");
		while( $selset && ($sel = $selset->fetch_object()) ) {
			if( $qu->q_type == 1 ) { // binary checks
				// to do check of the current value
				$curch = ( (int)$answer & (int)($sel->s_val) )? 'checked':'';
				echo "<label><input type=\"checkbox\" name=\"q_".$qid."_$sel->sel_id\" $curch value=\"$sel->s_val\" /> $sel->sel</label><br />\n";
			}
			elseif( $qu->q_type == 2 ) { // binary ratings
				// to do checkboxen
				$cura = array('','','','','');
				$v = $sel->s_val? intval(bcdiv(bcmod($answer, bcmul($sel->s_val,32)),$sel->s_val)) : 1;
				if( $v & 1 ) $cura[0] = 'checked';
				if( $v & 2 ) $cura[1] = 'checked';
				if( $v & 4 ) $cura[2] = 'checked';
				if( $v & 8 ) $cura[3] = 'checked';
				if( $v & 16 ) $cura[4] = 'checked';
				//echo " $sel->s_val ";
				echo "$sel->sel:<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Disagree&nbsp;<input title=\"Disagree\" $cura[0] type=\"checkbox\" name=\"q_".$qid."_a$sel->sel_id\" value=\"$sel->s_val\" />";
				echo "<input title=\"Disargee slightly\" type=\"checkbox\" $cura[1] name=\"q_".$qid."_b$sel->sel_id\" value=\"". bcmul($sel->s_val,2) ."\" />";
				echo "<input title=\"Neither agree nor disagree\" type=\"checkbox\" $cura[2] name=\"q_".$qid."_c$sel->sel_id\" value=\"". bcmul($sel->s_val,4) ."\" />";
				echo "<input title=\"Agree somewhat\" type=\"checkbox\" $cura[3] name=\"q_".$qid."_d$sel->sel_id\" value=\"". bcmul($sel->s_val,8) ."\" />";
				echo "<input title=\"Agree completely\" type=\"checkbox\" $cura[4] name=\"q_".$qid."_e$sel->sel_id\" value=\"". bcmul($sel->s_val,16) ."\" />&nbsp;Agree&nbsp;completely<br>\n";
	//			echo "<input title=\"Agree completely\" type=\"radio\" $cura[4] name=\"q_".$qid."_$sel->sel_id\" value=\"". $sel->s_val*4 ."\" />&nbsp;5\n";
			}
			else { // assuming radio
				// to do check of the current value
				$curch = ( (int)$answer == (int)($sel->s_val) )? 'checked':'';
				echo "<label><input type=\"radio\" $curch name=\"q_".$qid."_0\" value=\"$sel->s_val\" /> $sel->sel</label><br />\n";
			}
		}
		if( $selset ) $selset->close();
	?>
      </td>
  </tr>
<?php } // quest ?>
  <tr valign="bottom">
    <td><input name="submit2" type="submit" id="submit2" value="&lt;&lt;Back" width="74" style="width:74px " />
    </td>
    <td><input type="reset" value="Reset Changes" /></td>
    <td><input name="submit1" type="submit" id="submit1" value="Next&gt;&gt;"></td>
  </tr>
   </table>
   </form>
   <!--<p> Please note, that the questions above may include sets of questions that ask you to check boxes between &quot;Disagree&quot; and &quot;Agree Completely&quot; options. These boxes represent varying level of agreement with the statement above them, expressed by your candidate. In other words, you check boxes with answers that your &quot;ideal&quot; candidates would give, if asked the same question. You can check any number or all of such boxes. </p>-->
</div>
<?php 
				} // ACCESS
			else echo "<p>Access Denied.</p>";
		}
	else showLoginForm(); // UUID

$style->ShowFooter();
 ?>