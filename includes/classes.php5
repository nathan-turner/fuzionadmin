<?php
// global classes, etc.
// It is supposed to be included from $DOCUMENT_ROOT/globals.php5

function randomkeys($length) {
	// password generator
	$pattern = "123456789abcdefghijkLmnopqrstuvwxyzABCDEFGHJKMNPRSTUVWXYZ";
    for($i=0;$i<$length;$i++) 
		$key .= $pattern{rand(0,55)};
    return $key;
}

function chknul($v) {
	if( empty($v) ) return 'NULL';
	else return "'$v'";
}

function chknula($v) {
	if( empty($v) ) return 'NULL';
	else return "'".addslashes($v)."'";
}

function formatphone($a) {
	return $a? substr($a,0,strlen($a)-7).'-'.substr($a,-7,3).'-'.substr($a,-4): '';
}

function formatDateMod($date_mod) {
	// DEPRECATED
	return $date_mod;
	// formats from TIMESTAMP (YYYYMMDDHHMMSS) to ISO (YYYY-MM-DD HH:MM:SS)
	/*if( $date_mod ) {
		$dt = sscanf($date_mod,'%4s%2s%2s%2s%2s%2s');
		return vsprintf('%s-%s-%s %s:%s:%s',$dt);
	}
	else return false;*/
}

/* ************************************************************************
 * USER
 * ************************************************************************
 * Common Properties (see db for the full list):
 * uid, 
 * logdate, logip		  - last login date and IP if logged in.
 * lastlogdate, lastlogip - last login date and IP, or current if logged in.
 * email
 * password				  - sha1-encrypted
 * firstname, lastname
 * status				  - 1 active, 0 inactive/suspended; 2 expired if customer
 *		Constructor:
 * User($db,$uid)		  - loads user info by uid, either operator or customer
 * User($db,0,$username)  - operator by username
 * User($db,0,$email)	  - customer by email
 * 		Methods:
 * is_oper()			  - 1 if operator, 0 if customer
 * setlastlogin($db)	  - stores login information
 * change_password($db,$old,$new)
 * reset_password($db)	  - creates random password and emails it to user
 * ************************************************************************
 */

class User {
// Gets a user from the database, either Operator or Customer.
// It does not create a new user.
// New operator and new customer are created in the form processing modules, in place.
// Also see derived classes below.
    var $uid;		// user id, 1-9999 operators, >10000 customer
    protected $uobj;	// user name (same as email for customers)
	protected $table = 'clients';
	public $logdate;
	public $logip;
    function __construct($conn, $userid = 0, $username = NULL) {
	// gets a user record by either uid or email/uname
		if ( !$conn ) throw new Exception('Valid connection required',__LINE__);
		if ( !$userid && (!isset($username) || empty($username))  )
		    throw new Exception('Either userid or username are required',__LINE__);
		if ( $userid ) $this->get_by_uid($conn,$userid);
		elseif ( strpos($username,'@') === false ) { // not an email. operator?
			$this->table = 'operators';
		    $this->gets_operator($conn,addslashes($username));
		}
		else // user?
		    $this->gets_user($conn,addslashes($username));
    }
	public function __get($member) {
		if( $member == 'uid' ) return $this->uid;
		elseif( $member == 'logdate' ) return $this->logdate;
		elseif( $member == 'logip' ) return $this->logip;
		elseif( $member == 'username' )
			return $this->is_oper()? $this->uobj->username: stripslashes($this->uobj->email);
		elseif( $member == 'access' )
			return $this->is_oper()? $this->uobj->access: 0;
		elseif( $member == 'acct' )
			return $this->is_oper()? 0: $this->uobj->acct;
		elseif( $member == 'master_acct' )
			return $this->is_oper()? 0: $this->uobj->master_acct;
		elseif( $member == 'tc_agreed' )
			return $this->is_oper()? 1: $this->uobj->tc_agreed;
		elseif( $member == 'phone' )
			return formatphone($this->uobj->phone);
		elseif( $member == 'fax' )
			return formatphone($this->uobj->fax);
		elseif( $member == 'specs' )
			return $this->is_oper()? NULL: $this->uobj->specs;
		else {
			try {
				return is_string($this->uobj->$member)?stripslashes($this->uobj->$member):$this->uobj->$member;
			}
			catch(Exception $e) {
				// let's assume that it's just wrong member name. let's ignore it this time.
				// alternatively, you can re-throw an exception
				if( DEBUG ) throw $e;
				return false;
			}
		}
	}
	public function __set($member, $value) {
		global $ACCESS;
		if( $member == 'uid') return false; // uid is read-only
		elseif( $member == 'username' && !$this->is_oper() ) $this->uobj->email = addslashes($value);
		elseif( $member == 'access' && isset($this->uobj->access) && $this->uobj->access <= $ACCESS)
			$this->uobj->access = ($ACCESS > $value? $value: $ACCESS);
		elseif( $member == 'phone' && !$this->is_oper() ) $this->uobj->phone = ereg_replace('[^0-9]','',$value);
		else try {
			$this->uobj->$member = is_string($value)? addslashes($value): $value;
		}
		catch(Exception $e) {
			// ignore misspellings for now
			if( DEBUG ) throw $e;
			return false;
		}
		return $value;
	}
	protected function fetchobj($conn,$query) {
	// gets a row - any row, but only one
		$result = $conn->query($query);
		if ( !$result || $result->num_rows == 0 ) throw new Exception('Unknown user',__LINE__);
		$this->uobj = $result->fetch_object();
		$this->uid = $this->uobj->uid;
		$this->logdate = $this->uobj->lastlogdate;
		$this->logip = $this->uobj->lastlogip;
	}
    protected function gets_user($conn,$username) {
	// gets logon user info by email
		$this->fetchobj($conn,"select * from clients where email = '$username'");
    }
    protected function gets_operator($conn,$username) {
	// gets logon oper info by uname
		$this->fetchobj($conn,"select * from operators where username = '$username'");
    }
    protected function get_by_uid($conn,$userid) {
	// get logon user or oper by uid
		if ( $userid < 10000 ) $this->table = 'operators';
		$this->fetchobj($conn,"select * from ".$this->table." where uid = $userid");
    }
    function is_oper() {
		return isset($this->uid) && ($this->uid < 10000);
    }
	function setlastlogin($conn) {
		global $REMOTE_ADDR;
		if ( !$conn ) throw new Exception('Valid connection required',__LINE__);
		$this->logip = $this->uobj->lastlogip;
		$this->logdate = $this->uobj->lastlogdate;
		$this->uobj->lastlogip = $REMOTE_ADDR;
		$this->uobj->lastlogdate = date('r');
		$conn->query("delete from iplogscust WHERE logdate < date_sub( now(), INTERVAL 1 year)");
		$conn->query("insert into iplogscust (uid,ip) values ($this->uid,'$REMOTE_ADDR')");
		$result = $conn->query("update $this->table set lastlogdate = now(), lastlogip = '".
			$this->uobj->lastlogip."' where uid = ".$this->uid);
		if( !$result ) throw new Exception('Can not update last login time', __LINE__);
	}
	function change_password($conn,$old,$newp) {
	// this is for user or operator himself. I don't want to check if $uid == $UUID now
		global $UUID;
		if ( !$conn ) throw new Exception('Valid connection required',__LINE__);
		if ( !isset($newp) || empty($newp) ) throw new Exception('Password required',__LINE__);
		if ( $this->uobj->password != sha1(stripslashes($old)) ) throw new Exception('Old password is incorrect',16);
		$this->uobj->password = sha1(stripslashes($newp));
		$result = $conn->query("update $this->table set password='".$this->uobj->password.
			"',editedby=$UUID,editeddt=now() where uid = ".$this->uid);
		if( !$result ) throw new Exception('Can not update password', __LINE__);
	}
	function reset_password($conn) {
	// this is for any user or operator, even not logged in.
		global $UUID; global $REMOTE_ADDR;
		if ( !$conn ) throw new Exception('Valid connection required',1);
		$newp = randomkeys(rand(6,12));
		$this->uobj->password = sha1($newp);
		$result = $conn->query("update $this->table set password='".$this->uobj->password.
			"',editedby=$UUID,editeddt=now() where uid = ".$this->uid);
		if( !$result ) throw new Exception('Can not reset password', __LINE__);
		// now email the password
		$from = "From: help@physiciancareer.com\r\nX-Requested-by: $UUID/$REMOTE_ADDR\r\n";
		$msg = "Dear ".$this->uobj->firstname.
		    ":\r\n\r\nThe password for your PhysicianCareer.com account has been reset. Please note your\r\nnew password is: $newp (case-sensitive). We recommend that you log in now using your \r\nnew password and change it to something you will remember and use going forward.\r\n\r\nClick here to do that now: http://physiciancareer.com/employers/client-login/\r\n\r\nIf you believe you have received this email in error or that an unauthorized person has \r\naccessed your account, please log in now using your new password and then change it \r\nto something secure.\r\n\r\nPlease feel free to contact us by replying to this message if you have any questions or \r\nconcerns about our website.\r\n\r\nThank you,\r\nPhysicianCareer.com Support\r\n";
		if( !mail(stripslashes($this->uobj->email), 'PhysicianCareer.com password reset request', $msg, $from) )
			throw new Exception('Can not send email',17);
	}
	function agree_tc($conn) {
	// user agrees to terms and conditions when logs in 1st time
		if( $this->is_oper() ) return;
		if ( !$conn ) throw new Exception('Valid connection required',__LINE__);
		$this->uobj->tc_agreed = 1;
		$result = $conn->query("update $this->table set tc_agreed = 1 where uid = ".$this->uid);
		if( !$result ) throw new Exception('Can not update user record', __LINE__);
	}
	function save_user($conn) {
		// not exactly virtual function, but a stub
		return;
	}
	public function get_specs() {
		// returns array of specialty codes or NULL
		if( $this->is_oper() || !$this->uobj->specs ) return NULL;
		else return explode(',',$this->uobj->specs);
	}
	public function get_spec_str() {
		// returns NULL or string of specialty codes for sql query
		// e.g. ... "where spec in ($str)"
		if( $this->is_oper() || !$this->uobj->specs ) return NULL;
		else {
			$spec = explode(',',$this->uobj->specs);
			$str = "'" . implode("','",$spec) . "'";
			return $str;
		}
	}
	function getMasterCC($conn) {
		if( $this->is_oper() ) return NULL;
		if( $this->uobj->master_acct ) return stripslashes($this->uobj->maildrop);
		if( !$conn ) throw new Exception('Valid connection required',__LINE__);
		$sql = "select maildrop from clients where master_acct = 1 and acct = ".$this->uobj->acct;
		$result = $conn->query($sql);
		if( !$result ) throw new Exception(DEBUG?"$conn->error: $sql":'Can not find master', __LINE__);
		list($maildrop) = $result->fetch_row();
		$result->free();
		return stripslashes($maildrop);
	}
}

