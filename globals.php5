<?php
// global functions, variables, etc.

define(DEBUG,1);		// define as 1/0 to enable/disable additional non-fatal exceptions
define(EMERGENCY,0);	// immediately locks site from everyone except access 500
define(MYNAME,'Fuzion Health Group');

$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
$INCLUDE_DIR = "$DOCUMENT_ROOT/../includes";
$STORAGE_DIR = "$DOCUMENT_ROOT/../storage";
$HMA_DIR = "$DOCUMENT_ROOT/../hma";
$REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];

// application-specific
// global variables that must exist even if the user is not logged in

$ACCESS = 0;	// operator access level. 0 = none, 1-49 data entry (can edit residents),
				// 50-300 cust support (can edit customers),
				// 300-400 acct manager (can add new customers),
				// 400-499 administrators (can add/edit operators)
				// 500 superuser
$ACCT = 0;	// customer acct number
$MASTER = 0;	// customer master acct user
$UUID = 0;	// uid of current user
unset($USER);

$ResYears = array(2007, 2008, 2009, 2010, 2011, 2012, 2013, 2014);

function valid_email($address) {
  // check an email address is possibly valid
  if (preg_match('/^[a-zA-Z0-9_\.\-]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$/', $address))
    return true;
  else 
    return false;
}

require("$INCLUDE_DIR/dbconnect.php5");
require("$INCLUDE_DIR/classes.php5");
require("$INCLUDE_DIR/visual.php5");

function cookie_auth($user_id,$user_hash) {
	global $REMOTE_ADDR; global $UUID; global $ACCESS; global $ACCT; global $MASTER;
	try {
		$db = db_career();
		$user = new User($db,$user_id);
		$expdate = $user->exp_date;
		$today = date('Y-m-d');
		if( !$expdate ) $expdate = $today; // null exp date - ok
		if( $user && md5($user->password . $REMOTE_ADDR . $user->email . $user->uid) === $user_hash
			&& $expdate >= $today
		    && $user->status && $user->is_oper() ) {
			// success, let's accept credentials.
			if( !EMERGENCY || $user->access == 500 ) {
				$UUID = $user->uid;			$_SESSION['user_id'] = $UUID;
				$ACCESS = $user->access; 	$_SESSION['access'] = $ACCESS;
				$ACCT = $user->acct;		$_SESSION['acct'] = $ACCT;
				$MASTER = $user->master_acct;	$_SESSION['master_acct'] = $MASTER;
				//$user->setlastlogin($db);
				$_SESSION['userobj'] = clone $user;
				$db->close();
				return $user;
			}
		} 
		$db->close();
		unset($user_id,$user,$db);
	}
	catch (Exception $e) {
		// if( DEBUG ) echo "<!-- " . $e->getMessage() . " ( " . $e->getCode() . ") -->";
		// echo will break cookie set and header set.
		unset($user_id,$user,$db);
	}
	return false;
}

function logout($endsess = 1, $eatcookie = 0) {
// logout current user.
//	endsess: end current session, too.
//  eatcookie: delete uid and uha cookies.
	global $UUID; global $ACCESS; global $ACCT; global $MASTER; global $USER;
	$ACCESS = 0; $ACCT = 0; $MASTER = 0; unset($USER);
	$_SESSION['user_id'] = 0; $_SESSION['access'] = 0; $_SESSION['acct'] = 0;
	$_SESSION['master_acct'] = 0; unset($_SESSION['userobj']);
	if ( $endsess ) session_destroy();
	if ( $eatcookie ) {
		setcookie('uid','0',time()-60); setcookie('uha','0',time()-60);
	}
}

$SpecList = array();	// Specialty cache
$SpecList2 = array();	// Specialty cache prima
$StateList = array();	// States cache

