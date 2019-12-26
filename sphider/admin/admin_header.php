<?php
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE & ~E_STRICT);
   //   check PHP version
    $php_vers   = phpversion();
    $php        = substr(str_replace(".", "", phpversion()), 0, 2);
    if ($php < "53") {
        die ("<br /><br /><strong>&nbsp;&nbsp;&nbsp;&nbsp;Sphider-plus scripts require at minimum PHP version 5.3<br /><br />&nbsp;&nbsp;&nbsp;&nbsp;Currently version $php_vers is installed on your server.<br /><br /><strong>&nbsp;&nbsp;&nbsp;&nbsp;Please install a newer PHP version before using Sphider-plus.<br /><br />&nbsp;&nbsp;&nbsp;&nbsp; Script execution aborted.</strong>");
    }

    $zip = @new ZipArchive();
    $res = $zip->open('test.zip', ZipArchive::CREATE);
    if ($res === TRUE) {
        //  zip extension is available
    } else {
        die ("<br /><br /><strong>&nbsp;&nbsp;&nbsp;&nbsp;Sphider-plus requires an installed ZIP extension as part of the PHP environment.<br /><br />&nbsp;&nbsp;&nbsp;&nbsp;Please install the library as described in the PHP docu at http://php.net/manual/en/zip.installation.php<br /><br />&nbsp;&nbsp;&nbsp;&nbsp;Script execution aborted.</strong>");
    }

    //  check the server time setting
    $ddt        = '';
    $ddt_set    = '';
    $ddt = date_default_timezone_get();    //  try to read the server defaults
    $token1 	= "token1.txt";
    $token2 	= "token2.txt";

    if ($ddt != '') {
        $ddt_set = date_default_timezone_set($ddt);
        if (!$ddt_set){    //  this will prevent 'STRICT' error messages for date() and time() functions
            die ("<br /><br /><strong>&nbsp;&nbsp;&nbsp;&nbsp;The Sphider-plus scripts are unable to set the date_default_timezone on your server.<br /><br /><strong>&nbsp;&nbsp;&nbsp;&nbsp;Please enable this PHP function. Script execution aborted for security reasons.</strong>");
        }
    } else {
        die("<br /><br /><strong>&nbsp;&nbsp;&nbsp;&nbsp;The Sphider-plus scripts are unable to read the date_default_timezone from your server.<br /><br />&nbsp;&nbsp;&nbsp;&nbsp;Please enable this PHP function. Script execution aborted for security reasons.</strong>");
    }

    set_time_limit (0);

    $database1  	= '';
    $plus_nr    	= '';
    $inst_dir   	= '';
    $install_dir	= '';
    $dir0       	= '';
    //  For command line operation, try to correct the working directory
    $dir0 = str_replace('\\', '/', __DIR__);
    if (strlen($dir0) < '2') {
        echo "<br />Attention: Command line operation may fail for your Sphider-plus installation.<br />";
        echo " Please install PHP version 5.3 or newer for proper operation. <br /><br />";
    } else {
        chdir($dir0);
    }
    // now get the actual directory
    $dir = str_replace('\\', '/', getcwd());
    if (!strpos($dir, "/admin")) {
        echo "Unable to set the admin directory to Sphider-plus installation folder. Execution of admin backend aborted.";
        die ('');
    }

    //  get the root URL of this Sphider-plus installation
    $inst_dir       = substr($dir0, 0, strpos($dir0, "/admin"));
    $install_dir    = str_replace('\\', '/',$_SERVER['DOCUMENT_ROOT']);
    if (strpos($install_dir, ":")) {
        $install_dir    = str_replace('\\', '/',$_SERVER['REQUEST_URI']);   //  XAMPP prefers this
    }

    if (strpos($install_dir, "/admin")) {
        $install_dir = substr($install_dir, 0, strpos($install_dir, "/admin"));
    }

    //  prepare the HTML base tag for Sphider-plus installation ($install_url)  and admin script ($admin_url)
    $scheme     = $_SERVER['HTTPS'];
    if (!$scheme){
        $scheme = "http";
    } else {
        $scheme = "https";
    }
    $host           = $_SERVER['HTTP_HOST'];
    $uri            = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $file           = 'admin.php';
    $admin_url      = "$scheme://$host$uri/$file";
    $install_url    = substr($admin_url, 0, strpos($admin_url, "/admin"));
	$sess_dir 		= "./php_sessions/";

    $admin_dir      = "$inst_dir/admin";
    $log_dir        = "$admin_dir/log";
    $tmp_dir        = "$admin_dir/tmp";
    $admset_dir     = "$admin_dir/settings";
    $admback_dir    = "$admin_dir/settings/backup";
    $smap_dir       = "$admin_dir/sitemaps";
    $url_dir        = "$admin_dir/urls";
    $thumb_folder   = "$admin_dir/thumbs";          //  temporary folder for thumbnails during index procedure
    $thumb_url      = "$scheme://$host$uri/thumbs";
    $url_path       = "$admin_dir/urls/";           //  folder for URL import / export will be handled

    $template_dir   = "$inst_dir/templates";        //  folder which holds in subfolders the different templates like 'Pure', 'Sphider-plus' etc.
    $template_url   = "$install_url/templates";     //  Base template URL for HTML includes
    $include_dir    = "$inst_dir/include";
    $include_url    = "$install_url/include";

    $image_dir      = "$include_dir/images";
    $textcache_dir  = "$include_dir/textcache";
    $mediacache_dir = "$include_dir/mediacache";
    $thumb_dir      = "$include_dir/thumbs";        //  temporary folder for thumbnails for search algorithm
    $flood_dir      = "$include_dir/tmp";           //  temporary folder for web-shots and flood file

    $settings_dir   = "$inst_dir/settings";
    $converter_dir  = "$inst_dir/converter";
    $language_dir   = "$inst_dir/languages";
    $xml_dir        = "$inst_dir/xml";              //  folder for XML results

    //  check whether  'include(): open_basedir restriction in effect' for this app
	error_reporting (E_ALL);				//	temporary we like to know all
    $log_file = './tmp/z_error.log';
    file_put_contents($log_file, '');   	//  delete former content in log file
    ini_set('display_errors', '0');    		//  temporary no monitor output
	ini_set('log_errors', '1');
    ini_set('error_log', $log_file);    	//  instead write error report into log file
    include "$settings_dir/database.php";	//  this tries to include a file from another sub folder
    $content = file_get_contents($log_file);
    //  warning message is stored in log file?
    if (preg_match("/PHP Warning\:/", $content)) {
        die ("<br /><br />&nbsp;&nbsp;&nbsp;&nbsp;The PHP control structure 'include' seems not to work on your system.<br />&nbsp;&nbsp;&nbsp;&nbspopen_basedir restriction is in effect for the Sphider-plus installtion on this server.<br /><br />&nbsp;&nbsp;&nbsp;&nbsp; Please modify the according options in your PHP environment.<br /><br />&nbsp;&nbsp;&nbsp;&nbsp; Script execution aborted.");
    }
	ini_set('display_errors','1');	//  re-enable monitor output for PHP messages
    //error_reporting (E_ALL);    	//  use this for script debugging
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE & ~E_STRICT);

	if (!$database1) {
        // HTML5 header";
        echo "<!DOCTYPE HTML>\n";
		echo "<html lang='en'>\n";
        echo "  <head>\n";
        echo "      <base href = $admin_url>\n";
        echo "      <title>Sphider-plus administrator warning</title>\n";
        // meta data
        echo "      <meta charset='UTF-8'>\n";
        echo "      <meta http-equiv='cache-control' content='no-cache, no-store, must-revalidate'/>\n";
        echo "      <meta http-equiv='pragma' content='no-cache'/>\n";
        echo "      <meta http-equiv='expires' content='0'/>\n";
        echo "      <meta name='public' content='all'>\n";
        echo "      <meta http-equiv='X-UA-Compatible' content='IE=edge' />\n";
        echo "      <link href='$template_url/html/sphider-plus.ico' rel='shortcut icon' type='image/x-icon' />\n";
        echo "  </head>\n";
        echo "  <body>
    <br /><br />
    <div style=\"text-align:center;\">
        <strong>Attention:</strong> Unable to load the database configuration file.<br />
        Please reinstall Sphider-plus by using the original scripts as per download.<br />
        <br /><br />
    </div>
  </body>
</html>
            ";
        die ();
    }

    //  try to write in configuration sub folder into a test file
    $fp = @fopen("".$settings_dir."/".$testfile."","w");
    if(!is_writeable("".$settings_dir."/".$testfile."")) {
        echo "  <br />
                    <p class='warnadmin cntr'><br />
                    Folder for configuration files is not writeable.<br />
                    Sphider-plus will not be able to store any configuration.<br /><br />
                    On *nix operating systems chmod 777 the sub folder<br />
                    <span class='blue'>".$settings_dir."/</span> <br />
                    <br /><br /></p>
                ";
        die();
    }
    @fclose($fp);
    @unlink("$settings_dir/$testfile"); // remove test file

    //      get active database for Admin
    if ($dba_act == '1') {
        $db_con             = adb_connect($mysql_host1, $mysql_user1, $mysql_password1, $database1) ;
        $database           = $database1;
        $mysql_table_prefix = $mysql_table_prefix1;
    }

    if ($dba_act == '2') {
        $db_con             = adb_connect($mysql_host2, $mysql_user2, $mysql_password2, $database2) ;
        $database           = $database2;
        $mysql_table_prefix = $mysql_table_prefix2;
    }

    if ($dba_act == '3') {
        $db_con             = adb_connect($mysql_host3, $mysql_user3, $mysql_password3, $database3) ;
        $database           = $database3;
        $mysql_table_prefix = $mysql_table_prefix3;
    }

    if ($dba_act == '4') {
        $db_con             = adb_connect($mysql_host4, $mysql_user4, $mysql_password4, $database4) ;
        $database           = $database4;
        $mysql_table_prefix = $mysql_table_prefix4;
    }

    if ($dba_act == '5') {
        $db_con             = adb_connect($mysql_host5, $mysql_user5, $mysql_password5, $database5) ;
        $database           = $database5;
        $mysql_table_prefix = $mysql_table_prefix5;
    }

    //  now it is time to get any valid configuration file
    $default = '';
    include "".$settings_dir."/db".$dba_act."/conf_".$mysql_table_prefix.".php";

    if (!$plus_nr) {
        include "$admin_dir/settings/backup/Sphider-plus_default-configuration.php";
        $default = '1';
    }

    //  Repeat detection of installation directory.
    //  Eventually the Sphider-plus installation was moved to another server,
    //  so that even default values are invalid.
    $inst_dir   = '';
    $install_dir     = '';

    //  For command line operation, try to correct the working directory
    $dir0 = str_replace('\\', '/', __DIR__);
    chdir($dir0);
    // now get the actual directory
    $dir = str_replace('\\', '/', getcwd());

    if (!strpos($dir, "/admin")) {
        echo "Unable to set the admin directory to Sphider-plus installation folder. Execution of admin backend aborted.";
        die ('');
    }
    //  get the root URL of this Sphider-plus installation
    $inst_dir       = substr($dir0, 0, strpos($dir0, "/admin"));
    $install_dir    = str_replace('\\', '/',$_SERVER['DOCUMENT_ROOT']);
    if (strpos($install_dir, ":")) {
        $install_dir         = str_replace('\\', '/',$_SERVER['REQUEST_URI']);   //  XAMPP prefers this
    }

    if (strpos($install_dir, "/admin")) {
        $install_dir = substr($install_dir, 0, strpos($install_dir, "/admin"));
    }

    //  prepare the HTML base tag for this script installation
    $scheme     = $_SERVER['HTTPS'];
    if (!$scheme){
        $scheme = "http";
    } else {
        $scheme = "https";
    }
    $host           = $_SERVER['HTTP_HOST'];
    $uri            = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $file           = 'admin.php';
    $admin_url      = "$scheme://$host$uri/$file";
    $install_url    = substr($admin_url, 0, strpos($admin_url, "/admin"));
	$sess_dir 		= "./php_sessions/";

    //  re-defining the most important folder
    $admin_dir      = "$inst_dir/admin";
    $log_dir        = "$admin_dir/log";
    $tmp_dir        = "$admin_dir/tmp";
    $admset_dir     = "$admin_dir/settings";
    $admback_dir    = "$admin_dir/settings/backup";
    $smap_dir       = "$admin_dir/sitemaps";
    $url_dir        = "$admin_dir/urls";
    $thumb_folder   = "$admin_dir/thumbs";      //  temporary folder for thumbnails during index procedure
    $url_path       = "$admin_dir/urls/";       //  folder relative to .../admin/ where all the files for URL import / export will be handled

    $template_dir   = "$inst_dir/templates";    //  folder which holds in subfolders the different templates like 'Pure', 'Sphider-plus' etc.
    $template_url   = "$install_url/templates"; //  Base template URL for HTML includes
    $include_dir    = "$inst_dir/include";
    $include_url    = "$install_url/include";

    $image_dir      = "$include_dir/images";
    $textcache_dir  = "$include_dir/textcache";
    $mediacache_dir = "$include_dir/mediacache";
    $thumb_dir      = "$include_dir/thumbs";    //  temporary folder for thumbnails during search procedure
    $flood_dir      = "$include_dir/tmp";       //  temporary folder for web-shots and flood file

    $settings_dir   = "$inst_dir/settings";
    $converter_dir  = "$inst_dir/converter";
    $language_dir   = "$inst_dir/languages";
    $xml_dir        = "$inst_dir/xml";          //  folder for XML results
    //  end of repetition

    //  here to continue after re-defining all the above

    //  check for protection against remote server access
    $server_addr    = '';
    $remote_addr    = '';
    $test_file      = 'only.me';
    $server_addr = @$_SERVER['SERVER_ADDR'];
    $remote_addr = @$_SERVER['REMOTE_ADDR'];

	//	for all our friends, using Windows IIS v.6 server
	if (strlen($server_addr) < 2) {
		$server_addr = gethostbyname($_SERVER['SERVER_NAME']);
	}
	//	correct $remote_addr for 'localhost' applications
	if ($server_addr == '127.0.0.1' && $remote_addr ==  '::1'){
		$remote_addr =	'127.0.0.1';
	}
