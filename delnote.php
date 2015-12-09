<?php
    require("globals.php5");
    require("cookies.php5");
	$referrer = $_SERVER['HTTP_REFERER'];
	$noteid = $_SESSION['delete_note'];
	$qstr = $_SESSION['delete_note_str'];
	$yr = $_SESSION['delete_note_yr'];
	if( $UUID && $noteid ) try {
		$db = $yr == 2005? db_notes(): db_career();
		$sql = $ACCESS!=500? sprintf("delete from notes where note_id = '%s' and uid = %d",$noteid,$UUID):
			sprintf("delete from notes where note_id = '%s'",$noteid);
		$result = $db->query($sql);
	}
	catch(Exception $e) {
		// $mesg = 'Error found: '.$e->getMessage().' ('.$e->getCode().')';
		// NOTHING, REALLY
	}
	if( strpos($referrer,'?') === false ) $referrer .= '?'.$qstr;
	unset($_SESSION['delete_note']);
	header("Location: $referrer");
?>