/* ************************************************************************
 * OPERATOR inherits all methods and properties from User
 * ************************************************************************
 * 		Operator-specific Properties (see db for the full list):
 * username
 * access				  - see comments below
 *		Constructor:
 * Operator($db,$uid)		  - loads user info by uid
 * Operator($db,0,$username)  - by username
 * 		Method:
 * save_user($db)		  - saves changes
 * ************************************************************************
 */

class Operator extends User {
	// saves updates. Other that that, there is no point in instantiating Operator
	// operator access level:
	// 0 = none, 1-49 data entry (can edit residents),
	// 50-300 cust support (can edit customers),
	// 300-400 acct manager (can add new customers),
	// 400-499 administrators (can add/edit operators)
	// 500 superuser
	function save_user($conn) {
		global $ACCESS; global $UUID;
		if ( !$conn ) throw new Exception('Valid connection required',__LINE__);
		// users with access < 400 have no right to update other users
		if ( !$ACCESS || ($ACCESS < 400 && ($ACCESS < 50 || $ACCESS >= 200)) ) throw new Exception('Access denied',__LINE__);
		$result = $conn->query("update $this->table set email = '".$this->uobj->email.
		"',password='".$this->uobj->password."',access = ".$this->uobj->access.
		",firstname='".$this->uobj->firstname."',lastname='".$this->uobj->lastname."',exp_date=".chknul($this->uobj->exp_date).
		",status=".$this->uobj->status.",editedby=$UUID,editeddt=now() where uid = $this->uid and access <= $ACCESS");
		if( !$result ) throw new Exception('Can not update a user record', __LINE__);
	}
}

/* ************************************************************************
 * CUSTOMER inherits all methods and properties from User
 * ************************************************************************
 * Customer-specific Properties (see db for the full list):
 * mrmsdr				- Mr, Ms or Dr
 * phone, fax
 * title, company
 * addr1, addr2, city, state, zip
 * tc_agreed			- customer agreed to T&C 
 * acct					- account number
 * master_acct			- 1 if master account, 0 if sub-account
 * subaccts				- max number of subaccts allowed, including master
 * exp_date				- null if no expiration date
 * specs				- null or 1-5 specialty codes joined together
 * subscription			- Subscription bits. 1 - residents, 2 - experienced, ...
 * opplimit				- limit in posting jobs
 * maildrop				- for master accts, CC email address
 * emaillimit				- limit in mail lists
 *		Constructor:
 * Customer($db,$uid)		- loads user info by uid
 * Customer($db,0,$email)	- customer by email
 * 		Methods:
 * getMasterCC($db)		- finds Master's maildrop
 * save_user($db)		- saves changes (by operator)
 * update_user($db)		- saves changes (by user himself)
 * agree_tc($db)		- customer accepts terms and conditions
 * ************************************************************************
 */ 

class Customer extends User {
	// saves updates. Other that that, there is no point in instantiating Customer.
	function save_user($conn) {
	// requested by operator
		global $ACCESS,$UUID;
		if ( !$conn ) throw new Exception('Valid connection required',__LINE__);
		if ( !$ACCESS || $ACCESS < 50 ) throw new Exception('Access denied',__LINE__); // 50 is min acl
		$sqlq = "update $this->table set email='".$this->uobj->email.
		  "',password='".$this->uobj->password."',acct=".$this->uobj->acct.
		  ",firstname='".$this->uobj->firstname."',lastname='".$this->uobj->lastname.
		  "',mrmsdr=".chknul($this->uobj->mrmsdr).",phone=".chknul($this->uobj->phone).
		  ",fax=".chknul($this->uobj->fax).",title=".chknul($this->uobj->title).
		  ",company=".chknul($this->uobj->company).",addr1=".chknul($this->uobj->addr1).
		  ",addr2=".chknul($this->uobj->addr2).",city=".chknul($this->uobj->city).
		  ",state=".chknul($this->uobj->state).",zip=".chknul($this->uobj->zip).
		  ",master_acct=".$this->uobj->master_acct.",subaccts=".$this->uobj->subaccts.
		  ",exp_date=".chknul($this->uobj->exp_date).",status=".$this->uobj->status.
		  ",specs=".chknul($this->uobj->specs).",subscription=".$this->uobj->subscription.
		  ",opplimit=".$this->uobj->opplimit.",maildrop=".chknul($this->uobj->maildrop).",emaillimit=".$this->uobj->emaillimit.
		  ",editedby=$UUID,editeddt=now() where uid = $this->uid";
		$result = $conn->query($sqlq);
		if( !$result ) throw new Exception(DEBUG?"$conn->error: $sqlq":'Can not update a customer record', __LINE__);
	}
	function update_user($conn) {
	// when requested by user himself or by master
		global $UUID; global $MASTER; global $ACCT;
		if ( !$conn ) throw new Exception('Valid connection required',__LINE__);
		if ( $UUID == $this->uid || ($MASTER && $ACCT == $this->uobj->acct) ) {
			// user himself can not change his email
			$updemail = ($UUID == $this->uid)? '': "email = '".$this->uobj->email."',";
			$sqlq = "update $this->table set $updemail password='".$this->uobj->password.
			  "',firstname='".$this->uobj->firstname.
			  "',lastname='".$this->uobj->lastname."',mrmsdr=".chknul($this->uobj->mrmsdr).
			  ",phone=".chknul($this->uobj->phone).",fax=".chknul($this->uobj->fax).
			  ",title=".chknul($this->uobj->title).",company=".chknul($this->uobj->company).
			  ",addr1=".chknul($this->uobj->addr1).",addr2=".chknul($this->uobj->addr2).
			  ",city=".chknul($this->uobj->city).",state=".chknul($this->uobj->state).
			  ",zip=".chknul($this->uobj->zip).",subscription=".$this->uobj->subscription.
			  ",maildrop=".chknul($this->uobj->maildrop).
			  ",editedby=$UUID,editeddt=now() where uid = $this->uid";
			$result = $conn->query($sqlq);
			if( !$result ) throw new Exception(DEBUG?"$conn->error: $sqlq":'Can not update the record', __LINE__);
		}
		else throw new Exception('Access denied', __LINE__);
	}
	function unexpire_user($resdb,$whole) {
	// un-expires all their expired locations and opps, and if whole then for the whole acct.
		global $ACCESS,$UUID;
		if ( !$resdb ) throw new Exception('Valid PCDB connection required',__LINE__);
		if ( !$ACCESS || $ACCESS < 50 ) throw new Exception('Access denied',__LINE__); // 50 is min acl
		if ( $whole ) { $sqla = "l_acct = ".$this->uobj->acct; $sqlo = "o_acct = ".$this->uobj->acct; }
		else { $sqla = "l_uid = ".$this->uid; $sqlo = "o_uid = ".$this->uid; }
		$expd = $this->uobj->exp_date;
		$sql = "update locations set status=1,exp_date='$expd',l_usermod=$UUID where status=2 and $sqla";
		$result = $resdb->query($sql); // ignore result for now
		if( $result ) {
			$sql = "update opportunities set status=1,exp_date='$expd',o_usermod=$UUID where status=8 and $sqlo";
			$result = $resdb->query($sql); // ignore result for now
			if( !$result ) throw new Exception(DEBUG?"$resdb->error: $sql":'Can not update records', __LINE__);
		}
		else throw new Exception(DEBUG?"$resdb->error: $sql":'Can not update locations', __LINE__);
	}
}

/* ************************************************************************
 * CUSTLIST
 * ************************************************************************ 
 * This class CREATES custom list
 */

