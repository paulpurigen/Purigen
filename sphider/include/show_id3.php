<?php
    error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED ^ E_STRICT);

    $include_dir    = "./include";
    $template_dir 	= "../templates";
    $settings_dir 	= "../settings";
    $language_dir 	= "../languages";

    $prefix         = '';

    if (!defined("_SECURE")) {
        define("_SECURE",1);    // define secure constant
    }

    require_once("$settings_dir/database.php");
    include ("commonfuncs.php");

    $media_id   = trim(substr ($_GET['media_id'], 0, 6));
    $db         = trim(substr ($_GET['db'], 0, 1));
    $prefix     = trim(substr ($_GET['prefix'], 0, 20));

    //      if requested by Search-form, overwrite default db number
    if ($db > 0 && $db <= 5) {
        $dbu_act = $db;
    }

    //      if requested by Search-form, overwrite default table prefix
    if ($prefix != 0 ) {
        $mysql_table_prefix = $prefix;
    }

    //      get active database
    if ($dbu_act == '1') {
        $db_con = db_connect($mysql_host1, $mysql_user1, $mysql_password1, $database1);
        $mysql_table_prefix = $mysql_table_prefix1;
    }

    if ($dbu_act == '2') {
        $db_con = db_connect($mysql_host2, $mysql_user2, $mysql_password2, $database2);
        $mysql_table_prefix = $mysql_table_prefix2;
    }

    if ($dbu_act == '3') {
        $db_con = db_connect($mysql_host3, $mysql_user3, $mysql_password3, $database3);
        $mysql_table_prefix = $mysql_table_prefix3;
    }

    if ($dbu_act == '4') {
        $db_con = db_connect($mysql_host4, $mysql_user4, $mysql_password4, $database4);
        $mysql_table_prefix = $mysql_table_prefix4;
    }

    if ($dbu_act == '5') {
        $db_con = db_connect($mysql_host5, $mysql_user5, $mysql_password5, $database5);
        $mysql_table_prefix = $mysql_table_prefix5;
    }

    //      if requested by Search-form, overwrite default table prefix
    if ($prefix) {
        $mysql_table_prefix = $prefix;
    }

    $plus_nr = '';
    @include "".$settings_dir."/db".$dbu_act."/conf_".$mysql_table_prefix.".php";
    if (!$plus_nr) {
        include "/admin/settings/backup/Sphider-plus_default-configuration.php";
    }

    if ($debug == '0') {
        if (function_exists("ini_set")) {
            ini_set("display_errors", "0");
        }
        error_reporting(0);  //     suppress  PHP messages
    }

    require_once    ("$language_dir/en-language.php");

    if ($auto_lng == 1) {   //  if enabled in Admin settings get country code of calling client
        if ( isset ( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
            $cc = substr( htmlspecialchars($_SERVER['HTTP_ACCEPT_LANGUAGE']), 0, 2);
            $handle = @fopen ("$language_dir/$cc-language.php","r");
            if ($handle) {
                $language = $cc; // if available set language to users slang
            }
            else {
                include "$language_dir/$language-language.php";
            }
            @fclose($handle);
        }
        else {
            include "$language_dir/$language-language.php";
        }
    }
    require_once    ("$language_dir/$language-language.php");

    // HTML5 header";
    echo "<!DOCTYPE HTML>\n";
    echo "  <head>\n";
    echo "      <title>Sphider-plus administrator</title>\n";
    // meta data
    echo "      <meta charset='UTF-8'>\n";
    echo "      <meta name='public' content='all'>\n";
    echo "      <meta http-equiv='expires' content='0'>\n";
    echo "      <meta http-equiv='pragma' content='no-cache'>\n";
    echo "      <meta http-equiv='X-UA-Compatible' content='IE=9' />\n";

    echo "      <link href='$template_url/html/sphider-plus.ico' rel='shortcut icon' type='image/x-icon' />
      <title>$mytitle. Media Info</title>
      <link rel='stylesheet' type='text/css' href='$template_url/$template/userstyle.css' />
      <script type='text/javascript' src='dbase.js'></script>
  </head>
  <body>
    <a name=\"head\"></a>
    <div id='main'>
        <h1 class='cntr sml'>Sphider-plus v. $plus_nr</h1>
";

    //      Get all information about current media object
    $sql_query  = "SELECT * from ".$mysql_table_prefix."media where media_id like '$media_id'";
    $res    = $db_con->query($sql_query);
    if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-5;
                mysql_fault($db_con, $sql_query, $file, $function, $err_row);
    }


    $num_rows = $res->num_rows;
    if ($num_rows <> '1') {   //      Nothing valid found
        echo "  <br />
                <br />
                <div class='cntr tblhead warnadmin' >
                    No ID3 / EXIF info found for this media object
                </div>
        ";
        include "".$template_dir."/html/092_footer.html" ;
        die('');
    }

    $media_info = $res->fetch_array(MYSQLI_NUM) ;

    if (strpos($media_info[2], "youtube") && $media_info [6] == "video") {
        $medianame = str_replace("%20", ' ', substr($media_info[3], strrpos($media_info[3], '/')));
    } else {
        $medianame = str_replace("%20", ' ', substr($media_info[3], strrpos($media_info[3], '/')+1));
    }
    $id3 =  array();
    $id3_array = explode("<br />",$media_info[12]);   //  separate ID3 and EXIF data
    $class = "evrow";
    echo "        <div class='cntr'>
                    <p class='headline cntr sml'>Media Info (ID3 + EXIF) ".$sph_messages['for'].":</p>
                    <p class='headline cntr'>$medianame</p>
            ";
    if ($media_info[12][0] == '') {   //      No ID3 tag indexed for this object
        echo "
                        <br /><br />
                        <div class='cntr tblhead warn sml' >
                            <br />".$sph_messages['noEXIF']."
                            <br /><br />
                        </div>
                        <br>
                        <form class='left'>
                            <input type='submit' value='".$sph_messages['closewin']."' 'title='Return' onclick='window.close()'>
                        </form>
                    </body>
                </html>
            ";
        die('');
    }

    echo "          <table class='left'>
                        <tr>
                            <td class='tblhead sml'>Section</td>
                            <td class='tblhead sml'>Name</td>
                            <td class='tblhead sml'>Value <a class='navdown' href='#bottom' title='Jump to Page Bottom'>Down</a></td>
                        </tr>
        ";

    foreach ($id3_array as $data) {
        preg_match_all("/(.*?)>>(.*?);;(.*?)$/si", $data, $tags, PREG_SET_ORDER);
        foreach ($tags as $meta ) {

            if ($class =="evrow")
            $class = "odrow";
            else
            $class = "evrow";

            echo "                <tr class='$class left sml'>
                            <td>$meta[1]</td>
                            <td>&nbsp;&nbsp;$meta[2]</td>
                            <td>&nbsp;&nbsp;&nbsp;&nbsp;$meta[3]</td>
                        </tr>
        ";
        }
    }
    echo "          </table>
                    <a class='navup' href='#head' title='Jump to Page Top'>Top</a>
                    <br />
                    <form class='cntr' action=''>
                        <input type='submit' value='".$sph_messages['closewin']."' title='Return' onclick='window.close()' />
                    </form>
                </div>
            </div>
            <a name=\"bottom\"></a>
            <p class='stats cntr'><a href='http://www.sphider-plus.eu' title='Link: Visit Sphider-plus site in new window' target='rel'>Visit&nbsp;<img class='mid' src='images/sphider-plus-logo.gif' alt='Visit Sphider site in new window' height='39' width='42' /> Sphider-plus</a></p>
            <br />
        </body>
    </html>
        ";

?>