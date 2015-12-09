<?php
// visual style classes, etc.
// It is supposed to be included from $DOCUMENT_ROOT/globals.php5

$FOOTER = <<<FooterHere
1455 Lincoln Pkwy, Suite 350, Atlanta, GA 30346 <span 
	 class="bullets">&bull; </span> T: 800.789.6684 <span 
	 class="bullets">&bull; </span> F: 404.591.4198 <span 
	 class="bullets">&bull; </span> www.fuzionhg.com
FooterHere;
$KEYWORDS = <<<HereKeys
resident database, resident profile, recruitment, employment,
physician, medical, resident, health care, healthcare, in-house, hospital, facility
HereKeys;

function showLoginForm() {
	$self = $_SERVER['PHP_SELF'].($_SERVER['QUERY_STRING']?"?".$_SERVER['QUERY_STRING']:'');
	if( EMERGENCY )
		echo '<p id="error_msg">The Site is closed for Emergency Maintenance. Please try again later or contact administration.</p>';
	echo <<<HereDoc
    <h1>Log In</h1>
    <p class="nodescript">Use your assigned user name and password to login. If you forgot
	   your user name, please try your email address, or contact us.
	   If you can not remember your password,
	   you can reset it <a href="login.php">here</a>.
	   Click <a href="login.php">here</a> also if you have problems with login process.</p>
    <div id="formdiv"> 
    	<form name="form1" method="post" action="login.php">
        <p class="nodescript"> User name: <input name="username" type="text" maxlength="120" size="20">
		Password: <input name="password" type="password" id="password" maxlength="40" size="20">
		&nbsp;<input name="remember" type="checkbox" id="remember"
			   value="1" checked>&nbsp;Remember me on this computer&nbsp;
		<input type="submit" name="Submit" value="&nbsp;Log In&nbsp;">
		<input type="hidden" name="Referal" value="$self">
        </form>
    </div>
HereDoc;
}

