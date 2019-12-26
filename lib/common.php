<?php

    //Basic Include
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
    ini_set("display_errors", 0);
    header("Pragma: no-cache");
    header("Cache: no-cache");
    
    include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/dbHelper.php');
    include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/RowHelper.php');
    include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/PageHelper.php');
    include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/WebHelper.php');
?>
