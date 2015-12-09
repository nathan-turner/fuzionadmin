<?php
    require("globals.php5");
	//define(PG_SIZE,50);
    //require("cookies.php5");
	//
	// NO $UUID HERE, NO SESSION IS ACTIVE
	//
	// param: r = random, k = key md5(random+salt), t=type (1: daily client, 2: weekly client, 3: 3day phys, 
	//		4: 4 day phys, 5: monday only/daily client, 7: weekly phys)
	// 		(m.b. to merge 3/4, program here rather than there? dunno)
	//
	// client subscription & 64 = 0 daily, 64 weekly
	// client subscription & 128 = 0 all, 128 limit to their specialties
	// phys notifications = 1 3/4-day, 2 weekly
	$rnd = $_REQUEST["r"];
	$key = $_REQUEST["k"];
	$typ = $_REQUEST["t"];
	$mesg = 'Access Denied';
	if( $rnd && $typ && is_numeric($typ) && md5($rnd . $typ . "atum batum shumbashu chinchbiri atumbu\n") === $key ) try {
		// do stuff
		$db = db_career();
		// expire old records
		$sql = 'update clients set status=0 where status=1 and exp_date < curdate()';
		$result = $db->query($sql); // ignore result for now
		$sql = 'delete ratings from ratings, opportunities where r_oid = oid and status=1 and exp_date < curdate()';
		$result = $db->query($sql); // ignore result for now
		$sql = 'delete ratingop from ratingop, opportunities where roid = oid and status=1 and exp_date < curdate()';
		$result = $db->query($sql); // ignore result for now
		$sql = 'update applications,opportunities set applications.status=0 where opid=oid and opportunities.status=1 and exp_date < curdate()';
		$result = $db->query($sql); // ignore result for now
		$sql = 'update opportunities set status=8 where status=1 and exp_date < curdate()';
		$result = $db->query($sql); // ignore result for now
		$sql = 'update locations set status=2 where status=1 and exp_date < curdate()';
		$result = $db->query($sql); // ignore result for now
		$sql = 'update physicians,pendings set status=0 where ph_id=phid and status=1 and pending=1 and datediff(curdate(),pdate) > 30';
		$result = $db->query($sql); // ignore result for now
		$sql = 'delete from pendings where datediff(curdate(),pdate) > 30';
		$result = $db->query($sql); // ignore result for now

		$Regions = array();
		$result = $db->query('select reg_id, reg_name from regions');
		if( $result && $result->num_rows )  {
			while( list($code,$name) = $result->fetch_row() ) {
				$Regions[$code] = $name;
			}
			$result->free(); unset($result);
		}
		
		$interval = 7; $subq = 'subscription&64=64'; // typ == 2 or 0
		switch($typ) {
			case 1:	$interval = 1; $subq = 'subscription&64=0';
					break;
			case 3: $interval = 3; $subq = 'notifications=1';
					break;
			case 4: $interval = 4; $subq = 'notifications=1';
					break;
			case 5: $interval = 3; $subq = 'subscription&64=0';
					break;
			case 7: $subq = 'notifications=2';
					break;
			case 8:	$interval = 1; $typ = 1; $subq = '1=0'; // false
					break;
			case 9:	$typ = 2; $subq = '1=0'; // false
					break;
		}
		if( $typ <= 2 || $typ == 5 ) { // client
			// *** HMA HACK START ******************
			
			
			
			// - deletes files over 4 weeks old
			$sql = "SELECT filename from exportrac where stamp < date_add(curdate(), INTERVAL -1 month)";
			$result = $db->query($sql);
			if( $result && $result->num_rows ) {
				while( list($fn0) = $result->fetch_row() ) {
					unlink("$HMA_DIR/$fn0");
				}
			}
			$result->free();
			$sql = "DELETE from exportrac where stamp < date_add(curdate(), INTERVAL -1 month)";
			$result = $db->query($sql);
			// - creates export file for hma in ../hma directory, $HMA_DIR
			$fn0 = date("Ymd")."-$interval.csv";
			$csv = fopen("$HMA_DIR/$fn0","w");
			if( $csv ) {
				$sql = "SELECT fname,midname,lname,mddo,addr1,addr2,city,state, zip,homephone,cellphone, email,spec,sp_name,avail_date, visa_status,case `citizen` when 1 then 'US citizen' when 2 then 'perm.res.' end as us_citizen, bcbe, pref_region,languages,contact_pref
	 FROM physicians JOIN specialties ON spec = sp_code WHERE (reg_date BETWEEN date_add(curdate() , INTERVAL -$interval day) AND now() OR last_save BETWEEN date_add(curdate() , INTERVAL -$interval day) AND now()) AND checkin=1 AND pending=0 AND physicians.status=1 and inactive=0 ORDER BY sp_name, fname, lname"; // spec or sp_name? both
				$res = $db->query($sql);
				if( $res && $res->num_rows ) {
					// that would be too easy: 
					while( $row = $res->fetch_row() ) fputcsv($csv,$row);
					/*
					for( $i=0; $i < $res->num_rows; $i++ ) {
						// export
						$row = $res->fetch_row();
						$firstElem = true;
						foreach( $row as $value ) {
							if( $firstElem ) $firstElem = false;
							else fwrite($csv,',');
							$value = str_replace("\r\n"," ",$value);
							//if( $key == "zip" || $key == "ofzip" ) echo "\"'".stripslashes($value).'"';
							//else 
								fwrite($csv, '"'.stripslashes($value).'"');
						}
						fwrite($csv, "\r\n"); // windows style is always ok
					}
					*/
				} // res
				$res->free();
				fclose($csv);
			} // csv
			$sql = "INSERT into exportrac(filename) VALUES ('$fn0')";
			$result = $db->query($sql);
			// *** HMA HACK END **************************
			//exit;
			
			$sql = "SELECT ph_id, case when checkin=1 then fname else concat(left(fname,1),'.') end as fna, case when checkin=1 then lname else concat(left(lname,1),'.') end as lna, mddo, avail_date, spec, sp_name, pref_stopen, pref_states, pref_region, pref_commu2, as_new FROM physicians JOIN specialties ON spec = sp_code WHERE last_save BETWEEN date_add(curdate() , INTERVAL -$interval day) AND now() AND pending=0 AND physicians.status=1 and inactive=0 and as_new != 2 ORDER BY as_new desc, sp_name, fname, lname";
			$resBase = $db->query($sql);
			
			$sql = "select uid,acct,firstname,lastname,email,master_acct,subscription from clients where status = 1 and $subq";
			$result = $db->query($sql);
			if( $result && $result->num_rows ) {
				$numclients = $result->num_rows;
				header('Content-type: text/plain');
				echo "#%CLIENTUPDATE#$numclients##\n";
				for( $cli=0; $cli < $numclients; $cli++ ) {
					$client = $result->fetch_object();
					$byspec = $client->subscription & 128? 1: 0; // limit by spec
					if ( $byspec ) {
						$qq = $client->master_acct? "o_acct = $client->acct": "o_uid = $client->uid";
						$sql = "SELECT ph_id, case when checkin=1 then fname else concat(left(fname,1),'.') end as fna, case when checkin=1 then lname else concat(left(lname,1),'.') end as lna, mddo, avail_date, spec, sp_name, pref_stopen, pref_states, pref_region, pref_commu2, as_new FROM physicians JOIN specialties ON spec = sp_code WHERE spec IN (SELECT DISTINCT specialty FROM opportunities WHERE $qq AND specialty != '' AND opportunities.status=1 AND opportunities.notifications=1) AND last_save BETWEEN date_add(curdate() , INTERVAL -$interval day) AND now() AND pending=0 AND physicians.status=1 and inactive=0 and as_new != 2 ORDER BY as_new desc, sp_name, fname, lname";
						$res = $db->query($sql);
					}
					else $res = $resBase;
					$clienthead = 0; $upd0 = 2;
					if( $res && $res->num_rows ) {
						echo "##*$client->firstname#$client->lastname*$client->email($byspec)##$client->uid\n";
						$clienthead = 1;
						while( $doc = $res->fetch_object() ) {
							if( $doc->as_new != $upd0 ) {
								$upd0 = $doc->as_new; // 1 = new, 0 = not new
								echo "#*#$upd0\n";
							}
							$geopref = '';
							if( $doc->pref_stopen ) $geopref = 'Open'; 
							else for( $i = 0; $doc->pref_region && $i < strlen($doc->pref_region); $i+=2 )
								$geopref .= ($i?', ':'').$Regions[$doc->pref_region{$i}];
							$prstates = $doc->pref_states;
							if( strlen($prstates) > 45 ) $prstates = implode(', ',explode(',',$prstates));
							if( !$geopref ) $geopref=$prstates?$prstates:'N/A';
							echo "$doc->ph_id;$doc->fna;$doc->lna;$doc->mddo;$doc->avail_date;$doc->spec;$doc->sp_name;$doc->pref_commu2;$geopref\n";
						}
						if( $byspec ) $res->free();
						else $resBase->data_seek(0);
					} // res
					if( $clienthead ) echo "*##$client->uid\n";
				} // for
				// eof, no marker
				$result->free();
				exit;
			} else throw new Exception("No customers with notification interval $interval",__LINE__);
			$sql = "UPDATE physicians set as_new = 0 WHERE last_save BETWEEN date_add(curdate() , INTERVAL -$interval day) AND now() AND pending=0 AND status=1 and inactive=0 and as_new>0";
			$db->query($sql); // ignore result
		}
		else { // $typ; physician
			$sql = "SELECT DISTINCT specialty, sp_name FROM opportunities join specialties on specialty=sp_code WHERE specialty != '' AND opportunities.status=1 AND notifications=1 AND o_datemod between date_add(curdate(),interval -$interval day) and now() and o_acct<>18805";
			$result = $db->query($sql);
			if( $result && $result->num_rows ) {
				header('Content-type: text/plain; charset=UTF-8');
				echo "#%PHYSUPDATE#$result->num_rows##\n";
				for( $i=0; $i < $result->num_rows; $i++ ) {
					$spec = $result->fetch_row(); // $spec[0]
					$sql = "select ph_id,fname,lname,email from physicians where status=1 and inactive=0 and $subq and email_bounces=0 and spec='$spec[0]'";
					//echo $sql."\n"; // ***OOO
					$res = $db->query($sql);
					if( $res && $res->num_rows ) {
						$sql = "select oid,o_facility,o_city,o_state,show_state,description from opportunities where specialty='$spec[0]' and status=1 and o_datemod between date_add(curdate(),interval -$interval day) and now() and o_acct<>18805";
						//echo $sql."\n"; // ***OOO
						$opps = $db->query($sql);
						if( $opps && $opps->num_rows ) {
							echo "##*$spec[1]##\n";
							while( $opp = $opps->fetch_object() ) {
								$descr = str_replace("\n"," ",str_replace("\r","",html_entity_decode(strip_tags($opp->description))));
								$descr = str_replace("  "," ",str_replace("  "," ",str_replace("\t","",$descr)));
								$descr = substr(trim($descr),0,200).'...';
								/*$descr = htmlspecialchars($descr,ENT_COMPAT | ENT_HTML5,'UTF-8');*/ 
//								$fac = iconv("UTF-8", "us-ascii//TRANSLIT", strip_tags($opp->o_facility));
								$fac = strip_tags(html_entity_decode($opp->o_facility,ENT_COMPAT | ENT_HTML5,'UTF-8'));
//								$fac = htmlspecialchars(strip_tags($opp->o_facility),ENT_COMPAT | ENT_HTML5,'UTF-8');
								$city = iconv("UTF-8", "us-ascii//TRANSLIT", $opp->o_city);
								$state = $opp->show_state? $opp->o_state: 'USA';
								echo "$opp->oid;#$fac*$city*$state*;#$descr\n";
							}
							echo "#*#$spec[0]##\n";
							while( $doc = $res->fetch_object() ) {
								echo "$doc->ph_id#;$doc->fname;$doc->lname;$doc->email\n";
							}
							echo "*##$spec[0]\n";
							$opps->free();
						}
						$res->free();
					}
				}
				// end, no marker
				$result->free();
				exit;
			} else throw new Exception("No active specialties were modified within $interval days",__LINE__);
		} // typ
		$mesg = 'Success';
	}
	catch(Exception $e) {
		$mesg = "Request failed: ".$e->getMessage().' ('.$e->getCode().')<br>';
	}
	$style = new OperPage('Data Export',0,'reports','export');
	$style->Output();

?>
              <h1>Data Export Error</h1>
<?php 
		if( $mesg ) echo "<p id='error_msg'>$mesg</p>";
		$style->ShowFooter();
?>