// Site map and navigation bars generators for visitors and logged-in users.
// Can be extended for operators by creation of separate top and left arrays
class Sitemap {
	protected $top;		// top menu bar
	protected $left;	// left menu
	protected $usertop = array( 
		'index' =>		array( 'N' => 'Home',		'L' => 'index.php' ),
		'about' =>		array( 'N' => 'About Us', 	'L' => 'about.php' ),
		'products' =>	array( 'N' => 'Products', 	'L' => 'products.php' ),
		'contact' =>	array( 'N' => 'Contact Us', 'L' => 'contact.php' ),
		'residents' =>	array( 'N' => 'Search', 	'L' => 'ressearch.php' ),
		'jobboard' =>	array( 'N' => 'Job Board', 	'L' => 'locations.php' ),
		'logout' =>		array( 'N' => 'Log Out', 	'L' => 'logout.php' )
	);
	protected $userleft = array( 
		'index' => array(
			'account' => array( 'N' => 'Your Account', 'L' => 'account.php' ),
			'subacc' => array( 'N' => 'Sub-accounts', 'L' => 'subacc.php' ) ),
		'about' => array(
			'aboutus' => array( 'N' => 'Our Story', 'L' => 'about.php' ),
			'team' => array( 'N' => 'Our Team', 'L' => 'team.php' ),
			'references' => array( 'N' => 'References', 'L' => 'references.php' ),
			//'quality' => array( 'N' => 'Quality Policy', 'L' => 'quality.php' ),
			'tc' => array( 'N' => 'Our Policies', 'L' => 'TC.php' ) ),
		'products' => array( 
			'resdata' => array ( 'N' => 'Database',	'L' => 'resdata.php' ), 
			'outsourcing' => array( 'N' => 'Calling Services', 'L' => 'outsourcing.php' ),
			'proptions' => array( 'N' => 'Product Options', 'L' => 'proptions.php' ),
			'references' => array( 'N' => 'References', 'L' => 'references.php' ),
			'faq' => array( 'N' => 'F.A.Q.', 'L' => 'faq.php' ) ),
		'contact' => array(
			'faq' => array( 'N' => 'F.A.Q.', 'L' => 'faq.php' ),
			'demoreq' => array( 'N' => 'Request Demo', 'L' => 'demoreq.php' ),
			'renewal' => array( 'N' => 'Renewals', 'L' => 'renewal.php' ),
			'jobs' => array( 'N' => 'Job at Fuzion', 'L' => 'jobs.php' ) ),
		'residents' => array(
			'ressearch' => array( 'N' => 'Search', 'L' => 'ressearch.php' ),
			'custlists' => array( 'N' => 'Custom Lists', 'L' => 'custlists.php' ),
			'resemail' => array( 'N' => 'Email', 'L' => 'resemail.php' ),
			'export' => array( 'N' => 'Export', 'L' => 'export.php' ) ),
		'jobboard' => array(
			'locations' =>	array( 'N' => 'Locations', 	'L' => 'locations.php' ),
			'opportunities' => array( 'N' => 'Opportunities', 'L' => 'opportunities.php' ),
			'candidates' => array( 'N' => 'Candidates', 'L' => 'ressearch.php' ),
			'applications' => array( 'N' => 'Responses', 'L' => 'applications.php' ),
			'reassign' => array( 'N' => 'Reassign', 'L' => 'reassign.php' )	 )
	);
	protected $visitortop = array(
		'index' =>		array( 'N' => 'Home', 		'L' => 'index.php' ),
		'about' =>		array( 'N' => 'About Us', 	'L' => 'about.php' ),
		'products' =>	array( 'N' => 'Products', 	'L' => 'products.php' ),
		'news' =>		array( 'N' => 'News', 		'L' => 'news.php' ),
		'surveys' =>	array( 'N' => 'Surveys', 	'L' => 'surveys.php' ),
		'contact' =>	array( 'N' => 'Contact Us', 'L' => 'contact.php' )
	);
	protected $visitorleft = array(
		'index' => array(
			'login' => array( 'N' => 'Client Access', 'L' => 'login.php' ),
			'renewal' => array( 'N' => 'Renewals', 'L' => 'renewal.php' ),
			'demoreq' => array( 'N' => 'Request Demo', 'L' => 'demoreq.php' ) ),
		'about' => array(
			'aboutus' => array( 'N' => 'Our Story', 'L' => 'about.php' ),
			'team' => array( 'N' => 'Our Team', 'L' => 'team.php' ),
			'references' => array( 'N' => 'References',	'L' => 'references.php' ),
			//'quality' => array( 'N' => 'Quality Policy', 'L' => 'quality.php' ),
			'tc' => array( 'N' => 'Our Policies', 'L' => 'TC.php' ) ),
		'products' => array( 
			'resdata' => array ( 'N' => 'Database',	'L' => 'resdata.php' ), 
			'outsourcing' => array( 'N' => 'Calling Services', 'L' => 'outsourcing.php' ),
			'proptions' => array( 'N' => 'Product Options', 'L' => 'proptions.php' ),
			'subscription' => array( 'N' => 'Special Offer', 'L' => 'physcareer300.php' ),
			'references' => array( 'N' => 'References', 'L' => 'references.php' ),
			'faq' => array( 'N' => 'F.A.Q.', 'L' => 'faq.php' ) ),
		'contact' => array(
			'faq' => array( 'N' => 'F.A.Q.', 'L' => 'faq.php' ),
			'demoreq' => array( 'N' => 'Request Demo', 'L' => 'demoreq.php' ),
			'renewal' => array( 'N' => 'Renewals', 'L' => 'renewal.php' ),
			'subscription' => array( 'N' => 'Special Offer', 'L' => 'physcareer300.php' ),
			'jobs' => array( 'N' => 'Job Openings', 'L' => 'jobs.php' ) )
	);
	public function __construct($user) {
		if( !empty($user) && $user ) {
			$this->top = &$this->usertop; $this->left = &$this->userleft;
		}
		else {
			$this->top = &$this->visitortop; $this->left = &$this->visitorleft;
		}
	}
	public function outtop($aitem) {
		$str = '<div id="topnavbar"><ul id="topnavlist">'."\n";
		foreach( $this->top as $key => $value ) {
			$str .= '<li><a class="topnav_'.($aitem === $key?'active':'item').'" href="'.$value['L'].
				'"><span class="topnav_'.($aitem === $key?'active':'').'text">'.$value['N'].
				"</span></a></li>\n";
		}
		$str .= "</ul></div>\n";
		return $str;
	}
	public function outleft($topitem,$aitem = NULL) {
		if( !isset($this->left[$topitem]) || empty($this->left[$topitem]) ) return '';
		$str = '<div id="leftnavbar"><ul class="leftnavlist">'."\n";
		foreach( $this->left[$topitem] as $key => $value ) {
			$str .= '<li><a class="leftnav_'.($aitem === $key?'active':'item').'" href="'.$value['L'].
				'"><span class="leftnav_'.($aitem === $key?'active':'').'text">'.$value['N'].
				"</span></a></li>\n";
		}
		$str .= "</ul></div>\n";
		return $str;
	}
	public function nosubacc() { // no sub-accounts menu
		unset($this->left['index']['subacc']);
		$this->left['jobboard']['reassign'] = array( 'N' => 'All Postings', 'L' => 'reassign.php' );
	}
}

