<?php
	// this file works with amalist
    require("globals.php5");
    require("cookies.php5");
	$mesg = '';
	if( $UUID && $ACCESS == 500 ) try {
		$resdb = db_amalist();
		$res = $resdb->query('select count(*) from amalist where accept = 0 and reject = 0');
		if( $res ) {
			list($totalnum) = $res->fetch_row();
			$res->free();
		}
		$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
		extract($strippost,EXTR_SKIP);
		if( isset($submit1) && $ACCESS >= 50 ) { // 1st form
			// nfrom, nto, nlist1, sort1
			if( empty($nlist1) ) $nlist1 = "new-$nfrom-$nto";
			$sort = 'doc_id'; $vs = 'ID#';
			if( $sort1 == 2 ) { $sort = 'lname,fname'; $vs = 'Last and First name'; }
			elseif( $sort1 == 3 ) { $sort = 'spec'; $vs = 'Specialty'; }
			elseif( $sort1 == 4 ) { $sort = 'state,city'; $vs = 'State and City'; }

			$wher = ''; $verboz = '';
			if( $mofrom1 && $mofrom1 > ('2000-12-31') ) {
				$wher .= "and date_mod >= '$mofrom1' ";
				$verboz .= " since $mofrom1"; }
			if( $moto1 && $moto1 > ('2000-12-31') ) {
				$wher .= "and date_mod <= '$moto1' ";
				$verboz .= " until $moto1"; }
			
			$desc = "Records$verboz from $nfrom to $nto sorted by $vs. ".date('r');
			if( empty($nfrom) || !is_numeric($nfrom) ) $nfrom = 0;
			else $nfrom--;
			if( empty($nto) || !is_numeric($nto) ) throw new Exception('Upper record number required',__LINE__);
			if( $nto > $totalnum ) $nto = $totalnum;
			if( $nto <= $nfrom ) throw new Exception('Upper record number is incorrect',__LINE__);
			// select records and insert them in the list
			$sell = $sort=='doc_id'? 'doc_id': 'doc_id,'.$sort;
			$numm = $nto - $nfrom;
			$sql = "select $sell from amalist where accept = 0 and reject = 0 $wher order by $sort LIMIT $nfrom,$numm";
			$res = $resdb->query($sql);
			if( $res && $res->num_rows ) { // something
				$nomrows = $res->num_rows;
				$newl = new CustList($nlist1,1,$desc);
				$cdb = $newl->cdb;
				while( $row = $res->fetch_row() ) {
					$docid = $row[0];
					$cdb->query("insert into custlists values ($UUID, $docid, $newl->id)");
				}
				$res->free();
				$okmesg = "List $nlist1 created successfully, $nomrows inserted.";
			}
			else throw new Exception('Can not find anything',__LINE__);
		}
		elseif( isset($submit2) && $ACCESS >= 50 ) { // 2nd form
			// lna1, lna2, spec, spec2, spec3, state, state2, phonen 1/2/0, nlist2, program
			if( empty($nlist2) ) $nlist2 = "new-".time();
			$wher = ''; $vs = '';
			if( !empty($lna1) && !empty($lna2) ) {
				$wher = "and lname between '$lna1' and '$lna2' ";
				$vs = " where names are between $lna1 and $lna2";
			}
			elseif( !empty($lna1) ) {
				$wher = "and lname = '$lna1' ";
				$vs = " where last name is $lna1";
			}
			elseif( !empty($lna2) && strlen($lna2) >= 3 ) {
				$wher = "and lname like '$lna2%' ";
				$vs = " where last name like $lna2*";
			}
			// spec, spec2, spec3, state, state2, phonen 1/2/0, program
			if( !$spec3 || $spec3 == '---' ) { unset($spec3); }
			if( !$spec2 || $spec2 == '---' ) { $spec2 = $spec3; unset($spec3); }
			if( !$spec || $spec == '---' ) { $spec = $spec2; $spec2 = $spec3; unset($spec3); }
			if( $spec ) { // at least one
				$spq = "spec in ('$spec'";
				if( $spec2 ) $spq .= ",'$spec2'";
				if( $spec3 ) $spq .= ",'$spec3'";
				$wher .= "and $spq) ";
				$vs .= ($vs?' and ':' where ').$spq.')';
			}
			// state, state2, phonen 1/2/0, program
			if( !$state2 || $state2 == '--' ) unset($state2);
			if( !$state || $state == '--' )   unset($state);
			if( $state && $state2 ) {
				if( $state2 < $state ) { list($state,$state2) = array($state2,$state); }
				$wher .= "and state between '$state' and '$state2' ";
				$vs .= ($vs?' and ':' where ')."state is between $state and $state2";
			}
			elseif( $state ) {
				$wher .= "and state >= '$state' ";
				$vs .= ($vs?' and ':' where ')."state code is after $state";
			}
			elseif( $state2 ) {
				$wher .= "and state <= '$state2' ";
				$vs .= ($vs?' and ':' where ')."state code is before $state2";
			}
			// phonen 1/2/3/0, program
			if( $phonen ) {
				$spq = 'phone is '.($phonen==2?'':'not ').'null';
				$wher .= "and $spq ";
				if( $phonen == 3 ) $wher .= "and ho=2 ";
				$vs .= ($vs?' and ':' where ').$spq;
			}
			if( $program ) {
				$wher .= "and program = '$program' ";
				$vs .= ($vs?' and ':' where ')."program code is $program";
			}
			if( $mofrom2 && $mofrom2 > ('2000-12-31') ) {
				$wher .= "and date_mod >= '$mofrom2' ";
				$vs .= " since $mofrom2"; }
			if( $moto2 && $moto2 > ('2000-12-31') ) {
				$wher .= "and date_mod <= '$moto2' ";
				$vs .= " until $moto2"; }
			$desc = "All records$vs. ".date('r');
			if( empty($wher) ) throw new Exception('Please specify some criteria',__LINE__);
			$sql = "select doc_id from amalist where accept = 0 and reject = 0 $wher";
			$res = $resdb->query($sql);
			if( $res && $res->num_rows ) { // something
				$nomrows = $res->num_rows;
				$newl = new CustList($nlist2,1,addslashes($desc));
				$cdb = $newl->cdb;
				while( $row = $res->fetch_row() ) {
					$docid = $row[0];
					$cdb->query("insert into custlists values ($UUID, $docid, $newl->id)");
				}
				$res->free();
				$okmesg = "List $nlist2 created successfully, $nomrows inserted.";
			}
			else throw new Exception('Can not find anything',__LINE__); //DEBUG?"$resdb->error : $sql":
		} 
		elseif( isset($poisk) ) { // DE form
			// docid, phone, lname, fname
			$wher = '';
			if( !empty($lname) ) $wher = "and lname='$lname' ";
			if( !empty($fname) ) $wher .= "and fname = '$fname' ";
			if( !empty($phone) ) {
				$phone = preg_replace('/[^0-9]/','',$phone);
				$wher .= "and phone = '$phone' ";
			}
			if( !empty($docid) ) { // docid is exclusive
				$docid = preg_replace('/[^0-9]/','',$docid);
				if( $docid ) $wher = "and doc_id = $docid ";
			}
			if( empty($wher) ) throw new Exception('Please specify some criteria',__LINE__);
			$sql = "select doc_id from amalist where accept = 0 and reject = 0 $wher";
			$res = $resdb->query($sql);
			if( $res && $res->num_rows ) { // something
				$nomrows = $res->num_rows;
				$cdb = db_clients();
				// fixed id 248
				$cdb->query("insert into custlistdesc values ($UUID,248,1,'Shadow Search','Poisk',0,0,NULL)");
				$cdb->query("delete from custlists where owneruid=$UUID and listid=248");
				while( $row = $res->fetch_row() ) {
					$doc_id = $row[0];
					$cdb->query("insert into custlists values ($UUID, $doc_id, 248)");
				}
				$res->free();
				$redir = $nomrows==1?"shadowdoc.php?lid=248&y=1&pos=0&id=$doc_id":"results.php?id=248&y=1";
				$sss = $nomrows==1?'':'s';
				$okmesg = "$nomrows result$sss found. One moment, please. You will be redirected <a href='$redir'>here</a>.";
			}
			else throw new Exception('Can not find anything',__LINE__); //DEBUG?"$resdb->error : $sql":
		} // forms
	} // access
	catch(Exception $e) {
		$mesg = 'Request to create list failed: '.$e->getMessage().' ('.$e->getCode().')<br>';
	}
	if( !isset($sort1) ) $sort1 = 1;
	$style = new OperPage('Shadow Search',$UUID,'residents','shadow',($redir?"2; URL=$redir":''));
	$scrip2 = "<script type=\"text/javascript\" src=\"calendarDateInput.js\"></script>\n";
	$style->Output($scrip2);
	
	if( $UUID ) {
			if( $ACCESS < 500 ) {
				// shadows are deprecated
				echo "<h1 id='error_msg'>DEPRECATED. No Access.</h1>";
			} else { // ACCESS
				echo "<p id='warning_msg'>DEPRECATED</p>";
?>
<h1>Shadow Search</h1>
<?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
	if( $okmesg ) echo "<p id='warning_msg'>$okmesg</p>";
?>
<p>Search by ID#, First and/or Last name, or Phone number. <hr></p>

<form name="form3" method="post" action="shadow.php">
  <table width="80%" border="0">
    <tr>
      <td>ID#:</td>
      <td><input name="docid" type="text" id="docid" maxlength="8"></td>
      <td>Phone:</td>
      <td><input name="phone" type="text" id="phone2" maxlength="16"></td>
    </tr>
    <tr>
      <td>First Name: </td>
      <td><input name="fname" type="text" id="fname" maxlength="50"></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>Last Name:</td>
      <td><input name="lname" type="text" id="lname2" maxlength="50"></td>
      <td></td>
      <td><input name="poisk" type="submit" id="poisk" value="Search">&nbsp;&nbsp;&nbsp;<input type="reset" name="Submit2" value="Reset"></td>
    </tr>
  </table>
</form>
<hr>
<?php
			//if( $ACCESS >= 50 ) {
?>
              <h1>Process New List</h1>
<?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
	if( $okmesg ) echo "<p id='warning_msg'>$okmesg</p>";
?>
              <p>Here you find forms  for processing new (purchased, imported) lists. You select a subset a record and store it in one of the <a href="custlists.php">custom lists</a>, which you can later <a href="shadowass.php">assign</a> to data entry employee for further processing.</p>
              <h3> Select by numbers</h3>
              <div id="formdiv">
              <form name="form1" method="post" action="shadow.php">
                <p>There are <?php echo $totalnum; ?> records in the list. Put records from <input name="nfrom" type="text" id="nfrom" value="1" size="10" maxlength="8"> 
                to 
                  <input name="nto" type="text" id="nto" size="10" maxlength="8">,</p>
				  <table width="70%" style="width: 70%"><tr>
				  <td>modified from:</td>
				  <td title="Year 2000 means 'From the beginning'"><script language="javascript">DateInput('mofrom1', false, 'YYYY-MM-DD', '<?php echo $mofrom1?$mofrom1:'2000-01-01'; ?>');</script></td>
				  <td>to:</td>
				  <td title="Year 2000 means 'To the end',
and 2008-09-05 is day One"><script language="javascript">DateInput('moto1', false, 'YYYY-MM-DD', '<?php echo $moto1?$moto1:'2008-09-06'; ?>');</script></td>
				  </tr></table>
                  <p>and sorted by:</p>
                <p><label><input name="sort1" type="radio" value="1" <?php echo $sort1==1?'checked':''; ?>>
  ID#</label>
&nbsp;
  <label><input name="sort1" type="radio" value="2" <?php echo $sort1==2?'checked':''; ?>>
  Last &amp; First Name</label>
&nbsp;
  <label><input name="sort1" type="radio" value="3" <?php echo $sort1==3?'checked':''; ?>>
  Specialty</label>
&nbsp;
  <label><input name="sort1" type="radio" value="4" <?php echo $sort1==4?'checked':''; ?>>
  State &amp; City</label>, 
  into new custom list with the following name (optional):</p>
                <p align="center"><input name="nlist1" type="text" id="nlist1" maxlength="50"> &nbsp;
                  <input name="submit1" type="submit" id="submit1" value="Select">
</p>
                </form>
			  </div>
              <h3>Select by Criteria</h3>
              <div id="formdiv2">
              <form name="form2" method="post" action="shadow.php">
              <p>Select by Names, States, Specialties, Residency program, Phone availability, sorted by ID#.</p>
              <table width="80%"  border="0">
                <tr>
                  <td class="tborderUL">Last name From: </td>
                  <td class="tborderUR"><input name="lna1" type="text" id="lna1" maxlength="30"></td>
                  <td class="tborderUL">Specialty:</td>
                  <td colspan="2" class="tborderUR"><?php echo showSpecList($resdb,$spec); ?></td>
                  </tr>
                <tr>
                  <td class="tborderDL">Last Name To: </td>
                  <td class="tborderDR"><input name="lna2" type="text" id="lna2" maxlength="30"></td>
                  <td class="tborderL">OR:</td>
                  <td colspan="2" class="tborderR"><?php echo showSpecList($resdb,$spec2,'spec2'); ?></td>
                  </tr>
                <tr>
                  <td class="tborderUL">State Codes From: </td>
                  <td class="tborderUR"><?php echo showStateList($resdb,$state); ?></td>
                  <td class="tborderDL">OR:</td>
                  <td colspan="2" class="tborderDR"><?php echo showSpecList($resdb,$spec3,'spec3'); ?></td>
                  </tr>
                <tr>
                  <td class="tborderDL">State Codes To: </td>
                  <td class="tborderDR"><?php echo showStateList($resdb,$state2,'state2'); ?></td>
                  <td><a href="editprog.php?y=2010" target="_blank">Res.Program</a>:</td>
                  <td colspan="2"><input name="program" type="text" id="program"></td>
                </tr>
				<tr>
				  <td>Modified from:</td>
				  <td title="Year 2000 means 'From the beginning'"><script language="javascript">DateInput('mofrom2', false, 'YYYY-MM-DD', '<?php echo $mofrom2?$mofrom2:'2000-01-01'; ?>');</script></td>
				  <td>To:</td>
				  <td title="Year 2000 means 'To the end',
and 2008-09-05 is day One"><script language="javascript">DateInput('moto2', false, 'YYYY-MM-DD', '<?php echo $moto2?$moto2:'2008-09-06'; ?>');</script></td>
				</tr>
                <tr>
                  <td>With Phone Number: </td>
                  <td><label><input name="phonen" type="radio" value="1">
                    Any</label> 
                    <label><input name="phonen" type="radio" value="3">
                    Home Phone</label> <br>
                      <label><input name="phonen" type="radio" value="2">
                      Without</label></td>
                  <td bgcolor="#CCCCCC"><span class="style1">New List name*: </span></td>
                  <td bgcolor="#CCCCCC"><input name="nlist2" type="text" id="nlist2" maxlength="50"></td>
                  <td style="text-align:center" class="tdborder3399"><input name="submit2" type="submit" id="submit2" value="Select"></td>
                </tr>
              </table>
              </form>
			  </div>
			  <p>* optional field.</p>
              <?php		} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
