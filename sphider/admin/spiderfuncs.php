<?php

    error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE & ~E_STRICT);
	//error_reporting (E_ALL);	// only for debugging

/******************************* Check for forbidden direct access ************************************/
    if (!defined('_SECURE')) die ("No direct access to spiderfuncs script");


    function getFileContents($url, $get_charset) {
        global $db_con, $user_agent, $url_status, $home_charset, $cn_seg;
        global $user1, $pwd1, $user2, $pwd2, $user3, $pwd3, $clear, $include_dir, $converter_dir, $idna;

        $urlparts = parse_addr($url);
        $path = $urlparts['path'];
        $host = $urlparts['host'];

        if ($idna) {
            require_once "$converter_dir/idna_converter.php";
            // Initialize the converter class
            $IDN = new IdnaConvert(array('idn_version' => 2008));
            // The input string, if input is not UTF-8 or UCS-4, it must be converted before
            //$url = utf8_encode($url);
            // Encode it to its readyble presentation
            $host = $IDN->encode($host);
        }

        if ($urlparts['query'] != "")
            $path .= "?".$urlparts['query'];
        if (isset ($urlparts['port'])) {
            $port = (int) $urlparts['port'];
        } else
        if ($urlparts['scheme'] == "http") {
            $port = 80;
        } else
        if ($urlparts['scheme'] == "https") {
            $port = 443;
        }

        if ($port == 80) {
            $portq = "";
        } else {
            $portq = ":$port";
        }
;
        $all = "Accept-Encoding: 0";
        $auth = sprintf("Authorization: Basic %s", base64_encode($user1 . ":" . $pwd1));
        $request1 = "GET $path HTTP/1.0\r\nHost: $host$portq\r\n$all\r\nUser-Agent: $user_agent\r\n$auth\r\n\r\n";

        $fsocket_timeout = 60;

        if (substr($url, 0, 5) == "https") {
            $target = "ssl://".$host;
        } else {
            $target = $host;
        }
        @fclose($fp);   //close any previous socket connection
        $errno = 0;
        $errstr = "";
        $fp = fsockopen($target, $port, $errno, $errstr, $fsocket_timeout);

        $contents = array ();
        if (!$fp) {
            $contents['state'] = "NOHOST";
            return $contents;
        } else {
            if (!fputs($fp, $request1)) {
                $contents['state'] = "Cannot send request";
                return $contents;
            }

            $answer = fgets($fp, 4096);

            if (strpos($answer, "401")) {    //  Try with second and third authorization
                fclose($fp);
                $errno = 0;
                $errstr = "";
                $fp = fsockopen($target, $port, $errno, $errstr, $fsocket_timeout);
                print $errstr;
                $linkstate = "ok";
                if (!$fp) {
                    $status['state'] = "NOHOST";
                } else {
                    $user   = $user2;
                    $pwd    = $pwd2;
                    $answer = auth_connect($fp, $user, $pwd, $path, $host, $portq);
                }

                if (strpos($answer, "401")) {
                    fclose($fp);
                    $errno = 0;
                    $errstr = "";
                    $fp = fsockopen($target, $port, $errno, $errstr, $fsocket_timeout);
                    print $errstr;
                    $linkstate = "ok";
                    if (!$fp) {
                        $status['state'] = "NOHOST";
                    } else {
                        $user   = $user3;
                        $pwd    = $pwd3;
                        $answer = auth_connect($fp, $user, $pwd, $path, $host, $portq);
                    }
                }
            }

            $data = null;
            $pageSize = 0;
            socket_set_timeout($fp, $fsocket_timeout);
            $status = socket_get_status($fp);

            while ((!feof($fp) && !$status['timed_out']) && ($pageSize < 16000) ) {
                $data .= fgets($fp, 8192);
                $pageSize = number_format(strlen($data)/1024, 2, ".", "");
            }
            fclose($fp);

            if ($status['timed_out'] == 1) {
                $contents['state'] = "timeout";
            } else {
                $contents['state'] = "ok";
                $contents['file'] = substr($data, strpos($data, "\r\n\r\n") + 4);

                if ($get_charset == 1) {    //  if charset is already known, don't enter here
                    if (($url_status['content'] == 'text' || $url_status['content'] == 'xml' || $url_status['content'] == 'xhtml')){     //      do not search if pdf, doc, rtf, xls, rss etc.
                        $hedlen = strlen($data) - strlen($contents['file']);
                        $contents['header'] = substr($data,0,$hedlen);

                        $file	= $contents['file'];
						//	separate the head tag
						$head	= preg_match("@<head[^>]*>(.*?)<\/head>@si",$file, $regs);
						$head 	= $regs[1];
						$chrSet = '';

						if (preg_match("'encoding=[\'\"](.*?)[\'\"]'si", substr($file, 0, 3000), $regs)) {
							$chrSet = trim(strtoupper($regs[1]));      //      get encoding of current XML or XHTML file     and use it further on
						}

						if (!$chrSet) {
							if (preg_match("'charset=(.*?)[ \/\;\'\"]'si", substr($head, 0, 3000), $regs)) {
								$chrSet = trim(strtoupper($regs[1]));      //      get charset of current HTML file     and use it further on
							}
						}

						 if (!$chrSet) {
							if (preg_match("'charset=[\'\"](.*?)[\'\"]'si", substr($head, 0, 3000), $regs)) {

								$chrSet = trim(strtoupper($regs[1]));      //      get charset of current HTML file     and use it further on
							}
						}

						//  in assistance for all lazy webmasters
						$chrSet = preg_replace("/win-/si", "windows-", $chrSet);
						if ($chrSet == "1251") {
							$chrSet = "windows-1251";
						}

						//  this will fix the charset also for HTML5
						if (strpos($chrSet, ">")) {
							$chrSet = substr($chrSet, 0, strpos($chrSet, ">"));
						}

						$chrSet = trim(strtoupper($chrSet));

                        if(trim($chrSet) != ''){
                            $contents['charset'] = $chrSet;

                        } else { //not found, need to search in file
                            $inp = strtoupper($contents['file']);
                            if (preg_match("@(encoding=(\"|'))(.*?)('|\")@si", $inp, $regs)) {
                                $chrSet = trim(strtoupper($regs[1]));      //      get encoding of current XML or XHTML file     and use it furtheron
                            } else {
                                if (preg_match("'charset=(.*?)[\'\"]'si", $inp, $regs)) {
                                    $chrSet = trim(strtoupper($regs[1]));      //      get charset of current HTML file     and use it furtheron
                                }
                            }
                            if(trim($chrSet) != ''){
                                $contents['charset'] = $chrSet;
                            } else {
                                $contents['charset'] = $home_charset;    //  nothing found, we need to use default charset for DOCs, PDFs, etc
                            }
                        }
                    }
                }
            }
        }
        if ($clear == 1) unset ($data, $inp, $urlparts, $lines, $chrSet, $request, $status);
        return $contents;
    }

    //      try to connect without and with 'Basic Authorization'
    function auth_connect($fp, $user, $pwd, $path, $host, $portq, $call) {
        global $db_con, $user_agent;

        $all = "Accept-Encoding: 0";
        socket_set_timeout($fp, 60);
        $auth = sprintf("Authorization: Basic %s", base64_encode($user . ":" . $pwd));
        $request0   = "GET $path HTTP/1.1\r\nHost: $host$portq\r\n$all\r\nUser-Agent: $user_agent\r\n\r\n";
        $request    = "GET $path HTTP/1.1\r\nHost: $host$portq\r\n$all\r\nUser-Agent: $user_agent\r\n$auth\r\n\r\n";

        if ($call = "1") {
            fputs($fp, $request0);
        } else {
            fputs($fp, $request);
        }
        return (fgets($fp, 4096));
    }

    //      check if URL is accessible and try to connect
    function url_status($url) {
        global $db_con, $user_agent, $index_pdf, $index_doc, $index_rtf, $index_xls, $index_xlsx, $index_ppt, $index_ods, $index_odt, $index_docx, $realnum, $index_rss, $use_nofollow;
        global $plus_nr, $user1, $pwd1, $user2, $pwd2, $user3, $pwd3, $clear, $index_rar, $index_zip, $index_csv, $browser_string, $cl, $js_reloc, $homepage, $google_sb, $g_api_key;
        global $include_dir, $converter_dir, $idna, $ext, $strip_sessids, $debug, $curl, $mysql_table_prefix, $redir_count, $can_leave_domain, $local_redir, $wfs, $care_excl, $index_xml_pr;

        // check for malware URL
        //  $url = "http://ianfette.org";

        $homepage   = '';
        mysqltest();
        $sql_query = "SELECT * from ".$mysql_table_prefix."sites where url='$url'";
        $result = $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }

        $homepage = $result->num_rows;    //  defines, whether the url is the main URL as entered into Admin backend (find redirections only here, as it should be the main host URL)



        if ($can_leave_domain != 1) {
			$row = $result->fetch_array(MYSQLI_ASSOC);
			$can_leave_domain	= $row['can_leave_domain'];
		}


        $url                = stripslashes($url);
        $url0               = $url;
        $state              = array();
        $status             = array();
        $fsocket_timeout    = 30;

        //  process https scheme only if cCURL library is installed
        if (stristr($urlparts['scheme'], "ttps") && !$curl) {
            $status['state']    = "Unable to process $url <br />The cURL library is missing as part of the PHP environment.<br />This package is obligatory required to index domians using the https scheme.";
            return $status;
        }

        require_once('http.php');

        if ($idna) {
            $urlparts   = parse_all_url($url);
        } else {
            $urlparts   = parse_url($url);
        }

        $path       = $urlparts['path'];
        $host       = $urlparts['host'];

        if ($idna) {
            require_once "$converter_dir/idna_converter.php";
            // Initialize the converter class
            $IDN = new IdnaConvert(array('idn_version' => 2008));
            // Encode it to its readable presentation
            $host = $IDN->encode($host);
        }


        if (isset($urlparts['query'])) {
            $path .= "?".$urlparts['query'];
        }

        if (!isset($urlparts['path'])  && !isset($urlparts['query'])) {
            $path = "/";
        }

        //  rebuild the URL
        $s_url = $urlparts['scheme']."://".$host."".$path."";

        // if activated in admin settings,  check for save indexing at Google 'Save Browsing'
        if ($google_sb && $g_api_key && $host != "localhost") {

            $g_url      = $urlparts['scheme']."%3A%2F%2F".$host."".$path."";
            $google_get = "https://sb-ssl.google.com/safebrowsing/api/lookup?client=api&apikey=$g_api_key&appver=1.0&pver=3.0&url=$g_url%2F";

            // Instantiate the https request
            $http = new Http_s();

            $http->useCurl(TRUE);
            $http->setMethod('GET');
            $_referer = $_SERVER["HTTP_REFERER"];
            $http->setReferrer($_referer);
            $http->setUseragent($user_agent);
            $http->setTimeout($fsocket_timeout);
            $http->setMaxredirect($redir_count+1);

            //  now execute the GET command
            $http->execute($google_get);

            // error ?
            $error = $http->error;
            if (!$error) {
                $headers    = $http->headers;
                if (!$headers) {
                    $status['state'] = "Not indexed, because no response from Google.";
                    return $status;
                } else {
                    if(!stristr($headers[0], "204")) {  //  if content was not intentionally left blank
                        //  process the different 'error' situations
                        if(stristr($headers[0], "403")){
                            $status['state'] = "Not indexed, because invalid Google API key.<br />Unable to verify the URL";
                            return $status;
                        }
                        if(stristr($headers[0], "200") && $file = $http->result) { //   read the content in order to prepare the warning message
                            $status['state'] = "Not indexed, because according to Google defined as: $file";
                            return $status;
                        } else {    //  all other HTTP answers here
                            $status['state'] = "Not indexed, because Google answered with: $headers[0] ";
                            return $status;
                        }
                    }
                }
            } else {
                $status['state'] = "Not indexed, because Google request for save indexing caused error code: $error";
                return $status;
            }
        }

        // Instantiate the http class for indexing
        $http = new Http_s();

        //  should we use cURL ?
        if (!stristr($url, "localhost") && $curl) {
            $http->useCurl(TRUE);
        } else {
            $http->useCurl(false);
        }
        //  define method POST/GET
        $http->setMethod('GET');

        //  set referrer
        $_referer = $_SERVER["HTTP_REFERER"];
        $http->setReferrer($_referer);
        // set user agent
        $http->setUseragent($user_agent);
        //  set timeout
        $http->setTimeout($fsocket_timeout);
        //  set max. redirections to be followed
        $http->setMaxredirect($redir_count+1);

        //  now execute the URL to be indexed
        $http->execute($s_url);

        // error ?
        $error = $http->error;
//echo "\r\n\r\n<br /> error after first check WITH cURL: '$error'<br />\r\n";

        if (stripos($url, "robots.txt") && stristr($error, "nohost")) {
            $status['state'] = "404";
            return $status;         //  emergency exit (for our dear friends at http://www.cbc.gov.tw/)
        }
//echo "\r\n\r\n<br /> error after second check WITHOUT cURL: '$error'<br />\r\n";
        if (!$error) {
            //  fetch first executed header
            $headers = $http->headers;
//echo "\r\n\r\n<br>headers0 array:<br><pre>";print_r($headers);echo "</pre>\r\n";
            preg_match("@http(.*?)(\d{3})@i", $headers[0], $code);
//echo "\r\n\r\n<br>code array0:<br><pre>";print_r($code);echo "</pre>\r\n";

            //  if bad request (HTTP400), try without cURL
            if ($code[2] == '400' && $curl) {
                $http-> clear();
                unset($http);
                $error = '';

                $http = new Http_s();
                $http->setMethod('GET');
                $http->useCurl(false);
                $_referer = $_SERVER["HTTP_REFERER"];
                $http->setReferrer($_referer);
                $http->setUseragent($user_agent);
                $http->setTimeout($fsocket_timeout);
                $http->setMaxredirect($redir_count+1);

                $http->execute($s_url);
                $error = $http->error;
//echo "\r\n\r\n<br /> error after third check WITOUT cURL: '$error'<br />\r\n";
                if($error) {
                    $http-> clear();
                    unset($http);
                    $url_status['aborted'] = 1;
                    if (stripos($error, "network_getaddress") || stristr($error, "nohost")) {
                        $status['state'] = "NOHOST";
                        return $status;         //  emergency exit
                    }
                    if (stristr($error, "ssl")) {
                        $status['state'] = "NOHOST. ".$error;
                        return $status;         //  emergency exit
                    }
                }

                //  fetch first executed header now without cUrl
                $headers = $http->headers;
//echo "\r\n\r\n<br>headers0 array ohne cCurl:<br><pre>";print_r($headers);echo "</pre>\r\n";
            }

            preg_match("@http(.*?)(\d{3})@i", $headers[0], $code);
//echo "\r\n\r\n<br>code array0 ohne cUrl:<br><pre>";print_r($code);echo "</pre>\r\n";

            //  if required, try with 1. authentication
            if ($code[2] == '401' && $user1 && $pwd1) {
                // Instantiate it
                $http = new Http_s();

                //  should we use cURL ?
                if (!stristr($url, "localhost") && $curl) {
                    $http->useCurl(TRUE);
                } else {
                    $http->useCurl(FALSE);
                }
                //  define method POST/GET
                $http->setMethod('GET');

                //  set referrer
                $_referer = $_SERVER["HTTP_REFERER"];
                $http->setReferrer($_referer);
                // set user agent
                $http->setUseragent($user_agent);
                //  set timeout
                $http->setTimeout($fsocket_timeout);
                //  set max. redirections to be followed
                //$http->setMaxredirect("3");

                // enable first authentication
                $http->setAuth($user1, $pwd1);

                //  now execute the URL to be indexed wit first authentication
                $http->execute($s_url);
                // error ?
                $error = $http->error;
                if (!$error) {
                    //  fetch first executed header
                    $headers = $http->headers;

                } else {
                    if (stripos($error, "network_getaddress")) {
                        $status['state'] = "NOHOST";
                        return $status;
                    } else {
                        $status['state'] = $error;
                    }
                }       //  end auth1
                preg_match("@http(.*?)(\d{3})@i", $headers[0], $code);
//echo "\r\n\r\n<br>code array1:<br><pre>";print_r($code);echo "</pre>\r\n";
            }

            //  if required, try with 2. authentication
            if ($code[2] == '401' && $user2 && $pwd2) {
                // Instantiate it
                $http = new Http_s();

                //  should we use cURL ?
                if (!stristr($url, "localhost") && $curl) {
                    $http->useCurl(TRUE);
                } else {
                    $http->useCurl(false);
                }
                //  define method POST/GET
                $http->setMethod('GET');

                // add authentication parameters
                //$http->addParam('user_name' , 'yourusername');
                //$http->addParam('password'  , 'yourpassword');

                //  set referrer
                $_referer = $_SERVER["HTTP_REFERER"];
                $http->setReferrer($_referer);
                // set user agent
                $http->setUseragent($user_agent);
                //  set timeout
                $http->setTimeout($fsocket_timeout);
                //  set max. redirections to be followed
                //$http->setMaxredirect("3");

                // enable second authentication
                $http->setAuth($user2, $pwd2);
                //  now execute the URL to be indexed with second authentication
                $http->execute($s_url);
                // error ?
                $error = $http->error;
                if (!$error) {
                    //  fetch first executed header
                    $headers = $http->headers;
                } else {
                    if (stripos($error, "network_getaddress")) {
                        $status['state'] = "NOHOST";
                        return $status;
                    } else {
                        $status['state'] = $error;
                    }
                }       //  end auth1

                $row0 =$headers[0];
                preg_match("@http(.*?)(\d{3})@i", $headers[0], $code);
//echo "\r\n\r\n<br>code array2:<br><pre>";print_r($code);echo "</pre>\r\n";
            }

            //  if required, try with 3. authentication
            if ($code[2] == '401' && $user3 && $pwd3) {
                // Instantiate it
                $http = new Http_s();

                //  should we use cURL ?
                if (!stristr($url, "localhost") && $curl) {
                    $http->useCurl(TRUE);
                } else {
                    $http->useCurl(false);
                }
                //  define method POST/GET
                $http->setMethod('GET');

                // add authentication parameters
                //$http->addParam('user_name' , 'yourusername');
                //$http->addParam('password'  , 'yourpassword');

                //  set referrer
                $_referer = $_SERVER["HTTP_REFERER"];
                $http->setReferrer($_referer);
                // set user agent
                $http->setUseragent($user_agent);
                //  set timeout
                $http->setTimeout($fsocket_timeout);
                //  set max. redirections to be followed
                //$http->setMaxredirect("3");

                // enable third authentication
                $http->setAuth($user3, $pwd3);
                //  now execute the URL to be indexed with third authentication
                $http->execute($s_url);
                // error ?
                $error = $http->error;
                if (!$error) {
                    //  fetch first executed header
                    $headers = $http->headers;
//echo "\r\n\r\n<br>headers array:<br><pre>";print_r($headers);echo "</pre>\r\n";
                } else {
                    if (stripos($error, "network_getaddress")) {
                        $status['state'] = "NOHOST";
                        return $status;
                    } else {
                        $status['state'] = $error;
                    }
                }       //  end auth1
                preg_match("@http(.*?)(\d{3})@i", $headers[0], $code);
            }

            $status['state']    = $code[2];
            $file               = '';
            $redir              = '';
            $local_redir        = '';
            $relocated          = '1';

            if ($code[2] == 200 || $code[2] == 301 || $code[2] == 302) {

                $file = $http->result; //   read the contents
//echo "\r\n\r\n<br /> file0: '$file'<br />\r\n";
                if ($file) {
                    //  in order to prevent infinite indexation, try to find domain parking and abort for this URL
                    $top_file = substr($file, 0, 4000);
                    if (preg_match("/<script(.*?)(src|href) *= *['\"](.*?)(domainpark|domain-park)(.*?)['\"](.*?)<\/script>/si", $top_file, $regs)) {
                        if ($regs[4]) {
                            $status['state']    = "Domain parking detected, which is not supported by Sphider-plus. Indexation aborted for this URL";
                            return $status;
                        }
                    }

                    //  several webmasters do send page content PLUS redirections like HTTP301 or HTTP302
                    //  e.g. to be found at https://www.bcrlocuinte.ro/   and     http://www.dorinvest.ro/
                    //  so we need to overwrite the header and eventually the redireced URL for further processing
                    if (stristr($headers[0], "301") || stristr($headers[0], "302")) {

                        $redir  = '';
                        $redir0 = '';
                        $redir1 = '';

                        foreach($headers as $row) {
                            //  get any  relocation/redirection. Could be 'Location: . . .' OR 'Content-location: . . . ' as part of the header
                            if (preg_match("/^location: *([^\n\r ]+)/i", $row, $regs)) {
                                $redir0 = $regs[1];
                            }
                            if (!$redir0 && preg_match("/location: *([^\n\r ]+)/i", $row, $regs)) {
                                $redir0 = $regs[1];
                            }

                            if($redir0) {
                                //  check for already indexed link URL
                                $redir01    = urlencode($redir0);
                                $known_link = '';
                                $sql_query      = "SELECT * from ".$mysql_table_prefix."links where url='$redir01'";
                                $result     = $db_con->query($sql_query);
                                if ($debug && $db_con->errno) {
                                    $file       = __FILE__ ;
                                    $function   = __FUNCTION__ ;
                                    $err_row    = __LINE__-5;
                                    mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                                }
                                $known_link = $result->num_rows;

                                if ($known_link) {
                                    $status['state']    = "URL was redirected to an already indexed page.<br />In order to prevent infinite indexation, this is not supported by Sphider-plus.<br />Indexation aborted for this URL";
                                    return $status;
                                }

                                $redir = url_purify($redir0, $url, $can_leave_domain, 1, $relocated, $local_redir);   //  prefer 'Location: . . .'

                                if (!$redir) {
                                    $status['state']    = "Server tried to redirect to a non acceptable URL ( $redir0 )<br />Indexation aborted for this URL";
                                    return $status;
                                }
                                $redir1 = urlencode($redir);
                            }
                        }

                        if ($redir1 == urlencode($url)) {
                            $redir = '';        //  redirected in it selves
                            $status['state']    = "Server tried to overwrite a redirected URL in it selves.<br />In order to prevent infinite indexation, this is not supported by Sphider-plus.<br />Indexation aborted for this URL";
                            return $status;
                        }
                        //  check again for already indexed link URL
                        $known_link = '';
                        $sql_query      = "SELECT * from ".$mysql_table_prefix."links where url='$redir1'";
                        $result     = $db_con->query($sql_query);
                        if ($debug && $db_con->errno) {
                            $file       = __FILE__ ;
                            $function   = __FUNCTION__ ;
                            $err_row    = __LINE__-5;
                            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                        }
                        $known_link = $result->num_rows;

                        if ($known_link) {
                            $status['state']    = "URL was redirected to an already indexed page.<br />In order to prevent infinite indexation, this is not supported by Sphider-plus.<br />Indexation aborted for this URL";
                            return $status;
                        }



                        $headers[0]         = "HTTP/1.1 200 OK";    //  let's try to index this content
                        $status['state']    = "ok";
                        if ($redir) {
                            $status['url_over'] = $redir;           //  in case we additionally received the redirected URL for this content
                            $redir              = '';
                        }
                    }

                    if (!$status['url_over'] && $homepage == '1' && strlen($file) < '3000') {
                        //  try to find redirection
                        $redir = get_redirections($file, $url, $can_leave_domain, $relocated, $local_redir) ;

                    }
                    $raw_file = $file;

//echo "\r\n\r\n<br /> file0 in url_status(): '$file'<br />\r\n";
                    //  clean useless parts of the content
                    $file = preg_replace("@<style[^>]*>.*?<\/style>@si", " ", $file);
                    $file = str_replace ("encoding: ''", " ", $file);        //  yes, I've seen such nonsense !
                    $status['file'] = $file;
                }
            }

            array_shift($headers);

            foreach($headers as $row) {
                $redir0 = '';
                $redir1 = '';

                if (!$file) {
                    //  get any other relocation/redirection. Could be 'Location: . . .' OR 'Content-location: . . . ' as part of the header
                    if (preg_match("/^location: *([^\n\r ]+)/i", $row, $regs)) {
                        $redir0 = $regs[1];
                    }
                    if (preg_match("/location: *([^\n\r ]+)/i", $row, $regs)) {
                        $redir1 = $regs[1];
                    }

                    if($redir0 || $redir1) {
                        if ($redir0) {
                            $redir = url_purify($redir0, $url, $can_leave_domain, 1, $relocated, $local_redir);   //  prefer 'Location: . . .'
                        } else {
                             $redir = url_purify($redir1, $url, $can_leave_domain, 1, $relocated, $local_redir);
                        }
                    }
                }

                if ($redir) {
                    //  do not accept redirection in itselves. This might end in an infinite indexation
                    if ($redir) {
                        $end  = strpos($redir, ";");    //  yes, some webmaster include 2 URls into the redirect. Separated by a semicolon
                        if ($end) {
                            $redir = substr($redir, 0, $end);
                        }
                        $status['state']    = $code[2];
                        if($wait != '') {   // if we met a refresh meta tag
                            $status['relocate'] = "Redirected by refresh Meta tag after $wait seconds to: ";
                        } else {
                            //  care about non-exepted suffixes
							foreach ($ext as $excl){
                                if (preg_match("/\.$excl($|\?)/i", $redir) || stristr($redir, "xrds")){  //  if suffix is at the end of the link, or followd by a question mark
                                    $error = 'Not supported suffix in link name';
                                    $status['state'] = "Redirected by Meta tag or JavaScript to unsupported file: $redir => UFO ";
                                    return $status;
                                }
                            }
                            $status['relocate'] = "Redirected by Meta tag or JavaScript to: ";
                        }
                        $status['path']     = $redir;     //      URL redirected
                    }
                }

                //  get Last-Modified date
                if (preg_match("/(Date|Last-Modified): *([a-z0-9,: ]+)/i", $row, $regs)) {
                    $status['date'] = $regs[2];
                }

                //  get Content-Encoding like 'gzip'
                if (preg_match("/Content-Encoding: *([a-z0-9,: ]+)/i", $row, $regs)) {
                    $status['Content-Encoding'] = strtolower(trim($regs[1]));
                }

                //  get Transfer-Encoding like 'chunked'
                if (preg_match("/Transfer-Encoding: *([a-z0-9,: ]+)/i", $row, $regs)) {
                    $status['Transfer-Encoding'] = strtolower(trim($regs[1]));
                }

                //  get Content-Type and if available the charset
                if (preg_match("/Content-Type: *([a-z0-9,: ]+)/i", $row, $regs)) {
                    $status['Content-Type'] = $regs[1];
                    $content                = $row ;

                    if (strstr($row, ";")) {
                       $content = substr($row, 0, strpos($row, ";"));
                    }
                    if (preg_match("@charset=([a-z0-9,\- ]+)@i", $row, $regs)) {
                        $status['charset'] = strtoupper(trim($regs[1]));
                    }

                    //  some server do not send correct Content-Type. We need to assist them
                    $rest = substr($url, -3);
                    if($rest == "doc") {
                        $status['Content-Type'] = "application/msword";
                        $content                = "Content-Type: application/msword" ;
                    }
                }
            }

            if (stristr($content, "octet-stream")) { //  if the server did not send the 'real' content type
                $part_file = substr($raw_file, 0, 1000);
                //  get Content-Type and if available the charset from the file
                if (preg_match("/Content-Type: *([a-z0-9,\/ ]+);/i", $part_file, $regs)) {
                    $content                = str_replace(";", "", $regs[0]);
                    $status['Content-Type'] = $content;

                    if (preg_match("@charset=([a-z0-9,\- ]+)@i", $part_file, $regs)) {
                        $status['charset'] = strtoupper(trim($regs[1]));
                    }

                }
            }


            if (!$content && $status['url_over']) {
                $status['relocate'] = "Redirected by server header to: ";
                $status['path']     = $status['url_over'];     //      URL redirected
            }

            if ($status['file'] && $code[2] == 200 && $code[2] != 301  && $code[2] != 302  && $code[2] != 307) {
                $status['state'] = 'ok';
            }

        } else {

            if (stripos($error, "network_getaddress") || stristr($error, "nohost")) {
                $status['state']        = "NOHOST";
                //return $status;         //  emergency exit (to be used only for fast indaxation)
            }
            if (stristr($error, "ssl")) {
                $status['state'] = "NOHOST. ".$error;
                //return $status;         //  emergency exit
            }
            $status['state'] = $error;
        }
        $http-> clear();
        unset($http);

        if (!stripos($url, "robots.txt") && !strpos($status['state'], "relocation") && $status['state'] != "ok" && $status['state'] != "301" && $status['state'] != "302" && $status['state'] != "307"){
            if (isset ($urlparts['port'])) {
                $port = (int) $urlparts['port'];
            } else
            if ($urlparts['scheme'] == "http") {
                $port = 80;
            } else
            if ($urlparts['scheme'] == "https") {
                $port = 443;
            }

            if ($port == 80) {
                $portq = "";
            } else {
                $portq = ":$port";
            }

            if (substr($url, 0, 5) == "https") {
                $target = "ssl://".$host;
            } else {
                $target = $host;
            }

            $all                = "Accept-Encoding: 0";
            $auth               = sprintf("Authorization: Basic %s", base64_encode($user1 . ":" . $pwd1));
            $fsocket_timeout    = 60;
            $errno              = 0;
            $errstr             = "";
            //  request with first authorization
            $request1           = "GET $path HTTP/1.1\r\nHost: $host$portq\r\n$all\r\nUser-Agent: $user_agent\r\n$auth\r\n\r\n";

            ini_set("user_agent", $user_agent);

            $fp = @fsockopen($target, $port, $errno, $errstr, $fsocket_timeout);
            socket_set_timeout($fp, 60);

            //  we wil try to something from all  the header rows
            fputs($fp, $request1);
            $answer = @fgets($fp, 4096);    //  get the first row of the HTTP header
            $answer0 = $answer;             //  remember this first answer

            if (strpos($answer, "503")) {   //  temporary unreachable
                $retry      = '';
                $license    = '';

                if ($debug == "2") {
                    while ($answer) {
                        $answer = fgets($fp, 4096);
                        if (preg_match("/Retry-after: *([^\n\r ]+)/i", $answer, $regs)) {
                            $retry = $regs[0];
                        }
                        if (preg_match("/License status: *([^\n\r ]+)/i", $answer, $regs)) {
                            $license = $regs[0];
                            break;
                        }
                    }
                }
                //  prepare status message for HTTP 503
                $status['state'] = "Unreachable: HTTP 503 Service temporary unavailable<br />$retry<br />$license";
                $linkstate = "Unreachable";
            }

            if (strpos($answer, "500") && $browser_string) {  // try with standard browser http_user_agent (some servers do not like crawler)
                fclose($fp);    // close existing connection
                sleep(1);       //  might not be necessary to wait, but . . .

                $browser_agent      = "Mozilla/5.0 (Windows NT 6.1; rv:5.0) Gecko/20100101 Firefox/5.0";
                $browser_request    = "GET $path HTTP/1.1\r\nHost: $host$portq\r\n$all\r\nUser-Agent: $browser_agent\r\n$auth\r\n\r\n";

                $fsocket_timeout = 60;
                $errno = 0;
                $errstr = "";

                //try to re-connect
                $fp = @fsockopen($target, $port, $errno, $errstr, $fsocket_timeout);
                print $errstr;
                $linkstate = "ok";
                if (!$fp) {
                    $status['state'] = "NOHOST";
                } else {
                    fputs($fp, $browser_request);
                    $answer = fgets($fp, 4096);
                    ini_set("user_agent", $browser_agent);  //      overwrite $user_agent with $browser_agent
                }
            }

            //  some servers obligatory need a slash at the end of the path. We'll try it here
            //  some other server do not like the slash as last charachter of the path, lets follow also this quirk
            if ((strpos($answer, "301") || strpos($answer, "400") || strpos($answer, "404")) && !isset($urlparts['query'])) {  // try with slash at the end of host or path
                fclose($fp);    // close existing connection
                sleep(1);       //  might not be necessary to wait, but . . .

                if ($path != "/" && !strstr($path, ".")) {

                    //  if last charachter of $path isn't already a slash, add a slash at the end of the path
                    if (strrpos($path, "/") != strlen($path)-1) {
                        $path .="/";
                        $url = $urlparts['scheme']."://".$host."".$path.""; //  rebuild the URL, in case we need to fetch the file contents
                    }
                }

                $path = urlencode($path);
                $path = str_replace("%2F", "/", $path);

                $browser_agent  = "Mozilla/5.0 (Windows NT 6.1; rv:5.0) Gecko/20100101 Firefox/5.0";
                $request        = "GET $path HTTP/1.1\r\nHost: $host$portq\r\n$all\r\nUser-Agent: $user_agent\r\n$auth\r\n\r\n";

                $fsocket_timeout = 60;
                $errno = 0;
                $errstr = "";

                //try to re-connect
                $fp = @fsockopen($target, $port, $errno, $errstr, $fsocket_timeout);
                print $errstr;
                $linkstate = "ok";
                if (!$fp) {
                    $status['state'] = "NOHOST";
                } else {
                    fputs($fp, $request);
                    $answer = fgets($fp, 4096);

                }
                $status['path1'] = $path;   //  remember the corrected path, if we will try to get the file contents

                //  some other server do not like the slash as last charachter of the path, lets follow also this quirk
                if (strpos($answer, "404")) {
                    fclose($fp);    // close existing connection
                    sleep(1);       //  might not be necessary to wait, but . . .

                    //  if last charachter of $path is  a slash, remove the slash at the end of the path
                    if (strrpos($path, "/") == strlen($path)-1) {
                        $path = substr($path, 0, strlen($path)-1);
                        $url = $urlparts['scheme']."://".$host."".$path.""; //  rebuild the URL, in case we need to fetch the file contents
                    }

                    $browser_agent  = "Mozilla/5.0 (Windows NT 6.1; rv:5.0) Gecko/20100101 Firefox/5.0";
                    $request        = "GET $path HTTP/1.1\r\nHost: $host$portq\r\n$all\r\nUser-Agent: $user_agent\r\n$auth\r\n\r\n";

                    $fsocket_timeout = 60;
                    $errno = 0;
                    $errstr = "";

                    //try to re-connect
                    $fp = @fsockopen($target, $port, $errno, $errstr, $fsocket_timeout);
                    print $errstr;
                    $linkstate = "ok";
                    if (!$fp) {
                        $status['state'] = "NOHOST";
                    } else {
                        fputs($fp, $request);
                        $answer = fgets($fp, 4096);
                    }
                    $status['path1'] = $path;   //  remember the corrected path, if we will try to get the file contents
                    if (strpos($answer, "200")) {
                        $wfs = '';   //  remember to access this domain without final slash and overwrite the Admin settings
                    }
                }
            }

            if (strpos($answer, "401")) {    //  try without authorization (some servers do not like the $auth  annex)
                fclose($fp);
                $errno = 0;
                $errstr = "";
                $call = '1';
                $fp = @fsockopen($target, $port, $errno, $errstr, $fsocket_timeout);
                print $errstr;
                $linkstate = "ok";
                if (!$fp) {
                    $status['state'] = "NOHOST";
                } else {
                    $user   = $user1;
                    $pwd    = $pwd1;
                    $answer = auth_connect($fp, $user, $pwd, $path, $host, $portq, $call);
                }

                if (strpos($answer, "401")) {    //  try with second authorization
                    fclose($fp);
                    $errno = 0;
                    $errstr = "";
                    $call = '2';
                    $fp = @fsockopen($target, $port, $errno, $errstr, $fsocket_timeout);
                    print $errstr;
                    $linkstate = "ok";
                    if (!$fp) {
                        $status['state'] = "NOHOST";
                    } else {
                        $user   = $user2;
                        $pwd    = $pwd2;
                        $answer = auth_connect($fp, $user, $pwd, $path, $host, $portq, $call);
                    }
                }

                if (strpos($answer, "401")) {    //  try with third authorization
                    fclose($fp);
                    $errno = 0;
                    $errstr = "";
                    $call = '3';
                    $fp = @fsockopen($target, $port, $errno, $errstr, $fsocket_timeout);
                    print $errstr;
                    $linkstate = "ok";
                    if (!$fp) {
                        $status['state'] = "NOHOST";
                    } else {
                        $user   = $user3;
                        $pwd    = $pwd3;
                        $answer = auth_connect($fp, $user, $pwd, $path, $host, $portq, $call);
                    }
                }
            }

            $regs = Array ();
            if (preg_match("{HTTP/[0-9.]+ (([0-9])[0-9]{2})}i", $answer, $regs)) {
                $httpcode = $regs[2];
                $full_httpcode = $regs[1];

                if ($httpcode <> 2 && $httpcode <> 3) {
                    $status['state'] = "Unreachable: HTTP $full_httpcode";
                    $linkstate = "Unreachable";
                    $realnum -- ;
                }
            }

            $answer1 = $answer;

            //      this is the entry for usual response
            if ($linkstate <> "Unreachable" ) {

                $content = '';
                while ($answer && strlen($answer) > 2) {
                    $answer = fgets($fp, 4096);

                    //  get any relocation/redirection
                    if (preg_match("/location: *([^\n\r ]+)/i", $answer, $regs)) {
                        $status['path'] = $regs[1];     //      URL redirected
                        $status['relocate'] = "Redirected by HTTP $full_httpcode to ";
                    }

                    //  get Last-Modified date
                    if (preg_match("/(Date|Last-Modified): *([a-z0-9,: ]+)/i", $answer, $regs)) {
                        $status['date'] = $regs[2];
                    }

                    //  get Content-Encoding like 'gzip'
                    if (preg_match("/Content-Encoding: *([a-z0-9,: ]+)/i", $answer, $regs)) {
                        $status['Content-Encoding'] = strtolower(trim($regs[1]));
                    }

                    //  get Transfer-Encoding like 'chunked'
                    if (preg_match("/Transfer-Encoding: *([a-z0-9,: ]+)/i", $answer, $regs)) {
                        $status['Transfer-Encoding'] = strtolower(trim($regs[1]));
                    }

                    //  get Content-Type and if available Charset
                    if (preg_match("/Content-Type:/i", $answer)) {
                        $content = $answer;
                        if (preg_match("@charset=([a-z0-9,\- ]+)@i", $answer, $regs)) {
                        $status['charset'] = strtoupper(trim($regs[1]));
                        }
                    }
                }
/*
echo "\r\n\r\n<br /> content: '$content'<br />\r\n";
echo "\r\n\r\n<br /> linkstate: '$linkstate'<br />\r\n";
echo "\r\n\r\n<br /> answer022: '$answer1'<br />\r\n";
*/
                if (preg_match("/200/i", $answer1)) {
                    $status['state']    = 'ok';
                }
                if (!$answer1) {
//echo "\r\n\r\n<br>status Array:<br><pre>";print_r($status);echo "</pre>\r\n";
                    $code = $status['state'];
                    if ($code == "505") {
                        $code = "505 (Server does not support HTTP v.1.1)<br />";
                    }
                    $status['state'] = "Indexation aborted by HTTP code $code";
                    return $status;     //  return with status info
                }


                $file = file_get_contents($url);
//echo "\r\n\r\n<br /> file0: '$file'<br />\r\n";
                //  try to get the content without a slash at the end of the path
                if (!$file) {
                    if (!isset($urlparts['query']) && $urlparts['path'] != "/" && substr($url_status['path'], -1) == "/") {
                        $url1    = substr($url, 0, strlen($url)-1);
                        $url2    = str_replace("%2520", "%20", $url1);

                        $file   = file_get_contents($url2);
                    }
                }

                // try alternate method no. 3 to get the file content
                if (!$file) {
                    $get_charset    = '1';
                    $contents = getFileContents($url, $get_charset);
                    $file = $contents['file'];
                }

//echo "\r\n\r\n<br /> file2: '$file'<br />\r\n";
                //  convert gzip coded content into plain text
                if ($status['Content-Encoding'] == "gzip") {

                    $result = gz_decode($file, $status['Content-Encoding'], $status['Transfer-Encoding']);
                    if($result == "error_gz0") {
                        if ($debug == "2") {
                            $result = "Announced by the URL as gzip formatted content, it's not! We'll treat it as plain text";
                            printUrlStatus($result, $command_line, $no_log);
                        }
                    } else {
                        $file = $result;
                    }
                }


                if ($file) {
                    if ($homepage == '1' && strlen($file) < '3000') {
                        //  try to find redirections
                        $redir = get_redirections($file, $url, $can_leave_domain, $relocated, $local_redir) ;
//echo "\r\n\r\n<br /> redir nach function get_redirections(): '$redir'<br />\r\n";
                    }
                    //  clean useless parts of the content
                    $file = preg_replace("@<style[^>]*>.*?<\/style>@si", " ", $file);
                    $file = str_replace ("encoding: ''", " ", $file);        //  yes, I've seen such nonsense !
                    $status['file'] = $file;

                } else {
                    $status['state'] = "Unable to read the content of the file.<br />$url does not deliver any content.";
                }
/*
echo "\r\n\r\n<br /> answer0: '$answer0'<br />\r\n";
echo "\r\n\r\n<br /> answer1: '$answer1'<br />\r\n";
*/
                if ($redir) {
                    //  do not accept redirection in itselves. This might end in an infinite indexation
                    if ($redir) {
                        $end  = strpos($redir, ";");    //  yes, some webmaster include 2 URls into the redirect. Separated by a semicolon
                        if ($end) {
                            $redir = substr($redir, 0, $end);
                        }
                        $status['state']    = $code[2];
                        if($wait != '') {   // if we met a refresh meta tag
                            $status['relocate'] = "Redirected by refresh Meta tag after $wait seconds to: ";
                        } else {
                            //  care about non-exepted suffixes
							foreach ($ext as $excl){
                                if (preg_match("/\.$excl($|\?)/i", $redir) || stristr($redir, "xrds")){  //  if suffix is at the end of the link, or followd by a question mark
                                    $error = 'Not supported suffix in link name';
                                    $status['state'] = "Redirected by Meta tag or JavaScript to unsupported file: $redir => UFO ";
                                    return $status;
                                }
                            }
                            $status['relocate'] = "Redirected by Meta tag or JavaScript to: ";
                        }
                        $status['path']     = $redir;     //      URL redirected
//echo "\r\n\r\n<br><<<< status Array1 in redirection :<br><pre>";print_r($status);echo "</pre>\r\n";
                    }
                }


                //      relocated URL? So we need to overwrite the $status array and define the type of content
                if (!$file && $linkstate <> "Unreachable" && preg_match("/301|302|303|307/i", $answer0) && preg_match("/200/i", $answer1)) {
                    while ($answer1 && strlen($answer1) > 2) {
                        $answer1 = fgets($fp, 4096);

                        if (!$file) {
                            //  get any other relocation/redirection. Could be 'Location: . . .' OR 'Content-location: . . . ' as part of the header
                            if (preg_match("/^location: *([^\n\r ]+)/i", $answer1, $regs)) {
                                $redir0 = $regs[1];
                            }
                            if (preg_match("/location: *([^\n\r ]+)/i", $answer1, $regs)) {
                                $redir1 = $regs[1];
                            }

                            if($redir0 || $redir1) {
                                if ($redir0) {
                                    $redir = url_purify($redir0, $url, $can_leave_domain, 1, $relocated, $local_redir);   //  prefer 'Location: . . .'
                                } else {
                                     $redir = url_purify($redir1, $url, $can_leave_domain, 1, $relocated, $local_redir);
                                }
                            }
                        }

                        if ($redir) {
                            //  do not accept redirection in itselves. This might end in an infinite indexation
                            if ($redir) {
                                $end  = strpos($redir, ";");    //  yes, some webmaster include 2 URls into the redirect. Separated by a semicolon
                                if ($end) {
                                    $redir = substr($redir, 0, $end);
                                }
                                $status['state']    = $code[2];
                                if($wait != '') {   // if we met a refresh meta tag
                                    $status['relocate'] = "Redirected by refresh Meta tag after $wait seconds to: ";
                                } else {
                                    //  care about non-exepted suffixes
                                    reset($ext);
									foreach ($ext as $key => $excl){
                                        if (preg_match("/\.$excl($|\?)/i", $redir) || stristr($redir, "xrds")){  //  if suffix is at the end of the link, or followd by a question mark
                                            $error = 'Not supported suffix in link name';
                                            $status['state'] = "Redirected by Meta tag or JavaScript to unsupported file: $redir => UFO ";
                                            return $status;
                                        }
                                    }
                                    $status['relocate'] = "Redirected by Meta tag or JavaScript to: ";
                                }
                                $status['path']     = $redir;     //      URL redirected
//echo "\r\n\r\n<br><<<< status Array1 in redirection :<br><pre>";print_r($status);echo "</pre>\r\n";
                            }
                        }

                        //  get Last-Modified date
                        if (preg_match("/(Date|Last-Modified): *([a-z0-9,: ]+)/i", $answer1, $regs)) {
                            $status['date'] = $regs[2];
                        }

                        //  get Content-Encoding like 'gzip'
                        if (preg_match("/Content-Encoding: *([a-z0-9,: ]+)/i", $answer, $regs)) {
                            $status['encoding'] = $regs[1];
                        }

                        //  get Transfer-Encoding like 'chunked'
                        if (preg_match("/Transfer-Encoding: *([a-z0-9,: ]+)/i", $answer, $regs)) {
                            $status['Transfer-Encoding'] = $regs[1];
                        }

                        //  get Content-Type and if available Charset
                        if (preg_match("/Content-Type:/i", $answer1)) {
                            $status['content'] = $answer1;
                            $content = $answer1;
                            if (preg_match("@charset=([a-z0-9,\- ]+)@i", $answer1, $regs)) {
                                $status['charset'] = strtoupper(trim($regs[1]));
                            }
                        }

                        if ($content && $status['path']) {  //  these 2 conditions would be enough to index the relocated URL
                            $status['state']    = "ok";
                        }
                    }

                    //  if the relocated URL or the Content-Type could not be detected, we need to GET the complete header info from the remote server
                    if ($status['state'] != "ok") {
                        $header = array();
                        $header = get_headers($url);

                        foreach ($header as $value) {
                            if (preg_match("/location: *([^\n\r ]+)/i", $value, $regs)) {
                                $status['path'] = $regs[1];     //      URL redirected
                                $status['relocate'] = "Redirected by HTTP $full_httpcode to ";

                            }
                        }
                    }

                    //  if the relocated path is relative, add the calling URL
                    if (!stristr($status['path'], "ttp")) {
                    $url = substr($url, 0, strrpos($url, "/")+1);
                        $status['path'] = $url.$status['path'];
                    }

                    //  analyze the header
                    if ($header) {
                        //  check for multiple redirection
                        $i = '0';
                        foreach ($header as $value) {
                            if (preg_match("/HTTP\/(.*?)301|HTTP\/(.*?)302|HTTP\/(.*?)303|HTTP\/(.*?)307/i", $value)) {
                                $i++;
                            }
                        }

                        if ($i > "1") {
                            //      Example for requested cookie:     http://www.fogelplast.ru/
                            $status['state'] = "Multiple redirections, which is not supported by Sphider-plus version $plus_nr";
                        } else {
                            //  try to find the content type of the relocated URL
                            krsort ($header);
                            foreach ($header as $value) {
                                if (preg_match("/Content-Type: *([^\n\r ]+)/i", $value, $regs)) {
                                    $status['content']  = $regs[1]; //     content type
                                    $content            = $value;   //     content type
                                    //  get charset
                                    if (preg_match("@charset=([a-z0-9,\- ]+)@i", $regs[1], $charreg)) {
                                        $status['charset'] = strtoupper(trim($charreg[1]));
                                    }
                                    break;
                                }
                            }
                            //  check for valid file type in order to become indexed
                            foreach ($ext as $this_suffix) {
                                if (stristr($status['content'], $this_suffix)) {
                                    $status['state'] = "Not text or html";
                                }
                            }
                        }
                    }
                }

                if ($status['relocate'] && $status['path']) {  //  relocated in itself? Would cause infinite indexing
                    if ($url0 != $status['path']) {
                    $status['state']    = "ok";
                    } else {
                        $status['state']    = "Redirected in it selves. This might cause infinite indexation,<br />and is not supported by Sphider-plus version $plus_nr";
                        $status['relocate'] = '';
                    }
                }
            }
//echo "\r\n\r\n<br>status Array at the end of III :<br><pre>";print_r($status);echo "</pre>\r\n";
        }

    //  end row by row analyzing the header

		//  get charset, if not yet parsed
		if (!$status['charset']) {
			if (preg_match("@charset=([a-z0-9,\- ]+)@i", $status['file'], $regs)) {
				$status['charset'] = strtoupper(trim($regs[1]));
			}
		}

        //  prepare a readable message
        if ($status['state'] == "404") {
            $status['state'] = "Dead link detected by HTTP 404<br />";
        }

        // if Admin selected, remove session from relocated URL
        if ($status['state'] == "ok" && $strip_sessids == 1) {
            $status['path'] = remove_sessid($status['path']);
        }

        //  correct the Apache glitch (see http://tkurek.blogspot.de/2013/06/252f-instead-of-2f-in-url-apache.html)
        //  here done in order NOT to modify the Apache rewrite module add the NE flag
        $status['path']     = str_replace("%252F", "%2F", $status['path']);
        $status['path1']    = str_replace("%252F", "%2F", $status['path1']);

        if ($status['state'] == "ok") {
            $socket_status = @socket_get_status($fp);
            @fclose($fp);

//echo "\r\n\r\n<br /> content final: '$content'<br />\r\n";
            if ($content) {     //  if the server sent any info about Content-Type in header enter here
                //  re-read the contents, because executed by the header, sometimes fails (e.g. for .docx files)
                if(!stristr($content, "text/")) {
                    if ($file = file_get_contents($url)) {
                        $status['file'] = $file;
                    }
                }

                if (preg_match("{Content-Type: *([a-z/.-]*)}i", strtolower($content), $regs)) {
                    if ($regs[1] == 'text/html' || $regs[1] == 'text/' || $regs[1] == 'text/plain') {
                        $status['content'] = 'text';
                        $status['state'] = 'ok';

                    } else if ($regs[1] == 'application/pdf' && $index_pdf == 1) {
                        $status['content'] = 'pdf';
                        $status['state'] = 'ok';

                    } else if ($regs[1] == 'application/pdf' && $index_pdf == 0) {
                        $status['content'] = 'pdf';
                        $status['state'] = 'Indexing of PDF files is not activated in Admin Settings';

                    } else if (($regs[1] == 'application/msword' || $regs[1] == 'application/vnd.ms-word') && $index_doc == 1) {
                        $status['content'] = 'doc';
                        $status['state'] = 'ok';
                    } else if (($regs[1] == 'application/msword' || $regs[1] == 'application/vnd.ms-word') && $index_doc == 0) {
                        $status['content'] = 'doc';
                        $status['state'] = 'Indexing of DOC files is not activated in Admin Settings';

                    } else if (($regs[1] == 'text/rtf') && $index_rtf == 1) {
                        $status['content'] = 'rtf';
                        $status['state'] = 'ok';
                    } else if (($regs[1] == 'text/rtf') && $index_rtf == 0) {
                        $status['content'] = 'rtf';
                        $status['state'] = 'Indexing of RTF files is not activated in Admin Settings';

                    } else if (($regs[1] == 'application/excel' || $regs[1] == 'application/vnd.ms-excel') && $index_xls == 1) {
                        $status['content'] = 'xls';
                        $status['state'] = 'ok';
                    } else if (($regs[1] == 'application/excel' || $regs[1] == 'application/vnd.ms-excel') && $index_xls == 0) {
                        $status['content'] = 'xls';
                        $status['state'] = 'Indexing of XLS files is not activated in Admin Settings';

                    } else if (($regs[1] == 'text/csv') && $index_csv == 1) {
                        $status['content'] = 'csv';
                        $status['state'] = 'ok';
                    } else if (($regs[1] == 'text/csv') && $index_csv == 0) {
                        $status['content'] = 'csv';
                        $status['state'] = 'Indexing of CSV files is not activated in Admin Settings';

                    } else if (($regs[1] == 'application/mspowerpoint' || $regs[1] == 'application/vnd.ms-powerpoint') && $index_ppt == 1) {
                        $status['content'] = 'ppt';
                        $status['state'] = 'ok';
                    } else if (($regs[1] == 'application/mspowerpoint' || $regs[1] == 'application/vnd.ms-powerpoint') && $index_ppt == 0) {
                        $status['content'] = 'ppt';
                        $status['state'] = 'Indexing of PPT files is not activated in Admin Settings';

                    } else if (($regs[1] == 'application/vnd.openxmlformats-officedocument.presentationml.presentation') && $index_ppt == 1) {
                        $status['content'] = 'ppt';
                        $status['state'] = 'ok';
                    } else if (($regs[1] == 'application/vnd.openxmlformats-officedocument.presentationml.presentation') && $index_ppt == 0) {
                        $status['content'] = 'ppt';
                        $status['state'] = 'Indexing of PPT files is not activated in Admin Settings';

                    } else if (($regs[1] == 'application/xml' || $regs[1] == 'application/rss' || $regs[1] == 'text/xml') && $index_rss == 1) {
                        $status['content'] = 'xml';
                        $status['state'] = 'ok';
                    } else if (($regs[1] == 'application/xhtml' || $regs[1] == 'application/rss' || $regs[1] == 'text/xhtml' || $regs[1] == 'application/xhtml') && $index_rss == 1) {
                        $status['content'] = 'xhtml';
                        $status['state'] = 'ok';
                    } else if (($regs[1] == 'application/xml' || $regs[1] == 'application/rss' || $regs[1] == 'text/xml' || $regs[1] == 'text/xhtml' || $regs[1] == 'application/xhtml') && $index_rss == 0) {
                        $status['content'] = 'xml';
						if ($index_xml_pr != 1){
							$status['state'] = '<br />Indexing of RDF, RSD, RSS, Atom and XML product feeds is not activated in Admin Settings<br />';
						} else {
							$status['state'] = 'ok';	//	hopefully
						}
                    } else if (($regs[1] == 'application/zip' || $regs[1] == 'zip') && $index_zip == 1) {
                        $status['content'] = 'zip';
                        $status['state'] = 'ok';
                    } else if (($regs[1] == 'application/zip' || $regs[1] == 'zip') && $index_zip == 0) {
                        $status['content'] = 'zip';
                        $status['state'] = '<br />Indexing of ZIP archives is not activated in Admin Settings';

                    } else if (($regs[1] == 'application/rar' || $regs[1] == 'application/x-rar-compressed') && $index_rar == 1) {
                        $status['content'] = 'rar';
                        $status['state'] = 'ok';
                    } else if (($regs[1] == 'application/rar' || $regs[1] == 'application/x-rar-compressed') && $index_rar == 0) {
                        $status['content'] = 'rar';
                        $status['state'] = '<br />Indexing of RAR archives is not activated in Admin Settings';

                    } else if (($regs[1] == 'application/vnd.oasis.opendocument.spreadsheet') && $index_ods == 1) {
                        $status['content'] = 'ods';
                        $status['state'] = 'ok';
                    } else if (($regs[1] == 'application/vnd.oasis.opendocument.spreadsheet') && $index_ods == 0) {
                        $status['content'] = 'ods';
                        $status['state'] = '<br />Indexing of OpenDocument<strong>Spreadsheet</strong> is not activated in Admin Settings';

                    } else if (($regs[1] == 'application/vnd.oasis.opendocument.text') && $index_odt == 1) {
                        $status['content'] = 'odt';
                        $status['state'] = 'ok';
                    } else if (($regs[1] == 'application/vnd.oasis.opendocument.text') && $index_odt == 0) {
                        $status['content'] = 'odt';
                        $status['state'] = '<br />Indexing of OpenDocument<strong>Text</strong> is not activated in Admin Settings';

                    } else if (($regs[1] == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') && $index_docx == 1) {
                        $status['content'] = 'docx';
                        $status['state'] = 'ok';
                    } else if (($regs[1] == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') && $index_docx == 0) {
                        $status['content'] = 'docx';
                        $status['state'] = '<br />Indexing of <strong>docx</strong> files is not activated in Admin Settings';

                    } else if (($regs[1] == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') && $index_xlsx == 1) {
                        $status['content'] = 'xlsx';
                        $status['state'] = 'ok';
                    } else if (($regs[1] == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') && $index_xlsx == 0) {
                        $status['content'] = 'xlsx';
                        $status['state'] = '<br />Indexing of <strong>xlsx</strong> files is not activated in Admin Settings';

                    } else if (stripos ($urlparts['path'], ".js") || $regs[1] == 'application/javascript') {
                        $status['content'] = 'js';
                        $status['state'] = 'ok';

                    } else {
                        $status['state'] = "<br />For Sphider-plus v.$plus_nr not executable text or media.<br /> $content&nbsp;&nbsp;&nbsp;=>&nbsp;&nbsp;&nbsp;UFO file<br />";
                        $status['content'] = 'ufo';
                        $realnum -- ;
                    }

                } else {
                    if ($socket_status['timed_out'] == 1) {
                        $status['state'] = "Timed out. URL: $url0 <br />No reply from server within $fsocket_timeout seconds.";
                        $realnum -- ;
                    } else {
                        $status['state'] = "Not text or html";
                    }
                }
            } else {

                $overparts = parse_url($status['url_over']);
                $pathparts = parse_url($status['path']);

                if (stristr($overparts['path'], ".ashx") || stristr($pathparts['path'], ".ashx")) {
                    $status['state'] = "<br />For Sphider-plus v.$plus_nr not executable content.<br />File suffix ASHX for ASP.NET Web Handler Files is not supported.<br />";

                } else {
                    $status['state'] = "<br />For Sphider-plus v.$plus_nr not executable content.<br />Server did not send any info about content type<br />";
                }
                $realnum -- ;
            }
        }

        if ($clear == 1) {
            unset ($urlparts, $answer);
            $socket_status = array();
        }
//echo "\r\n\r\n<br>status array final:<br><pre>";print_r($status);echo "</pre>\r\n";
        return $status;
    }

    function check_robot_txt($url, $robots) {
        global $db_con, $user_agent, $clear, $cl;

        $urlparts = parse_addr($url);

        if ($urlparts['host'] == 'localhost') {     //  for 'localhost' applications add the path until last slash
            $loc_path = substr($urlparts['path'], 0, strrpos($urlparts['path'], '/'));
            $url = $urlparts['scheme'].'://'.$urlparts['host']."".$loc_path."/$robots";
        } else {    //      Internet
            $url = $urlparts['scheme'].'://'.$urlparts['host']."/$robots";
        }

        $url_status = url_status($url);
        $omit = array ();

        if ($url_status['state'] == "ok") {
            $file = @file_get_contents($url);
            $robot = explode("\n", $file);
            if (!$robot) {
                $get_charset    = '';
                $contents = getFileContents($url, $get_charset);    //  read the robots.txt file
                $file = $contents['file'];
                $robot = explode("\n", $file);
            }

            //  check for invalid content in robots.txt
            if (stristr($file, "Disallow:<!--") || stristr($file, "<script") ) {
                $domain = str_replace($robots, "", $url);
                printBadRobots($domain, $cl);

            } else {
                //  robots.txt seems okay, now parse it
                $regs = Array ();
                $this_agent= "";
				foreach ($robot as $line){
                    if (preg_match("/^user-agent: *([^#]+) */i", $line, $regs)) {
                        $this_agent = trim($regs[1]);
                        if ($this_agent == '*' || $this_agent == $user_agent)
                        $check = 1;
                        else
                        $check = 0;
                    }

                    if (preg_match("/disallow: *([^#]+)/i", $line, $regs) && $check == 1) {
                        $disallow_str = rawurldecode(preg_replace("/[\n ]+/i", "", $regs[1])); //  make readable the %BO%D1 coded URLs
                        if (trim($disallow_str) != "") {
                            if ($urlparts['host'] == 'localhost') {     //  for 'localhost' applications add the path until last slash
                                $omit[] = "".$loc_path."".$disallow_str."";
                            } else {        //      www application
                                $omit[] = $disallow_str;
                            }
                        } else {
                            if ($this_agent == '*' || $this_agent == $user_agent) {
                                if ($clear == 1) unset ($urlparts, $contents, $file, $robot, $regs);
                                return null;
                            }
                        }
                    }
                }
            }
        }

        if ($clear == 1) unset ($urlparts, $contents, $file, $robot, $regs);
        return $omit;       //     array that holds all forbidden links from robots.txt
    }

    // Remove the file part from url (to build the url from a url and given relative path)
    function remove_file_from_url($url) {

        $url_parts = parse_addr($url);
        $path = $url_parts['path'];
        $path = str_replace("+", "", $path);    //  as not cooperating with preg_replace

        $regs = Array ();
        //if (preg_match('/([^\/]+)$/i', $path, $regs)) {
        if (preg_match('/([^\/]+)$/i', $path, $regs)) {
            $file = $regs[1];
            $check = $file.'$';
            $path = preg_replace("/$check"."/i", "", $path);
        }

        if ($url_parts['port'] == 80 || $url_parts['port'] == "") {
            $portq = "";
        } else {
            $portq = ":".$url_parts['port'];
        }

        $url = $url_parts['scheme']."://".$url_parts['host'].$portq.$path;

        unset ($url_parts, $regs, $file);
        return $url;
    }

    function get_redirections($file, $url, $can_leave_domain, $relocated, $local_redir) {
        global $db_con, $js_reloc, $refresh_delay;

        $regs       = array();
        $relocated  = 1;

        $file = str_replace("\r\n", " ", $file);
        $redir      = '';
        $headdata   = '';

        preg_match("@<head[^>]*>(.*?)<\/head>@si",$file, $regs);
        //  no headdata, if reload in it selves. Some webmaster try to bother crawler like Sphider-plus
        if (!stristr($regs[1], "location.reload")) {
            $headdata   = $regs[1];
        }

        //  redirections are to expected at the beginning of a file
        //  otherwise we might fetch JavaScript links and not only redirections
        if(stristr($file, "<body")) {
            $file = substr($file, 0, stripos($file, "<body")+500);
        } else {
            $file = substr($file, 0, 500);
        }

        // try to find redirections like: document.location.href = '. . . . '    and     document.location.href = '. . . . ' , which might be placed above the head tag
        if ($js_reloc && !$redir && preg_match("/<script(.*?)location[.]href *= *['\"](.*?)['\"](.*?)<\/script>/si", $file, $regs)) {
            if (strlen($regs[0]) < '300') {
                $redir      = url_purify($regs[2], $url, $can_leave_domain, 1, $relocated, $local_redir);
            }
        }

        //  try to find redirections in JavaScript
        if ($js_reloc) {
            $file_js = $file;     //  remember it
            //  kill this, as it might cause infinite indexation
            $file = preg_replace("@\(loadcount.*?\}@si", " ", $file);
            $file = preg_replace("@<script.*?location[.]pathname.*?<\/script>@si", " ",$file);
            $file = preg_replace("@if *(.*? ==.*?)@si", " ",$file);
            //  remove noscript tags from the head, which might misguide the indexer. But only for $file in this function here !!!!
            $file = preg_replace("@<noscript>.*?</noscript>@si", " ",$file);    //   we will need it in $file in order to find links

            // try to find redirections like: location.href = '. . . . '
            if ($headdata && !$redir && preg_match("/(location[.]href) *= *['\"](.*?)['\"]/si", $headdata, $regs)) {
                if (strlen($regs[0]) < '50') {
                    $redir      = url_purify($regs[2], $url, $can_leave_domain, 1, $relocated, $local_redir);
                }
            }

            // try to find redirections like: location.href = '. . . . '
            if ($headdata && !$redir && preg_match("/<script(.*?)location *= *['\"](.*?)['\"](.*?)<\/script>/si", $headdata, $regs)) {
                if (strlen($regs[0]) < '300') {
                    $redir      = url_purify($regs[2], $url, $can_leave_domain, 1, $relocated, $local_redir);
                }
            }

            // try to find redirections like: window.location = . . . language. . .
            if ($headdata && !$redir && preg_match("/(window[.]location) *=(.*?)['\"](.*?)[\'\" ]/i", $headdata, $regs)){
                if (strlen($regs[0]) < '50') {
                    if(strstr($regs[2], "lang")) {  //  try to find language support and its subfolder
                        // fetch the currently valid Admin language
                        $cc = "en";
                        if ( isset ( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
                            $cc = substr( htmlspecialchars($_SERVER['HTTP_ACCEPT_LANGUAGE']), 0, 2);
                        }
                        $string = substr($headdata, 0, strpos($headdata, $regs[3]));
                        if (preg_match_all("/lang *= *[\'\"](.*?)[\'\"]/i", $string, $langs, PREG_SET_ORDER)) {
                           $lang = $langs[0][1];       //  might be dafault language, if nothing else matches
                            foreach ($langs as $val) {
                                if($cc == $val[1]){     //  if Admin language matches
                                    $lang = $cc;
                                }
                            }
                        } else {
                            $lang = "en";   //  assuming English to be the default language
                        }

                        $redirection    = "/$lang$regs[3]";
                        $redir          = url_purify($redirection, $url, $can_leave_domain, 1, $relocated, $local_redir);
                    } else {    //  pure redirect, without language selection
                        $redir      = url_purify($regs[3], $url, $can_leave_domain, 1, $relocated, $local_redir);
                    }

                }
            }

            // try to find redirections like: window.location = . . . .  AND " + location.host + "
            if ($headdata && !$redir && preg_match("/(window[.]location) *= *['\"](.*?)(.*?)['\"](.*?)[\'\"](.*?)[\'\"]/si", $headdata, $regs)) {
                if (strlen($regs[0]) < '50') {
                    if (stristr($regs[4], "location.host")) {
                        $reloc = $regs[3]."".$host."".$regs[5]."";
                        $redir = url_purify($reloc, $url, $can_leave_domain, 1, $relocated, $local_redir);
                    }
                }
            }

            // try to find redirections like: var URL =  '. . . . '
            if ($headdata && !$redir && preg_match("/var URL *= *['\"](.*?)['\"]/si", $headdata, $regs)) {

                if (strlen($regs[1]) < '50') {
                    $redir      = url_purify($regs[1], $url, $can_leave_domain, 1, $relocated, $local_redir);

                }
            }

            // try to find redirections like: location.replace = '. . . . '
            //  yes, sometimes this is to be found without head tag. Thus we need to find it in $file
            if (!$redir && preg_match("/(location[.]replace) *[\(=] *['\"](.*?)['\"]/si", $file, $regs)) {
                if (strlen($regs[0]) < '50') {
                    $redir      = url_purify($regs[2], $url, $can_leave_domain, 1, $relocated, $local_redir);
                }
            }

            // try to find redirections like: window.location = '. . . . ' first attempt
            //  yes, sometimes this is to be found without head tag. Thus we need to find it in $file
            if (!$redir && preg_match("/(window[.]location) *=[ \(*]['\"](.*?)['\"]/si", $file, $regs)) {
                if (strlen($regs[0]) < '50') {
                    $redir      = url_purify($regs[2], $url, $can_leave_domain, 1, $relocated, $local_redir);
                }
            }

            // try to find redirections like: window.location = '. . . . ' second attempt
            if (!$redir && preg_match("/(window[.]location)(.*?)= *['\"](.*?)['\"]/si", $file_js, $regs)) {
                if (strlen($regs[0]) < '100') {
                    $redir      = url_purify($regs[3], $url, $can_leave_domain, 1, $relocated, $local_redir);
                }
            }

            // try to find redirections like: document.location = . . . "
            if (!$redir && preg_match("/(document[.]location) *= *['\"](.*?)[\'\"]/si", $file, $regs)) {
                if (strlen($regs[2]) < '50') {
                    $redir = url_purify($regs[2], $url, $can_leave_domain, 1, $relocated, $local_redir);
                }
            }

            // try to find redirections like: 'window.onload = function . . . .  '
            if (!$redir && preg_match("/(window[.]onload) *= *function(.*?)document.(.*?)[)|.]/si", $file, $regs)) {
                if (strlen($regs[0]) < '50') {
                    if ($regs[3]) {
                        $pattern = "name *= *['\"]$regs[3]['\"](.*?)action *= *['\"](.*?)['\"]>";
                        if (preg_match("/$pattern/si", $file, $regs)) {
                            $redir      = url_purify($regs[2], $url, $can_leave_domain, 1, $relocated, $local_redir);
                        }
                    }
                }
            }

            //  now redirections like '<script . . . var cURL = . . . .'
            if (!$redir && preg_match("@<script(.*?)var cURL *= *[\'\"](.*?)[\'\"](.*?)<\/script@si", $file, $regs)) {
                if ($regs[3]) {
                    $redir      = url_purify($regs[2], $url, $can_leave_domain, 1, $relocated, $local_redir);
                }
            }

            //  now redirections like 'document.location.replace'
            if (!$redir && preg_match("@<script(.*?)document.location.replace(.*?)[\'\"](.*?)[\'\"](.*?)<\/script@si", $file, $regs)) {
                if ($regs[3]) {
                    $redir      = url_purify($regs[3], $url, $can_leave_domain, 1, $relocated, $local_redir);
                }
            }

            //  now redirections like 'window.location.replace . . . . '
            if (!$redir && preg_match("@<script(.*?)window.location.replace(.*?)[\'\"](.*?)[\'\"](.*?)<\/script@si", $file, $regs)) {
                if ($regs[3]) {
                    $redir      = url_purify($regs[3], $url, $can_leave_domain, 1, $relocated, $local_redir);
                }
            }

            //  another attempt for  redirections like 'window.location.replace . . . . ' and try to get the redirected URL from the function inside JavaScript
            if (!$redir && preg_match("@<script(.*?)window[.]location[.]replace(.*?)[\(\'\"](.*?)[\)\'\"](.*?)</script@si", $file, $regs)) {
                if ($regs[3]) {
                    if (preg_match("@strUrl@si", $regs[3])) {
                        $found = $regs[3];
                        $substr = substr($regs[0], strrpos($regs[0], ":"), stripos($regs[0], $found));
                        preg_match("@[\(\'\"](.*?)[\)\'\"]@si", $substr, $new);
                        $regs[3] = $new[1];
                    }

                    $redir      = url_purify($regs[3], $url, $can_leave_domain, 1, $relocated, $local_redir);
                }
            }

            //  another attempt for  redirections like 'location.href . . . . ' and try to get the redirected URL from the function inside JavaScript
            //like to be found at http://www.consolidar.com.ar/
            if (!$redir && preg_match("@<script(.*?)location[.]href *= *(.*?)[\)\}\'\"](.*?)<\/script@si", $file, $regs)) {
                if ($regs[2]) {
                    $found = $regs[2];
                    $substr = substr($regs[0], stripos($regs[0], $found), stripos($regs[0], "function"));
                        preg_match("@[\(\'\"](.*?)[\)\'\"]@si", $substr, $new);
                    $regs[3] = $new[1];
                    $redir      = url_purify($regs[3], $url, $can_leave_domain, 1, $relocated, $local_redir);
                }
            }


             //      now links in 'top.location . . . '
            if (!$redir && preg_match("@<script(.*?)top[.]location *= *[\'\"](.*?)[\'\"](.*?)<\/script@si", $file, $regs)) {
                if ($regs[2]) {
                    $redir      = url_purify($regs[2], $url, $can_leave_domain, 1, $relocated, $local_redir);
                }
            }

            //      now links in 'self.location . . . '
            if (!$redir && preg_match("@<script(.*?)self[.]location(.*?)[\'\"](.*?)[\'\"](.*?)<\/script@si", $file_js, $regs)) {
                if ($regs[3]) {
                    $redir      = url_purify($regs[3], $url, $can_leave_domain, 1, $relocated, $local_redir);
                }
            }

            //      now links in 'parent.location . . . '
            if (!$redir && preg_match("@<script(.*?)parent[.]location(.*?)[\'\"](.*?)[\'\"](.*?)<\/script@si", $file_js, $regs)) {
                if ($regs[3]) {
                    $redir      = url_purify($regs[3], $url, $can_leave_domain, 1, $relocated, $local_redir);
                }
            }

            //  try to find links in '<body onLoad . . . javascript . . . location.replace'
            if (preg_match("@<body(.*?)onload(.*?)javascript(.*?)location.replace(.*?)[\'\"](.*?)[\'\"](.*?)>@si", $file_js, $regs)) {
                if ($regs[5]){
                    $redir      = url_purify($regs[5], $url, $can_leave_domain, 1, $relocated, $local_redir);
                }
            }
        }

        //  try to find 'onLoad . . . location= . . . ' redirections as part of the Body Meta tag
        if (!$redir && preg_match("/onLoad=(.*?)['\"](.*?)location=(.*?)['\"](.*?)[\'\"](.*?)[\'\"]/si", $file, $regs)) {
            if (strlen($regs[0]) < '200' && $regs[4] != ");") {
                $redir = url_purify($regs[4], $url, $can_leave_domain, 1, $relocated, $local_redir);
            }
        }

        //  try to find <BODY onLoad = "parent.location = 'home.asp'"> redirections as part of the Body Meta tag
        if (!$redir && preg_match("/<body *onLoad *=(.*?)['\"]parent[.]location *= *['\"](.*?)['\"]/si", $file, $regs)) {
            if (strlen($regs[0]) < '200' && $regs[4] != ");") {
                $redir = url_purify($regs[2], $url, $can_leave_domain, 1, $relocated, $local_redir);
            }
        }

        //  try to find <body onload="MM_goToURL('parent','http://www.semperconstantia.at/index.htm');return document.MM_returnValue"> redirections as part of the Body Meta tag
        if (!$redir && preg_match("/<body *onLoad *=(.*?)['\"]parent['\"] *, *['\"](.*?)['\"]/si", $file, $regs)) {

            if (strlen($regs[0]) < '200' && $regs[0] != ");") {
                $redir = url_purify($regs[2], $url, $can_leave_domain, 1, $relocated, $local_redir);
            }
        }

        //  try to find links in '<body onload . . . .window.open . . . '
        if (preg_match("@<body(.*?)onload(.*?)window[.]open *\([\'\"](.*?)[\'\"]\,(.*?)>@si", $file, $regs)) {
            if ($regs[3]){
                $redir      = url_purify($regs[3], $url, $can_leave_domain, 1, $relocated, $local_redir);
            }
        }

        //      check for 'HTTP-EQUIV= . . refresh . . content= . . .' links in  head Meta tags, using $file as source in order to fetch also the other
        if (!$redir && preg_match("/http-equiv=[\"']refresh[\"'] *content=[\"'](.*?); *url= *(.*?)[\"']/si", $file, $regs)) {
            $wait = '';
            if (strlen($regs[0]) < '200') {
                $wait   = $res[1];
                if ($refresh_delay) {
                    sleep($wait);       //  if we should wait for refresh time
                }
                $redir      = url_purify($regs[2], $url, $can_leave_domain, 1, $relocated, $local_redir);
            }
        }

        //  try to find HTTP-EQUIV="refresh" redirections as part of a body Meta tag (first attempt)
        if (!$redir && preg_match("/(http-equiv *= *['\"]refresh['\"] *content *= *[\'\"](.*?);) *url *= *(.*?)[\'\"]/si", $file, $regs)) {
            if (strlen($regs[0]) < '200') {
                $wait = $regs[2];
                if ($refresh_delay) {
                    sleep ($wait);   // before continuing,  wait for the refresh time
                }
                $redir = url_purify($regs[3], $url, $can_leave_domain, 1, $relocated, $local_redir);
            }
        }

        //  try to find HTTP-EQUIV="refresh" redirections as part of a Meta tag (second attempt)
        if (!$redir && preg_match("/http-equiv *= *refresh content *= *[\'\"](.*?); *url *= *(.*?)[\'\"]/si", $file, $regs)) {
            if (strlen($regs[0]) < '200') {
                $wait = $regs[1];
                if ($refresh_delay) {
                    sleep ($wait);   // before continuing,  wait for the refresh time
                }
                $redir = url_purify($regs[2], $url, $can_leave_domain, 1, $relocated, $local_redir);
            }
        }

        //  don't follow all these old IE specialties as a redirection
        if (preg_match("/ie[4-8]/i", $redir)) {
            $redir = '';
        }

       //j ust to verify the url_purify() results
        $url1 = str_replace( " ", "%20", $url);
        $url2 = str_replace("%2520", "%20", $url1);
        $red1 = str_replace( " ", "%20", $redir);
        $red2 = str_replace("%2520", "%20", $red1);
        if (urlencode($url2) == urlencode($red2)) {
            $redir = '';
        }

        if ($clear == 1)  unset($regs);
        return $redir;
    }

    // Extract links from html
    function get_links($file, $url, $can_leave_domain, $base, $media_links, $use_nofollow, $local_redir, $url_reloc, $charSet) {
        global $db_con, $strip_sessids, $imagelist, $audiolist, $videolist, $command_line, $no_log, $index_media, $homepage, $inst_dir, $install_dir;
        global $mainurl, $include_dir, $idna, $local, $index_rss, $index_alt, $lower_links, $debug, $ext, $js_reloc, $local_rfc, $no_cano;

        $chunklist = array ();
        // The base URL comes from either the meta tag or the current URL.
        if (!empty($base)) {
            $url = $base;
        }

        if (stripos($url, "localhost") && $local_rfc) {
            $url        = substr($url, 0, stripos($url, "localhost")+9);
            $local      = $url;
            $url_len    = strrpos($url, "/");
        }

        $links          = array ();
        $regs           = Array ();
        $checked_urls   = Array();
        $care_excl      = '1';   //  care file suffixed to be excluded
        $relocated      = '';    //  link is not relocated
        $window_loc     = '';
        $body           = '';
        $redir          = '';


        $file = str_replace("&lt;", "<", $file);
        $file = str_replace("&gt;", ">", $file);
		$file = purify_content($file);				// delete comments etc. from HTML

        if (!$js_reloc) {   //  if not required for indexing JavaScript links, delete all scripts from the content
            $file = preg_replace("@<script[^>]*?>.*?<\/script>@si", " ", $file);
            $file = str_ireplace("window.document", " ", $file);
            $file = str_ireplace("window.document.location.href", " ", $file);
            $file = str_ireplace("window.location", " ", $file);
            $file = str_ireplace("body onload", "body ", $file);
        }

        //  get the body part of the content, even for several bodies.
        //  Might happen if several frames are part of content file
        $body = substr($file, stripos($file, "<body"), strripos($file, "</body"));

        if (!$body) {
            $body = $file;  //  if no body tag was found, we need to use the complete file content
        }

        if ($homepage == '1' && strlen($file) < '3000') {
            //  try to find any other redirection, which will be treated as a new link
            $redir = get_redirections($file, $url, $can_leave_domain, $relocated, $local_redir);
            if ($redir) {
                $links[] = $redir;    //  add this redirection as a new link
            }
        }
//echo "\r\n\r\n<br>links array0:<br><pre>";print_r($links);echo "</pre>\r\n";
        if ($js_reloc) {
            //  try to find links in JavaScript src=. . .
            if (preg_match_all("@<script(.*?)src(.*?)=(.*?)[\'\"](.*?)[\'\"]@si", $body, $regs)) {
                foreach ($regs[4] as $val) {
                    if (($a = url_purify($val, $url, $can_leave_domain, 1, $relocated, $local_redir)) != '') {
                        $links[] = $a;    //  add this new link
                    }
                }
            }

            //  try to find links in JavaScript like 'http://..........' as well as 'https://.........'
            if (preg_match_all("@<script(.*?)<\/script>@si", $file, $scripts)) {
                $i = "0";
                foreach ($scripts[$i] as $cont) {
                    if (preg_match_all("@[\'\"]https|http(.*?)[\'\"]@si", $cont, $regs)) {
                        foreach ($regs[0] as $val) {
                            if (($a = url_purify($val1, $url, $can_leave_domain, 1, $relocated, $local_redir)) != '') {
                                $links[] = $a;    //  add this new link
                            }
                        }
                    }
                    $i++;
                }
            }

            //  1. attempt for JavaScript links => window.location  iin $val[2]   in rudimentarily content like to be found at http://www.cbc.gov.tw/ or multiple links at http://www.bsfn.co.kr/
            preg_match_all("/(location[.]href)[[:blank:]]*=[[:blank:]]*['\"](.*?)['\"]/si", $file, $regs, PREG_SET_ORDER);
            foreach ($regs as $val) {
                if ($checked_urls[$val[2]]!=1) {
                    if (($a = url_purify($val[2], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {
                        $links[]        = $a;    //  add links
                    }
                    $checked_urls[$val[2]] = 1;
                }
            }

            //  2. attempt for Script links => window.location to be found in in $val[1]
            preg_match_all("/(window[.]location)[[:blank:]]*=[[:blank:]]*[\'\"]?(([[a-z]{3,5}:\/\/(([.a-zA-Z0-9-])+(:[0-9]+)*))*([+:%\/?=&;\\\(\),._ a-zA-Z0-9-]*))(#[.a-zA-Z0-9-]*)?[\'\" ]?/i", $file, $regs, PREG_SET_ORDER);
            foreach ($regs as $val) {
                if ($checked_urls[$val[1]]!=1 && !isset ($val[4])) { //if nofollow is not set
                    if (($a = url_purify($val[1], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {
                        $links[]        = $a;    //  add links
                        $window_loc1    = 1;
                    }
                    $checked_urls[$val[1]] = 1;
                }
            }

            if (!$window_loc1) {
                //  3. attempt for JavaScript links => window.location  (now in $val[2] , without 'nofollow', and without $checked_urls)
                preg_match_all("/(window[.]location)[[:blank:]]*=[[:blank:]]*[\'\"]?(([[a-z]{3,5}:\/\/(([.a-zA-Z0-9-])+(:[0-9]+)*))*([+:%\/?=&;\\\(\),._ a-zA-Z0-9-]*))(#[.a-zA-Z0-9-]*)?[\'\" ]?/i", $file, $regs, PREG_SET_ORDER);
                foreach ($regs as $val) {
                    if ($checked_urls[$val[2]]!=1) {
                        //if (($a = url_purify($val[1], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {
                        if (($a = url_purify($val[2], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {
                            $links[]        = $a;    //  add links
                            $window_loc2    = 1;
                        }
                        $checked_urls[$val[2]] = 1;
                    }
                }
            }

            if (!$window_loc1 && !$window_loc2) {
                // 4. attempt for JavaScript links => window.document.location.href  (now in $val[2] )
                preg_match_all("/(window[.]document[.]location[.]href)[[:blank:]]*=[[:blank:]]*[\'\"]?(([[a-z]{3,5}:\/\/(([.a-zA-Z0-9-])+(:[0-9]+)*))*([+:%\/?=&;\\\(\),._ a-zA-Z0-9-]*))(#[.a-zA-Z0-9-]*)?[\'\" ]?/i", $file, $regs, PREG_SET_ORDER);
                foreach ($regs as $val) {
                    if ($checked_urls[$val[1]]!=1) {
                        //if (($a = url_purify($val[1], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {
                        if (($a = url_purify($val[2], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {
                            $links[] = $a;    //  add links
                            $window_loc3    = 1;
                        }
                        $checked_urls[$val[2]] = 1;
                    }
                }
            }

            if (!$window_loc1 && !$window_loc2 && !$window_loc3) {
                //  5. attempt for JavaScript links => window.document.location.replace  (now in $val[2] )
                preg_match_all("/(window[.]location[.]replace)[[:blank:]]*\([[:blank:]]*[\'\"]?(([[a-z]{3,5}:\/\/(([.a-zA-Z0-9-])+(:[0-9]+)*))*([+:%\/?=&;\\\(\),._ a-zA-Z0-9-]*))(#[.a-zA-Z0-9-]*)?[\'\" ]?/i", $file, $regs, PREG_SET_ORDER);
                foreach ($regs as $val) {
                    if ($checked_urls[$val[1]]!=1) {
                        //if (($a = url_purify($val[1], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {
                        if (($a = url_purify($val[2], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {
                            $links[] = $a;    //  add links
                            $window_loc4    = 1;
                        }
                        $checked_urls[$val[2]] = 1;
                    }
                }
            }

            if (!$window_loc1 && !$window_loc2 && !$window_loc3 && !$window_loc4) {
                //  Additional attempt for Script links => document.location.href to be found in in $val[2]
                preg_match_all("/(document[.]location[.]href)[[:blank:]]*=[[:blank:]]*[\'\"](.*?)[\'\" ]/i", $file, $regs, PREG_SET_ORDER);
                foreach ($regs as $val) {
                    if ($checked_urls[$val[2]] != 1) {
                        if (($a = url_purify($val[2], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {
                            $links[]        = $a;    //  add links
                            $document_loc1    = 1;
                        }
                        $checked_urls[$val[2]] = 1;
                    }
                }
            }

            preg_match_all("/(http-equiv=['\"]refresh['\"] *content=['\"][0-9]+;url)[[:blank:]]*=[[:blank:]]*[\'\"]?(([[a-z]{3,5}:\/\/(([.a-zA-Z0-9-])+(:[0-9]+)*))*([+:%\/?=&;\\\(\),._ a-zA-Z0-9-]*))(#[.a-zA-Z0-9-]*)?[\'\" ]?/i", $file, $regs, PREG_SET_ORDER);
            foreach ($regs as $val) {
                //  first version for  HTTP-EQUIV="Refresh" links
                if ($checked_urls[$val[1]]!=1 && !isset ($val[4])) { //if nofollow is not set

                    if (($a = url_purify($val[1], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {
                        $links[] = $a;    //  add links
                    }
                    $checked_urls[$val[1]] = 1;

                }
                //  second version to fetch HTTP-EQUIV="Refresh" links
                if (($a = url_purify($val[2], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {
                    $links[] = $a;    //  add links
                    $checked_urls[$val[2]] = 1;
                }
            }

            preg_match_all("/(window[.]open[[:blank:]]*[(])[[:blank:]]*[\'\"]?(([[a-z]{3,5}:\/\/(([.a-zA-Z0-9-])+(:[0-9]+)*))*([+:%\/?=&;\\\(\),._ a-zA-Z0-9-]*))(#[.a-zA-Z0-9-]*)?[\'\" ]?/i", $file, $regs, PREG_SET_ORDER);
            foreach ($regs as $val) {
                if ($checked_urls[$val[1]]!=1 && !isset ($val[4])) { //if nofollow is not set
                    if (($a = url_purify($val[1], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {
                        $links[] = rawurldecode($a);    //  add links
                    }
                    $checked_urls[$val[1]] = 1;
                }
            }
        }
//echo "\r\n\r\n<br>links Array1:<br><pre>";print_r($links);echo "</pre>\r\n";
        //  all further links are only searched in the body part of the  file content
        $body = preg_replace("@<script[^>]*?>.*?<\/script>@si", " ",$body); //  delete all scripts from the content

        if ($index_rss) {
            $body = preg_replace("@<link>|<url>@si", "<href=\"", $body);     //  convert all links to href=
            $body = preg_replace("@</link>|</url>@si", "\">", $body);
        }

        $body   = str_replace(" ", "%20", $body);    //  such blanks ' ' sometimes are to be found in links

		//found as part of Wordpress like <a href="javascript:delay('/museum/shockers/')">Shockers</a>
		//	currently not supported by Sphider-plus, so we delete it
		$body1 = str_ireplace("javascript:delay('", "", $body);
		$body1 = str_ireplace("')", "", $body1);

		preg_match_all("/href\s*=\s*[\'\"](.*?)(\"\%20|\'\%20|\">|\'>|\/a\">)(.*?)>/si", $body1, $regs, PREG_SET_ORDER);    //  Replaced in order to index links containing non-ASCII characters
        foreach ($regs as $val) {
            if ($use_nofollow == '0') {
                $val[2] = '';   //  temporary ignore 'nofollow' directive
            }

            if (strstr($val[2], "nofollow") && $debug){
                $report = "<br /><br />Found ".$val[1].", but <strong>nofollow</strong> flag is set.";
                printNofollowLink($report, $command_line, $no_log);
            }
        }

        foreach ($regs as $val) {
            if ($val[1] && $val[1] != "?" && $val[1] != " " && $val[1] != "%20") {  //  reject empty links and pure argument link, which would cause invalid url_purify()
                $ignore = '';
                if ($use_nofollow == '1' && (strstr($val[2], "nofollow"))) {
                    $ignore = '1';   //  temporary ignore 'nofollow' directive
                }

                if ($checked_urls[$val[1]]!=1 && $ignore == '') { //if nofollow is not set

                    //  create a link, which points back to the domain
                    if ($val[1] == "/") {
                        $main_url_parts = parse_all_url($mainurl);
                        $val[1] = $main_url_parts['scheme']."://".$main_url_parts['host']."/";
                    }

                    if (($a = url_purify($val[1], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {

                        $match_i = '0';
                        $match_a = '0';
                        $match_v = '0';

                        //  prevent self-linking for link pathes ending with and/or without final slash
                        //  and for relocated on it selves as detected in nurl_purify
                        if ($mainurl == $a || $a == "self") {
                            $a = '';
                        }

                        $a   = str_replace( " ", "%20", $a);    //  in order to find also links containing blanks.

                        if($index_media > 0 && $a){
                            if ($index_image == '1') {
                                $select  = $imagelist;
                                $match_i = valid_link($a, $select);
                            }
                            if ($index_audio == '1') {
                                $select  = $audiolist;
                                $match_a = valid_link($a, $select);
                            }
                            if ($index_video == '1') {
                                $select  = $videolist;
                                $match_v = valid_link($a, $select);
                            }
                        }

                        if ($a && $media_links == '0' && $match_i == '0' && $match_a == '0' && $match_v == '0') {
                            $links[] = $a;    //  find only non-media links
                        }
                        if ($a && $media_links == '1' && ($match_i == '1' || $match_a == '1' || $match_v == '1')) {
                            $links[] = $a;    //  find only media links
                        }
                    }
                    $checked_urls[$val[1]] = 1;
                }
            }
        }
//echo "\r\n\r\n<br>links Array2:<br><pre>";print_r($links);echo "</pre>\r\n";
        if (!count($links)) {
            //  try to parse HTML5 links like
            //  <a href=this_link.php> title for this link </a><br />
            preg_match_all("/href\s*=\s*(.*?)\s*>(.*?)>/si", $body, $regs, PREG_SET_ORDER);    //  Replaced in order to index links containing non-ASCII characters
            foreach ($regs as $val) {
//echo "\r\n\r\n<br>val Array HTML5:<br><pre>";print_r($val);echo "</pre>\r\n";
                if ($val[1]  && $val[1] != "?" && $val[1] != " " && $val[1] != "%20" && !preg_match("/\'|\"/", $val[1])) {
                    $ignore = '';
                    if ($use_nofollow == '1' && (strstr($val[2], "nofollow"))) {
                        $ignore = '1';   //  temporary ignore 'nofollow' directive
                    }

                    if ($checked_urls[$val[1]]!=1 && $ignore == '') { //if nofollow is not set

                        //  create a link, which points back to the domain
                        if ($val[1] == "/") {
                            $main_url_parts = parse_all_url($mainurl);
                            $val[1] = $main_url_parts['scheme']."://".$main_url_parts['host']."/";
                        }

                        if (($a = url_purify($val[1], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {

                            $match_i = '0';
                            $match_a = '0';
                            $match_v = '0';

                            //  prevent self-linking for link pathes ending with and/or without final slash
                            //  and for relocated on it selves as detected in nurl_purify
                            if ($mainurl == $a || $a == "self") {
                                $a = '';
                            }

                            $a   = str_replace( " ", "%20", $a);    //  in order to find also links containing blanks.

                            if($index_media > 0 && $a){
                                if ($index_image == '1') {
                                    $select  = $imagelist;
                                    $match_i = valid_link($a, $select);
                                }
                                if ($index_audio == '1') {
                                    $select  = $audiolist;
                                    $match_a = valid_link($a, $select);
                                }
                                if ($index_video == '1') {
                                    $select  = $videolist;
                                    $match_v = valid_link($a, $select);
                                }
                            }

                            if ($a && $media_links == '0' && $match_i == '0' && $match_a == '0' && $match_v == '0') {
                                $links[] = $a;    //  find only non-media links
                            }
                            if ($a && $media_links == '1' && ($match_i == '1' || $match_a == '1' || $match_v == '1')) {
                                $links[] = $a;    //  find only media links
                            }
                        }
                        $checked_urls[$val[1]] = 1;
                    }
                }
            }
        }
//echo "\r\n\r\n<br>links Array3:<br><pre>";print_r($links);echo "</pre>\r\n";

		//	try to parse JavaScript links like
		//	<a href="javascript:changePGM('/tw/tbo1/jsp/TBO1_ImportFormDownload.jsp');" onMouseOver="javascript:chgView('none');">進口表單下載</a>

        preg_match_all("@href=\"javascript:ch(.*?)\'(.*?)\'(.*?)\/a>@si", $body, $regs, PREG_SET_ORDER);
//echo "\r\n\r\n<br>regs Array JavaScript:<br><pre>";print_r($regs);echo "</pre>\r\n";
        foreach ($regs as $val) {
            if ($use_nofollow == '0') {
                $val[3] = '';   //  temporary ignore 'nofollow' directive
            }

            if (strstr($val[3], "nofollow") && $debug){
                $report = "<br /><br />Found ".$val[2].", but <strong>nofollow</strong> flag is set.";
                printNofollowLink($report, $command_line, $no_log);
            }
        }

        foreach ($regs as $val) {
            if ($val[2] && $val[2] != "?" && $val[2] != " " && $val[2] != "%20") {  //  reject empty links and pure argument link, which would cause invalid url_purify()
                $ignore = '';
                if ($use_nofollow == '1' && (strstr($val[3], "nofollow"))) {
                    $ignore = '1';   //  temporary ignore 'nofollow' directive
                }

                if ($checked_urls[$val[2]]!=1 && $ignore == '') { //if nofollow is not set

                    //  create a link, which points back to the domain
                    if ($val[2] == "/") {
                        $main_url_parts = parse_all_url($mainurl);
                        $val[2] = $main_url_parts['scheme']."://".$main_url_parts['host']."/";
                    }

                    if (($a = url_purify($val[2], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {

                        $match_i = '0';
                        $match_a = '0';
                        $match_v = '0';

                        //  prevent self-linking for link pathes ending with and/or without final slash
                        //  and for relocated on it selves as detected in nurl_purify
                        if ($mainurl == $a || $a == "self") {
                            $a = '';
                        }

                        $a   = str_replace( " ", "%20", $a);    //  in order to find also links containing blanks.

                        if($index_media > 0 && $a){
                            if ($index_image == '1') {
                                $select  = $imagelist;
                                $match_i = valid_link($a, $select);
                            }
                            if ($index_audio == '1') {
                                $select  = $audiolist;
                                $match_a = valid_link($a, $select);
                            }
                            if ($index_video == '1') {
                                $select  = $videolist;
                                $match_v = valid_link($a, $select);
                            }
                        }

                        if ($a && $media_links == '0' && $match_i == '0' && $match_a == '0' && $match_v == '0') {
                            $links[] = $a;    //  find only non-media links
                        }
                        if ($a && $media_links == '1' && ($match_i == '1' || $match_a == '1' || $match_v == '1')) {
                            $links[] = $a;    //  find only media links
                        }
                    }
                    $checked_urls[$val[2]] = 1;
                }
            }
        }

//echo "\r\n\r\n<br>links Array4:<br><pre>";print_r($links);echo "</pre>\r\n";

        preg_match_all("/(frame[^>]*src[[:blank:]]*)=[[:blank:]]*[\'\"]?(([[a-z]{3,5}:\/\/(([.a-zA-Z0-9-])+(:[0-9]+)*))*([+:%\/?=&;\\\(\),._ a-zA-Z0-9-]*))(#[.a-zA-Z0-9-]*)?[\'\" ]?/i", $body, $regs, PREG_SET_ORDER);
        foreach ($regs as $val) {
            if ($checked_urls[$val[1]]!=1 ) { //    if nofollow is not set
                //if (($a = url_purify($val[1], $url, $can_leave_domain, '1')) != '') {      //modified in order to follow frame links Tec 23.03.2009
                if (($a = url_purify($val[2], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {

                   // for our Galizien webmaster, who ignores W3C rules
                    if (stristr($a, "%20style")) {
                        $a = substr($a, 0, stripos($a, "%20style"));
                    }

                    $links[] = $a;    //  find only media links
                }
                $checked_urls[$val[1]] = 1;
            }
        }

        //  find invalid links for localhost application
        if (strstr($url, "localhost") && !$can_leave_domain && !$local_rfc) {

            $local_links    = array();
            $pre            = strlen($local);   //  path length to the localhost URLs
//echo "\r\n\r\n<br /> pre: '$pre'<br />\r\n";
            foreach ($links as $thislink) {
                //  if $url contains another slash behind $pre, there must be a subfolder
                if (strstr($url, "/", $pre)) {
                    //  extract the path (folder name) of parent URL
                    $url_len = strpos($url, "/", $pre);  //  find first slash behind $pre
                    $dom = substr($url, $pre);
                    $dom = substr($dom, 0, strpos($dom, "/"));
                    //if (strlen($thislink) > $url_len && strstr($thislink, $dom)) {
                    if (strlen($thislink) > $url_len && strstr($thislink, $dom)) {
                        $local_links[] = $thislink;
                    }
                } else {    //  direct link at $local
                    if (strlen($thislink) > $url_len) {
                        $local_links[] = $thislink;
                    }

                }
                $links = $local_links;
            }
        }
//echo "\r\n\r\n<br>links Array:<br><pre>";print_r($links);echo "</pre>\r\n";
        if (strstr($url, "localhost") && $local_rfc) {
            foreach ($links as $thislink) {
                $local_links[] = $thislink;
            }
            $links = $local_links;
        }

        //  care about non-exepted suffixes, which might not been detected up to now
        if ($care_excl == '1') {
            reset($ext);
            $acc_links  = array();
            $error      = '';

            foreach($links as $url) {
				foreach ($text as $excl){
                    if (preg_match("/\.$excl($|\?)/i", $url)){  //  if suffix is at the end of the link, or followed by a question mark
                        $error = 'Found: Not supported suffix'; //  error message only for debug mode;
                    }
                }
                if (!$error) {
                    $acc_links[] = $db_con->real_escape_string($url);
                }
            }
            $links = $acc_links;
        }

        if ($lower_links) { //  convert all link URLs to lower case
            $links = lower_array($links, $charSet);
        }

        if ($clear == 1) unset ($chunklist, $regs, $checked_urls, $a);
//echo "\r\n\r\n<br>links array at the end of get_links():<br><pre>";print_r($links);echo "</pre>\r\n";
        if ($strip_sessids == 1) {
            return remove_sessid($links);
        } else {
            return $links;
        }
    }

    // Function to build a unique word array from the text of a webpage, together with the count of each word
    function unique_array($arr) {
        global $db_con, $min_word_length, $common, $word_upper_bound;
        global $index_numbers, $stem_words, $clear, $case_sensitive;

        if ($stem_words != 'none') {
            $newarr = Array();
            foreach ($arr as $val) {
                $newarr[] = stem_word($val, '0');
            }
            $arr = array_merge($arr, $newarr);  //  add the stemmed to the originals. Thus the suggest framework will present all of them
        }

        sort($arr, SORT_STRING);
        reset($arr);
        $newarr = array ();
        $i = 0;
        $counter = 1;

        $element = current($arr);

        if ($index_numbers == 0) {
            $pattern = "/[0-9]+/";
        } else {
            $pattern = "/[ ]+/";
        }

        $regs = Array ();
        for ($n = 0; $n < sizeof($arr); $n++) {
            //check if word is long enough, does not contain characters as defined in $pattern and is not a common word
            //to eliminate/count multiple instance of words
            $next_in_arr = next($arr);

            if ($case_sensitive == "1") {   //  compare words by means of upper and lower case characters (e.g. for Chinese language)
                if ($next_in_arr != $element) {
                    if (strlen($element) >= $min_word_length && !preg_match($pattern, $element) && ($common[$element] != 1)) {
                        if (preg_match("/^(-|\\\')(.*)/", $element, $regs))
                        $element = $regs[2];

                        if (preg_match("/(.*)(\\\'|-)$/", $element, $regs))
                        $element = $regs[1];

                        $newarr[$i][1] = $element;
                        $newarr[$i][2] = $counter;
                        $element = current($arr);
                        $i++;
                        $counter = 1;
                    } else {
                        $element = $next_in_arr;
                        $counter = 1;   //  otherwise the count will be the amount of skipped words
                    }
                } else {
                    if ($counter < $word_upper_bound)
                    $counter++;
                }

            } else {        //  compare all words only using lower case characters

                if ($next_in_arr != $element) {
                    if (strlen($element) >= $min_word_length && !preg_match($pattern, $element) && ($common[strtolower($element)] != 1)) {
                        if (preg_match("/^(-|\\\')(.*)/", $element, $regs))
                        $element = $regs[2];

                        if (preg_match("/(.*)(\\\'|-)$/", $element, $regs))
                        $element = $regs[1];

                        $newarr[$i][1] = $element;
                        $newarr[$i][2] = $counter;
                        $element = current($arr);
                        $i++;
                        $counter = 1;
                    } else {
                        $element = $next_in_arr;
                        $counter = 1;   //  otherwise the count will be the amount of skipped words
                    }
                } else {
                    if ($counter < $word_upper_bound)
                    $counter++;
                }
            }

        }

        if ($clear == 1) unset ($element, $arr);
        return $newarr;
    }

    // Check if url is legal, relative to the main url.
    // Currently working only for port 80 connections !!!
    function url_purify($url, $parent_url, $can_leave_domain, $care_excl, $relocated) {
        global $db_con, $ext, $mainurl, $apache_indexes, $strip_sessids, $debug, $clear, $dup_path, $return_url, $wfs, $charSet, $utf8_links;
        global $other_host, $redir_host, $sldlist, $only_links, $command_line, $no_log, $include_dir, $converter_dir, $idna, $conv_puny;

        //$debug = '99';    //  uncomment in order to get debug info about rejected links and redirections
        $error = '';

        //  there is a lot of nonsense to be found on the Internet
        $url = str_replace("%20", " ", $url);
        $url = trim($url);
        $url = str_replace(" ", "%20", $url);
        $url = str_replace("/'", "/", $url);

        //  exit on error
        if(!$url || $url == '#' || $url == '&') {
            $error = 'Only anchor link, which is not supported';
            if ($debug == '99') {
                printWarning($error, $command_line, $no_log);
            }
            return '';

        }

        //  exit on invalid parsed URL
        if (preg_match("/[\/]?href|[\/]?\<\/a/i", $url)) {
            $error = 'Invalid parsed URL';
            if ($debug == '99') {
                printWarning($error, $command_line, $no_log);
            }
            return '';

        }

        // for relatrive redirections reject all kind of 'mobile' links like in
        //  <script type="text/javascript" src="/js/detectmobilebrowser.js"></script>
        if(!stristr($url, "http") && $relocated && preg_match("/mobile/si", $url)) {
            $error = 'Missing scheme or unsupported link';
            if ($debug == '99') {
                printWarning($error, $command_line, $no_log);
            }
            return '';
        }

        //  if not relative link, and if not exist, add the scheme of the parent URL to the new URL
        if(substr($url, 0,1) != "/" && strstr(substr($url, 0, 10), "www") && !strstr(substr($url, 0, 10), "://")) {
            $parent_url_parts   = parse_all_url($parent_url);
            $url                = $parent_url_parts['scheme']."://".$url;
        }

        $orig_parent_url = $parent_url;  //  in order to remember, also after several modifications

        //  parse IDN coded URLs and make punycode readable
        if ($idna || $conv_puny) {
            //  with respect to the different codings of our dear webmasters (and their special CMS)
            $url        = rawurldecode($url);
            $parent_url = rawurldecode($parent_url);
            $mainurl    = rawurldecode($mainurl);

            require_once "$converter_dir/idna_converter.php";
            // Initialize the converter class
            $IDN = new IdnaConvert(array('idn_version' => 2008));

            if ($conv_puny && strstr($url, "xn--")) {
                $url = $IDN->decode($url);
            }

            if ($conv_puny && strstr($mainurl, "xn--")) {
                $mainurl = $IDN->decode($mainurl);
            }

            $main_url_parts = parse_all_url($mainurl);
            $url_parts      = parse_all_url($url);

            if ($conv_puny && strstr($mainurl, "xn--")) {
                $main_url_parts['host'] = $IDN->decode($main_url_parts['host']);
            }

        } else {
            $main_url_parts = parse_all_url($mainurl);
            $url_parts      = parse_all_url($url);
        }

        //  there is a lot of nonsense to be found on the Internet
        $url_parts['path'] = str_replace("%20", " ", $url_parts['path']);
        $url_parts['path'] = trim($url_parts['path']);
        $url_parts['path'] = str_replace(" ", "%20", $url_parts['path']);

        //  convert the path into UTF-8
        if ($utf8_links) {
            $utf8_path = @iconv($charSet, "UTF-8//IGNORE", $url_parts['path']);
            $utf8_query = @iconv($charSet, "UTF-8//IGNORE", $url_parts['query']);
            $url_parts['path'] = $utf8_path;
            $url_parts['query'] = $utf8_query;
            //  additionally for relative links
            if(!stristr($url, "http")) {
                $url = $utf8_path;
                if ($utf8_query) {
                    $url = $utf8_path."?".$utf8_query;
                }
            }
        }

        // strip sessions
        if ($strip_sessids == 1) {
            $url = remove_sessid($url);
        }

        // if missing, add a final slash to URL
        if($wfs && !$url_parts['path'] && !preg_match("/\/$/", $url)) {
            $url = $url."/";
        }

        //  if there is no filename in urlpath, add a final slash to the url
        if ($wfs && $url_parts['path'] != "/") {
            $last = substr($url_parts['path'], strrpos($url_parts['path'], "/"));
            if ($last != "/" && !strstr($last, ".")) {
                $url =  $url."/" ;
            }
        }

        //  linking or reindex 'in it selves'
        if($url == $parent_url) {
            $error = 'Linking or reindex in it selves, which is not supported';
            if ($debug == '99') {
                printWarning($error, $command_line, $no_log);
            }
            return '';
        }

        //   if activated in Admin settings, allow other hosts in same domain, and also ignore www. and TLD and SLD
        if (!$can_leave_domain &&($local_redir != 1 && $relocated ==1 && $redir_host == 1 || $other_host == 1)
        && $url_parts['host'] != "" && $url_parts['host'] != $main_url_parts['host']){

            //  remove 'www'
            $new_host = str_replace('www.', '', $url_parts['host']) ;
            $main_host = str_replace('www.', '', $main_url_parts['host']);

            //  remove TLD
            if(strstr($new_host, '.')) {
                $new_host = substr($new_host , 0, strrpos($new_host, '.')) ;
            }

            if(strstr($main_host, '.')) {
                $main_host = substr($main_host , 0, strrpos($main_host, '.')) ;
            }

            //  If exist, remove eventually existing SLD
            if(strstr($new_host, '.')) {
                $value = '';
                foreach ($sldlist as &$value) {

                    if (preg_match("/\\$value$/si", $new_host)){
                        $new_host = substr($new_host , 0, strpos($new_host, $value)) ;
                    }

                    if (preg_match("/$value$/si", $main_host)){
                        $main_host = substr($main_host , 0, strpos($main_host, $value)) ;
                    }
                }
            }

            //  if exist, remove sub-domains
            if(strstr($new_host, '.')) {
                $new_host = substr($new_host , strrpos($new_host, '.')+1) ;
            }

            if(strstr($main_host, '.')) {
                $main_host = substr($main_host , strrpos($main_host, '.')+1) ;
            }

            //  follow only host with same domain-name
            if ($new_host == $main_host) {

                if ($care_excl == '1') {    //  care about non-exepted suffixes
					foreach ($ext as $excl){
                        if (preg_match("/\.$excl($|\?)/i", $url)){  //  if suffix is at the end of the link, or followd by a question mark
                            $error = 'Found: Not supported suffix'; //  error message only for debug mode
                        }
					}
                }

                if (substr($url, -1) == '\\') {
                    $error = 'Found: Double slashes in path'; //  error message only for debug mode
                }

                if (isset($url_parts['query'])) {
                    if ($apache_indexes[$url_parts['query']]) {
                        $error = 'Found: Violation the Apache indexes'; //  error message only for debug mode
                    }
                }

                if (preg_match("/[\/]?mailto:|[\/]?javascript:|[\/]?news:/i", $url)) {
                    $error = 'Found: mailto link'; //  error message only for debug mode
                }

                //only http and https links are followed
                if (isset($url_parts['scheme'])) {
                    $scheme = $url_parts['scheme'];
                } else {
                    $scheme ="";
                }
                if (!($scheme == 'http' || $scheme == '' || $scheme == 'https')) {
                    $error = 'Not http or https scheme'; //  error message only for debug mode
                }

                //  exit on error
                if ($error) {
                    if ($debug == '99') {
                        printWarning($error, $command_line, $no_log);
                    }
                    return '';
                }

                return convert_url($url);

            } else {
                //  exit on error
                if ($error) {
                    if ($debug == '99') {
                        $error = 'Redirected out of domain';
                        printWarning($error, $command_line, $no_log);
                    }
                    return '';
                }
            }

        }   //  end of finding new URLs for 'follow other host with same domain-name'

        //  now purify links only for known domains, but independent from containing www or not www
        $url_host       = str_replace("www.", "", $url_parts['host']);
        $main_url_host = str_replace("www.", "", $main_url_parts['host']);
        //  This detects foreign domains:                                $url_parts['host']                !=         $main_url_parts['host']
        if ($url_host != "" && $url_host != $main_url_host  && $can_leave_domain != 1) {

            if ($only_links && $can_leave_domain == 1) {
                return $url;
            } else {
                $error = 'Linking or reindex out of domain, which is not supported';
                if ($debug == '99') {
                    printWarning($error, $command_line, $no_log);
                }
                return '';
            }
        }

        if ($care_excl == '1') {    //  care about non-exepted suffixes
			foreach ($ext as $excl){
                if (preg_match("/\.$excl($|\?)/i", $url)){  //  if suffix is at the end of the link, or followd by a question mark
                    $error = 'Not supported suffix in link name';
                    if ($debug == '99') {
                        printWarning($error, $command_line, $no_log);
                    }
                    return '';
                }
			}
        }

        if (substr($url, -1) == '\\') {
            $error = 'Double back slashes found in path';
            if ($debug == '99') {
                printWarning($error, $command_line, $no_log);
            }
            return '';
        }

        if (strstr(substr($url, 8), "//")) {    //  we've seen double slashes in url path. Ignore such links
            $error = 'Double slashes found in path';
            if ($debug == '99') {
                printWarning($error, $command_line, $no_log);
            }
            return '';
        }

        if (isset($url_parts['query'])) {
            if ($apache_indexes[$url_parts['query']]) {
                $error = 'Violation the Apache indexes';
                if ($debug == '99') {
                    printWarning($error, $command_line, $no_log);
                }
                return '';
            }
        }

        if (preg_match("/[\/]?mailto:|[\/]?javascript:|[\/]?news:/i", $url)) {
            $error = 'Found a MAILTO link';
            if ($debug == '99') {
                printWarning($error, $command_line, $no_log);
            }
            return '';

        }

        if (isset($url_parts['scheme'])) {
            $scheme = $url_parts['scheme'];
        } else {
            $scheme ="";
        }

        //  only http and https links are followed
        if (!($scheme == 'http' || $scheme == '' || $scheme == 'https')) {
                $error = 'Not http or https scheme, which is not supported';
                if ($debug == '99') {
                    printWarning($error, $command_line, $no_log);
                }
                return '';
        }

        //  now special processing for relative links
        if (!strpos(substr($url, 0, 5), "ttp")) {
            $url = make_abs($url, urldecode($parent_url));
        }

        if ($mainurl == $url) {
            $error = 'Reindexed or link in it selves, which is not supported';
            if ($debug == '99') {
                printWarning($error, $command_line, $no_log);
            }
            return '';
        }

        //  try to find anchor-links (anchor is to be ignored)
        //  here again required for absolute links
        if (strstr($url, "#")) {
            $url = substr($url, 0, strpos($url, "#"));  //  remove the anchor part of the link
        }

        // convert 'blank' and '&amp;'
        $url = convert_url($url);

        if ($can_leave_domain == 1 || $other_host == 1) {
            return $url;
        }

        //  if activated in Admin backend, parse 'returnURL' in link/URL
        if ($return_url && stristr($url, "returnUrl=")) {
        $return = substr($url, (stripos($url, "returnUrl=")+10));
            if (substr($return, 0, 1) == "/"){ //  relative returnURL
                $url = "".$url_parts['scheme']."://".$url_parts['host']."$return";
            } else {    //  absolute returnURL
                $url = $return;
            }
        }

        //  only urls staying in the starting domain/directory are followed
        if (stristr($url, $main_url_host) == false && $only_links != '1') {   //  $main_url_parts['host'] will support also relative-back-folder like ../../
            if ($clear == 1) unset ($mainurl, $url_parts, $urlparts, $urlpath, $page);
            $error = 'URL out of domain';
            if ($debug == '99') {
                printWarning($error, $command_line, $no_log);
            }
            return '';
        } else {
            if ($clear == 1) unset ($mainurl, $url_parts, $urlparts, $urlpath, $page);
            return $url;
        }
    }

    function save_keywords($wordarray, $link_id, $domain) {
        global $db_con, $mysql_table_prefix, $all_keywords, $debug, $db_con, $clear, $command_line, $no_log;

        reset($wordarray);
        sort($wordarray);   //  get alphabetic order
        $count 		= "0";
		$keyword_id	= "";

		foreach ($wordarray as $thisword){

			$word = trim($thisword[1]);
            $word = trim(str_replace(" ", "", $word));
            $word = str_replace("/&nbsp;/","",$word);
            $word = str_replace("<", "&lt;", $word);    //make it visible
            $word = str_replace(">", "&gt;", $word);    //make it visible

            $wordmd5 = substr(md5($word), 0, 1);
            $hits = $thisword[2];
            $weight = $thisword[3];

            if (strlen($word)<= 255) {
                $keyword_id = $all_keywords[$word];

                if ($keyword_id  == "") {
                    if ($debug == '2') {
                        $word1 = str_replace("\\", "", $word);              //  nobody will query for something
                        $word1 = str_replace("%20"," ",$word1);             //  make it readable
                        printActKeyword(str_replace("\'", "'", $word1));    //  make it readable for all
                        $count++;
                    }

                    $word = $db_con->real_escape_string($word); //  protect the database
                    mysqltest();
                    $sql_query = "INSERT into ".$mysql_table_prefix."keywords (keyword) values ('$word')";
                    $db_con->query($sql_query);

                    //  check for duplicate entry in db (errno 1062)
                    if ($db_con->errno == 1062) {
                        $sql_query ="SELECT keyword_ID from ".$mysql_table_prefix."keywords where keyword='$word'";
                        $result = $db_con->query($sql_query);
                        if ($debug > 0 && $db_con->errno) {
                            printf("Duplicate entry for keyword $word<br /> MySQL failure: %s\n", $db_con->error);
                            echo "<br />Script execution aborted.";
                            exit;
                        }
                        $row = $result->fetch_array(MYSQLI_NUM);
                        $keyword_id = $row[0];
                    } else{
                        $keyword_id = $db_con->insert_id;
                        if ($debug && $db_con->errno) {
                            $file       = __FILE__ ;
                            $function   = __FUNCTION__ ;
                            $err_row    = __LINE__-18;
                            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                        }

                        $all_keywords[$word] = $keyword_id;
                    }
                }
                if ($keyword_id) {
                    $inserts[$wordmd5] .= ",($link_id, $keyword_id, $weight, $domain, $hits, now())";
                }
            }
		}

        mysqltest();
        for ($i=0;$i<=15; $i++) {
            $char = dechex($i);
            $values= substr($inserts[$char], 1);
            if ($values != "") {
                mysqltest();
                $sql_query = "INSERT into ".$mysql_table_prefix."link_keyword$char (link_id, keyword_id, weight, domain, hits, indexdate) values $values";
                $db_con->query($sql_query);
                    if ($debug && $db_con->errno) {
                        $file       = __FILE__ ;
                        $function   = __FUNCTION__ ;
                        $err_row    = __LINE__-5;
                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                }
            }
        }

        if ($clear == 1) unset ($values, $char, $inserts, $all_keywords, $weight, $word, $wordarray);
        return $count;
    }

    function get_head_data($file, $url, $use_nofollow, $use_robot, $can_leave_domain, $type, $edititle, $edidescription) {
        global $db_con, $clear, $cano_leave, $only_links, $use_edidesc, $min_title, $min_desc, $no_cano;

        $data       = array();
        $headdata   = "";
        $title      = '';

        $file   = str_replace("'", "\'", $file);            // recover single hyphens
        $file   = preg_replace("/   +/", " ", $file);       //  replace TABs with a standard blank
        $file   = preg_replace("/	+/", " ", $file);       //  replace  this special TABs with one standard blank
        $file   = preg_replace("/  +/", " ", $file);        //  kill duplicate blanks

        if ($only_links != '1') {
            $regs   = Array ();
            if (preg_match("@<title *>(.*?)<\/title*>@si", $file, $regs)) {
                //  fetch title as tag
                $title = trim($regs[1]);
                $title = "".$title." ";
            } else if (preg_match("@<meta name(.*?)=(.*?)[\'\"]title[\'\"] content(.*?)=(.*?)[\'\"](.*?)[\'\"]@si", $file, $regs)) {
                //  fetch alternate title as meta name
                $title = trim($regs[5]);
                $title = "".$title." ";
            } else if ($type == 'pdf' || $type == 'doc' || $type == 'ppt' || $type == 'rtf' || $type == 'xls' || $title == '') {
                //create title for all non-HTML files
                $offset = strrpos ($url, '/');      //      get document file name as title
                $title = substr ($url, $offset+1);
                //  remove file suffix
                $offset = strrpos ($title, '.');
                $title = substr ($title, 0, $offset);
            }

            //  if no title was found, use admin edited title
            if (strlen($title) < 2 && $edititle) {
                $title = $edititle;
            } else {
                //  use admin edited title, if word count did not match minimum
                if ($edititle && $min_title != -1) {
                    if (substr_count(trim($title), " ")+1 < $min_title) {
                        $title = $edititle;
                    }
                }
                //  if admin selected, always use admin edited title
                if ($use_edidesc == 1 && $edititle) {
                    $title = $edititle;
                    //  alternately use both titles (comment the above row)
                    //$title .= $edititle;
                }
            }


            $title          = preg_replace("@<(.*?)>@si", "", $title);  //  remove all the fancy jokes some webmasters might have added
            $title          = preg_replace("@ +@si", " ", $title);
            $data['title']  = str_replace("\'", "'", $title);
        }

        preg_match("@<head[^>]*>(.*?)<\/head>@si",$file, $regs);
        $headdata = $regs[1];
//echo "\r\n\r\n<br /> headdata: '$headdata'<br />\r\n";
        if ($headdata) {
            $description    = "";
            $robots         = "";
            $keywords       = "";
            $base           = "";
            $cano_link      = "";
            $refresh        = "";
            $wait           = "0";
            $res            = Array ();

            //      check for robots in meta tags
            preg_match("/<meta +name *=[\"']?robots[\"']? *content=[\"']?([^<>'\"]+)[\"']?/i", $headdata, $res);
            if (isset ($res)) {
                $robots = $res[1];
            }

            //      check for description tag in header
            $res = array();
            preg_match("/<meta +name *=[\"']?description[\"']? *content=[\"']?([^<>\"]+)[\"']?/i", $headdata, $res);

            if (isset ($res)) {
                $description = $res[1];
                $description = preg_replace("@<(.*?)>@si", "", $description);  //  remove all the fancy jokes some webmasters add
                $description = preg_replace("@ +@si", " ", $description);
            }

            //  if no descripton was found, use admin edited description
            if (strlen($description) < 2 && $edidescription) {
                $description = $edidescription;
            } else {
                //  use admin edited description, if word count did not match minimum
                if ($edidescription && $min_desc != -1) {
                    if (substr_count(trim($description), " ")+1 < $min_desc) {
                        $description = $edidescription;
                    }
                }
                //  if admin selected, always use admin edited description
                if ($use_edidesc == 1 && $edidescription) {
                    $description = $edidescription;
                    //  alternately use both descriptions (comment the above row)
                    //$description.= $edidescription;
                }
            }
            //      check for keywords tag in header
            $res = array();
            preg_match("/<meta +name *=[\"']?keywords[\"']? *content=[\"']?([^<>\"]+)[\"']?/i", $headdata, $res);
            if (isset ($res)) {
                $keywords = $res[1];
                $keywords = preg_replace("/[, ]+/", " ", $keywords);
            }

            // e.g. <base href="http://www.consil.co.uk/index.php" />
            $res = array();
            preg_match("/<base +href *= *[\"']?([^<>'\"]+)[\"']?/i", $headdata, $res);
            if (isset($res)  && $res[1] != "/") {
                $base = $res[1];
            } else {
                $base = $url;   //  eventually this needs to be reduced to the URL of the domain. Not sure about this
            }

            $robots = explode(",", strtolower($robots));
            $nofollow = 0;
            $noindex = 0;
            foreach ($robots as $x) {
                if (trim($x) == "noindex" && $use_robot == '1') {
                    $noindex = 1;
                }
                if (trim($x) == "nofollow" && $use_nofollow == '1') {
                    $nofollow = 1;
                }
            }

			if ($no_cano ==1) {
			//	find canonical link in header
            preg_match("/<link +rel *= *[\"']canonical(.*?)[\"'] +\\/>?/i", $headdata, $res);
				if (isset ($res[0])) {
					//delete this
					$headdata = str_replace($res[0], "", $headdata);
					$res = array();		//kill it
				}
			}

            //      check for canonical link info in meta tags
            $res        = array();
            preg_match("/<link +rel *=[\"']canonical[\"'] *href=[\"'](.*?)[\"']/i", $headdata, $res);
//echo "\r\n\r\n<br>res Array:<br><pre>";print_r($res);echo "</pre>\r\n";
            if (isset ($res[0])) {
                $this_link      = $db_con->real_escape_string($res[1]);
                $cano_link      = '1';
                $care_excl      = '1';   //  care file suffix to be excluded
                $relocated      = '';    //  URL is not relocated
                $local_redir    = '';

                if ($cano_leave == '1') {   //  if acttivated in Admin backend, allow to leave the domain for canonical links
                    $can_leave_domain = '1';
                }

                if (($a = url_purify($res[1], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {
                    if (strcmp($url, $a)) {
                        $cano_link = $a;    //  if cano_link != url
                    } else {
                        $cano_link = '';    // if cano-link is invalid
                    }
                }

                $url1 = substr($url, 0, -1);    //  remove an eventually existing final slash

                if ($this_link == $url || $this_link == $url1) {    //  canonical link in itself?
                    $cano_link = $this_link;    //  later on we'll decide whether this might end in an infinite loop
                }

                if (rawurldecode($url) == rawurldecode($res[1])) {
                    $cano_link = '';  //  another kind of self-linking
                }
            }

            $data['description']    = str_replace("\'", "'", $description);
            $data['keywords']       = str_replace("\'", "'", $keywords);
            $data['nofollow']       = $nofollow;
            $data['noindex']        = $noindex;
            $data['base']           = $base;
            $data['cano_link']      = $cano_link;
            $data['refresh']        = $refresh;
            $data['wait']           = $wait;
        }
//echo "\r\n\r\n<br>head_data array:<br><pre>";print_r($data);echo "</pre>\r\n";
        if ($clear == 1) unset ($headdata, $res, $title, $keywords, $robots);
        return $data;
    }

    function get_link_details($file, $url, $can_leave_domain, $base, $media_links, $use_nofollow, $local_redir) {
        global $db_con, $strip_sessids, $imagelist, $audiolist, $videolist, $command_line, $no_log;
        global $clear, $div_all, $div_hyphen, $del_secchars, $debug, $cl;
        global $use_white1, $use_white2, $use_black, $whitelist, $blacklist;

        $chunklist = array ();
        // The base URL comes from either the meta tag or the current URL.
        if (!empty($base)) {
            $url = $base;
        }

        $links          = array();
        $regs           = array();
        $checked_urls   = array();
        $data           = array();
        //  first clean unused parts of the file
        $file = preg_replace("@<!--.*?-->@si", " ",$file);
        $file = preg_replace("@<script[^>]*?>.*?<\/script>@si", " ",$file);
        $file = preg_replace("@<style[^>]*>.*?<\/style>@si", " ", $file);

        //  get all links

        preg_match_all("/<a href=[\'\"](.*?)[\'\" ](.*?)>(.*?)<\/a>/si", $file, $regs, PREG_SET_ORDER);    //get all links

        foreach ($regs as $val) {
            if ($use_nofollow == '0') {
                $val[2] = '';   //  temporary ignore 'nofollow' directive
            }

            if (stristr($val[2], "nofollow")){
                $report = "<br /><br />Found ".$val[1].", but <strong>nofollow</strong> flag is set.";
                printNofollowLink($report, $command_line, $no_log);
            }
        }

        $i = 0;
        foreach ($regs as $val) {
            if ($val[1] && !stristr($val[0], ".css")) {  //  reject empty links, which would cause invalid url_purify()  and ignore style links

                //      for all servers  that deliver ' / ' instead of ' ./ ' as relative links on localhost
                if (strpos($val[1], "/") === 0 && strpos($url, "localhost")) {
                    $val[1] = ".".$val[1]."";
                }

                $ignore = '';
                if ($use_nofollow == '1' && (stristr($val[2], "nofollow"))) {
                    $ignore = '1';   //  temporary ignore 'nofollow' directive
                }

                if ($checked_urls[$val[1]]!=1 && $ignore == '') { //if nofollow is not set
                    $care_excl = '1';   //  care file suffix to be excluded
                    $relocated = '';    //  URL is not relocated
                    $title = '';

                    if (($a = url_purify($val[1], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {
                        //  get title from images
                        if (stripos($val[3], "title=")) {
                            preg_match_all("/title=\"(.*?)\"/si", $val[3], $regtlt, PREG_SET_ORDER);
                            $title = $regtlt[0][1];
                        } else {
                            if (stripos($val[3], "alt=")) {
                                preg_match_all("/alt=\"(.*?)\"/si", $val[3], $regtlt, PREG_SET_ORDER);    //get alternate title from images
                                $title = $regtlt[0][1];
                            }
                        }

                        if (!$title){
                            $title = $val[3];
                        }

                        if ($use_white1 == '1') {       //  check, whether this title matches ANY word in whitelist
                            $found = '0';
                            foreach ($whitelist as $key => $value) {
                                if (stristr($title, $value)) {
                                    $found = '1';
                                }
                            }

                            if ($found == '0') {
                                if ($debug == '2') {
                                    printWhiteLink($url, $title, $cl);
                                }
                                $title = '';
                            }
                        }

                        if ($use_white2 == '1') {       //  check whether this  title matches ALL words in whitelist
                            $all  = count($whitelist);
                            $found = '0';
                            $found_this = '0';
                            foreach ($whitelist as $key => $value) {
                                if (stristr($title, $value)) {
                                    $found_this = '1';
                                }

                                if ($found_this != '0'){
                                    $found++;
                                    $found_this = '0';
                                }
                            }

                            if ($found != $all) {
                                if ($debug == '2') {
                                    printWhiteLink($url, $title, $cl);
                                }
                                $title = '';
                            }
                        }

                        if ($use_black == '1') {
                            $found = '0';           //  check whether this title matches ANY string in blacklist
                            foreach ($blacklist as $key => $value) {
                                $met = stristr($title, $value);
                                if($met) $found = '1';
                            }
                            if ($found == '1') {
                                if ($debug == '2') {
                                    printBlackLink($a, $title, $cl);
                                }
                                $title = '';
                            }
                        }

                        if ($title) {
                            $data[0][0] .= " $title";     //  add current link text as part of the complete title string

                            //  clean title from stuff
                            $trash   = array("  ", "&nbsp;&nbsp;", " &nbsp;", "<br />", "\r\n", "\n", "\r", "\\r\\n", "\\n", "\\r", "\\", "\\\\", "<strong>", "</strong>", "\"");
                            $replace = ' ';

                            $title      = str_replace($trash, $replace, $title);
                            $data[0][0] = str_replace($trash, $replace, $data[0][0]);

                            $search = '';

                            if ($del_secchars){
                                $data[0][0] = del_secchars($data[0][0]);
                            }
                            //$data[0][0] = preg_replace('/,|\. |\.\. |\.\.\. |!|\? |" |: |\) |\), |\). |ÃƒÆ’Ã‚Â£ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã‹Å“ |ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¼ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â° |ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¼Ãƒâ€¦Ã‚Â¸,|ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¼Ãƒâ€¦Ã‚Â¸ |ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¼ÃƒÂ¯Ã‚Â¿Ã‚Â½ |ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¼ÃƒÂ¯Ã‚Â¿Ã‚Â½|ÃƒÆ’Ã‚Â£ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡,|ÃƒÆ’Ã‚Â£ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ |ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾ |ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“ |ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¯Ã‚Â¿Ã‚Â½ |ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¯Ã‚Â¿Ã‚Â½|ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¯Ã‚Â¿Ã‚Â½&nbsp;|ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â» |.ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»|;ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»|:ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»|,ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»|.ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»|ÃƒÆ’Ã…Â½ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»|ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â«|ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â« |ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â», |ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â». |.ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¯Ã‚Â¿Ã‚Â½ |,ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¯Ã‚Â¿Ã‚Â½|;ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¯Ã‚Â¿Ã‚Â½ |ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¯Ã‚Â¿Ã‚Â½. |ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¯Ã‚Â¿Ã‚Â½, |ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¿|ÃƒÆ’Ã‚Â£ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¯Ã‚Â¿Ã‚Â½|ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¼ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â°|ÃƒÆ’Ã…Â½ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡|;|\] |\} |=|\<|\>/', " ", $data[0][0]);
                            //$data[0][0] = preg_replace('/ \[| "| \(| ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾| ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“|ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¼Ãƒâ€¹Ã¢â‚¬Â | ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â«| ÃƒÆ’Ã‚Â£ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¯Ã‚Â¿Ã‚Â½| ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¿| ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¼Ãƒâ€¹Ã¢â‚¬Â /', " ", $data[0][0]);     //    kill special characters in front of words


                            $data[0][0]     = $db_con->real_escape_string($data[0][0]);
                            $data[$i][1]    = $db_con->real_escape_string($val[0]);
                            $data[$i][2]    = $db_con->real_escape_string($a);
                            $data[$i][3]    = $db_con->real_escape_string($title);

                            $checked_urls[$val[1]] = 1;
                        }
                    }
                }
            }
            $i++;
        }

        //  split words at hyphen, single quote, dot and comma into their basics
        if (($div_all || $div_hyphen)) {
            $data[0][0] = split_words($data[0][0]);
        }

        if ($clear == 1)  unset ($regs, $regtlt, $title, $val);
        return $data;
    }

    function purify_content($file) {
        global $db_con, $clear, $use_nofollow, $js_reloc, $ignore_comment;

        if ($ignore_comment == 1) {
            $file = preg_replace("@<!--.*?-->@si", " ",$file);
        }

        if ($use_nofollow != '0') {
            $use_nofollow = "1";
        }

        if ($use_nofollow) {
            $file = preg_replace("@<!--sphider_noindex-->.*?<!--\/sphider_noindex-->@si", " ",$file);
        }

        //  clean useless parts of the content
        $file = preg_replace("@<style[^>]*>.*?<\/style>@si", " ", $file);
        $file = str_replace ("encoding: ''", " ", $file);        //  yes, I've seen such nonsense !

        if ($clear == 1)  unset ($regs);
        return $file;
    }

    function clean_file($file, $url, $type, $charSet, $use_nofollow, $use_robot, $can_leave_domain, $edititle, $edidescription) {
        global $db_con, $entities, $index_host, $index_meta_keywords, $index_meta_description, $case_sensitive, $utf_16;
        global $home_charset, $chrSet, $del_secchars, $index_rss, $converter_dir, $div_all, $div_hyphen, $del_dups, $cl;
        global $bb_decode, $ent_decode, $cn_seg, $quotes, $dup_quotes, $clear, $only_links, $text_length, $strict_high;
        global $use_divs, $not_divs, $not_divlist, $use_divlist, $ignore_fulltxt, $index_meta_title, $js_reloc, $ignore_comment;
        global $use_elems, $not_elems, $use_elementslist, $not_elementslist, $del_elems, $conv_puny, $include_dir, $div_hidden;
        global $not_uls, $not_ullist, $not_pres, $not_prelist, $not_meta, $not_option, $not_noscript, $not_href, $no_email, $only_email;

        $new            = array();
        $data           = array();
        $string         = '';
        $home_charset   = strtoupper($home_charset);

        if ($use_nofollow != '0') {
            $use_nofollow = "1";
        }

        if ($utf_16) {
            //$file = mb_ereg_replace("\\0", "", $file);
            $file = utf16_to_utf8($file);
        }

        if ($use_nofollow == '1') {
            $file = preg_replace("@<!--sphider_noindex-->.*?<!--\/sphider_noindex-->@si", " ",$file);
        }

        if ($ignore_comment) {
             //  remove BOM and others from HTML files
            if (stripos(substr($file, 0, 1000), "<!DOCTYPE")) {
                $file = substr($file, stripos($file, "<!DOCTYPE"));
            }

            //  don't index anything behind end of html
            //  remove scripts and comments beyond the HTML part of the content
            if (stripos($file, "</html>")) {
                $file = substr($file, 0, strripos($file, "</html>")+7);
            }
            $file = preg_replace("@<!--.*?-->@si", " ",$file);
        }

        //  clean useless parts of the content
        $file = preg_replace("@<style[^>]*>.*?<\/style>@si", " ", $file);
        $file = str_replace ("encoding: ''", " ", $file);        //  yes, I've seen such nonsense !
        //      kill useless blanks and line feeds
        $file       = str_replace(".</span>", ". ", $file);
        $file       = str_replace(".<br />", ". ", $file);
        $file       = str_replace(".\r\n", ". ", $file);
        $file       = preg_replace("/[  |\r\n]+/i", " ", $file);
        $urlparts   = parse_addr($url);
        $host       = $urlparts['host'];
        //remove filename from path and all tags which should be ignored
        $path = preg_replace('/([^\/]+)$/i', "", $urlparts['path']);

        if (preg_match("/\.htm|\.php/i", $url)) {
            //  check for the most common HTML source code errors
            $file1 = preg_replace("@<\!--.*?-->@si", " ",$file);    //  remove  comment part
            $file1 = preg_replace("@<div.*?>@si", " ",$file1);  //  remove all div content
            $file1 = str_replace("'<body", " ", $file1);        //  remove <body from scripts

            $first_head_pos = '';
            $sec_head_pos   = '';
            $first_body_pos = '';
            $sec_body_pos   = '';

            $first_head_pos = stripos($file, "<head>");
            if ($first_head_pos) {
                $sec_head_pos = stripos($file, "<head>", $first_head_pos+5);
                if ($sec_head_pos) {
                    $report  = "<br /><br />Attention :&nbsp;&nbsp;&nbsp;&nbsp;Duplicate HTML head tags detected here.<br />";
                    $report .= "Please verify the HTML code by means of the W3C Markup Validation Service at http://validator.w3.org/<br />";
                    $report .= "Index procedure will not be able to index complete content of this URL.";
                    printWarning1($report, $cl);
                }
            }
            $first_body_pos = stripos($file1, "<body");
            if ($first_body_pos) {
                $sec_body_pos = stripos($file1, "<body", $first_body_pos+5);
                if ($sec_body_pos) {
                    $report  = "<br /><br />Attention :&nbsp;&nbsp;&nbsp;&nbsp;Duplicate HTML body tags detected here.<br />";
                    $report .= "Please verify the HTML code by means of the W3C Markup Validation Service at http://validator.w3.org/<br />";
                    $report .= "Index procedure will not be able to index complete content of this URL.";
                    printWarning1($report, $cl);

                }
            }
        }
        //  parse the HTML head
        $headdata       = get_head_data($file, $url, $use_nofollow, $use_robot, $can_leave_domain, $type, $edititle, $edidescription);

        $title          = $headdata['title'];
        $description    = $headdata['description'];
        $keywords       = $headdata['keywords'];

        $file = preg_replace("@<head>.*?</head>@si", " ",$file);    //  remove HTML head from file
        $file = preg_replace("@<script[^>]*?>.*?<\/script>@si", " ",$file);
        //$file = str_replace("window.location.replace", " ", $file);
        $file = preg_replace("@<style[^>]*>.*?<\/style>@si", " ", $file);
        $file = preg_replace("@<map.*?<\/map>@si", " ", $file);
        $file = preg_replace("/<link rel[^<>]*>/i", " ", $file);
        $file = preg_replace("@<div style=(\"|')display\:none(\"|').*?<\/div>@si", " ", $file);
        $file = preg_replace("@<(object|img|audio|video).*?>@si", " ", $file);
        $file = preg_replace("@<(object|img|audio|video).*?<\/(object|img|audio|video)>@si", " ", $file);
        $file = preg_replace("@<(align|alt|data|body|form|height|input|id|name|span|src|table|td|type|width|layer|span).*?>@si", " ", $file);
        $file = preg_replace("@<embed.*?<\/embeded>@si", " ", $file);   //  remove embeded scripts
        $file = preg_replace("@\{document\..*?\}@si", " ", $file);

        //  if activated in Admin settings, ignore 'meta' tags in body part of the page
        if($not_meta == 1) {
            $file = preg_replace("@<meta.*?\/>@si", " ", $file);
        }

        //  if activated in admin settings, do not index 'option' tags
        if($not_option == 1) {
            $file = preg_replace("@<option.*?\/>@si", " ", $file);
        }

        //  if activated in Admin settings, ignore links
        if($not_href == 1) {
            $file = preg_replace("@<a href=.*?>@si", " ", $file);
        }

        //  if activated in Admin settings, ignore 'noscript' tags in body part of the page
        if($not_noscript == 1) {
            $file = preg_replace("@<noscript>.*?<\/noscript>@si", " ", $file);
        }

        //  if activated in Admin settings, ignore the full text
        if ($ignore_fulltxt == '1') {
            $file = '';
        }

        // if activated in Admin settings, remove all ul tag content as defined in common 'uls_not' list
        if ($not_uls == '1') {
            foreach ($not_ullist as $thisclass) {  //    try to find ul classes with id as specified in common 'uls' list

                //  regexp ?
                if (strpos($thisclass, "/") == "1" && strrpos($thisclass, "/") == strlen($thisclass)-1) {
                    $thisclass = substr($thisclass, 2, strlen($thisclass)-3);    //  remove the regex capsules
                } else {    //  for string input only
                    if (strrpos($thisclass, "*") == strlen($thisclass)-1) {
                        $thisclass = str_replace("*", "(.*?)", $thisclass);   //  replace wildcards at the end of string input
                    }
                }

                $done = '';  //  will be used to find multiple tags matching the preg_match_all
                while ($done != '1') {
                    if (preg_match_all("@(<ul class)=(\"|')".$thisclass."(\"|')(.*?</ul>)+@i", $file, $found_ul, PREG_OFFSET_CAPTURE )) {
                        $this_ul   = array();
                        foreach ($found_ul[0] as $this_ul) {  //  loop through nested divs

                            if (preg_match("@(<ul class)=(\"|')".$thisclass."(\"|')@si", $this_ul[0])) {
                                $tag_type       = "div";
                                $this_tagstart  = strpos($file, $this_ul[0]);  //  absolute position of this tag

                                $i              = "end";                    //  if required $i will become the loop counter for nested tags
                                $nextstart      = strpos($file, "<$tag_type", $this_tagstart+4);      //  find start pos of next tag
                                $nextend        = strpos($file, "</$tag_type", $this_tagstart+4);     //  find end pos of next tag

                                //check for nested tags
                                if ($nextstart && $nextstart < $nextend) {
                                    $i = "1";   //  yes, nested
                                }

                                while ($i != "end") {   //  loop for (multiple) 'nested tags'
                                    while ($nextstart && $nextstart < $nextend) {   // next tag is a nested tag?

                                        $nextend1   = strpos($file, "</$tag_type", $nextstart+4);   //  this is only the endpos of current tag
                                        $nextstart  = strpos($file, "<$tag_type", $nextstart+4);    // find start pos of next tag
                                        $nextend    = strpos($file, "</$tag_type", $nextend1+6);    //  find end pos of next tag

                                        if ($nextstart && $nextstart < $nextend) {   //  again nested in next layer?
                                            $i++ ;                      //  counter for next level nested tags
                                        } else {
                                            $i = 'end'; //  no more nested tags
                                        }
                                    }
                                }       //  end of 'nested tags' loop

                                //  delete this tag content from $file
                                $kill_thistag = substr($file, $this_tagstart, ($nextend+6)-$this_tagstart);
                                $file = str_replace($kill_thistag, " ", $file);
                            }
                        }
                    } else {
                        $done = '1';
                    }
                }

            }
        }

        // if activated in Admin settings, remove all pre tag content as defined in common 'pres_not' list
        if ($not_pres == '1') {
            foreach ($not_prelist as $thisclass) {  //    try to find pre classes with id as specified in common 'pres' list
                //  regexp ?
                if (strpos($thisclass, "/") == "1" && strrpos($thisclass, "/") == strlen($thisclass)-1) {
                    $thisclass = substr($thisclass, 2, strlen($thisclass)-3);    //  remove the regex capsules
                } else {    //  for string input only
                    if (strrpos($thisclass, "*") == strlen($thisclass)-1) {
                        $thisclass = str_replace("*", "(.*?)", $thisclass);   //  replace wildcards at the end of string input
                    }
                }

                $done = '';  //  will be used to find multiple tags matching the preg_match_all
                while ($done != '1') {
                    if (preg_match_all("@(<pre class)=(\"|')".$thisclass."(\"|')(.*?</pre>)+@i", $file, $found_pre, PREG_OFFSET_CAPTURE )) {
                        $this_pre   = array();
                        foreach ($found_pre[0] as $this_pre) {  //  loop through nested divs

                            if (preg_match("@(<pre class)=(\"|')".$thisclass."(\"|')@si", $this_pre[0])) {
                                $tag_type       = "div";
                                $this_tagstart  = strpos($file, $this_pre[0]);  //  absolute position of this tag

                                $i              = "end";                    //  if required $i will become the loop counter for nested tags
                                $nextstart      = strpos($file, "<$tag_type", $this_tagstart+4);      //  find start pos of next tag
                                $nextend        = strpos($file, "</$tag_type", $this_tagstart+4);     //  find end pos of next tag

                                //check for nested tags
                                if ($nextstart && $nextstart < $nextend) {
                                    $i = "1";   //  yes, nested
                                }

                                while ($i != "end") {   //  loop for (multiple) 'nested tags'
                                    while ($nextstart && $nextstart < $nextend) {   // next tag is a nested tag?

                                        $nextend1   = strpos($file, "</$tag_type", $nextstart+4);   //  this is only the endpos of current tag
                                        $nextstart  = strpos($file, "<$tag_type", $nextstart+4);    // find start pos of next tag
                                        $nextend    = strpos($file, "</$tag_type", $nextend1+6);    //  find end pos of next tag

                                        if ($nextstart && $nextstart < $nextend) {   //  again nested in next layer?
                                            $i++ ;                      //  counter for next level nested tags
                                        } else {
                                            $i = 'end'; //  no more nested tags
                                        }
                                    }
                                }       //  end of 'nested tags' loop

                                //  delete this tag content from $file
                                $kill_thistag = substr($file, $this_tagstart, ($nextend+6)-$this_tagstart);
                                $file = str_replace($kill_thistag, " ", $file);
                            }
                        }
                    } else {
                        $done = '1';
                    }
                }
            }
        }

        // if activated in Admin settings, remove all div contents as defined in common 'divs_not' list
        if ($not_divs == '1') {
            foreach ($not_divlist as $thisid) {  //    try to find divs with id as specified in common 'divs' list
                //  regexp ?
                if (strpos($thisid, "/") == "1" && strrpos($thisid, "/") == strlen($thisid)-1) {
                    $thisid = substr($thisid, 2, strlen($thisid)-3);    //  remove the regex capsules
                } else {    //  for string input only
                    if (strrpos($thisid, "*") == strlen($thisid)-1) {
                        $thisid = str_replace("*", "(.*?)", $thisid);   //  replace wildcards at the end of string input
                    }
                }

                $done = '';  //  will be used to find multiple divs matching the preg_match_all
                while ($done != '1') {
                    if (preg_match_all("@(<div class|<div id)=(\"|')".$thisid."(\"|')(.*?</div>)+@i", $file, $found_div, PREG_OFFSET_CAPTURE )) {

                        $this_div   = array();
                        foreach ($found_div[0] as $this_div) {  //  loop through nested divs

                            if (preg_match("@(<div class|<div id)=(\"|')".$thisid."(\"|')@si", $this_div[0])) {
                                $tag_type       = "div";
                                $this_tagstart  = strpos($file, $this_div[0]);  //  absolute position of this tag

                                $i              = "end";                    //  if required $i will become the loop counter for nested tags
                                $nextstart      = strpos($file, "<$tag_type", $this_tagstart+4);      //  find start pos of next tag
                                $nextend        = strpos($file, "</$tag_type", $this_tagstart+4);     //  find end pos of next tag

                                //check for nested tags
                                if ($nextstart && $nextstart < $nextend) {
                                    $i = "1";   //  yes, nested
                                }

                                while ($i != "end") {   //  loop for (multiple) 'nested tags'
                                    while ($nextstart && $nextstart < $nextend) {   // next tag is a nested tag?

                                        $nextend1   = strpos($file, "</$tag_type", $nextstart+4);   //  this is only the endpos of current tag
                                        $nextstart  = strpos($file, "<$tag_type", $nextstart+4);    // find start pos of next tag
                                        $nextend    = strpos($file, "</$tag_type", $nextend1+6);    //  find end pos of next tag

                                        if ($nextstart && $nextstart < $nextend) {   //  again nested in next layer?
                                            $i++ ;                      //  counter for next level nested tags
                                        } else {
                                            $i = 'end'; //  no more nested tags
                                        }
                                    }
                                }       //  end of 'nested tags' loop

                                //  delete this tag content from $file
                                $kill_thistag = substr($file, $this_tagstart, ($nextend+6)-$this_tagstart);
                                $file = str_replace($kill_thistag, " ", $file);
                            }
                        }
                    } else {
                        $done = '1';
                    }
                }
            }
        }

        // if activated in Admin settings, fetch all div content as defined in common 'divs_use' list
        if ($use_divs == '1') {
            $all_divs   = '';   //  will contain all divs to be indexed
            foreach ($use_divlist as $thisid) {  //    try to find divs with id as specified in common 'divs' list

                //  regexp ?
                if (strpos($thisid, "/") == "1" && strrpos($thisid, "/") == strlen($thisid)-1) {
                    $thisid = substr($thisid, 2, strlen($thisid)-3);    //  remove the regex capsules
                } else {    //  for string input only
                    if (strrpos($thisid, "*") == strlen($thisid)-1) {
                        $thisid = str_replace("*", "(.*?)", $thisid);   //  replace wildcards at the end of string input
                    }
                }

                $done = '';  //  will be used to find multiple divs matching the preg_match_all
                while ($done != '1') {
                    if (preg_match_all("@(<div class|<div id)=(\"|')".$thisid."(\"|')(.*?</div>)+@i", $file, $found_div, PREG_OFFSET_CAPTURE )) {

                        $this_div   = array();
                        foreach ($found_div[0] as $this_div) {  //  loop through nested divs

                            if (preg_match("@(<div class|<div id)=(\"|')".$thisid."(\"|')@si", $this_div[0])) {
                                $tag_type       = "div";
                                $this_tagstart  = strpos($file, $this_div[0]);  //  absolute position of this tag

                                $i              = "end";                    //  if required $i will become the loop counter for nested tags
                                $nextstart      = strpos($file, "<$tag_type", $this_tagstart+4);      //  find start pos of next tag
                                $nextend        = strpos($file, "</$tag_type", $this_tagstart+4);     //  find end pos of next tag

                                //check for nested tags
                                if ($nextstart && $nextstart < $nextend) {
                                    $i = "1";   //  yes, nested
                                }

                                while ($i != "end") {   //  loop for (multiple) 'nested tags'
                                    while ($nextstart && $nextstart < $nextend) {   // next tag is a nested tag?

                                        $nextend1   = strpos($file, "</$tag_type", $nextstart+4);   //  this is only the endpos of current tag
                                        $nextstart  = strpos($file, "<$tag_type", $nextstart+4);    // find start pos of next tag
                                        $nextend    = strpos($file, "</$tag_type", $nextend1+6);    //  find end pos of next tag

                                        if ($nextstart && $nextstart < $nextend) {   //  again nested in next layer?
                                            $i++ ;                      //  counter for next level nested tags
                                        } else {
                                            $i = 'end'; //  no more nested tags
                                        }
                                    }
                                }       //  end of 'nested tags' loop

                                //  separate this tag from full text
                                $kill_thistag = substr($file, $this_tagstart, ($nextend+6)-$this_tagstart);
                                //  collect all divs to be indexed
                                $all_divs .= $kill_thistag;
                                // in order not to find it a second time (endless loop), delete this tag
                                $file = str_replace($kill_thistag, " ", $file);
                            }
                        }
                    } else {
                        $done = '1';
                    }
                }
            }
            if (strlen($all_divs) > '3') {  //  we found divs
                $file = $all_divs;  //  now this will be used as the body part of page content
            }
        }

        // if activated in Admin settings, fetch the content of all elements as defined in common 'elements_use' list and use the content of these elements as page content
        if ($use_elems == '1') {
            foreach ($use_elementslist as $this_element) {  //    try to find elements with id as specified in common 'elÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¶ements_use' list
                //  regexp ?
                if (strpos($this_element, "/") == "1" && strrpos($this_element, "/") == strlen($this_element)-1) {
                    $this_element = substr($this_element, 2, strlen($this_element)-3);    //  remove the regex capsules
                }

                if (preg_match_all("@<$this_element.*?>.*?<\/$this_element>@si", $file, $found_elements, PREG_OFFSET_CAPTURE )) {

                    foreach ($found_elements as $new_element) {  //  walk through all found elementss.
                        foreach ($new_element as $new) {
                            //  build substring without content tags
                            $string = $new[0];
                            $string = substr($string, strpos($string, ">")+1);
                            $string = substr($string, 0, strrpos($string, "<"));
                            //  collect all elements to be indexed
                            $all_elements[] = $string;
                        }
                    }
                }
            }
            $file = '';
            //  add content of all found elements to full text
            foreach($all_elements as $use_thiselem) {
                $file .= " ".$use_thiselem;  //  now all this will be used as the body part of the page content
            }
        }

        // if activated in Admin settings, fetch the content of all elements as defined in common 'elements_not' list and delete that part of the page
        if ($not_elems == '1') {
            foreach ($not_elementslist as $this_element) {  //    try to find elements with id as specified in common 'elements_not' list
                //  regexp ?
                if (strpos($this_element, "/") == "1" && strrpos($this_element, "/") == strlen($this_element)-1) {
                    $this_element = substr($this_element, 2, strlen($this_element)-3);    //  remove the regex capsules
                }

                if (preg_match_all("@<$this_element.*?>.*?<\/$this_element>@si", $file, $found_elements, PREG_OFFSET_CAPTURE )) {

                    foreach ($found_elements as $new_element) {  //  walk through all found elementss.
                        foreach ($new_element as $new) {
                            //  collect all elements to be ignored
                            $all_elements[] = $new[0];
                        }
                    }
                }
            }
            //  remove the content of all found elements from full text
            foreach($all_elements as $use_thiselem) {
                $file = str_replace($use_thiselem, " ", $file);
            }
        }

        //  parse bbcode
        if ($bb_decode == '1' ){
            $file = bbcode($file);
        }

        //  parse the value and title of <option . . . > tags
        if (preg_match_all("@<option value=['|\"](.*?)<\/option>@si", $file, $regs, PREG_SET_ORDER )) {
            foreach ($regs as $found_option) {
                //  delete trash as part of the option tag
                $found_option1  = preg_replace("@['|\"]>@", " ", $found_option[1]);
                $found_option1  = preg_replace("@['|\"] selected>@", " ", $found_option1);

                $file           = str_replace($found_option[0], " $found_option1 ", $file);
            }
        }

        //  delete content, which is placed as <div . . .class="s-hidden"> this content </div>
        if ($div_hidden) {
            if (preg_match_all("@<div[^<>]+(id =|id=)['|\"][^<>]+['|\"] +(class =|class=)['|\"]s\-hidden['|\"]>(.*?)<\/div>@i", $file, $hidden)) {
                foreach($hidden[0] as $hide_me) {
                    $file = str_replace($hide_me, " ", $file);
                }
            }
        }

        //create spaces between remaining tags, so that removing tags doesn't concatenate strings
        $file = preg_replace("/<[\w ]+>/", "\\0 ", $file);
        $file = preg_replace("/<\/[\w ]+>/", "\\0 ", $file);

        //  remove the content of remaining HTML tags from $file
        $found_tags     = array();
        $another_tag    = array();
        if (preg_match_all("@<(?!\!--)(.*?)>@s", $file, $found_tags, PREG_OFFSET_CAPTURE )) {
            foreach ($found_tags[0] as $another_tag) {       //  walk through all found tags.
/*
                if (strlen($another_tag[0]) < "500") {      //  delete this tag from full text if not too long (unclosed)
                    $file = str_replace($another_tag[0], " ", $file);
                }
*/
                $file = str_replace($another_tag[0], " ", $file);
            }
        }

        if ($del_elems) {   //  if activated in Admin backend, delete  &lt; element /&gt; from full text
            $found_tags     = array();
            $another_tag    = array();
            if (preg_match_all("@\&lt;.*?\&gt;@s", $file, $found_tags, PREG_OFFSET_CAPTURE )) {
                foreach ($found_tags[0] as $another_tag) {       //  walk through all found tags.
                    $file = str_replace($another_tag[0], " ", $file);
                }
            }
        }

        //  remove useless blanks from $file
        $file  = preg_replace("/[  ]+/i", " ", $file);

        if ($conv_puny) {   //  make punycode readable
            require_once "$converter_dir/idna_converter.php";
            // Initialize the converter class
            $IDN            = new IdnaConvert(array('idn_version' => 2008));
            $found_tags     = array();
            $another_tag    = array();
            $this_tag       = '';

            $file = str_replace("http", " http", $file);    //place a blank in front of all http's
            if (preg_match_all("@http.*? @s", $file, $found_tags, PREG_OFFSET_CAPTURE )) {

                foreach ($found_tags[0] as $another_tag) {       //  walk through all found tags.
                    // Decode the URL to readable format
                    $this_tag = $IDN->decode(rawurldecode($another_tag[0]));
                    $this_tag = rawurldecode($this_tag);
                    $file = str_replace($another_tag[0], $this_tag, $file);
                }
            }
        }

        $file   = str_replace("ÃƒÆ’Ã‚Â£ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬", " ", $file);      //  replace special (long) blanks with standard blank
        $file   = str_replace("ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â", "'", $file);       //  replace  invalid coded quotations
        $file   = str_replace("ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©", "&#151;", $file);     //  replace  invalid coded long dash with correct long dash
        $file   = preg_replace ("/(\s)+/", " ", $file);     //  delete whitespaces
        $file   = preg_replace("/   +/", " ", $file);       //  replace TABs with a standard blank
        $file   = preg_replace("/	+/", " ", $file);       //  replace  also theseTABs with one standard blank
        $file   = preg_replace("/  +/", " ", $file);        //  kill duplicate blanks
        $file   = preg_replace("/__+/", " ", $file);        //  kill duplicate underscore
        $file   = preg_replace("/--+/", " ", $file);        //  kill duplicate hyphens
        $file   = preg_replace("/\*\*+/", " ", $file);      //  kill duplicate stars
        $file   = preg_replace("/\#\#+/", " ", $file);      //  kill duplicate hash tags
        $file   = str_replace("&amp;", " ", $file);         //  kill & characters

        $file   = preg_replace("/\☨\☨+/", " ", $file);     //  kill duplicates. . .  Yes, I've met something
        $file   = preg_replace("/\(\(/", " ", $file);      //  kill duplicates.  . .  no comment
        $file   = preg_replace("/\<\</", " ", $file);      //  kill duplicates
        $file   = preg_replace("/\>\>/", " ", $file);      //  kill duplicates
        $file   = preg_replace("/\*\~+/", " ", $file);      //  kill duplicates
        $file   = preg_replace("/\+\+/", " ", $file);      //  kill duplicates
        $file   = preg_replace("/\=\=/", " ", $file);      //  kill duplicates
        $file   = preg_replace("/\~\~/", " ", $file);      //  kill duplicates


/*
        //  kill some other duplicates, already met on the Internet
        if ($del_dups) {
            $file   = preg_replace("/\(\(+/", " ", $file);
            $file   = preg_replace("/\)\)+/", " ", $file);
            $file   = preg_replace("/\~\~+/", " ", $file);
            $file   = preg_replace("/\=\=+/", " ", $file);
            $file   = preg_replace("/\?\?+/", " ", $file);
            $file   = preg_replace("/\!\!+/", " ", $file);
            $file   = preg_replace("/\!\!+/", " ", $file);
            $file   = preg_replace("/\.\.+/", " ", $file);
            $file   = preg_replace("/\<\<+/", " ", $file);
            $file   = preg_replace("/\>\>+/", " ", $file);
            $file   = preg_replace("/\:\:+/", " ", $file);
            $file   = preg_replace("/\+\++/", " ", $file);
            $file   = preg_replace("/\-\-+/", " ", $file);
            $file   = preg_replace("/\*\*+/", " ", $file);
        }
*/

        $file   = str_replace(" &nbsp;", " ", $file);
        $file   = str_replace("&nbsp;&nbsp;", " ", $file);  //  kill duplicate &nbsp; blanks
        $file   = str_replace ("&shy;", "", $file);         //  kill  break character

        //  kill some special cases
        $file = str_replace("&quot;", "\"", $file);
        $file = str_replace("…", " ", $file);

		if (!strstr($file, "%##")) {
			$file = str_replace("%", "% ", $file);		//	 % directly followed by other characters will disturb urlencode()
		}

        //  make URLs readable in full text
        $file = urldecode($file);

        //  kill remaining trash (all seen on the Internet)
        $file = preg_replace("@<b|<l|<u|<|>|div>|div&gt;|div&gt@", " ", $file);

        if ($text_length != "0") {
            //  build substring of full text until last space in front of $text_length
            $file = substr($file, 0, strrpos(substr($file, 0, $text_length), " "));

        }

        //  for URLs use entities, so that links become readable in full text
        $file   = str_replace("<a href=\"http://www.","&lt;a href=&quot;http://www.",$file);
        //  replace .. with a standard blank
        $file   = str_replace("...", " ", $file);
        //  kill duplicate blanks  " ", \r, \t, \n and \f
        if (preg_match("@8859|utf@", $charSet)) {
            $file = preg_replace("/[\s]+/", " ", $file);
        }

        if ($ent_decode == '1') {

            //  as it seems, the PHP function html_entity_decode() has some problems.
            //  In case that 2 entities are placed directly together like: &mdash;&nbsp;
            //  we are obliged to be helpful by eliminating one of them, so PHP does not get confused

            $file       = str_replace("&nbsp;", " ", $file);
            $file       = html_entity_decode($file, ENT_QUOTES, 'UTF-8');

            $title      = str_replace("&nbsp;", " ", $title);
            $title      = html_entity_decode($title, ENT_QUOTES, 'UTF-8');

            $keywords   = str_replace("&nbsp;", " ", $keywords);
            $keywords   = html_entity_decode($keywords, ENT_QUOTES, 'UTF-8');

            $description    = str_replace("&nbsp;", " ", $description);
            $description    = html_entity_decode($description, ENT_QUOTES, 'UTF-8');
        }

        $fulltext = $file;  //  required for result listing as extract around the keywords and for PHRASE search

        //  kill some other duplicates, already met on the Internet
        if ($del_dups) {
            $file   = preg_replace("/\(\(+/", " ", $file);
            $file   = preg_replace("/\)\)+/", " ", $file);
            $file   = preg_replace("/\~\~+/", " ", $file);
            $file   = preg_replace("/\=\=+/", " ", $file);
            $file   = preg_replace("/\?\?+/", " ", $file);
            $file   = preg_replace("/\!\!+/", " ", $file);
            $file   = preg_replace("/\!\!+/", " ", $file);
            $file   = preg_replace("/\.\.+/", " ", $file);
            $file   = preg_replace("/\<\<+/", " ", $file);
            $file   = preg_replace("/\>\>+/", " ", $file);
            $file   = preg_replace("/\:\:+/", " ", $file);
            $file   = preg_replace("/\+\++/", " ", $file);
            $file   = preg_replace("/\-\-+/", " ", $file);
            $file   = preg_replace("/\*\*+/", " ", $file);
        }

        //  if only e-mail accounts should be content 'to be OR not to be' indexed
        if ($no_email == 1 || $only_email == 1) {
            //  extract all accounts from file, title etc.
            $f_mails = get_emails($file);
            $t_mails = get_emails($title);
            $k_mails = get_emails($keywords);
            $d_mails = get_emails($description);

            if ($no_email == 1 ) {
                //  enter here for 'do not index e_mails'
                //  delete all accounts from content
                if(count($f_mails) != 0) {
                    foreach ($f_mails as $this_email){
                        $file = str_replace($this_email, " ", $file);
                    }
                }
                if(count($t_mails) != 0) {
                    foreach ($t_mails as $this_email){
                        $title = str_replace($this_email, " ", $title);
                    }
                }
                if(count($k_mails) != 0) {
                    foreach ($k_mails as $this_email){
                        $keywords = str_replace($this_email, " ", $keywords);
                    }
                }
                if(count($d_mails) != 0) {
                    foreach ($d_mails as $this_email){
                        $description = str_replace($this_email, " ", $description);
                    }
                }
            } else {
                //  enter here for 'index only e_mails'
                $file           = '';
                $title          = '';
                $keywords       = '';
                $description    = '';
                //  make accounts to content
                if(count($f_mails) != 0) {
                    $file = implode(" ", $f_mails);
                }
                if(count($t_mails) != 0) {
                    $title = implode(" ", $t_mails);
                }
                if(count($k_mails) != 0) {
                    $keywords = implode(" ", $k_mails);
                }
                if(count($d_mails) != 0) {
                    $description = implode(" ", $d_mails);
                }
            }
        }

        if ($index_host == 1) {
            //  separate words in host and path
            $host_sep = preg_replace("/\.|\/|\\\/", " ", $host);
            $path_sep = preg_replace("/\.|\/|\\\/", " ", $path);

            $file = $file." ".$host." ".$host_sep;
            $file = $file." ".$path." ".$path_sep;
        }

        if ($headdata['title'] && $index_meta_title) {
            $file = $file." ".$title;
        }

        if ($index_meta_description == 1) {
            $file = $file." ".$description;
        }

        if ($index_meta_keywords == 1) {
            $file = $file." ".$keywords;
        }

        //  correct some other trash found on the Internet
        $file   = str_replace("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¬ÃƒÂ¯Ã‚Â¿Ã‚Â½", "fi", $file);
        $file   = str_replace("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡", "fl", $file);

        if ($index_rss == '1') {
            $file = preg_replace('/0b/si', '.', $file);     // try to correct bad charset interpretation
            $file = preg_replace('//si', '\'', $file);

            $trash   = array("\r\n", "\n", "\r", "0E", "0C", "0I");     // kill 'LF' and the others
        } else {
            $trash   = array("\r\n", "\f", "\n", "\r", "\t");
        }
        $replace    = ' ';
        $file       = str_replace($trash, $replace, $file);
        $fulltext   = str_replace($trash, $replace, $fulltext);

		//  convert all quotes into standard quote
		$file 	= clean_quotes($file);
		$title	= clean_quotes($title);

        if ($del_secchars) {
            $file = del_secchars($file);
        }

        //  use the cleaned $file to just highlight the pure query term in result listing
        if ($strict_high) {
            $fulltext = $file;
        }

        //  split words at hyphen, single quote, dot and comma into their basics
        if (($div_all || $div_hyphen)) {
            $file           = split_words($file);
        }

        //  replace special (long) blanks in title
        $title = str_replace("ÃƒÆ’Ã‚Â£ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬", " ", $title);
        //remove all the fancy jokes some webmasters add
        $title      = preg_replace("@<.*?>@si", " ", $title);
        $title      = preg_replace("@ +@si", " ", $title);
        $fulltext   = preg_replace("@<.*?>@si", " ", $fulltext);
        $fulltext   = preg_replace("@ +@si", " ", $fulltext);
        $file       = preg_replace("@<div .*?>@si", " ", $file);
        $file       = preg_replace("@<.*?>@si", " ", $file);
        $file       = preg_replace("@ +@si", " ", $file);

        //  a string with a maximum length of 16,777,215 characters.
        //  is accepted as type 'mediumtext' in MySQL database  table 'links'
        $limit = "16700000";    //  theoretically: 16,777,215 Bytes
        $fulltext = limit_text( $fulltext, $limit);

        //$count      = count(preg_split("/[\s,]+/", $fulltext));

        $data['fulltext']       = trim($fulltext);     //  will be used as text with highlighted keywords in result listing
        $data['content']        = trim($file);         //  will be used to extract the keywords
        $data['title']          = trim($title);        //  title tag from HTML head
        $data['description']    = trim($description);  //  description tag from HTML head tag
        $data['keywords']       = trim($keywords);     //  keywords tag from HTML head tag
        $data['host']           = $host;
        $data['path']           = $path;
        $data['nofollow']       = $headdata['nofollow'];
        $data['noindex']        = $headdata['noindex'];
        $data['base']           = $headdata['base'];
        $data['cano_link']      = $headdata['cano_link'];
        //$data['count']          = $count;
        $data['refresh']        = $headdata['refresh'];
        $data['wait']           = $headdata['wait'];

        if ($clear == 1) unset ($char, $file, $fulltext, $path_sep, $headdata, $regs, $urlparts, $host);
//echo "\r\n\r\n<br>data Array:<br><pre>";print_r($data);echo "</pre>\r\n";
        return $data;
    }

    function make_abs($url, $parent_url) {
        global $db_con, $include_dir, $converter_dir, $idna, $conv_puny, $mainurl, $dup_path, $wfs;

        //  parse IDN coded URLs and make punycode readable
        if ($idna || $conv_puny) {
            //  with respect to the different codings of our dear webmasters (and their special CMS)
            $url        = rawurldecode($url);
            $parent_url = rawurldecode($parent_url);
            $mainurl    = rawurldecode($mainurl);

            require_once "$converter_dir/idna_converter.php";
            // Initialize the converter class
            $IDN = new IdnaConvert(array('idn_version' => 2008));

            if ($conv_puny && strstr($url, "xn--")) {
                $url = $IDN->decode($url);
            }

            if ($conv_puny && strstr($mainurl, "xn--")) {
                $mainurl = $IDN->decode($mainurl);
            }

            $main_url_parts     = parse_all_url($mainurl);
            $parent_url_parts   = parse_all_url($parent_url);
            $url_parts          = parse_all_url($url);

            if ($conv_puny && strstr($mainurl, "xn--")) {
                $main_url_parts['host'] = $IDN->decode($main_url_parts['host']);
            }
            if ($conv_puny && strstr($parent_url, "xn--")) {
                $parent_url_parts['host'] = $IDN->decode($parent_url_parts['host']);
            }

        } else {
            $main_url_parts     = parse_all_url($mainurl);
            $parent_url_parts   = parse_all_url($parent_url);
            $url_parts          = parse_all_url($url);
        }

        $orig_parent_url    = $parent_url;  //  in order to remember, also after several modifications

        //  if only a query is added to the  current page URL
        if (preg_match("/^\?/", $url)) {
            $parent_end = substr($parent_url, strrpos($parent_url, "/")+1); //  parse the end of the parent url behind the last slash

            //  if the link is only a new query
            if (substr($parent_end, 0, 1) == "?" ) {
                $parent_url = substr($parent_url, 0, strrpos($parent_url, "/")+1) ;
            }

            //  unfortunately some webmasters repeat the file name (and/or query) as part of the new link
            if (strstr($url, $parent_end) || strstr($parent_end, $url)) {  //  so we need the name (and/or query) from the parent url
                $parent_url = substr($parent_url, 0, strrpos($parent_url, "/")+1);
            }

            //  in case that $parent end contains of a file name plus  a query, we need to kill the query from the parent _url
            if (strstr($parent_end, "?")) {
                $parent_url = substr($parent_url, 0, strpos($parent_url, "?"));
            }

            $url = $parent_url.$url;    //  build the complete link

            if (!strpos($url, "ttp")) {
                if ($main_url_parts['port'] == 80 || $url_parts['port'] == "") {
                    $portq = "";
                } else {
                    $portq = ":".$main_url_parts['port'];
                }
                $url = $parent_url_parts['scheme']."://".$parent_url_parts['host'].$portq.$parent_url_parts['path'].$url;
            }
            return convert_url($url);
        } else {
            //  kill eventually existing arguments from the parent url
            if (strpos($parent_url, "?")) {
                $parent_url = substr($parent_url, 0, strpos($parent_url, "?"));
            }

            //  parent url might be used to build the URL from relative path
            // don't remove filename if it is a bare query or fragment
            if (substr($url, 0, 1) != '?' && substr($url, 0, 1) != '#') {
                $parent_url = remove_file_from_url($parent_url);
            }

            $parent_end = substr($parent_url, strrpos($parent_url, "/")+1);     //  parse the end of the parent url behind the last slash

            //  now try to find anchor-links (anchor is to be ignored)
            if (strstr($url, "#")) {
                $url = substr($url, 0, strpos($url, "#"));  //  remove the anchor part of the link
                if (!$url) {    //  this link was only an anchor, forget it
                    return 'self';
                }
            }

            //  another kind of self linking
            if (urlencode($orig_parent_url) == urlencode($url)) {
                return 'self';
            }

            //  another kind of self linking in real links
            //  'urlencode' added for IDN domains
            $par_length = strlen(urlencode($parent_url));
            $url_length = strlen(urlencode($url));
            $pos = strpos($parent_url, $url);

            if ($pos) {
                $rel = $par_length-$pos;
                if ($rel == $url_length+1) {    //  the new link is just the end of $parent_url, this is self linking
                    return 'self';
                }
            }

            $urlpath = $url_parts['path'];      //  simplified for string functions

            //      if ../ should cause one folder up (even several times)
            $regs1   = Array ();
            $parent_url_parts['path'] = substr($parent_url_parts['path'], 0, strrpos($parent_url_parts['path'], "/"));

            while (preg_match("/^[.]{2}\//", $urlpath, $regs1)) {
                //  remove ../ from link path
                $urlpath = substr($urlpath, 3);
                //  remove last folder from parent url path
                $parent_url_parts['path'] = substr($parent_url_parts['path'], 0, strrpos($parent_url_parts['path'], "/" ));
            }

            //  in order to add the urlpath to  $parent_url_parts['path'], we need to separate them with a slash
            if ($urlpath && substr($parent_url_parts['path'],  strlen($parent_url_parts['path'])-1, 1)  != "/") {
                $parent_url_parts['path'] .= "/";
            }


            //  if activated in Admin, we need to add a slash at the end of the path
            if ($wfs && substr($parent_url_parts['path'],  strlen($parent_url_parts['path'])-1, 1)  != "/") {
                $parent_url_parts['path'] .= "/";
            }

            $urlpath    = preg_replace("/\/+/", "/", $urlpath);
            $urlpath    = str_replace("//", "/", $urlpath);    //  we've seen so much nonsense, even double slashes at the beginning of the urlpath)
            $query      = "";

            if (isset($url_parts['query'])) {
                $query = "?".$url_parts['query'];      // (Some servers seem to run this . . .)
                //$query = "/?".$url_parts['query'];            // (Some other servers even seem to run this . . .)
            }
            if ($main_url_parts['port'] == 80 || $url_parts['port'] == "") {
                $portq = "";
            } else {
                $portq = ":".$main_url_parts['port'];
            }

            if ($parent_url_parts['host'] != "localhost") {
                //  if the link URL contains the complete path like the calling URL(root folder) remove the path from the parent_url_path
                if ($parent_url_parts['path'] != "/" && substr($urlpath, 0, 1) == "/") {
                    $parent_url_parts['path'] = "/";
                }

                //  remove the eventually existing leading ./ from the link
                $urlpath = str_replace("./", "/", $urlpath);

                //  if there is no filename in urlpath, add a final slash to the urlpath
                if ($wfs && $url_parts['path'] != "/") {
                    $last = substr($urlpath, strrpos($urlpath, "/"));
                    if ($last != "/" && !strstr($last, ".")) {
                        $urlpath .= "/" ;
                    }
                }

                //  if activated in Admin settings, and parts of the parent_url_path are equal to the url_path,
                //  delete the duplicate part from the parent_url_path
                if ($dup_path && strstr($urlpath, "/")) {
                    $path = substr($urlpath, 0, strrpos($urlpath, "/")+1);

                    if ( $parent_url_parts['path'] != "/" && strstr($parent_url_parts['path'], $path)) {
                        $dup = stripos($parent_url_parts['path'], $path);
                        //$parent_url_parts['path'] = str_replace($path, "", $parent_url_parts['path']);
                        $parent_url_parts['path'] = substr($parent_url_parts['path'], 0, $dup);

                        if (substr($parent_url_parts['path'], 0, 1) != '/'){
                        //if(!substr($parent_url_parts['path'], 0 , "/")) {
                            $parent_url_parts['path'] = "/".$parent_url_parts['path'];
                        }

                        //  in case that we killed the complete path from the parent_url, we use / as path
                        if (!$parent_url_parts['path']) {
                            $parent_url_parts['path'] = "/";
                        }
                    }
                }
            } else {    //  here special processing for 'localhost' applications

                //  remove the eventually existing leading ./ from the link
                $urlpath = str_replace("./", "", $urlpath);

                //  if there is no filename in urlpath, add a final slash to the urlpath
                if ($url_parts['path'] != "/") {
                    $last = substr($urlpath, strrpos($urlpath, "/"));
                    if ($last != "/" && !strstr($last, ".")) {
                        $urlpath .= "/" ;
                    }
                }
            }

            //  remove any trailing slash, which will be supported by $parent_url_parts
            if (substr($urlpath, 0, 1) == "/") {
                $urlpath = substr($urlpath, 1);
            }

            //  finally build the complete URL for relative links
            $url = $parent_url_parts['scheme']."://".$parent_url_parts['host'].$portq.$parent_url_parts['path'].$urlpath.$query;

            //  in case that someone has forgotten to fix the backslashes (Windows like)  in the URL
            //  I've seen even this . . .
            $url = str_replace("\\", "/", $url);
        }
//echo "\r\n\r\n<br /> final_url: '$url'<br />\r\n";
        return $url;
    }

    function calc_weights($wordarray, $title, $host, $path, $keywords, $url_parts) {
        global $db_con, $index_host, $index_meta_keywords, $sort_results, $domain_mul, $cn_seg, $clear, $dompromo, $keypromo;

        $hostarray = unique_array(explode(" ", preg_replace("/[^[:alnum:]-]+/i", " ", strtolower($host))));
        $patharray = unique_array(explode(" ", preg_replace("/[^[:alnum:]-]+/i", " ", strtolower($path))));

        if ($cn_seg == '1') {   //      we need all characters for Chinese language
            $titlearray     = unique_array(explode(" ", strtolower($title)));
            $keywordsarray  = unique_array(explode(" ", strtolower($keywords)));
        } else {
            $titlearray     = unique_array(explode(" ", preg_replace("/[^[:alnum:]-]+/i", " ", strtolower($title))));
            $keywordsarray  = unique_array(explode(" ", preg_replace("/[^[:alnum:]-]+/i", " ", strtolower($keywords))));
        }

        $path_depth = countSubstrs($path, "/");
        $main_url_factor = '1';

        if ($sort_results == '2') {         //      enter here if 'Main URLs (domains) on top'  is selected
            $act_host = $host;
            $act_path =  $url_parts['path'];
            $act_query =  $url_parts['query'];

            //      try to find main URL for localhost systems
            if ($act_host == 'localhost' && substr_count($act_path, ".") == '0' && substr_count($act_path, "/") <= '3') {
                $main_url_factor = $domain_mul;     //      if localhost: increase weight for domains in path
            }
            /*
             if ($act_host == 'localhost' && substr_count($act_path, ".") == '1' && substr_count($act_path, "/") <= '3') {
             $main_url_factor = $domain_mul/2;     //      if localhost: increase weight for sub-domains in path slightly
             }
             */
            //      only these files are exepted as valid part of the url path
            $act_path = str_replace ('index.php', '', $act_path);
            $act_path = str_replace ('index.html', '', $act_path);
            $act_path = str_replace ('index.htm', '', $act_path);
            $act_path = str_replace ('index.shtml', '', $act_path);

            //      try to find main URL in the wild
            if ($act_host != 'localhost'  && substr_count($act_host, ".") == '2' && strlen($act_path) <= '1' && !$url_parts['query']) {
                $main_url_factor = $domain_mul;     //      increase weight for main URLs (domains)
            }
        }

        $promo          = '';
        $catch_found    = '';
        $dom_found      = '';
        //  if promoted keywords need to be weighted
        if ($keypromo) {
            $promo_keys = explode(',', $keypromo);
            //  process multiple promoted keywords
            foreach ($promo_keys as $this_key) {
                if ($catch_found != 1) {
					foreach ($wordarray as $word){
                        if (trim($this_key) == $word[1]) {
                            $catch_found = '1'; //  promoted keyword found in text
                            break;
                        }
                    }
                }
            }
        }

         //  if promoted domains need to be weighted
        if ($dompromo) {
            $promo_doms = explode(',', $dompromo);
            //  process multiple promoted domains
            foreach ($promo_doms as $this_dom) {
                reset($wordarray);
                if (trim($this_dom) == $host) {
                    $dom_found = '1'; //  promoted domain found in URL
                    break;
                }
            }
        }

        //  for promoted catchwords, correct the weighting
        if (!$dompromo && $keypromo && $catch_found){
            $promo = '1';
        }

        //  for promoted domains, correct the weighting
        if (!$keypromo && $dompromo && $dom_found){
            $promo = '1';
        }

        //  for promoted domains AND promoted catchwords , correct the weighting
        if ($keypromo && $dompromo && $catch_found && $dom_found){
            $promo = '1';
        }

		foreach ($wordarray as $wid => $word){
            $word_in_path = 0;
            $word_in_domain = 0;
            $word_in_title = 0;
            $meta_keyword = 0;

            if ($index_host == 1) {
				foreach ($patharray as $path => $value){
                    if ($path[1] == $word[1]) {
                        $word_in_path = 1;
                        break;
                    }
                }

				foreach ($hostarray as $host){
                    if ($host[1] == $word[1]) {
                        $word_in_domain = 1;
                        break;
                    }
                }
                reset($hostarray);
            }

            if ($index_meta_keywords == 1) {
				foreach ($keywordarray as $keyword){
                    if ($keyword[1] == $word[1]) {
                        $meta_keyword = 1;
                        break;
                    }
                }
                reset($keywordsarray);
            }

			foreach ($titlearray as $tit){
                if ($tit[1] == $word[1]) {
                    $word_in_title = 1;
                    break;
                }
            }
            reset($titlearray);
            $wordarray[$wid][3] = (int) (calc_weight($wordarray[$wid][2], $word_in_title, $word_in_domain, $word_in_path, $path_depth, $meta_keyword, $main_url_factor, $host, $promo));
        }
        if ($clear == 1) {
			unset($act_path, $act_host, $act_query);
			$titlearray 	= array();
			$keywordsarray	= array();
			$hostarray 		= array();
			$patharray 		= array();
        }
		reset($wordarray);
        return $wordarray;
    }

    function calc_weight($words_in_page, $word_in_title, $word_in_domain, $word_in_path, $path_depth, $meta_keyword, $main_url_factor, $host, $promo) {
        global $db_con, $title_weight, $domain_weight, $path_weight, $meta_weight;

        $weight =   ( (   $words_in_page
        + $word_in_title * $title_weight
        + $word_in_domain * $domain_weight
        + $word_in_path * $path_weight
        + $meta_keyword * $meta_weight
        ) * 10
        / (0.2 + 0.8*$path_depth)
        )*$main_url_factor;

        //  for promoted domains and/or promoted catchwords, correct the weighting
        if ($promo){
            $weight = $weight*8;
        }
        return $weight;
    }

    function isDuplicateMD5($md5sum) {
        global $db_con, $mysql_table_prefix, $debug, $clear;

        mysqltest();
        $sql_query = "SELECT link_id from ".$mysql_table_prefix."links where md5sum='$md5sum'";
        $result = $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }

        if ($result->num_rows > 0) {
            return true;
        }
        if ($clear == 1) clean_resource($result, '51') ;
        return false;
    }

    function check_include($link, $inc, $not_inc) {
        global $db_con, $clear;

        $url_inc = Array ();
        $url_not_inc = Array ();
        if ($inc != "") {
            $url_inc = explode("\n", $inc);
        }
        if ($not_inc != "") {
            $url_not_inc = explode("\n", $not_inc);
        }

        $include = true;
        foreach ($url_not_inc as $str) {
            $str = trim($str);
            if ($str != "") {
                if (substr($str, 0, 1) == '*') {
                    if (preg_match(substr($str, 1), $link)) {
                        $include = false;
                        break;
                    }
                } else {
                    if (!(strpos($link, $str) === false)) {
                        $include = false;
                        break;
                    }
                }
            }
        }
        if ($include && $inc != "") {
            $include = false;
            foreach ($url_inc as $str) {
                $str = trim($str);
                if ($str != "") {
                    if (substr($str, 0, 1) == '*') {
                        if (preg_match(substr($str, 1), $link)) {
                            $include = true;
                            break;
                        }
                    } else {
                        if (strpos($link, $str) !== false) {
                            $include = true;
                            break;
                        }
                    }
                }
            }
        }
        if ($clear == 1) unset ($str, $link, $url_not_inc, $url_inc);
        return $include;
    }

    function check_for_removal($url) {
        global $db_con, $mysql_table_prefix, $debug, $no_log, $command_line, $clear, $not_erase;

        if (!$not_erase) {  //  delete links only if "URL Must Not include" is not activated for erasing function
            mysqltest();
            $sql_query = "SELECT link_id, visible from ".$mysql_table_prefix."links"." where url='$url'";
            $result = $db_con->query($sql_query);
            if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-5;
                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
            }
            if ($result->num_rows > 0) {
                $row = $result->fetch_array(MYSQLI_NUM);
                $link_id = $row[0];
                $visible = $row[1];
                if ($visible > 0) {
                    $visible --;
                    $sql_query = "UPDATE ".$mysql_table_prefix."links set visible='$visible' where link_id='$link_id'";
                    $db_con->query($sql_query);
                    if ($debug && $db_con->errno) {
                        $file       = __FILE__ ;
                        $function   = __FUNCTION__ ;
                        $err_row    = __LINE__-5;
                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                    }
                } else {
                    $sql_query = "DELETE from ".$mysql_table_prefix."links where link_id=$link_id";
                    $db_con->query($sql_query);
                    if ($debug && $db_con->errno) {
                        $file       = __FILE__ ;
                        $function   = __FUNCTION__ ;
                        $err_row    = __LINE__-5;
                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                    }
                    for ($i=0;$i<=15; $i++) {
                        $char = dechex($i);
                        $sql_query = "DELETE from ".$mysql_table_prefix."link_keyword$char where link_id=$link_id";
                        $db_con->query($sql_query);
                        if ($debug && $db_con->errno) {
                            $file       = __FILE__ ;
                            $function   = __FUNCTION__ ;
                            $err_row    = __LINE__-5;
                            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                        }
                    }
                    printStandardReport('pageRemoved',$command_line, '0');
                }
            }
            if ($clear == 1) clean_resource($result, '52') ;
            unset ($char, $link_id, $visible);
        }
    }

    function extract_text($file, $file0, $source_type, $url, $chrSet) {
        global $db_con, $tmp_dir, $pdftotext_path, $catdoc_path, $xls2csv_path, $op_system, $mb, $debug;
        global $catppt_path, $home_charset, $command_line, $no_log, $clear, $converter_dir, $cl, $index_xmeta;

        $result 		= array();
        $home_charset1  = str_ireplace ('iso-','',$home_charset);
        $charset_int    = str_ireplace ('iso','',$home_charset1);
        $temp_file      = "tmp_file";
        $filename       = $tmp_dir."/".$temp_file ;

        if ($source_type == 'ods'){
            $filename .= ".".$source_type."";
        }
        if ($source_type == 'doc'){
            $filename .= ".".$source_type."";
        }
        if ($source_type == 'docx'){
            $filename .= ".".$source_type."";
        }
        if ($source_type == 'xlsx'){
            $filename .= ".".$source_type."";
        }

        if ($source_type == 'pdf'){
            $filename .= ".".$source_type."";
        }

        if (!$handle = fopen($filename, 'w')) {
            die ("Cannot open file $filename in temp folder");
        }

        mysqltest();
        if (fwrite($handle, $file) === FALSE) {
            die ("Cannot write to file $filename in temp folder");
        }
        fclose($handle);
        mysqltest();

/*
$current_encoding = mb_detect_encoding($filename, 'auto', true);
echo "\r\n\r\n<br /> current_encoding: '$current_encoding'<br />\r\n";

$order = mb_detect_order();
echo "\r\n\r\n<br>order Array:<br><pre>";print_r($order);echo "</pre>\r\n";
$filename = iconv($current_encoding, 'UTF-8', $filename);
*/

        //	for PDF documents enter here
        if ($source_type == 'pdf') {

            if (!$handle = fopen($pdftotext_path, 'rb')) {
                printStandardReport('errorNoPDFConv',$command_line, $no_log);
                $result[] = 'ERROR';
            } else {    //   prepare command line for PDF converter
                if ($op_system != 'win') {
                    $command = "".$pdftotext_path." -enc UTF-8 ".$filename."";
                } else {
                    $command = "".$pdftotext_path." -cfg xpdfrc ".$filename." -";
                }
                $a = exec($command, $result, $retval);  //  convert the PDF document

                if ($retval != '0') {                   //   error handler for PDF file converter
                    if ($retval == '1' || $retval == '3' || $retval == '127') {
                        if ($retval == '1') {
                            printStandardReport('errorOpenPDF',$command_line, $no_log);
                        }
                        if ($retval == '3') {
                            printStandardReport('permissionError',$command_line, $no_log);
                        }
                        if ($retval == '127') {
                            printStandardReport('noConverter',$command_line, $no_log);
                        }
                    } else {
                        printStandardReport('ufoError',$command_line, $no_log);
                    }
                    $result[] = 'ERROR';
                }

                $result = implode(' ', $result);
//echo "\r\n\r\n<br /> XPDF output:<br />'$result'<br />\r\n";

                //  individual processing for http://www.galiziengermandescendants.org/
                //  containing faulty PDFs
                if (strstr($url, "galiziengermandescendants")) {
                    $result = str_replace("", " ", $result);
                }
                //  individual processing for http://osa.ch
                //  containing a faulty PDF
                if (strstr($url, "Hausordnung.pdf")) {
                    $result     = preg_replace("/\‐/", " ", $result);   //  ANSI error: kill the    Â   which is represented by    â€
                    $allowed    = "/[^a-zäöüÄÖÜ0-9\(\)\„\“\/\\040\\.\-\+\@\>\<\\-\\_\\\\]/i";
                    $result     = preg_replace($allowed, "",$result);
                }
            }

			//      for DOC and RTF files enter here
        } else if ($source_type == 'doc' || $source_type == 'rtf') {

/*
echo "\r\n\r\n<br /> op_system: '$op_system'<br />\r\n";
echo "\r\n\r\n<br /> catdoc_path: '$catdoc_path'<br />\r\n";
echo "\r\n\r\n<br /> charset_int: '$charset_int'<br />\r\n";
echo "\r\n\r\n<br /> filename: '$filename'<br />\r\n";
*/
            if ($op_system == 'win') {
                $command = "".$catdoc_path." -s ".$charset_int." -d utf-8 -x ".$filename."";
                $a = exec($command, $result, $retval);
                if (stristr($result[0], "catdoc.exe")) {
                    printDocReport($result[0], $cl);
                }

            } else {
                $message = "&nbsp;&nbsp;&nbsp;&nbsp;Indexing of .doc and .rtf documents is currently not supported on non WINDOWS OS.";
                printDocReport($message, $cl);

/*
                $retval = '';
                $catdoc_path = str_ireplace("catdoc.exe", "catdoc.lin", $catdoc_path);
                //$command = "".$catdoc_path." -cfg xpdfrc ".$filename." -";
                $command = "".$catdoc_path." -s ".$charset_int." -d utf-8 -w -x ".$filename."";
				$a = exec($command, $result, $retval);  //  convert the DOC document
//echo "\r\n\r\n<br /> retval: '$retval'<br />\r\n";
//echo "\r\n\r\n<br>result Array:<br><pre>";print_r($result);echo "</pre>\r\n";
                if ($retval) {
                    $result = 'ERROR';
//echo "\r\n\r\n<br /> retval: '$retval'<br />\r\n";
                    if($retval == '2') {
                        $message = "&nbsp;&nbsp;&nbsp;&nbsp;File to be converted not found";
                        printDocReport($message, $cl);
                    }
                     else if($retval == '3') {
                        $message = "&nbsp;&nbsp;&nbsp;&nbsp;Path to file not found.";
                        printDocReport($message, $cl);
                    }
                    else if($retval == '11') {
                        $message = "&nbsp;&nbsp;&nbsp;&nbsp;The executable is corrupted.";
                        printDocReport($message, $cl);
                    }
                    else if($retval == '12') {
                        $message = "&nbsp;&nbsp;&nbsp;&nbsp;Out of memory execution.";
                        printDocReport($message, $cl);
                    }
                    else if($retval == '22') {
                        $message = "&nbsp;&nbsp;&nbsp;&nbsp; dll error";
                        printDocReport($message, $cl);
                    }
                    else if($retval == '31') {
                        $message = "&nbsp;&nbsp;&nbsp;&nbsp;The association is missing, use Shell to try the OpenWith dialog.";
                        printDocReport($message, $cl);
                    }
                    else if($retval == '32') {
                        $message = "&nbsp;&nbsp;&nbsp;&nbsp;File could not be opened.";
                        printDocReport($message, $cl);
                    }
                    else if($retval == '126') {
                        $message = "&nbsp;&nbsp;&nbsp;&nbsp;Command invoked cannot execute (Permission problem or command is not an executable).";
                        printDocReport($message, $cl);
                    }
                    else if($retval == 127) {
                        $message = "&nbsp;&nbsp;&nbsp;&nbsp;Command not found.";
                        printDocReport($message, $cl);
                    }
                    else if($retval == 128) {
                        $message = "&nbsp;&nbsp;&nbsp;&nbsp;Invalid argument to exit. Exit takes only integer range 0 – 255.";
                        printDocReport($message, $cl);
                    }

                    else if($retval > 128 && $retval < 255) {
                        $message = "&nbsp;&nbsp;&nbsp;&nbsp;Fatal error code $retval";
                        printDocReport($message, $cl);
                    }
                    else if($retval == 255) {
                        $message = "&nbsp;&nbsp;&nbsp;&nbsp;Exit status out of range. Exit takes only integer range 0 – 255.";
                        printDocReport($message, $cl);
                    } else {
                        $message = "&nbsp;&nbsp;&nbsp;&nbsp;Unknown error code $retval.";
                        printDocReport($message, $cl);
                    }
                }
*/
			}

            //      for PPT files enter here
        } else if ($source_type == 'ppt') {
//  currently unsupported,as a failure was encountered for large PowerPoint presentations
            $a = '';
/*
            $command = $catppt_path." -s $charset_int -d utf-8 $filename";
            $a = exec($command, $result, $retval);
*/
        //      for XLS spreadsheets enter here
        } else if ($source_type == 'xls') {
            $error = '';
            require_once "".$converter_dir."/xls_reader.php";
            $data = new Spreadsheet_Excel_Reader();

            if ($mb == '1') {
                //  if extention exists, change 'iconv' to mb_convert_encoding:
                $data->setUTFEncoder('mb');
            }

            // set output encoding.
            $data->setOutputEncoding('UTF-8');

            //  read this document
            $data->read($filename);
            $error = $data->_ole->error;
            if ($error == '1'){
                printStandardReport('xlsError', $command_line, $no_log);
                $result = 'ERROR';
            } else {
                $result = '';
                $boundsheets    = array();
                $sheets         = array();
                $boundsheets    = $data->boundsheets;   // get all tables in this file
                $sheets         = $data->sheets;        // get content of all sheets in all tables

                if($boundsheets) {
                    foreach ($boundsheets as &$bs) {
                        $result .= "".$bs['name'].", "; //  collect all table names in this file
                    }

                    if ($sheets) {
                        foreach ($sheets as &$sheet) {
                            $cells = $sheet['cells'];

                            if ($cells) {    //  ignore all empty cells
                                foreach ($cells as &$cell) {
                                    foreach ($cell as &$content) {
                                        $result .= "".$content.", ";     //  collect content of all cells
                                    }
                                }
                            }
                        }
                    }
                    if (strtoupper($home_charset) == 'ISO-8859-1') {
                        $result = utf8_encode($result);
                    }
                }
            }

        //      for ODS spreadsheets enter here
        } else if ($source_type == 'ods') {
            require_once "".$converter_dir."/ods_reader.php";
            $reader = ods_reader::reader($filename);
            $sheets = $reader->read($filename);

            if($sheets) {
                $result = '';
                foreach ($sheets as &$sheet) {
                    if($sheet) {
                        foreach ($sheet as &$cell) {
                            if($cell) {    //  ignore all empty cells
                                foreach ($cell as &$content) {
                                    $result .= "".$content." ";     //  collect content of all cells
                                }
                            }
                        }
                    }
                }

            } else {
                $result = 'ERROR';
            }

        //	for ODT documents enter here
        } else if ($source_type == 'odt') {

            require_once "".$converter_dir."/odt_reader.php";
            $x = new odt_reader();
            // Unzip the document
            $u = $x->odt_unzip($filename, false);
            // read the document
            $result = $x->odt_read($u[0], 2);
            //  create some blanks around the <div> tags
            $result = str_replace("<", " <", $result);
            $result = str_replace(">", "> ", $result);
            //echo "\r\n\r\n<br /> odt result: $result<br />\r\n";

        //  for DOCX files enter here
        }else if ($source_type == 'docx') {

            //  converter class supplied by http://www.phpdocx.com
            $options    = array('paragraph' => false, 'list' => false,'table' => false, 'footnote' => false, 'endnote' => false, 'chart' => 0);
            $docx_file  = "docx.txt";
            $result     = '';

            require_once "".$converter_dir."/docx/CreateDocx.inc.php";
            CreateDocx::DOCX2TXT($filename, $tmp_dir."/".$docx_file, $options);

            if ($file = @file_get_contents($tmp_dir."/".$docx_file)) {
                $result = "$file ";
            }

            if ($index_xmeta) {
                require_once "".$converter_dir."/xmeta_converter.php";
                $docxmeta = new x_metadata();
                $docxmeta->setDocument($filename);
/*
                echo "Title : " . $docxmeta->getTitle() . "<br>";
                echo "Subject : " . $docxmeta->getSubject() . "<br>";
                echo "Creator : " . $docxmeta->getCreator() . "<br>";
                echo "Keywords : " . $docxmeta->getKeywords() . "<br>";
                echo "Description : " . $docxmeta->getDescription() . "<br>";
                echo "Last Modified By : " . $docxmeta->getLastModifiedBy() . "<br>";
                echo "Revision : " . $docxmeta->getRevision() . "<br>";
                echo "Date Created : " . $docxmeta->getDateCreated() . "<br>";
                echo "Date Modified : " . $docxmeta->getDateModified() . "<br>";
*/
                $result .= $docxmeta->getTitle() . $docxmeta->getSubject() . $docxmeta->getCreator() . $docxmeta->getKeywords() . $docxmeta->getDescription() . $docxmeta->getLastModifiedBy() . $docxmeta->getRevision() . $docxmeta->getDateCreated() . $docxmeta->getDateModified();
            }

            @unlink($tmp_dir."/".$docx_file);
/*
            if($result && $chrSet != "UTF-8") {
                $result = @mb_convert_encoding($result, "UTF-8", $chrSet);
            }
*/
        //  for XLSX spreadsheets enter here
        }else if ($source_type == 'xlsx') {

            $result     = '';
            $i          = 1;
            $name       = '';
            $finished   = false;
            $names      = array();
            require_once "".$converter_dir."/xlsx_reader.php";
            $xlsx = new SimpleXLSX($filename);

            $names = $xlsx->sheetNames();
//echo "\r\n\r\n<br>names array:<br><pre>";print_r($names);echo "</pre>\r\n";
            if ($debug == 2 && $names) {
                printXLSXreport(count($names), $cl);
            }

            foreach ($names as $my_name){
                $result .= $my_name." ";
                if ($debug == 2) {
                    printActKeyword($my_name);
                }
            }

            while (!$finished) {    //  get all sheets
                if ($rows = $xlsx->rows($i)) {
                    foreach($rows as $key ) {
                        foreach ($key as $val) {
                            if ($val) {
                                $result .= " ".$val; //  add value of each cell
                            }
                        }
                    }
                } else {
                    $finished = true;   // no more sheets found
                }
                //$my_name = $xlsx->sheetName($i);
                //echo "\r\n\r\n<br /> sheet name $i: '$my_name'<br />\r\n";

                $i++;   //  try to get next sheet
            }

            if ($index_xmeta) {
                require_once "".$converter_dir."/xmeta_converter.php";
                $xlscxmeta = new x_metadata();
                $xlscxmeta->setDocument($filename);
/*
                echo "Title : " . $xlscxmeta->getTitle() . "<br>";
                echo "Subject : " . $xlscxmeta->getSubject() . "<br>";
                echo "Creator : " . $xlscxmeta->getCreator() . "<br>";
                echo "Keywords : " . $xlscxmeta->getKeywords() . "<br>";
                echo "Description : " . $xlscxmeta->getDescription() . "<br>";
                echo "Last Modified By : " . $xlscxmeta->getLastModifiedBy() . "<br>";
                echo "Revision : " . $xlscxmeta->getRevision() . "<br>";
                echo "Date Created : " . $xlscxmeta->getDateCreated() . "<br>";
                echo "Date Modified : " . $xlscxmeta->getDateModified() . "<br>";
*/
                $result .= $xlscxmeta->getTitle() . $xlscxmeta->getSubject() . $xlscxmeta->getCreator() . $xlscxmeta->getKeywords() . $xlscxmeta->getDescription() . $xlscxmeta->getLastModifiedBy() . $xlscxmeta->getRevision() . $xlscxmeta->getDateCreated() . $xlscxmeta->getDateModified();
            }

/*
            if($result && $chrSet != "UTF-8") {
                $result = @mb_convert_encoding($result, "UTF-8", $chrSet);
            }
*/
        //  for JavaScript enter here
        } else if ($source_type == 'js') {
            $result = extract_js($file);
        }

        if ($result != 'ERROR') {

            if(is_array($result)) {
                    $result = implode(" ", $result);
            }

            $count = strlen($result);
            if ($count =='0'){          //      if there was not one word found, print warning message
                if ($source_type == 'js') {
                   printStandardReport('jsEmpty',$command_line, $no_log);
                } else {
                    printStandardReport('nothingFound',$command_line, $no_log);
                }
                $result = 'ERROR';
            }
        }
//echo "\r\n\r\n<br /> result: '$result'<br />\r\n";
        unlink ($filename);
        mysqltest();
        if ($clear == 1) unset ($command, $retval, $a, $file, $count);
        return $result;
    }

    function mysql_array($array) {
        global $db_con, $debug;

        $mysql_array = array();
        foreach ($array as $this_val){
            $mysql_array[] = $db_con->real_escape_string($this_val);
        }
        return $mysql_array;
    }

    function lower_array($array, $charSet) {
        global $mb;

        $lower_array = array();
        foreach ($array as $this_val){
            if($mb) {
                $lower_array[] = mb_strtolower($this_val, $charSet);    //  Attention:  mb_strtolower()  is about 50 times slower than strtolower() !!!
            } else {
                $lower_array[] = strtolower($this_val);
            }
        }
        return $lower_array;
    }

    function remove_sessid($url) {
        global $strip_s_sessids;

        if (is_array($url)) {   //  prcess the array
            $url2 = array();
            foreach ($url as $this_url) {
                if ($strip_s_sessids) {
                    $url2[] = preg_replace("/(\?|;|&|&amp;)(PHPSESSID|JSESSIONID|session_id|ASPSESSIONID|sid|zenid|cmssessid|osCsid|s)=(.)+$/i", "", $this_url);
                } else {
                    $url2[] = preg_replace("/(\?|;|&|&amp;)(PHPSESSID|JSESSIONID|session_id|ASPSESSIONID|sid|zenid|cmssessid|osCsid)=(.)+$/i", "", $this_url);
                }
            }
            return $url2;
        } else {    //  process a single URL
            if ($strip_s_sessids) {
                return preg_replace("/(\?|;|&|&amp;)(PHPSESSID|JSESSIONID|session_id|ASPSESSIONID|sid|zenid|cmssessid|s)=(.)+$/i", "", $url);
            } else {
                return preg_replace("/(\?|;|&|&amp;)(PHPSESSID|JSESSIONID|session_id|ASPSESSIONID|sid|zenid|cmssessid)=(.)+$/i", "", $url);
            }
        }
    }

    function get_sitemap($input_file, $indexed_map, $mysql_table_prefix) {
        global $db_con, $mysql_table_prefix, $command_line, $debug, $no_log, $max_links, $clear;

        if ($indexed_map) {
            $map_cont = '';
            //      read  content of uncomressed secondary sitemap file
            if (!strstr($input_file, "gz") && $fd = @fopen($input_file, "r")) {   //  read uncompressed sitemap file
                //if ($zd = @gzopen("".$input_file.".xml", "r")) {    //  uncompressed
                $map_cont = @stream_get_contents($fd);
                fclose($fd);
                }
                if (!$map_cont && $zd = @fopen("compress.zlib://$input_file", "r")) {  // read compressed secondary sitemap
                    //if (!$smap_found && $zd = @gzopen("".$input_file.".xml.gz", "r")) {  // compressed  ;
                    $map_cont = @gzread($zd, 10485760);      //  max. 10 MB (might be too large for some server)
                    gzclose($zd);
                    }

        } else {
            $map_cont = $input_file;
        }
        $s_map = simplexml_load_string ($map_cont);

        if ($s_map) { // if sitemap file is conform to XML version 1.0
//echo "\r\n\r\n<br>s_map Array1:<br><pre>";print_r($s_map);echo "</pre>\r\n";

            $links = array ();
            mysqltest();
            $count = '0';
            $scheme = '';

            foreach($s_map as $url) {
                if ($count < $max_links) {  //  save time, we dont need more

                    $the_url = str_replace("&amp;","&",$url->loc);
                    if ($the_url) {     //  hopefully this is a URL
						$the_url = preg_replace( "/(\s)+/", " ",$the_url);		//	delete whitespaces
						$the_url = preg_replace("/[  |\r\n]+/i", "", $the_url);	//	delete LF and NL

                        if (!strstr($the_url, "ttp")) {
                            $scheme = '1';
                            $the_url = "http://".$the_url;
                        }

                        $lastmod = strtotime($url->lastmod);    // get lastmod date only for this page from sitemap
                        if (strlen($lastmod) < 5) $lastmod = '999999999';  //  if the webmaster was lazy we are obliged to index this link

						$sql_query = "SELECT * from ".$mysql_table_prefix."links where url like '%$the_url%'";
                        $res = $db_con->query($sql_query);
                        $num_rows = $res->num_rows;; // do we already know this link?
                        $indexdate = '0';
                        $new    = '1';

                        if ($num_rows) {
                            $row = $res->fetch_array(MYSQLI_ASSOC);
                            $indexdate = strtotime($row['indexdate']);
                            $new = $lastmod - $indexdate;
                        }
                        if ($new >= '0') $links[] =($url->loc); // add new link only if date from sitemap.xml is newer than date of last index
                    }
                    $count++;
                }
            }

            $links = explode(",",(implode(",",$links))); // destroy SimpleXMLElement Object and get the link array
        }

        if ($scheme == '1'){ //  hopefully this is a URL, otherwise we need to add the scheme
            $i = '0';
            foreach($links as $url) {
                if (!strstr($url, "ttp")) {
                    $url = "http://".$url;
                    $links[$i] = $url;
                    $i++;
                }
            }
        }

		if (count($links) == 1 && $count != 0) $links[0] = "i=$count";	//	as reminder that links were found,
																		//	but lastmod date is older than lastindexedate
//echo "\r\n\r\n<br>links Array from sitemap.xml:<br><pre>";print_r($links);echo "</pre>\r\n";
        return($links);
    }

    function store_newLinks($links, $level, $sessid) {
        global $db_con, $mysql_table_prefix, $debug;

        mysqltest();
		foreach ($links as $thislink){
            //  check if we already know this link as a site url
            $thislink = $db_con->real_escape_string($thislink);
            $sql_query = "SELECT url from ".$mysql_table_prefix."sites where url like '$thislink%'";
            $result = $db_con->query($sql_query);
            $rows = $result->num_rows;

            if (!$rows) {     // for all new links: save in temp table
                $sql_query = "INSERT into ".$mysql_table_prefix."temp (link, level, id) values ('$thislink', '$level', '$sessid')";
                $db_con->query ($sql_query);
            }
		}
        return;
    }

    function create_sitemap($site_id, $url) {
        global $db_con, $mysql_table_prefix, $inst_dir, $smap_dir, $smap_unique, $debug, $clear, $cl;

        $changefreq = "monthly";   //      individualize this variable
        $priority   = "0.50";      //      individualize this variable

        //      Only change something, if you are sure to remain compatible to http://www.sitemaps.org/schemas/sitemap/0.9
        $date       = date("Y-m-d");
        $time       = date("h:i:s");
        $modtime    = "T$time+01:00";
        $version    = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" ;
        $urlset     = "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.google.com/schemas/sitemap/0.84 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">";
        $copyright  = "<!-- Generated by Sphider-plus created by Tec (v.1.3 rev.2) -->" ;
        $update     = "<!-- Last update of this sitemap: $date / $time -->" ;

        $all_links  = '';
        mysqltest();
        $sql_query = "SELECT * from ".$mysql_table_prefix."links where site_id = $site_id";
        $res = $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }
        $num_rows = $res->num_rows;;    //      Get all links of the current domain
		if ($num_rows) {
	        while ($this_link = $res->fetch_array(MYSQLI_ASSOC)) {
	            $links[] = $this_link;
	        }

	        for ($i=0; $i<$num_rows; $i++) {    //      Create individual rows for XML-file
	            $link = $links["$i"]['url'];
	            $link = str_replace("&", "&amp;", $link);   // URL should become XML conform
	            $all_links .= "<url><loc>$link</loc><lastmod>$date$modtime</lastmod><changefreq>$changefreq</changefreq><priority>$priority</priority></url>\n";
	        }
    	}


        $name = parse_addr($url);                    //      Create filename and open file
        $hostname = $name[host];

        if ($hostname == 'localhost'){              //  if we run a localhost system extract the domain
            $pathname = $name[path];                //  get path, domain and filename
            $pos = strpos($pathname,"/",1);         //  extract domain from path and forget first / by +1 offset
            $pathname = substr($pathname,$pos+1);   // suppress /localhost/
            $pos = strrpos($pathname,"/");

            if ($pos) {
                $pathname = substr(str_replace("/", "_", $pathname),0,$pos);   // if exists, suppress folder, filename and suffix
            }

            if (!is_dir($smap_dir)) {
                mkdir($smap_dir, 0777);     // if new, create directory
            }
            if ($smap_unique == '0') {      // different names for every sitemap file
                $filename   = "$smap_dir/sitemap_localhost_$pathname.xml";
            } else {
                $filename   = "$smap_dir/sitemap.xml";
            }

            if (!$handle = fopen($filename, "w")) {
                printInvalidFile($filename);
                die;
            }

        } else {    //  if we run in the wild
            if (!is_dir($smap_dir)) {
                mkdir($smap_dir, 0777);     // if new, create directory
            }
            if ($smap_unique == '0') {      // different names for every sitemap file
                $filename   = "$smap_dir/sitemap_$hostname.xml";
            } else {
                $filename   = "$smap_dir/sitemap.xml";
            }

            if (!$handle = fopen($filename, "w")) {
                printInvalidFile($filename);
                die ('');
            }
        }

        chmod($filename, 0777);    //  required for command line operation

        //      Now write all to XML-file
        if (!fwrite($handle, "$version\n$urlset\n$copyright\n$update\n$all_links</urlset>\n")) {
            printInvalidFile($filename);
            die ('');
        }
        fclose($handle);

        if ($debug == 2) {
            //      sitemap.xml done! Now final printout
            $filename = str_replace($inst_dir, " ...", $filename);
            printSitemapCreated($filename, $cl);
        }
    }

    function build_url($url, $parent_url, $select) {
        global $clear, $ext, $mainurl, $apache_indexes, $strip_sessids, $ex_media, $clear;

        // find only media-files with allowed file suffix  or type-description  or application descriptor
        $match = valid_link($url, $select);
        if ($match == '0') {
            return '';
        }

        if (substr($url, -1) == '\\') {
            return '';
        }

        $url                        = str_replace(" ", "%20", $url);
        $original_parent_url_parts  = parse_all_url($url);
        $urlparts                   = parse_all_url($url);
        $main_url_parts             = parse_all_url($mainurl);

        if ($urlparts['host'] != "" && $urlparts['host'] != $main_url_parts['host']  && $ex_media != 1) {
            return '';
        }

        if (isset($urlparts['query'])) {
            if ($apache_indexes[$urlparts['query']]) {
                return '';
            }
        }

        if (preg_match("/[\/]?mailto:|[\/]?javascript:|[\/]?news:/i", $url)) {
            return '';
        }
        if (isset($urlparts['scheme'])) {
            $scheme = $urlparts['scheme'];
        } else {
            $scheme ="";
        }

        //only http and https links are followed
        if (!($scheme == 'http' || $scheme == '' || $scheme == 'https')) {
            return '';
        }

        //parent url might be used to build an url from relative path
        $parent_url = remove_file_from_url($parent_url);
        $parent_url_parts = parse_all_url($parent_url);


        if (substr($url, 0, 1) == '/') {
            $url = $parent_url_parts['scheme']."://".$parent_url_parts['host'].$url;
        } else
        if (!isset($urlparts['scheme'])) {
            $url = $parent_url.$url;
        }

        $url_parts = parse_all_url($url);
        $urlpath    = $url_parts['path'];
        $regs       = Array ();

        while (preg_match("/[^\/]*\/[.]{2}\//", $urlpath, $regs)) {
            $urlpath = str_replace($regs[0], "", $urlpath);
        }

        //remove relative path instructions like ../ etc
        $urlpath    = preg_replace("/\/+/", "/", $urlpath);
        $urlpath    = preg_replace("/[^\/]*\/[.]{2}/", "",  $urlpath);
        $urlpath    = str_replace("./", "", $urlpath);
        $query      = "";

        if (isset($url_parts['query'])) {
            $query = "?".$url_parts['query'];
        }
        if ($main_url_parts['port'] == 80 || $url_parts['port'] == "") {
            $portq = "";
        } else {
            $portq = ":".$main_url_parts['port'];
        }

        if (!$urlpath) $urlpath = "/";      //     if not exists, add slash instead of real urlpath
        $url = $url_parts['scheme']."://".$url_parts['host'].$portq.$urlpath.$query;

        if (strstr($url, "/?")) {           //added to address <a href="?id=1"> syntax
            $page = str_replace($main_url_parts['path'], null, $original_parent_url_parts['path']);
            if (substr(trim($mainurl), -1) !== "/" and substr(trim($page), 0, 1) !== "/") {
                $page = "/" . $page;
            }
            $url = $mainurl . $page . $query;

        }

        if ($ex_media == 1) {    	//  if we index sub-domains
            return $url;
        }

        $mainurl = remove_file_from_url($mainurl);
        $url = convert_url($url);           // convert 'blank' and '&amp;'

        if ($strip_sessids == 1) {
            $url = remove_sessid($url);
        }

        if (strstr($url, $main_url_parts['host']) == false) {   //  $main_url_parts['host'] will support also relative-back-folder like ../../
            if ($clear == 1) {
                unset ($select, $mainurl, $urlpath, $query, $page);
                $original_parent_url_parts  = array();
                $main_url_parts             = array();
                $url_parts                  = array();
                $urlparts                   = array();
            }
            return '';
        } else {
            if ($clear == 1) {
                unset ($select, $mainurl, $urlpath, $query, $page);
                $original_parent_url_parts  = array();
                $main_url_parts             = array();
                $url_parts                  = array();
                $urlparts                   = array();
            }
            return $url;
        }
    }

    function make_abslinks($body, $url){

        //  assuming that all src, data, classid and value links are relative links in a page and without ../ or ./
        //  otherwise we need to run through all links by using $offset++
        //  this function is used only for frames and iframes in order to correct the link URL with respect to the found frame-folder
        $offset = '0';
        $link = '';
        $domain = substr($url, '0', strrpos($url, "/")+1);

        $found_link = strpos($body, "src=", $offset);
        $link = substr($body, $found_link, '20');

        if (!$link) {
            $found_link = strpos($body, "classid=", $offset);
            $link = substr($body, $found_link, '20');
        }

        if (!$link) {
            $found_link = strpos($body, "data=", $offset);
            $link = substr($body, $found_link, '20');
        }

        if (!$link) {
            $found_link = strpos($body, "value=", $offset);
            $link = substr($body, $found_link, '20');
        }

        if ($link) {
            $abs = strpos($link, "http");
            $sc1 = strpos($link, "./");
            $sc2 = strpos($link, "../");
            if (!$abs && !$sc1 && !$sc2) {      //  add domain to link, href is not altered
                $body = preg_replace("/src=\"/", "src=\"".$domain."", $body);
                $body = preg_replace("/classid=\"/", "classid=\"".$domain."", $body);
                $body = preg_replace("/data=\"/", "data=\"".$domain."", $body);
                $body = preg_replace("/value=\"/", "value=\"".$domain."", $body);
            }
        }
        return $body;
    }

    function get_frames($frame, $url, $can_leave_domain) {
        global $abslinks, $cl;

        $links          = array ();
        $regs           = array ();
        $replace        = array ();
        $get_charset    = '1';
        $care_excl      = '1';   //  care file suffixed to be excluded
        $relocated      = '';    //  URL is not relocated
        $local_redir    = '';
        //  find all frames of the frameset
        preg_match_all("/(frame[^>]*src[[:blank:]]*)=[[:blank:]]*[\'\"]?(([[a-z]{3,5}:\/\/(([.a-zA-Z0-9-])+(:[0-9]+)*))*([+:%\/?=&;\\\(\),._ a-zA-Z0-9-]*))(#[.a-zA-Z0-9-]*)?[\'\" ]?/i", $frame, $regs, PREG_SET_ORDER);
        foreach ($regs as $val) {
            if (($a = url_purify($val[2], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)) != '') {

                // for our Galizien webmaster, who ignore W3C rules
                if (stristr($a, "%20style")) {
                    $a = substr($a, 0, stripos($a, "%20style"));
                }

                $links[] = ($a);    // collect  all frame links
            }
        }

        if ($links) {
            foreach ($links as $url) {
                printNewLinks($url, $cl);
                if (preg_match("/.html|.htm|.xml|.php|.asp|.aspx/i", $url)) {
                    $contents = getFileContents($url, $get_charset);      //      get content of this frame
					$frame	= $contents['file'];
					$chrSet	= $contents['charset'];
                    //  separate the body part of this frame
                    /*
                    preg_match("@<body(.*?)>(.*?)<\/body>@si",$frame, $regs);       //  doesn't work for all frame content
                    $body = $regs[1];
                    */
                    $start_body = strpos($frame,"<body")+6;
                    $end_body   = strpos($frame,"</body")-1;
                    $length     = $end_body-$start_body;
                    $body       = substr($frame, $start_body, $length);

                    if ($abslinks == '1') {
                        $body = make_abslinks($body, $url);     //  if required, correct links relative to found frame
                    }
                    $rep = "".$rep."<br />".$body."";
                } else {    //  might be an image
                    $rep = "".$rep."<br /><img src=\"".$url."\">";
                }
            }
        }
		$replace[0] = $rep;
		$replace[1] = $contents['charset'];		// supplied by function getFileContents()
        return $replace;
    }

    function get_elements($element, $all_media, $raw_file, $regs, $trash1, $replace1) {
        global $clear, $index_embeded;

		if ($element == "audio" || $element == "video") {
			$all_media = array();	// we don't need the former links
		}

		$regs = array();
        preg_match_all("/<$element(.*?)<\/$element\s*>/si", $raw_file, $regs);		//get '$element' elements (HTML 5)
        preg_match_all("/<embed(.*?)\/>/si", $raw_file, $regs1, PREG_SET_ORDER);	//get '<embed . . ./>' elements (< HTML 5)

		$regs = array_merge($regs,$regs1);	// add both results

		foreach ($regs as $val) {
            $val = preg_replace("@<map.*?map>@si", " ",$val);           //  kill <map> elements in object

            $val = str_replace("  ","", str_replace($trash1, $replace1, $val));
            //      this must be an object but not client- or server-side maped, not ActiveX and no Java Script
            if (!preg_match("/[\/]?usemap|[\/]?ismap|[\/]?javascript:|[\/]?java:|[\/]?clsid:/i", $val)) {

				if (strpos($val[0],"src")) {
//if ($element == 'video') {
//echo "\r\n\r\n<br>val Array:<br><pre>";print_r($val);echo "</pre>\r\n";
					//preg_match("/src[[:blank:]]*=[[:blank:]]*[\'\"](.*?)[\'\"]/si", $val[0], $new);
					//$new = array_reverse($new);
//echo "\r\n\r\n<br>new Array:<br><pre>";print_r($new);echo "</pre>\r\n";
					$all_media[] = $val;	//	add the new element
//echo "\r\n\r\n<br>all_media Array:<br><pre>";print_r($all_media);echo "</pre>\r\n";
//die ('Bis hier');
//}
				} else {
					// older attempt to find object for non HTML 5
					$all = $val;
					$nested = substr_count(lower_case($val[1]), $element);
					if ($nested) {
						while ($nested > '0') {
							$inner = array();
							$inner[0] = '';
							$last_pos = strrpos (lower_case($all[1]), $element);    // find inner nested  element
							$inner[1] = substr($all[1], $last_pos);                 // separate inner nested element
							if ($index_embeded == '1') {
								$inner = array_reverse($inner);         //  move <object> into [0] of array
								$all_media[] = $inner;                  // save actual element
							}
							$all[1] = substr($all[1], 0, $last_pos);    // get previous element

							$nested--;
						}
					}

					if ($index_embeded == '1') {            //  search for embeded objects
						if (preg_match("/<embed(.*?)<\/embed\s*>/si", $all[1], $regs)) {;    //get 'embed' elements
						foreach ($regs as $val) {
							$embed[0] = $val;
							$embed[1] = '';
							if (strstr($embed[0], 'embed')) {
								$all_media[] = $embed;  // save embeded element
							}
						}
						}
					}
					$all[0] = substr($all[0], '0', strpos($all[0], '>')+1);         //  kill nested elements in object[0]
					$all[1] = substr($all[1], '0', strpos($all[1], '>')+1);         //  kill nested elements in object[1]
					$all[1] = preg_replace("@<embed.*?embed>@si", " ",$all[1]);     //  kill <embed> element in object

					if (strstr(lower_case($all[1]), '<object')) {
						$all = array_reverse($all);     //  move <object> into [0] of array
					}

					$all_media[] = $all;                //  save outer element
				}
			}
        }
        if ($clear == '1') unset ($all, $val, $regs, $embed, $inner, $element);
        return $all_media;
    }

    function get_id3string($link, $build_tmp, $cl) {
        global $clear, $case_sensitive, $curl, $debug;

        $error          = '';
        $id3_string     = '';
        $localtempfile  = $link;
        $unreachable    = '';

        if ($build_tmp == '1') {        //  we need to build a temporary file
            mysqltest();
            if ($fp_remote = @fopen($link, 'rb')) {
                $localtempfile = tempnam('./tmp', 'getID3');
                if ($fp_local = fopen($localtempfile, 'wb')) {
                    //  this will read the first  kBytes of the media file
                    for ($i = 1; $i <= 200; $i++) {
                        $buffer = @fread($fp_remote, 8192);
                        fwrite($fp_local, $buffer);
                    }

                    fclose($fp_local);
                }
            } else {    //  if impossible to open by PHP function 'fopen()', try to open this image by means of cURL library
                if ($curl == '1') {    //  if cURL library is available
                    if($buffer = curl_open($link)) {
                        $localtempfile = tempnam('./tmp', 'getID3');
                        if ($fp_local = fopen($localtempfile, 'wb')) {
                            fwrite($fp_local, $buffer);
                        } else {
                            $unreachable = '1';    //   unable to write to temp-file
                        }
                        fclose($fp_local);
                    } else {
                        $unreachable = '2'; //  unable to open the remote file by cURL
                    }
                } else {
                    $unreachable = '3'; //  no cURL library available
                }
            }
            if ($debug == '2') {
                if ($unreachable) {
                    if ($unreachable == '1') $report = "Unable to write to temp-file.";
                    if ($unreachable == '2') $report = "Unable to open the remote media file $link by cURL function.";
                    if ($unreachable == '3') $report = "Unable to open media file $link by means of PHP function fopen(), nor cURL library available.";
                    printWarning($report, $cl);

                }
            }
        }

        // Remote files are not supported
        if (!preg_match('/^(ht|f)tp:\/\//', $localtempfile) && !$unreachable) {
            $getID3 = new getID3;   // Initialize getID3 engine
            $getid3->encoding = 'UTF-8';

            try {
                $This_ID3 = $getID3->analyze($localtempfile);
            }
            catch (Exception $e) {
                $rep = $e->message ;
                $report = "Problem when analysing media file. ".$rep.".";
                printWarning($report, $cl);
            }
            getid3_lib::CopyTagsToComments($This_ID3);
            //echo '<pre>'.htmlentities(print_r($This_ID3, true)).'</pre>';

            if ($build_tmp == '1') {
                unlink($localtempfile);     // Delete temporary file
                fclose($fp_remote);
            }

            $id3_array = array();
            foreach ($This_ID3 as $key0 => $val0) {         //  prepare all relevant ID3 and EXIF information  into array
                if (is_array($val0)) {

                    foreach ($This_ID3 as $key1 => $section1) {
                        foreach ($section1 as $name1 => $val1) {
                            if (is_array($val1)) {

                                foreach ($val1 as $key2 => $section2) {
                                    foreach ($section2 as $name2 => $val2) {
                                        if (is_array($val2)) {
                                            //  for future releases
                                        } else {
                                            if (strlen($val2) < 100 && $key2 != "THUMBNAIL"  && $key2 != "keyframes"  && $val2 != "") {
                                                //echo "2 $key2.$name2: $val2<br />\n";
                                                $id3_array[] = " ".$key2." >> ".$name2." ;; ".$val2." ";
                                            }
                                        }
                                    }
                                }

                            } else {
                                if (strlen($val1) < 100   && $val1 != "") {
                                    //echo "1 $key1.$name1: $val1<br />\n";
                                    $id3_array[] = " ".$key1." >> ".$name1." ;; ".$val1." ";
                                }
                            }
                        }
                    }
                } else {
                    if ($key0 != "GETID3_VERSION") {
                        //echo "0 $key0: $val0<br />\n";
                        $id3_array[] = " >> ".$key0." ;; ".$val0." ";
                    }
                }
            }

            sort($id3_array);
            $id3_string = implode("<br />",array_unique($id3_array));  //  convert array into string with <br /> as delimiter

            if ($case_sensitive == '0') {
                $id3_string = lower_ent($id3_string);
                $id3_string = lower_case($id3_string);
            }
            if ($clear == '1') {
                unset ($key0, $key1, $key2, $name1, $name2, $val0, $val1, $val2);
                unset ($section1, $section2, $This_ID3, $getID3);
                $id3_array = array();
            }
        }
        return $id3_string;
    }

    function get_exif($localtempfile) {
        global $clear, $case_sensitive;

        $id3_string = '';
        // Remote files are not supported
        if (!preg_match('/^(ht|f)tp:\/\//', $localtempfile)) {

            $getID3 = new getID3;   // Initialize getID3 engine
            $getid3->encoding = 'UTF-8';

            try {
                $This_ID3 = $getID3->analyze($localtempfile);
            }
            catch (Exception $e) {
                echo 'Problem to analyze media file '.$link.' : ' .  $e->message;
            }

            $id3_array = array();
            foreach ($This_ID3 as $key0 => $val0) {         //  prepare all relevant ID3 and EXIF information  into array
                if (is_array($val0)) {

                    foreach ($This_ID3 as $key1 => $section1) {
                        foreach ($section1 as $name1 => $val1) {
                            if (is_array($val1)) {

                                foreach ($val1 as $key2 => $section2) {
                                    foreach ($section2 as $name2 => $val2) {
                                        if (is_array($val2)) {
                                            //  for future releases
                                        } else {
                                            if (strlen($val2) < 100 && $key2 != "THUMBNAIL"  && $key2 != "keyframes"  && $val2 != "") {
                                                //echo "2 $key2.$name2: $val2<br />\n";
                                                $id3_array[] = " ".$key2." >> ".$name2." ;; ".$val2." ";
                                            }
                                        }
                                    }
                                }

                            } else {
                                if (strlen($val1) < 100   && $val1 != "") {
                                    //echo "1 $key1.$name1: $val1<br />\n";
                                    $id3_array[] = " ".$key1." >> ".$name1." ;; ".$val1." ";
                                }
                            }
                        }
                    }

                } else {
                    if ($key0 != "GETID3_VERSION") {
                        //echo "0 $key0: $val0<br />\n";
                        $id3_array[] = " >> ".$key0." ;; ".$val0." ";
                    }
                }
            }

            sort($id3_array);
            $id3_string = implode("<br />",array_unique($id3_array));  //  convert array into string with <br /> as delimiter

            if ($case_sensitive == '0') {
                $id3_string = lower_ent($id3_string);
                $id3_string = lower_case($id3_string);
            }
            if ($clear == '1') {
                unset ($key0, $key1, $key2, $name1, $name2, $val0, $val1, $val2);
                unset ($section1, $section2, $This_ID3, $getID3);
            }
        }
        return $id3_string;
    }

    function mysqltest(){
        global $log_format, $db_con, $debug, $database, $mysql_host, $mysql_user, $mysql_password, $command_line, $tmp_dir;

        //  get our current MySQL thread id and if new, save it
        $thread_id  = "".$db_con->thread_id."\r\n";
        $all_ids    = @file_get_contents("".$tmp_dir."/thread_ids.txt");
        //  if the index procedure meanwhile was manually aborted (for multi threaded indexing)
        if(!is_file("".$tmp_dir."/thread_ids.txt") || !$all_ids) {
            $db_con->kill($thread_id); //close last MySQL connection
            $cl     = '';
            $report = "Indexation manually aborted.";
            printCancel($report, $cl);
            printEndHTMLBody($cl);
            exit;   //  terminate this indexing thread completely
        }

        $mysql_fail = '';
        $check1     = '0';
        $check2     = '0';

        if ($check1 = $db_con->ping() != '1'){
            $dbtries = 0;
            while ($dbtries < 5 && $check1 = $db_con->ping() != '1'){
                $dbtries++;
                printDB_errorReport('noSQL',$command_line, '1');
                sleep(10);
                $db_con = new mysqli($mysql_host, $mysql_user, $mysql_password);
                if (!$db_con)
                $mysql_fail = '1';
                //echo "<span class='blue sml'>&nbsp;&nbsp;Cannot connect to database.<br /></span>";
                if ($db_con) {
                    $success = mysqli_select_db($link, $database);
                    if (!$success) {
                        $mysql_fail = '1';
                        //echo "<p class='blue sml'>&nbsp;&nbsp;Cannot choose database.<br /></p>";
                    }
                }
            }
            if ($check2 = $db_con->ping() != '1'){
                printDB_errorReport('noSucc',$command_line, '1');   //  failed 5 times. End of index procedure
                printDB_errorReport('aborted',$command_line, '1');
                printDB_errorReport('end',$command_line, '1');

                die('');
            }
            printStandardReport('newSQL',$command_line, '1');   //  reconnected to db
        }

        //  get our current MySQL thread id and if new, save it
        $thread_id  = "".$db_con->thread_id."\r\n";
        $all_ids    = @file("".$tmp_dir."/thread_ids.txt");
        //  if the index procedure meanwhile was manually aborted (for multi threaded indexing)
        if(!is_file("".$tmp_dir."/thread_ids.txt") || !is_array($all_ids)) {
            $db_con->kill($thread_id); //close last MySQL connection
            $cl     = '';
            $report = "Indexation manually aborted.";
            printCancel($report, $cl);
            printEndHTMLBody($cl);
            exit;   //  terminate this indexing thread completely
        }

        if (!in_array($thread_id, $all_ids)) {
            $fp         = fopen("".$tmp_dir."/thread_ids.txt","a+");    //  try to write
            if(!is_writeable("".$tmp_dir."/thread_ids.txt")) {
                echo "
                    <br /><br />
                    <p class='warnadmin cntr'>
                    <br /><br />
                    Unable to open the file .../admin/".$tmp_dir."/thread_ids.txt
                    <br /><br />
                    Index procedure aborted.
                    <br /><br /></p>
                    <br /><br />
                    <p class='evrow'><a class='bkbtn' href='admin.php?f=2' title='Go back to Admin'>Back to admin</a></p>
                    <br /><br />
                </body>
            </html>
                        ";
                exit;
            }
            if (!fwrite($fp, $thread_id)) {
                echo "
                    <br /><br />
                    <p class='warnadmin cntr'>
                    <br /><br />
                    Unable to write the actual MySQL thread-id into file ".$tmp_dir."/thread_ids.txt
                    <br /><br />
                    Index procedure aborted.
                    <br /><br /></p>
                    <br /><br />
                    <p class='evrow'><a class='bkbtn' href='admin.php?f=2' title='Go back to Admin'>Back to admin</a></p>
                    <br /><br />
                </body>
            </html>
                ";
                exit;
            }
            fclose($fp);
        }

        return $mysql_fail;
    }

    function clean_resource($result, $event) {
        global $db_con, $clear, $db_con, $debug, $cl;

        if ($clear == '1' && $result) {
            $mysql_fail = '';
            $mysql_fail = mysqltest();
            if (!$mysql_fail) {
                if ($result == '') {
                    printFreeRes($event, $cl);
                }

                /* free result set */
                $result->close();

                mysqltest();
                //  DO NOT USE THE NEXT ROW ON SHARED HOSTING SYSTEMS ! ! !   'flush query cache' could be forbidden.
                $db_con->query("FLUSH QUERY CACHE");
                if ($debug && $db_con->errno) {
                    $file       = __FILE__ ;
                    $function   = __FUNCTION__ ;
                    $err_row    = __LINE__-5;
                    mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                }
            }
        }
    }

    function valid_link($url, $select) {

        reset($select);
        $match      = '0';
        $url_parts  = parse_all_url($url);
        $path       = $url_parts['path'];   //  if exsists, remove domain and query

        foreach ($select as $key =>$value) {
            $last_dot   = strrpos($path, ".");       //  find last dot in URL string
            $suffix     = lower_case(substr($path, $last_dot));  //  extract suffix
            if (preg_match("/\.$value$/i", $suffix)) {
                $match = '1';
            }
        }
        return $match;
    }

    function bbcode($text) {
        //      encrypt Smilies
        $smiles = array();
        $smiles['&lt;:)&gt;'] = '&lt;:)&gt; beard';
        $smiles['&gt;:)'] = '&gt; Evil';
        $smiles[':)'] = ':) Smile';
        $smiles['|:('] = '|:( Headbanger';
        $smiles[':('] = ':( Angry';
        $smiles[':\'('] = ':\ Rears';
        $smiles[':o'] = ':o Amazed';
        $smiles[':D'] = ':D Big Smile';
        $smiles[':r'] = ':r Disgusted';
        $smiles[':9~'] = ':9~ Jummy!';
        $smiles[':9'] = ':9 Delicious';
        $smiles[';)'] = ';) Wink';
        $smiles[':9'] = ':9 Delicious';
        $smiles[':7'] = ':7 Love It';
        $smiles[':+'] = ':+ Clown';
        $smiles['O+'] = 'O+ Heart';
        $smiles[':*'] = ':* Kiss';
        $smiles['}:O'] = '}: Stupid Cow';
        $smiles['^)'] = '^) Married';
        $smiles['_O_'] = '_O_ Worshippie';
        $smiles[':W'] = ':W Wave goodbye';
        $smiles['^O^'] = '^O^ Way To Go!';
        $smiles[':?'] = ':? Come Again?';
        $smiles['(8&gt;'] = '(8&gt; Spy vs. Spy';
        $smiles[':Y)'] = ':Y) Vork';
        $smiles[':Z'] = 'Sleeping';
        $smiles[';('] = 'cry';
        $smiles['}:|'] = '}:| Grmbl';
        $smiles[':z'] = ':z Sleepy';
        $smiles['}&gt;'] = '}&gt; Evil';
        $smiles[':X'] = ':X Hgnn';
        $smiles[':O'] = ':O Booooring';
        $smiles['*)'] = '*) Prodent';
        $smiles[':{'] = ':{ Uhuh';
        $smiles['O-)'] = 'O-) The Saint';
        $smiles['8-)'] = '8-) Sunchaser';
        $smiles['*;'] = '*;Liefde is';
        $smiles[':Y'] = ':Y Yes';
        $smiles[':N'] = ':N No';
        $smiles[':@'] = ':@ Ashamed';
        $smiles['8)7'] = '8)7 Twisted';
        $smiles[':P'] = ':P puh';

        foreach($smiles as $grim => $txt)
        $text = str_replace($grim, ''.$txt.'', $text);

        $bb_search = array( //     convert most important bbcodes
    "/(\[)(url)(=)(['\"]?)(www\.)([^\"']*)(\\4)(.*)(\[\/url\])/siU",
    "/(\[)(url)(=)(['\"]?)([^\"']*)(\\4])(.*)(\[\/url\])/siU",
    "/(\[)(url)(\])(www\.)([^\"]*)(\[\/url\])/siU",
    "/(\[)(url)(\])([^\"']*)(\[\/url\])/siU",
    "/(\[)(email)(\])([^\"']*)(\[\/email\])/siU",
    "/(\[)(email)(=)(['\"]?)([^\"']*)(\\4])(.*)(\[\/email\])/siU",
    "/(\[)(color=)([^\W]*)(\])(.*)(\[\/color\])/siU",
    "/(\[)(size=)([^\.]*)(\])(.*)(\[\/size\])/siU",
    "/(\[)(font=)([^\W]*)(\])(.*)(\[\/font\])/siU",
    "/(\[)(b)(\])(\r\n)*(.*)(\[\/b\])/siU",
    "/(\[)(u)(\])(\r\n)*(.*)(\[\/u\])/siU",
    "/(\[)(i)(\])(\r\n)*(.*)(\[\/i\])/siU",
    "/(\[)(indent)(\])(\r\n)*(.*)(\[\/indent\])/siU",
    "/(\[)(center)(\])(\r\n)*(.*)(\[\/center\])/siU",
    "/(\[)(left)(\])(\r\n)*(.*)(\[\/left\])/siU",
    "/(\[)(right)(\])(\r\n)*(.*)(\[\/right\])/siU",
    "/(\[)(quote)(\])(\r\n)*(.*)(\[\/quote\])/siU",
    "/(\[)(code)(\])(\r\n)*(.*)(\[\/code\])/siU",
    "/(\[)(pre)(\])(\r\n)*(.*)(\[\/pre\])/siU",
    "/(\[)(img)(\])(?!javascript:)(\r\n)*([^\"']*)(\[\/img\])/siU",
    "/about:/si");

        $replace = array(
    "<a href=\"http://www.\\6\" target=\"_blank\">\\8</a>",
    "<a href=\"\\5\" target=\"_blank\">\\7</a>",
    "<a href=\"http://www.\\5\" target=\"_blank\">\\5</a>",
    "<a href=\"\\4\" target=\"_blank\">\\4</a>",
    "<a href=\"mailto:\\4\" target=\"_blank\">\\4</a>",
    "<a href=\"mailto:\\5\" target=\"_blank\">\\7</a>",
    "<span style=\"color:\\3;\">\\5</span>",
    "<span style=\"font-size:\\3;\">\\5</span>",
    "<span style=\"font-family:\\3;\">\\5</span>",
    "<b>\\5</b>",
    "<u>\\5</u>",
    "<i>\\5</i>",
    "<blockquote>\\5</blockquote>",
    "<center>\\5</center>",
    "<left>\\5</left>",
    "<right>\\5</right>",
    "<blockquote>Quote:
<hr>
\\5<hr></blockquote>",
    "<blockquote>Code:
<hr>
\\5<hr></blockquote>",
    "<pre>Code:
\\5</pre>",
    "<img src=\"\\5\" border=\"0\">",
    "about: ");
        $text= preg_replace($bb_search, $replace, $text);

        //      Create surrounding spaces for not yet encoded BB's
        $text = str_replace("[", " [", $text);
        $text = str_replace("]", "] ", $text);

        return ($text);
    }

    function microtime_float(){
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    function index_url($url, $level, $site_id, $md5sum, $domain, $indexdate, $sessid, $can_leave_domain, $reindex, $use_nofollow, $cl, $use_robot, $use_pref, $url_inc, $url_not_inc, $num, $edititle, $edidescription) {
        global $db_con, $entities, $min_delay, $link_check, $command_line, $min_words_per_page, $dup_content, $dup_url, $quotes, $dup_quotes, $all_quotes, $plus_nr, $use_prefcharset;
        global $min_words_per_page,  $supdomain, $smp, $follow_sitemap,  $max_links, $realnum, $local, $tmp_dir, $auto_add, $admin_email, $idna, $conv_puny;
        global $mysql_table_prefix, $user_agent, $tmp_urls, $delay_time, $domain_arr, $home_charset, $charSet, $url_status, $redir_count, $lang_detect, $lang_Key;
        global $debug, $common, $use_white1, $use_white2, $use_black, $whitelist, $blacklist, $clear, $abslinks, $utf8_verify, $webshot, $ex_media, $del_secchars;
        global $index_media, $index_image, $suppress_suffix, $imagelist, $min_image_x, $min_image_y, $dup_media, $index_alt, $no_log, $index_rss, $sp_sitemap;
        global $index_audio, $audiolist, $index_video, $videolist, $index_embeded, $rss_template, $index_csv, $delim, $ext, $index_id3, $dba_act, $index_xml_pr;
        global $converter_dir, $dict_dir, $smap_dir, $cn_seg, $jp_seg, $index_framesets, $index_iframes, $cdata, $dc, $preferred, $index_rar, $index_zip, $curl, $del_secchars, $div_all, $div_hyphen;
        global $docs, $only_docs, $only_links, $case_sensitive, $vowels, $noacc_el, $include_dir, $thumb_folder, $js_reloc, $server_char, $ignore_text, $xml_pr_feeds0, $xml_pr_feeds, $file_keys;
        global $latin_ligatures, $phon_trans, $liga, $inst_dir, $install_dir, $admin_dir, $admin_url, $install_url, $feed_pure, $not_cookie, $domaincb, $index_youtube, $sort_alpha, $tag_min;

        if ($feed_pure == '1') {
            $index_media  = '0';    //  overwrite settings in config file
        }

        $data                   = array();
        $cn_data                = array();
        $url_parts              = array();
        $url_status             = array();
        $url_status['black']    = '';
        $contents               = array();
        $links                  = array();
        $wordarray              = array();

        $topic                  = '';
        $url_reloc              = '';
        $js_link                = '';
        $document               = '';
        $file                   = '';
        $file0                  = '';
        $raw_file               = '';
        $seg_data               = '';

        $url                    = $db_con->real_escape_string($url);
        $index_url              = $url;
        $comment                = $db_con->real_escape_string("Automatically added during index procedure, as this domain is not yet available in 'Sites' menu.");
        $admin_email            = $db_con->real_escape_string($admin_email);

        if ($debug == '0'){
            if (function_exists("ini_set")) {
                ini_set("display_errors", "0");
            }
            error_reporting(0) ;
        } else {
            error_reporting (E_ERROR) ;     //  otherwise  a non existing siemap.xml  would always cause a warning message
        }

        $needsReindex   = 1;
        $deletable      = 0;
        $nohost         = 1;
        $i              = 0;
        $nohost_count   = 5;    //  defines count of attempts to get in contact with the server
        //  check URL status
        while ($i < $nohost_count && $nohost){
            $url_status     = url_status($url, $site_id, $sessid);
            if (!stristr($url_status['state'], "NOHOST")) {
                $nohost = '';   //  reset for successfull attempt
            }
            $i++;
        }

        //  check for emergency exit
        if ($url_status['aborted'] == '1' || stristr($url_status['state'], "NOHOST")){
            return $url_status;
        }

        //  check for UFO file or invalid suffix
        if (stristr($url_status['state'], "ufo")){
            return $url_status;
        }


        //  check for 'unreachable' links and if it is a known URL, delete all keyword relationships, former indexed from the meanwhile unreachable link
        if (stristr($url_status['state'], "unreachable")) {

            //printStandardReport('unreachable',$command_line, $no_log);

            $sql_query = "SELECT link_id from ".$mysql_table_prefix."links where url='$url'";
            $result = $db_con->query($sql_query);
            if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-5;
                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
            }
            $row = $result->fetch_array(MYSQLI_NUM);
            $link_id = $row[0];

            if ($link_id) {
                for ($i=0;$i<=15; $i++) {
                    $char = dechex($i);
                    $sql_query = "DELETE from ".$mysql_table_prefix."link_keyword$char where link_id=$link_id";
                    $db_con->query ($sql_query);
                    if ($debug && $db_con->errno) {
                        $file       = __FILE__ ;
                        $function   = __FUNCTION__ ;
                        $err_row    = __LINE__-5;
                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                    }
                }
                //  here we should delete the keywords associated only to the unreachable link
                //  but this takes too much time during index procedure
                //  the admin is asked to do it manually by using the regarding option in 'Clean' menue
                //
                //  delete the meanwhile unreachable link from db
                $sql_query = "DELETE from ".$mysql_table_prefix."links where link_id = $link_id";
                $db_con->query ($sql_query);
                if ($debug && $db_con->errno) {
                    $file       = __FILE__ ;
                    $function   = __FUNCTION__ ;
                    $err_row    = __LINE__-5;
                    mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                }

            }
            return $url_status;
        }

        //  check for overwritten URL, forced by the header, sending content PLUS any redirected URL
        if ($url_status['url_over']  && !$url_status['relocate']) {
            $url = $url_status['url_over'];
        }

        $url_parts  = parse_all_url($url);
        $thislevel  = $level - 1;

        //  redirected URL ?
        if ($url_status['relocate']){          //  if relocated,  print message, verify the new URL, and redirect to new URL

            //  check for redirection on an already indexed link
            $known_link = '';
            $sql_query      = "SELECT * from ".$mysql_table_prefix."links where url='$url'";
            $result     = $db_con->query($sql_query);
            if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-5;
                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
            }
            $known_link = $result->num_rows;

            if ($known_link) {
                $urlo_status['state']    = "URL was redirected to an already indexed page.<br />In order to prevent infinite indexation, this is not supported by Sphider-plus.<br />Indexation aborted for this URL";
                $url_status['aborted']  = 1;
                return $url_status;
            }

            //  remove the original URL from temp table. The relocated URL will be added later on.
            mysqltest();
            $sql_query = "DELETE from ".$mysql_table_prefix."temp where link = '$url' AND id = '$sessid'";
            $db_con->query ($sql_query);
            if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-5;
                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
            }

            $new_url    = $url_status['path']; //  URL of first redirection

            //  remove the redirected URL, which eventually is  already stored in db
            //  before finally storing in db, we need to check for correct redirection.
            $sql_query = "DELETE from ".$mysql_table_prefix."temp where link = '$new_url' AND id = '$sessid'";
            $db_con->query ($sql_query);
            if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-5;
                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
            }
            //  now special processing for relative links
            if (!strpos(substr($new_url, 0, 5), "ttp")) {
                $new_url = make_abs($new_url, $index_url);
            }

            if ($url == $new_url && $url_status['file']) {
                $url_status['relocate'] = '';   //  remove this redirection, as it is 'in it selves'
                $url_status['state']    = "ok"; //  try to index the conteent
            }



            $care_excl = '1';   //  care file suffixed to be excluded
            $relocated = '1';   //  URL is relocated

            if ($debug) {
                printRedirected($url_status['relocate'], $url_status['path'], $cl);
            }

            $count = "1";
            while ($count <= $redir_count && $url_status['relocate'] && !$url_status['aborted']) {

                //  check this redirection
                $url_status = url_status($new_url, $site_id, $sessid);

                if ($url_status['path']) {
                    $new_url    = $url_status['path'];  //  URL of another redirections
                    //  now special processing for relative links
                    if (!strpos(substr($new_url, 0, 5), "ttp")) {
                        $new_url = make_abs($new_url, $index_url);
                    }
                }
                if ($debug) {
                    printRedirected($url_status['relocate'], $url_status['path'], $cl);
                }
                $count++;
            }

            if ($url_status['relocate']) {
                $url_status['aborted']  = 1;
                $url_status['state']    = "<br />Indexation aborted because of too many redirections.<br />";
                return $url_status;
            }

            if ($url_status['state'] != "ok") {
                $code = $url_status['state'];

                //  check for most common client errors
                if (!preg_match("/401|402|403|404/", $code)) {
                    $url_status['aborted']  = 1;    //  end indexing for cmplete site
                } else {
                    $url_status['aborted']  = '';   //  abort only for this page
                }

                if (strstr($code, "401")) {
                    $code = "401 (Authentication required)";
                }
                if (strstr($code, "403")) {
                    $code = "403 (Forbidden)";
                }
                if (strstr($code, "404")) {
                    $code = "404 (Not found)";
                }
                $url_status['state']    = "<br />Indexation aborted because of code: $code.<br />";
            }

            $new_url_parts  = parse_all_url($new_url);

            if ($can_leave_domain != "1" && $url_parts['host'] != $new_url_parts['host']) {
                if ($debug){
                    printStandardReport('outofDomain',$command_line, $no_log);
                }
                //  get link id of this 'out of domain' relocation
                $sql_query = "SELECT link_id from ".$mysql_table_prefix."links where url='$url'";
                $result = $db_con->query($sql_query);
                if ($debug && $db_con->errno) {
                    $file       = __FILE__ ;
                    $function   = __FUNCTION__ ;
                    $err_row    = __LINE__-5;
                    mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                }
                $row = $result->fetch_array(MYSQLI_NUM);
                $link_id = $row[0];

                if ($link_id) {
                    for ($i=0;$i<=15; $i++) {
                        $char = dechex($i);
                        $sql_query = "DELETE from ".$mysql_table_prefix."link_keyword$char where link_id=$link_id";
                        $db_con->query ($sql_query);
                        if ($debug && $db_con->errno) {
                            $file       = __FILE__ ;
                            $function   = __FUNCTION__ ;
                            $err_row    = __LINE__-5;
                            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                        }
                    }
                    //  here we should delete the keywords associated only to the out of domain link
                    //  but this takes too much time during index procedure
                    //  the admin is asked to do it manually by using the regarding option in 'Clean' menue
                    //
                    //  delete the out of domain link from db
                    $sql_query = "DELETE from ".$mysql_table_prefix."links where link_id = $link_id";
                    $db_con->query ($sql_query);
                    if ($debug && $db_con->errno) {
                        $file       = __FILE__ ;
                        $function   = __FUNCTION__ ;
                        $err_row    = __LINE__-5;
                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                    }
                }

                $url_status['status']   = 'unreachable';
                $url_status['file']     = '';

                return $url_status;
            }

            //  check final URL (which might be the 3. redirection)
            //  and puriify final redirected URL
            $url = $db_con->real_escape_string(url_purify($new_url, $index_url, $can_leave_domain, $care_excl, $relocated, $local_redir));

            // valid file suffix for the redirection??
            if($url) {
                if ($care_excl == '1') {    //  care about non-accepted suffixes

					foreach ($ext as $excl){
                        if (preg_match("/\.$excl($|\?)/i", $url)){  //  if suffix is at the end of the link, or followd by a question mark
                            $url_status['state'] = 'Found: Not supported suffix'; //  error message
                            return $url_status;
                        }
					}
                }
            }

            if (!$url) {
                $link_parts = parse_all_url($url);
                $host = $link_parts['host'];
                $sql_query = "DELETE from ".$mysql_table_prefix."temp where link like '$index_url' AND id = '$sessid' OR relo_link like '$url'";
                $db_con->query ($sql_query);
                if ($debug && $db_con->errno) {
                    $file       = __FILE__ ;
                    $function   = __FUNCTION__ ;
                    $err_row    = __LINE__-5;
                    mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                }

                $url_status['aborted']  = 1;
                $url_status['state']    = "<br />Indexation aborted because of undefined redirection error.<br />";
                return $url_status;
            }

             //  abort indexation, if the redirected URL is equal to calling URL
            if ($url == 'self') {
                $link_parts = parse_all_url($url);
                $host = $link_parts['host'];
                $sql_query = "DELETE from ".$mysql_table_prefix."temp where link like '$url' AND id = '$sessid' OR relo_link like '$url'";
                $db_con->query ($sql_query);
                if ($debug && $db_con->errno) {
                    $file       = __FILE__ ;
                    $function   = __FUNCTION__ ;
                    $err_row    = __LINE__-5;
                    mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                }
                $url_status['aborted']  = 1;
                $url_status['state']    = "<br />Indexation aborted for this page, because the redirection was a link in it selves.<br />Blocked by Sphider-plus, because this could end in an infinite indexation loop.<br />";
                return $url_status;
            }

            //  abort indexation, if the redirected URL contains invalid file suffix
            if ($url == 'excl') {
                $link_parts = parse_all_url($url);
                $host = $link_parts['host'];
                $sql_query = "DELETE from ".$mysql_table_prefix."temp where link like '$url' AND id = '$sessid' OR relo_link like '$url'";
                $db_con->query ($sql_query);
                if ($debug && $db_con->errno) {
                    $file       = __FILE__ ;
                    $function   = __FUNCTION__ ;
                    $err_row    = __LINE__-5;
                    mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                }
                $url_status['aborted']  = 1;
                $url_status['state']    = "<br />Indexation aborted because the redirected link does not meet the URL suffix conditions.<br />";
                return $url_status;
            }

            //  abort indexation, because purifing the redirected URL failed
            if (!strstr($url, "//")) {
                $sql_query = "DELETE from ".$mysql_table_prefix."temp where link like '$url' AND id = '$sessid' OR relo_link like '$url'";
                $db_con->query ($sql_query);
                if ($debug && $db_con->errno) {
                    $file       = __FILE__ ;
                    $function   = __FUNCTION__ ;
                    $err_row    = __LINE__-5;
                    mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                }
                $url_status['aborted']  = 1;
                $url_status['state']    = "<br />Indexation aborted because $url is not supported.<br />";
                return $url_status;
            }

            //  abort indexation, if redirected URL met 'must/must not include' string rule
            if (!check_include($url, $url_inc, $url_not_inc )) {
                $link_parts = parse_all_url($url);
                $host = $link_parts['host'];
                $sql_query = "DELETE from ".$mysql_table_prefix."temp where link like '$url' AND id = '$sessid' OR relo_link like '$url'";
                $db_con->query ($sql_query);
                if ($debug && $db_con->errno) {
                    $file       = __FILE__ ;
                    $function   = __FUNCTION__ ;
                    $err_row    = __LINE__-5;
                    mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                }
                $url_status['aborted']  = 1;
                $url_status['state']    = "<br />Indexation aborted because the redirected link does not meet<br />the URL 'must include' or 'must not include' conditions.<br />";
                return $url_status;
            }

            //  if redirected URL is already known and in database: abort
            $rows0 = '';
            $rows1 = '';

            mysqltest();
            $sql_query = "SELECT url from ".$mysql_table_prefix."sites where url like '$url'";
            $result = $db_con->query($sql_query);
            if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-5;
                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
            }
            $rows0 = $result->num_rows;

            $sql_query  = "SELECT * from ".$mysql_table_prefix."links where url='$url'";
            $result     = $db_con->query($sql_query);
            if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-5;
                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
            }

            $known_link = $result->fetch_array(MYSQLI_NUM);
            $md5        = $known_link[8];

            if ($clear == 1) clean_resource($result, '02') ;

            if ($rows0) {
                $url_status['state']    = "<br />URL already in database (as a site URL). Index aborted.<br />";
                $url_status['aborted']  = 1;
                return $url_status;
            }

            // if known link, which is already indexed (because containing the md5 checksum), enter here
            if ($known_link[8]) {

                $count = $known_link[15];
                $count++;

                if ($count > $redir_count) {    //  abort indexation
                    $url_status['state']    = "<br />$count. attempt to redirect in the same (already indexed) URL, <br />which is no longer accepted by Sphider-plus. Indexation aborted for this site.<br />";
                    $url_status['aborted']  = 1;
                    return $url_status;
                } else {
                    $sql_query = "UPDATE ".$mysql_table_prefix."links set relo_count='$count' where url='$url'";
                    $db_con->query($sql_query);
                }
            }

            //  add redirected URL to temp table, if not yet known
            $sql_query = "SELECT link from ".$mysql_table_prefix."temp where link='$url' && id = '$sessid'";
            $result = $db_con->query($sql_query);
            if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-5;
                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
            }
            $rows = $result->num_rows;

            if ($rows == 0) {
                $sql_query = "INSERT into ".$mysql_table_prefix."temp (link, level, id, relo_count) values ('$url', '$level', '$sessid', '1')";
                $db_con->query($sql_query);
                if ($debug && $db_con->errno) {
                    $file       = __FILE__ ;
                    $function   = __FUNCTION__ ;
                    $err_row    = __LINE__-5;
                    mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                }
            }
            if ($clear == 1) clean_resource($result, '02') ;

            //  at the end of redirect, rebuild the url parts from the redirected URL.
            //  This is the final URL, which will be indexed
            $url_parts  = parse_all_url($url);
        }   //  end check any redirection/relocation

        //  if a JavaScript file is currently indexed?
        $suffix = substr($url, strrpos($url, ".")+1);
        $suffix = str_replace("/", "", $suffix);
        if (strlen($suffix) < "5") {
            if (preg_match("/js$/", $suffix)) {
                $js_link    = 1;    //  activate JS switch
            }
        }

        if ($smp != 1 && $follow_sitemap == 1) {        //  enter here if we don't already know a valid sitemap and if admin settings allowed us to do so
            $tmp_urls 	= get_temp_urls($sessid);   	//  reload previous temp
            $url2 		= remove_sessid(convert_url($url));
			$too_old	= '';		//	in order to start, will be overwritten with actual value

            // get folder where sitemap should be and if exists, cut existing filename, suffix and subfolder
            $host 			= parse_addr($url2);
            $hostname 		= $host[host];
            $more_sitemaps	= array ();
			$these_links 	= array();
			$links 			= array();

            if ($hostname == 'localhost') $host1 = str_replace($local,'',$url2);
            $pos = strpos($host1, "/");                //      on local server delete all behind the /

            if ($pos) $host1 = substr($host1,0,$pos);   //      build full adress again, now only the host
            if ($hostname == 'localhost') {
                $url2 = ("".$local."".$host1."");
            }else {
                $url2 = ("$host[scheme]://$hostname");
            }

			//	search for private Sphider-plus relevant sp-sitemap.xml file
			if ($sp_sitemap == "1"){
				$sitemap_name   = "sp-sitemap";         	//      name for private sitemap file
				$input_file     = "$url2/$sitemap_name";	//      create path to sitemap
				$smp_file       = "$smap_dir/current_sitemap_".date("Y-m-d_H.i.s")."_".mt_rand().".xml"; //      destination for sitemap file
				$smap_found     = '';
				$indexed_map    = '';
				$map_cont       = '';
				$priv_sitemap	= '';
				//  try to fetch individual sitemap url from database
				mysqltest();
				$sql_query = "SELECT smap_url from ".$mysql_table_prefix."sites where site_id='$site_id'";
				$result = $db_con->query($sql_query);
				if ($debug && $db_con->errno) {
					$file       = __FILE__ ;
					$function   = __FUNCTION__ ;
					$err_row    = __LINE__-5;
					mysql_fault($db_con, $sql_query, $file, $function, $err_row);
				}
				$row = $result->fetch_array(MYSQLI_NUM);
				if (preg_match("/http:\/\//", $row[0])) {  //   use the individual sitemap
					$input_file = preg_replace("/.xml.gz|.xml/i", "", $row[0]);
				}

				$file = "".$input_file.".xml";
				if ($fd = @fopen($file, "r")) {    //  uncompressed ?
					//if ($zd = @gzopen("".$input_file.".xml", "r")) {    //  uncompressed ?

					$map_cont = @stream_get_contents($fd);
					if ($map_cont && strpos($map_cont, "schemas/sitemap")) {        //  if we were able to read it
						$smap_found 	= '1';
						$priv_sitemap	= '1';
					}
					fclose($fd);
				}

				$gz_file = "".$input_file.".xml.gz";
				if (!$smap_found && $zd = @fopen("compress.zlib://$gz_file", "r")) {  // compressed  ?
					//if (!$smap_found && $zd = @gzopen("".$input_file.".xml.gz", "r")) {  // compressed  ?
					$map_cont = @gzread($zd, 10485760);      //  max. 10 MB (might be too large for some server)
					gzclose($zd);
					if ($map_cont && strpos($map_cont, "schemas/sitemap")) {
						$smap_found 	= '1';
						$priv_sitemap	= '1';
					}
				}
			}

			//	search for standard sitemap file, which is globally used (sitemap.xml)
			if ($smap_found != '1') {
				$sitemap_name   = "sitemap";				//      standard name for sitemap file
				$input_file     = "$url2/$sitemap_name";	//      create path to sitemap
				$smp_file       = "$smap_dir/current_sitemap_".date("Y-m-d_H.i.s")."_".mt_rand().".xml"; //      destination for sitemap file
				$smap_found     = '';
				$indexed_map    = '';
				$map_cont       = '';
				$priv_sitemap	= '';

				//  try to fetch individual sitemap url from database
				mysqltest();
				$sql_query = "SELECT smap_url from ".$mysql_table_prefix."sites where site_id='$site_id'";
				$result = $db_con->query($sql_query);
				if ($debug && $db_con->errno) {
					$file       = __FILE__ ;
					$function   = __FUNCTION__ ;
					$err_row    = __LINE__-5;
					mysql_fault($db_con, $sql_query, $file, $function, $err_row);
				}
				$row = $result->fetch_array(MYSQLI_NUM);
				if (preg_match("/http:\/\//", $row[0])) {  //   use the individual sitemap
					$input_file = preg_replace("/.xml.gz|.xml/i", "", $row[0]);
				}

				$file = "".$input_file.".xml";
				if ($fd = @fopen($file, "r")) {    //  uncompressed ?
					//if ($zd = @gzopen("".$input_file.".xml", "r")) {    //  uncompressed ?

					$map_cont = @stream_get_contents($fd);
					if ($map_cont && strpos($map_cont, "schemas/sitemap")) {        //  if we were able to read it
						$smap_found = '1';
					}
					fclose($fd);
				}

				$gz_file = "".$input_file.".xml.gz";
				if (!$smap_found && $zd = @fopen("compress.zlib://$gz_file", "r")) {  // compressed  ?
					//if (!$smap_found && $zd = @gzopen("".$input_file.".xml.gz", "r")) {  // compressed  ?
					$map_cont = @gzread($zd, 10485760);      //  max. 10 MB (might be too large for some server)
					gzclose($zd);
					if ($map_cont && strpos($map_cont, "schemas/sitemap")) {
						$smap_found = '1';
					}
				}
			}
//echo "\r\n\r\n<br>map_cont Array:<br><pre>";print_r($map_cont);echo "</pre>\r\n";
            if($smap_found == '1') {
                if ($debug != '0') {    //      create a sitemap file of current sitemap.xml
                    file_put_contents($smp_file, $map_cont);
                }
				// function get_sitemap() and function store_links() will build a new temp table
                $sql_query = "DELETE from ".$mysql_table_prefix."temp";
				$result = $db_con->query($sql_query);
				if ($debug && $db_con->errno) {
					$file       = __FILE__ ;
					$function   = __FUNCTION__ ;
					$err_row    = __LINE__-5;
					mysql_fault($db_con, $sql_query, $file, $function, $err_row);
				}

				if (stristr($map_cont, "<sitemapindex")) {   //      if current sitemap file is an index file
                    printStandardReport('validSitemapInd',$command_line, $no_log);
                    $get_maps = simplexml_load_string ($map_cont);
                    if ($get_maps) {
                        reset($get_maps);
                        foreach($get_maps as $map_x) {
                            $new_links[] =($map_x->loc); //   get all links to further sitemap files
                        }
                        if (is_array($new_links)) {     //      if we found more sitemap.xml files
                            $new_links = explode(",",(implode(",",$new_links))); // destroy SimpleXMLElement Object and get the link array
                            $new_links = array_slice($new_links, 0, $max_links);
                            $indexed_map = '1';
                            $i = '0';
                            //echo "\r\n\r\n<br>new_links Array:<br><pre>";print_r($new_links);echo "</pre>\r\n";
                            foreach($new_links as $input_file) {
                                $these_links = get_sitemap($input_file, $indexed_map, $mysql_table_prefix); // now extract page links from this sitemap file
                                //echo "\r\n\r\n<br>these_links Array:<br><pre>";print_r($these_links);echo "</pre>\r\n";
								if (strlen($these_links[0]) > 10) {
                                    reset($these_links);
                                    store_newLinks($these_links, $level, $sessid);
                                    $smp = '1';  //     there were valid sitemap files and we stored the new links
                                    $i++;
                                } else {
									if(strpos($these_links[0], "=")) {
										printStandardReport('too_old',$command_line, $no_log);				//	sitemap.xml lastmod value is older than last index date
									} else {
										printStandardReport('invalidSecSitemap',$command_line, $no_log);	//  unable to extract links from secondary sitemap file
									}
								}
                            }
                            printValidSecSmap($i, $cl);
                            unset ($input_file, $map_cont, $new_links);
                        } else {
                            printStandardReport('invalidSecSitemap',$command_line, $no_log);    //  unable to extract links from secondary sitemap file
                        }
                    } else {
                        printStandardReport('invalidSitemapInd',$command_line, $no_log);        //  unable to extract links from sitemap INDEX  file
                    }
                } else {
                    $smp 		= '1';  //     there was one valid sitemap and we stored the new links
					$too_old	= '';
                    $links = get_sitemap($map_cont, $indexed_map, $mysql_table_prefix);         // extract links from sitemap.xml
//echo "\r\n\r\n<br>links Array:<br><pre>";print_r($links);echo "</pre>\r\n";
					if (strlen($links[0]) > 10) {
                        reset ($links);
//echo "\r\n\r\n<br>sitemmap links Array:<br><pre>";print_r($links);echo "</pre>\r\n";
                        store_newLinks($links, $level, $sessid);
						if ($priv_sitemap == '1'){
							printStandardReport('validPrivSitemap',$command_line, $no_log);
						} else {
							printStandardReport('validSitemap',$command_line, $no_log);
						}
                    } else {
						if(strpos($links[0], "=")) {
							$too_old = 1;
							printStandardReport('too_old',$command_line, $no_log);	//sitemap.xml lastmod value is older than last index date
						} else {
							printStandardReport('invalidSitemap',$command_line, $no_log);
						}
                    }
                    unset ($links);
                }
            }
        }

        if ($debug == '0'){
            if (function_exists("ini_set")) {
                ini_set("display_errors", "0");
            }
        }

        if ($url_status['state'] == 'ok') {

            $OKtoIndex = 1;
            $file_read_error = 0;

            if (time() - $delay_time < $min_delay) {
                sleep ($min_delay- (time() - $delay_time));
            }

            if ($url_status['file']) {
                $file = $url_status['file'];
            } else {
                $url_status['state'] = "Unable to read the content of the file.<br />$url does not deliver any content.";
                $realnum -- ;
            }
        }

		//	try to find XML product feeds
		$my_suffix = substr($url, -4);

		if ($my_suffix == ".xml" && $index_xml_pr == 1 && !strstr($url, "sitemap")) {
			$content = @file_get_contents($url);

			if ($content !== FALSE) {

				//	get encoding of current XML and, if required, convert it to UTF-8
				if (preg_match("'encoding=[\'\"](.*?)[\'\"]'si", substr($content, 0, 300), $regs)) {
					$charaSet	= trim(strtoupper($regs[1]));
					if ($charaSet != "UTF-8") {
						$content = iconv( "UTF-8", "$charaSet//IGNORE", $content);
					}
				}

				// check correct charset definition and encoding
				$valid_utf8 = (@iconv('UTF-8','UTF-8',$content) === $content);

				if ($valid_utf8 == "1") {
					//	kill unwanted characters from the XML string and get the XML elements
					$temp	= preg_replace('/&(?!(quot|amp|pos|lt|gt);)/', '&amp;', $content);

					$xml	= simplexml_load_string($temp, 'SimpleXMLElement', LIBXML_NOEMPTYTAG | LIBXML_SCHEMA_CREATE | LIBXML_NOBLANKS | LIBXML_NOCDATA);
//echo "\r\n\r\n<br>xml Array:<br><pre>";print_r($xml);echo "</pre>\r\n";
					preg_match("{Content-Type: *([a-z/.-]*)}i", strtolower($xml), $regs);
					//	check for any other XML, which might not be a product feed
					if (($regs[1] != 'application/xml' || $regs[1] != 'application/rss' || $regs[1] != 'text/xml' || $regs[1] != 'text/xhtml' || $regs[1] != 'application/xhtml')) {
						if($xml === FALSE) {
							if ($debug > 0 ) {
								printStandardReport('invalidProductFeed',$command_line, $no_log);
							}
						} else {
							$xml_pro_feed		= '';		//	switch for further index procedure
							$invalid_pr_feed 	= '';
							$file_values		= '';		//	will hold all product attribute values
							$article 			= array();	//	intermediate array, which holds keys and values of one product
							$articles			= array();	//	will hold all product keys and their values
							$count 				= '0';		//	internal counter for each product
							$product_file 		= '';		//	will ontain all attributes for each detected product
							$file				= '';		//	will be build as pure string
							$fulltxt			= '';		//	will be build from $product_file including formatting
							$data				= array();	//	will hold several meta data (like HTML) for db storage
							//	parse all elements of this product feed
							foreach ($xml as $item) {
//echo "<br />Met new article $count -----------------------------------------------<br />\r\n";
								$item = SimpleXML2Array($item);	// convert object to array
//echo "\r\n\r\n<br>item Array0:<br><pre>";print_r($item);echo "</pre>\r\n";

								reset($item);
								$article 	= array();
								$i			= 0;		//	counter for valid keys
//echo "\r\n\r\n<br>xmll_pr_feeds Array:<br><pre>";print_r($xml_pr_feeds);echo "</pre>\r\n";
								//	walk through all tags names as defined in .../include/common/xml_product_feeds.txt
								foreach ($xml_pr_feeds as $all_needles) {
//echo "\r\n\r\n<br /> all_needles string: '$all_needles'<br />\r\n";

									//	build array from all needles
									$needles = explode(",", $all_needles);
//echo "\r\n\r\n<br>needles Array:<br><pre>";print_r($needles);echo "</pre>\r\n";

									foreach($needles as $needle) {

//echo "\r\n\r\n<br /> needle: '$needle'<br />\r\n";
										$needle = trim($needle);
										if (array_key_exists($needle, $item)) {
											$act_item = array($item);
//echo "\r\n\r\n<br>act_item Array:<br><pre>";print_r($act_item);echo "</pre>\r\n";
											$line = array_column($act_item, $needle);
//echo "\r\n\r\n<br> line Array0:<br><pre>";print_r($line);echo "</pre>\r\n";
											if (!is_array($line[0])) {
												$i++;										// increase counter for valid key found
												$this_value = $line[0];
//echo "\r\n\r\n<br /> this_value: '$this_value'<br />\r\n";
												if (preg_match("@https|http@si", $this_value)) {	// if the value is a link, build it up
													$this_link = "<a href='$this_value' title='Open in a new tab' target='_blank' >$this_value</a>";
//echo "\r\n\r\n<br /> this_link: '$this_link'<br />\r\n";
													$line[0] = $this_link;
												}
												$article[$needles[0]] = $line[0];			// add only the first value of this line;

//echo "\r\n\r\n<br>updated article Array0:<br><pre>";print_r($article);echo "</pre>\r\n";

											} else {

												// process 'nested tags' here
//echo "\r\n\r\n<br>nested line Array1:<br><pre>";print_r($line);echo "</pre>\r\n";
												$n_count= count($line[0]);

												if ($n_count >= 1) {		//	if line is not an empty array

													//	required for processing the nested attributes
													$n_item 		= $line;
													$n_xml_pr_feeds	= $xml_pr_feeds;
													reset($n_xml_pr_feeds);

													foreach ($n_xml_pr_feeds as $n_all_needles) {

														$n_needles = explode(",", $n_all_needles);	// build array from all needles
//echo "\r\n\r\n<br>n_needles Array:<br><pre>";print_r($n_needles);echo "</pre>\r\n";
														foreach($n_needles as $n_needle) {
															$n_needle = trim($n_needle);

															if (array_key_exists($n_needle, $n_item[0])) {
																$n_act_item = array($n_item[0]);

																$n_line = array_column($n_act_item, $n_needle);		// unfortunately it doesn't work correctly here

																foreach ($n_line as $row) {		// additionally required to extract the value
																	$value = $row;
//echo "\r\n\r\n<br /> value: '$value'<br />\r\n";
																	if (preg_match("@https|http@si", $value)) {	// if the value is a link, build it up
																		$this_link = "<a href='$value' title='Open in a new tab' target='_blank' >$value</a>";
																		$value = $this_link;
																	}
																}

																$n_line		= array($n_needle=>$value);			// build the attribute array
//echo "\r\n\r\n<br>n_line Array:<br><pre>";print_r($n_line);echo "</pre>\r\n";
																$article	= array_merge($article, $n_line);	// add this nested attribute to the article array

																$i++;											// increase counter for valid (nested) key found
//echo "\r\n\r\n<br>updated article Array1:<br><pre>";print_r($article);echo "</pre>\r\n";
															}
														}
													}
												}
											}
										}
									}
								}

								if ($i >= $tag_min) {
/*
									//	find all other tags, not used in this product feed, and add them to the feed
									reset($xml_pr_feeds0);
									while (list($this_key, $value) = each($xml_pr_feeds0))
									if (!array_key_exists($this_key, $article)) {
//echo "\r\n\r\n<br /> this_key: '$this_key'<br />\r\n";
										$act_line = array_flip(array($this_key));	//	replacement for each(), which has been DEPRECATED as of PHP 7.2.0
//echo "\r\n\r\n<br>act_line Array:<br><pre>";print_r($act_line);echo "</pre>\r\n";
										$article = array_merge($article, $act_line);
									}
//echo "\r\n\r\n<br>article0 Array:<br><pre>";print_r($article);echo "</pre>\r\n";
*/
									if ($sort_alpha == 1) {
										// case sensitive sorting the keys
										ksort($article, SORT_FLAG_CASE | SORT_NATURAL);
									}

									$keys 			= array_keys($article);
									$i 				= 0;
//echo "\r\n\r\n<br>keys Array:<br><pre>";print_r($keys);echo "</pre>\r\n";
									foreach ($article as $value) {
										$act_key = $keys[$i];
										$product_file .= " $act_key = $value    <br />";
										$i++;
									}
									$keys = '';
									$keys 			.= implode(", ", array_keys($article));	// build a string from all involved array keys
									$keys			= " $keys ";							// add blanks before and after the string
									$values 		 = implode(", ", $article);				// build a string from all array values
									$file_values	.= " $values ";							// add blanks before and after the string

									//	finally add all attributes to one array holding all products and all their attributes
									//$articles[$count] = $article;
//echo "\r\n\r\n<br>articles Array:<br><pre>";print_r($articles);echo "</pre>\r\n";


									$count++;
									$product_file .= "<br /><br />";	//	at the end of all attributes of this product
//echo "\r\n\r\n<br /> product_file:<br /> '$product_file'<br />\r\n";
								} else {
									printStandardReport('invalidXMLtagAmount',$command_line, $no_log);
									$xml 				= array();	//	kill all further arrays and stop processing
									$invalid_pr_feed 	= '1';
								}
							}
/*
echo "\r\n\r\n<br /> i total: '$i'<br />\r\n";
echo "\r\n\r\n<br>article Array:<br><pre>";print_r($article);echo "</pre>\r\n";
echo "\r\n\r\n<br /> file_keys:<br /> '$keys'<br />\r\n";
echo "\r\n\r\n<br /> file_values:<br /> '$file_values'<br />\r\n";
echo "\r\n\r\n<br /> product_file:<br > '$product_file'<br />\r\n";
*/
							if ($invalid_pr_feed != 1) {

								$title = substr($url, strrpos($url, "/")+1);			// extract name from URL as title for this feed
								$xml_pro_feed	= '1';									// just a flag for further index procedure
								$file 			= str_replace(",", "", $keys);			// only the used keys for this product
								$file		   .= str_replace(",", "", $file_values);

								//  convert all quotes into standard quote
								$file = clean_quotes($file);
								// kill  special characters
								if ($del_secchars == 1){
									$file = del_secchars($file);
								}
								//  split words at hyphen, single quote, dot and comma into their basics
								if (($div_all || $div_hyphen)) {
									$file	= split_words($file);
								}

								$raw_file		= $file;
								$data['title']		= "$title";
								$data['fulltext']	= $product_file;					// used to extract the text in result listing
								if ($del_secchars == 1){
									$file = del_secchars($file);
								}
								$data['content']	= $file;							// used to store as keywords in db

								printValidXML_pr_feed($count, $cl);
								$pageSize = number_format(strlen($product_file)/1024, 2, ".", "");
								printPageSizeReport($pageSize, $topic);
							}

						}
					}
				} else {
					printStandardReport('invalidCharSet',$command_line, $no_log);
				}
			}

		}
//echo "\r\n\r\n<br>data Array:<br><pre>";print_r($data);echo "</pre>\r\n";
        if ($url_status['state'] == 'ok') {

			//	not required for XML product feeds
			if ($xml_pro_feed != 1){
				//  first attempt to define a charset
				$chrSet = '';
				if ($use_prefcharset == '1') {      //  use preferred charset as defined in Admin settings
					$chrSet = $home_charset;

				} else {
					if($server_char && $url_status['charset']) {
						$chrSet = $url_status['charset'];    //  use charset as supplied by the remote server
					} else {                        //  try to extract the charset of this file
						if (preg_match("'encoding=[\'\"](.*?)[\'\"]'si", substr($file, 0, 3000), $regs)) {
							$chrSet = trim(strtoupper($regs[1]));      //      get encoding of current XML or XHTML file     and use it further on
						}

						if (!$chrSet) {
							if (preg_match("'charset=(.*?)[ \/\;\'\"]'si", substr($file, 0, 3000), $regs)) {
								$chrSet = trim(strtoupper($regs[1]));      //      get charset of current HTML file     and use it further on
							}
						}

						 if (!$chrSet) {
							if (preg_match("'charset=[\'\"](.*?)[\'\"]'si", substr($file, 0, 3000), $regs)) {

								$chrSet = trim(strtoupper($regs[1]));      //      get charset of current HTML file     and use it further on
							}
						}

						//  in assistance for all lazy webmasters
						$chrSet = preg_replace("/win-/si", "windows-", $chrSet);
						if ($chrSet == "1251") {
							$chrSet = "windows-1251";
						}

						//  this will fix the charset also for HTML5
						if (strpos($chrSet, ">")) {
							$chrSet = substr($chrSet, 0, strpos($chrSet, ">"));
						}

						$chrSet = trim(strtoupper($chrSet));

						if (!$chrSet) {
							$chrSet = $home_charset;    //  no charset found, we need to use default charset like for DOCs, PDFs, etc
						}
					}
				}

				//  if required, uncompress ZIP archives and make content of each file => text
				if ($url_status['content'] == 'zip' && $index_zip == '1' && $file) {
					file_put_contents("".$tmp_dir."/archiv.temp",$file);
					$zip = zip_open("".$tmp_dir."/archiv.temp");
					if ($zip) {
						$url_status['content'] = "text";    //  preventiv, if not another status will be detected for individual archiv files
						$file   = '';                       //  starting with a blank file for all archive files
						$topic  = 'zip';

						if ($debug == '2') {
							printStandardReport('archivFiles', $command_line, $no_log);
						}

						while ($zip_entry = zip_read($zip)) {
							if (zip_entry_open($zip, $zip_entry, "r")) {
								$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));  //uncompress the content of recent archiv file
								$name = zip_entry_name($zip_entry);             //  get filename of recent archive file
								if ($debug == '2') {    //
									$report = "<strong>&nbsp;&nbsp;".$name."</strong>";
									printThis($report, $cl);
									$size = (int)(zip_entry_filesize($zip_entry)/1024);
									if ($size == 0) $size = '1';
									$report =  "&nbsp;&nbsp;&nbsp;-&nbsp;Unpacked size:&nbsp;".$size." kByte<br />";
									printThis($report, $cl);
								}
								$buf = get_arch_content($buf, $name, $url, $chrSet);     //  if necessary, convert PDF, extract feed etc. for the recent file
								zip_entry_close($zip_entry);                    //  done for this file in archiv
								$file .= "".$buf."<br /><br />";                //  add all uncompressed and converted files together
							}
						}
						zip_close($zip);
					}
					unlink("".$tmp_dir."/archiv.temp");
				}

				//  if required, uncompress RAR archives and make content of each file => text
				if ($url_status['content'] == 'rar' && $index_rar == '1') {
					file_put_contents("".$tmp_dir."/archiv.temp",$file);
					$rar = rar_open("".$tmp_dir."/archiv.temp");

					if ($rar) {
						$url_status['content'] = "text";    //  preventiv, all individual archiv files willl be converted to 'text'
						$file       = '';                   //  starting with a blank file for all archive files
						$topic      = 'rar';
						$entries    = rar_list($rar);

						if ($rar) {
							if ($debug == '2') {
								printStandardReport('archivFiles', $command_line, $no_log);
							}
							foreach ($entries as $entry) {
								$name =  $entry->getName();
								if ($debug == '2') {
									$report = "<strong>&nbsp;&nbsp;".$name."</strong>";
									printThis($report, $cl);
									$size = (int)($entry->getPackedSize()/1024);
									if ($size == 0) $size = '1';
									$report = "&nbsp;&nbsp;&nbsp;-&nbsp;Packed size:&nbsp;&nbsp;".$size." kByte";
									printThis($report, $cl);
									$size = (int)($entry->getUnpackedSize()/1024);
									if ($size == 0) $size = '1';
									$report =  "&nbsp;&nbsp;&nbsp;-&nbsp;Unpacked size:&nbsp;".$size." kByte<br />";
									printThis($report, $cl);
								}
								$entry->extract('', "./".$tmp_dir."/".$name."");        //  extract single file of archiv into temporary folder
								$buf = file_get_contents("./".$tmp_dir."/".$name."");   //  read content of this intermediate file
								unlink ("./".$tmp_dir."/".$name."");                    //  destroy this file

								if ($buf) {
									$buf = get_arch_content($buf, $name, $url, $chrSet); //  if necessary, convert PDF, extract feed etc. for the recent file
									$file .= "".$buf."<br /><br />";            //  add all uncompressed and converted files together
								}
							}
						}
						rar_close($rar);
					}
					unlink("".$tmp_dir."/archiv.temp");
				}

				$file0 = $file; //  remember the original (e.g. for doc2txt converter)
				//  remove useless part of the content
				$file = preg_replace("@<style[^>]*>.*?<\/style>@si", " ", $file);
				$file = str_replace ("encoding: ''", " ", $file);        //  yes, I've seen such nonsense !

				$valid_utf8 = '1';

				//  kill eventually duplicate coding info in dynamic links
				if (stristr(substr($file, '0', '4000'), "encoding") && strstr(substr($file, '0', '4000'), "charset")) {
					$file = substr($file, strrpos($file, "<!DOCTYPE")); //  subsstring starting at last found <!DOCTYPE
				}

				//  we need to do it again for eventually new charset in archive
				$chrSet = '';
				if ($use_prefcharset == '1') {      //  use preferred charset as defined in Admin settings
					$chrSet = $home_charset;

				} else {
					if($server_char && $url_status['charset']) {
						$chrSet = $url_status['charset'];    //  use charset as supplied by the remote server

					} else {                        //  try to extract the charset of this file
						if (preg_match("'encoding=[\'\"](.*?)[\'\"]'si", substr($file, 0, 3000), $regs)) {
							$chrSet = trim(strtoupper($regs[1]));      //      get encoding of current XML or XHTML file     and use it furtheron
						}

						if (!$chrSet) {
							if (preg_match("'charset=(.*?)[ \/\;\'\"]'si", substr($file, 0, 3000), $regs)) {
								$chrSet = trim(strtoupper($regs[1]));      //      get charset of current HTML file     and use it furtheron
							}
						}

						 if (!$chrSet) {
							if (preg_match("'charset=[\'\"](.*?)[\'\"]'si", substr($file, 0, 3000), $regs)) {

								$chrSet = trim(strtoupper($regs[1]));      //      get charset of current HTML file     and use it furtheron
							}
						}

						//  in assistance for all lazy webmasters
						$chrSet = preg_replace("/win-/si", "windows-", $chrSet);
						if ($chrSet == "1251") {
							$chrSet = "windows-1251";
						}

						//  this will fix the charset also for HTML5
						if (strpos($chrSet, ">")) {
							$chrSet = substr($chrSet, 0, strpos($chrSet, ">"));
						}

						if ($chrSet == '') {
							$chrSet = $home_charset;    //  no charset found, we need to use default charset like for DOCs, PDFs, etc
						}
					}
				}

				if (strpos($chrSet, " ")) {     // in the wild we have already seen a lot of variants
					$chrSet = substr($chrSet, 0, strpos($chrSet, " "));
				}

				//  some webmaster still use 'UNICODE' as name
				if (stristr($chrSet, "UNICODE")) {
					$chrSet = "UTF-8";
				}
				//  obsolete since 1990, but some (Italian) server still send it as charset . . . .
				if (stristr($chrSet, "8858")) {
					$chrSet = str_replace("8858", "8859", $chrSet);
				}
				//  required coaching for some webmasters
				if (stristr($chrSet, "cp-")) {
					$chrSet = str_ireplace("CP-", "CP", $chrSet);
				}

				//  also seen, and needs to be coached by deleting all nonsense
				if (stristr($chrSet, "meta")) {
					printStandardReport('noCharset', $command_line, $no_log);
					$chrSet = '';
				}

				$contents['charset'] = $chrSet;
//echo "\r\n\r\n<br /> chrSet0: '$chrSet'<br />\r\n";
				if ($index_framesets == '1' && $too_old != '1') {
					if (preg_match("@<frameset[^>]*>(.*?)<\/frameset>@si",$file, $regs)) {
						printStandardReport('newFrameset', $command_line, $no_log);
						//  separate the <frameset> ....</frameset> part of this file
						$frame	 = $regs[1];
						$rep	 = get_frames($frame, $url, $can_leave_domain);
						$replace = $rep[0];
						$replace = "<body>".$replace."</body>";  //  create the body tags for $file
						$chrSet	 = $rep[1];
//echo "\r\n\r\n<br /> chrSet1: '$chrSet'<br />\r\n";
						If (strlen($chrSet) > 2) {
							$contents['charset'] = $chrSet;		// rebuild charset, which might be different for a frame
						}
						//  include all replacements instead of the frameset tag into the actual file. This will become the body
						$file = preg_replace("@<frameset.*?</frameset>@si", "$replace", $file);
					}
				}

				if ($index_iframes == '1' && $too_old != '1') {
					$links          = array ();
					$regs           = Array ();
					$replace        = '';
					$get_charset    = '';
					$real_url       = $url;
					if (preg_match_all("/(iframe[^>]*src[[:blank:]]*)=[[:blank:]]*[\'\"]?(([[a-z]{3,5}:\/\/(([.a-zA-Z0-9-])+(:[0-9]+)*))*([+:%\/?=&;\\\(\),._ a-zA-Z0-9-]*))(#[.a-zA-Z0-9-]*)?[\'\" ]?/i", $file, $regs, PREG_SET_ORDER)) {
						//printStandardReport('newIframe', $command_line, $no_log);
						//  find all frames of the iframe;
						$care_excl = '';   //  don't care file suffixed to be excluded
						$relocated = '';   //  URL is not relocated

						foreach ($regs as $val) {
							if (($a = url_purify($val[2], $url, $can_leave_domain, $care_exel, $relocated, $local_redir)) != '') {
								$links[] = ($a);    // collect  all iframe links
							}
						}

						if ($links) {
							printStandardReport('newIframe', $command_line, $no_log);
							foreach ($links as $url) {
								printNewLinks($url, $cl);

								if (preg_match("/.html|.htm|.xhtml|.xml|.php/i", $url)) {
									$frame = file_get_contents($url);      //      get content of this frame
									//  separate the body part of this frame
									preg_match("@<body[^>]*>(.*?)<\/body>@si",$frame, $regs);
									$body = $regs[1];
									if ($abslinks == '1') {
										$body = make_abslinks($body, $url);     //  if required, correct links relative to found iframe
									}
									$replace = "".$replace."<br />".$body."";
								} else {    //  might be an image
									$replace = "".$replace."<br /><img src=\"".$url."\">";
								}

							}
						}
						//  include all replacements instead of the iframe tag into the actual file
						$file = preg_replace("@<iframe.*?</iframe>@si", "$replace", $file);
						$contents['charset'] = $chrSet;     // rebuild charset
					}
					$url = $real_url;
				}

				$raw_file = $file;

				if (($url_status['content'] != 'xml') && $feed_pure =='1') {
					$file           = ' ';    //  no text and media content to be indexed. Pure feed indexation
				}
				//      in order to index RDF, RSD, RSS and ATOM feeds enter here
				if (($url_status['content'] == 'xml') && $index_rss =='1') {

					$file = str_ireplace("utf8", "UTF-8", $file);   //  yes, I've such trash on the Internet

					if (!preg_match("/<rss|atom|<feed|<rdf|<rsd/si", substr($file,0,400))) {
						printStandardReport('notRSS',$command_line, $no_log);   //  no valid feed detected
						$OKtoIndex = 0;
						$file_read_error = 1;
						$realnum -- ;
					} else {
						$html = '';

						$xml = XML_IsWellFormed($file);     //      check for well-formed XML
						if ($xml != '1') {
							if ($debug > 0 ) {
								printNotWellFormedXML($xml, $cl);
							}

							$OKtoIndex = 0;
							$file_read_error = 1;
							$realnum -- ;

						} else {

							$rss = new feedParser;
							// define options for feed parser
							$rss->limit     = $max_links;   //   save time by limiting the items/entries to be processed
							$rss->in_cp     = strtoupper($contents['charset']); //  charset of actual file
							$rss->out_cp    = 'UTF-8';      //  convert all into this charset
							$rss->cache_dir = '';           //  currently unused
							$rss->dc        = $dc;          //  treat Dublin Core tags in RDF feeds
							$rss->pro       = $preferred;   //  obey the PREFERRED directive in RSD feeds
							$rss->file      = '1';          //  use $file as feed (as a string, not URL)

							if ($cdata != 1) {
								$rss->CDATA = 'content';    //  get it all  (naughty)
							} else {
								$rss->CDATA = 'nochange';   //  well educated crawler
							}

							//  get feed as array
							if ($feed = $rss->get($url, $file)){
								//  if you want to see the feed during index procedure, uncomment the following row
								//  echo "<br>FEED array:<br><pre>";print_r($feed);echo "</pre>";
								$link           = '';
								$textinput_link = '';
								$image_url      = '';
								$image_link     = '';
								$docs           = '';
								$subjects       = '';
								$count          = '';
								$type           = $feed[type];
								$count          = $feed[sub_count];
								$cached         = $feed[cached];

								//  kill all no longer required values
								$feed[type]         = '';
								$feed[sub_count]    = '';
								$feed[encoding_in]  = '';
								$feed[encoding_out] = '';
								$feed[items_count]  = '';
								$feed[cached]       = '';

								if (!$count) {
									$count = '0';
								}

								if ($type == 'RSD') {
									//      prepare all RSD APIs
									for($i=0;$i<$count;$i++){
										$subjects .= ''.$feed['api'][$i]['name'].'<br />
												'.$feed['api'][$i]['apiLink'].'<br />
												'.$feed['api'][$i]['blogID'].'<br />
												'.$feed['api'][$i]['settings_docs'].'<br />
												'.$feed['api'][$i]['settings_notes'].'<br />';
									}
								}

								if ($type == 'Atom') {
									//      prepare all Atom entries
									for($i=0;$i<$count;$i++){
										$subjects .= ''.$feed['entries'][$i]['link'].'<br />
												'.$feed['entries'][$i]['title'].'<br />
												'.$feed['entries'][$i]['id'].'<br />
												'.$feed['entries'][$i]['published'].'<br />
												'.$feed['entries'][$i]['updated'].'<br />
												'.$feed['entries'][$i]['summary'].'<br />
												'.$feed['entries'][$i]['rights'].'<br />
												'.$feed['entries'][$i]['author_name'].' '.$feed['entries'][$i]['author_email'].' '.$feed['entries'][$i]['author_uri'].'<br />
												'.$feed['entries'][$i]['category_term'].' '.$feed['entries'][$i]['category_label'].' '.$feed['entries'][$i]['category_scheme'].'<br />
												'.$feed['entries'][$i]['contributor_name'].' '.$feed['entries'][$i]['contributor_email'].' '.$feed['entries'][$i]['contributor_uri'].'<br />
											';
									}

								}
								if ($type == 'RDF' | $type =='RSS v.0.91/0.92' | $type == 'RSS v.2.0'){    //  For RDF and RSS feeds enter here
									//  prepare channel image
									$image_url = $feed[image_url];
									if($image_url){
										$width = $feed[image_width];
										if (!$width || $width > '144') {
											$width = '88';  //set to default value
										}
										$height = $feed[image_height];
										if (!$height || $height > '400') {
											$height = '31';  //set to default value
										}

										$feed[image_url] = "<img id=\"rss_007\" src=\"".$image_url."\" alt=\"".$feed[image_title]."\" width=\"".$width."\" height=\"".$height."\">";
									}
									$image_link = $feed[image_link];
									if($image_link){
										$feed[image_link] = "<a href=\"".$image_link."\">".$image_link."</a>";
									}

									//      prepare all RDF or RSS items
									for($i=0;$i<$count;$i++){
										$subjects .= ''.$feed['items'][$i]['link'].'<br />
												'.$feed['items'][$i]['title'].'<br />
												'.$feed['items'][$i]['description'].'<br />
												'.$feed['items'][$i]['author'].'<br />
												'.$feed['items'][$i]['category'].'<br />
												'.$feed['items'][$i]['guid'].'<br />
												'.$feed['items'][$i]['comments'].'<br />
												'.$feed['items'][$i]['pubDate'].'<br />
												'.$feed['items'][$i]['source'].'<br />
												'.$feed['items'][$i]['enclosure'].'<br />
												'.$feed['items'][$i]['country'].'<br />
												'.$feed['items'][$i]['coverage'].'<br />
												'.$feed['items'][$i]['contributor'].'<br />
												'.$feed['items'][$i]['date'].'<br />
												'.$feed['items'][$i]['industry'].'<br />
												'.$feed['items'][$i]['language'].'<br />
												'.$feed['items'][$i]['publisher'].'<br />
												'.$feed['items'][$i]['state'].'<br />
												'.$feed['items'][$i]['subject'].'<br />
											';
									}
								}

								//  convert  the channel/feed part  into a string
								$feed_common = implode(" ", $feed);

								//  build something that could be indexed
								$html .= "<html>\r\n<head>\r\n<title>".$feed['title']."</title>\r\n<meta name=\"description\" content=\"".$feed['description']." \">\r\n</head>\r\n";
								$html .= "<body>\r\n".$feed_common."\r\n".$subjects."\r\n</body>\r\n</html>\r\n";
							}

							if (strlen($html) < "130") {    //  can't be a valid feed
								if ($type == "unknown") {
									printInvalidFeedType($type, $cl);
								} else {
									printStandardReport('invalidRSS',$command_line, $no_log);
								}
								$OKtoIndex = 0;
								$file_read_error = 1;
								$realnum -- ;
							} else {
								$contents['charset'] = 'UTF-8';     //      the feed reader converts all to utf-8
								$file = $html;                      //     use feed reader output

								if ($debug > 0 ) {
									printValidFeed($type, $count, $cl);
								}
							}
						}
					}
				}

				//  duplicate here, but frames, iframes, or RSS might have added nonsense content
				//  clean useless parts of the content
				$file = preg_replace("@<style[^>]*>.*?<\/style>@si", " ", $file);
				$file = str_replace ("encoding: ''", " ", $file);        //  yes, I've seen such nonsense !

				//  prepare CVS files
				if (($url_status['content'] == 'csv') && $index_csv =='1') {
					$file = str_replace(",", " ", $file);
					$file = str_replace(";", " ", $file);
				}
//echo "\r\n\r\n<br>url_status Array:<br><pre>";print_r($url_status);echo "</pre>\r\n";
				// for DOCs, PDFs, etc we need special text converter
				if ($url_status['content'] != 'text' && $url_status['content'] != 'xml' && $url_status['content'] != 'xhtml' && $url_status['content'] != 'csv') {

					$document = 1;
					$file = extract_text($file, $file0, $url_status['content'], $url, $chrSet);

					//  because the converter already transferred the documents to UTF-8, we need to adjust it here
					$contents['charset']    = 'UTF-8';
					$charSet                = 'UTF-8';

					if ($file == 'ERROR') {     //      if error, suppress further indexing
						$OKtoIndex = 0;
						$file_read_error = 1;
						$realnum -- ;
					}

					//  reduce Pashtu and Urdu to the main Farsi letters
					if (strtolower($charSet) == 'windows-1256' && $url_status['content'] == 'pdf') {
						$f_letter0= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€¦Ã‚Â½","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒÂ¯Ã‚Â¿Ã‚Â½");
						$f_letter1= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒÂ¯Ã‚Â¿Ã‚Â½","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒÂ¯Ã‚Â¿Ã‚Â½","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒÂ¢Ã¢â€šÂ¬Ã‹Å“","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢");
						$f_letter2= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â­ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â­ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â­Ãƒâ€¹Ã…â€œ","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â­ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢");
						$f_letter3= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¢","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€¹Ã…â€œ");
						$f_letter4= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€¦Ã‚Â¡","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒÂ¢Ã¢â€šÂ¬Ã‚Âº","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€¦Ã¢â‚¬Å“");
						$f_letter5= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒÂ¯Ã‚Â¿Ã‚Â½","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€¦Ã‚Â¾","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€¦Ã‚Â¸","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â ");
						$f_letter6= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â­Ãƒâ€šÃ‚Âº","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â­Ãƒâ€šÃ‚Â»","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â­Ãƒâ€šÃ‚Â¼","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â­Ãƒâ€šÃ‚Â½");
						$f_letter7= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¡","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¢","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â£","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¤");
						$f_letter8= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â®ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¹","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â®Ãƒâ€¦Ã‚Â ");
						$f_letter9= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¥","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¦","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â§","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¨");
						$f_letter10= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â©","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Âª");
						$f_letter11= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â«","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¬");
						$f_letter12= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â­","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â®");
						$f_letter13= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¯","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â°");
						$f_letter14= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â±","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â²","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â³","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â´");
						$f_letter15= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Âµ","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¶","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â·","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¸");
						$f_letter16= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¹","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Âº","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â»","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¼");
						$f_letter17= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â½","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¾","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¿","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬");
						$f_letter18= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¯Ã‚Â¿Ã‚Â½","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€ Ã¢â‚¬â„¢","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾");
						$f_letter19= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€¹Ã¢â‚¬Â ");
						$f_letter20= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â°","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€¦Ã‚Â ","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¹","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€¦Ã¢â‚¬â„¢");
						$f_letter21= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¯Ã‚Â¿Ã‚Â½","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€¦Ã‚Â½","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¯Ã‚Â¿Ã‚Â½","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¯Ã‚Â¿Ã‚Â½");
						$f_letter22= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‹Å“","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â");
						$f_letter23= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¢","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€¹Ã…â€œ");
						$f_letter24= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€¦Ã‚Â¡","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‚Âº","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€¦Ã¢â‚¬Å“","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â®Ãƒâ€¦Ã‚Â½","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â®ÃƒÂ¯Ã‚Â¿Ã‚Â½","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â®ÃƒÂ¯Ã‚Â¿Ã‚Â½","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â®ÃƒÂ¢Ã¢â€šÂ¬Ã‹Å“");
						$f_letter25= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â®ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â®ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â®ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â®ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¢");
						$f_letter26= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»ÃƒÂ¯Ã‚Â¿Ã‚Â½","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€¦Ã‚Â¾","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€¦Ã‚Â¸","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â ");
						$f_letter27= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â¡","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â¢","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â£","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â¤");
						$f_letter28 = array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â§","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â¨","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â¦","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â¥");
						$f_letter29= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â­","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â®");
						$f_letter30= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â©","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Âª","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â«","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â¬");
						$f_letter31= array("ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â¯","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â°","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â±","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â²","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â³","ÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â´");

						$file=str_replace($f_letter0,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â§",$file);
						$file=str_replace($f_letter1,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¨",$file);
						$file=str_replace($f_letter2,"ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€šÃ‚Â¾",$file);
						$file=str_replace($f_letter3,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Âª",$file);
						$file=str_replace($f_letter4,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â«",$file);
						$file=str_replace($f_letter5,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¬",$file);
						$file=str_replace($f_letter6,"ÃƒÆ’Ã…Â¡ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ",$file);
						$file=str_replace($f_letter7,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â­",$file);
						$file=str_replace($f_letter8,"ÃƒÆ’Ã…Â¡Ãƒâ€¹Ã…â€œ",$file);
						$file=str_replace($f_letter9,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â®",$file);
						$file=str_replace($f_letter10,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¯",$file);
						$file=str_replace($f_letter11,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â°",$file);
						$file=str_replace($f_letter12,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â±",$file);
						$file=str_replace($f_letter13,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â²",$file);
						$file=str_replace($f_letter14,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â³",$file);
						$file=str_replace($f_letter15,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â´",$file);
						$file=str_replace($f_letter16,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Âµ",$file);
						$file=str_replace($f_letter17,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¶",$file);
						$file=str_replace($f_letter18,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â·",$file);
						$file=str_replace($f_letter19,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¸",$file);
						$file=str_replace($f_letter20,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Â¹",$file);
						$file=str_replace($f_letter21,"ÃƒÆ’Ã‹Å“Ãƒâ€šÃ‚Âº",$file);
						$file=str_replace($f_letter22,"ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¯Ã‚Â¿Ã‚Â½",$file);
						$file=str_replace($f_letter23,"ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡",$file);
						$file=str_replace($f_letter24,"ÃƒÆ’Ã…Â¡Ãƒâ€šÃ‚Â©",$file);
						$file=str_replace($f_letter25,"ÃƒÆ’Ã…Â¡Ãƒâ€šÃ‚Â¯",$file);
						$file=str_replace($f_letter26,"ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¾",$file);
						$file=str_replace($f_letter27,"ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦",$file);
						$file=str_replace($f_letter28,"ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â ",$file);
						$file=str_replace($f_letter29,"ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¹Ã¢â‚¬Â ",$file);
						$file=str_replace($f_letter30,"ÃƒÆ’Ã¢â€žÂ¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡",$file);
						$file=str_replace($f_letter31,"ÃƒÆ’Ã¢â€žÂ¢Ãƒâ€¦Ã‚Â ",$file);
					}
				}

				if ($OKtoIndex == 1) {
					$pageSize = number_format(strlen($raw_file)/1024, 2, ".", "");
					printPageSizeReport($pageSize, $topic);
				}

				$charSet = strtoupper(trim($contents['charset']));              //      final charset for UTF-8 converter

				if (stristr($charSet, "encoding") || strlen($charSet) < '3') {  //  must be invalid encountered charset
					$charSet = 'UTF-8';
				}
//echo "\r\n\r\n<br /> final charSet: '$charSet'<br />\r\n";
				if ($charSet == "UTF-16") {
					$charSet = "UTF-8";     //  content will be converted in function clean_file()
				}

				$dic        = '';
				//  if Chinese or Korean text should be segmented enter here
				if ($cn_seg == '1' && $file && !$js_link && !stristr($charSet, "8859")) {

					if ($charSet == 'GB2312' || $charSet == 'GB18030' || $charSet == 'GBK') {
						$dic = "".$dict_dir."/cn_gb18030.dic";          //  simplified Chinese
					}
					if ($charSet == 'BIG5') {
						$dic = "".$dict_dir."/cn_big5.dic";             //  traditional Chinese
					}
					if ($charSet == 'ISO10646-1933') {
						$dic = "".$dict_dir."/kr_iso10646-1933.dic";    // Korean
					}
					if ($charSet == 'EUC-KR') {
						$dic = "".$dict_dir."/kr_euc-kr.dic";           //  Korean
					}
					if ($charSet == 'UTF-8') {
						$dic = "".$dict_dir."/cn_utf-8.dic";            //  Unicode
					}

					if ($dic) {      //  if dictionary is available for page charset, perform a segmentation
						$Segmentation = new Segmentation;
						$Segmentation->load($dic);
						$Segmentation->setLowercase(FALSE);
						$cn_result = $Segmentation->segmentString($file);

						if($cn_result  && $charSet != 'UTF-8'){
							$iconv_file = @iconv($charSet, "UTF-8//IGNORE", $cn_result);
							if(trim($iconv_file) == ""){            // iconv is not installed or input charSet not available. We need to use class ConvertCharset
								$NewEncoding = new ConvertCharset($charSet, "utf-8");
								$NewFileOutput = $NewEncoding->Convert($cn_result);
								$cn_result = $NewFileOutput;
							}else{
								$cn_result = $iconv_file;
							}
							unset ($iconv_file, $NewEncoding, $NewFileOutput);
						}

						$seg_data = clean_file($cn_result, $url, $url_status['content'], $charSet, $use_nofollow, $use_robot, $can_leave_domain, $edititle, $edidescription);

					} else {
						printNoDictionary($charSet, $cl);   //  no dictionary found for this charset
					}
				}

				//  if Japanese text should be segmented enter here. But not if a Chinese dictonary was already found
				if ($jp_seg == '1' && $file && !$js_link && !stristr($charSet, "ISO") && !$dic) {
					$dic = '';

					if ($charSet == 'UTF-8' || $charSet == 'EUC-JP') {
						$file = @iconv($charSet, "SHIFT_JIS//IGNORE", $file);
						$charSet = "SHIFT_JIS";
					}

					if ($charSet == 'SHIFT_JIS') {
						$dic = "".$dict_dir."/jp_shiftJIS.dic";
					}

					if ($dic) {      //  if dictionary is available for page charset, perform a segmentation
						$Segmentation = new Segmentation;
						$Segmentation->load($dic);
						$Segmentation->setLowercase(FALSE);
						$jp_result = $Segmentation->segmentString($file);
						//echo "\r\n\r\n<br /> jp_result: $jp_result<br />\r\n";

						if($jp_result  && $charSet != 'UTF-8'){
							$iconv_file = @iconv($charSet, "UTF-8//IGNORE" ,$jp_result);
							if(trim($iconv_file) == ""){            // iconv is not installed or input charSet not available. We need to use class ConvertCharset
								$NewEncoding = new ConvertCharset($charSet, "utf-8");
								$NewFileOutput = $NewEncoding->Convert($jp_result);
								$jp_result = $NewFileOutput;
							}else{
								$jp_result = $iconv_file;
							}
							unset ($iconv_file, $NewEncoding, $NewFileOutput);
						}
						$seg_data = clean_file($jp_result, $url, $url_status['content'], $charSet, $use_nofollow, $use_robot, $can_leave_domain, $edititle, $edidescription);
					} else {
						printNoDictionary($charSet, $cl);   //  no dictionary found for this charset
					}
				}

				//for the lazy webmasters
				if ($charSet == "UTF8") {
					$charSet = "UTF-8";
				}

				//  enter here only, if site / file is not yet UTF-8 coded or had already been converted to UTF-8
				if($charSet != "UTF-8" && $file){
					$file = convertToUTF8($file, $charSet, $char_Set, $converter_dir);
				}

				//  if activated in Admin backend, check for correct converting of $file into UTF-8
				if ($utf8_verify) {
                $valid_utf8 = (@iconv('UTF-8','UTF-8',$file) === $file);
				}
			}

            if ($valid_utf8 != "1") {
                $url_status['state'] = "<br />Invalid charset definition placed in meta tags of HTML header. Unable to convert the text into UTF-8<br />Indexing aborted for $url";
                if($server_char) {
                    $url_status['state'] = "<br />Invalid charset definition supplied via HTTP by the client server. Unable to convert the text into UTF-8<br />Indexing aborted for $url";
                }
                if($use_prefcharset) {
                    $url_status['state'] = "<br />Invalid charset definition placed Admin Settings.<br />Site was created with another charset<br />Indexing aborted for $url";
                }

                printUrlStatus($url_status['state'], $command_line, $no_log);
                $file = '';
                $deletable = 1;
            } else {

                if ($index_media == '1') {
                    $newmd5sum = md5($file);    //  get md5 including links and title of media files
                }

				//	not required for XML product feeds
				if ($xml_pro_feed != 1){
					$data = clean_file($file, $url, $url_status['content'], $charSet, $use_nofollow, $use_robot, $can_leave_domain, $edititle, $edidescription);
//echo "\r\n\r\n<br>data Array0:<br><pre>";print_r($data);echo "</pre>\r\n";

					//  detect language of body tag, but only if 'debug' mode is activated for admin backend
					if ($lang_detect == '1' && $debug == '2') {
						// Initialize CURL:
						$ch = curl_init('http://apilayer.net/api/detect?access_key='.$lang_Key.'&query='.urlencode($data['fulltext']).'');
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						// execute CURL
						$json = curl_exec($ch);
						curl_close($ch);
						// Decode JSON response and get array:
						$result = json_decode($json, true);
						//echo "\r\n\r\n<br>result Array:<br><pre>";print_r($result);echo "</pre>\r\n";

						if (!$result['success']) {  //  no success, parse error code
							$lang = "Unable to detect the language of this page. ".substr($result['error']['info'], 0, strpos($result['error']['info'], ".")+1);
						} else {
							if ($result['results']['1']) {  //  parse, if multiple languages are detected here
								$lang = $result['results'][0]['language_name'].", ".$result['results'][1]['language_name'];

								$i = 2;
								//  loop through max. 9 results.
								while ($i <= 10) {
									if ($result['results'][$i]) {
									$lang .= ", ".$result['results'][$i]['language_name'];
									} else {
										break;
									}
									$i++;
								}
							} else {    //  only one language detected here
								$lang = $result['results'][0]['language_name'];
							}
						}
						//  correct some Austrian/English language mismatches . . .
						$lang = str_replace("have", "did", $lang);
						$lang = str_replace("supplied", "supply", $lang);
						$lang = str_replace("specified a query", "specify any", $lang);

						printLanguage($lang, $cl);
					}

					//  index only links and their titles
					if($only_links) {
						$media_links = '0';
						$my_links = get_link_details($file, $url, $can_leave_domain, $data['base'], $media_links, $use_nofollow, $local_redir);
						$data['content'] = $my_links[0][0];   //  define new content
						$data['fulltext'] = $my_links[0][0];   //  define new content also for 'full text';
					}

					//  combine raw words plus segmented  words
					if ($cn_seg == 1 || $jp_seg == 1 && $dic && !$js_link) {
						if ($debug != '0') {
							$seg_add = $seg_data[count]-$data[count];  //      calculate segmentation result
							if ($seg_add > '0') {
								if ($charSet == 'EUC-KR' || $charSet == 'ISO10646-1933'){
									printSegKR($seg_add, $cl);
								}
								if ($charSet == 'SHIFT_JIS'){
									printSegJA($seg_add, $cl);
								} else {
									printSegCN($seg_add, $cl);
								}
							}
/*
echo "<br /><pre>Results of word segmentation:</pre>";
echo "<br />Unsegmented title :<br><pre>";print_r($data[title]);echo "</pre>";
echo "<br />Segmented title :<br><pre>";print_r($seg_data[title]);echo "</pre>";
echo "<br />Unsegmented full text:<br />$data[fulltext]<br />";
echo "<br />Segmented full text:<br />$seg_data[fulltext]";
*/
						}
						$data[content]      ="".$data[content]."".$seg_data[content]."";
						//$data[title]        ="".$data[title]."".$seg_data[title]."";
						$data[description]  ="".$data[description]."".$seg_data[description]."";
						$data[keywords]     ="".$data[keywords]."".$seg_data[keywords]."";
					}

				}
                //      check if canonical redirection was found in page head
                $cano_link = '0';
                if ($data['cano_link'] && $xml_pro_feed != 1) {
//echo "\r\n\r\n<br /> url: '$url'<br />\r\n";
                    $cano_link = $db_con->real_escape_string($data['cano_link']);
//echo "\r\n\r\n<br /> cano_link: '$cano_link'<br />\r\n";
                    if ($url != $cano_link) {   //  only new cano links are accepted
                        $OKtoIndex = 0;
                        $deletable = 1;
                        $realnum -- ;

                        if ($cano_link == "1") {
                            printNoCanonical($cano_link, $cl);                  //  if unable to extract redirection link
                        } else {
                            if ($data['refresh'] == '1') {
                                printRefreshed($cano_link, $data['wait'], $cl);  //  if refresh meta tag was found in HTML head
                            } else {
                                printCanonical($cano_link, $cl);                //  if canonical link was found in HTML head
                            }

                            //      do we already know this link in link-table
                            $sql_query = "SELECT url from ".$mysql_table_prefix."links where url like '$cano_link'";
                            $res = $db_con->query($sql_query);
                            if ($debug && $db_con->errno) {
                                $file       = __FILE__ ;
                                $function   = __FUNCTION__ ;
                                $err_row    = __LINE__-5;
                                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                            }
                            $rows = $res->num_rows;;

                            if ($rows == 0) {    // if not known in link-table, check if already known in temp-table
                                $sql_query = "SELECT link from ".$mysql_table_prefix."temp where link like '$cano_link'";
                                $res = $db_con->query($sql_query);
                                if ($debug && $db_con->errno) {
                                    $file       = __FILE__ ;
                                    $function   = __FUNCTION__ ;
                                    $err_row    = __LINE__-5;
                                    mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                                }
                                $rows = $res->num_rows; ;

                                if ($rows == 0) {    // not known in link-table, add new link
                                    if ($numoflinks <= $max_links) {

                                        $sql_query = "INSERT into ".$mysql_table_prefix."temp (link, level, id) values ('$cano_link', '$level', '$sessid')";
                                        $db_con->query ($sql_query);
                                    }
                                    if ($debug && $db_con->errno) {
                                        $file       = __FILE__ ;
                                        $function   = __FUNCTION__ ;
                                        $err_row    = __LINE__-5;
                                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                                    }
                                }
                            }
                        }
                    }
                    $cano_link = '0';   //  reset the cano flag
                } else {

                    if ($index_media == '0') {
                        //$newmd5sum = md5($data['content']); // get md5 from cleaned full text only
                        $newmd5sum = md5($raw_file);
                    }


                    if ($md5sum == $newmd5sum) {

                        printStandardReport('md5notChanged',$command_line, $no_log);
                        $OKtoIndex = 0;
                        $realnum -- ;
                    } else {
                        mysqltest();
                        //     check for duplicate page content
                        $sql_query = "SELECT * from ".$mysql_table_prefix."links where md5sum='$newmd5sum'";
                        $result = $db_con->query($sql_query);
                        if ($debug && $db_con->errno) {
                            $file       = __FILE__ ;
                            $function   = __FUNCTION__ ;
                            $err_row    = __LINE__-5;
                            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                        }

                        if ($num_rows = $result->num_rows) {  //  display warning message and urls with duplicate content
                            printStandardReport('duplicate',$command_line, $no_log);

                            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                                $dups[] = $row['link_id'];
                            }

                            for ($i=0; $i<$num_rows; $i++) {
                                $link_id = $dups[$i];
                                //$num = $i+1;
                                $sql_query = "SELECT * from ".$mysql_table_prefix."links where link_id like '$link_id'";
                                $res1 = $db_con->query($sql_query);
                                if ($debug && $db_con->errno) {
                                    $file       = __FILE__ ;
                                    $function   = __FUNCTION__ ;
                                    $err_row    = __LINE__-5;
                                    mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                                }
                                $row = $res1->fetch_array(MYSQLI_NUM);
                                $dup_url = urldecode($row[2]);
                                $dup_url = ($dup_url);
                                $dup_url = @iconv($charSet, "UTF-8//IGNORE", $dup_url);

                                if ($idna) {
                                    // Initialize the converter class
                                    $IDN = new IdnaConvert(array('idn_version' => 2008));

                                    if ($conv_puny && strstr($dup_url, "xn--") && $idna) {
                                        $dup_url = $IDN->decode($dup_url);
                                    }
                                }
                                if ($clear == 1) clean_resource($res, '03') ;

                                if (($url_status['content'] != 'xml') && $feed_pure != '1') {
                                    printDupReport($dup_url,$command_line);
                                }
                            }
                            if ($dup_content == '0') {    //  enter here, if pages with duplicate content should not be indexed/re-indexed
                                $OKtoIndex = 0;
                                $realnum -- ;
                            } else {
                                $OKtoIndex = 1;
                            }
                        }
                    }
                }


//echo "\r\n\r\n<br>data array1:<br><pre>";print_r($data);echo "</pre>\r\n";
                if (($md5sum != $newmd5sum || $reindex ==1) && $OKtoIndex == 1) {
                    $urlparts = parse_addr($url);
                    $newdomain = $urlparts['host'];
                    $type = 0;

                    if ($data['noindex'] == 1) {

                        //  remember this URL, so it might not become another time a new link
                        //  check without scheme and www.
                        $check_link = substr($check_link, stripos($url, "//")+2);
                        if (stristr($check_link, "www.")) {
                            $check_link = substr($check_link, stripos($check_link, "www")+4);
                        }
                        $sql_query = "SELECT url from ".$mysql_table_prefix."links where url like '%$check_link'";
                        $res = $db_con->query($sql_query);
                        if ($debug && $db_con->errno) {
                            $file       = __FILE__ ;
                            $function   = __FUNCTION__ ;
                            $err_row    = __LINE__-5;
                            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                        }
                        $known_link = $res->num_rows;

                        if ($known_link != '1') {
                            $sql_query = "INSERT into ".$mysql_table_prefix."links (site_id, url, indexdate, size, md5sum, level) values ('$site_id', '$url', curdate(), '$pageSize', '$newmd5sum', '$thislevel')";
                            $db_con->query ($sql_query);
                            if ($debug && $db_con->errno) {
                                $file       = __FILE__ ;
                                $function   = __FUNCTION__ ;
                                $err_row    = __LINE__-5;
                                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                            }
                        }

                        $OKtoIndex = 0;
                        $deletable = 1;
                        $realnum -- ;
                        printStandardReport('metaNoindex',$command_line, $no_log);
                    }

                    if (!$js_link) {   //  JavaScript will not deliver keywords, only links are parsed
                        $content    = explode(" ",addslashes($data['content']));
//echo "\r\n\r\n<br>content array0:<br><pre>";print_r($content);echo "</pre>\r\n";
                        $acc_words[] = array();
                        $type = '';
                        //  if Greek accents should be removed from Greek vowels
                        if ($noacc_el) {
                            foreach ($content as &$thisword) {
                                $no_acc = remove_acc_el($thisword, '');
                                if($no_acc != $thisword) {
                                    $acc_words[] = $no_acc;
                                }
                            }
                        }

                        //  if the other (Latin)  accents should be removed from their vowels
                        if ($vowels) {
                            foreach ($content as $thisword) {
                                if (!preg_match("/[0-9]/", $thisword)) {
                                    $no_acc = remove_accents($thisword, '');
                                    if($no_acc != $thisword) {
                                        $acc_words[] = $no_acc;
                                    }
                                }
                            }
                        }

                        //  now add the words without accents to the total text content
                        $content    = array_merge($content, $acc_words);
//echo "\r\n\r\n<br>content array1:<br><pre>";print_r($content);echo "</pre>\r\n";

                        //  if ligatures should be equalized
                        if ($liga) {
                            $liga_words = array();  //  will contain converted ligatures
                            $phon_words = array();  //  will contain converted phonetics
                            //  first: convert letters into latin ligatures
                            foreach($content as $thisword) {
                                if ($thisword) {
                                    $liga_words[]   = html_entity_decode($thisword, ENT_QUOTES, "UTF-8");
                                    $thisword1      = $thisword;

									foreach ($latin_ligatures as $key => $value){
                                        $thisword2 = preg_replace("/".$key."/s", $value, $thisword1); //  convert ligatures
                                        if ($thisword1 != $thisword2) {  //  break on first ligature
                                            $liga_words[]   = html_entity_decode($thisword2, ENT_QUOTES, "UTF-8");  //  collect new words with ligatures
                                            $thisword1      = $thisword2;    //  continue with the word, containing the ligatures
                                            //break;
                                        }
                                    }
                                }
                            }
                            // second: convert all letters into phonetic transcriptions
                            reset($liga_words);
                            foreach($liga_words as $thisword) {
                                $thisword1 = $thisword;
								foreach ($phon_trans as $key => $value){
                                    $thisword2 = preg_replace("/".$key."/s", $value, $thisword1); //  convert into phonetics
                                    if ($thisword1 != $thisword2) {  //  break on first ligature
                                        $phon_words[]   = html_entity_decode($thisword2, ENT_QUOTES, "UTF-8");  //  collect new words with phonetics
                                        $thisword1      = $thisword2;    //  continue with the word, containing the ligatures
                                        //break;
                                    }
                                }
                            }
                            $liga_words = array_merge($liga_words, $phon_words);    //  add all phoneticss to the liga array

                            //  now vice versa: convert latin ligatures and phonetic transcriptions into standard letters
                            reset($content);
                            $not_liga_words = array();

                            foreach($content as $thisword) {
                                if ($thisword) {
                                    //  first: convert Latin ligatures into standard letters
                                    $thisword1 = superentities($thisword, ENT_QUOTES, "UTF-8");

									foreach ($latin_ligatures as $key => $value){
                                        $thisword2 = preg_replace("/".$value."/s", $key, $thisword1); //  re-convert ligatures
                                        if ($thisword1 != $thisword2) {
                                            $not_liga_words[]   = html_entity_decode($thisword2, ENT_QUOTES, "UTF-8");  //  collect new words without ligatures
                                            $thisword1      = $thisword2;    //  continue with the word, containing the ligature
                                        }
                                    }
                                }
//echo "\r\n\r\n<br>not_liga_words Array:<br><pre>";print_r($not_liga_words);echo "</pre>\r\n";
                                // second: convert phonetic transcriptions into standard letters
                                reset($not_liga_words);
                                $not_phon_words = array();

                                foreach($not_liga_words as $thisword) {
                                    $thisword1 = superentities($thisword, ENT_QUOTES, "UTF-8");

									foreach ($phon_trans as $key => $value){
                                        $thisword2 = preg_replace("/".$value."/s", $key, $thisword1); //  re-convert sphonetic
                                        if ($thisword1 != $thisword2) {
                                            $not_phon_words[]   = html_entity_decode($thisword2, ENT_QUOTES, "UTF-8");  //  collect new words without phonetics
                                            $thisword1      = $thisword2;    //  continue with the word, containing the phonetic trans.
                                        }

                                    }
                                }
                            }
                            $not_words = array_merge($not_liga_words, $not_phon_words);    //  add all together
                            $content   = array_merge($liga_words, $not_words);    //  add all ligatures and re-converted letters to the content array
                        }
                        $wordarray  = unique_array($content);
                    }

//echo "\r\n\r\n<br>wordarray0:<br><pre>";print_r($wordarray);echo "</pre>\r\n";
                    if ($smp != 1 && $too_old != '1') {
                        if ($data['nofollow'] != 1 && $cano_link == '0') {
                            $media_links    = '0';
                            $links          = array();

                            if (!$document) {       //  don't try to find links in PDFs and other pure documents

                                if (($url_status['content'] != 'xml') && $feed_pure =='1') {
                                    $file = $raw_file;  //  get the raw file to search for links
                                }

                                $links      = get_links($file, $url, $can_leave_domain, $data['base'], $media_links, $use_nofollow, $local_redir, $url_reloc, $charSet);
                            }

                            if ($links[0]) {
                                $links      = distinct_array($links);
                                $all_links  = count($links);
                                if ($all_links > $max_links) $all_links = $max_links;
                                $links = array_slice($links,0,$max_links);

                                if ($realnum < $max_links) {
                                    $numoflinks = 0;
                                    //if there are any new links, add to the temp table, but only if there isn't such url already
                                    if ($links[0]) {

                                        reset ($links);
//echo "\r\n\r\n<br>links Array final:<br><pre>";print_r($links);echo "</pre>\r\n";
                                        $tmp_urls = get_temp_urls($sessid);         //  reload previous temp
//echo "\r\n\r\n<br>tmp_urls array:<br><pre>";print_r($tmp_urls);echo "</pre>\r\n";
                                        if ($debug == '2' ) {    //  if debug mode, show details
                                            printStandardReport('newLinks', $command_line, $no_log);
                                        }

										foreach ($links as $thislink){
                                            //  ignore error (message) links and self linking
                                            if (strstr($thislink, "//") && $thislink != $url){
                                                //  find new domains for _addurl table
                                                if ($auto_add && $can_leave_domain) {
                                                    $all_link = parse_all_url($thislink);
                                                    //  only the domain will be stored as new URL into addurl table
                                                    $dom_link = $all_link['host'];
                                                    //  reduce to domain name and tld
                                                    $new_link = str_replace("www.", "", $dom_link);

                                                    // use the complete URL
                                                    //$dom_link = $thislink;

                                                    //  use only the domain
                                                    $dom_link = $all_link['scheme']."://".$dom_link;

                                                    $banned = '';
                                                    mysqltest();
                                                    //     check whether URL is already known in sites table
                                                    $sql_query = "SELECT url from ".$mysql_table_prefix."sites where url like '%$new_link%'";
                                                    $res1 = $db_con->query($sql_query);

                                                    //     check whether URL is already known in addurl table
                                                    $sql_query = "SELECT url from ".$mysql_table_prefix."addurl where url like '%$new_link%'";
                                                    $res2 = $db_con->query($sql_query);

                                                    //     check whether URL is banned
                                                    $sql_query = "SELECT domain from ".$mysql_table_prefix."banned where domain like '%$new_link%'";
                                                    $res3 = $db_con->query($sql_query);

                                                    if ($res3->num_rows) {
                                                        $banned = "1";
                                                    }

                                                    if ($res1->num_rows == 0 && $res2->num_rows == 0 && $res3->num_rows == 0) {
                                                        //  add new domain into _addurl table
                                                        $sql_query ="INSERT into ".$mysql_table_prefix."addurl (url, description, account) values ('$dom_link', '$comment', '$admin_email')";
                                                        $db_con->query ($sql_query);

                                                    }
                                                }

                                                //      check whether thislink is already known as a link ( might happen by means of relocated URLs)
                                                $res4       = '';
                                                $res5       = '';
                                                $known_link = '';
                                                $known_temp = '';
                                                $check_link = $thislink;

                                                //  check without scheme and www.
                                                $check_link = substr($check_link, stripos($check_link, "//")+2);
                                                if (stristr($check_link, "www.")) {
                                                    $check_link = substr($check_link, stripos($check_link, "www")+4);
                                                }
//echo "\r\n\r\n<br /> check_link: '$check_link'<br />\r\n";
                                                $sql_query = "SELECT url from ".$mysql_table_prefix."links where url like '%$check_link'";
                                                $res4 = $db_con->query($sql_query);

                                                $known_link = $res4->num_rows;;
//echo "\r\n\r\n<br /> known_link: '$known_link'<br />\r\n";
                                                $sql_query = "SELECT link from ".$mysql_table_prefix."temp where link like '%$check_link'";
                                                $res5 = $db_con->query($sql_query);
                                                if ($debug > 0 && $db_con->errno) {
                                                    printf("MySQL failure: %s\n", $db_con->error);
                                                    echo "<br />Script aborted.";
                                                    exit;
                                                }
                                                $known_temp = $res5->num_rows;;
//echo "\r\n\r\n<br /> known_temp: '$known_temp'<br />\r\n";
                                                //      if this is a new link not yet known or banned, add this new link to the temp table
                                                if ($tmp_urls[$thislink] != 1 && !$res1 && !$known_link && !$known_temp && !$banned) {
                                                    $tmp_urls[$thislink] = 1;
                                                    $numoflinks++;

                                                    if ($debug == '2') {
                                                        $act_link = rawurldecode($thislink);   //  make it readable
                                                        $act_link = stripslashes($act_link);
                                                        printNewLinks($act_link, $cl);
                                                    }
                                                    mysqltest();

                                                    $sql_query = "INSERT into ".$mysql_table_prefix."temp (link, level, id) values ('$thislink', '$level', '$sessid')";
                                                    if ($numoflinks <= $max_links) $db_con->query ($sql_query);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            printStandardReport('noFollow',$command_line, $no_log);
                        }
                        unset ($file);
                    }

                    //  if we should index only the files as defined in docs list
                    if ($only_docs) {
                        $OKtoIndex = '';
                        foreach ($docs as $thisdoc){
                            if (strstr($urlparts['path'], $thisdoc)) {
                                $OKtoIndex = "1";
                            }
                        }
                        if (!$OKtoIndex) {
                            printStandardReport('noDoclist',$command_line, $no_log);
                        }
                    }

                    if ($OKtoIndex == 1 && $too_old != '1') {
                        if ($link_check == 0) {
                            $title = $data['title'];
                            $host = $data['host'];
                            $path = $data['path'];
                            $fulltxt = $data['fulltext'];
                            $desc = substr($data['description'], 0,1024);

                            //  extract domain
                            $url_parts  = parse_all_url($url);
                            $hostname   = $url_parts[host];

                            //  rebuild domain for localhost applications
                            if ($hostname == 'localhost') {
                                $host1 = str_replace($local,'',$url);
                            }

                            $pos = strpos($host1, "/");         //      on local server delete all behind the /
                            //      will work for localhost URLs like http://localhost/publizieren/japan1/index.htm
                            //       will fail for localhost URLs like http://localhost/publizieren/externe/japan2/index.htm
                            if ($pos) {
                                $host1 = substr($host1,0,$pos); //      build full adress again, now only local domain
                            }

                            if ($hostname == 'localhost') {
                                $domain_for_db = ("".$local."".$host1."/");   // complete URL
                                $domain_for_db = str_replace("http://", "", $domain_for_db);
                                //$domain_for_db = $host1;
                            }else {
                                //$domain_for_db = ("$url_parts[scheme]://".$hostname."/");  // complete URL
                                $domain_for_db = $hostname;
                            }

                            if (isset($domain_arr[$domain_for_db])) {
                                $dom_id = $domain_arr[$domain_for_db];
                            } else {
                                mysqltest();
                                $sql_query = "INSERT into ".$mysql_table_prefix."domains (domain) values ('$domain_for_db')";
                                $db_con->query($sql_query);
                                if ($debug && $db_con->errno) {
                                    $file       = __FILE__ ;
                                    $function   = __FUNCTION__ ;
                                    $err_row    = __LINE__-5;
                                    mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                                }
                                $dom_id = $db_con->insert_id;
                                $domain_arr[$domain_for_db] = $dom_id;
                            }

                            if (!$js_link) {   //  JavaScript will not deliver keywords, only links are parsed
                                reset($wordarray);
                                if ($case_sensitive == '0') {
                                    foreach ($wordarray as &$value) {
                                        $value[1] = lower_ent($value[1]);
                                        $value[1] = lower_case($value[1]);  //  convert keywords to lower case
                                    }
                                }
                                $wordarray = calc_weights ($wordarray, $title, $host, $path, $data['keywords'], $url_parts);
                            } else {
                                $wordarray = '';
                            }

                            //  if activated in Admin settings, ignore cookies tags
                            if($not_cookie == 1) {
                                reset($wordarray);
                                foreach ($wordarray as &$this_word) {
                                    if (stristr($this_word[1], "sphider") && strlen($this_word[1]) > 20) {
                                        $this_word[1] = '';
                                    }
                                    if (stristr($this_word[1], "cookie=") || stristr($this_word[1], "uid=")) {
                                        $this_word[1] = '';
                                    }

                                }
                                reset($wordarray);
                            }

                            //  if not activated in Admin settings, do not index URLs outside domain as words of the full text
                            if($domaincb != 1) {
                                reset($wordarray);
                                foreach ($wordarray as &$this_word) {
                                    if (stristr($this_word[1], "http") && !stristr($this_word[1], $hostname)) {
                                        $this_word[1] = '';
                                    }
                                }
                                reset($wordarray);
                            }

                            //if there are words to index, add the link to the database, get its id, and add the word + their relation
                            if (is_array($wordarray) && count($wordarray) >= $min_words_per_page) {

                                $OKtoSave = 1;
                                if ($use_white1 == '1') {       //  check if content of page matches ANY word in whitelist
                                    $found = '0';
                                    foreach ($whitelist as $key => $val1) {
										foreach ($wordarray as $thisword){
                                            $word = trim($thisword);
                                            if (strcasecmp($val1, $word) == 0) {
                                                $found = '1';
                                            }
                                        }
                                    }

                                    if ($found == '0') {
                                        printStandardReport('noWhitelist',$command_line, $no_log);
                                        $OKtoSave = 0;
                                        $realnum -- ;
                                    }
                                }

                                if ($use_white2 == '1') {       //  check if content of page matches ALL words in whitelist
                                    $all  = count($whitelist);
                                    $found = '0';
                                    $found_this = '0';
                                    foreach ($whitelist as $key => $val2) {
										foreach ($wordarray as $thisword){
                                            $word = trim($thisword);
                                            if (strcasecmp($val2, $word) == 0) {
                                                $found_this = '1';
                                            }
                                        }
                                        if ($found_this != '0'){
                                            $found++;
                                            $found_this = '0';
                                        }
                                    }

                                    if ($found != $all) {
                                        printStandardReport('noWhitelist',$command_line, $no_log);
                                        $OKtoSave = 0;
                                        $realnum -- ;
                                    }
                                }

                                if ($use_black == '1') {
                                    $found = '0';           //  check if content of page matches ANY string in blacklist
                                    foreach ($blacklist as $key => $val3) {
                                        $met = stripos($data[fulltext], $val3);
                                        if($met) $found = '1';
                                    }
                                    if ($found == '1') {
                                        printStandardReport('matchBlacklist',$command_line, $no_log);
                                        $OKtoSave = 0;
                                        $realnum -- ;
                                        $url_status['black'] = 1;
                                        return ($url_status);
                                    }
                                }

                                //  if activated in Admin backend, create a thumbnail of this URL
                                if ($OKtoSave && $hostname != 'localhost' && $webshot) {

                                    $shot   = '';   //  will contain the png webshot
                                    $img    = new webshots();
                                    $shot   = $img->url_to_image($url);

                                    if($debug && stristr($shot, "error: #")) {
                                        $shot_warn = "<br />Unable to create the webshot because of ".$shot;
                                        printWarning($shot_warn,$command_line, $no_log);
                                    } else {
                                        $shot = $db_con->real_escape_string($shot);
                                    }
                                } else {
                                    $shot = '0'; //  sheet anchor
                                }

                                //  prepare all data for secure mysqli connector
                                $url        = $db_con->real_escape_string(substr($url, 0, 1023));
                                $title      = $db_con->real_escape_string(substr($title, 0, 255));
                                $desc       = $db_con->real_escape_string(substr($desc, 0, 255));
                                //  for MySQL older than 5.5.3 we need to remove all emoji characters
                                $fulltxt    = remove_emoji($db_con->real_escape_string(substr($fulltxt, 0, 16000000)));
/*
echo "\r\n\r\n<br /> title: '$title'<br />\r\n";
echo "\r\n\r\n<br /> desc: '$desc'<br />\r\n";
echo "\r\n\r\n<br /> fulltxt: '$fulltxt'<br />\r\n";
*/
								//  if we are sure about the MySQL version, used to create the tables, we could use
/*
$vers = $db_con->server_info;
$vers = str_replace(".", "", substr($vers, 0, 5));
//  starting with version 5.5.3 MySQL server supports 4 byte strings
if (trim($vers) <= "553") {
$fulltxt    = remove_emoji($db_con->real_escape_string(substr($fulltxt, 0, 16000000)));
}
*/

                                if ($md5sum == '' || ($md5sum == '' && $url_status['relocate'])) {
                                    //  enter here for new page (unknown link) OR for new relocated URL (so it will become a new link)
                                    mysqltest();
                                    $sql_query = "INSERT into ".$mysql_table_prefix."links (site_id, url, title, description, fulltxt, indexdate, size, md5sum, level, webshot) values ('$site_id', '$url', '$title', '$desc', '$fulltxt', curdate(), '$pageSize', '$newmd5sum', '$thislevel', '$shot')";
                                    $db_con->query ($sql_query);
                                    if ($debug && $db_con->errno) {
                                        $file       = __FILE__ ;
                                        $function   = __FUNCTION__ ;
                                        $err_row    = __LINE__-5;
                                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                                    }

                                    $sql_query = "SELECT link_id from ".$mysql_table_prefix."links where url='$url'";
                                    $result = $db_con->query($sql_query);
                                    if ($debug && $db_con->errno) {
                                        $file       = __FILE__ ;
                                        $function   = __FUNCTION__ ;
                                        $err_row    = __LINE__-5;
                                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                                    }

                                    $row = $result->fetch_array(MYSQLI_NUM);
                                    $link_id = $row[0];

                                    if ($OKtoSave) {
                                        //  store link details, if not yet known (during reindex)
                                        if ($only_links) {
                                            //  extract domain of current page delivering the new links
                                            $url_parts  = parse_all_url($url);
                                            $hostname   = $url_parts[host];

                                            if ($hostname == 'localhost') {     //  rebuild domain for localhost applications
                                                $host1 = str_replace($local,'',$url);
                                            }

                                            $pos = strpos($host1, "/");         //      on local server delete all behind the /
                                            //      will work for localhost URLs like http://localhost/publizieren/japan1/index.htm
                                            //       will fail for localhost URLs like http://localhost/publizieren/externe/japan2/index.htm
                                            if ($pos) {
                                                $host1 = substr($host1,0,$pos); //      build full adress again, now only local domain
                                            }

                                            if ($hostname == 'localhost') {
                                                $domain_db = ("".$local."".$host1."/");   // complete URL
                                                $domain_db = str_replace("http://", "", $domain_db);
                                                //$domain_db = $host1;
                                            }else {
                                                //$domain_db = ("$url_parts[scheme]://".$hostname."/");  // complete URL
                                                $domain_db = $hostname;
                                            }

                                            //    now store all link details into db
                                            foreach ($my_links as $found_link) {
                                                //  but only if we have found a title
                                                if ($found_link[3]) {
                                                    mysqltest();
                                                    //     check whether URL is already known in sites table
                                                    $sql_query = "SELECT title from ".$mysql_table_prefix."link_details where link_id like '$link_id' and url like '%$found_link[2]%'";
                                                    $res1 = $db_con->query($sql_query);

                                                    if ($res1->num_rows == 0) {   //  must be new link
                                                        $sql_query = "INSERT into ".$mysql_table_prefix."link_details (link_id, url, title, indexdate, domain) values ('$link_id', '$found_link[2]', '$found_link[3]', now(), '$domain_db')";
                                                        $db_con->query ($sql_query);

                                                    }
                                                }
                                            }
                                        }
//echo "\r\n\r\n<br>wordarray:<br><pre>";print_r($wordarray);echo "</pre>\r\n";
                                        //  if keywords should be stored in db
                                        if(!$ignore_text) {
                                            if ($debug == '2') {    //  if debug mode, show details
                                                printStandardReport('newKeywords', $command_line, $no_log);
                                            }
                                        $count = save_keywords($wordarray, $link_id, $dom_id);
                                        }
                                    }

                                    mysqltest();
                                    if ($index_media == '1' && $OKtoSave) {     //   find media content only if there was no conflict with text (white and/or blacklist)
                                        include "$admin_dir/index_media.php";              //  try to find media files
                                    }
                                    mysqltest();

                                    if ($debug == '2') {
                                        printStandardReport('indexed1', $command_line, $no_log);
                                    } else {
                                        printStandardReport('indexed', $command_line, $no_log);
                                    }
                                } else if (($md5sum <> '') && ($md5sum <> $newmd5sum) && $OKtoSave) { //if page has changed, start updating
                                    //  if page content has changed, start updating
                                    mysqltest();
                                    $sql_query = "SELECT link_id from ".$mysql_table_prefix."links where url='$url'";
                                    $result = $db_con->query($sql_query);
                                    if ($debug && $db_con->errno) {
                                        $file       = __FILE__ ;
                                        $function   = __FUNCTION__ ;
                                        $err_row    = __LINE__-5;
                                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                                    }

                                    $link_id    = '';
                                    $test       = 'dummy';  // is not a numerical value
                                    $row        = $result->fetch_array(MYSQLI_NUM);
                                    if ($row[0] && ctype_digit($row[0])) {   //  get any valid link_id
                                        $link_id    = $row[0];
                                        for ($i=0;$i<=15; $i++) {
                                            $char = dechex($i);
                                            $sql_query = "DELETE from ".$mysql_table_prefix."link_keyword$char where link_id=$link_id";
                                            $db_con->query ($sql_query);
                                            if ($debug && $db_con->errno) {
                                                $file       = __FILE__ ;
                                                $function   = __FUNCTION__ ;
                                                $err_row    = __LINE__-5;
                                                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                                            }
                                        }

                                        //  if keywords should be stored in db
                                        if(!$ignore_text) {
                                            if ($debug == '2') {    //  if debug mode, show details
                                                printStandardReport('newKeywords', $command_line, $no_log);
                                            }
                                            $count =save_keywords($wordarray, $link_id, $dom_id);
                                        }

                                        $sql_query = "UPDATE ".$mysql_table_prefix."links set title='$title', description ='$desc', fulltxt = '$fulltxt', indexdate=now(), size = '$pageSize', md5sum='$newmd5sum', level='$thislevel', webshot='$shot' where link_id='$link_id'";
                                        mysqltest();
                                        $db_con->query($sql_query);
                                        if ($debug && $db_con->errno) {
                                            $file       = __FILE__ ;
                                            $function   = __FUNCTION__ ;
                                            $err_row    = __LINE__-5;
                                            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                                        }

                                        if ($index_media == '1') {
                                            include "$admin_dir/index_media.php";      //  try to find media files
                                        }
                                    }
                                    if ($debug == '2') {
                                        printStandardReport('re-indexed1', $command_line, $no_log);
                                    }
                                }
                            } else {

                                if ($js_link) {
                                    printStandardReport('js_content', $command_line, $no_log);
                                } else {
                                    if ($feed_pure !='1') {
                                        printStandardReport('minWords',$command_line, $no_log);
                                    }
                                }

                                $realnum -- ;

                                //  prepare all data for secure mysqli connector
                                $url        = $db_con->real_escape_string(substr($url, 0, 1023));
                                $title      = $db_con->real_escape_string(substr($title, 0, 255));
                                $desc       = $db_con->real_escape_string(substr($desc, 0, 255));
                                //  for MySQL older than 5.5.3 we need to remove all emoji characters
                                $fulltxt    = remove_emoji($db_con->real_escape_string(substr($fulltxt, 0, 16000000)));
//  if we are sure about the MySQL version, used to create the tables, we could use
/*
    $vers = $db_con->server_info;
    $vers = str_replace(".", "", substr($vers, 0, 5));
    //  starting with version 5.5.3 MySQL server supports 4 byte strings
    if (trim($vers) <= "553") {
        $fulltxt    = remove_emoji($db_con->real_escape_string(substr($fulltxt, 0, 16000000)));
    }
*/

                                //  already known link?
                                $sql_query = "SELECT link_id from ".$mysql_table_prefix."links where url='$url'";
                                $result = $db_con->query($sql_query);
                                if ($debug && $db_con->errno) {
                                    $file       = __FILE__ ;
                                    $function   = __FUNCTION__ ;
                                    $err_row    = __LINE__-5;
                                    mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                                }
                                $row = $result->fetch_array(MYSQLI_NUM);
                                $link_id = $row[0];
                                 // remember this link, even if not enough words in content.
                                if (!$link_id) {
                                    $sql_query = "INSERT into ".$mysql_table_prefix."links (site_id, url, title, description, fulltxt, indexdate, size, md5sum, level, webshot) values ('$site_id', '$url', '$title', '$desc', '$fulltxt', curdate(), '$pageSize', '$newmd5sum', '$thislevel', '$shot')";
                                    $db_con->query ($sql_query);
                                    if ($debug && $db_con->errno) {
                                        $file       = __FILE__ ;
                                        $function   = __FUNCTION__ ;
                                        $err_row    = __LINE__-5;
                                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                                    }

                                    //  is there any media content, even if not enough text?
                                    if ($index_media == '1') {
                                        include "$admin_dir/index_media.php";      //  try to find media files
                                    }
                                }
                            }
                        } else {
                            printStandardReport('link_okay', $command_line, $no_log);
                        }

                        unset ($file, $title, $fulltxt, $desc);
                        $wordarray  = array();
                        $data       = array();
                        $seg_data   = array();
                    }
                }
            }
        } else {
            $deletable = 1;
            //printUrlStatus($url_status['state'], $command_line, $no_log);
        }

        mysqltest();
        if ($url_status['relocate'] ){
            //  remove this relocated URL from temp table, because it is indexed now
            $sql_query = "DELETE from ".$mysql_table_prefix."temp where link = '$url' AND id = '$sessid'";
            $db_con->query ($sql_query);
            if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-5;
                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
            }
        }

		if ($reindex ==1 && $deletable == 1) {
			check_for_removal($url);
		} else if ($reindex == 1) {

		}
		if (!isset($all_links)) {
			$all_links = 0;
		}
		if (!isset($numoflinks)) {
			$numoflinks = 0;
		}

        if ($OKtoIndex == 1 && $url_status['state']  == 'ok') {
            //  if valid sitemap found, or canonical link, or something else, no LinkReport
            if ($smp != 1) {
                printLinksReport($numoflinks, $all_links, $command_line);
                if (($url_status['content'] != 'xml') && $feed_pure =='1') {
                    printStandardReport('end_sec',$command_line, $no_log);    //  </section> required here
                }
            }
            if (!$ignore_text && $count > "0") {
                printCountKeywords($count, $cl);
            } else {
                printStandardReport('end_sec',$command_line, $no_log);    //  </section> required here
            }
        }
        //  remove the URL, which has been indexed now from temp table.
        mysqltest();
        $sql_query = "DELETE from ".$mysql_table_prefix."temp where link = '$url' AND id = '$sessid'";
        $db_con->query ($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }
        //echo "  </section>\r\n";
        return $url_status;
	}

    function index_site($url, $reindex, $maxlevel, $soption, $url_inc, $url_not_inc, $can_leave, $use_robot, $use_nofollow, $cl, $all, $use_pref) {
        global $db_con, $mysql_table_prefix, $command_line, $mainurl,  $tmp_urls, $domain_arr, $all_keywords, $smp, $follow_sitemap;
        global $link_check, $smap_dir, $index_media, $clear, $create_sitemap, $tmp_dir, $domaincb;
        global $max_links, $realnum, $debug, $no_log, $dba_act, $add_auth, $interrupt, $index_media, $thumb_folder;

        if (!$can_leave) {
            $can_leave = $domaincb;
        }
        $can_leave_domain = $can_leave;

        $starttime  = getmicrotime();   //  start time to index this site
        $black      = '0';  //  will become counter for hits of blacklist
        $site_id    = '';
        $skip       = '';
        $smp        = '0';
        $omit       = array();
        $url        = $db_con->real_escape_string(stripslashes($url));
;
        if (strstr($interrupt, "-")) {  //  if indexer should not be interrupted periodically
            $interrupt = '999999';      //  never
        }
        $int_count = $interrupt;        //  $int_count will be decreased by each indexed link until $int_count = 1

		//	delete former sitemaps, created during last index procedure
        $i = '0';
        if (is_dir("$smap_dir/")) {
            if ($dh = opendir("$smap_dir/")) {
                while (($smpfile = readdir($dh)) !== false) {
                    if (preg_match("/urrent/i", $smpfile) && preg_match("/\.xml$/i", $smpfile)) {  //	  only *.xml are valid sitemap files
						@unlink("$smap_dir/$smpfile");	//	  delete this sitemap file
                        $i++ ;	  //	  count all files
                    }
                }
                closedir($dh);
            }
        } else {
            echo "<p class='warnadmin'><br />
                    Folder '$smap_dir' does not exist.<br />
                    No files deleted.</p>
                ";
        }

        printStandardReport('starting',$command_line, $no_log);

        if (!isset($all_keywords)) {
            mysqltest();
            $sql_query = "SELECT keyword_ID, keyword from ".$mysql_table_prefix."keywords";
            $result = $db_con->query($sql_query);
            if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-5;
                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
            }
            $num_rows = $result->num_rows;
            if ($num_rows != 0 ) {
                while($row = $result->fetch_array(MYSQLI_NUM)) {
                    $all_keywords[$row[1]] = $row[0];
                }
            }
            if ($clear == 1) clean_resource($result, '06') ;
        }

        $url        = convert_url($url);
        $compurl    = parse_addr($url);

        if ($compurl['path'] == '') {
            $url = $url . "/";
        }

        $t      = microtime();
        $a      =  getenv("REMOTE_ADDR");
        $sessid = md5 ($t.$a);

        if ($url != '/') {      //      ignore dummies
            $urlparts = parse_addr($url);

            $domain = $urlparts['host'];
            if (isset($urlparts['port'])) {
                $port = (int)$urlparts['port'];
            }else {
                $port = 80;
            }

            if (strpos($url, "?")) {
                $url_bas = substr($url, 0, strpos($url, "?"));
            } else {
                $url_bas = $url;
            }

            mysqltest();
            $sql_query = "SELECT * from ".$mysql_table_prefix."sites where url like '$url_bas%'";
            $result = $db_con->query($sql_query);
            if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-5;
                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
            }

            $row = $result->fetch_array(MYSQLI_NUM);

            $site_id        = $row[0];
            $edititle       = $row[2];
            $edidescription = $row[3];
            $authent        = $row[11];

            if ($add_auth && $authent) {        //  for sites with authentication we need to verify the value
                $url_status = url_status($url, $site_id, $sessid);
                $url_parts  = parse_all_url($url);

                if ($url_status['state'] == 'ok' && $url_status['content'] == 'text') {

                    if ($url_status['relocate'] ){          //  if relocated,  print message and redirect to new URL

                        printRedirected($url_status['relocate'], $url_status['path'], $cl);

                        if (strstr($url_status['path'], "//")) {                            //  if redirected to absolute URL, use this for further usage
                            $url = $url_status['path'];
                        } else {
                            $relo_url = str_replace($url_parts['query'], "", $url);         //  url without query
                            $relo_url = substr($url, 0, strrpos($relo_url, "/")+1);         //  url without file name
                            if (strpos($url_status['path'], "./") === 0) {                  //  if redirected relativ to same folder depth
                                $url_status['path'] = str_replace("./", "", $url_status['path']);
                                $url = "".$relo_url."".$url_status['path']."";
                            }
                            if (strpos($url_status['path'], "../") === 0) {                 //  if redirected relativ and one folder up
                                $url_status['path'] = str_replace("./", "", $url_status['path']);
                                $relo_url = substr($url, 0, strpos($url_parts['path']));    //  url without file name
                                $relo_url = substr($url, 0, strrpos($relo_url, "/")+1);     //  url without last folder
                                $url = "".$relo_url."".$url_status['path']."";
                            }
                        }
                    }

                    //  read file
                    $contents   = array();
                    $file       = '';
                    $file = file_get_contents($url);

                    if ($file === FALSE) {  //  we know another way to get the content
                        $get_charset    = '';
                        $contents = getFileContents($url, $get_charset);
                        $file = $contents['file'];
                    }

                    //  parse header only
                    preg_match("@<head[^>]*>(.*?)<\/head>@si",$file, $regs);
                    $headdata = $regs[1];
                    //  fetch the tag value
                    preg_match("/<meta +name *=[\"']?Sphider-plus[\"']? *content=[\"'](.*?)[\"']/i", $headdata, $res);
                    if (isset ($res)) {
                        if ($authent != $res[1]) {      //  invalid value in authentication tag
                            $skip = '1';
                            printHeader ($omit, $url, $command_line);
                            printStandardReport('Skipped_03', $command_line, $no_log);
                        }
                    } else {                            //  no authentication tag found in header
                        $skip = '1';
                        printHeader ($omit, $url, $command_line);
                        printStandardReport('Skipped_02', $command_line, $no_log);
                    }

                } else {
                    $skip = '1';
                    printHeader ($omit, $url, $command_line);
                    printStandardReport('statError', $command_line, $no_log);
                }
            }

            if (!$skip) {
                if ($site_id != "" && $reindex == 1) {
                    mysqltest();

                    $sql_query ="INSERT into ".$mysql_table_prefix."temp (link, level, id) values ('$url', 0, '$sessid')";
                    $db_con->query ($sql_query);
                    if ($debug && $db_con->errno) {
                        $file       = __FILE__ ;
                        $function   = __FUNCTION__ ;
                        $err_row    = __LINE__-5;
                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                    }
                    $sql_query = "SELECT url, level from ".$mysql_table_prefix."links where site_id = $site_id";
                    $result = $db_con->query($sql_query);
                    $num_rows   = $result->num_rows;
                    if ($num_rows != 0 ) {
                        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                            $site_link = $row['url'];
                            $link_level = $row['level'];
                            if ($site_link != $url) {
                                $sql_query = "INSERT into ".$mysql_table_prefix."temp (link, level, id) values ('$site_link', '$link_level', '$sessid')";
                                $db_con->query ($sql_query);
                            }
                        }
                    }

                    $sql_query = "UPDATE ".$mysql_table_prefix."sites set indexdate=now(), spider_depth ='$maxlevel', required = '$url_inc'," .
                        "disallowed = '$url_not_inc', can_leave_domain='$can_leave', use_prefcharset='$use_pref' where site_id='$site_id'";
                    mysqltest();
                    $db_con->query ($sql_query);
                    if ($debug && $db_con->errno) {
                        $file       = __FILE__ ;
                        $function   = __FUNCTION__ ;
                        $err_row    = __LINE__-5;
                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                    }
                } else if ($site_id == '') {
                    mysqltest();
                    $sql_query = "INSERT into ".$mysql_table_prefix."sites (url, indexdate, spider_depth, required, disallowed, can_leave_domain, use_prefcharset) " .
                        "values ('$url', now(), '$maxlevel', '$url_inc', '$url_not_inc', '$can_leave_domain', '$use_pref')";
                    $db_con->query ($sql_query);
                    if ($debug && $db_con->errno) {
                        $file       = __FILE__ ;
                        $function   = __FUNCTION__ ;
                        $err_row    = __LINE__-5;
                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                    }
                    $sql_query = "SELECT site_ID from ".$mysql_table_prefix."sites where url='$url'";
                    $result = $db_con->query($sql_query);
                    if ($debug && $db_con->errno) {
                        $file       = __FILE__ ;
                        $function   = __FUNCTION__ ;
                        $err_row    = __LINE__-5;
                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                    }
                    $row = $result->fetch_array(MYSQLI_NUM);
                    $site_id = $row[0];
                    if ($clear == 1) clean_resource($result, '09') ;
                } else {
                    mysqltest();
                    $sql_query ="UPDATE ".$mysql_table_prefix."sites set indexdate=now(), spider_depth ='$maxlevel', required = '$url_inc'," .
                        "disallowed = '$url_not_inc', can_leave_domain='$can_leave_domain', use_prefcharset='$use_pref' where site_id='$site_id'";
                    $db_con->query ($sql_query);
                    if ($debug && $db_con->errno) {
                        $file       = __FILE__ ;
                        $function   = __FUNCTION__ ;
                        $err_row    = __LINE__-5;
                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                    }
                }

                $pending = array();
                mysqltest();
                $sql_query ="SELECT site_id, temp_id, level, count, num from ".$mysql_table_prefix."pending where site_id='$site_id'";
                $result = $db_con->query($sql_query);
                if ($debug && $db_con->errno) {
                    $file       = __FILE__ ;
                    $function   = __FUNCTION__ ;
                    $err_row    = __LINE__-5;
                    mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                }
                $row        = $result->fetch_array(MYSQLI_NUM);
                $pending    = $row[0];
                $level      = '0';
                $count      = '0';
                if ($clear == 1) clean_resource($result, '10') ;

                $domain_arr = get_domains();
                if ($pending == '') {
                    mysqltest();
                    $sql_query = "INSERT into ".$mysql_table_prefix."temp (link, level, id) values ('$url', 0, '$sessid')";
                    $db_con->query ($sql_query);
                    if ($debug && $db_con->errno) {
                        $file       = __FILE__ ;
                        $function   = __FUNCTION__ ;
                        $err_row    = __LINE__-5;
                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                    }
                } else if ($pending != '') {
                    printStandardReport('continueSuspended',$command_line, $no_log);
                    mysqltest();
                    $pend_count = '0';
                    //$result = $db_con->query("SELECT temp_id, level, count from ".$mysql_table_prefix."pending where site_id='$site_id'");
                    $sql_query = "SELECT * from ".$mysql_table_prefix."pending where site_id='$site_id'";
                    $result = $db_con->query($sql_query);
                    if ($debug && $db_con->errno) {
                        $file       = __FILE__ ;
                        $function   = __FUNCTION__ ;
                        $err_row    = __LINE__-5;
                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                    }
                    $row = $result->fetch_array(MYSQLI_NUM);
                    if ($row) {
                        $sessid = $row[1];
                        $level = $row[2];
                        $pend_count = $row[3] + 1;
                        $num = $row[4];
                        $pending = 1;
                        $tmp_urls = get_temp_urls($sessid);
                        if ($clear == 1) clean_resource($result, '11') ;
                    }
                }

                if ($pending != 1) {
                    mysqltest();
                    $sql_query = "INSERT into ".$mysql_table_prefix."pending (site_id, temp_id, level, count) values ('$site_id', '$sessid', '0', '0')";
                    $db_con->query ($sql_query);
                    if ($debug && $db_con->errno) {
                        $file       = __FILE__ ;
                        $function   = __FUNCTION__ ;
                        $err_row    = __LINE__-5;
                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                    }
                }

                $time   = time();
                $robots = ("robots.txt"); // standardname of robots file
                if ($use_robot == '1') {
                    $omit = check_robot_txt($url, $robots);
                }

                printHeader ($omit, $url, $command_line);
				printStandardReport('forIP', $command_line, $no_log);

                if ($link_check == 1) printStandardReport('start_link_check', $command_line, $no_log);
                if ($link_check == 0 && $reindex == 1 ) printStandardReport('start_reindex', $command_line, $no_log);
                if ($link_check == 0 && $reindex == 0 ) printStandardReport('starting', $command_line, $no_log);

                $mainurl    = $url;
                $realnum    = $num;
                $num        = 0;

                while (($level <= $maxlevel && $soption == 'level') || ($soption == 'full')) {
                    if ($pending == 1) {
                        $count = $pend_count;
                        $pending = 0;
                    } else {
                        $count = 0;
                    }

                    $links = array();
                    mysqltest();
                    $sql_query = "SELECT distinct link from ".$mysql_table_prefix."temp where level=$level && id='$sessid' order by link";
                    $result = $db_con->query($sql_query);

                    $rows = $result->num_rows;

                    if ($rows == 0) {
                        break;
                    } else {
                        while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                            $links[] = $row['link'];
                        }
                    }

                    //  now loop through all available links(pages)
                    while ($count < count($links)) {
                        $num++;
                        $realnum++ ;
                        if ($realnum > $max_links ) {    //  if max. links per page reached
                            mysqltest();
                            $sql_query = "DELETE from ".$mysql_table_prefix."temp where id = '$sessid'";
                            $db_con->query ($sql_query);

                            $sql_query = "DELETE from ".$mysql_table_prefix."pending where site_id = '$site_id'";
                            $db_con->query ($sql_query);

                            printMaxLinks($max_links, $cl);

                            if ($create_sitemap == 1) {
                                create_sitemap($site_id, $url);
                            }

                            printStandardReport('completed',$command_line, $no_log);

                            return;
                        }

                        $thislink   = $db_con->real_escape_string(stripslashes($links[$count]));
                        $urlparts   = parse_addr($thislink);

                        $forbidden  = 0;

                        if (is_array($omit)) {   //      if valid robots.txt  was found
                            reset ($omit);
                            foreach ($omit as $omiturl) {
                                $omiturl = trim($omiturl);

                                $omiturl_parts = array();
                                $omiturl_parts = parse_addr($omiturl);
                                if (@$omiturl_parts['scheme'] == '') {
                                    $check_omit = $urlparts['host'] . $omiturl;
                                } else {
                                    $check_omit = $omiturl;
                                }

                                if (strpos($thislink, $check_omit)) {
                                    printRobotsReport($num, $thislink, $command_line);
                                    $realnum -- ;
                                    check_for_removal($thislink);
                                    $forbidden = 1;
                                    break;
                                }
                            }
                        }

                        if (!check_include($thislink, $url_inc, $url_not_inc )) {
                            $realnum -- ;
                            printUrlStringReport($num, $thislink, $command_line);
                            check_for_removal($thislink);
                            $forbidden = 1;
                        }

                        if ($forbidden == 0) {
                            printRetrieving($num, stripslashes(rawurldecode($thislink)), $command_line);
                            mysqltest();
                            $sql_query  = "SELECT md5sum, indexdate from ".$mysql_table_prefix."links where url='$thislink'";
                            $result     = $db_con->query($sql_query);
                            $rows       = $result->num_rows;

                            if ($rows == 0) {
                                $url_status = index_url($thislink, $level+1, $site_id, '',  $domain, '', $sessid, $can_leave_domain, $reindex, $use_nofollow, $cl, $use_robot, $use_pref, $url_inc, $url_not_inc, $num, $edititle, $edidescription);

                                //echo "  </section>\r\n";
                                //  check for touching the blacklist and its count against limit
                                if ($url_status['black'] == "1") {
                                    $black++;
                                    if ($black > 20){   //  limit until aborting the indexation of this site
                                        $url_status['aborted']  = "1";
                                        $url_status['state']    = "<br /><br />Indexation aborted for this site, as it met too often the blacklist.";
                                    }
                                } else {
                                    $black = 0;     //  reset counter, as should count only on continuous hits
                                }
//echo "\r\n\r\n<br>url_status Array0:<br><pre>";print_r($url_status);echo "</pre>\r\n";
                                //  check for emergency exit
                                if ($url_status['aborted'] == "1") {

                                    $sql_query = "SELECT * from ".$mysql_table_prefix."temp where id = '$sessid'";
                                    $result     = $db_con->query($sql_query);
                                    $rows       = $result->num_rows;

                                    if ($rows < 1) {
                                        //  delete all links from the temp table, which might be left for this site,  etc
                                        // and abort index procedure totally
                                        //  otherwise continue with next URL
                                        mysqltest();
                                        $sql_query = "DELETE from ".$mysql_table_prefix."temp where id = '$sessid'";
                                        $db_con->query ($sql_query);

                                        $sql_query = "DELETE from ".$mysql_table_prefix."pending where site_id = '$site_id'";
                                        $db_con->query ($sql_query);

                                        $sql_query = "UPDATE ".$mysql_table_prefix."sites set indexdate=now() where url = '$url'";
                                        $db_con->query ($sql_query);

                                        //  end all loops
                                        $forbidden  = '1';
                                        $omit       = '';
                                        $reindex    = '';
                                        $count      = '9999999999';
                                        $pending    = array();

                                        if (!stristr($url_status['state'], "NOHOST") && !stristr($url_status['state'], "black")) {  //  NOHOST warning will be printed separately
                                            printWarning($url_status['state'],$command_line, $no_log);
                                        }
                                        return;
                                    }
                                }

                                //  check for NOHOST
                                if (stristr($url_status['state'], "NOHOST")) {
                                    $sql_query = "SELECT * from ".$mysql_table_prefix."temp where id = '$sessid'";
                                    $result     = $db_con->query($sql_query);
                                    $rows       = $result->num_rows;

                                    if ($rows < 1) {
                                        //  delete all links from the temp table, which might be left for this site,  etc
                                        // and abort index procedure totally
                                        //  otherwise continue with next URL
                                        mysqltest();
                                        $sql_query = "DELETE from ".$mysql_table_prefix."temp where id = '$sessid'";
                                        $db_con->query ($sql_query);

                                        $sql_query = "DELETE from ".$mysql_table_prefix."pending where site_id = '$site_id'";
                                        $db_con->query ($sql_query);

                                        $sql_query = "UPDATE ".$mysql_table_prefix."sites set indexdate=now() where url = '$url'";
                                        $db_con->query ($sql_query);

                                        //  end all loops
                                        $forbidden  = '1';
                                        $omit       = '';
                                        $reindex    = '';
                                        $count      = '9999999999';
                                        $pending    = array();
                                        printWarning($url_status['state'],$command_line, $no_log);
                                        return;
                                    }
                                }

                                //  check for UFO file or invalid suffix (by redirected URL)
                                if (stristr($url_status['state'], "ufo")) {
                                    //printWarning($url_status['state'],$command_line, $no_log);
                                }

                                if (($url_status['state'] != "ok")) {
                                    printWarning($url_status['state'],$command_line, $no_log);
                                }

                                mysqltest();
                                $sql_query = "UPDATE ".$mysql_table_prefix."pending set level ='$level', count='$count', num='$realnum' where site_id='$site_id'";
                                $db_con->query($sql_query);

                            } else if ($rows <> 0 && $reindex == 1) {
                                $row = $result->fetch_array(MYSQLI_ASSOC);
                                $md5sum = $row['md5sum'];
                                $indexdate = $row['indexdate'];

                                if ($link_check == 1 && $reindex == 1) link_check($thislink, $level+1, $sessid, $can_leave_domain, $reindex, $site_id);
                                else {
                                    $url_status = index_url($thislink, $level+1, $site_id, $md5sum,  $domain, $indexdate, $sessid, $can_leave_domain, $reindex, $use_nofollow, $cl, $use_robot, $use_pref, $url_inc, $url_not_inc, $num, $edititle, $edidescription);
                                    echo "  </section>\r\n";

                                    //  check for emergency exit
                                    if ($url_status['aborted']) {
                                        $sql_query = "SELECT * from ".$mysql_table_prefix."temp where id = '$sessid'";
                                        $result     = $db_con->query($sql_query);
                                        $rows       = $result->num_rows;

                                        if ($rows < 1) {
                                            //  delete all links from the temp table, which might be left for this site,  etc
                                            // and abort index procedure totally
                                            //  otherwise continue with next URL
                                            mysqltest();
                                            $sql_query = "DELETE from ".$mysql_table_prefix."temp where id = '$sessid'";
                                            $db_con->query ($sql_query);

                                            $sql_query = "DELETE from ".$mysql_table_prefix."pending where site_id = '$site_id'";
                                            $db_con->query ($sql_query);

                                            $sql_query = "UPDATE ".$mysql_table_prefix."sites set indexdate=now() where url = '$url'";
                                            $db_con->query ($sql_query);

                                            //  end all loops
                                            $forbidden  = '1';
                                            $omit       = '';
                                            $reindex    = '';
                                            $count      = '9999999999';
                                            $pending    = array();

                                            printWarning($url_status['state'],$command_line, $no_log);
                                            //return;
                                        }

                                    }
                                }
                            }else {
                                printStandardReport('inDatabase',$command_line, $no_log);
                                $realnum -- ;
                                //$num--;
                            }
                            if ($rows <> 0) {
                                mysqltest();
                                $sql_query = "UPDATE ".$mysql_table_prefix."pending set level ='$level', count='$count', num='$realnum' where site_id='$site_id'";
                                $db_con->query($sql_query);
                            }
                            if ($clear == 1) clean_resource($result, '13') ;
                        }

                        //  check for interrupt counter
                        if ($int_count == '1') {   //  interrupt the index procedure until interactive resume
                            $sql_query = "UPDATE ".$mysql_table_prefix."pending set level ='$level', count='$count', num='$realnum' where site_id='$site_id'";
                            $db_con->query($sql_query);

                            printInterrupt($interrupt, $url, $cl) ;
                            die;
                        }
                        $count++;
                        $int_count--;
                    }
                    $level++;
                }
            }

            mysqltest();
            $sql_query = "DELETE from ".$mysql_table_prefix."temp where id = '$sessid'";
            $db_con->query ($sql_query);
            if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-5;
                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
            }

            $sql_query = "DELETE from ".$mysql_table_prefix."pending where site_id = '$site_id'";
            $db_con->query ($sql_query);
            if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-5;
                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
            }

            if ($create_sitemap == 1) {
                create_sitemap($site_id, $url);
            }

            printStandardReport('completed',$command_line, $no_log);
            $stats = get_Stats();
            printDatabase($stats, $cl);
        }

        if ($index_media) {
            //  delete all thumbnails in .../admin/thumbs/ folder
            clear_folder("$thumb_folder");
        }

    }

    function index_all() {
        global $db_con, $debug, $mysql_table_prefix, $reindex, $command_line, $omit;
        global $url, $cl, $clear, $real_log, $use_robot, $use_nofollow, $no_log;

        $all = '1'; //  here only as a dummy; needed to display the back to admin  button
        mysqltest();
        $sql_query = "SELECT url, spider_depth, required, disallowed, can_leave_domain, use_prefcharset from ".$mysql_table_prefix."sites";
        $result=$db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }
        $num_rows   = $result->num_rows;
        if ($num_rows != 0 ) {
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $url                = $row[0];
                $depth              = $row[1];
                $include            = $row[2];
                $not_include        = $row[3];
                $can_leave_domain   = $row[4];
                $use_prefcharset    = $row[5];

                if ($can_leave_domain=='') {
                    $can_leave_domain=0;
                }

                if ($depth == -1) {
                    $soption = 'full';
                } else {
                    $soption = 'level';
                }

                index_site($url, 1, $depth, $soption, $include, $not_include, $can_leave_domain, $use_robot, $use_nofollow, $cl, $all, $use_prefcharset);
            }
        }
        if ($clear == 1) clean_resource($result, '14') ;
        printStandardReport('ReindexFinish', $command_line, $no_log);
        create_footer();
    }

    function index_these() {
        global $db_con, $mysql_table_prefix, $reindex, $command_line, $omit, $tmp_dir;
        global $url, $cl, $clear, $real_log, $debug, $use_robot, $use_nofollow, $no_log;

        $site_ids   = array();
        $all        = '1';                              //  here only as a dummy; needed to display the back to admin  button
        $site_ids   = @file("$tmp_dir/act_sites.txt");  //   read the temp file that holds the actual site ids

        if (is_array($site_ids) && count($site_ids)) {
            mysqltest();
            foreach($site_ids as $this_id) {
                $sql_query = "SELECT url, spider_depth, required, disallowed, can_leave_domain, use_prefcharset from ".$mysql_table_prefix."sites where site_id='$this_id'";
                $result = $db_con->query($sql_query);
                if ($debug && $db_con->errno) {
                    $file       = __FILE__ ;
                    $function   = __FUNCTION__ ;
                    $err_row    = __LINE__-5;
                    mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                }

                $row = $result->fetch_array(MYSQLI_NUM);

                $url                = $row[0];
                $depth              = $row[1];
                $include            = $row[2];
                $not_include        = $row[3];
                $can_leave_domain   = $row[4];
                $use_prefcharset    = $row[5];

                if ($can_leave_domain=='') {
                    $can_leave_domain=0;
                }

                if ($depth == -1) {
                    $soption = 'full';
                } else {
                    $soption = 'level';
                }

                index_site($url, 1, $depth, $soption, $include, $not_include, $can_leave_domain, $use_robot, $use_nofollow, $cl, $all, $use_prefcharset);
            }
        } else {
            printStandardReport('NoSitesFound', $command_line, $no_log);    //  print warning message
        }

        if ($clear == 1) {
            clean_resource($result, '14') ;
            $site_ids   = array();
            $row        = array();
        }
        printStandardReport('ReindexFinish', $command_line, $no_log);
        create_footer();
    }

    function index_prior($pref_level) {
        global $db_con, $debug, $mysql_table_prefix, $reindex, $command_line, $omit;
        global $url, $cl, $clear, $real_log, $use_robot, $use_nofollow, $no_log;

        $all = '1'; //  here only as a dummy; needed to display the back to admin  button
//echo "\r\n\r\n<br /> pref_level: '$pref_level'<br />\r\n";
        mysqltest();
        $sql_query = "SELECT url, spider_depth, required, disallowed, can_leave_domain, use_prefcharset FROM ".$mysql_table_prefix."sites WHERE prior_level <= '$pref_level'";
        $result=$db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }
        $num_rows   = $result->num_rows;
        if ($num_rows != 0 ) {
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $url                = $row[0];
                $depth              = $row[1];
                $include            = $row[2];
                $not_include        = $row[3];
                $can_leave_domain   = $row[4];
                $use_prefcharset    = $row[5];

                if ($can_leave_domain=='') {
                    $can_leave_domain=0;
                }

                if ($depth == -1) {
                    $soption = 'full';
                } else {
                    $soption = 'level';
                }

                index_site($url, 1, $depth, $soption, $include, $not_include, $can_leave_domain, $use_robot, $use_nofollow, $cl, $all, $use_prefcharset);
            }
        }
        if ($clear == 1) clean_resource($result, '14') ;
        printStandardReport('ReindexFinish', $command_line, $no_log);
        create_footer();
    }

    function erase() {    //  only for command line option:  -erase
        global $db_con, $mysql_table_prefix, $reindex, $command_line, $omit;
        global $url, $cl, $clear, $real_log, $debug, $use_robot, $use_nofollow;
        global $no_log, $clear_cache, $textcache_dir, $mediacache_dir ;

        //  if Admin selected, clear text and media cache
        if ($clear_cache == '1') {
            if ($handle = opendir($textcache_dir)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        @unlink("".$textcache_dir."/".$file."");
                    }
                }
            }

            if ($handle = opendir($mediacache_dir)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != "." && $file != "..") {
                        @unlink("".$mediacache_dir."/".$file."");
                    }
                }
            }

        }

        //  clear all data in database
        $erase =array ("domains","keywords","links","link_keyword0","link_keyword1","link_keyword2","link_keyword3","link_keyword4","link_keyword5","link_keyword6","link_keyword7","link_keyword8","link_keyword9","link_keyworda","link_keywordb","link_keywordc","link_keywordd","link_keyworde","link_keywordf","media");
        foreach ($erase as $allthis){
            $sql_query = "TRUNCATE `".$mysql_table_prefix."$allthis`";
            $db_con->query ($sql_query);
        }
        if ($clear == 1) clean_resource($result, '14') ;
        printStandardReport('ErasedFinished', $command_line, $no_log);
        create_footer();
    }



    function index() {    //  only for command line option:  -eall
        global $db_con, $mysql_table_prefix, $command_line, $no_log;
        global $url, $clear, $debug, $use_robot, $use_nofollow;

        //  now re-index all
        mysqltest();
        $sql_query = "SELECT url, spider_depth, required, disallowed, can_leave_domain, use_prefcharset from ".$mysql_table_prefix."sites";
        $result=$db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }
        $num_rows   = $result->num_rows;
        if ($num_rows != 0 ) {
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $url                = $row[0];
                $depth              = $row[1];
                $include            = $row[2];
                $not_include        = $row[3];
                $can_leave_domain   = $row[4];
                $use_prefcharset    = $row[5];

                if ($can_leave_domain=='') {
                    $can_leave_domain=0;
                }
                if ($depth == -1) {
                    $soption = 'full';
                } else {
                    $soption = 'level';
                }

                index_site($url, 1, $depth, $soption, $include, $not_include, $can_leave_domain, $use_robot, $use_nofollow, $use_prefcharset );
            }
        }
        if ($clear == 1) clean_resource($result, '14') ;
        printStandardReport('ReindexFinish', $command_line, $no_log);
        create_footer();
    }

    function get_temp_urls($sessid) {
        global $db_con, $mysql_table_prefix, $debug, $clear;

        $sql_query = "SELECT link from ".$mysql_table_prefix."temp where id='$sessid' limit 0,100";
        $result = $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }
        $tmp_urls = Array();
        $num_rows = $result->num_rows;
        if ($num_rows != 0 ) {
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $tmp_urls[$row[0]] = 1;
            }
        }
        if ($clear == 1) clean_resource($result, '15') ;
        return $tmp_urls;

    }

    function get_domains() {
        global $db_con, $mysql_table_prefix, $debug, $clear;

        mysqltest();
        $sql_query = "SELECT domain_id, domain from ".$mysql_table_prefix."domains";
        $result = $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }
        $num_rows = $result->num_rows;
        $domains = Array();
        if ($num_rows != 0 ) {
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $domains[$row[1]] = $row[0];
            }
        }
        if ($clear == 1) clean_resource($result, '16') ;
        return $domains;

    }

    function get_arch_content($buf, $name, $url, $chrSet) {
        global $index_framesets, $command_line, $no_log, $can_leave_domain, $index_rss;

        $suffix = substr(strtolower($name), strrpos($name, ".")+1);
        //  if special converter is required
        if ($suffix == 'pdf')   $buf = extract_text($buf, $file0, 'pdf', 0, $charSet);
        if ($suffix == 'doc')   $buf = extract_text($buf, $file0, 'doc', 0, $chrSet);
        if ($suffix == 'rtf')   $buf = extract_text($buf, $file0, 'rtf', 0, $chrSet);
        if ($suffix == 'xls')   $buf = extract_text($buf, $file0, 'xls', 0, $chrSet);
        if ($suffix == 'ptt')   $buf = extract_text($buf, $file0, 'ptt', 0, $chrSet);
        if ($suffix == 'docx')  $buf = extract_text($buf, $file0, 'docx', 0, $chrSet);
        if ($suffix == 'xlsx')  $buf = extract_text($buf, $file0, 'xlsx', 0, $chrSet);

        //  for extracting framesets of this file enter here. Iframes will be extracted later on for the complete $file
        if ($index_framesets == '1') {
            if (preg_match("@<frameset[^>]*>(.*?)<\/frameset>@si",$buf, $regs)) {
                printStandardReport('newFrameset', $command_line, $no_log);
                //  separate the <frameset> ....</frameset> part of this file
                $frame = $regs[1];
                $rep	 = get_frames($frame, $url, $can_leave_domain);
				$replace = $rep[0];
                $replace ="<body>".$replace."</body>";  //  create the body tags for $buf
                //  include all replacements instead of the frameset tag into the actual file. This will become the body
                $buf = preg_replace("@<frameset.*?</frameset>@si", "$replace", $buf);
            }
        }

        // for extracting archived feeds enter here
        if ((preg_match("/<rss|atom|<feed|<rdf|<rsd/si", substr($buf,0,400))) && $index_rss =='1')  {
            $buf = get_arch_feeds($buf, $url);
        }

        return $buf;
    }

    function get_arch_feeds($buf, $url) {
        global $command_line, $no_log, $debug, $cl, $max_links, $dc, $preferred, $cdata;

        $html = '';
        $xml = XML_IsWellFormed($buf);     //      check for well-formed XML
        if ($xml != '1') {
            if ($debug > 0 ) {
                printNotWellFormedXML($xml, $cl);
            }
        } else {
            $rss = new feedParser;
            // define options for feed parser
            $rss->limit     = $max_links;   //   save time by limiting the items/entries to be processed
            $rss->in_cp     = strtoupper($contents['charset']); //  charset of actual file
            $rss->out_cp    = 'UTF-8';      //  convert all into this charset
            $rss->cache_dir = '';           //  currently unused
            $rss->dc        = $dc;          //  treat Dublin Core tags in RDF feeds
            $rss->pro       = $preferred;   //  obey the PREFERRED directive in RSD feeds
            $rss->file      = '1';          //  use $buf as feed (as a string, not URL)

            if ($cdata != 1) {
                $rss->CDATA = 'content';    //  get it all  (naughty)
            } else {
                $rss->CDATA = 'nochange';   //  well educated crawler
            }
            //  get feed as array
            if ($feed = $rss->get($url, $buf)){
                //  if you want to see the feed, uncomment the following row
                //echo "<br>Feed array:<br><pre>";print_r($feed);echo "</pre>";
                $link           = '';
                $textinput_link = '';
                $image_url      = '';
                $image_link     = '';
                $docs           = '';
                $subjects       = '';
                $count          = '';
                $type           = $feed[type];
                $count          = $feed[sub_count];
                $cached         = $feed[cached];

                //  kill all no longer required values
                $feed[type]         = '';
                $feed[sub_count]    = '';
                $feed[encoding_in]  = '';
                $feed[encoding_out] = '';
                $feed[items_count]  = '';
                $feed[cached]       = '';

                if (!$count) {
                    $count = '0';
                }

                if ($type == 'RSD') {
                    //      prepare all RSD APIs
                    for($i=0;$i<$count;$i++){
                        $subjects .= ''.$feed['api'][$i]['name'].'<br />
                                '.$feed['api'][$i]['apiLink'].'<br />
                                '.$feed['api'][$i]['blogID'].'<br />
                                '.$feed['api'][$i]['settings_docs'].'<br />
                                '.$feed['api'][$i]['settings_notes'].'<br />';
                    }
                }



                if ($type == 'Atom') {
                    //      prepare all Atom entries
                    for($i=0;$i<$count;$i++){
                        $subjects .= ''.$feed['entries'][$i]['link'].'<br />
                                '.$feed['entries'][$i]['title'].'<br />
                                '.$feed['entries'][$i]['id'].'<br />
                                '.$feed['entries'][$i]['published'].'<br />
                                '.$feed['entries'][$i]['updated'].'<br />
                                '.$feed['entries'][$i]['summary'].'<br />
                                '.$feed['entries'][$i]['rights'].'<br />
                                '.$feed['entries'][$i]['author_name'].' '.$feed['entries'][$i]['author_email'].' '.$feed['entries'][$i]['author_uri'].'<br />
                                '.$feed['entries'][$i]['category_term'].' '.$feed['entries'][$i]['category_label'].' '.$feed['entries'][$i]['category_scheme'].'<br />
                                '.$feed['entries'][$i]['contributor_name'].' '.$feed['entries'][$i]['contributor_email'].' '.$feed['entries'][$i]['contributor_uri'].'<br />
                            ';
                    }

                }
                if ($type == 'RDF' | $type =='RSS v.0.91/0.92' | $type == 'RSS v.2.0'){    //  For RDF and RSS feeds enter here
                    //  prepare channel image
                    $image_url = $feed[image_url];
                    if($image_url){
                        $width = $feed[image_width];
                        if (!$width || $width > '144') {
                            $width = '88';  //set to default value
                        }
                        $height = $feed[image_height];
                        if (!$height || $height > '400') {
                            $height = '31';  //set to default value
                        }

                        $feed[image_url] = "<img id=\"rss_007\" src=\"".$image_url."\" alt=\"".$feed[image_title]."\" width=\"".$width."\" height=\"".$height."\">";
                    }
                    $image_link = $feed[image_link];
                    if($image_link){
                        $feed[image_link] = "<a href=\"".$image_link."\">".$image_link."</a>";
                    }

                    //      prepare all RDF or RSS items
                    for($i=0;$i<$count;$i++){
                        $subjects .= ''.$feed['items'][$i]['link'].'<br />
                                '.$feed['items'][$i]['title'].'<br />
                                '.$feed['items'][$i]['description'].'<br />
                                '.$feed['items'][$i]['author'].'<br />
                                '.$feed['items'][$i]['category'].'<br />
                                '.$feed['items'][$i]['guid'].'<br />
                                '.$feed['items'][$i]['comments'].'<br />
                                '.$feed['items'][$i]['pubDate'].'<br />
                                '.$feed['items'][$i]['source'].'<br />
                                '.$feed['items'][$i]['enclosure'].'<br />
                                '.$feed['items'][$i]['country'].'<br />
                                '.$feed['items'][$i]['coverage'].'<br />
                                '.$feed['items'][$i]['contributor'].'<br />
                                '.$feed['items'][$i]['date'].'<br />
                                '.$feed['items'][$i]['industry'].'<br />
                                '.$feed['items'][$i]['language'].'<br />
                                '.$feed['items'][$i]['publisher'].'<br />
                                '.$feed['items'][$i]['state'].'<br />
                                '.$feed['items'][$i]['subject'].'<br />
                            ';
                    }
                }

                //  convert  the channel/feed part  into a string
                $feed_common = implode(" ", $feed);

                //  build something that could be indexed
                $html .= "<html>\r\n<head>\r\n<title>".$feed['title']."</title>\r\n<meta name=\"description\" content=\"".$feed['description']." \">\r\n</head>\r\n";
                $html .= "<body>\r\n".$feed_common."\r\n".$subjects."\r\n</body>\r\n</html>\r\n";
            }

            if (strlen($html) < '100') {    //  can't be a valid feed
                printStandardReport('invalidRSS',$command_line, $no_log);
            } else {
                if ($debug > 0 ) {
                    printValidFeed($type, $count, $cl);
                }
            }
        }
        return $html;
    }

    function commandline_help() {
        print "Usage: php spider.php <options>\n\n";
        print "Options:\n";
        print " -all\t\t Re-index everything in the database\n";
        print " -eall\t\t Erase and afterwards Re-index everything in the database\n";
        print " -new\t\t Index only the new sites\n";
        print " -erase\t\t Erase database\n";
        print " -erased\t\t Index all meanwhile erased sites\n";
        print " -preferred <level>\t\t	Index with respect to preference level\n";
        print " -preall\t\t Set 'Last indexed' to 0000\n";
        print " -u <url>\t Set url to index\n";
        print " -f\t\t Set indexing depth to full (unlimited depth)\n";
        print " -d <num>\t Set indexing depth to <num>\n";
        print " -l\t\t Allow spider to leave the initial domain\n";
        print " -r\t\t Set spider to reindex a site\n";
        print " -m <string>\t Set the string(s) that an url must include (use \\n as a delimiter between multiple strings)\n";
        print " -n <string>\t Set the string(s) that an url must not include (use \\n as a delimiter between multiple strings)\n";
    }

    function link_check($url, $level, $sessid, $can_leave_domain, $reindex, $site_id) {
        global $db_con, $debug, $command_line, $mysql_table_prefix, $user_agent, $index_media, $no_log, $clear;

        $needsReindex = 1;
        $deletable = 0;
        $local_url = 0;

        $local_url = strpos($url, 'localhost');
        if ($local_url != '/') {
            $url_status = url_status($url, $site_id, $sessid);
            $thislevel = $level - 1;

            if (strstr($url_status['state'], "Relocation")) {
                $care_excl      = '1';   //  care file suffixed to be excluded
                $relocated      = '1';   //  URL is relocated
                $local_redir    = '';

                $url = $db_con->real_escape_string(preg_replace("/ /i", "", url_purify($url_status['path'], $url, $can_leave_domain, $care_excl, $relocated, $local_redir)));

                if (!$url) {
                    $url_status['aborted']  = 1;
                    $url_status['state']    = "Indexation aborted because of undefined redirection error.";
                    return $url_status;
                }

                 //  abort indexation, if the redirected URL is equal to calling URL
                if ($url == 'self') {
                    $url_status['aborted']  = 1;
                    $url_status['state']    = "Indexation aborted for this page, because the redirection was a link in it selves.<br />Blocked by Sphide-plus, because this could end in an infinite indexation loop.";
                    return $url_status;
                }

                //  abort indexation, if the redirected URL contains invalid file suffix
                if ($url == 'excl') {
                    $url_status['aborted']  = 1;
                    $url_status['state']    = "Indexation aborted because the redirected link does not meet the URL suffix conditions.";
                    return $url_status;
                }

                //  abort indexation, because purifing the redirected URL failed
                if (!strstr($url, "//")) {
                    $url_status['aborted']  = 1;
                    $url_status['state']    = "Indexation aborted because: $url";
                    return $url_status;
                }

                mysqltest();
                $sql_query = "SELECT link from ".$mysql_table_prefix."temp where link='$url' && id = '$sessid'";
                $result = $db_con->query($sql_query);
                if ($debug && $db_con->errno) {
                    $file       = __FILE__ ;
                    $function   = __FUNCTION__ ;
                    $err_row    = __LINE__-5;
                    mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                }
                $rows = $result->num_rows;
                if ($rows == 0) {
                    $sql_query = "INSERT into ".$mysql_table_prefix."temp (link, level, id) values ('$url', '$level', '$sessid')";
                    $db_con->query ($sql_query);
                    if ($debug && $db_con->errno) {
                        $file       = __FILE__ ;
                        $function   = __FUNCTION__ ;
                        $err_row    = __LINE__-5;
                        mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                    }
                }

                $url_status['state'] == "redirected";
                if ($clear == 1) clean_resource($result, '17') ;

            }

            ini_set("user_agent", $user_agent);
            if ($url_status['state'] == 'ok') {
                printStandardReport('link_okay', $command_line, $no_log);
            } else {
                $deletable = 1;
                printUrlStatus($url_status['state'], $command_line);
            }
        }

        if ($local_url == '7') {
            printStandardReport('link_local', $command_line, $no_log);
        }

        if ($reindex ==1 && $deletable == 1) {
            check_for_removal($url);
        } else if ($reindex == 1) {

        }
        if (!isset($all_links)) {
            $all_links = 0;
        }
        if (!isset($numoflinks)) {
            $numoflinks = 0;
        }
    }

    function get_Stats() {
        global $db_con, $mysql_table_prefix, $debug, $clear;

        $stats = array();
        $keywordQuery = "SELECT count(keyword_id) from ".$mysql_table_prefix."keywords";
        $linksQuery = "SELECT count(url) from ".$mysql_table_prefix."links";
        $siteQuery = "SELECT count(site_id) from ".$mysql_table_prefix."sites";
        $categoriesQuery = "SELECT count(category_id) from ".$mysql_table_prefix."categories";
        $mediaQuery = "SELECT count(media_id) from ".$mysql_table_prefix."media";
        mysqltest();

        $result = $db_con->query($keywordQuery);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }

        if ($row = $result->fetch_array(MYSQLI_NUM)) {
            $stats['keywords']=$row[0];
        }
        $result = $db_con->query($linksQuery);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }

        if ($row = $result->fetch_array(MYSQLI_NUM)) {
            $stats['links']=$row[0];
        }
        for ($i=0;$i<=15; $i++) {
            $char = dechex($i);
            mysqltest();
            $sql_query = "SELECT count(link_id) from ".$mysql_table_prefix."link_keyword$char";
            $result = $db_con->query($sql_query);

            if ($row = $result->fetch_array(MYSQLI_NUM)) {
                $stats['index']+=$row[0];
            }
        }


        mysqltest();
        $result = $db_con->query($siteQuery);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }

        if ($row = $result->fetch_array(MYSQLI_NUM)) {
            $stats['sites']=$row[0];
        }
        $result = $db_con->query($categoriesQuery);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }

        if ($row = $result->fetch_array(MYSQLI_NUM)) {
            $stats['categories']=$row[0];
        }
        $result = $db_con->query($mediaQuery);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }

        if ($row = $result->fetch_array(MYSQLI_NUM)) {
            $stats['media']=$row[0];
        }

        return $stats;
    }

    function index_new() {
        global $db_con, $mysql_table_prefix, $command_line, $debug, $use_robot, $use_nofollow, $no_log, $clear, $cl, $started;

        $reindex == 0;
        printStandardReport('NewStart',$command_line, $no_log);

        mysqltest();
        $sql_query = "SELECT url, indexdate, spider_depth, required, disallowed, can_leave_domain, use_prefcharset from ".$mysql_table_prefix."sites";
        $result = $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }
        $num_rows = $result->num_rows;
        if ($num_rows != 0 ) {
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $url = $db_con->real_escape_string($row[0]);
                //  get actual status of indexdate, eventually other threads meanwhile indexed this URL
                $sql_query = "SELECT indexdate from ".$mysql_table_prefix."sites where url='$url'";
                $res = $db_con->query($sql_query);
                $ind = $res->fetch_array(MYSQLI_NUM);

                if ($ind[0] == '') {
                    // immediately info for all other threads: now indexed by this thread
                    $sql_query = "UPDATE ".$mysql_table_prefix."sites set indexdate=now() where url='$url'";
                    mysqltest();
                    $db_con->query ($sql_query);

                    $depth = $row[2];
                    $include = $row[3];
                    $not_include = $row[4];
                    $can_leave_domain = $row[5];
                    $use_prefcharset = $row[6];

                    if ($can_leave_domain=='') {
                        $can_leave_domain=0;
                    }
                    if ($depth == -1) {
                        $soption = 'full';
                    } else {
                        $soption = 'level';
                    }

                    //  now index this new site
                    index_site($url, 1, $depth, $soption, $include, $not_include, $can_leave_domain, $use_robot, $use_nofollow, $use_prefcharset );
                }
            }
        }

        if ($clear == 1) clean_resource($result, '18');
        $ended = time();
        $consumed = $ended - $started;
        printConsumedReport('consumed', $cl, '0', $consumed);
        printStandardReport('NewFinish',$command_line, '0');
        create_footer();
    }

    function index_erased() {
        global $db_con, $mysql_table_prefix, $command_line, $debug, $use_robot, $use_nofollow, $no_log, $clear, $started, $cl;

        $started = time();
        $reindex == 0;
        printStandardReport('ErasedStart',$command_line, $no_log);

        mysqltest();
        $sql_query = "SELECT url, indexdate, spider_depth, required, disallowed, can_leave_domain, use_prefcharset from ".$mysql_table_prefix."sites";
        $result = $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }
        $num_rows = $result->num_rows;
        if ($num_rows != 0 ) {
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $url = $row[0];
                //  get actual status of indexdate, eventually other threads meanwhile indexed this URL
                $sql_query ="SELECT indexdate from ".$mysql_table_prefix."sites where url='$url'";
                $res = $db_con->query($sql_query);

                $ind = $res->fetch_array(MYSQLI_NUM);

                if (strstr($ind[0], '0000')) {
                    // immediately info for all other threads: now indexed by this thread
                    $sql_query = "UPDATE ".$mysql_table_prefix."sites set indexdate=now() where url='$url'";
                    mysqltest();
                    $db_con->query ($sql_query);
                    if ($debug > 0 && $db_con->errno) {
                        printf("MySQL failure: %s\n", $db_con->error);
                        echo "<br />Script aborted.";
                        exit;
                    }

                    $depth              = $row[2];
                    $include            = $row[3];
                    $not_include        = $row[4];
                    $can_leave_domain   = $row[5];
                    $use_prefcharset    = $row[6];

                    if ($can_leave_domain=='') {
                        $can_leave_domain=0;
                    }
                    if ($depth == -1) {
                        $soption = 'full';
                    } else {
                        $soption = 'level';
                    }

                    //  now index this erased site
                    index_site($url, 1, $depth, $soption, $include, $not_include, $can_leave_domain, $use_robot, $use_nofollow, $cl, 1, $use_prefcharset);
                }
            }
        }

        if ($clear == 1) clean_resource($result, '19');
        $ended = time();
        $consumed = $ended - $started;
        printConsumedReport('consumed', $cl, '0', $consumed);
        //printStandardReport('ErasedFinish',$command_line, '0');
        printStandardReport('ReindexFinish',$command_line, '0');
        create_footer();
    }

    function index_suspended() {
        global $db_con, $mysql_table_prefix, $command_line, $debug, $use_robot, $use_nofollow, $no_log, $clear, $started, $cl;

        $started = time();
        $reindex = 0;
        printStandardReport('SuspendedStart',$command_line, $no_log);

        //  get ID and URL of all sites
        $sql_query = "SELECT site_id, url from ".$mysql_table_prefix."sites ORDER by url";
        $result1 = $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }
        $num_rows = $result1->num_rows;
        if ($num_rows != 0 ) {
            while ($row1 = $result1->fetch_array(MYSQLI_NUM)) {
                $url = $row1[1];
                $site_id = $row1[0];

                //  check whether this site is pending
                $sql_query = "SELECT site_id from ".$mysql_table_prefix."pending where site_id =$site_id";
                $result2 = $db_con->query($sql_query);

                $row2 = $result2->fetch_array(MYSQLI_ASSOC);

                //  if pending, continue indexing this URL
                if ($row2['site_id'] == $site_id) {
                    //  fetch all important data of this site
                    $sql_query = "SELECT url, spider_depth, required, disallowed, can_leave_domain, use_prefcharset from ".$mysql_table_prefix."sites where url='$url'";
                    $result=$db_con->query($sql_query);

                    if($row = $result->fetch_array(MYSQLI_NUM)) {
                        $maxlevel           = $row[1];
                        $in                 = $row[2];
                        $out                = $row[3];
                        $domaincb           = $row[4];
                        $use_prefcharset    = $row[5];

                        if ($domaincb=='') {
                            $domaincb=0;
                        }
                        if ($maxlevel == -1) {
                            $soption = 'full';
                        } else {
                            $soption = 'level';
                        }
                    }

                    if ($clear == 1) clean_resource($result, '21') ;

                    if (!isset($in)) {
                        $in = "";
                    }

                    if (!isset($out)) {
                        $out = "";
                    }
                    //  now indnex the rest of this site
                    index_site($url, $reindex, $maxlevel, $soption, $in, $out, $domaincb, $use_robot, $use_nofollow, $cl, $all, $use_prefcharset);
                }
            }
        }

        if ($clear == 1) clean_resource($result, '20');
        $ended = time();
        $consumed = $ended - $started;
        printConsumedReport('consumed', $cl, '0', $consumed);
        printStandardReport('SuspendedFinish',$command_line, '0');
        create_footer();
    }

    function create_footer() {
        global $db_con, $plus_nr, $log_handle, $log_file;

        $footer_msg = "<p class='bd'>
                <span class='em'>
                <br /><br />Indexing / Re-indexing finished.<br /><br />
                </span></p>
            ";

        LogUpdate($log_handle, $footer_msg);
    }

    function create_logFile($id) {
        global $log_format, $log_dir, $dba_act, $log_file;

        //  prepare current log file
        if ($log_format == 'text') {
            $log_file =  $log_dir."/db".$dba_act."_".Date("ymd-H.i.s").".txt";
        } else {
            $log_file =  $log_dir."/db".$dba_act."_".Date("ymd-H.i.s")."_".$id.".html";
        }
        if (!$log_handle = fopen($log_file, 'w')) {            //      create a new log file
            $logdir = mkdir($log_dir, 0777);                    //      try to create a log directory
            if ($logdir != '1') {
                die ("Logging option is set, but cannot create folder for logging files.");
            } else {
                if (!$log_handle = fopen($log_file, 'w')) {     //      try again to create a log file
                    die ("Logging option is set, folder was created, but cannot open a file for logging.");
                }
            }
        }
        chmod("$log_file", 0777);    //  required for command line operation
        return $log_handle;
    }

    function LogUpdate($log_handle, $log_msg){

        if (!$log_handle) {
            die ("Cannot open file for realtime logging. ");
        }

        if (fwrite($log_handle, $log_msg) === FALSE) {
            die ("Cannot write to file for realtime logging. ");
        }
    }

    function clear_TextCache() {
        global $textcache_dir;

        $count = '0';
        if ($handle = opendir($textcache_dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    @unlink("".$textcache_dir."/".$file."");
                    $count++;
                }
            }
        }
    }

    function clear_MediaCache() {
        global $mediacache_dir;

        $count = '0';
        if ($handle = opendir($mediacache_dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    @unlink("".$mediacache_dir."/".$file."");
                    $count++;
                }
            }
        }
    }

    function gz_decode($data, $c, $t) {

        $fpointer   = 0;
        $result     = '';

        //  check, for really gzip coded data
        if("\x1f\x8b" != substr($data, $pointer,2) ){
          $result = "error_gz0";
        }
/*
        if("\x08" != substr($data, $pointer,1) ){
          $result = "Compression method must be 'deflate'";
        }
*/
        if(!$result) {
            $result = gzinflate(substr($data,10,-8));
        }

        return $result;
    }

    function pre_all() {
        global $db_con, $mysql_table_prefix, $debug;

        $sql_query ="SET SQL_MODE='ALLOW_INVALID_DATES'";
        $db_con->query ($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }

        $sql_query = "UPDATE ".$mysql_table_prefix."sites set indexdate='NULL'";
        $db_con->query ($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }
    }

    function extract_js($contents) {
        global $clear;

        $regs = array();

        if(preg_match_all("/document\.write\((\"|')(.*?)(\"|')\);/si", $contents, $regs)) {
            $content = '';
            $content = implode("\r\n", $regs[2]);

            //  remove unused parts of the content
            $content    = preg_replace("@<!--.*?-->@si", " ",$content);
            $content    = preg_replace("@<style[^>]*>.*?<\/style>@si", " ", $content);
            $content    = preg_replace("/<link rel[^<>]*>/i", " ", $content);
            $content    = str_replace ("encoding: ''", " ", $content);        //  yes, I've seen such nonsense !
            $content    = preg_replace("@<script[^>]*?>.*?<\/script>@si", " ",$content);
        }

/*
//  if only links and their titles should be found in JavaScript
//  comment the above if preg_match_all loop completely and use this one here
        if(preg_match_all("/<a\s*href(.*?)<\/a>/si", $contents, $regs)) {
            $content = '';
            $content = implode("\r\n", $regs[0]);
        }
*/
        if ($clear == 1) {
            $regs = array ();
            unset ($contents);
        }

        return $content;
    }

    function curl_get_file_contents($url) {
             $c = curl_init();
             curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
             curl_setopt($c, CURLOPT_URL, $url);
             $contents = curl_exec($c);
             curl_close($c);

             if ($contents) return $contents;
                 else return FALSE;
         }

    function convertToUTF8($file, $charSet, $char_Set, $converter_dir) {
        global $home_charset;

        $conv_file  = $file;     //  pure code

        if (stristr($charSet, "WINDOWS-31J")) { //
            $charSet = 'cp936';     //  use cp936, which is equal to WINDOWS-31J
        }

        $iconv_file = @iconv($charSet,"UTF-8//IGNORE",$conv_file);  //      if installed, first try to use PHP function iconv()
        //      IGNORE => ignore unknown characters
        //      TRANSLIT=> replace unknown characters  with something similar
        //      Attention: TRANSLIT breaks converting, if no 'close to' chararacter will be found
        //echo "\r\n\r\n<br /> iconv_file: $iconv_file<br />";
        if(trim($iconv_file) == ""){        // iconv is not installed or input charSet not available. We need to use class ConvertCharset
            $char_Set = str_ireplace ('iso-','',$charSet);
            //$charSet = str_ireplace ('iso','',$charSet);
            $converter = "".$converter_dir."/charsets/".$char_Set.".txt" ;
            if(!is_file($converter) ) {                             //      if this charset table is not avaulable
                $char_Set = str_ireplace ('iso-','',$home_charset);  //      try alternatively the home charset
                printConverterError($charSet, $cl);
                printTryHome($home_charset, $cl);
            }

            if (is_file($converter) || $home_charset != 'UTF-8') {  //  UTF-8 -> UTF-8 would not work
                $NewEncoding    = new ConvertCharset($char_Set, "utf-8");
                $NewFileOutput  = $NewEncoding->Convert($conv_file);

                //$NewEncoding    = new ConvertCharset;
                //$NewFileOutput  = $NewEncoding->Convert($conv_file, $chrSet, "utf-8",false);
                $file = $NewFileOutput;
            }
        }else{
            $file = $iconv_file;
        }
        unset ($conv_file, $iconv_file, $NewEncoding, $NewFileOutput);
        return $file;
    }

    function check_utf8($str) {

        $len = strlen($str);
        for($i = 0; $i < $len; $i++){
            $c = ord($str[$i]);
            if ($c > 128) {
                if (($c > 247)) return false;
                elseif ($c > 239) $bytes = 4;
                elseif ($c > 223) $bytes = 3;
                elseif ($c > 191) $bytes = 2;
                else return false;
                if (($i + $bytes) > $len) return false;
                while ($bytes > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191) return false;
                    $bytes--;
                }
            }
        }
        return true;
    }

    // Unicode BOM is U+FEFF, but after encoded, it will look like this.
    define ('UTF32_BIG_ENDIAN_BOM'   , chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF));
    define ('UTF32_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00));
    define ('UTF16_BIG_ENDIAN_BOM'   , chr(0xFE) . chr(0xFF));
    define ('UTF16_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE));
    define ('UTF8_BOM'               , chr(0xEF) . chr(0xBB) . chr(0xBF));

    function detect_utf_encoding($filename) {

        $text = file_get_contents($filename);
        $first2 = substr($text, 0, 2);
        $first3 = substr($text, 0, 3);
        $first4 = substr($text, 0, 3);

        if ($first3 == UTF8_BOM) return 'UTF-8';
        elseif ($first4 == UTF32_BIG_ENDIAN_BOM) return 'UTF-32BE';
        elseif ($first4 == UTF32_LITTLE_ENDIAN_BOM) return 'UTF-32LE';
        elseif ($first2 == UTF16_BIG_ENDIAN_BOM) return 'UTF-16BE';
        elseif ($first2 == UTF16_LITTLE_ENDIAN_BOM) return 'UTF-16LE';
    }

    function utf16_to_utf8($str) {

        $c0 = ord($str[0]);
        $c1 = ord($str[1]);

        if ($c0 == 0xFE && $c1 == 0xFF) {
            $be = true;
        } else if ($c0 == 0xFF && $c1 == 0xFE) {
            $be = false;
        } else {
            return $str;
        }

        $str = substr($str, 2);
        $len = strlen($str);
        $dec = '';
        for ($i = 0; $i < $len; $i += 2) {
            $c = ($be) ? ord($str[$i]) << 8 | ord($str[$i + 1]) :
            ord($str[$i + 1]) << 8 | ord($str[$i]);
            if ($c >= 0x0001 && $c <= 0x007F) {
                $dec .= chr($c);
            } else if ($c > 0x07FF) {
                $dec .= chr(0xE0 | (($c >> 12) & 0x0F));
                $dec .= chr(0x80 | (($c >>  6) & 0x3F));
                $dec .= chr(0x80 | (($c >>  0) & 0x3F));
            } else {
                $dec .= chr(0xC0 | (($c >>  6) & 0x1F));
                $dec .= chr(0x80 | (($c >>  0) & 0x3F));
            }
        }
        return $dec;
    }

    function XML_IsWellFormed($buf) {

        libxml_use_internal_errors(true);
        libxml_clear_errors(true);

        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->loadXML($buf);

        $errors = libxml_get_errors();
        if (empty($errors)){
            return true;
        }

        $error = $errors[ 0 ];
        if ($error->level < 3){
            return true;
        }

        $lines = explode("r", $buf);
        $line = $lines[($error->line)-1];

        $message = $error->message . ' at line ' . $error->line . ':<br /><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ' . htmlentities($line);

        return $message;
    }

    function limit_text( $text, $limit) {
        global $command_line, $no_log;

        if ( strlen ( $text ) < $limit ) {
            return $text;
        }

        printStandardReport('outLimit', $command_line, $no_log);
        $split_words = explode(' ', $text );
        $out = null;
        foreach ( $split_words as $word ) {
            if ( ( strlen( $word ) > $limit ) && $out == null ) {
                 return substr( $word, 0, $limit )."...";
            }

            if (( strlen( $out ) + strlen( $word ) ) > $limit) {
                return $out . "...";
            }
            $out.=" " . $word;
        }
        return $out;
    }

	function SimpleXML2Array($xml){
		$array = (array)$xml;

		//recursive Parser
		foreach ($array as $key => $value){
			if(strpos(get_class($value),"SimpleXML")!==false){
				$array[$key] = SimpleXML2Array($value);
			}
		}
		return $array;
	}

	function clean_quotes($file) {
		global $all_quotes, $dup_quotes;
        //  convert all single  into standard quote
        if ($quotes == '1') {
            $all_quotes = array
            (
                    "&#8216;"   => "'",
                    "&lsquo;"   => "'",
                    "&#8217;"   => "'",
                    "&rsquo;"   => "'",
                    "&#8242;"   => "'",
                    "&prime;"   => "'",
                    "ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¹Ã…â€œ" => "'",
                    "ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¹Ã…â€œ" => "'",
                    "ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â´"              => "'",
                    "`"         => "'",
                    "‘"         => "'",
                    "’"         => "'",
                    "Ã¢â‚¬â„¢"  => "'",
                    "Ã¢â‚¬Ëœ"   => "'",
                    "ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢"  => "'",
                    "ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢"  => "'"
            );

			foreach ($all_quotes as $key => $value){
                $file           = preg_replace("/".$key."/si", $value, $file);
            }
        }

        //  convert all double quotes into standard quotations
        if ($dup_quotes == '1') {
            $all_quotes = array
            (
                    "ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã¢â‚¬Å“"   => "\"",
                    "ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¯Ã‚Â¿Ã‚Â½"   => "\"",
                    "ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¾"   => "\""
                    );

					foreach ($all_quotes as $key => $value){
                        $file           = preg_replace("/".$key."/i", $value, $file);
                    }
        }
		return $file;
	}

	function clean_cdata($str) {
		return preg_replace('#(^\s*<!\[CDATA\[|\]\]>\s*$)#sim', '', (string)$str);
	}

?>