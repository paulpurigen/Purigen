<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/manager/list.php";
$PageCode = "010101";
$webhelper->CheckAdminLogin($PageCode, urlencode($PageUrl), true);

//Parameter
$id = $webhelper->RequestFilter("id", 20, false);
$email = $webhelper->RequestFilter("email", 50, false);
$passwd = $webhelper->RequestFilter("pass1", 50, false);
$status = $webhelper->RequestFilter("status", 0, false);

$isAdministrator = $webhelper->RequestFilter("isAdministrator", 0, false);
$isEditor = $webhelper->RequestFilter("isEditor", 0, false);
$isCareers = $webhelper->RequestFilter("isCareers", 0, false);

$firstname = $webhelper->RequestFilter("firstname", 20, false);
$lastname = $webhelper->RequestFilter("lastname", 20, false);
$company = $webhelper->RequestFilter("company", 100, false);
$country = $webhelper->RequestFilter("country", 100, false);
$providence = $webhelper->RequestFilter("providence", 100, false);
$city = $webhelper->RequestFilter("city", 100, false);
$postcode = $webhelper->RequestFilter("postcode", 50, false);
$phone = $webhelper->RequestFilter("phone", 50, false);

//check parameter
if($webhelper->isNull($id)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($email)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($passwd)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($status)) $status = 1;
if($webhelper->isNull($isAdministrator)) $isAdministrator = 0;
if($webhelper->isNull($isEditor)) $isEditor = 0;
if($webhelper->isNull($isCareers)) $isCareers = 0;

if($webhelper->isNull($firstname)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($lastname)) $webhelper->AlertMessage("Error. Please try again.");

$dbhelper->dbOpen();
$sql = "insert into admin( id, pass, email, status, isAdministrator, isEditor, isCareers, firstname, lastname, " .
        "company, country, providence, city, postcode, phone ) values ( '$id', '$passwd', '$email', '$status', '$isAdministrator', '$isEditor', '$isCareers', '$firstname', '$lastname', " .
        "'$company', '$country', '$providence', '$city', '$postcode', '$phone')";
$dbhelper->RunSQL($sql);
$dbhelper->dbClose();

$webhelper->AlertMessageAndGo("", "/admin/manager/list.php");
?>