<?php
	//error_reporting (E_ALL);
    if (!defined("_SECURE")) {
        define("_SECURE",1);    // if this script is called directly, define secure constant
    }

    $result         = '';
    $token          = '';
    $inst_dir       = '';
    $install_dir    = '';
    $admin_life     = '';
    $token1       = "token1.txt";
    $token2       = "token2.txt";

    //  For command line operation, try to correct the working directory
    $dir0 = str_replace('\\', '/', __DIR__);
    chdir($dir0);
    // now get the actual directory
    $dir = str_replace('\\', '/', getcwd());

    if (!strpos($dir, "/admin")) {
        echo "<br />Unable to set the admin directory to Sphider-plus installation folder. Execution of admin backend aborted.<br /><br />";
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
    $scheme     = @$_SERVER['HTTPS'];
    if (!$scheme){
        $scheme = "http";
    } else {
        $scheme = "https";
    }
    $host       = $_SERVER['HTTP_HOST'];
    $uri        = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $file       = 'admin.php';
    $admin_url  = "$scheme://$host$uri/$file";
    $install_url     = substr($admin_url, 0, strpos($admin_url, "/admin"));

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

    include "$settings_dir/database.php";

    //      get active database for Admin
    if ($dba_act == '1') {
        $db_con             = auth_db_connect($mysql_host1, $mysql_user1, $mysql_password1, $database1) ;
        $database           = $database1;
        $mysql_table_prefix = $mysql_table_prefix1;
    }

    if ($dba_act == '2') {
        $db_con             = auth_db_connect($mysql_host2, $mysql_user2, $mysql_password2, $database2) ;
        $database           = $database2;
        $mysql_table_prefix = $mysql_table_prefix2;
    }

    if ($dba_act == '3') {
        $db_con             = auth_db_connect($mysql_host3, $mysql_user3, $mysql_password3, $database3) ;
        $database           = $database3;
        $mysql_table_prefix = $mysql_table_prefix3;
    }

    if ($dba_act == '4') {
        $db_con             = auth_db_connect($mysql_host4, $mysql_user4, $mysql_password4, $database4) ;
        $database           = $database4;
        $mysql_table_prefix = $mysql_table_prefix4;
    }

    if ($dba_act == '5') {
        $db_con             = auth_db_connect($mysql_host5, $mysql_user5, $mysql_password5, $database5) ;
        $database           = $database5;
        $mysql_table_prefix = $mysql_table_prefix5;
    }

    $default = '';

    if (is_file("".$settings_dir."/db".$dba_act."/conf_".$mysql_table_prefix.".php")) {
        include "".$settings_dir."/db".$dba_act."/conf_".$mysql_table_prefix.".php";
    }

    if (!$plus_nr) {
        include "$admin_dir/settings/backup/Sphider-plus_default-configuration.php";
        $default = '1';
    }
    //  repeat detection of installation folder,
    //  because the scripts might have been moved to another server
    $inst_dir       = substr($dir0, 0, strpos($dir0, "/admin"));
    $install_dir    = str_replace('\\', '/',$_SERVER['DOCUMENT_ROOT']);
    if (strpos($install_dir, ":")) {
        $install_dir         = str_replace('\\', '/',$_SERVER['REQUEST_URI']);   //  XAMPP prefers this
    }

    if (strpos($install_dir, "/admin")) {
        $install_dir = substr($install_dir, 0, strpos($install_dir, "/admin"));
    }

    //  prepare the HTML base tag for this script installation
    $scheme     = @$_SERVER['HTTPS'];
    if (!$scheme){
        $scheme = "http";
    } else {
        $scheme = "https";
    }
    $host       = $_SERVER['HTTP_HOST'];
    $uri        = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $file       = 'admin.php';
    $admin_url  = "$scheme://$host$uri/$file";
    $install_url     = substr($admin_url, 0, strpos($admin_url, "/admin"));

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

    $template_path  = "$template_url/$template";

    //  define admin life time, if this is first call during installation update
    if ($admin_life < '1') {
        $admin_life     = '300';    //  value in seconds
    }

    //  first call for fresh installation
    if (!$plus_nr) {
        include "$admin_dir/settings/backup/Sphider-plus_default-configuration.php";
        $default = '1';
    }

    include "settings/authentication.php";

    $result = '';
    // if Intrusion Detection System should be used
    if ($use_ids == 1){
        require_once ("$include_dir/ids_handler.php");
        //IDS detected an attack?
        if (strlen($result) > 13) {
            //  get impact of intrusion
            $len = strpos($result, "<")-13;
            $res = trim(substr($result, '13', $len));

            if ($res >= $ids_stop) {
                echo "      <br /><br />
      <div class='headline cntr'>
          IDS result message
      </div>
      <br /><br />
      $result
      <br />
      <div class='cntr warnadmin'>
          <br />
          Further input blocked by the Sphider-plus supervisor, because the
          <br /><br />
          Intrusion Detection System noticed the above attempt to attack this search engine.
          <br /><br />
      </div>
      <div class='headline cntr'>
          &nbsp;
      </div>
      <br /><br />
  </body>
</html>
                ";
                die();
            }
            if ($res >= $ids_warn && !$ids_suppress) {
                echo "
            <div class='headline cntr'>
                IDS warning message
            </div>
            <br /><br />
            $result
            <br />
                ";
            }
        }
    }

	 if (!@is_array($_SESSION)){
		@session_unset();
		@session_destroy();
		$_SESSION   			= array();
		$username   			= '';
		$password   			= '';
		$_SESSION['admin']      = '';
		$_SESSION['admin_pw']   = '';
		//$_POST      			= array();
		@session_start();
		$_SESSION['admin_time']	= time();
    }

    //  limit admin inactivity life time
    if (!$admin_life) {
        $admin_life = trim(substr($_POST['admin_life'], 0,3));
    }

    //  update admin time-out value with return time from some (evtl. long running) scripts.
    if (preg_match("/spider\.php|install_tables|db_config|database|db_copy/i", @$_SERVER['HTTP_REFERER'])) {
        $_SESSION['admin_time'] = time();
    }

    //  if the time-out field exists.
    if(isset($_SESSION['admin_time'])) {
        // see whether the number of seconds since last visit is larger than the time-out period.
        $duration = time() - (int)$_SESSION['admin_time'];
        if($duration > $admin_life) {
            // Destroy the session and start a new one.
			session_unset();
            session_destroy();
			$_SESSION   			= array();
            $username   			= '';
            $password   			= '';
            $_SESSION['admin']      = '';
            $_SESSION['admin_pw']   = '';
            $_POST      			= array();
            session_start();
        }
    }

    //  update admin time-out value with the current time.
    $_SESSION['admin_time'] = time();

    //  limit admin inactivity life time
    if (!$admin_life) { //  enter here for direct call of auth.php
        $admin_life = trim(substr($_POST['admin_life'], 0,3));
    }

	//	observe the amount of 'log in' attempts
	if (@$f == 99 && $login_att != 0){
		$act_time 		= time();				//	get actual timestap
		$login_delay	= $login_delay * 60;	//	get the delay in seconds
		$count0 		= '';					//	required for fresh attempt counter

		chmod("$tmp_dir/log_count.txt", 0777);
		if (array_key_exists ('HTTP_REFERER', $_SERVER) && !strpos($_SERVER['HTTP_REFERER'], "localhost")) {
			if(!is_writable("$tmp_dir/thread_ids.txt")) {
				echo "        <br />
			<p class='warnadmin cntr'><br />
			Attention: Sphider-plus is unable to set full write permission to the log_count file in .../admin/tmp/ folder.<br />
			Might cause problems for command line operation.<br />
			Modify the according server settings for PHP scripts.
			<br /><br />
			Script execution aborted.
			<br /><br /></p>
	";
				@fclose($fp);
				die();
			}
		}

		$fp = fopen("$tmp_dir/log_count.txt", "a+");
		$contents = fread($fp, filesize("$tmp_dir/log_count.txt"));
		@fclose($fp);
		//	get time of last access atempt
		$beginn 	= strpos($contents, '=')+2;
		$end		= strpos($contents, ";");
		$old_time	= substr($contents, $beginn, $end-$beginn);

		//	on first access old_time does not yet exist
		if ($old_time < 1) {
			$old_time = $act_time;
		}

		if ($act_time-$old_time >= $login_delay) {
			$count0 = 'new';		//	start with fresh attempts
		}

		//	build a fresh file content for first access
		if (!strstr($contents, "count")) {
			$count = 1;
			$contents = "time = $act_time;\r\ncount = $count;\r\n";
			file_put_contents ("$tmp_dir/log_count.txt", $contents);

		//	get contents and increase counter up to limit
		} else {
			$count = substr($contents, strrpos($contents, ";")-2, 2);
			if ($count0 == 'new') {
				$count = 0;		//	start with fresh attempt counter
			}

			//	too may attempts
			if ($count >= $login_att){
				echo "<br />
							<p class='warnadmin cntr'><br />
							<strong>Attention:</strong>
							<br /><br >
							Too many invald attempts to log in.
							<br /><br />
							Script execution aborted.
							<br /><br />
							<strong>Bye for now.</strong>
							<br /><br /></p>
						  </body>
						</html>
					";
				die ;
			} else {	//	another allowed attempt
				$count++;
				$contents = "time = $act_time;\r\ncount = $count;\r\n";
				//	store new values
				file_put_contents ("$tmp_dir/log_count.txt", $contents);
			}
		}
	}

    if (isset($_POST['user']) && isset($_POST['pass']) && isset($_POST['token'])) {
        //  remember the token , stored during last call of auth.php
        $fp = @fopen("$tmp_dir/$token1", "r");
        if (!$fp) {
            echo "  <br />
                        <div style=\"text-align:center;\">
                            <p class='warnadmin cntr'><br />
                            Unable to rebuild the token.<br /><br />Script execution aborted for security reasons.<br />
                            <br /><br /></p>
                        </div>
                    </div>
                  </body>
                </html>
                    ";
            die();
        } else {
            $o_token = @fread($fp, 512);
            @fclose($fp);
        }
/*
        //  detect XSRF attacks
        if($_POST['token'] != $o_token) {
            session_unset();
            session_destroy();
            $_SESSION   = array();
            $username   = '';
            $password   = '';
			$post_token = $_POST['token'];
            $_POST      = array();

            // message output
            echo "<!DOCTYPE HTML>\n";
            echo "  <head>\n";
            echo "      <title>Sphider-plus administrator knock-out</title>\n";
            echo "      <meta charset='UTF-8'>\n";
            echo "  </head>\n";
            echo "  <body>
    <br /><br />
    <div style=\"text-align:center;\">
        <strong>Attention:</strong> XSRF attack<br /><br />
        Script execution aborted for security reasons.<br />
        <br /><br />
    </div>
  </body>
</html>
            ";
            die ();
        }
*/

        $username = substr(trim($_POST['user']),0,255);
        //      prevent SQL-injection
        $username = str_replace('\\','\\\\', $username);
        $username = str_replace('"','\"', $username);

        //	prevent XSS-attack, Shell-execute and JavaScript execution
        if (preg_match("/cmd|CREATE|DELETE|DROP|eval|EXEC|File|INSERT|printf/i",$username)) {
            $username = '';
        }
        if (preg_match("/LOCK|PROCESSLIST|SELECT|shell|SHOW|SHUTDOWN/i",$username)) {
            $username = '';
        }
        if (preg_match("/SQL|SYSTEM|TRUNCATE|UNION|UPDATE|DUMP/i",$username)) {
            $username = '';
        }
        if (preg_match("/java|vbscri|embed|onclick|onmouseover|onfocus/i",$username)) {
            $username = '';
        }

        $password = substr(trim($_POST['pass']),0,255);

        //      prevent SQL-injection
        //$password = str_replace('\\','\\\\', $password);
        //$password = str_replace('"','\"', $password);
        //	prevent XSS-attack, Shell-execute and JavaScript execution
        if (preg_match("/cmd|CREATE|DELETE|DROP|eval|EXEC|File|INSERT|printf/i",$password)) {
            $password = '';
        }
        if (preg_match("/LOCK|PROCESSLIST|SELECT|shell|SHOW|SHUTDOWN/i",$password)) {
            $password = '';
        }
        if (preg_match("/SQL|SYSTEM|TRUNCATE|UNION|UPDATE|DUMP/i",$password)) {
            $password = '';
        }
        if (preg_match("/java|vbscri|embed|onclick|onmouseover|onfocus/i",$password)) {
            $password = '';
        }

        //if ($username == $admin && $password == $admin_pw && strlen($password) > 3) {
        if (crypt($username, $admin) == $admin && crypt($password, $admin_pw) ==$admin_pw) {
            $_SESSION['admin']      = $username;
            $_SESSION['admin_pw']   = $password;
        } else {
            $_SESSION['admin']      = '';
            $_SESSION['admin_pw']   = '';
			$password = '';
		}
		//if (strlen($_SESSION['admin']) > 3 && $_SESSION['admin'] == $username && strlen($_SESSION['admin_pw']) > 3 && $_SESSION['admin_pw']== $password  && $_SERVER['REMOTE_ADDR']!="") {
		if (isset($_SESSION['admin']) && isset($_SESSION['admin_pw']) && crypt($_SESSION['admin'], $admin) == $admin && crypt($_SESSION['admin_pw'], $admin_pw) == $admin_pw  || ($_SERVER['REMOTE_ADDR']=="")) {

			@session_regenerate_id();    //  prevent 'session fixation attacks'
			$token = generateToken();
			$fp = @fopen("$tmp_dir/$token1","wb");    //  try to store the first token into .../admin/tmp/token1.txt
			if (!fwrite($fp, $token)) {
				echo "  <br />
							<p class='warnadmin cntr'><br />
							$token1 is not writeable.<br />
							Script execution aborted.
							<br /><br /></p>
						</div>
					  </body>
					</html>
						";
				die();
			}
			@fclose($fp);

			$_POST['token'] = $o_token;

			$tk2 = "passed";
			$fp = @fopen("$tmp_dir/$token2","wb");    //  try to store the second (passed) token into .../admin/tmp/token2.txt
			if (!fwrite($fp, $tk2)) {
				echo "  <br />
							<p class='warnadmin cntr'><br />
							$token2 is not writeable.<br />
							Script execution aborted.
							<br /><br /></p>
						</div>
					  </body>
					</html>
						";
				die();
			}
			@fclose($fp);

			if ($default == 1 ) {
				header("Location: admin.php?default=$default");
			} else {
				header("Location: admin.php?f=98");		//	switch for valid login attempt
			}
		} else {
			//$_POST		= array();	//	reset $_POST array
			$_SESSION	= array();

			header("Location: admin.php?f=99");		//	switch for invalid name and password. Try again
		}
    } else {

        //  for our dear friends on 'Shared Hosting' server, we do not use $_SESSION here,
        //  but save the token in file.
        $token1 = "token1.txt";
		$token = generateToken();
        //      create and test temporary folder
        if (!is_dir($tmp_dir)) {
            mkdir("".$tmp_dir."", 0777);         //if not exist, try to create tmp folder
            if (!is_dir("$tmp_dir")) {
                echo "  <br />
                            <p class='warnadmin cntr'><br />
                            Unable to create folder<br />
                            <span class='blue'> .../admin/".$tmp_dir."</span>.<br />
                            Sphider-plus will not be able to store any file required during index procedure.
                            Script execution aborted.
                            <br /><br /></p>
                        </div>
                      </body>
                    </html>
                        ";
                die();
            }
        }

        $fp = @fopen("$tmp_dir/$token1","wb");    //  try to store the token into .../admin/tmp/token1.txt
        if(!is_writeable("$tmp_dir/$token1")) {
            echo "  <br />
                        <p class='warnadmin cntr'><br />
                        Temporary folder is not writeable.<br />
                        Sphider-plus will not be able to store any file required during index procedure.<br /><br />
                        On *nix operating systems chmod 777 the folder<br />
                        <span class='blue'> .../admin/".$tmp_dir."</span><br />
                        Script execution aborted.
                        <br /><br /></p>
                    </div>
                  </body>
                </html>
                    ";
            die();
        }

        if (!fwrite($fp, $token)) {
            echo "  <br />
                        <p class='warnadmin cntr'><br />
                        $token1 is not writeable.<br />
                        Script execution aborted.
                        <br /><br /></p>
                    </div>
                  </body>
                </html>
                    ";
            die();
        }
        @fclose($fp);
		$_POST['token'] = $token;

?>
      <h1 class='cntr'>Sphider-plus v.<?php echo $plus_nr ?></h1>
      <br /><br />
      <div class='panel x3'>
        <form class='txt' action='auth.php' method='post'>
            <fieldset><legend>[ Sphider-plus Admin Login ]</legend>
                <input type="hidden" name="admin_life" value="<?php echo $admin_life ?>"/>
                <input type="hidden" name="token" value="<?php echo $token ?>"/>
                <label for='user'>[ Name ]</label>
                <input type='text' name='user' id='user' size='15' maxlength='255' title='Required - Enter your user name here' onfocus='this.value=\"\"' value=''/>
                <label for='pass'>[ Password ]</label>
                <input type='password' name='pass' id='pass' size='15' maxlength='255' title='Required - Enter your password here' onfocus='this.value=\"\"' value=''/>
            </fieldset>
            <fieldset><legend>[ Log In ]</legend>
                <input class='sbmt' type='submit' id='submit' value='&nbsp;Login &raquo;&raquo; ' title='Click to confirm'/>
            </fieldset>
        </form>
      </div>
      <br /><br />
  </body>
</html>
<?php
        exit();
    }

    function generateToken() {
        return md5(md5(uniqid().uniqid().mt_rand()));
    }

     // Database 1-5 connection
    function auth_db_connect($mysql_host, $mysql_user, $mysql_password, $database) {
        $db_con = '';
        $db_con = @new mysqli($mysql_host, $mysql_user, $mysql_password, $database);
        /* check connection */
/*
        if ($db_con->connect_errno) {
            echo "<p>&nbsp;</p>
            <p><p class='warnadmin cntr'><br />&nbsp;No valid database found to start up.<br />&nbsp;Configure at least one database.<br /><br />
            <p>&nbsp;</p>
            ";

        }
*/
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