function showSpecList($conn = NULL, $current = NULL, $selname = 'spec', $listtype = 2) {
// returns string containing <select> control with the given name,
// with options from specialties table sorted by group and name.
// It caches the table, so no db connection needed in the subsequent calls.
// List types: 0 = Categorized dropdown, 1 = Cat listbox multi, 2 = alpha dropdown, 3 = Alpha listbox multi
	global $SpecList; global $SpecList2;
	$multi = $listtype & 1; $listtype &= 2;
	if( $listtype != 2 ) $listtype = '';
	$pList = "SpecList$listtype";
	$select = "<select name='$selname' id='$selname' ".($multi?"size='10' multiple":"size='1'").'>';
	if( !$conn && !count($$pList) ) return false;
	$spc = explode(',',$current);  
	$group1st = true;
	if( count($$pList) ) foreach ($$pList as $code => $name) {
//		if( $code{0} == '@' ) $select .= "<option value='---'>*** $name *********</option>";
		if( $code{0} == '@' ) {
			if( $group1st ) $group1st = false; else $select .= '</optgroup>';
			$select .= "<optgroup label='$name *********'>";
		}
		elseif( in_array($code,$spc) ) {
			if( !$multi ) $select .= "<option value='$code' selected>$name ($code)</option>";
		}
		else $select .= "<option value='$code'>$name ($code)</option>";
	}
	elseif( $conn ) {
		if( $listtype ) {
			$res = $conn->query('select sp_code,sp_name,sp_prima from specialties order by sp_prima desc, sp_name');
			if( !$res || !$res->num_rows ) return false;
			$select .= "<optgroup label='PRIMARY SPECIALTIES *********'>";
			$SpecList2['@--'] = 'PRIMARY SPECIALTIES';
			$pr = 1;
			while( list($code,$name,$prima) = $res->fetch_row() ) {
				if( $prima != $pr ) {
					$pr = $prima;
					$SpecList2['@00'] = 'SECONDARY SPECIALTIES';
					$select .= "</optgroup><optgroup label='SECONDARY SPECIALTIES *********'>";
				}
				$SpecList2[$code] = $name;
				if( in_array($code,$spc) ) {
					if( !$multi ) $select .= "<option value='$code' selected>$name ($code)</option>";
				}
				else $select .= "<option value='$code'>$name ($code)</option>";
			}
		}
		else {
			$res = $conn->query('select * from specialties order by sp_group, sp_name');
			if( !$res || !$res->num_rows ) return false;
			while( list($code,$name,$group,$grcode) = $res->fetch_row() ) {
				if( $group != $groupo ) {
					$groupo = $group;
					$SpecList['@'.$grcode] = $group;
					if( $group1st ) $group1st = false; else $select .= '</optgroup>';
					$select .= "<optgroup label='$group *********'>";
				}
				$SpecList[$code] = $name;
				if( in_array($code,$spc) ) {
					if( !$multi ) $select .= "<option value='$code' selected>$name ($code)</option>";
				}
				else $select .= "<option value='$code'>$name ($code)</option>";
			}
		}
		$res->free();
	}
	$select .= '</optgroup></select>';
	return $select;
}


function showStateList($conn = NULL, $current = NULL, $selname = 'state') {
// returns string containing <select> control with the given name,
// with options from states table sorted by name
// It caches the table, so no db connection needed in the subsequent calls.
	global $StateList;
	if( !$conn && !count($StateList) ) return false;
	$select = "<select name='$selname' id='$selname' size='1'>";
	if( count($StateList) ) foreach ($StateList as $code => $name) {
			$select .= "<option value='$code'".($current == $code?' selected':'').">$name ($code)</option>";
	}
	elseif( $conn ) {
		$res = $conn->query('select st_code, st_name from states order by st_name');
		if( !$res || !$res->num_rows ) return false;
		while( list($code,$name) = $res->fetch_row() ) {
			$StateList[$code] = $name;
			$select .= "<option value='$code'".($current == $code?' selected':'').">$name ($code)</option>";
		}
		$res->free();
	}
	$select .= '</select>';
	return $select;
}

function AccessDesc($acc) {
	// returns access level name
	if( !$acc ) $desc = 'Access Denied';
	elseif($acc < 50) $desc = 'Data Entry';
	elseif($acc < 200) $desc = 'Database Manager';
	elseif($acc < 300) $desc = 'Customer Support';
	elseif($acc < 400) $desc = 'Account Manager';
	elseif($acc < 500) $desc = 'Administrator';
	elseif($acc == 500) $desc = 'Super Administrator';
	else $desc = 'Access Denied';
	return $desc;
}

function CustListYear($yer) {
	if( $yer == 1 ) $desc = 'Shadow List';
	elseif( $yer == 2006 ) $desc = 'Practicing';
	elseif( $yer == 2005 ) $desc = 'PCAREER';
	else $desc = $yer;
	return $desc;
}

function SearchRes($year,$wher,$joins = '') {
	// searches residents, returns number of rows. Throws exceptions.
	// year parameter is deprecated
	global $UUID;
	if( empty($wher) ) throw new Exception('Search: Required parameters are missing',__LINE__);
	$resdb = db_career();
	$resdb->query("delete from custlistsus where owneruid = $UUID and listid=0");
	$result = $resdb->query("insert into custlistsus select $UUID,ph_id,0 from physicians $joins where $wher");
	if( !$result ) throw new Exception(DEBUG?"Problem with query: $wher":'Program Error',__LINE__);
	return $resdb->affected_rows;
}

?>