class CustList {
	var $id;
	var $name;
	var $desc;
	var $acct;
	var $shared;
	var $cdb; // cached db connection
	protected $year;
	function __construct($na,$yer,$de = NULL, $act=0, $sha=0, $nuid = 0) {
		// name, year, desc, [account, shared - for customers]
		global $UUID; global $ACCESS;
		if( !$nuid || $ACCESS != 500 ) $nuid = $UUID;
		$this->year = $yer;
		$this->name = substr(addslashes(trim($na)),0,50);
		if( strlen($this->name) == 50 ) $this->name{49} = '-'; // to prevent unfinished escapes
		$this->desc = substr(addslashes(trim($de)),0,255);
		if( strlen($this->desc) == 255 ) $this->desc{254} = '-'; // to prevent unfinished escapes
		$db = db_career();
		$newlid = 10;
		for( $i = $newlid; $i < 128; $i++ ) {
			$result = $db->query("select listid from custlistdesc where listid = $i and uid = $nuid");
			if( !$result || !$result->num_rows ) { $newlid = $i; break; }
			$result->free();
		}
		$this->id = $newlid; if( $this->id < 10 ) $this->id = 10; // 1-10 are reserved
		if( $this->id > 127 ) throw new Exception('Maximum number of 127 lists reached',__LINE__);
		$this->shared = $sha; $this->acct = $act;
		$result = $db->query("insert into custlistdesc values ($nuid,$this->id,$yer,'$this->desc','$this->name',$act,$sha,NULL)");
		if( !$result ) throw new Exception('Can not insert new list',__LINE__);
		$this->cdb = $db;
	}
	function __get($member) {
		return $this->$member;
	}
	function __set($member,$value) {
		if( $member == 'id' || $member == 'year' || $member == 'cdb' ) return false;
		else $this->$member = $value;
		return $value;
	}
}

class CustListPC {
	var $id;
	var $name;
	var $desc;
	var $acct;
	var $shared;
	var $cdb; // cached db connection
	function __construct($na, $de = NULL, $act=0, $sha=0, $nuid = 0) {
		// name, year, desc, [account, shared - for customers]
		global $UUID; global $ACCESS;
		if( !$nuid || $ACCESS != 500 ) $nuid = $UUID;
		$this->name = substr(addslashes(trim($na)),0,50);
		if( strlen($this->name) == 50 ) $this->name{49} = '-'; // to prevent unfinished escapes
		$this->desc = substr(addslashes(trim($de)),0,255);
		if( strlen($this->desc) == 255 ) $this->desc{254} = '-'; // to prevent unfinished escapes
		$db = db_career();
		$newlid = 10;
		$result = $db->query("select listid from custlistdesc where listid between 10 and 127 and uid = $nuid");
		if( !$result ) throw new Exception('Can not insert new list',__LINE__);
		for( $i = $newlid; $i < 128 && $result->num_rows; $i++ ) {
			list($lid) = $result->fetch_row();
			if( $i != $lid ) { $newlid = $i; break; }
		}
		$result->free();
		$this->id = $newlid; if( $this->id < 10 ) $this->id = 10; // 1-10 are reserved
		if( $this->id > 127 ) throw new Exception('Maximum number of 127 lists reached',__LINE__);
		$this->shared = $sha; $this->acct = $act;
		$result = $db->query("insert into custlistdesc values ($nuid,$this->id,2005,'$this->desc','$this->name',$act,$sha,NULL)");
		if( !$result ) throw new Exception('Can not insert new list',__LINE__);
		$this->cdb = $db;
	}
	function __get($member) {
		return $this->$member;
	}
	function __set($member,$value) {
		if( $member == 'id' || $member == 'year' || $member == 'cdb' ) return false;
		else $this->$member = $value;
		return $value;
	}
}

// PC.Com classes - please SYNC changes!!!
// Read-only version!!!

// PC User - Read only, except select fields
class PCUser {
// Gets a user from the database - always a physician here
// It does not create a new user.
// New user is created in the form processing modules, in place.
// Also see derived classes below.
    var $uid;		// user id = ph_id
    protected $uobj;	// user object
	protected $resdb;	// db connection cache. Remember that it does not work between pages, so don't rely on it for $USER
	protected $table = 'physicians';
	protected $modi;	// modified fields array
	public $logdate;
	public $logip;
    function __construct($conn, $userid = 0, $username = NULL) {
	// gets a user record by either uid or email/uname
		if ( !$conn ) throw new Exception('Valid connection required',__LINE__);
		$this->resdb = $conn;
		if ( !$userid && (!isset($username) || empty($username))  )
		    throw new Exception('Either userid or username is required',__LINE__);
		if ( $userid ) $this->get_by_uid($conn,$userid);
		elseif ( strpos($username,'@') === false ) throw new Exception('Invalid username',__LINE__);
		else // user?
		    $this->gets_user($conn,addslashes($username));
		$this->modi = array();
    }
	public function __get($member) {
		if( $member == 'uid' || $member == 'ph_id' ) return $this->uid;
		elseif( $member == 'logdate' ) return $this->logdate;
		elseif( $member == 'logip' ) return $this->logip;
		elseif( $member == 'username' )	return stripslashes($this->uobj->email);
		elseif( $member == 'firstname' ) return stripslashes($this->uobj->fname);
		elseif( $member == 'lastname' ) return stripslashes($this->uobj->lname);
		elseif( $member == 'access' ) return 0;
		elseif( $member == 'acct' )	return 0;
		elseif( $member == 'master_acct' ) return 0;
		elseif( $member == 'homephone' ) return formatphone($this->uobj->homephone);
		elseif( $member == 'cellphone' ) return formatphone($this->uobj->cellphone);
		elseif( $member == 'officephone' ) return formatphone($this->uobj->officephone);
		//elseif( $member == 'pager' ) return formatphone($this->uobj->pager);
		elseif( $member == 'specs' ) return NULL;
		else {
			try {
				return is_string($this->uobj->$member)?stripslashes($this->uobj->$member):$this->uobj->$member;
			}
			catch(Exception $e) {
				// let's assume that it's just wrong member name. let's ignore it this time.
				// alternatively, you can re-throw an exception
				if( DEBUG ) throw $e;
				return false;
			}
		}
	}
	public function __set($member, $value) {
		if( $member == 'uid' || $member == 'ph_id' ) return false; // uid is read-only
		elseif( ($member == 'username' || $member == 'email') && $this->uobj->email != addslashes($value) ) { 
			$this->modi['email_confirm'] = $this->uobj->email? $this->uobj->email: '(old)'; // save old value
			$this->uobj->email = addslashes($value); $this->modi['email'] = 2;
			$this->uobj->email_bounces = 0; $this->mobi['email_bounces'] = 1;
			$this->uobj->email_confirm = 0;
		}
		elseif( $member == 'homephone' ) { $this->uobj->homephone = ereg_replace('[^0-9]','',$value); $this->modi['homephone'] = 3; }
		elseif( $member == 'officephone' ) { $this->uobj->officephone = ereg_replace('[^0-9]','',$value); $this->modi['officephone'] = 3; }
		elseif( $member == 'cellphone' ) { $this->uobj->cellphone = ereg_replace('[^0-9]','',$value); $this->modi['cellphone'] = 3; }
		//elseif( $member == 'pager' ) { $this->uobj->pager = ereg_replace('[^0-9]','',$value); $this->modi['pager'] = 3; }
		elseif( $member == 'inactive' ) { $this->uobj->inactive = $value; $this->modi['inactive'] = 1; }
		else return false; // read-only
		return $value;
	}
	protected function fetchobj($conn,$query) {
	// gets a row - any row, but only one
		$result = $conn->query($query);
		if ( !$result || $result->num_rows == 0 ) throw new Exception('Unknown profile ID',__LINE__);
		$this->uobj = $result->fetch_object();
		$this->uid = $this->uobj->ph_id;
		$this->logdate = $this->uobj->lastlogdate;
		$this->logip = $this->uobj->lastlogip;
	}
    protected function gets_user($conn,$username) {
	// gets logon user info by email
		$this->fetchobj($conn,"select * from $this->table where email = '$username'");
    }
    protected function gets_operator($conn,$username) {
	// identical to gets_user
		$this->fetchobj($conn,"select * from $this->table where email = '$username'");
    }
    protected function get_by_uid($conn,$userid) {
	// get logon user uid
		//if ( $userid < 10000 ) $this->table = 'operators';
		$this->fetchobj($conn,"select * from $this->table where ph_id = $userid");
    }
    function is_oper() {
		return false;
    }
	function setlastlogin($conn = NULL) {
		return false;
	}
	function set_pending($conn = NULL,$uuid = 0) {
		global $UUID;
		if ( !$conn && !$this->resdb ) throw new Exception('Valid connection required',__LINE__);
		else $conn2 = $conn? $conn: $this->resdb;
		$uuid2 = $uuid? $uuid: $UUID;
		$result = $conn2->query("insert into pendings values($this->uid, curdate(), $uuid2) ON DUPLICATE KEY UPDATE pdate=curdate(), uidmod=$uuid2");
		if( !$result ) throw new Exception('Can not update interview date', __LINE__);
	}
	function unset_pending($conn = NULL) {
		if ( !$conn && !$this->resdb ) throw new Exception('Valid connection required',__LINE__);
		else $conn2 = $conn? $conn: $this->resdb;
		$result = $conn2->query("delete from pendings where phid = $this->uid");
		if( !$result ) throw new Exception('Can not update verification data', __LINE__);
	}
	function change_password($conn = NULL,$old = NULL,$newp) {
	// this is for operator. Does not check old
		global $UUID;
		if ( !$conn && !$this->resdb ) throw new Exception('Valid connection required',__LINE__);
		else $conn2 = $conn? $conn: $this->resdb;
		if ( !isset($newp) || empty($newp) ) throw new Exception('Password required',__LINE__);
		$this->uobj->password = sha1(stripslashes($newp));
		$result = $conn2->query("update $this->table set password='".$this->uobj->password."',uid_mod=$UUID where ph_id = ".$this->uid);
		if( !$result ) throw new Exception('Can not update password', __LINE__);
		$this->save_user();
	}
	function make_new_password($conn = NULL) {
	// this is for any user or operator, even not logged in.
		return false;
	}
	function reset_password($conn = NULL) {
	// this is for any user or operator, even not logged in.
		return false;
	}
	public function confirm_email( $addr ) {
		// 1. send email to the old address with the option to override.
		// 2. send email to the new address with the option to confirm.
		global $UUID; global $REMOTE_ADDR;
		// now email
		$newe = stripslashes($this->uobj->email);
		$newt = time();
		$myname = 'PhysicianCareer.com';
		$from = "From: help@physiciancareer.com\r\nX-Requested-by: $UUID/$REMOTE_ADDR\r\nPrecedence: normal\r\n";
		if( $addr && $addr != '(old)' ) {
			$thelink1 = 'http://physiciancareer.com/log-in/?u='.$this->uobj->ph_id.'&e='.urlencode($addr)
				.'&d='.$newt.'&v='. sha1($this->uobj->ph_id.$addr.$newt.'Please reset my email back - this is the secret code!');
			$msg = "Dear Dr. ".$this->uobj->lname.
				":\r\n\r\nThe email address for your $myname registration has been changed. The \r\nregistered email was changed to <$newe>. If you did NOT request this \r\nchange, please click on the link below to log in to your account and reset it. If you \r\ndid request it, please check for a registration message sent to your new email account.\r\n\r\n$thelink1\r\n\r\nIf you believe you have received this email in error or that an unauthorized person has \r\naccessed your account, please log in to your account now and reset it back to your \r\noriginal email.\r\n\r\nPlease contact us by replying to this message if you have questions or concerns about our website.\r\n\r\nThank you,\r\n$myname Support\r\n\r\nThis notification was sent to address: ".$addr."\r\n";
			if( !mail(stripslashes($addr), 'Physician Career registration', $msg, $from) )
				throw new Exception('Can not send email',__LINE__);
		}
		$thelink2 = 'http://physiciancareer.com/log-in/?i='.$this->uobj->ph_id
			.'&d='.$newt.'&k='. sha1($this->uobj->ph_id.$newe.$newt.'Please confirm my email, its mine - this is the secret code!');
		$msg = "Dear Dr. ".$this->uobj->lname.
		    ":\r\nThank you for registering to join the online community of physicians and employers on \r\n$myname. This email is being sent in an attempt to verify the email address \r\nyou used for registration. Having your correct email address ensures that employers can \r\ncontact you regarding opportunities that meet your specified preferences.\r\n\r\nThe address <$newe> is your new user name.\r\nPlease click on the confirmation link below below to confirm your email address and start using \r\n$myname to help you manage your career.\r\n\r\n$thelink2\r\n\r\nRemember, keeping an active member profile enables you to stay up-to-date with\r\nthe latest career information and practice opportunities.\r\n\r\nPlease contact us by replying to this message if you have questions or concerns about our website.\r\n\r\nThanks again,\r\nTom Broxterman\r\n$myname\r\n404-591-4256 (direct)\r\n";
		if( !mail($newe, 'PhysicianCareer.com registration - Welcome', $msg, $from) )
			throw new Exception('Can not send email',__LINE__);
		return true;
	}
	function agree_tc($conn = NULL) {
	// deprecated
		return false;
	}
	function save_user() {
		return false;
	}
	public function get_specs() {
		return NULL;
	}
	public function get_spec_str() {
		return NULL;
	}
}


