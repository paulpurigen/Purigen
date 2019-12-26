<?php

    error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE & ~E_STRICT);

    $com_in     = array();      //  intermediate array for ignored words
    $all_in     = array();      //  intermediate array for ignored words
    $common     = array();      //  array fo ignored words
    $ext        = array();      //  array for ignored file suffixes
    $whitelist  = array();      //  array for whitelist
    $white      = array();
    $white_in   = array();
    $blacklist  = array();      //  array for blacklist
    $black_in   = array();
    $uas_in     = array();      //  intermediate array for evil User Agents
    $ips_in     = array();      //  intermediate array for bad IPs
    $black_uas  = array();      // User Agent strings belonging to evil bots
    $black_ips  = array();      // IPs belonging to Google, MSN, Amazon, etc bots
    $black      = array();
    $image		= array();	    //	array for image suffixes
    $audio		= array();		//	array for audio suffixes
    $video		= array();	    //	array for video suffixes
    $pres_not   = array();      //	array for pre classes not to be indexed
    $uls_not	= array(); 		//	array for ul classes not to be indexed
    $divs_not   = array();      //	array for divs not to be indexed
    $divs_use   = array();      //	array for divs to be indexed
    $docs       = array();      //	array holding  a list of documents to be indexed
    $elements_not   = array();  //	array for HTML elements not to be indexed
    $elements_use   = array();  //	array for HTML elements to be indexed
    $slv        	= array();	//	array of most common Second Level Domains
	$xml_pr_feeds0 	= array();	//	array of product feed (tag names), only first word per line
	$xml_pr_feeds 	= array();	//	array of product feed (tag names) to be indexed
	$key_file	= '';			//	file for all XML product feed keys

    //  currently not required
    //$mysql_charset = conv_mysqli($home_charset); //  convert the home._charset to MySQL format

    if (is_dir($common_dir)) {
        $handle = opendir($common_dir);
        if ($use_common == 'all') {
            while (false !== ($common_file = readdir($handle))) {   //  get all common files
                if (strpos($common_file, "ommon_")) {
                    $act = @file($common_dir.$common_file);         //  get content of this common file
                    $all_in = array_merge($all_in, $act);           //  build a complete array of common words
                }
            }
        }

        if ($use_common != 'all' && $use_common != 'none') {
            $all_in = @file("".$common_dir."common_".$use_common.".txt");         //  get content of language specific common file
        }

        if (is_array($all_in)) {
			foreach ($all_in as $word){
				if (preg_match("/\S/", $word)) {    //  remove empty entries from list
					$com_in[] = $word;
				}
			}
        }

        if (is_array($com_in)) {
			foreach ($com_in as $word){
				$common[trim($word)] = 1;
			}
        }

        if ($use_white1 == '1' || $use_white2 == '1') {
            $white_in = @file($common_dir.'whitelist.txt');    //  get all words to enable page indexing
            foreach ($white_in as $val) {
                if ($case_sensitive == '0') {
                    $val = lower_case($val);
                }
                $val = @iconv($home_charset,"UTF-8",$val);
                if (preg_match("/\S/", $val)) {    //  remove empty entries from list
                    $white[] = addslashes($val);
                }
            }

            while (list($id, $word) = each($white))
			foreach ($white as $word){
				$whitelist[] = trim($word);
			}
            $whitelist = array_unique($whitelist);
            sort($whitelist);
        }

        $suffix         = @file($common_dir.'suffix.txt');      //  get all file suffixes to be ignored during index procedure
        $black_in       = @file($common_dir.'blacklist.txt');   //  get all words to prevent indexing of page
        $uas_in         = @file($common_dir.'black_uas.txt');   //  get all evil user-agent strings
        $ips_in_priv    = @file($common_dir.'black_ips_priv.txt');   //  get all Meta search engine IPs
        $image          = @file($common_dir.'image.txt');       //  get all image suffixes to be indexed
        $audio          = @file($common_dir.'audio.txt');       //  get all audio suffixes to be indexed
        $video          = @file($common_dir.'video.txt');       //  get all audio suffixes to be indexed
        $pres_not       = @file($common_dir.'pres_not.txt');    //  get all pre tag classes not to be indexed (Admin selected)
        $uls_not        = @file($common_dir.'uls_not.txt');     //  get all ul tag classes not to be indexed (Admin selected)
        $divs_not       = @file($common_dir.'divs_not.txt');    //  get all div's not to be indexed (Admin selected)
        $divs_use       = @file($common_dir.'divs_use.txt');    //  get all div's to be indexed (Admin selected)
        $docu           = @file($common_dir.'docs.txt');        //  get all document suffixes to be indexed (Admin selected)
        $elements_not   = @file($common_dir.'elements_not.txt');    //  get all HTML elements to not to be indexed (Admin selected)
        $elements_use   = @file($common_dir.'elements_use.txt');    //  get all HTML elements to be indexed (Admin selected)
        $sld            = @file($common_dir.'sld.txt');         	//  get all SLDs
        $pr_feeds_in	= @file($common_dir.'xml_product_feeds.txt');	//  get all xml product feed tag names

        closedir($handle);

        //  $ext is required only for index procedure, not for search function
        if (strpos($_SERVER["SCRIPT_NAME"], "admin")) {
			foreach ($suffix as $word){
				if (preg_match("/\S/", $word)) {    //  remove empty entries from list
					$ext[] = trim($word);
				}
			}

            //  if JavaScript redirections should not be followed
            if (!$js_reloc) {
                $ext[] = "js";  //  add suffix for JavaScript files
            }

            $ext = array_unique($ext);
            sort($ext);
        }

        if ($use_black == 1) {
            foreach ($black_in as $val) {
                if ($case_sensitive == '0') {
                    $val = lower_case($val);
                }
                $val = @iconv($home_charset,"UTF-8",$val);
                if (preg_match("/\S/", $val)) {    //  remove empty entries from list
                    $black[] = trim($val);
                }
            }

			foreach ($black as $word){
				$blacklist[] = $word;
			}
            $blacklist = array_unique($blacklist);
            sort($blacklist);
        }

        if ($index_image == 1) {

			foreach ($image as $word){
				if (preg_match("/\S/", $word)) {    //  remove empty entries from list
					$imagelist[] = trim(strtolower($word));
				}
			}
            $imagelist = array_unique($imagelist);
            sort($imagelist);
        }

        if ($index_audio == 1) {
			foreach ($audio as $word){
				if (preg_match("/\S/", $word)) {    //  remove empty entries from list
					$audiolist[] = trim(strtolower($word));
				}
			}
            $audiolist = array_unique($audiolist);
            sort($audiolist);
        }

        if ($index_video == 1) {
			foreach ($video as $word){
				if (preg_match("/\S/", $word)) {    //  remove empty entries from list
					$videolist[] = trim(strtolower($word));
				}
			}
            $videolist = array_unique($videolist);
            sort($videolist);
        }

        if ($not_pres == 1) {
			foreach ($pres_not as $word){
				if (preg_match("/\S/", $word)) {    //  remove empty entries from list
					$not_prelist[] = trim($word);
				}
			}
            $not_prelist = array_unique($not_prelist);
            sort($not_prelist);
        }

        if ($not_uls == 1) {
			foreach ($uls_not as $word){
				if (preg_match("/\S/", $word)) {    //  remove empty entries from list
					$not_ullist[] = trim($word);
				}
			}
            $not_ullist = array_unique($not_ullist);
            sort($not_ullist);
        }

        if ($not_divs == 1) {
			foreach ($divs_not as $word){
				if (preg_match("/\S/", $word)) {    //  remove empty entries from list
					$not_divlist[] = trim($word);
				}
			}
            $not_divlist = array_unique($not_divlist);
            sort($not_divlist);
        }

        if ($use_divs == 1) {
			foreach ($divs_use as $word){
				if (preg_match("/\S/", $word)) {
					$use_divlist[] = trim($word);
				}
			}
            $use_divlist = array_unique($use_divlist);
            sort($use_divlist);
        }

        if ($only_docs == 1) {
			foreach ($docu as $word){
				if (preg_match("/\S/", $word)) {    //  remove empty entries from list
					$docs[] = trim(strtolower($word));
				}
			}
            $docs = array_unique($docs);
            sort($docs);
        }

        if ($not_elems == 1) {
			foreach ($elements_not as $word){
				if (preg_match("/\S/", $word)) {    //  remove empty entries from list
					$not_elementslist[] = trim($word);
				}
			}
            $not_elementslist = array_unique($not_elementslist);
            sort($not_elementslist);
        }

        if ($use_elems == 1) {
			foreach ($elements_use as $word){
				if (preg_match("/\S/", $word)) {
					$use_elementslist[] = trim($word);
				}
			}
            $use_elementslist = array_unique($use_elementslist);
            sort($use_elementslist);
        }

        if ($redir_host == 1 || $other_host == 1) {
			foreach ($sld as $word){
				$sldlist[] = trim($word);
			}
            $sldlist = array_unique($sldlist);
            sort($sldlist);
        }

        if ($kill_black_uas == 1) {
			foreach ($uas_in as $word){
				if (preg_match("/\S/", $word)) {    //  remove empty entries from list
					$black_uas[] = $word;
				}
			}
        }

        if ($index_xml_pr == 1) {
             foreach($pr_feeds_in as $word){
				 //$word = strtolower($word);	//	required for case insensitive sorting
				if (preg_match("/\S/", $word) && !strstr($word, "//")) {    //  remove empty entries and comment rows from list
					$xml_pr_feeds[] = trim($word);
					//	if multipe names in one line
					if (strstr($word, ",")) {
					$xml_pr_feeds0[] = trim(substr($word, 0,strpos($word, ",")));
					} else {
						$xml_pr_feeds0[] = trim($word);
					}
				}
			}

			sort($xml_pr_feeds0, SORT_STRING | SORT_FLAG_CASE | SORT_NATURAL);	// here case sensitive sorting
			$xml_pr_feeds0 	= array_flip($xml_pr_feeds0);
			$keys 			= array_keys($xml_pr_feeds0);

			// build a file (string) of all keys
			foreach ($keys as $this_key) {
				$file_keys.= "$this_key, ";
			}
			//	fill complete array with "0"
			reset ($keys);
			$xml_pr_feeds0	= array_fill_keys($keys , "0");
		}

        if ($kill_black_ips == 1) {
            $last_mod       = filemtime($common_dir.'black_ips.txt');
            $new_file       = "http://www.stopforumspam.com/downloads/bannedips.zip";   //  this is the actual list supplied by the web service
            $new_file_res   = "http://sphider-plus.eu/test/bannedips.zip";  //  only used for first update and not to bother the web service too often
            $priv_file      = $common_dir.'black_ips_priv.txt';
            $black_ips      = $common_dir.'black_ips.txt';
            $new_bad_ips    = $include_dir.'/tmp//bannedips.zip';
            $path           = $include_dir.'/tmp';
            $csv            = $include_dir.'/tmp/bannedips.csv';
			$priv			= '';
            $diff           = "86400";  //  24 hours
            //$diff           = "60";  //  60 seconds (for tests only)
            if ($diff < "40000") {
                $new_file = $new_file_res;    // for tests only
            }

            $old            = @file_get_contents($black_ips);   //get the former content from $black_ips

            $all_black_ips  = explode("\r\n", $old); //  will be used, if no updated black IPs are currently available
            $new_black_ips  = array();

             //  use only the known Meta search engines IPs, no spammer IPs
            if ($kill_spam_ips != 1) {
                $all_black_ips  = preg_replace("/[^a-zA-Z0-9 \/\.\-\#]+/", "", @file($priv_file));
                $priv_black_ips  = implode("\r\n", $all_black_ips);
                @file_put_contents($black_ips, $priv_black_ips);
            }

            //  for first access of $kill_spam_ips, use the reserve file and do not touch the 24 hour rule
            if ($kill_spam_ips == 1 && strlen($old) < '100000' ) {
                //  get the private black IPs
                $black_ips_priv = preg_replace("/[^a-zA-Z0-9 \/\.\-\#]+/", "", @file($priv_file));

				if (strstr($_SERVER['SERVER_NAME'], "localhost")) {
					$res 	= $black_ips_priv;	//	as localhost applications might not be able to access the Internet
					$priv	= '1';
				} else {
					//  get the reserve Spammer IPs
					$res = @file_get_contents($new_file_res);   // Unfortunately the PHP library 'ZipArchive' does not cooperate with remote URLs
				}
                if (strlen($res) > 10000) {
                    $all_black_ips = array();
                    @file_put_contents($new_bad_ips, $res);
                    //  unzip the file
                    $zip = new ZipArchive;
                    $res = $zip->open($new_bad_ips);
                    if ($res === TRUE) {
                        // extract it to the path we determined above
                        $zip->extractTo($path);
                        $zip->close();
                        //  convert the intermediate cvs file into  an array
                        $new_black_ips  = explode(",", @file_get_contents($csv));
                        //  merge both arrays
                        $all_black_ips  = array_unique(array_merge($black_ips_priv, $new_black_ips));

                        // store the updated file with the new time info
                        $the_black_ips  = implode("\r\n", $all_black_ips);
                        @file_put_contents($black_ips, $the_black_ips);
                        //  as a short info for admin backend
                        if (stripos($_SERVER['REQUEST_URI'], "/admin") && $debug == "2") {
                            echo "<br />Spammer IPs successfully added.<br /><br />\r\n";
                        }
                    } else {
                        if (stripos($_SERVER['REQUEST_URI'], "/admin") && $debug == "2") {
                            echo "Dash! Couldn't open $csv";
                            echo "<br />Error code:" . $res;
                            echo "<br />Unable to update spammer IPs";
                        }
                    }
                } else {
                    //  if unable to read the spammer IPs, enter here
                    if (stripos($_SERVER['REQUEST_URI'], "/admin") && $debug == "2") {
                        echo "Dash! Couldn't read $new_file_res";
                        echo "<br />Unable to update spammer IPs";
                    }
                    $all_black_ips = $black_ips_priv;    //  only the private black IPs are available
                }
            }
            //  if activated in admin backend and last update of spammer IPs file  is > 24 hours, enter here
            if ($kill_spam_ips == 1 && time()-$last_mod > $diff && strlen($old) > '100000' ) {
                //  get the private black IPs
                $black_ips_priv = preg_replace("/[^a-zA-Z0-9 \/\.\-\#]+/", "", @file($priv_file));

				if (strstr($_SERVER['SERVER_NAME'], "localhost")) {
					$new 	= $black_ips_priv;	//	as localhost applicationsmight not be able to access the Internet
					$priv	= '1';
				} else {
				//  get the actual spammer IPs
					$new = @file_get_contents($new_file);   // Unfortunately the PHP library 'ZipArchive' does not cooperate with remote URLs
				}

                if (strlen($new) < 10000) {
                    $new = @file_get_contents($new_file_res);  //   try to use the reserve file from Sphider-plus server
                }

                if (strlen($new) > 10000) {
                    $all_black_ips = array();
                    @file_put_contents($new_bad_ips, $new);
                    //  unzip the file
                    $zip = new ZipArchive;
                    $res = $zip->open($new_bad_ips);
                    if ($res === TRUE) {
                        // extract it to the path we determined above
                        $zip->extractTo($path);
                        $zip->close();
                        //  convert the intermediate cvs file to array
                        $new_black_ips  = explode(",", @file_get_contents($csv));
                        //  merge both arrays
                        $all_black_ips  = array_unique(array_merge($black_ips_priv, $new_black_ips));
                        // store the updated file with the new time info
                        $the_black_ips  = implode("\r\n", $all_black_ips);
                        file_put_contents($black_ips, $the_black_ips);
                        //  short info for admin
                        if (stripos($_SERVER['REQUEST_URI'], "/admin") && $debug == "2" && $priv != '1') {
                            echo "<br />Spammer IPs successfully updated.<br /><br />\r\n";
                        }
                    } else {
                        if (stripos($_SERVER['REQUEST_URI'], "/admin") && $debug == "2") {
                            echo "Dash! Couldn't open $csv";
                            echo "<br />Error code:" . $res;
                            echo "<br />Unable to update spammer IPs";
                        }
                    }
                } else {
                    //  if unable to read the spammer IPs, enter here
                    if (stripos($_SERVER['REQUEST_URI'], "/admin") && $debug == "2") {
                        echo "Dash! Couldn't read $new_file";
                        echo "<br />Unable to update spammer IPs";
                    }
                    $all_black_ips = $black_ips_priv;    //  only the private black IPs are available
                }
            }

            if (count($all_black_ips) < 100 ) {
                $all_black_ips = explode("\r\n", $old);
            }
            $black_ips = array();
            foreach($all_black_ips as $word) {
                $word = trim($word);
                if (strpos($word, " ")) {
                    $word = trim(substr($word, 0, strpos($word, " ", 1)));
                }
                if (!strstr($word, "#") && strlen($word) > 3) { //  remove empty entries and comment rows
                    $black_ips[] = $word;
                }
            }
            $black_ips = array_unique($black_ips);  //  remove duplicates from private IPs and spammer IPs
//$count = count($black_ips);
//echo "\r\n\r\n<br /> black_ips count: '$count'<br />\r\n";
        }
    }

?>