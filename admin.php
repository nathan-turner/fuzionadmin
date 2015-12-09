<?php
	// fixed - done - 3/27/07 - SL
    require("globals.php5");
    require("cookies.php5");
    // $UUID <> 0 if auth
	$style = new OperPage('Administration',$UUID,'admin','');
	$style->Output();
	
	if( $UUID ) {
			if( $ACCESS < 50 ) echo '<h1>Access Denied</h1>';
			else {
?>
              <h1>Administration</h1>
              <ul>
                <?php if( $ACCESS >= 300 ) echo '<li><a href="custnew.php">Register</a> new customer</li>'; ?>
                <li><a href="custsearch.php">Browse</a> and edit customers</li>
                <?php if( $ACCESS >= 400 ) echo '<li><a href="editoper.php">Add or edit</a> employees</li>'; ?>
                <li><a href="shadowass.php">Reassign</a> lists<br>
                <li>Quick find a customer here.<br>
                </li>
              </ul>
			  <div id="formdiv">
              <form name="form1" method="post" action="custsearch.php"><table>
                <tr>
				  <td>ID:</td><td><input name="uid" type="text" id="uid"></td>
                  <td>Acct #:</td><td><input name="acct" type="text" id="acct"></td>
				  <td>&nbsp;</td><td>&nbsp;</td>
				</tr><tr>
				  <td>First Name:</td><td><input name="firsname" type="text" id="firsname"></td>
				  <td>Last Name:</td><td><input name="lastname" type="text" id="lastname"></td>
				  <td>Facility:</td><td><input name="company" type="text" id="company"></td>
				</tr><tr>
				  <td>Phone:</td><td><input name="phone" type="text" id="phone"></td>
				  <td>Email:</td><td><input name="email" type="text" id="email"></td>
				  <td>&nbsp;</td>
				  <td><input type="submit" name="submitto" value="Submit"> &nbsp;&nbsp;&nbsp;
				    <input type="reset" name="Reset" value="Reset"></td>
				</tr>
              </table>
              </form>
			</div>
                <?php		} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>