/* ************************************************************************
 * PC PHYSICIAN
 * ************************************************************************ 
  `ph_id` int(10) unsigned NOT NULL auto_increment,
  `res_id` int(10) unsigned default NULL,
  `year` smallint(6) default NULL,
  `dup` tinyint(1) NOT NULL default '0',
  `inactive` tinyint(1) NOT NULL default '0',
  `iv_date` date default NULL,
  `checkin` tinyint(1) NOT NULL default '0',
  `status` tinyint(4) NOT NULL default '1',
  `uid_mod` int(10) unsigned default NULL,
  `date_mod` timestamp(14) NOT NULL,
  `fname` varchar(50) NOT NULL default '',
  `midname` varchar(30) default NULL,
  `lname` varchar(50) NOT NULL default '',
  `title` varchar(50) default NULL,
  `mddo` varchar(4) NOT NULL default 'MD',
  `addr1` varchar(50) default NULL,
  `addr2` varchar(50) default NULL,
  `city` varchar(30) default NULL,
  `state` char(2) NOT NULL default '--',
  `zip` varchar(10) default NULL,
  `ofaddr1` varchar(50) default NULL,
  `ofaddr2` varchar(50) default NULL,
  `ofcity` varchar(30) default NULL,
  `ofstate` char(2) NOT NULL default '--',
  `ofzip` varchar(10) default NULL,
  `ho` tinyint(4) NOT NULL default '2',
  `homephone` varchar(16) default NULL,
  `cellphone` varchar(10) default NULL,
  `officephone` varchar(16) default NULL,
  `officeext` varchar(10) default NULL,
  `pager` varchar(16) default NULL,
  `pagerext` varchar(10) default NULL,
  `email` varchar(128) NOT NULL default '',
  `email_2nd` varchar(128) default NULL,
  `email_bounces` tinyint(1) NOT NULL default '0',
  `spec` char(3) NOT NULL default '---',
  `spec_2nd` varchar(50) default NULL,
  `school` varchar(100) default NULL,
  `sch_loc` varchar(50) default NULL,
  `sch_state` char(2) NOT NULL default '--',
  `amg` tinyint(1) NOT NULL default '0',
  `sch_year` int(11) default NULL,
  `fellowship` varchar(100) default NULL,
  `fel_state` char(2) NOT NULL default '--',
  `fel_city` varchar(30) default NULL,
  `fel_spec` char(3) NOT NULL default '---',
  `fel_year` int(11) default NULL,
  `program` varchar(100) default NULL,
  `res_state` char(2) NOT NULL default '--',
  `res_city` varchar(30) default NULL,
  `res_spec` char(3) NOT NULL default '---',
  `res_year` int(11) default NULL,
  `program_2` varchar(100) default NULL,
  `res2_state` char(2) NOT NULL default '--',
  `res2_city` varchar(30) default NULL,
  `res2_spec` char(3) NOT NULL default '---',
  `res2_year` smallint(6) default NULL,
  `fellow_2` varchar(100) default NULL,
  `fel2_state` char(2) NOT NULL default '--',
  `fel2_city` varchar(30) default NULL,
  `fel2_spec` char(3) NOT NULL default '---',
  `fel2_year` smallint(6) default NULL,
  `avail_date` date default NULL,
  `licensed` varchar(100) default NULL,
  `visa_status` char(3) default NULL,
  `citizen` tinyint(4) NOT NULL default '0',
  `birth_state` char(2) NOT NULL default '--',
  `bcbe` varchar(20) default NULL,
  `bcbe_year` smallint(6) default NULL,
  `pref_region` set('1','2','3','4','5','6','7','8','9','0') default NULL,
  `pref_states` set('AK','AL','AR','AZ','CA','CO','CT','DC','DE','FL','GA','HI','IA','ID','IL','IN','KS','KY','LA','MA','MD','ME','MI','MN','MO','MS','MT','NC','ND','NE','NH','NJ','NM','NV','NY','OH','OK','OR','PA','PR','RI','SC','SD','TN','TX','UT','VA','VT','WA','WI','WV','WY') default NULL,
  `pref_stopen` tinyint(1) NOT NULL default '0',
  `pref_city` varchar(100) default NULL,
  `pref_commu2` set('S','C','M') default NULL,
  `pref_amen` set('L','A','D','P','S') default NULL,
  `pref_school` set('P','V','R') default NULL,
  `pref_practice` varchar(50) default NULL,
  `marital_status` char(1) NOT NULL default '',
  `children` tinyint(1) NOT NULL default '0',
  `spouse` varchar(60) default NULL,
  `spouse_prof` varchar(50) default NULL,
  `spouse_spec` char(3) NOT NULL default '---',
  `spouse_state` char(2) NOT NULL default '--',
  `languages` varchar(60) default NULL,
  `hobbies` varchar(250) default NULL,
  `contact_pref` tinyint(4) NOT NULL default '0',
  `interviewing` tinyint(1) NOT NULL default '1',
  `reason_leaving` varchar(255) default NULL,
  `other_pref` varchar(255) default NULL,
  `salary_hour` tinyint(4) NOT NULL default '0',
  `salary_min` double default NULL,
  `salary_exp` double default NULL,
  `salary_other` varchar(120) default NULL,
  `password` varchar(40) NOT NULL default '',
  `newsletter` tinyint(1) NOT NULL default '1',
  `notifications` tinyint(1) NOT NULL default '1',
  `secret_q` varchar(50) default NULL,
  `secret_a` varchar(50) default NULL,
  `lastlogdate` datetime default NULL,
  `lastlogip` varchar(16) default NULL,
	email_confirm  	tinyint(1)
	 iv_complete  	int(11) 
	 noemail  	tinyint(1) 	
	 reg_date  	datetime 	NULL
	 pending  	tinyint(4) 	
	 last_save  	date 	NULL
	 uid_saved  	int(10) NULL
	 source		tinyint(4)
	 data_entry	datetime NULL
	 as_new		tinyint(4) default '1'
	 phg_source varchar(50) NULL
 */

