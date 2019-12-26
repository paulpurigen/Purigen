<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/manager/list.php";
$PageCode = "010101";
$webhelper->CheckAdminLogin($PageCode, urlencode($PageUrl), true);

//Parameter
$pkid = $webhelper->RequestFilter("pkid", 0, false);
$page = $webhelper->RequestFilter("page", 0, false);
$searchKey = $webhelper->RequestFilter("searchKey", -1, false);
$filter = $webhelper->RequestFilter("filter", 0, false);
$order = $webhelper->RequestFilter("order", 0, false);

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
if($webhelper->isNull($pkid)) $webhelper->AlertMessage("Error. Please try again.1");
if($webhelper->isNull($id)) $webhelper->AlertMessage("Error. Please try again.2");
if($webhelper->isNull($email)) $webhelper->AlertMessage("Error. Please try again.3");
if($webhelper->isNull($status)) $status = 1;
if($webhelper->isNull($isAdministrator)) $isAdministrator = 0;
if($webhelper->isNull($isEditor)) $isEditor = 0;
if($webhelper->isNull($isCareers)) $isCareers = 0;

if($webhelper->isNull($firstname)) $webhelper->AlertMessage("It is the wrong access5.");
if($webhelper->isNull($lastname)) $webhelper->AlertMessage("Error. Please try again.6");

$Parameter = "?page=$page&searchKey=$searchKey&filter=$filter&order=$order";

$dbhelper->dbOpen();
if($passwd != "")
{
    $sql = "update admin set id = '$id', 
                pass = '$passwd', 
                email = '$email', 
                status = '$status', 
                isAdministrator = '$isAdministrator',
                isEditor = '$isEditor',
                isCareers = '$isCareers', 
                firstname = '$firstname', 
                lastname = '$lastname', 
                company = '$company', 
                country = '$country', 
                providence = '$providence',
                city = '$city',
                postcode = '$postcode',
                phone = '$phone'
                where pkid = $pkid";
}else
{
    $sql = "update admin set id = '$id', 
                email = '$email', 
                status = '$status', 
                isAdministrator = '$isAdministrator',
                isEditor = '$isEditor',
                isCareers = '$isCareers', 
                firstname = '$firstname', 
                lastname = '$lastname', 
                company = '$company', 
                country = '$country', 
                providence = '$providence',
                city = '$city',
                postcode = '$postcode',
                phone = '$phone'
                where pkid = $pkid";
}
$dbhelper->RunSQL($sql);
$dbhelper->dbClose();

$webhelper->AlertMessageAndGo("", "/admin/manager/list.php" . $Parameter);
?>