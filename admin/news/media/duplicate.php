<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/news/media/list.php";
$PageCode = "030101";
$webhelper->CheckAdminLogin($PageCode, urlencode($PageUrl), true);

//Parameter
$pkid = $webhelper->RequestFilter("pkid", 0, false);
$page = $webhelper->RequestFilter("page", 0, false);
$type = $webhelper->RequestFilter("type", 0, false);
$searchKey = $webhelper->RequestFilter("searchKey", -1, false);
$order = $webhelper->RequestFilter("order", 0, false);

//check parameter
if($webhelper->isNull($pkid)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($page)) $page = 1;
if($webhelper->isNull($type)) $type = 1;
if($webhelper->isNull($searchKey)) $searchKey = "";
if($webhelper->isNull($order)) $order = 0;

$Parameter = "?type=$type&page=$page&searchKey=$searchKey&order=$order";

$dbhelper->dbOpen();
$sql = "insert into media(year, month, day, source, url, maintitle, subtitle, body, thumbnail, filename, orgfilename, status) 
        select year, month, day, source, url, maintitle, subtitle, body, thumbnail, filename, orgfilename, status from media where pkid = $pkid";
$inserpkid = $dbhelper->RunSQLReturnID($sql);

$sql = "select thumbnail, filename from media where pkid = $pkid";
$ViewData = $dbhelper->RunSQLReturnOneRow($sql);

if($ViewData["thumbnail"] != "")
    $newThumbnail = $webhelper->CopyFile("/media/", $ViewData["thumbnail"]);
if($ViewData["filename"] != "")
    $newFilename = $webhelper->CopyFile("/media/", $ViewData["filename"]);

$sql = "update media set thumbnail = '$newThumbnail', filename = '$newFilename' where pkid = $inserpkid";
$dbhelper->RunSQL($sql);
$dbhelper->dbClose();

$webhelper->AlertMessageAndGo("", $PageUrl . $Parameter);
?>

