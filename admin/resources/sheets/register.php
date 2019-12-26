<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/resources/sheets/list.php";
$PageCode = "020201";
$webhelper->CheckAdminLogin($PageCode, urlencode($PageUrl), true);

//Parameter
$year = $webhelper->RequestFilter("year", 4, false);
$month = $webhelper->RequestFilter("month", 2, false);
$day = $webhelper->RequestFilter("day", 2, false);
$title = $webhelper->RequestFilter("title", 200, false);
$body = $webhelper->RequestFilter("body", -1, true);

//check parameter
if($webhelper->isNull($year)) $webhelper->AlertMessage("There is an error in your Year entry. Please try again..");
if($webhelper->isNull($month)) $webhelper->AlertMessage("There is an error in your Month entry. Please try again.");
if($webhelper->isNull($day)) $webhelper->AlertMessage("There is an error in your Day entry. Please try again.");
if($webhelper->isNull($title)) $webhelper->AlertMessage("Error. Please try again.");

//file upload
$thumbnail1 = $webhelper->UploadFile("thumbnail1", "/sheets/", true);
$thumbnail2 = $webhelper->UploadFile("thumbnail2", "/sheets/", true);

$file = $webhelper->UploadFile("up_file", "/sheets/");

$dbhelper->dbOpen();
$sql = "insert into sheets(year, month, day, title, body, thumbnail1, thumbnail2, filename, orgfilename, updatedate) values 
        ('$year', '$month', '$day', '$title', '$body', '" . $thumbnail1["filename"] . "','" . $thumbnail2["filename"] . "', '" . $file["filename"] . "','" . $file["orgfilename"] . "', now() )";
$dbhelper->RunSQL($sql);
$dbhelper->dbClose();

$webhelper->AlertMessageAndGo("", "/admin/resources/sheets/list.php");
?>