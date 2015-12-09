<?php
	// fixed - done - 3/29/07 - SL
    require("globals.php5");
    require("cookies.php5");
	// $UUID <> 0 if auth
	$mesg = '';
	$result = false;
	if( $UUID && $ACCESS && $ACCESS >= 200 ) try {
		if( isset($_GET['acct']) || isset($_POST['acct']) ) {
			if(isset($_GET['acct']))
				$acct=$_GET['acct'];
			else
				$acct=$_POST['acct'];
			
			$db = db_clients();
			$sql = "select uid,firstname,lastname,email from clients where status=1 and acct='".$acct."' ";
			//echo $sql;
			$exresult = $db->query($sql);
		}
		
	}
	catch(Exception $e) {
		$mesg = 'Request failed: '.$e->getMessage().' ('.$e->getCode().')<br>';
	}
	$style = new OperPage('Email Customers',$UUID,'admin','custemail');
	///// JavaScriplet below
	$scrip = <<<TryMe
	
var subwind;
var listed;

function poptest() {
  subwind = window.open("","emails","menubar=0,scrollbars=yes,toolbar=0,width=450,location=0,height=400");
  setTimeout("popshow()",60);
  return true;
}

function popshow() {
        var d = subwind.document;
        d.write("<html><head><title>Email List</title></head><body><p>",
				listed,
                "</p></body></html>");
        d.close();
        subwind.focus();
}

function copyToCLP() {
	var elist = document.getElementById("emdiv");
	var list1 = elist.innerHTML;
	if( list1.length > 4 && list1.charAt(list1.length - 1) == '>' )
	    list1 = list1.substr(0,list1.length - 2); // kill 1/2 of <br>
	if( list1.length > 2 ) listed = list1.substr(0,list1.length - 2);
	else listed = "Nothing found";
	poptest();
	return true;
}

TryMe;
	$scrip2 = "<script type=\"text/javascript\" src=\"/ckeditor/ckeditor.js\"></script>\n<script language=\"JavaScript\" type=\"text/JavaScript\"><!--\n".$scrip.
		"// -->\n</script>";
	$style->Output($scrip2);
	if( $UUID ) {
			if( $ACCESS < 200 ) echo '<h1>Access Denied</h1>';
			else {
?>
        <h1>Email</h1>
        
        <h3>Export Email Addresses</h3>
<?php if( $exresult && $exresult->num_rows ) { ?>
		<p>Copy addresses from the frame below and paste them into your email program or list management software. You can open this list as a separate window, for your convenence.</p>
		<div id="emdiv" style="width: 440px; border: inset; height: 144px; padding: 6px; overflow: auto">
<?php 
			$delim = ', ';
			if( $_POST['delim'] === 'S' ) $delim = '; ';
			elseif( $_POST['delim'] === 'N' || $_POST['delim'] === 'X' ) $delim = '<br>';
			//$emails = '';
			while( list($cid,$fname,$lname,$email) = $exresult->fetch_row() ) {
					//$emails .= stripslashes("\"$fname $lname\" &amp;lt;$email&amp;gt;$delim ");
					if( $_POST['delim'] === 'X' ) echo stripslashes('"","","'.$fname.'","' .$lname. '","","","","","","","","","","' .$email. '","","","","","","","","",""' .$delim);
					else echo stripslashes("\"$fname $lname\" &lt;$email&gt;$delim");
			}
			//$emails = substr($emails,0,strlen($emails)-2);
?>
		</div>
		<p onClick='copyToCLP()'><u>Click here</u> to open this list in a pop-up window</p>
<?php } ?>
		<div id="formdiv">
        <form action="custemail2.php" method="post" name="form1ex" id="form1ex">
          List Delimiter: 
          <label><input name="delim" type="radio" value="C" checked>
Comma</label> or <label><input name="delim" type="radio" value="S"> Semicolon</label> 
<label><input name="delim" type="radio" value="N"> New&nbsp;line</label>
<label><input name="delim" type="radio" value="X"> Newsletter</label>
<input name="acct" type="hidden" value="<?php echo $acct; ?>" />
<input name="export" type="submit" id="export" value="Export">    
        </form>
		</div>
        <h3>Send a message</h3>
<?php
	if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
	if( $okmesg ) echo "<h3 id='warning_msg'>$okmesg</h3>";
?>
		

<?php 	//} // preview
		} // ACCESS
		}
		else showLoginForm(); // UUID
		$style->ShowFooter();
?>