/*
echo "\r\n\r\n<br /> server_addr : '$server_addr'<br />\r\n";
echo "\r\n\r\n<br /> remote_addr: '$remote_addr'<br />\r\n";
*/
    if ($ext_IP == 1 && is_file($test_file) && strlen($server_addr) > 2 && $server_addr != $remote_addr) {
        die ("<br /><br />&nbsp;&nbsp;&nbsp;&nbsp;Access is not granted to this admin backend.<br /><br />&nbsp;&nbsp;&nbsp;&nbsp; Script execution aborted.");
    }

	//	add all the stuff we may need
    include "$include_dir/commonfuncs.php";

    //  block all queries from IPs known to ne evil
    if ($protect_admin == '1' && (false===strrpos($_SERVER['REMOTE_ADDR'], ":"))) {

		$client_ip = @$_SERVER['REMOTE_ADDR'];

		//$client_ip =  '46.249.204.22';	//  okay for tests
		//$client_ip =  '212.227.109.162';	//  okay for tests
        //$client_ip =  '2.46.148.146';     //  okay for tests
        //$client_ip =  '83.167.241.0';     //  okay for tests
        //$client_ip = "174.129.228.67";  	//	test for known IP used by Amazon
        //$client_ip = "40.77.167.92";  	//	test for known IP range used by MSN
        //$client_ip = "66.102.6.199";  	//	another test for known IP range used by Google
		//$client_ip = "5.45.203.7";  		//	another test for known IP range used by Yandex
//echo "\r\n\r\n<br /> client_ip: '$client_ip'<br />\r\n";
		$client_ip 		= str_replace(".00-", ".0-", $client_ip);	//	required because sometimes ip2log() fails for .00-
        $enc_client_ip 	= unpack('l', pack('l', ip2long(trim($client_ip))));
        $enc_client_ip 	= $enc_client_ip[1];

        foreach ($black_ips as $value) {    //  check all forbidden single IPs and IP ranges
            if(!strpos($value, "-")) {      //  enter here for single IPs
                if ($client_ip == $value) {

					$range			= array();
					$enc_range_low	= '-';
					$enc_range_high	= '-';
					$enc_client_ip	= '-';

					$message1 = "No access for you (1).";
					$message2 = "No results for you (1), because of blocked single IP.";
					report($uri, $server_name, $server_addr, $client_ip, $client_host, $request_uri, $range, $value,
							$enc_range_low, $enc_range_high, $enc_client_ip, $message2, $client_ua, $cc_co);

                    if ($debug_user) {
                        die("<br />With respect to the corresponding Admin setting,<br />no results are presented for the IP <strong>$client_ip</strong><br />Known to be used by a Meta search engine, or has been former evil.");
                    } else {
                        die("<br />$message1");
                    }
                }
            } else {    //  enter here for IP range
				$enc_range_low 	= '';
				$enc_range_high	= '';
				$value = str_replace(".00-", ".0-", $value);	//	required because sometimes ip2log() fails for .00-
				$value = preg_replace("/\s|\/|[A-Z][a-z]/", "", $value);
//echo "\r\n\r\n<br /> value: '$value'<br />\r\n";
                $range = explode('-', $value);	// separate the low border IP from the high border
//echo "\r\n\r\n<br>range Array:<br><pre>";print_r($range);echo "</pre>\r\n";
                $enc_range_low = unpack('l', pack('l', ip2long(trim($range[0]))));
                $enc_range_low = $enc_range_low[1];
                $enc_range_high = unpack('l', pack('l', ip2long(trim($range[1]))));
                $enc_range_high = $enc_range_high[1];

                if($enc_client_ip >= $enc_range_low && $enc_client_ip <= $enc_range_high) {

					$message1 = "No access for you (2).";
					$message2 = "No results for you (2), because of blocked IP range.";
					report($uri, $server_name, $server_addr, $client_ip, $client_host, $request_uri, $range, $value,
							$enc_range_low, $enc_range_high, $enc_client_ip, $message2, $client_ua, $cc_co);

                    if ($debug_user) {
                        $value = str_replace("-", " - ", $value);
                        die("<br />With respect to the corresponding Admin setting,<br />no results are presented for the IP range <strong> $value </strong><br /> Known to be used by a Meta search engine.<br />Here they used: <strong>$client_ip</strong>");
                    } else {
                        die("<br />$message1");
                    }
                }
            }
        }
    }

    if ($debug == '0') {
        if (function_exists("ini_set")) {
            ini_set("display_errors", "0");
        }
    }
    //  check if multi-byte functions are available
    //  this check is required only for first call of admin.php
    //  later on this check is also performed by configset.php together with a warning message
    $mb = '';
    if (function_exists('mb_internal_encoding')) {
        if(function_exists('mb_stripos')) {
            $mb = '1';
        }
    }
    if ($mb != 1) {
        $mb = '0';
    }

    $template_path  = "$template_url/$template";

    //require_once('phpSecInfo/PhpSecInfo.php');        //   (might not work on Shared Hosting server)
    //include("$admin_dir/geoip.php");

    // HTML5 header";
    echo "<!DOCTYPE HTML>\n";
	echo "<html lang='en'>\n";
    echo "  <head>\n";
    echo "      <base href = $admin_url>\n";
    echo "      <title>Sphider-plus administrator</title>\n";
    // meta data
    echo "      <meta charset='UTF-8'>\n";
    echo "      <meta name='public' content='all'>\n";
    echo "      <meta http-equiv='X-UA-Compatible' content='IE=edge' />\n";

    echo "      <link href='$template_url/html/sphider-plus.ico' rel='shortcut icon' type='image/x-icon' />\n";
    echo "      <link rel='stylesheet' type='text/css' href='$template_path/adminstyle.css' />\n";
    echo "      <script src='confirm.js'></script>
      <script>
          function JumpBottom() {
              window.scrollTo(0,100000);
          }
      </script>\n";
    echo "      <script>
          function JumpUp() {
              window.scrollTo(0,-100000);
          }
      </script>\n";
    echo "  </head>\n";
    echo "  <body>\n";

    //  check offset between MySQL clock and PHP clock
    //  yes, we've seen differences of up to 5 hours on some server . . .
    $test = $db_con->errno;     //  check for valid $db_con, which will deliver '0', but not ''
    if (strlen($test) == 1)  {  //  this test will not work, until first db is initialized. Enter here if $test = '0'

		$remote_addr = '0.1.2.3';
        $sql_query = "INSERT into ".$mysql_table_prefix."query_log (query, time, elapsed, results, ip, media)
                                                            values ('sphider-plus', NOW(), '1', '2', '$remote_addr', '0')";

        $db_con->query($sql_query);
        if (!$db_con->errno) {  //  this test will not work, until first set of tables is installed in db
            $sql_query = "SELECT * from ".$mysql_table_prefix."query_log where ip = '$remote_addr' order by time desc  limit 0,10 ";
            $result = $db_con->query($sql_query);
            if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-5;
                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
            }

            $row 		= $result->fetch_array(MYSQLI_NUM);
            $queried1   = $row[1];                      //  this is the MySQL time
            $queried2   = retMktimest($row[1]);
            $test1      = time();                       //  this is the PHP time
            $test2      = date("Y-m-d H:i:s");
            $diff_s     = $test1-$queried2;
            if (strstr($diff_s, '-')) {
                $diff_h = toHours(substr($diff_s, 1));
                $diff_h = "- ".$diff_h;
            } else {
                $diff_h = toHours($diff_s);
            }
            $queried1   = str_replace(" ", "&nbsp;&nbsp;&nbsp;&nbsp;", $queried1);
            $test2      = str_replace(" ", "&nbsp;&nbsp;&nbsp;&nbsp;", $test2);
        /*
            echo "\r\n\r\n<br /> MySQL date/time: '$queried1'<br />\r\n";
            echo "\r\n\r\n<br /> PHP date/time: &nbsp;&nbsp;&nbsp;&nbsp;'$test2'<br />\r\n";
            echo "\r\n\r\n<br /> MySQL(UNIX timestamp) '$queried2'<br />\r\n";
            echo "\r\n\r\n<br /> PHP: (UNIX timestamp) &nbsp;&nbsp;'$test1'<br />\r\n";
            echo "\r\n\r\n<br /> Difference '$diff_s' seconds =  '$diff_h' hours<br />\r\n";
        */
            $sql_query = "DELETE FROM ".$mysql_table_prefix."query_log WHERE ip='$remote_addr'";
            $result = $db_con->query($sql_query);
            if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-5;
                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
            }

            if ($diff_s > "1" || $diff_s < -1) {
                echo "<p class='red em cntr'><br /><br />Attention: Sphider-plus recognized a server problem (clocks asynchronous).<br /><br /><br /></p>
                    <p class='red em'><br />&nbsp;&nbsp;&nbsp;A test to read the MySQL database <span class='blue'>$db_num</span> answers at:&nbsp;&nbsp;<span class='blue'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$queried1</span><br />
                    <br />&nbsp;&nbsp;&nbsp;Your PHP server (UNIX time stamp) responds with:&nbsp;&nbsp;<span class='blue'>$test2</span>, which is time zone<span class='blue'> '$ddt'</span><br /><br />
                    <br />&nbsp;&nbsp;&nbsp;Sphider-plus is unable to continue with a difference of <span class='blue'>".$diff_s = str_replace("-", "- ", $diff_s)." seconds ($diff_h hours)</span> between MySQL and PHP clocks.<br />
                    <br />&nbsp;&nbsp;&nbsp;Please follow the instructions as explained in the FAQs at <a href ='http://www.sphider-plus.eu/index.php?f=30#10e'><span class='green'>http://www.sphider-plus.eu/index.php?f=30#10e</span></a><br />
                    <br />&nbsp;&nbsp;&nbsp;or contact your system administrator, and ask him to synchronize both clocks.<br /><br />
                    <br />&nbsp;&nbsp;&nbsp;Script execution aborted.<br /><br /></p>
        </body>
        </html>
                ";
                die (); //  unable to proceed
            }
        }
    }

    //  finally admin authentication is required

    @$fp 	= fopen("$tmp_dir/$token1", "r");		//	token1 available?
	$tk1	= @fread($fp, 512);
    @$hp 	= fopen("$tmp_dir/$token2", "r");		//	token2 available?
	$tk2	= @fread($hp, 512);