// site map for operators site
class OperSitemap extends Sitemap {
	protected $opertop = array( 
		'index' =>		array( 'N' => 'Home',		'L' => 'index.php' ),
		'admin' =>		array( 'N' => 'Administration', 	'L' => 'admin.php' ),
		'reports' =>	array( 'N' => 'Reports', 	'L' => 'reports.php' ),
		'residents' =>	array( 'N' => 'Database', 	'L' => 'residents.php' ),
		'links' =>		array( 'N' => 'Other Sites', 	'L' => 'links.php' ),
		'logout' =>		array( 'N' => 'Log Out', 	'L' => 'logout.php' )
	);
	protected $operleft = array( 
		'index' => array(
			'oprofile' => array( 'N' => 'Your Profile', 'L' => 'oprofile.php' ),
			'custlists' => array( 'N' => 'Custom Lists', 'L' => 'custlists.php' ) ),
		'admin' => array(
			'custnew' => array( 'N' => 'New Customer', 'L' => 'custnew.php' ),
			'custsearch' => array( 'N' => 'Browse Customers', 'L' => 'custsearch.php' ),
			'custemail' => array( 'N' => 'Email Customers', 'L' => 'custemail.php' ),
			'editoper' => array( 'N' => 'Edit Employees', 'L' => 'editoper.php' ),
			'shadowass' => array( 'N' => 'Assign Lists', 'L' => 'shadowass.php' ),
			'editprog' => array( 'N' => 'Res. Programs', 'L' => 'editprog.php' ) ),
		'reports' => array(
			'residentstats' => array( 'N' => 'Physicians', 'L' => 'residentstats.php' ),
			'customerstats' => array( 'N' => 'Customers', 'L' => 'customerstats.php' ),
			'updatedrpt' => array( 'N' => 'Verification', 'L' => 'updatedrpt.php' ),
			'managerrpts' => array( 'N' => 'DB Manager', 'L' => 'managerrpts.php' ),
			'listsrpt' => array( 'N' => 'Call Lists', 'L' => 'listsrpt.php' ),
			'export' => array( 'N' => 'Data Export', 'L' => 'export.php' ) ),
		'residents' => array(
			'ressearch' => array( 'N' => 'Search', 'L' => 'ressearch.php' ),
			/*'shadow' => array( 'N' => 'Shadow Search', 'L' => 'shadow.php' ),*/
			'career' => array( 'N' => 'Physician Career', 'L' => 'pcsearch.php' ),
			'resnew' => array( 'N' => 'Add New Record', 'L' => 'resnew.php' ),
			'custlists1' => array( 'N' => 'Custom Lists', 'L' => 'custlists.php' ) )
	);
	protected $guesttop = array(
		'index' =>		array( 'N' => 'Home', 		'L' => 'http://fuzionhg.com/index.php' ),
		'products' =>	array( 'N' => 'Products', 	'L' => 'http://fuzionhg.com/products.php' ),
		'about' =>		array( 'N' => 'About Us', 	'L' => 'http://fuzionhg.com/about.php' ),
		'contact' =>	array( 'N' => 'Contact', 	'L' => 'http://fuzionhg.com/contact.php' ),
		'surveys' =>	array( 'N' => 'Surveys', 	'L' => 'http://fuzionhg.com/surveys.php' ),
		'news' =>		array( 'N' => 'News', 		'L' => 'http://fuzionhg.com/news.php' )
	);
	protected $guestleft = array(
		'index' => array(
			'login' => array( 'N' => 'Log in', 'L' => 'login.php' ) )
	);
	public function __construct($user) {
		if( !empty($user) && $user < 10000 ) {
			$this->top = &$this->opertop; $this->left = &$this->operleft;
		}
		else {
			$this->top = &$this->guesttop; $this->left = &$this->guestleft;
		}
	}
}
 
