<?php

    error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE & ~E_STRICT);

    //  For command line operation, try to correct the working directory
    $dir0 = str_replace('\\', '/', __DIR__);
    chdir($dir0);
    // now get the actual directory
    $dir = str_replace('\\', '/', getcwd());

    if (!strpos($dir, "/admin")) {
        echo "Unable to set the working directory to Sphider-plus installation folder. Script execution aborted.";
        die ('');
    }

    if (!defined("_SECURE")) {
        define("_SECURE",1);    // define secure constant
    }

    //  get the root folder of this Sphider-plus installation
    $inst_dir       = substr($dir0, 0, strpos($dir0, "/admin"));

    $include_dir    = "$inst_dir/include";
    $settings_dir 	= "$inst_dir/settings";
    $language_dir 	= "$inst_dir/languages";

    require_once ("$settings_dir/database.php");
    require_once ("$include_dir/commonfuncs.php");
    require_once ("$language_dir/en-language.php");

    $ip                 = trim(substr ($_GET['ip'], 0, 20));
    $dba_act            = trim(substr ($_GET['dba_act'], 0, 1));
    $mysql_table_prefix = trim(substr ($_GET['mysql_table_prefix'], 0, 20));

    //$ip = "222.46.18.34";  // just for tests

    //      get active database
    if ($dba_act == '1') {
        $db_con = db_connect($mysql_host1, $mysql_user1, $mysql_password1, $database1);
        $mysql_table_prefix = $mysql_table_prefix1;
    }

    if ($dba_act == '2') {
        $db_con = db_connect($mysql_host2, $mysql_user2, $mysql_password2, $database2);
        $mysql_table_prefix = $mysql_table_prefix2;
    }

    if ($dba_act == '3') {
        $db_con = db_connect($mysql_host3, $mysql_user3, $mysql_password3, $database3);
        $mysql_table_prefix = $mysql_table_prefix3;
    }

    if ($dba_act == '4') {
        $db_con = db_connect($mysql_host4, $mysql_user4, $mysql_password4, $database4);
        $mysql_table_prefix = $mysql_table_prefix4;
    }

    if ($dba_act == '5') {
        $db_con = db_connect($mysql_host5, $mysql_user5, $mysql_password5, $database5);
        $mysql_table_prefix = $mysql_table_prefix5;
    }

    $plus_nr = '';
    @include "".$settings_dir."/db".$dba_act."/conf_".$mysql_table_prefix.".php";
    if (!$plus_nr) {
        include "/settings/backup/Sphider-plus_default-configuration.php";
    }

	$all = ipgeo($ip);
//$line = __LINE__;echo "\r\n<br>$line all Array:<br><pre>";print_r($all);echo "</pre>";

    $cc = $all['geoplugin_countryCode'];
    $cN = $all['geoplugin_countryName'];
    $rC = $all['geoplugin_regionCode'];
    $rN = $all['geoplugin_regionName'];
    $ct = $all['geoplugin_city'];
    $tc = $all['geoplugin_timezone'];
    $la = $all['geoplugin_latitude'];
    $lo = $all['geoplugin_longitude'];
    $kc = $all['geoplugin_continentCode'];
    $kn = $all['geoplugin_continentName'];
    $cu = $all['geoplugin_currencyCode'];
    $cS = $all['geoplugin_currencySymbol_UTF8'];


    echo "<!DOCTYPE HTML>\n";
    echo "  <head>\n";
    echo "      <title>Sphider-plus administrator</title>\n";
    // meta data
    echo "      <meta charset='UTF-8'>\n";
    echo "      <meta name='public' content='all'>\n";
    echo "      <meta http-equiv='expires' content='0'>\n";
    echo "      <meta http-equiv='pragma' content='no-cache'>\n";
    echo "      <meta http-equiv='X-UA-Compatible' content='IE=9' />\n";

    echo "      <link href='$template_url/html/sphider-plus.ico' rel='shortcut icon' type='image/x-icon' />\n";
    echo "      <link rel='stylesheet' type='text/css' href='$template_url/$template/adminstyle.css' />\n";
    echo "      <script type='text/javascript' src='confirm.js'></script>
      <script type='text/javascript'>
          function JumpBottom() {
              window.scrollTo(0,1000);
          }
      </script>\n";
    echo "  </head>\n";
    echo "  <body>
        <a name=\"head\"></a>
        <h1 class='cntr sml'>Sphider-plus v. $plus_nr</h1>

        <div class='cntr'>
            <p class='headline cntr'>IP geo info</p>
            <br />

            <table class='left' width='50%'>
                <tr>
                    <td class='tblhead sml'>Section</td>
                    <td class='tblhead sml'>Value</td>
                </tr>
                <tr  class='evrow'>
                    <td>&nbsp;&nbsp;IP:</td>
                    <td>&nbsp;&nbsp;$ip</td>
                </tr>
                <tr  class='odrow'>
                    <td>&nbsp;&nbsp;Continent:</td>
                    <td>&nbsp;&nbsp;$kc</td>
                </tr>
                <tr  class='evrow'>
                    <td>&nbsp;&nbsp;Country code:</td>
                    <td>&nbsp;&nbsp;$cc</td>
                </tr>
                <tr  class='odrow'>
                    <td>&nbsp;&nbsp;Country name:</td>
                    <td>&nbsp;&nbsp;$cN</td>
                </tr>
                <tr  class='evrow'>
                    <td>&nbsp;&nbsp;Region code:</td>
                    <td>&nbsp;&nbsp;$rC</td>
                </tr>
                <tr  class='odrow'>
                    <td>&nbsp;&nbsp;Region name:</td>
                    <td>&nbsp;&nbsp;$rN</td>
                </tr>
                <tr  class='evrow'>
                    <td>&nbsp;&nbsp;City:</td>
                    <td>&nbsp;&nbsp;$ct</td>
                </tr>
                <tr  class='odrow'>
                    <td>&nbsp;&nbsp;Time zone:</td>
                    <td>&nbsp;&nbsp;$tc</td>
                </tr>
                <tr  class='evrow'>
                    <td>&nbsp;&nbsp;Latitude:</td>
                    <td>&nbsp;&nbsp;$la</td>
                </tr>
                <tr  class='odrow'>
                    <td>&nbsp;&nbsp;Longitude:</td>
                    <td>&nbsp;&nbsp;$lo</td>
                </tr>
                <tr  class='evrow'>
                    <td>&nbsp;&nbsp;Currency:</td>
                    <td>&nbsp;&nbsp;$cu</td>
                </tr>
                <tr  class='odrow'>
                    <td>&nbsp;&nbsp;Currency symbol:</td>
                    <td>&nbsp;&nbsp;$cS</td>
                </tr>
                <tr  class='evrow'>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <tr  class='odrow'>
                    <td><a href='https://whatismyipaddress.com/ip/$ip' target='_blank' title='Open details in new window'>Even more details</a></td>
                    <td>Incl. Google Maps, black list check, etc.</td>
                </tr>
            </table>
            <br><br />
        </div>


        <br />
        <br />
        <br />




        <form id='killme' class='cntr'>
            <input type='submit' value='".$sph_messages['closewin']."' 'title='Return' onclick='window.close()'>
        </form>
        <br />
    </body>
</html>
            ";
?>