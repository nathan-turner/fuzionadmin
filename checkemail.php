<?php
	// ready 3/27/07 - SL
    require("globals.php5");
    require("cookies.php5");
    // $UUID <> 0 if auth
	$mesg = '';
	if( $UUID ) {
		$email = addslashes($_REQUEST['e']);
		$yer = $_REQUEST['y'];
		$docid = $_REQUEST['id'];
		if( empty($email) || empty($yer) || !is_numeric($yer) || !is_numeric($docid) ) $mesg = 'Required parameters missing';
		elseif( !valid_email($email) ) $mesg = "Email '$email' appears to be invalid";
		else try {
			if( $yer == 2005 ) {
				$resdb = db_career();
				$result = $resdb->query("select ph_id as res_id,'PCAREER' as year,fname,lname,city,state from physicians where email='$email' and ph_id != $docid");
			}
			else {
				$resdb = db_resident(2007);
				$result = $resdb->query("select res_id,year,fname,lname,city,state from residents where email='$email' and (res_id != $docid or `year` != $yer)");
			}
			if( $result && $result->num_rows ) {
			    $mesg = 'DUP';
				$row = $result->fetch_assoc();
				extract($row);
				$result->free();
			}
			//else $mesg = DEBUG? "not found $email in residents-$yer":'';
			$resdb->close();
		}
		catch(Exception $e) {
			$mesg = 'Error found: '.$e->getMessage().' ('.$e->getCode().')';
		}
	}
?> <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Email Check</title>
</head>
<body>
<?php 
	if( $UUID ) {
		echo '<h1>Email check</h1>';
		if( $mesg === 'DUP' ) {
			echo '<h3 style="color:red">Duplicate</h3>';
			echo <<<HereDoc
<p>Duplicate emails are not allowed. Please check below if it is a duplicate record.
If it is a family email address, please use Secondary Email in the form instead.<br />
ID# $res_id/$year: $fname $lname, $city, $state, $email</p>
HereDoc;
		}
		elseif( $mesg ) echo "<p style='color:red'>$mesg</p>";
		else echo '<h3>Email is VALID</h3>';
	}
	else echo '<h2>Please log in <a href="login.php" target="_blank">here</a> first</h2>';
?>
<p><a href="javascript:window.close()">Close window</a></p>
</body>
</html>
