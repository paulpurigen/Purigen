<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/resources/literature/list.php";
$PageCode = "020301";
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
$sql = "insert into literature(type, year, month, day, title, body, status, thumbnail1, thumbnail2, filename, orgfilename, updatedate) 
        select type, year, month, day, title, body, status, thumbnail1, thumbnail2, filename, orgfilename, updatedate from literature where pkid = $pkid";
$inserpkid = $dbhelper->RunSQLReturnID($sql);

$sql = "select thumbnail1, thumbnail2, filename, orgfilename from literature where pkid = $pkid";
$ViewData = $dbhelper->RunSQLReturnOneRow($sql);

if($ViewData["thumbnail1"] != "")
    $newThumbnail1 = $webhelper->CopyFile("/literature/", $ViewData["thumbnail1"]);
if($ViewData["thumbnail2"] != "")
    $newThumbnail2 = $webhelper->CopyFile("/literature/", $ViewData["thumbnail2"]);
if($ViewData["filename"] != "")
    $newFilename = $webhelper->CopyFile("/literature/", $ViewData["filename"]);

$sql = "update literature set thumbnail1 = '$newThumbnail1', thumbnail2 = '$newThumbnail2', filename = '$newFilename' where pkid = $inserpkid";
$dbhelper->RunSQL($sql);

$dbhelper->dbClose();

$webhelper->AlertMessageAndGo("", $PageUrl . $Parameter);
?>