<?php
    require("globals.php5");
	//define(PG_SIZE,50);
    //require("cookies.php5");
	//
	// NO $UUID HERE, NO SESSION IS ACTIVE
	//
	// param: r = random, k = key md5(random+salt), b=bounces list (string), or userfile (text file)
	//
	$rnd = $_REQUEST["r"];
	$key = $_REQUEST["k"];
	$lst = $_REQUEST["b"];
	$BOUtotal = 0;
	
function callme($stream, &$buffer, $buflen, &$errmsg)
{	global $BOUtotal;
	$bu = fgets($stream,$buflen);
	if( $bu !== FALSE ) {
		$buffer = $bu; // rtrim($bu);
		$BOUtotal++;
	    return strlen($buffer);
	}
	if( feof($stream) ) {
		$buffer = '';
		return 0;
	}
	$errmsg = "READ ERROR";
	return -1;
}
	
	$mesg = 'Access Denied';
	if( $rnd && $key && md5( $rnd . ".kigle dan, kigle dan, kigle kigle kigle dan!\n") === $key ) try {
		// do stuff
		$db = db_career();

		if (is_uploaded_file($_FILES['userfile']['tmp_name']) ) {
			$file_size = $_FILES['userfile']['size'];
			//$file_type = $_FILES['userfile']['type'];
			//if( substr($file_type,0,6) != 'text/' ) throw new Exception ('text only, please',__LINE__);
			$db->set_local_infile_handler("callme");
			$result = $db->query("LOAD DATA LOCAL INFILE '".$_FILES['userfile']['tmp_name']."' IGNORE INTO TABLE bounces (emails)");
			if( !$result ) throw new Exception(DEBUG?"$db->error: $sql":'Can not load file',__LINE__);
			$db->set_local_infile_default();
			$mesg = 'RESULT-OK';
		}
		elseif( !empty($lst) ) {
		    $sqlv = ''; $comma = ''; $ecnt = 0;
			foreach( explode("\n",$lst) as $eml ) {
				if( valid_email($eml) ) {
					$eml = addslashes($eml);
					$sqlv .= "$comma ('$eml')"; $comma = ',';
					$ecnt++;
				}
			}
			if( $sqlv ) {
				$sql = "insert ignore into bounces (emails) values $sqlv";
				$db->query($sql);
				$BOUtotal += $db->affected_rows;
				$mesg = 'RESULT-OK';
			}
			else $mesg = 'Empty list!';
		}
		else $mesg = 'Nothing to do!';
		if( $mesg == 'RESULT-OK' ) {
			$sql = "update physicians,bounces set email_bounces = 1 where email=emails and email_bounces = 0";
			$db->query($sql);
			$mesg .= " ($db->affected_rows $ecnt)";
		}
	}
	catch(Exception $e) {
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
	}
	$style = new OperPage('Bounce List',0,'reports','export');
	$style->Output();

?>
              <h1>Data Import</h1>
<?php 
		if( $mesg ) echo "<p id='error_msg'>$mesg</p>";

		echo "<p>Total: $BOUtotal</p>";
		$style->ShowFooter();
?>