class Physician extends PCUser {
	public function __get($member) {
		switch($member) {
			case 'uid':			return $this->uid;
			case 'res_name':	return stripslashes($this->uobj->program);
			case 'res_phone':	return NULL;
			case 'res2_name':	return stripslashes($this->uobj->program_2);
			case 'homephone':	return formatphone($this->uobj->homephone);
			case 'cellphone':	return formatphone($this->uobj->cellphone);
			case 'officephone':	return formatphone($this->uobj->officephone);
			//case 'pager':		return formatphone($this->uobj->pager);
			default: try { 		return is_string($this->uobj->$member)?stripslashes($this->uobj->$member):$this->uobj->$member;
				} catch(Exception $e) {
					if( DEBUG ) throw $e; return false;
				}
		}
	}
	public function __set($member, $value) {
		if( $member == 'uid' || $member == 'ph_id' ) return false; // uid is read-only
		elseif( ($member == 'username' || $member == 'email') && $this->uobj->email != addslashes($value) ) { 
			$this->modi['email_confirm'] = $this->uobj->email? $this->uobj->email: '(old)'; // save old value
			$this->uobj->email = addslashes($value); $this->modi['email'] = 2;
			$this->uobj->email_bounces = 0; $this->mobi['email_bounces'] = 1;
			$this->uobj->email_confirm = 0;
		}
		elseif( $member == 'homephone' ) { $this->uobj->homephone = ereg_replace('[^0-9]','',$value); $this->modi['homephone'] = 3; }
		elseif( $member == 'officephone' ) { $this->uobj->officephone = ereg_replace('[^0-9]','',$value); $this->modi['officephone'] = 3; }
		elseif( $member == 'cellphone' ) { $this->uobj->cellphone = ereg_replace('[^0-9]','',$value); $this->modi['cellphone'] = 3; }
		//elseif( $member == 'pager' ) { $this->uobj->pager = ereg_replace('[^0-9]','',$value); $this->modi['pager'] = 3; }
		/*elseif( $member == 'salary_min' || $member == 'salary_exp' ) {
		   // check for numbers only, but replace K with 000
		   $this->uobj->$member = preg_replace(array('/k/i','/m/i','/[^\d.KkMm]/'),array('000','000000',''), $value);
		   $this->modi[$member] = 3;
		}*/
		else try {
			$this->uobj->$member = is_string($value)? addslashes($value): $value;
			$this->modi[$member] = is_string($this->uobj->$member)? 2: 1;
		}
		catch(Exception $e) {
			if( DEBUG ) throw $e;
			return false;
		}

		return $value;
	}
	public function save_res($verify = 0) {
		global $UUID;
		// new approach
		$sql = "update physicians set ";
		foreach( $this->modi as $key => $vtype ) {
			if( $vtype == 1 || $key == 'email_confirm') $varv = is_null($this->uobj->$key)?'NULL':$this->uobj->$key;
			//elseif( $vtype == 2 ) $varv = chknul(addslashes($this->uobj->$key)); // addslashes? is it really needed? NOO
			else $varv = chknul($this->uobj->$key);
			$sql .= "$key=$varv,";
		}
		$subsql = $verify? '':"`uid_saved`=$UUID,";
		$sql .= "uid_mod=$UUID,$subsql last_save=now() where ph_id=$this->uid";
		if( $this->modi['email_confirm'] && $this->uobj->email )
			$this->confirm_email(NULL); // send email change confirmation to a new address only
		$result = $this->resdb->query($sql);
		if( !$result ) throw new Exception(DEBUG?"{$this->resdb->error}: $sql":'Can not update the record', __LINE__);
		$this->modi = array(); // drop old array
		return $result;
	}
	public function specswap() {
		$this->resdb->query("delete from ratings where r_ph_id = $this->uid");
		$this->resdb->query("delete from ratingdoc where rdid = $this->uid");
	}
	public function save_doc() {
		// doc himself saves his/her profile
		return false;
	}	
	public function getcv($cuid = 0) { // it gets only chunk 0
		// it checks cv_status (access) here, 0 = public, 1 = applied to, 2 = private
		// cuid is client uid, default is UUID
		global $UUID;
		global $ACCESS;
		$result = $this->resdb->query("select * from cvs where cv_ph_id = $this->uid"); /*  and chunk=0 */
		if( !$result || $result->num_rows == 0 ) return NULL;
		$cv = $result->fetch_object();
		$result->free();
		if( !$cv->cv_status || $ACCESS ) return $cv; // non-zero ACCESS overrides cv access rules
		elseif( $cv->cv_status >= 2 ) return NULL;
		else {
			// cv_status=1, it will check applications
			if( !$cuid ) $cuid = is_null($UUID)?0:$UUID;
			$sql = "select count(*) from applications join opportunities on oid = opid where phid = $this->uid and o_uid = $cuid and applications.status != 4 and opportunities.status != 0";
			$result = $this->resdb->query($sql);
			if( !$result || $result->num_rows == 0 ) throw new Exception(DEBUG?"{$this->resdb->error}: $sql":'Can not verify CV access',__LINE__);
			list($cnt) = $result->fetch_row();
			$result->free();
			return $cnt? $cv: NULL;
		}
	}
	function sendcv($cv = NULL) {
		global $STORAGE_DIR;
		if( !$cv ) $cv = $this->getcv();
		if( $cv ) {
			header( "Content-Type: " . $cv->contenttype );
			header( "Content-Length: " . $cv->filesize );
			header( "Content-Disposition: attachment; filename=\"$cv->filename\"" );
			$file_to_download = "$STORAGE_DIR/$cv->internal";
			readfile($file_to_download);
		}
	}
	public function savecv($userfile) { // userfile is $_FILES[var]
		global $STORAGE_DIR;
		if( is_uploaded_file($userfile['tmp_name']) ) {
			$this->resdb->query("delete from cvs where cv_ph_id = $this->uid");
			$file_size = $userfile['size'];
		    $file_type = $userfile['type'];
		    $file_name = addslashes($userfile['name']);

		    $random_number = rand(10000,99999);
			$internal_file = "$this->uid.cvfile.$random_number";
			$uploadfile = "$STORAGE_DIR/$internal_file";
			if( !move_uploaded_file($userfile['tmp_name'], $uploadfile) ) throw new Exception('Can not save CV File',__LINE__);

			$sql = "insert into cvs (cv_ph_id,filesize,contenttype,filename,internal) values ($this->uid, $file_size, '$file_type', '$file_name', '$internal_file')";
			$result = $this->resdb->query($sql);
			if( !$result ) {
				unlink($uploadfile);
				throw new Exception('Can not save CV File in the database',__LINE__);
			}
		}
	}
	public function setcvaccess($accs) { // access 0 - public, 1 - to whom I applied only, 2 - private (me only)
		if( !is_numeric( $accs ) || $accs < 0 || $accs > 255 ) $accs = 0;
		$result = $this->resdb->query("update cvs set cv_status = $accs where cv_ph_id = $this->uid"); /*  and chunk=0 */
		if( !$result ) throw new Exception(DEBUG?$this->resdb->error:'Can not set CV access level',__LINE__);
		return $result;
	}
}

/* ************************************************************************
 * PC LOCATIONS
 * ************************************************************************ 
  `l_id` int(10) unsigned NOT NULL auto_increment,
  `l_uid` int(10) unsigned NOT NULL default '0',
  `l_facility` varchar(100) default NULL,
  `l_city` varchar(50) NOT NULL default '',
  `l_state` char(2) NOT NULL default '--',
  `l_zip` varchar(5) default NULL,
#  `practicetype` varchar(50) default NULL,
  `status` tinyint(4) NOT NULL default '1',
  `l_commu2` enum('S','C','M') default NULL,
#  `l_amen` set('B','A','M','R','S') default NULL,
#  `l_school` set('P','V','R') default NULL,
  `l_underserved` tinyint(1) NOT NULL default '0',
  `l_description` text default NULL,
  `l_pic0` blob,
  `l_pic1` blob,
  `l_pic2` blob,
  `l_pic3` blob,
  `l_pic4` blob,
  `l_pic0type` varchar(50) default NULL,
  `l_pic1type` varchar(50) default NULL,
  `l_pic2type` varchar(50) default NULL,
  `l_pic3type` varchar(50) default NULL,
  `l_pic4type` varchar(50) default NULL,
  `l_pic0size` int(11) NOT NULL default '0',
  `l_pic1size` int(11) NOT NULL default '0',
  `l_pic2size` int(11) NOT NULL default '0',
  `l_pic3size` int(11) NOT NULL default '0',
  `l_pic4size` int(11) NOT NULL default '0',
  `l_datemod` timestamp,
  `l_usermod` int(10) unsigned NOT NULL default '0',
  `exp_date` date default NULL,
  `l_acct` int(11) NOT NULL default '0',
  `l_commdescr` text default NULL,
 */
