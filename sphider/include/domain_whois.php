<?php

// the WHOIS class of Sphider-plus

    class whois {

        function lookup($url, $list) {

			// 	For the full list of TLDs/Whois servers see http://www.iana.org/domains/root/db/
			//	and http://www.whois365.com/en/listtld/

			$whoisservers = array (
				"ac" 		=> "whois.nic.ac", 			// Ascension Island
				"ac.uk"     => "whois.ja.net",
				"ad"	    => "ad whois.ripe.net",		// Andorra - no whois server assigned
				"ae" 	    => "whois.nic.ae", 			// United Arab Emirates
				"aero"	    =>"whois.aero",
				"af" 	    => "whois.nic.af", 			// Afghanistan
				"ag" 	    => "whois.nic.ag", 			// Antigua And Barbuda
				"ai" 	    => "whois.ai", 				// Anguilla
				"al" 	    => "whois.ripe.net", 		// Albania
				"am" 	    => "whois.amnic.net",  		// Armenia
								// an - Netherlands Antilles - no whois server assigned
								// ao - Angola - no whois server assigned
								// aq - Antarctica (New Zealand) - no whois server assigned
								// ar - Argentina - no whois server assigned
				"arpa"	    => "whois.iana.org",
				"as" 	    => "whois.nic.as", 			// American Samoa
				"asia" 	    => "whois.nic.asia",
				"at" 	    => "whois.nic.at", 			// Austria
				"au" 	    => "whois.aunic.net", 		// Australia
				"aw"	    => "aw whois.nic.aw",		// Aruba - no whois server assigned
				"ax" 	    => "whois.ax", 				// Aland Islands
				"az" 	    => "whois.ripe.net", 		// Azerbaijan
				"ba" 	    => "whois.ripe.net",
				"bar"	    => "whois.nic.bar",
								// bb - Barbados - no whois server assigned
								// bd - Bangladesh - no whois server assigned
				"be"	     => "whois.dns.be", 		// Belgium
				"berlin"     => "whois.nic.berlin",		// Berlin
				"best"	    => "whois.nic.best",
				"bg" 	    => "whois.register.bg", 	// Bulgaria
				"bi" 	    => "whois.nic.bi", 			// Burundi
				"biz" 	    => "whois.biz",
				"bj"     	=> "whois.nic.bj", 			// Benin
								// bm - Bermuda - no whois server assigned
				"bn" 	    => "whois.bn", 				// Brunei Darussalam
				"bo" 	    => "whois.nic.bo", 			// Bolivia
				"br" 	    => "whois.registro.br", 	// Brazil
				"br.com"    => "whois.centralnic.com",
				"bt" 	    => "whois.netnames.net", 	// Bhutan
								// bv - Bouvet Island (Norway) - no whois server assigned
				"bw"	    => "whois.nic.net.bw",		// Botswana - no whois server assigned
				"by" 	    => "whois.cctld.by", 		// Belarus
				"bz" 	    => "whois.belizenic.bz", 	// Belize
				"bzh"	    => " whois-bzh.nic.fr",
				"ca" 	    => "whois.cira.ca", 		// Canada
				"cat" 	    => "whois.cat", 			// Spain
				"cc" 	    => "whois.nic.cc", 			// Cocos (Keeling) Islands
				"cd" 	    => "whois.nic.cd", 			// Congo, The Democratic Republic Of The
				"ceo"	    => "whois.nic.ceo",
				"cf"	    => "whois.dot.cf",			// Central African Republic - no whois server assigned
				"ch" 	    => "whois.nic.ch", 			// Switzerland
				"ci" 	    => "whois.nic.ci", 			// Cote d'Ivoire
				"ck" 	    => "whois.nic.ck", 			// Cook Islands
				"cl" 	    => "whois.nic.cl", 			// Chile

				"cloud"     => "whois.nic.cloud",
				"club"	    => "whois.nic.club",
								// cm - Cameroon - no whois server assigned
				"cn" 	    => "whois.cnnic.net.cn", 	// China
				"cn.com"    => "whois.centralnic.com",
				"co" 	    => "whois.nic.co", 			// Colombia
				"co.nl"     => "whois.co.nl",
				"com" 	    => "whois.verisign-grs.com",
				"coop" 	    => "whois.nic.coop",
								// cr - Costa Rica - no whois server assigned
								// cu - Cuba - no whois server assigned
								// cv - Cape Verde - no whois server assigned
								// cw - Curacao - no whois server assigned
				"cx" 	    => "whois.nic.cx", 			// Christmas Island
				"cy" 	    => "whois.ripe.net",
				"cz" 	    => "whois.nic.cz", 			// Czech Republic
				"de" 	    => "whois.denic.de", 		// Germany
								// dj - Djibouti - no whois server assigned
				"dk" 	    => "whois.dk-hostmaster.dk", // Denmark
				"dm" 	    => "whois.nic.dm", 			// Dominica
								// do - Dominican Republic - no whois server assigned
				"dz" 	    => "whois.nic.dz", 			// Algeria
				"ec" 	    => "whois.nic.ec", 			// Ecuador
				"edu" 	    => "whois.educause.edu",
				"ee" 	    => "whois.eenet.ee", 		// Estonia
				"eg" 	    => "whois.ripe.net", 		// Egypt
								// er - Eritrea - no whois server assigned
				"es" 	    => "whois.nic.es", 			// Spain
								// et - Ethiopia - no whois server assigned
				"eu" 	    => "whois.eu",
				"eu.com"    => "whois.centralnic.com",
				"eus"	    => "whois.nic.eus",
				"fi" 	    => "whois.ficora.fi", 		// Finland
								// fj - Fiji - no whois server assigned
								// fk - Falkland Islands - no whois server assigned
								// fm - Micronesia, Federated States Of - no whois server assigned
				"fo" 	    => "whois.nic.fo", 			// Faroe Islands
				"fr" 	    => "whois.nic.fr", 			// France
								// ga - Gabon - no whois server assigned
				"gb" 	    => "whois.ripe.net",		// Great Britain
				"gb.com"    => "whois.centralnic.com",
				"gb.net"    => "whois.centralnic.com",
				"qc.com"    => "whois.centralnic.com",
				"gd"	    => "whois.nic.gd", 			// Grenada
				"ge" 	    => "whois.ripe.net",		// Georgia
								// gf - French Guiana - no whois server assigned
				"gg" 	    => "whois.gg", 				// Guernsey
								// gh - Ghana - no whois server assigned
				"gi" 	    => "whois2.afilias-grs.net", // Gibraltar
				"gl" 	    => "whois.nic.gl", 			// Greenland (Denmark)
				"gm" 	    => "whois.ripe.net",		// Gambia
								// gn - Guinea - no whois server assigned
				"gov" 	    => "whois.nic.gov",
				"gov.uk"    => "whois.ja.net",
				"gr" 	    => "whois.ripe.net",		// Greece
								// gt - Guatemala - no whois server assigned
				"gs" 	    => "whois.nic.gs", 			// South Georgia And The South Sandwich Islands
								// gu - Guam - no whois server assigned
				"gw" 	    => "whois.nic.gw",
				"gy" 	    => "whois.registry.gy", 	// Guyana
				"hamburg"    => "whois.nic.hamburg",
				"hiphop"    => "whois.uniregistry.net",
				"hk" 	    => "whois.hkirc.hk", 		// Hong Kong
				"hm" 	    => "whois.registry.hm",		// eard and McDonald Islands (Australia)
				"hn" 	    => "whois.nic.hn", 			// Honduras
				"host"	    => "whois.nic.host",
				"hr" 	    => "whois.dns.hr", 			// Croatia
				"ht" 	    => "whois.nic.ht", 			// Haiti
				"hu" 	    => "whois.nic.hu", 			// Hungary
				"hu.com"    => "whois.centralnic.com",
				"id"	    => "whois.pandi.or.id",		//Indonesia - no whois server assigned
				"ie" 	    => "whois.domainregistry.ie", // Ireland
				"il" 	    => "whois.isoc.org.il", 	// Israel
				"im" 	    => "whois.nic.im", 			// Isle of Man
				"in" 	    => "whois.inregistry.net", 	// India
				"info" 	    => "whois.afilias.net",
				"ing"	    => "domain-registry-whois.l.google.com",
				"ink"		=> "ink whois.centralnic.com",
				"int" 	    => "whois.iana.org",
				"io" 	    => "whois.nic.io", 			// British Indian Ocean Territory
				"iq" 	    => "whois.cmc.iq", 			// Iraq
				"ir" 	    => "whois.nic.ir", 			// Iran, Islamic Republic Of
				"is" 	    => "whois.isnic.is", 		// Iceland
				"it" 	    => "whois.nic.it", 			// Italy
				"je" 	    => "whois.je", 				// Jersey
								// jm - Jamaica - no whois server assigned
								// jo - Jordan - no whois server assigned
				"jobs" 	    => "jobswhois.verisign-grs.com",
				"jp" 	    => "whois.jprs.jp", 		// Japan
				"ke" 		    => "whois.kenic.or.ke", // Kenya
				"kg"     	=> "www.domain.kg", 			// Kyrgyzstan
								// kh - Cambodia - no whois server assigned
				"ki" 	    => "whois.nic.ki", 			// Kiribati
								// km - Comoros - no whois server assigned
								// kn - Saint Kitts And Nevis - no whois server assigned
								// kp - Korea, Democratic People's Republic Of - no whois server assigned
				"kr" 	    => "whois.kr", 				// Korea, Republic Of
								// kw - Kuwait - no whois server assigned
								// ky - Cayman Islands - no whois server assigned
				"kz" 	    => "whois.nic.kz", 			// Kazakhstan
				"la" 	    => "whois.nic.la", 			// Lao People's Democratic Republic
								// lb - Lebanon - no whois server assigned
								// lc - Saint Lucia - no whois server assigned
				"li" 	    => "whois.nic.li", 			// Liechtenstein
								// lk - Sri Lanka - no whois server assigned
				"london"    => "whois.nic.london",
				"lt" 	    => "whois.domreg.lt", 		// Lithuania
				"lu" 	    => "whois.dns.lu", 			// Luxembourg
				"lv" 	    => "whois.nic.lv", 			// Latvia
				"ly" 	    => "whois.nic.ly", 			// Libya
				"ma" 	    => "whois.iam.net.ma", 		// Morocco
				"mc"	    => "whois.ripe.net",		// Monaco - no whois server assigned
				"md" 	    => "whois.nic.md", 			// Moldova
				"me" 	    => "whois.nic.me", 			// Montenegro
				"mg" 	    => "whois.nic.mg", 			// Madagascar
								// mh - Marshall Islands - no whois server assigned
				"mil" 	    => "whois.nic.mil",
				"mk"	    => "whois.ripe.net",		// Macedonia, The Former Yugoslav Republic Of - no whois server assigned
				"ml"     	=> "whois.dot.ml", // Mali
								// mm - Myanmar - no whois server assigned
				"mn" 	    => "whois.nic.mn", // Mongolia
				"mo" 	    => "whois.monic.mo", 		// Macao
				"mobi" 	    => "whois.dotmobiregistry.net",
				"mp" 	    => "whois.nic.mp", 			// Northern Mariana Islands
								// mq - Martinique (France) - no whois server assigned
								// mr - Mauritania - no whois server assigned
				"ms" 	    => "whois.nic.ms", 			// Montserrat
				"mt" 	    => "whois.ripe.net", 		// Malta
				"mu" 	    => "whois.nic.mu", 			// Mauritius
				"museum"     => "whois.museum",
								// mv - Maldives - no whois server assigned
								// mw - Malawi - no whois server assigned
				"mx" 	    => "whois.mx", 				// Mexico
				"my" 	    => "whois.domainregistry.my", // Malaysia
				"mz"	    => " whois.nic.mz",			// Mozambique - no whois server assigned
				"na" 	    => "whois.na-nic.com.na", 	// Namibia
				"name" 	    => "whois.nic.name",
				"nc"     	=> "whois.nc", 				// New Caledonia
								// ne - Niger - no whois server assigned
				"net" 	    => "whois.verisign-grs.net",
				"nf" 	    => "whois.nic.nf", 			// Norfolk Island
				"ng" 	    => "whois.nic.net.ng", 		// Nigeria
								// ni - Nicaragua - no whois server assigned
				"nl" 	    => "whois.domain-registry.nl", // Netherlands
				"no" 	    => "whois.norid.no", 			// Norway
				"no.com"    => "whois.centralnic.com",
								// np - Nepal - no whois server assigned
								// nr - Nauru - no whois server assigned
				"nu" 	    => "whois.nic.nu", // Niue
				"nz" 	    => "whois.srs.net.nz", 		// New Zealand
				"om" 	    => "whois.registry.om", 	// Oman
				"ong"	    => "whois.publicinterestregistry.net",
				"ooo" 	    =>	"whois.nic.ooo",
				"org" 	    => "whois.pir.org",
								// pa - Panama - no whois server assigned
				"paris"     => "whois-paris.nic.fr",
				"pe" 	    => "kero.yachay.pe", 		// Peru
				"pf" 	    => "whois.registry.pf", 	// French Polynesia
								// pg - Papua New Guinea - no whois server assigned
								// ph - Philippines - no whois server assigned
				"pics"	    => "whois.uniregistry.net",
								// pk - Pakistan - no whois server assigned
				"pl" 	    => "whois.dns.pl", 			// Poland
				"pm" 	    => "whois.nic.pm", 			// Saint Pierre and Miquelon (France)
								// pn - Pitcairn (New Zealand) - no whois server assigned
				"post" 	    => "whois.dotpostregistry.net",
				"pr" 	    => "whois.nic.pr", 			// Puerto Rico
				"press"	    => "whois.nic.press",
				"pro" 	    => "whois.dotproregistry.net",
								// ps - Palestine, State of - no whois server assigned
				"pt" 	    => "whois.dns.pt", 			// Portugal
				"pub"	    => "whois.unitedtld.com",
				"pw" 	    => "whois.nic.pw", 			// Palau
								// py - Paraguay - no whois server assigned
				"qa" 	    => "whois.registry.qa", 	// Qatar
				"re" 	    => "whois.nic.re", 			// Reunion (France)
				"ro" 	    => "whois.rotld.ro", 		// Romania
				"rs" 	    => "whois.rnids.rs", 		// Serbia
				"ru" 	    => "whois.tcinet.ru", 		// Russian Federation
								// rw - Rwanda - no whois server assigned
				"sa" 	    => "whois.nic.net.sa", 		// Saudi Arabia
				"sa.com"    => "whois.centralnic.com",
				"sb" 	    => "whois.nic.net.sb", 		// Solomon Islands
				"sc" 	    => "whois2.afilias-grs.net", // Seychelles
								// sd - Sudan - no whois server assigned
				"se" 	    => "whois.iis.se", 			// Sweden
				"se.com"    => "whois.centralnic.com",
				"se.net"    => "whois.centralnic.com",
				"sg" 	    => "whois.sgnic.sg", 		// Singapore
				"sh" 	    => "whois.nic.sh", 			// Saint Helena
				"si" 	    => "whois.arnes.si", 		// Slovenia
				"sk" 	    => "whois.sk-nic.sk", 		// Slovakia
								// sl - Sierra Leone - no whois server assigned
				"sm" 	    => "whois.nic.sm", 			// San Marino
				"sn" 	    => "whois.nic.sn", 			// Senegal
				"so" 	    => "whois.nic.so", 			// Somalia
								// sr - Suriname - no whois server assigned
				"st"	    => "whois.nic.st", 			// Sao Tome And Principe
				"su" 	    => "whois.tcinet.ru", 		// Russian Federation
								// sv - El Salvador - no whois server assigned
				"sx" 	    => "whois.sx", 				// Sint Maarten (dutch Part)
				"sy" 	    => "whois.tld.sy", 			// Syrian Arab Republic
								// sz - Swaziland - no whois server assigned
				"tc" 	    => "whois.meridiantld.net", // Turks And Caicos Islands
								// td - Chad - no whois server assigned
				"tel" 	    => "whois.nic.tel",
				"tf"     	=> "whois.nic.tf", 			// French Southern Territories
								// tg - Togo - no whois server assigned
				"th" 	    => "whois.thnic.co.th", 	// Thailand
				"tj" 	    => "whois.nic.tj", 			// Tajikistan
				"tk" 	    => "whois.dot.tk", 			// Tokelau
				"tl" 	    => "whois.nic.tl", 			// Timor-leste
				"tm" 	    => "whois.nic.tm", 			// Turkmenistan
				"tn" 	    => "whois.ati.tn", 			// Tunisia
				"to" 	    => "whois.tonic.to", 		// Tonga
				"top"	    => "whois.nic.top",
				"tp" 	    => "whois.nic.tl", 			// Timor-leste
				"tr" 	    => "whois.nic.tr", 			// Turkey
				"travel"    => "whois.nic.travel",
								// tt - Trinidad And Tobago - no whois server assigned
				"tv" 	    => "tvwhois.verisign-grs.com", // Tuvalu
				"tw" 	    => "whois.twnic.net.tw", 	// Taiwan
				"tz" 	    => "whois.tznic.or.tz", 	// Tanzania, United Republic Of
				"ua" 	    => "whois.ua", 				// Ukraine
				"ug" 	    => "whois.co.ug", 			// Uganda
				"uk" 	    => "whois.nic.uk", 			// United Kingdom
				"uk.com"    => "whois.centralnic.com",
				"uk.net"    => "whois.centralnic.com",
				"us" 	    => "whois.nic.us", 			// United States
				"us.com"    => "whois.centralnic.com",
				"uy" 	    => "whois.nic.org.uy", 		// Uruguay
				"uy.com"    => "whois.centralnic.com",
				"uz" 	    => "whois.cctld.uz", 		// Uzbekistan
				"va" 	    => "whois.ripe.net", 		// Holy See (vatican City State)
				"vc" 	    => "whois2.afilias-grs.net",// Saint Vincent And The Grenadines
				"ve" 	    => "whois.nic.ve", 			// Venezuela
				"vg" 	    => "whois.adamsnames.tc", // Virgin Islands, British
								// vi - Virgin Islands, US - no whois server assigned
								// vn - Viet Nam - no whois server assigned
				"vu"	    => "vunic.vu",				// Vanuatu - no whois server assigned
				"wang"	    => "whois.nic.wang",
				"wf" 	    => "whois.nic.wf", 			// Wallis and Futuna
				"wiki"	    => "whois.nic.wiki",
				"ws" 	    => "whois.website.ws", 		// Samoa
				"xxx" 	    => "whois.nic.xxx",
				"xyz"	    => "whois.nic.xyz",
								// ye - Yemen - no whois server assigned
				"yt" 	    => "whois.nic.yt", 			// Mayotte
				"yu" 	    => "whois.ripe.net",
				"za.com"    => "whois.centralnic.com"
			);

            $res_array = array();
            $url = strtolower(trim($url));

            if (!strpos($url, "ttp://")) {
                $url = "http://".$url;      //  if missing, add the scheme
            }

            $urlparts  = parse_url($url);
            $new_domain = @str_replace('www.', '', $urlparts['host']) ;

            //  if exist, remove sub-domains
            if(substr_count($new_domain, '.') > 1) {
                $no_suffix = substr($new_domain , 0, strrpos($new_domain, '.')) ;   //  remove suffix
                $new_domain = substr($new_domain , strrpos($no_suffix, '.')+1) ;    //  remove subdomains
            }
            //  extract the suffix
            $delim  = strrpos($new_domain, ".");
            $name   = substr($new_domain, 0, $delim);
            $suffix = substr($new_domain, $delim + 1);
            //  start preparing the result arry
            $res_array['url'] = $url;
            $res_array['domain_name'] = $name;
            $res_array['suffix'] = $suffix;

            if ($list) {
                //  present list of supported suffixes
                $supported = '';
                $all_suffixes = array_keys($whoisservers);
                if ($all_suffixes) {
                    for ($i = 0; $i < count($all_suffixes); $i++) {
                        $supported .= '&nbsp;.'.$all_suffixes [$i].'&nbsp;';
                    }
                    $res_array['result'] = "okay";
                    $res_array['answer'] = $supported;
                } else {
                    $res_array['result'] = "invalid array";
                    $res_array['answer'] = "server array not found, or empty";
                }
                return $res_array;

            } else {
                //  perform a WHOIS check
                //  first check for valid input
                if (!$delim) {
                    $res_array['result'] = "Invalid URL";
                    $res_array['answer'] = "Delimiter missing in URL";
                    return $res_array;
                } else {
                    if (!array_key_exists($suffix, $whoisservers)) {
                        $res_array['result'] = "Invalid URL";
                        $res_array['answer'] = "Suffix '$suffix' not supported";
                        return $res_array;
                    }
                }
                //  now  do the WHOIS query
                $answer     = '';
                $neg_answer = '';
                $server     = $whoisservers[$suffix];

                $request    =  fsockopen($server, 43, $errno, $errstr, 30);

                if (!$request) {
                    $answer = "$errstr ($errno)";
                } else {
                    fputs($request, "$new_domain\r\n");

                    while (!feof($request)) {
                        stream_set_timeout($request, 30);
                        $answer .= fread($request,128);
                    }
                    fclose ($request);
                }

                if (!$answer) {
                    $neg_answer = 1 ;
                } else {    //  check for any negative answer
                    $whois_string =preg_replace("/\s+/"," ",$answer);   //Replace whitespace with single space

                    foreach ($this->neg_response as $reject) {          //  test for all available negative answers
                        if (stripos(" ".$whois_string, $reject)) {
                            $neg_answer = 1 ;
                        }
                    }
                }

                if (!$neg_answer) {
                    $res_array['result']        = "okay";
                    $res_array['answer']        = $answer;
                    $res_array['whoisserver']   = $server;
                } else {
                    $res_array['result']        = "invalid, domain not found";
                    $res_array['answer']        = $answer;
                    $res_array['whoisserver']   = $server;
                }
                return $res_array;
            }
        }

        private $neg_response = array (
                "10060",
                "Connection refused",
                "does not exist",
                "domain name not known",
                "domain status: vailable",
                "error:101",
                "error for",
                "getaddrinfo failed",
                "is free",
                "is available",
                "is not registered",
                "no bbjects found",
                "no data found",
                "no data was found",
                "no domain records",
                "no entries found",
                "no existe",
                "no information available",
                "no match",
                "no match for",
                "nomatching",
                "nombre del Ddminio",
                "no records matching",
                "no such domain",
                "not available",
                "not found in database",
                "not registered",
                "not been registered",
                "not exist in database",
                "not found",
                "not have an entry",
                "nothing found",
                "object_not_found",
                "query_status: 500",
                "reject: not available",
                "status: avail",
                "status: available",
                "status: free",
                "to purchase",
                "(null)"
                );
    }

?>