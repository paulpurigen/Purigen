<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/resources/literature/list.php";
$PageCode = "020301";
$webhelper->CheckAdminLogin($PageCode, urlencode($PageUrl), true);

//Parameter
$type = $webhelper->RequestFilter("type", 0, false);
$year = $webhelper->RequestFilter("year", 4, false);
$month = $webhelper->RequestFilter("month", 2, false);
$day = $webhelper->RequestFilter("day", 2, false);
$title = $webhelper->RequestFilter("title", 200, false);
$body = $webhelper->RequestFilter("body", -1, true);

//check parameter
if($webhelper->isNull($type)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($year)) $webhelper->AlertMessage("There is an error in your Year entry. Please try again..");
if($webhelper->isNull($month)) $webhelper->AlertMessage("There is an error in your Month entry. Please try again.");
if($webhelper->isNull($day)) $webhelper->AlertMessage("There is an error in your Day entry. Please try again.");
if($webhelper->isNull($title)) $webhelper->AlertMessage("Error. Please try again.");

//file upload
$thumbnail1 = $webhelper->UploadFile("thumbnail1", "/literature/", true);
$thumbnail2 = $webhelper->UploadFile("thumbnail2", "/literature/", true);

$file = $webhelper->UploadFile("up_file", "/literature/");

$dbhelper->dbOpen();
$sql = "insert into literature(type, year, month, day, title, body, thumbnail1, thumbnail2, filename, orgfilename, updatedate) values 
        ('$type', '$year', '$month', '$day', '$title', '$body', '" . $thumbnail1["filename"] . "','" . $thumbnail2["filename"] . "', '" . $file["filename"] . "','" . $file["orgfilename"] . "', now() )";
$dbhelper->RunSQL($sql);
$dbhelper->dbClose();

$webhelper->AlertMessageAndGo("", "/admin/resources/literature/list.php");
?>