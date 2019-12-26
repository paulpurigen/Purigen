<?php

    error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE & ~E_STRICT);

    if (!defined("_SECURE")) {
        define("_SECURE",1);    // define secure constant
    }

    $f          = '';
    $switch     = '';
    $ad_user    = '';
    $ad_pass    = '';
    $db_user    = '';
    $db_pass    = '';
    $result     = '';

    require_once "admin_header.php";     //  display header

    $auth_file = "settings/authentication.php";
    require_once $auth_file;   //  contains all usernames and passwords

    // if Intrusion Detection System should be used
    if ($use_ids == 1){
        require_once ("$include_dir/ids_handler.php");

        //IDS detected an attack?
        if (strlen($result) > 13) {
            //  get impact of intrusion
            $len = strpos($result, "<")-13;
            $res = trim(substr($result, '13', $len));

            if ($res >= $ids_stop) {

                // HTML5 header";
                echo "<!DOCTYPE HTML>\n";
                echo "  <head>\n";
                echo "      <title>Sphider-plus administrator authentication-settings</title>\n";
                // meta data
                echo "      <meta charset='UTF-8'>\n";
                echo "      <meta name='public' content='all'>\n";
                echo "      <meta http-equiv='expires' content='0'>\n";
                echo "      <meta http-equiv='pragma' content='no-cache'>\n";
                echo "      <meta http-equiv='X-UA-Compatible' content='IE=9' />\n";

                echo "      <link href='$template_url/html/sphider-plus.ico' rel='shortcut icon' type='image/x-icon' />\n";
                echo "      <link rel='stylesheet' type='text/css' href='$template_url/$template_path/adminstyle.css' />\n";
                echo "      <script type='text/javascript' src='confirm.js'></script>
                    <script type='text/javascript'>
                    function JumpBottom() {
                        window.scrollTo(0,1000);
                    }
                    </script>\n";
                echo "  </head>\n";
                echo "  <body>
            <br /><br />
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

    if (!session_id()) {
        session_start();
    }

    //  get all variables for this script
    if (isset($_GET['switch']))
    $switch     = cleaninput(substr(trim($_GET['switch']),0,255));
    if (isset($_GET['ad_user']))
    $ad_user    = cleaninput(substr(trim($_GET['ad_user']),0,255));
    if (isset($_GET['ad_pass']))
    $ad_pass    = cleaninput(substr(trim($_GET['ad_pass']),0,255));
    if (isset($_GET['db_user']))
    $db_user    = cleaninput(substr(trim($_GET['db_user']),0,255));
    if (isset($_GET['db_pass']))
    $db_pass    = cleaninput(substr(trim($_GET['db_pass']),0,255));

    $site_funcs     = Array (22=> "default",21=> "default",4=> "default", 19=> "default", 1=> "default", 2 => "default", "add_site" => "default", 20=> "default", 28=> "default", 30=> "default", 40=> "default", 45=> "default", 50=> "default", 51=> "default", "edit_site" => "default", 5=>"default");
    $stat_funcs     = Array ("statistics" => "default",  "delete_log"=> "default");
    $settings_funcs = Array ("settings" => "default", 41=> "default");
    $index_funcs    = Array ("index" => "default");
    $clean_funcs    = Array ("clean" => "default", 15=>"default", 16=>"default", 17=>"default", 23=>"default");
    $cat_funcs      = Array (11=> "default", 10=> "default", "categories" => "default", "edit_cat"=>"default", "delete_cat"=>"default", "add_cat" => "default", 7=> "default");
    $database_funcs = Array ("database" => "default");

    echo "    <div id='tabs'>
              <ul>
        ";

    if ($stat_funcs[$f] ) {
        $stat_funcs[$f] = "selected";
    } else {
        $stat_funcs[$f] = "default";
    }

    if ($site_funcs[$f] ) {
        $site_funcs[$f] = "selected";
    }else {
        $site_funcs[$f] = "default";
    }

    if ($settings_funcs[$f] ) {
        $settings_funcs[$f] = "selected";
    } else {
        $settings_funcs[$f] = "default";
    }

    if ($index_funcs[$f] ) {
        $index_funcs[$f]  = "selected";
    } else {
        $index_funcs[$f] = "default";
    }

    if ($cat_funcs[$f] ) {
        $cat_funcs[$f]  = "selected";
    } else {
        $cat_funcs[$f] = "default";
    }

    if ($clean_funcs[$f] ) {
        $clean_funcs[$f]  = "selected";
    } else {
        $clean_funcs[$f] = "default";
    }

    if ($database_funcs[$f] ) {
        $database_funcs[$f]  = "selected";
    } else {
        $database_funcs[$f] = "default";
    }
    $settings_funcs[$f] = "selected";

    echo "        <li><a title='Manage Sites' href='admin.php?f=2' class='$site_funcs[$f]'>Sites</a></li>
                <li><a title='Manage Categories' href='admin.php?f=categories' class='$cat_funcs[$f]'>Categories</a></li>
                <li><a title='Indexing Options' href='admin.php?f=index' class='$index_funcs[$f]'>Index</a></li>
                <li><a title='Main Settings' href='admin.php?f=settings' class='$settings_funcs[$f]'>Settings</a></li>
                <li><a  name='head' title='Indexing Statistics' href='admin.php?f=statistics' class='$stat_funcs[$f]'>Statistics</a> </li>
                <li><a title='Memory and Database Cleaning Options' href='admin.php?f=clean' class='$clean_funcs[$f]'>Clean</a> </li>
                <li><a title='Display Database Contents' href='admin.php?f=database&amp;sel=1' class='$database_funcs[$f]'>Database</a></li>
                <li><a title='Close Admin backend' href='admin.php?f=24' class='default'>Log out</a></li>
              </ul>
            </div>
            <div id='main'>
            ";

    echo "    <br /><br />
                <fieldset><legend>[ Define authentication for admin backend and database configuration ]</legend><br />
            ";

    //  Admin authent here
    if ($switch != "current_db" && $switch != "new_db") {
        //  store new admin authentication
        if ($switch == "new_admin") {
            $fhandle = fopen($auth_file, "wb");
            fwrite($fhandle,"<?php \n");
            fwrite($fhandle,"/************************************************\n ");
            fwrite($fhandle,"Sphider-plus version $plus_nr authentication file.\n");
            fwrite($fhandle,"\n > > >  DO NOT EDIT THIS FILE. < < < \n\n");
            fwrite($fhandle,"Any modification must be done by Admin settings. \n");
            fwrite($fhandle,"*************************************************/");
            fwrite($fhandle,"\n\n/******************************* Check for forbidden direct access ************************************/\n\n");
            fwrite($fhandle,"    if (!defined('_SECURE')) die (\"No direct access to authentication file\");");
            fwrite($fhandle,"\n\n/*********************** \nAdmin access \n***********************/\n");
            fwrite($fhandle,"   $"."admin       = '".crypt($ad_user). "';\n");
            fwrite($fhandle,"   $"."admin_pw    = '".crypt($ad_pass). "';");
            fwrite($fhandle,"\n\n/*********************** \nDatabase access \n***********************/\n");
            fwrite($fhandle,"   $"."db_admin    = '".$db_admin. "';\n");
            fwrite($fhandle,"   $"."db_admin_pw = '".$db_admin_pw. "';");
            fwrite($fhandle,"\n\n?>");
            fclose($fhandle);
            echo "
                    <br />
                    <h1>New username and password for Admin backend stored</h1>
                ";
        } else {
            //if ($ad_user == $admin && $ad_pass == $admin_pw) {
			if (crypt($ad_user, $admin) == $admin && crypt($ad_pass, $admin_pw) == $admin_pw) {
                echo "        <h1>Now enter new username and password for admin backend</h1>
                    <br />
                    <div class='panel x3'>
                        <form class='txt' name='form10' method='get' action='new_authent.php'>
                            <fieldset><legend>[ Admin login ]</legend>
                                <label for='user'>[ Name ]</label>
                                <input type='text' name='ad_user' id='ad_user' size='25' maxlength='255' title='Required - Enter your user name here' onfocus='this.value=\"\"' value=''/>
                                <label for='pass'>[ Password ]</label>
                                <input type='password' name='ad_pass' id='ad_pass' size='25' maxlength='255' title='Required - Enter your password here' onfocus='this.value=\"\"' value=''/>
                                <br /><br />
                                <input class='sbmt' type='submit' value='Save' id='submit10' title='Click once to save these settings'/>
                                <input type=\"hidden\" name=\"switch\" value=\"new_admin\" />
                                <br /><br />
                             </fieldset>
                        </form>
                    </div>
                    <br /><br />
                    ";
            } else {
                 echo "        <h1>Enter current username and password for admin backend</h1>
                    <br />
                    <div class='panel x3'>
                        <form class='txt' name='form10' method='get' action='new_authent.php'>
                            <fieldset><legend>[ Admin login ]</legend>
                                <label for='user'>[ Name ]</label>
                                <input type='text' name='ad_user' id='ad_user' size='25' maxlength='255' title='Required - Enter your user name here' onfocus='this.value=\"\"' value=''/>
                                <label for='pass'>[ Password ]</label>
                                <input type='password' name='ad_pass' id='ad_pass' size='25' maxlength='255' title='Required - Enter your password here' onfocus='this.value=\"\"' value=''/>
                                <br /><br />
                                <input class='sbmt' type='submit' value='Send' id='submit10' title='Click once to save these settings'/>
                                <input type=\"hidden\" name=\"switch\" value=\"current_admin\" />
                                <br /><br />
                             </fieldset>
                        </form>
                    </div>
                    <br /><br />
                    ";
            }
        }
    }

    //  database authent here
    if ($switch != "current_admin" && $switch != "new_admin") {
        //  store new db authentication
        if ($switch == "new_db") {
            $fhandle = fopen($auth_file, "wb");
            fwrite($fhandle,"<?php \n");
            fwrite($fhandle,"/************************************************\n ");
            fwrite($fhandle,"Sphider-plus version $plus_nr authentication file.\n");
            fwrite($fhandle,"\n > > >  DO NOT EDIT THIS FILE. < < < \n\n");
            fwrite($fhandle,"Any modification must be done by Admin settings. \n");
            fwrite($fhandle,"*************************************************/");
            fwrite($fhandle,"\n\n/******************************* Check for forbidden direct access ************************************/\n\n");
            fwrite($fhandle,"    if (!defined('_SECURE')) die (\"No direct access to authentication file\");");
            fwrite($fhandle,"\n\n/*********************** \nAdmin access \n***********************/\n");
            fwrite($fhandle,"   $"."admin       = '".$admin. "';\n");
            fwrite($fhandle,"   $"."admin_pw    = '".$admin_pw. "';");
            fwrite($fhandle,"\n\n/*********************** \nDatabase access \n***********************/\n");
            fwrite($fhandle,"   $"."db_admin    = '".crypt($db_user). "';\n");
            fwrite($fhandle,"   $"."db_admin_pw = '".crypt($db_pass). "';");
            fwrite($fhandle,"\n\n?>");
            fclose($fhandle);

            echo "
                    <br />
                    <h1>New username and password for database configuration stored</h1>
            ";
        } else {
            if (crypt($db_user, $db_admin) == $db_admin && crypt($db_pass, $db_admin_pw) == $db_admin_pw) {
                echo "            <h1>Now enter new username and password for database configuration</h1>
                        <br />
                        <div class='panel x3'>
                            <form class='txt' name='form11' method='get' action='new_authent.php'>
                                <fieldset><legend>[ Database configuration login ]</legend>
                                    <label for='user'>[ Name ]</label>
                                    <input type='text' name='db_user' id='ad_user' size='25' maxlength='255' title='Required - Enter your user name here' onfocus='this.value=\"\"' value=''/>
                                    <label for='pass'>[ Password ]</label>
                                    <input type='password' name='db_pass' id='ad_pass' size='25' maxlength='255' title='Required - Enter your password here' onfocus='this.value=\"\"' value=''/>
                                    <br /><br />
                                    <input class='sbmt' type='submit' value='Send' id='submit11' title='Click once to save these settings'/>
                                    <input type=\"hidden\" name=\"switch\" value=\"new_db\" />
                                    <br /><br />
                                 </fieldset>
                            </form>
                        </div>
                    ";
            } else {
                echo "<h1>Enter current username and password for database configuration</h1>
                    <br />
                    <div class='panel x3'>
                        <form class='txt' name='form11' method='get' action='new_authent.php'>
                            <fieldset><legend>[ Database configuration login ]</legend>
                                <label for='user'>[ Name ]</label>
                                <input type='text' name='db_user' id='ad_user' size='25' maxlength='255' title='Required - Enter your user name here' onfocus='this.value=\"\"' value=''/>
                                <label for='pass'>[ Password ]</label>
                                <input type='password' name='db_pass' id='ad_pass' size='25' maxlength='255' title='Required - Enter your password here' onfocus='this.value=\"\"' value=''/>
                                <br /><br />
                                <input class='sbmt' type='submit' value='Send' id='submit11' title='Click once to save these settings'/>
                                <input type=\"hidden\" name=\"switch\" value=\"current_db\" />
                                <br /><br />
                             </fieldset>
                        </form>
                    </div>
                    ";
            }
        }
    }
    echo "<br /><br />
                </fieldset>
            </div>
        </body>
    </html>
    ";
?>