class PCLocation {
    protected $obj;	// db object without blobs
	protected $cdb; // connection cache
    function __construct($conn, $locid) {
	// get a doc from the database
		if ( !$conn ) throw new Exception('Valid connection required',__LINE__);
		$this->cdb = $conn;
		if ( !$locid || !is_numeric($locid) ) throw new Exception('Valid location id required',__LINE__);
		$result = $conn->query("SELECT `l_id`, `l_uid`, `l_facility`, `l_city`, `l_state`, `l_zip`, `status`, `l_commu2`, `l_underserved`, `l_description`, `l_pic0type`, `l_pic1type`, `l_pic2type`, `l_pic3type`, `l_pic4type`, `l_pic0size`, `l_pic1size`, `l_pic2size`, `l_pic3size`, `l_pic4size`, `l_datemod`, `l_usermod`, `exp_date`, `l_acct`, `l_commdescr` FROM `locations` WHERE l_id = $locid");
		// no more `practicetype`,
		if ( !$result || $result->num_rows == 0 ) throw new Exception('Unknown location',__LINE__);
		$this->obj = $result->fetch_object();
		$result->free();
	}
	public function __get($member) {
		//loads pics on demand
		switch($member) {
			case 'id':			return $this->obj->l_id;
			case 'l_pic0':		return $this->getpic(0);
			case 'l_pic1':		return $this->getpic(1);
			case 'l_pic2':		return $this->getpic(2);
			case 'l_pic3':		return $this->getpic(3);
			case 'l_pic4':		return $this->getpic(4);
			default: try { 		return is_string($this->obj->$member)?stripslashes($this->obj->$member):$this->obj->$member;
				} catch(Exception $e) {
					if( DEBUG ) throw $e; return false;
				}
		}
	}
	public function getpic($nomer) {
		if( $this->getpicsize($nomer) > 0 ) {
			$member = "l_pic$nomer";
			if( empty($this->obj->$member) ) {
				$sql = "select $member from locations where l_id = ".$this->obj->l_id;
				$result = $this->cdb->query($sql);
				if( !$result || $result->num_rows == 0 ) return false;
				list($img) = $result->fetch_row();
				$this->obj->$member = $img; //stripslashes($img); // no need to strip
			}
			return $this->obj->$member;
		}
		return false;
	}
	public function getpicsize($nomer) {
		if( is_numeric($nomer) && $nomer >= 0 && nomer <= 4 ) {
			$member = "l_pic".$nomer."size";
			return $this->obj->$member;
		}
		return false;
	}
	public function getpictype($nomer) {
		if( is_numeric($nomer) && $nomer >= 0 && nomer <= 4 ) {
			$member = "l_pic".$nomer."type";
			return $this->obj->$member;
		}
		return false;
	}
	function __set($member,$value) {
		// pictures are not supposed to be updated here
		if( $member == 'id' || $member == 'l_id' ) return false;
		elseif( substr($member,0,5) == 'l_pic' ) return false;
		else try {
			$this->obj->$member = is_string($value)? addslashes($value): $value;
		}
		catch(Exception $e) {
			if( DEBUG ) throw $e;
			return false;
		}
		return $value;
	}
	public function setpic($nomer, $img, $size, $ctyp) {
		$result = false;
		global $UUID;
		if( is_numeric($nomer) && $nomer >= 0 && nomer <= 4 && $img) {
			$member = "l_pic$nomer";
			$memsize = $member."size";
			$memtype = $member."type";
			$this->obj->$member = $img;
			$this->obj->$memsize = is_numeric($size)? $size: strlen($img);
			$this->obj->$memtype = $ctyp;
			$sql = "update locations set $member = '".addslashes($img)."', $memsize = ".$this->obj->$memsize.
				", $memtype = ".(empty($ctyp)?'NULL':"'$ctyp'").", l_usermod = $UUID where l_id = ".$this->obj->l_id;
			$result = $this->cdb->query($sql);
		}
		return $result;
	}
	public function delpic($nomer) {
		$result = false;
		global $UUID;
		if( is_numeric($nomer) && $nomer >= 0 && nomer <= 4 ) {
			$member = "l_pic$nomer";
			$memsize = $member."size";
			$memtype = $member."type";
			$this->obj->$member = NULL;
			$this->obj->$memsize = 0;
			$this->obj->$memtype = NULL;
			$sql = "update locations set $member = NULL, $memsize = 0, $memtype = NULL, l_usermod = $UUID where l_id = ".$this->obj->l_id;
			$result = $this->cdb->query($sql);
		}
		return $result;
	}
	function showpic($nomer) {
		$img = $this->getpic($nomer);
		if( !empty($img) ) {
		  header( "Content-Type: " . $this->getpictype($nomer) );
		  header( "Content-Length: " . $this->getpicsize($nomer) );
		  //header( "Content-Disposition: attachment; filename=$fobj->fname" );
		  echo $img;
		}
	}
	function save() {
		// does not save pictures, use setpic above for that
		global $ACCESS, $UUID, $USER;
		$expt = strtotime($this->obj->exp_date);
		if( !$ACCESS && ($expt === FALSE || $expt != strtotime($USER->exp_date)) ) $this->obj->exp_date = $USER->exp_date;
		$sql = "update locations set `l_facility`=".chknul($this->obj->l_facility).", `l_city`=".chknul($this->obj->l_city).
			", `l_state`='".$this->obj->l_state."', `l_zip`=".chknul($this->obj->l_zip).
			", `l_commdescr`=".chknul($this->obj->l_commdescr).", `status`=".$this->obj->status.
			", `l_commu2`=".chknul($this->obj->l_commu2).", `l_underserved`=".$this->obj->l_underserved.
			", `exp_date`=".chknul($this->obj->exp_date).
			", `l_description`=".chknul($this->obj->l_description).", `l_usermod`= $UUID where l_id = ".$this->obj->l_id;
		$result = $this->cdb->query($sql);
		if( !$result ) throw new Exception(DEBUG?"{$this->cdb->error}: $sql":'Can not update location',__LINE__);
	}
	function deactivate() {
		global $UUID;
		$this->obj->status = 0;
		$sql = "update locations set `status`=0, `l_usermod`= $UUID where l_id = ".$this->obj->l_id;
		$result = $this->cdb->query($sql);
		if( !$result ) throw new Exception(DEBUG?"{$this->cdb->error}: $sql":'Can not deactivate location',__LINE__);
		$sql = "update opportunities set `status`=0, `o_usermod`= $UUID where status=1 and o_lid = ".$this->obj->l_id;
		$result = $this->cdb->query($sql);
		if( !$result ) throw new Exception(DEBUG?"{$this->cdb->error}: $sql":'Can not deactivate opportunities',__LINE__);
		$sql = "update ratingop, opportunities set ro_spec='N/A' where roid = oid and o_lid = ".$this->obj->l_id;
		$result = $this->cdb->query($sql);
		$sql = "delete ratings from ratings join opportunities on r_oid = oid where o_lid = ".$this->obj->l_id;
		$result = $this->cdb->query($sql);
		$sql = "update applications, opportunities set applications.status=0 where opid = oid and o_lid = ".$this->obj->l_id;
		$result = $this->cdb->query($sql);
	}
	function reactivate($opps = true) { // true = reactivates inactive and expired opportunities for this location, too
		global $UUID, $ACCESS, $USER;
		$this->obj->status = 1;
		$expd = $ACCESS? '': ",exp_date = '$USER->exp_date' ";
		$sql = "update locations set `status`=1, `l_usermod`= $UUID $expd where l_id = ".$this->obj->l_id;
		$result = $this->cdb->query($sql);
		if( !$result ) throw new Exception(DEBUG?"{$this->cdb->error}: $sql":'Can not reactivate location',__LINE__);
		if( $opps ) {
			$sql = "update opportunities set `status`=1, `o_usermod`= $UUID $expd where status in (0,8) and o_lid = ".$this->obj->l_id;
			$result = $this->cdb->query($sql);
			if( !$result ) throw new Exception(DEBUG?"{$this->cdb->error}: $sql":'Can not reactivate opportunities',__LINE__);
			$sql = "insert ratingop (roid,ro_spec) select oid,specialty from opportunities where o_lid = ".$this->obj->l_id.
				" on duplicate key update ro_spec=values(ro_spec)";
			$result = $this->cdb->query($sql);
			// note that such reactivation does not re-rate
		}
	}
}

