<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/news/media/list.php";
$PageCode = "030101";
$webhelper->CheckAdminLogin($PageCode, urlencode($PageUrl), true);

//Parameter
$year = $webhelper->RequestFilter("year", 4, false);
$month = $webhelper->RequestFilter("month", 2, false);
$day = $webhelper->RequestFilter("day", 2, false);
$source = $webhelper->RequestFilter("source", 100, false);
$url = $webhelper->RequestFilter("url", 200, false);
$maintitle = $webhelper->RequestFilter("maintitle", 200, false);
$subtitle = $webhelper->RequestFilter("subtitle", -1, true);
$body = $webhelper->RequestFilter("body", -1, true);

//check parameter
if($webhelper->isNull($year)) $webhelper->AlertMessage("There is an error in your Year entry. Please try again..");
if($webhelper->isNull($month)) $webhelper->AlertMessage("There is an error in your Month entry. Please try again.");
if($webhelper->isNull($day)) $webhelper->AlertMessage("There is an error in your Day entry. Please try again.");
if($webhelper->isNull($maintitle)) $webhelper->AlertMessage("There is an error in your Title entry. Please try again.");
if($webhelper->isNull($subtitle)) $webhelper->AlertMessage("There is an error in your Subtitle entry. Please try again.");

//file upload
$thumbnail = $webhelper->UploadFile("thumbnail", "/media/", true);
$file = $webhelper->UploadFile("up_file", "/media/");

$status = 0;

$dbhelper->dbOpen();
$sql = "insert into media(year, month, day, source, url, maintitle, subtitle, body, thumbnail, filename, orgfilename, status) values 
        ('$year', '$month', '$day', '$source', '$url', '$maintitle', '$subtitle', '$body', '" . $thumbnail["filename"] . "', '" . $file["filename"] . "', '" . $file["orgfilename"] . "', '$status' )";
$dbhelper->RunSQL($sql);
$dbhelper->dbClose();

$webhelper->AlertMessageAndGo("", "/admin/news/media/list.php");
?>