<?php
require("globals.php5");
//require("cookies.php5");
//
// NO $UUID HERE, NO SESSION IS ACTIVE
//
// param: r = random, k = key md5(random+salt), t=type (1: get mailcamp, 2: set mailcamp status), mc = 0 or NN (mc_id)
//
$rnd = $_REQUEST['r'];
$key = $_REQUEST['k'];
$typ = $_REQUEST['t'];
$mc = $_REQUEST['mc'];
$mesg = 'Access Denied';
if( $rnd && $typ && is_numeric($typ) && md5($rnd . $typ . "atum batum shumbashu chinchbiri atumbu\n" . $mc) === $key ) try {
	// do stuff
	$db = db_career();
	if( $typ == 2 && $mc && is_numeric($mc) ) {
            header('Content-type: text/plain');
	    $sql = "delete from maillist where camp_id = $mc";
	    $db->query($sql); // ignore result for now
	    $sql = "update mailcamp set mc_status = 3 where mc_id = $mc";
	    $db->query($sql); // ignore result for now
	    echo "OK\n";
	    exit;
	}
	elseif( $typ == 1 && $mc && is_numeric($mc) ) { // get list
	    $sql = "update mailcamp set mc_status = 2 where mc_id = $mc"; // and mc_status = 1 -- not now, not yet
	    $result = $db->query($sql);
	    if( !$result || !$db->affected_rows ) throw new Exception("Invalid MC=$mc",__LINE__);
            header('Content-type: text/plain');
	    $sql = "select cemail,clname,cfname,chash from maillist where camp_id = $mc";
	    $res = $db->query($sql);
	    while( $res &&( $doc = $res->fetch_object() ) ) {
		    echo "$doc->chash#;$doc->cfname;$doc->clname;$doc->cemail\n"; // chash can be null
	    }
	    $res->free();
	    exit;
	}
	elseif( $typ == 1 && !$mc ) { // get camp
	    $sql = "select * from mailcamp where mc_status = 1 order by mc_date limit 0,1"; // get one camp
	    $res = $db->query($sql);
	    if( $res && $res->num_rows ) {
		// we should probably touch mailcamp at this point, but I don't want to bother
                header('Content-type: text/plain');
		$fs = "<$rnd>";
		if( $camp = $res->fetch_object() ) {
		    echo $fs,$camp->mc_id,$fs,$camp->mc_client,$fs,$camp->mc_subj,$fs,$camp->mc_header,$fs,$camp->mc_body,$fs,"\n";
		}
		$res->free();
	    }
	    else {
		header('HTTP/1.0 404 Not Found'); // or Status: 404 Not Found
		//db_shutdown();
	    }
	    exit;
	}
	$mesg = 'Success';
}
catch(Exception $e) {
	$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
}
$style = new OperPage('Mail Export',0,'reports','export');
$style->Output();

?>
              <h1>Mail Export Error</h1>
<?php 
		if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
		$style->ShowFooter();
?>
