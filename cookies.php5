<?php
// authentification procedure.
// customer version
    session_start();
    if ( $_SESSION['user_id'] && (!EMERGENCY || $_SESSION['access']==500) ) {
		$UUID = $_SESSION['user_id'];
		$ACCESS = $_SESSION['access'];
		$ACCT = $_SESSION['acct'];
		$MASTER = $_SESSION['master_acct'];
		$USER = $_SESSION['userobj'];
	}
    elseif ( $_COOKIE['uid'] ) {
		// cookie auth - returning user. allow for the same IP only.
    	$USER = cookie_auth($_COOKIE['uid'],$_COOKIE['uha']);
    }
?>