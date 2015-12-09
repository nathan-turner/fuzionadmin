<?php
    require("globals.php5");
	require("cookies.php5");
    	$mesg = '';
	if( $UUID && $ACCESS && isset($_POST['submit2']) ) try {
		$strippost = str_replace('"',"'",$_POST); // make all double quotes to be single
		extract($strippost,EXTR_SKIP);
		$wher = '';
		$verboz = '';
		if( $res_id && is_numeric($res_id) ) { // res_id search is exclusive
			$wher = "ph_id = $res_id";
			$verboz = "ID is $res_id; ";
		}
		else {
			// lname, fname, spec, state, city, zip, phone, email, program, program_2, res_year, res2_year, res2_spec
			if( $fname ) {
			    $q = addslashes($fname);
				$wher = "fname like '$q%'";
				$verboz .= "First name $fname; "; }
			if( $lname ) {
			    $q = addslashes($lname);
				$wher .= ($wher?' and ':'')."lname like '$q%'";
				$verboz .= "Last name $lname; "; }
			if( $spec && $spec != '---' ) {
			    $q = addslashes($spec);
				$wher .= ($wher?' and ':'')."spec = '$q'";
				$verboz .= "Spec. is $spec; "; }
			//if( $res2_spec && $res2_spec != '---' ) $wher .= ($wher?' and ':'')."res2_spec='$res2_spec'";
			if( $state && $state != '--' ) {
				$wher .= ($wher?' and ':'')."state = '$state'";
				$verboz .= "State is $state; "; }
			if( $city ) {
			    $q = addslashes($city);
				$wher .= ($wher?' and ':'')."city like '$q%'";
				$verboz .= "City is $city; "; }
			if( isset($zip) && strlen($zip)>=3 ) {
			    $q = addslashes($zip);
				$wher .= ($wher?' and ':'')."zip like '$q%'";
				$verboz .= "ZIP is $zip; "; }
			elseif( !empty($zip) ) throw new Exception('Please enter at least 3 digits in ZIP field',__LINE__);
			$phone2 = preg_replace('/[^0-9]/','',$phone);
			if( isset($phone2) && strlen($phone2)>=3 ) {
				$wher .= ($wher?' and ':'').
				  "(homephone like '$phone2%' or cellphone like '$phone2%' or officephone like '$phone2%' or pager like '$phone2%')";
				$verboz .= "Phone like $phone2; "; }
			elseif( !empty($phone2) ) throw new Exception('Please enter at least 3 digits in Phone field',__LINE__);
			if( $email ) {
			    $q = addslashes($email);
				$wher .= ($wher?' and ':'')."(email like '$q%' or email like '%$q')";
				$verboz .= "Email like $email; "; }
		}
		$totres = 0; 
		if( empty($wher) ) throw new Exception('Search: Required parameter missing',__LINE__);
		$totres = SearchRes(NULL,$wher,$custjoin);
		if( !$totres ) throw new Exception('Nothing Found!',0);
		else setcookie('resy',2005,time()+3600*24*7);
		if( $totres == 1 ) {
			$resdb = db_career();
			$result= $resdb->query("select memberuid from custlistsus where owneruid=$UUID and listid=0");
			if( !$result ) throw new Exception('Can not fetch record number!',__LINE__);
			list($docid) = $result->fetch_row();
			$redir = "showdocpc.php?lid=0&pos=0&id=$docid";
		}
		else $redir = "results.php?id=0";
		if( $verboz ) $_SESSION['verboz'] = $verboz; // lid 0 verbose descr
		$okmesg = "$totres results found. One moment, please. You will be redirected <a href='$redir'>here</a>.";
	}
	catch(Exception $e) {
		$mesg = 'Search failed: '.$e->getMessage().' ('.$e->getCode().')<br>';
	}
	if( !isset($resdb) ) $resdb = db_career();
	$style = new OperPage('Database Search',$UUID,'residents','career',($redir?"1; URL=$redir":''));
	$style->Output();
?> 
<?php	if( $UUID ) {
?>
              <h1>Search PCareer Physicians</h1>
<?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
	if( $okmesg ) echo "<p id='warning_msg'>$okmesg</p>";
?>
              <h3>Basic Search</h3>
              <p>Advanced Search form is <a href="ressearch.php">available here</a>.</p>
              <div id="formdiv">
                <form name="form1" method="post" action="pcsearch.php">
                  <table width="80%"  border="0">
                    <tr>
                      <td>&nbsp;</td>
                      <td colspan="2">&nbsp;</td>
                      <td>&nbsp;</td>
                      <td class="tdborder3399"><label>
                        ID#: 
                        <input name="res_id" type="text" id="res_id" size="15" maxlength="10">
                      </label></td>
                    </tr>
                    <tr>
                      <td class="tborderUL">Last name: </td>
                      <td class="tborderUR"><input name="lname" type="text" id="lname" value="<?php echo stripslashes($lname); ?>" maxlength="30"></td>
                      <td>Specialty:</td>
                      <td colspan="2"><?php echo showSpecList($resdb,$spec); ?></td>
                    </tr>
                    <tr>
                      <td class="tborderDL">First name: </td>
                      <td class="tborderDR"><input name="fname" type="text" id="fname" value="<?php echo stripslashes($fname); ?>" maxlength="50"></td>
                      <td class="tborderUL">City:</td>
                      <td class="tborderU"><input name="city" type="text" id="city" value="<?php echo stripslashes($city); ?>" maxlength="50"></td>
                      <td class="tborderUR">Zip:
                      <input name="zip" type="text" id="zip2" value="<?php echo $zip; ?>" size="12" maxlength="10"></td>
                    </tr>
                    <tr>
                      <td>Phone:</td>
                      <td><input name="phone" type="text" id="phone" value="<?php echo $phone2; ?>" maxlength="16"></td>
                      <td class="tborderDL">State:</td>
                      <td colspan="2" class="tborderDR"><?php echo showStateList($resdb,$state); ?></td>
                    </tr>
                    <tr>
                      <td>Email: </td>
                      <td><input name="email" type="text" id="email" value="<?php echo stripslashes($email); ?>" maxlength="128"></td>
                      <td>&nbsp;</td>
                      <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                      <td>&nbsp;</td>
                      <td><label>
                        </label><label>
                        <input name="submit2" type="submit" id="submit22" value="Search">
<sup>*</sup>                      </label></td>
                      <td><input type="reset" name="Reset" value="Reset"></td>
                      <td colspan="2">&nbsp;</td>
                    </tr>
                  </table>
                  <p>* Searches for ALL criteria entered. If ID# entered, it searches for that particular number only.</p>
                </form>
              </div>
<?php	}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
