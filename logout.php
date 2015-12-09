<?php
	// done 3/27/07 - SL
    require("globals.php5");
    session_start();
    if ( $_SESSION['user_id'] ) {
		logout();
	}
	// no cookie auth here. not supposed to login if user is not already
	$style = new OperPage('Log Out',NULL,'index','');
	$style->Output();
	
?> 
              <p>
                Thank you for logging out. Your can <a 
				href="login.php">log back in here</a>.<br>
              </p>
<?php
	$style->ShowFooter();
?>
