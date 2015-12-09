<?php	
	   require("globals.php5");
    require("cookies.php5");
	$style = new OperPage('Links',$UUID,'links','');
	$style->Output();
?>
<h1>Links</h1><p>Some useful links (will open in new window)</p>
<ul>
<li style="padding-bottom: 0.5cm"><a href="http://physiciancareer.com/" target="_blank"><b>Physician Career</b></a> main site</li>
<li><a href="http://www.m-w.com/" target="_blank"><b>Merriam-Webster's</b> Collegiate&reg; Dictionary</a></li>
<li><a href="http://www.medterms.com/" target="_blank"><b>MedTerms.com</b> Medical Dictionary</a></li>
<li><a href="http://decoder.americom.com/" target="_blank"><b>AmeriCom</b> Long Distance Area Decoder</a></li>
<li><a href="http://www.superpages.com/" target="_blank"><b>SuperPages</b> - Yellow &amp; White Pages, and more
</a></li>
<li><a href="http://www.switchboard.com/" target="_blank"><b>Switchboard</b> - Yellow &amp; White Pages, and more
</a></li>
<li><a href="http://www.anywho.com/" target="_blank"><b>AnyWho</b> - White and Yellow Pages Online, and more</a></li>
<li><a href="http://www.usps.gov/" target="_blank"><b>USPS</b> - The United States Postal Service</a></li>
<li><a href="http://www.askdrwalker.com/index/medboards.htm" target="_blank"><b>AskDrWalker</b> List of State Medical Boards</a></li>
<li><a href="http://www.ama-assn.org/aps/amahg.htm" target="_blank"><b>AMA</b> Online Doctor Finder</a></li>

</ul>
     
<?php
    $style->ShowFooter();
?>