/* ************************************************************************
 * Opportunities PC
 * ************************************************************************
 * CREATE TABLE IF NOT EXISTS `opportunities` (
  `oid` int(10) unsigned NOT NULL auto_increment,
  `o_uid` int(10) unsigned NOT NULL default '0',
  `o_lid` int(10) unsigned NOT NULL default '0',
  `o_facility` varchar(100) default NULL,
  `o_name` varchar(50) default NULL,
  `o_city` varchar(50) default NULL,
  `o_state` char(2) NOT NULL default '--',
  `show_state` tinyint(1) NOT NULL default '1',
  `o_zip` varchar(5) default NULL,
  `practice_type` varchar(50) default NULL,
  `status` tinyint(4) NOT NULL default '1',
  `o_commu2` enum('S','C','M') default NULL,
  `o_amen` set('B','A','M','R','S') default NULL,
  `o_school` set('P','V','R') default NULL,
  `o_underserved` tinyint(1) NOT NULL default '0',
  `description` text,
  `specialty` char(3) NOT NULL default '---',
#  `o_bcbe` char(2) default NULL,
  `avail_date` datetime default NULL,
#  `o_salaryhour` tinyint(4) NOT NULL default '0',
#  `o_salarymin` double default NULL,
#  `o_salarymax` double default NULL,
  `o_salaryother` varchar(100) default NULL,
  `partnership` tinyint(1) NOT NULL default '0',
  `partner_other` varchar(50) default NULL,
  `buy_in` tinyint(1) NOT NULL default '0',
  `bonus_sign` tinyint(1) NOT NULL default '0',
  `bonus_prod` tinyint(1) NOT NULL default '0',
  `relocation` tinyint(1) NOT NULL default '0',
  `vacation_wks` varchar(50) default NULL,
  `benefits` varchar(100) default NULL,
  `malpractice` varchar(50) default NULL,
#  `consider_j1` tinyint(1) NOT NULL default '1',
#  `consider_amg` tinyint(1) NOT NULL default '1',	// actually, it is "consider_img"
#  `residents` tinyint(1) NOT NULL default '1',
#  `practicing` tinyint(1) NOT NULL default '1',
  `notifications` tinyint(1) NOT NULL default '0',
  `o_email` varchar(128) NOT NULL default '',
  `o_datemod` timestamp(14) NOT NULL,
  `o_usermod` int(10) unsigned NOT NULL default '0',
  `o_contact` varchar(60) default NULL,
  `o_phone` varchar(20) default NULL,
  `o_title` varchar(80) default NULL,
  `o_fax` varchar(16) default NULL,
  `exp_date` date default NULL,
 */
class Opportunity extends PCLocation {
    function __construct($conn, $oppid = 0, $locid = 0, $peekuid = 0, $peekact = 0) {
		global $UUID; global $USER;
		// if oppid = 0, creates new opp based on location #locid
		// else, loads both opp and picture part of associated loc
		if ( !$conn ) throw new Exception('Valid connection required',__LINE__);
		$this->cdb = $conn;
		if( !$oppid || !is_numeric($oppid) ) {
			if ( !$locid || !is_numeric($locid) ) throw new Exception('Valid location id required',__LINE__);
			$result = $conn->query("SELECT `l_id`, `l_uid`, `l_acct`, `l_facility`, `l_city`, `l_state`, `l_zip`, `l_commu2`, `l_underserved`, `exp_date` FROM `locations` WHERE l_id = $locid");
			if ( !$result || $result->num_rows == 0 ) throw new Exception('Unknown location',__LINE__);
			$this->obj = $result->fetch_object();
			$result->free();
			// now let's create the thing
			if( !$peekuid ) $peekuid = $UUID;
			if( !$peekact ) $peekact = $USER->acct;
			if( $peekuid != $this->obj->l_uid && $peekact != $this->obj->l_acct )  throw new Exception('You can not peek onto this location',__LINE__);
			$this->obj->o_uid = $peekuid;
			$this->obj->o_lid = $this->obj->l_id;
			$this->obj->o_facility = addslashes($this->obj->l_facility);
			$this->obj->o_city = addslashes($this->obj->l_city);
			$this->obj->o_state = $this->obj->l_state;
			$this->obj->o_zip = $this->obj->l_zip;
			$this->obj->o_commu2 = $this->obj->l_commu2;
			//$this->obj->o_amen = $this->obj->l_amen;
			//$this->obj->o_school = $this->obj->l_school;
			$this->obj->o_underserved = $this->obj->l_underserved;
			$this->obj->o_usermod = $UUID;
			$this->obj->o_email = addslashes($USER->email);
			$this->obj->o_phone = addslashes($USER->phone);
			$this->obj->o_fax = ereg_replace('[^0-9]','',$USER->fax);
			$this->obj->o_title = addslashes($USER->title);
			$this->obj->o_contact = addslashes($USER->firstname." ".$USER->lastname);
			$this->obj->o_acct = $peekact;
			$sql = "insert into opportunities (o_uid,o_lid,o_facility,o_city,o_state,o_zip, o_commu2, o_underserved,o_email, o_usermod,o_contact, o_phone,o_title,o_fax,exp_date,o_acct) values (".
				$this->obj->o_uid.','.$this->obj->l_id.','.chknul($this->obj->o_facility).','.chknul($this->obj->o_city).
				",'".$this->obj->l_state."',".chknul($this->obj->l_zip).','.chknul($this->obj->l_commu2).
				','.$this->obj->l_underserved.",'".$this->obj->o_email."',$UUID,'".$this->obj->o_contact."','".$this->obj->o_phone.
				"',".chknul($this->obj->o_title).','.chknul($this->obj->o_fax).','.chknul($this->obj->exp_date).','.$this->obj->o_acct.')';
			$result = $conn->query($sql);
			if( !$result ) throw new Exception(DEBUG?"$conn->error: $sql":'Can not insert new record',__LINE__);
			$oppid = $conn->insert_id;
		}
		if( $oppid && is_numeric($oppid) ) {
			$result = $conn->query("SELECT opportunities.*, l_id, l_description, l_commdescr, l_pic0type, l_pic1type, l_pic2type, l_pic3type, l_pic4type, l_pic0size, `l_pic1size`, `l_pic2size`, `l_pic3size`, `l_pic4size` FROM `opportunities` join `locations` on o_lid = l_id WHERE oid = $oppid");
			if ( !$result || $result->num_rows == 0 ) throw new Exception('Opportunity does not exist',__LINE__);
			$this->obj = $result->fetch_object();
			$result->free();
		}
	}
	public function __get($member) {
		//loads pics on demand
		switch($member) {
			case 'id':			return $this->obj->oid;
			case 'o_datemod':	return formatDateMod($this->obj->o_datemod);
			case 'l_pic0':		return $this->getpic(0);
			case 'l_pic1':		return $this->getpic(1);
			case 'l_pic2':		return $this->getpic(2);
			case 'l_pic3':		return $this->getpic(3);
			case 'l_pic4':		return $this->getpic(4);
			case 'o_fax':		return formatphone($this->obj->o_fax);
			default: try { 		return is_string($this->obj->$member)?stripslashes($this->obj->$member):$this->obj->$member;
				} catch(Exception $e) {
					if( DEBUG ) throw $e; return false;
				}
		}
	}
	function __set($member,$value) {
		// pictures are not supposed to be updated here
		if( $member == 'id' || $member == 'l_id' || $member == 'oid' ) return false;
		elseif( substr($member,0,5) == 'l_pic' ) return false;
		//elseif( $member == 'o_phone' ) $this->obj->o_phone = ereg_replace('[^0-9]','',$value); // allow EXT# 12345
		elseif( $member == 'o_fax' ) $this->obj->o_fax = ereg_replace('[^0-9]','',$value);
		/*elseif( $member == 'o_salarymin' || $member == 'o_salarymax' ) {
		   // check for numbers only, but replace K with 000
		   $this->obj->$member = preg_replace(array('/k/i','/m/i','/[^\d.KkMm]/'),array('000','000000',''), $value);
		}*/
		else try {
			$this->obj->$member = is_string($value)? addslashes($value): $value;
		}
		catch(Exception $e) {
			if( DEBUG ) throw $e;
			return false;
		}
		return $value;
	}
	function ratecalc() {
			$strat = 0;
			$rest = $this->cdb->query("select pref from border_states where st = '".$this->obj->o_state."'");
			if( $rest->num_rows ) {
				list($strat) = $rest->fetch_row();
			}
			$rest->free();
			$qual = '';
			if( stripos($this->obj->practice_type,'MSG')!==false ) $qual .= ($qual?',':'').'MSG';
			if( stripos($this->obj->practice_type,'Solo')!==false ) $qual .= ($qual?',':'').'Solo';
			if( stripos($this->obj->practice_type,'Hosp')!==false ) $qual .= ($qual?',':'').'Hosp';
			if( stripos($this->obj->practice_type,'Acad')!==false ) $qual .= ($qual?',':'').'Acad';
			if( stripos($this->obj->practice_type,'SSG')!==false ) $qual .= ($qual?',':'').'SSG';
			if( stripos($this->obj->practice_type,'Locum')!==false ) $qual .= ($qual?',':'').'Locum';
			if( stripos($this->obj->practice_type,'Pub')!==false ) $qual .= ($qual?',':'').'Pub';
			if( stripos($this->obj->practice_type,'Rural')!==false ) $qual .= ($qual?',':'').'Rural';
			if( stripos($this->obj->practice_type,'ER')!==false ) $qual .= ($qual?',':'').'ER';
			// 'ER','Rural','Pub','Locum','SSG','Acad','Hosp','Solo','MSG'
			$sql = "update ratingop set ro_spec='".$this->obj->specialty."', rostate='".$this->obj->o_state.
				"', rocomm=".chknul($this->obj->o_commu2).", roqualif=".chknul($qual).", rostrat=$strat where roid = ".$this->obj->oid;
			$result = $this->cdb->query($sql);
			if( !$result ) throw new Exception(DEBUG?"{$this->cdb->error}: $sql":'Can not update aux record',__LINE__);
	}
	function save() {
		// does not save location parts, oid, o_uid, o_lid.
		global $ACCESS, $UUID, $USER;
		$expt = strtotime($this->obj->exp_date);
		if( !$ACCESS && ($expt === FALSE || $expt > strtotime($USER->exp_date)) ) $this->obj->exp_date = $USER->exp_date;
		$sql = "update opportunities set `o_facility`=".chknul($this->obj->o_facility).", `o_city`=".chknul($this->obj->o_city).
			", `o_state`='".$this->obj->o_state."', `o_zip`=".chknul($this->obj->o_zip).
			", `practice_type`=".chknul($this->obj->practice_type).", `status`=".$this->obj->status.
			", `o_commu2`=".chknul($this->obj->o_commu2).", `o_underserved`=".$this->obj->o_underserved.
			", `description`=".chknul($this->obj->description).", `o_name`=".chknul($this->obj->o_name).
			", `show_state`=".$this->obj->show_state.", `specialty`='".$this->obj->specialty.
			"', `avail_date`=".chknul($this->obj->avail_date).", `o_salaryother`=".chknul($this->obj->o_salaryother).
			", `partnership`=".$this->obj->partnership.", `partner_other`=".chknul($this->obj->partner_other).
			", `buy_in`=".$this->obj->buy_in.", `bonus_sign`=".$this->obj->bonus_sign.
			", `bonus_prod`=".$this->obj->bonus_prod.", `relocation`=".$this->obj->relocation.
			", `vacation_wks`=".chknul($this->obj->vacation_wks).", `benefits`=".chknul($this->obj->benefits).
			", `malpractice`=".chknul($this->obj->malpractice).", `notifications`=".$this->obj->notifications.
			", `o_email`=".chknul($this->obj->o_email).", `o_contact`=".chknul($this->obj->o_contact).
			", `o_phone`=".chknul($this->obj->o_phone).", `o_title`=".chknul($this->obj->o_title).
			", `o_fax`=".chknul($this->obj->o_fax).", `exp_date`=".chknul($this->obj->exp_date).
			", `o_lid`=".$this->obj->o_lid.
			", `o_usermod`= $UUID where oid = ".$this->obj->oid;
		$result = $this->cdb->query($sql);
		if( !$result ) throw new Exception(DEBUG?"{$this->cdb->error}: $sql":'Can not update record',__LINE__);
		// now do ratingop
		if( $this->obj->status == 1 ) {
			$res1 = $this->cdb->query("select * from ratings where r_oid = ".$this->obj->oid." LIMIT 0,1");
			if( $res1->num_rows == 0 ) {
				$this->cdb->query("insert ratingop (roid,ro_spec) values(".$this->obj->oid.",'".$this->obj->specialty."') ON DUPLICATE KEY UPDATE ro_spec='".$this->obj->specialty."'");
				// no error check
				$this->cdb->query("insert ratings select rdid,".$this->obj->oid.", 0 from ratingdoc where rd_spec = '".$this->obj->specialty."'");
			}
			$res1->free();
			$this->ratecalc();
			$this->rerate();
		} // status == 1
		else $this->specswap(); // delete ratings for all other statuses
	}
	public function specswap() {
		$this->cdb->query("delete from ratings where r_oid = ".$this->obj->oid);
		$this->cdb->query("replace ratingop (roid,ro_spec) values(".$this->obj->oid.",'".$this->obj->specialty."')");
	}
	function deactivate() {
		// already set by save()
		//global $UUID;
		//$this->obj->status = 0;
		//$sql = "update opportunities set `status`=0, `o_usermod`= $UUID where oid = ".$this->obj->oid;
		//$result = $this->cdb->query($sql);
		//if( !$result ) throw new Exception(DEBUG?"{$this->cdb->error}: $sql":'Can not deactivate opportunity',__LINE__);
		$this->cdb->query("delete from ratings where r_oid = ".$this->obj->oid);
		$this->cdb->query("delete from ratingop where roid = ".$this->obj->oid);
		// pending applications: mark all as read
		$sql = "update applications set `status`=0 where opid = ".$this->obj->oid;
		$result = $this->cdb->query($sql);
	}
	function reactivate($opps = false) { // opps = true sets status in the DB
		global $UUID,$ACCESS,$USER;
		if( $opps ) {
			$this->obj->status = 1;
			$expd = $ACCESS? '': ",exp_date = '$USER->exp_date' ";
			$sql = "update opportunities set `status`=1, `o_usermod`= $UUID $expd where oid = ".$this->obj->oid;
			$result = $this->cdb->query($sql);
			if( !$result ) throw new Exception(DEBUG?"{$this->cdb->error}: $sql":'Can not reactivate opportunity',__LINE__);
		}
		$this->cdb->query("replace ratingop (roid,ro_spec) values(".$this->obj->oid.",'".$this->obj->specialty."')");
		$this->cdb->query("insert ratings select rdid,".$this->obj->oid.", 0 from ratingdoc where rd_spec = '".$this->obj->specialty."'");
		$this->ratecalc();
		$this->rerate();
	}
	function apply_to($phid = 0) { // see PC version
		return false;
	}
	function rerate() {
		// that's the one. 2700 is the max
		// new: rostrat (+-20 adj per state), rdpref, rdopen
		$sql = "update ratings,ratingop,ratingdoc set rating = 
		IFNULL(BIT_COUNT(ro1 & rd1)+BIT_COUNT(ro2 & rd2)+BIT_COUNT(ro3 & rd3)+BIT_COUNT(ro4 & rd4)+BIT_COUNT(ro5 & rd5)+BIT_COUNT(ro6 & rd6),0)*23*(1.-rostrat/100.)+
		IFNULL(BIT_COUNT(rocomm & rdcomm),0)*225*(1.-rostrat/100.)+
		IFNULL(BIT_COUNT(roqualif & rdqualif),0)*25*(1.-rostrat/100.)+
		IFNULL(BIT_COUNT(rostate & rdstate)+rdopen,0)*338*(1.+rostrat/100.)+
		IFNULL(BIT_COUNT(rostate & rdpref),0)*675*(1.+rostrat/100.)
		 where r_ph_id=rdid and ro_spec = rd_spec and r_oid = roid and r_oid=".$this->obj->oid;
		$result = $this->cdb->query($sql);
		if( !$result ) throw new Exception(DEBUG?"{$this->cdb->error}: $sql":'Can not rate the opportunity',__LINE__);

		return false;
	}
}

