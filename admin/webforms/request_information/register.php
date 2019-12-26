<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/webforms/request_information/list.php";
$PageCode = "010201";
$webhelper->CheckAdminLogin($PageCode, urlencode($PageUrl), true);

//Parameter
$firstname = $webhelper->RequestFilter("firstname", 20, false);
$lastname = $webhelper->RequestFilter("lastname", 20, false);
$email = $webhelper->RequestFilter("email", 50, false);
$phone = $webhelper->RequestFilter("phone", 50, false);
$organization = $webhelper->RequestFilter("organization", 100, false);
$job_title = $webhelper->RequestFilter("job_title", 100, false);
$country = $webhelper->RequestFilter("country", 50, false);
$state = $webhelper->RequestFilter("state", 50, false);
$main_application = $webhelper->RequestFilter("main_application", 100, false);
$main_product = $webhelper->RequestFilter("main_product", 100, false);
$purchase_timeline = $webhelper->RequestFilter("purchase_timeline", 100, false);
$isreceive = $webhelper->RequestFilter("isreceive", 0, false);
$note = $webhelper->RequestFilter("note", -1, true);

//check parameter
if($webhelper->isNull($firstname)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($lastname)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($email)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($phone)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($organization)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($job_title)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($country)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($state)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($main_application)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($main_product)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($purchase_timeline)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($isreceive)) $isreceive = 0;
if($webhelper->isNull($note)) $webhelper->AlertMessage("Error. Please try again.");

$dbhelper->dbOpen();
$sql = "insert into request_information(firstname, lastname, email, phone, organization, job_title, country, state, main_application, main_product, purchase_timeline, isreceive, note)
        values ('$firstname', '$lastname', '$email', '$phone', '$organization', '$job_title', '$country', '$state', '$main_application', '$main_product', '$purchase_timeline', '$isreceive', '$note')";
$dbhelper->RunSQL($sql);
$dbhelper->dbClose();

$webhelper->AlertMessageAndGo("", "/admin/webforms/request_information/list.php");
?>