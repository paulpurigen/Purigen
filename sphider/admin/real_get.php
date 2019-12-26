<?php
/***********************************************************
 If Real-time Logging is enabled, this script delivers refresh rate and
 latest logging data, requested from the JavaScript file 'real_ping.js'.
 Also reset of the real_log table is performed.
 This is the server-side part of the AJAX function.
 ***********************************************************/

    error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE & ~E_STRICT);

    // make sure that user's browser doesn't cache the results
    header('Expires: Wed, 23 Dec 1980 00:30:00 GMT');   // time in the past
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    if (!defined("_SECURE")) {
        define("_SECURE",1);    // define secure constant
    }

    //  For command line operation, try to correct the working directory
    $dir0 = str_replace('\\', '/', __DIR__);
    chdir($dir0);
    // now get the actual directory
    $dir = str_replace('\\', '/', getcwd());

    if (!strpos($dir, "/admin")) {
        echo "Unable to set the working directory to Sphider-plus installation folder. Indexation aborted.";
        die ('');
    }

    //  get the root folder of this Sphider-plus installation
    $inst_dir       = substr($dir0, 0, strpos($dir0, "/admin"));

    $include_dir    = "$inst_dir/include";
    $settings_dir   = "$inst_dir/settings";

    include "$settings_dir/database.php";
    include "$include_dir/commonfuncs.php";

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

    if ($debug == '0') {
        error_reporting(0);  //     suppress  PHP messages
    }

    set_time_limit (0);
    $action = '';

    $action = $_GET['action'];                          // what to do now?
    $action = substr(cleaninput($action), '0', '6');    // clean input as it comes from a far away client

    if ($action == 'GetLog') {                          //  enter here for fresh log info
        $sql_query = "SELECT real_log from ".$mysql_table_prefix."real_log  LIMIT 1";
        $result = $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }
        $sql_query = "UPDATE ".$mysql_table_prefix."real_log set `real_log`='' LIMIT 1";
        $db_con->query ($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }

        $log        = $result->fetch_array(MYSQLI_NUM);
        $log_data   = $db_con->real_escape_string($log[0]);   //  get actual real-log info and clean data
        $real_buf   = "<p class='evrow'>$log_data";

        echo $real_buf;     //    this is taken by theJavaScript file 'real_ping.js
        unset ($real_buf);

    }
    elseif ($action == 'Ready')         //      enter here to catch refresh rate
    {
        $sql_query = "SELECT refresh from ".$mysql_table_prefix."real_log  LIMIT 1";
        $result = $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }

        $rate_arr   = $result->fetch_array(MYSQLI_NUM);
        $rate       = $db_con->real_escape_string($rate_arr[0]);   //  get actual real-log info and clean data

        echo $rate;
    } else {
        echo "
                Error talking to the server. Transferred action: '$action'.
            ";
    }

?>
