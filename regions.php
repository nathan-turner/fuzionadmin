<?php
    require("globals.php5");
    require("cookies.php5");
    // $UUID <> 0 if auth
	if( $UUID ) try {
		$resdb = db_career();
		$sql = "select reg_id,reg_name,st_code,st_name from regions join states on reg_id=st_region where reg_id != 0 order by reg_id,st_name";
		$result = $resdb->query($sql);
		if( !$result ) throw new Exception('Can not read regions table',__LINE__);
	}
	catch(Exception $e) {
		$mesg = 'Request Failed: '.$e->getMessage().' ('.$e->getCode().')';
	}
?> <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Regions and States</title>
</head>

<body>
<?php if( $UUID ) { ?>
<h1>Regions &amp; States</h1>
<?php
      if( $mesg ) echo "<p style='color:red'>$mesg</p>";
?>
<dl>
<?php 
	$regid = 0;
	if( $result ) while( list($reg_id,$reg_name,$st_code,$st_name) = $result->fetch_row() ) {
		if( $reg_id != $regid ) {
			if( $regid ) echo '</dd>';
			echo "<dt>$reg_name</dt><dd>";
			$regid = $reg_id;
		}
		else echo ', ';
		echo "$st_name ($st_code)";
	}
?></dd>
</dl>
	<?php } // UUID
	  else echo '<h1 style="color:red">Access Denied</h1><p>Please <a href="login.php" target="_blank">log in</a>!</p>';
?><hr>
<p><a href="javascript:window.close()">Close Window</a></p>
</body>
</html>