/* ************************************************************************
 * PCApplication
 **************************************************************************
  `opid` int(10) unsigned NOT NULL default '0',
  `phid` int(10) unsigned NOT NULL default '0',
  `stamp` timestamp(14) NOT NULL,
  `status` tinyint(4) NOT NULL default '1', // 4 is cancel/revoke, 1 unread, 0 read, 2 pending/saved or?
  `app_date` datetime default NULL,			// by physician
  `read_date` datetime default NULL,		// by client
  `notes` varchar(255) default NULL,		// by client
  `ph_msg` varchar(255) default NULL,		// by physician
  PRIMARY KEY  (`opid`,`phid`)				// 1 doc per opp
 */
class PCApplication {
    protected $obj;	// db object
	protected $cdb; // connection cache
    function __construct($conn, $opid, $phid) {
		if ( !$conn ) throw new Exception('Valid connection required',__LINE__);
		$this->cdb = $conn;
		if( $opid && is_numeric($opid) && $phid && is_numeric($phid) ) {
			$result = $conn->query("select applications.*, `lname`,`fname` from applications join physicians on phid = ph_id where opid = $opid and phid = $phid");
			if ( !$result || $result->num_rows == 0 ) throw new Exception('Response does not exist',__LINE__);
			$this->obj = $result->fetch_object();
			$result->free();
		}
		else throw new Exception('Valid opportunity and physician id required',__LINE__);
	}
	public function __get($member) {
		//loads pics on demand
		switch($member) {
			case 'stamp':		return formatDateMod($this->obj->stamp);
			default: try { 		return is_string($this->obj->$member)?stripslashes($this->obj->$member):$this->obj->$member;
				} catch(Exception $e) {
					if( DEBUG ) throw $e; return false;
				}
		}
	}
	function __set($member,$value) {
		if( $member == 'phid' || $member == 'opid' || $member == 'stamp' ) return false;
		else try {
			$this->obj->$member = is_string($value)? addslashes($value) : $value;
		}
		catch(Exception $e) {
			if( DEBUG ) throw $e;
			return false;
		}
		return $value;
	}
	function save() {
		// does not save app_date and ph_msg, see PC version for that
		$sql = "update applications set `read_date`=".chknul($this->obj->read_date).", `notes`=".chknul($this->obj->notes).
			", `status`=".$this->obj->status." where opid = ".$this->obj->opid." and phid = ".$this->obj->phid;
		$result = $this->cdb->query($sql);
		if( !$result ) throw new Exception(DEBUG?"{$this->cdb->error}: $sql":'Can not update response',__LINE__);
	}
	function mark_read() {
		if( $this->obj->status == 4 ) return false; // should never happen but who knows
		$this->obj->status = 0; $this->obj->read_date = date('Y-m-d H:i:s');
		$sql = "update applications set `status`=0, `read_date`=now() where opid = ".$this->obj->opid." and phid = ".$this->obj->phid;
		$result = $this->cdb->query($sql);
		if( !$result ) throw new Exception(DEBUG?"{$this->cdb->error}: $sql":'Can not mark response as read',__LINE__);
	}
}
?>