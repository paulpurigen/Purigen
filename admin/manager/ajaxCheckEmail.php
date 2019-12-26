<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/";
$PageCode = "0000";
$webhelper->CheckAdminLogin($PageCode, $PageUrl, true);

//Parameter
$email = $webhelper->RequestFilter("email", 50, false);
if($webhelper->isNull($email))
    $webhelper->AlertMessage ("Error. Please try again.");


$dbhelper->dbOpen();
$sql = "select pkid from admin where email = '" . $email . "'";
$ViewData = $dbhelper->RunSQLReturnOneRow($sql);
$dbhelper->dbClose();

if($ViewData == null || $ViewData["pkid"] == "")
    echo 'T';
else
    echo 'F';
?>