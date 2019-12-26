<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/news/events/list.php";
$PageCode = "030201";
$webhelper->CheckAdminLogin($PageCode, urlencode($PageUrl), true);

//Parameter
$pkid = $webhelper->RequestFilter("pkid", 0, false);
$page = $webhelper->RequestFilter("page", 0, false);
$searchKey = $webhelper->RequestFilter("searchKey", -1, false);
$order = $webhelper->RequestFilter("order", 0, false);

//check parameter
if($webhelper->isNull($pkid)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($page)) $page = 1;
if($webhelper->isNull($searchKey)) $searchKey = "";
if($webhelper->isNull($order)) $order = 0;

$Parameter = "?page=$page&searchKey=$searchKey&order=$order";

$dbhelper->dbOpen();
$sql = "insert into events(type, startyear, startmonth, startday, endyear, endmonth, endday, maintitle, subtitle, location1, location2, booth, status, body, thumbnail1, thumbnail2, filename, orgfilename, updatedate) 
        select type, startyear, startmonth, startday, endyear, endmonth, endday, maintitle, subtitle, location1, location2, booth, status, body, thumbnail1, thumbnail2, filename, orgfilename, now() from events where pkid = $pkid";
$inserpkid = $dbhelper->RunSQLReturnID($sql);

$sql = "select thumbnail1, thumbnail2, filename, orgfilename from events where pkid = $pkid";
$ViewData = $dbhelper->RunSQLReturnOneRow($sql);

if($ViewData["thumbnail1"] != "")
	$newThumbnail1 = $webhelper->CopyFile("/events/", $ViewData["thumbnail1"]);
if($ViewData["thumbnail2"] != "")
	$newThumbnail2 = $webhelper->CopyFile("/events/", $ViewData["thumbnail2"]);
if($ViewData["filename"] != "")
	$newFilename = $webhelper->CopyFile("/events/", $ViewData["filename"]);

$sql = "update events set thumbnail1 = '$newThumbnail1', thumbnail2 = '$newThumbnail2', filename = '$newFilename' where pkid = $inserpkid";
$dbhelper->RunSQL($sql);

$dbhelper->dbClose();

$webhelper->AlertMessageAndGo("", "/admin/news/events/list.php" . $Parameter);
?>

