<?php
    require("globals.php5");
	define(PG_SIZE,50);
    require("cookies.php5");
    // $UUID <> 0 if auth
	$mesg = '';
	if( $UUID && $ACCESS >= 500 ) try { // DEPRECATED
		throw new Exception('Who am I? Who are you?',__LINE__);
	}
	catch(Exception $e) {
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
	}
	$style = new OperPage('DEPRECATED',$UUID,'reports','managerrpts');
	$style->Output();
 
	if( $UUID ) {
			if( $ACCESS < 500 ) echo '<h1>Access Denied</h1>'; // DEPRECATED
			else {
?>
              <h1>DEPRECATED </h1>
              <?php 
			if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
			} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
           
