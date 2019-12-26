<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/webforms/request_information/list.php";
$PageCode = "010201";
$webhelper->CheckAdminLogin($PageCode, urlencode($PageUrl), true);

//Parameter
$pkid = $webhelper->RequestFilter("pkid", 0, false);
$page = $webhelper->RequestFilter("page", 0, false);
$searchKey = $webhelper->RequestFilter("searchKey", -1, false);
$order = $webhelper->RequestFilter("order", 0, false);

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
if($webhelper->isNull($firstname)) $webhelper->AlertMessage("Error. Please try again.1");
if($webhelper->isNull($lastname)) $webhelper->AlertMessage("Error. Please try again.2");
if($webhelper->isNull($email)) $webhelper->AlertMessage("Error. Please try again.3");
if($webhelper->isNull($phone)) $webhelper->AlertMessage("Error. Please try again.4");
if($webhelper->isNull($organization)) $webhelper->AlertMessage("Error. Please try again.5");
if($webhelper->isNull($job_title)) $webhelper->AlertMessage("Error. Please try again.6");
if($webhelper->isNull($country)) $webhelper->AlertMessage("Error. Please try again.7");
if($webhelper->isNull($state)) $webhelper->AlertMessage("Error. Please try again.8");
if($webhelper->isNull($main_application)) $webhelper->AlertMessage("Error. Please try again.9");
if($webhelper->isNull($main_product)) $webhelper->AlertMessage("Error. Please try again.10");
if($webhelper->isNull($purchase_timeline)) $webhelper->AlertMessage("Error. Please try again.11");
if($webhelper->isNull($isreceive)) $isreceive = 0;
if($webhelper->isNull($note)) $webhelper->AlertMessage("Error. Please try again.12");

if($webhelper->isNull($pkid)) $webhelper->AlertMessage("Error. Please try again.13");

if($webhelper->isNull($page)) $page = 1;
if($webhelper->isNull($searchKey)) $searchKey = "";
if($webhelper->isNull($order)) $order = 0;

$Parameter = "?page=$page&searchKey=$searchKey&order=$order";

$dbhelper->dbOpen();
$sql = "update request_information 
        set firstname = '$firstname', 
            lastname = '$lastname', 
            email = '$email', 
            phone = '$phone',
            organization = '$organization', 
            job_title = '$job_title',
            country = '$country', 
            state = '$state', 
                
            main_application = '$main_application', 
            main_product = '$main_product',
            purchase_timeline = '$purchase_timeline',
            isreceive = '$isreceive',
            note = '$note'
        where pkid = $pkid";
$dbhelper->RunSQL($sql);
$dbhelper->dbClose();

$webhelper->AlertMessageAndGo("", "/admin/webforms/request_information/list.php" . $Parameter);
?>