// basic user-logged-in page (secondary logo, two columns)
// usage:
//	$page = new BasicPage('Outsourcing',$UUID,'products','outsourcing')
//	$page->Output();
//	.... (main content here)
//	$page->ShowFooter();
//
class BasicPage {
	var $title = MYNAME;
	var $topbar = '';
	var $leftbar = '';
	var $desc = 'Fuzion Health Group provides services for health care facilities';
	var $redirect = '';
	var $slogan = 0; // 0 means "show slogan"
	protected $rowspan = 1;
	function __construct($titul,$user,$topitem,$leftitem,$redir = NULL,$descr = NULL) {
		global $MASTER;
		$site = new SiteMap($user);
		if( !$MASTER ) $site->nosubacc();
		if( !empty($titul) ) $this->title = "$titul - ".MYNAME;
		if( !empty($topitem) ) $this->topbar = $site->outtop($topitem); // empty only when TC is shown
		$this->leftbar = $site->outleft($topitem,$leftitem);
		if( !empty($descr) ) $this->desc = $descr;
		if( !empty($redir) ) $this->redirect = $redir;
		$this->slogan = $user;
	}
	///// FOOTER
	public function ShowFooter() {
		global $FOOTER;
		echo <<<HereFooter
    </div>
  </td>
  </tr></table>
  <div id="footer">
    <div id="footer_text">$FOOTER</div>
  </div>
</div>
</body>
</html>
HereFooter;
	}
	///// LOGO
	public function ShowLogo () {
		$site = MYNAME;
		$suffix = $this->slogan? 'secondary': 'secslog';
		echo <<<LogoHere
<body>
<div id="holding">
  <div id="logoform2" title="$site"><img src="images/header_$suffix.jpg" border="0" alt="$site" />
  </div>
LogoHere;
	}
	///// HEADER
	public function ShowHeader($headon = NULL) {
		global $KEYWORDS;
		$quirk = 'script>'; // DW does not like it
		$redir = '';
		$head = $headon?'':'</head>';
		if( !empty($this->redirect) ) $redir = '<meta http-equiv="refresh" content="'.$this->redirect.'">';
		echo <<<HereHeader
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>$this->title</title>
$redir<meta name="description" content="$this->desc" />
<meta name="author" content="Sergey Latkin" />
<meta name="keywords" content="$KEYWORDS" />
<link rel="SHORTCUT ICON" href="images/favicon.ico" />
<link href="fuzion.css" rel="stylesheet" type="text/css" media="screen" />
<link href="fuzionprint.css" rel="stylesheet" type="text/css" media="print" />
<script type="text/javascript" src="live_tinc.js"></$quirk
$head
HereHeader;
	}
	//// TABLE AND LEFT TD
	public function ShowLeft() {
		echo '<table border="0" id="maintab" cellpadding="0" cellspacing="0"><tr valign="top">';
		echo '<td class="leftcol"'.($this->rowspan===1?'':' rowspan="2"').'>';
		if( empty($this->leftbar) ) echo "&nbsp;\n";
		else echo "<br />\n$this->leftbar\n<br />";
		echo '</td>';
	}
	public function ShowRight($rcontent) {
		// does nothing in this class
		return;
	}
	public function ShowMain() {
		echo '<td background="images/circles_trans'.($this->rowspan===1?'1':'2nd').
			'.jpg" class="'.($this->rowspan===2?'mid':'main').
			'col"><div id="maincontent">'."\n";
	}
	//// All parts, except for footer
	public function Output($rcontent = NULL) {
		$this->ShowHeader();
		$this->ShowLogo();
		if( !empty($this->topbar) ) echo $this->topbar;
		$this->ShowLeft();
		$this->ShowRight($rcontent);
		$this->ShowMain();
	}
}

// three-column secondary
class TriplePage extends BasicPage {
	var $h2 = '';
	function __construct($titul,$user,$topmenu,$leftmenu,$redir = NULL, $descr = NULL) {
		parent::__construct($titul,$user,$topmenu,$leftmenu,$redir,$descr);
		$this->h2 = $titul;
		$this->rowspan = 2; 
	}
	public function ShowRight($rcontent) {
		echo <<<HereRight
  <td class="midcol"><div id="sec_logo">
   	  <h2>$this->h2</h2>
  </div></td><td rowspan="2" class="rightcol">
  	$rcontent
  </td></tr><tr valign="top">
HereRight;
	}
}

// main index
class IndexPage extends BasicPage {
	public function ShowLogo () {
		$site = MYNAME;
		$suffix = $this->slogan? 'main': 'mainslog';
		echo <<<LogoHere
<body>
<div id="holding">
  <div id="logoform" title="$site"><img src="images/header_$suffix.jpg" border="0" alt="$site" />
  </div>
LogoHere;
	}
}

// custom scripts and styles page
class ScriptPage extends BasicPage {
	public function Output($scrcontent = NULL) {
		$this->ShowHeader($scrcontent);
		if( !empty($scrcontent) ) echo "$scrcontent\n</head>\n";
		$this->ShowLogo();
		if( !empty($this->topbar) ) echo $this->topbar;
		$this->ShowLeft();
		$this->ShowRight(NULL);
		$this->ShowMain();
	}
}

// Operators Site Page
class OperPage extends ScriptPage {
	function __construct($titul,$user,$topitem,$leftitem,$redir = NULL,$descr = NULL) {
		$site = new OperSiteMap($user);
		if( !empty($titul) ) $this->title = "$titul - ".MYNAME;
		if( !empty($topitem) ) $this->topbar = $site->outtop($topitem); // empty only when TC is shown
		$this->leftbar = $site->outleft($topitem,$leftitem);
		if( !empty($descr) ) $this->desc = $descr;
		if( !empty($redir) ) $this->redirect = $redir;
		$this->slogan = 1;
	}
}

?>