/*
echo "\r\n\r\n<br /> tk1: '$tk1'<br />\r\n";
echo "\r\n\r\n<br /> tk2: '$tk2'<br />\r\n";
echo "\r\n\r\n<br /> f: '$f'<br />\r\n";
echo "\r\n\r\n<br /> default: '$default'<br />\r\n";
*/
	if ($default == 1) {
		$f = 98;	//	already passed during first attempt			
	}

	if (!$fp || strlen($tk1) < 5 || !$hp || $tk2 != "passed" || $f == 99) {	

		include "$admin_dir/auth.php";
	}

	@fclose($fp);
	@fclose($hp);

    // Database 1-5 connection
    function adb_connect($mysql_host, $mysql_user, $mysql_password, $database) {
        $db_con = '';
        $db_con = @new mysqli($mysql_host, $mysql_user, $mysql_password, $database);
        /* check connection */
        if (!$db_con->connect_errno) {
            /* define character set to utf8 */
            if (!$db_con->set_charset("utf8")) {
                printf("Error loading character set utf8: %s\n", $db_con->error);

                /* Print current character set */
                $charset = $db_con->character_set_name();
                printf ("<br />Current character set is: %s\n", $charset);

                $db_con->close();
                exit;
            }
        }
        return ($db_con);
    }

?>
