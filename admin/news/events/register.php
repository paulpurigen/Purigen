<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/news/events/list.php";
$PageCode = "030201";
$webhelper->CheckAdminLogin($PageCode, urlencode($PageUrl), true);

//Parameter
$type = $webhelper->RequestFilter("type", 0, false);
$startyear = $webhelper->RequestFilter("startyear", 4, false);
$startmonth = $webhelper->RequestFilter("startmonth", 2, false);
$startday = $webhelper->RequestFilter("startday", 2, false);
$endyear = $webhelper->RequestFilter("endyear", 4, false);
$endmonth = $webhelper->RequestFilter("endmonth", 2, false);
$endday = $webhelper->RequestFilter("endday", 2, false);
$maintitle = $webhelper->RequestFilter("maintitle", 200, false);
$subtitle = $webhelper->RequestFilter("subtitle", -1, true);
$url = $webhelper->RequestFilter("url", 100, true);
$location1 = $webhelper->RequestFilter("location1", 100, true);
$location2 = $webhelper->RequestFilter("location2", 100, true);
$boothnum = $webhelper->RequestFilter("boothnum", 50, false);
$body = $webhelper->RequestFilter("body", -1, true);

//check parameter
if($webhelper->isNull($type)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($startyear)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($startmonth)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($startday)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($endyear)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($endmonth)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($endday)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($maintitle)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($subtitle)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($location1)) $webhelper->AlertMessage("Error. Please try again.");

//file upload
$thumbnail1 = $webhelper->UploadFile("thumbnail1", "/events/", true);
$thumbnail2 = $webhelper->UploadFile("thumbnail2", "/events/", true);
$file = $webhelper->UploadFile("up_file", "/events/");

$dbhelper->dbOpen();
$sql = "insert into events(type, startyear, startmonth, startday, endyear, endmonth, endday, maintitle, subtitle, url, location1, location2, booth, body, thumbnail1, thumbnail2, filename, orgfilename, updatedate) values 
        ('$type', '$startyear', '$startmonth', '$startday', '$endyear', '$endmonth', '$endday', '$maintitle', '$subtitle', '$url', '$location1', '$location2', '$boothnum', '$body',  '" .
        $thumbnail1["filename"] . "', '" . $thumbnail2["filename"] . "', '" . $file["filename"] . "', '" . $file["orgfilename"] . "', now() )";
$dbhelper->RunSQL($sql);
$dbhelper->dbClose();

$webhelper->AlertMessageAndGo("", "/admin/news/events/list.php");
?>