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
$searchKey = $webhelper->RequestFilter("searchKey", -1, false);
$order = $webhelper->RequestFilter("order", 0, false);
$oldthumbnail = $webhelper->RequestFilter("oldthumbnail", 100, false);
$oldfile = $webhelper->RequestFilter("oldFile", 100, false);
$oldorgfile = $webhelper->RequestFilter("oldOrgFile", 100, false);

$year = $webhelper->RequestFilter("year", 4, false);
$month = $webhelper->RequestFilter("month", 2, false);
$day = $webhelper->RequestFilter("day", 2, false);
$source = $webhelper->RequestFilter("source", 100, false);
$url = $webhelper->RequestFilter("url", 200, false);
$maintitle = $webhelper->RequestFilter("maintitle", 200, false);
$subtitle = $webhelper->RequestFilter("subtitle", -1, true);
$body = $webhelper->RequestFilter("body", -1, true);
$deleteThumbnail = $webhelper->RequestFilter("deleteThumbnail", 1, false);
$deleteFile = $webhelper->RequestFilter("deleteFile", 1, false);

//check parameter
if($webhelper->isNull($year)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($month)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($day)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($maintitle)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($subtitle)) $webhelper->AlertMessage("Error. Please try again.");

if($webhelper->isNull($pkid)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($page)) $page = 1;
if($webhelper->isNull($searchKey)) $searchKey = "";
if($webhelper->isNull($order)) $order = 0;
if($webhelper->isNull($deleteThumbnail)) $deleteThumbnail = 0;
if($webhelper->isNull($deleteFile)) $deleteFile = 0;

$Parameter = "?page=$page&searchKey=$searchKey&order=$order";

//file upload
$thumbnail = $webhelper->UploadFile("thumbnail", "/media/", true);
if($thumbnail != null)
{ //upload new file so that delete old file;
    if($oldthumbnail != "")
        $webhelper->DeleteFile("/media/", $oldthumbnail);
    $newThumbnailFielName = $thumbnail["filename"];
}else
{
    if($deleteThumbnail == "1")
    {
        //deleteFile
        $webhelper->DeleteFile("/media/", $oldthumbnail);
        $newThumbnailFielName = "";
    }else
    {
        $newThumbnailFielName = $oldthumbnail;
    }
}

$file = $webhelper->UploadFile("up_file", "/media/");

if($file != null)
{ //upload new file so that delete old file;
    if($oldfile != "")
        $webhelper->DeleteFile("/media/", $oldfile);
    $newFielName = $file["filename"];
    $newOrgFielName = $file["orgfilename"];
}else
{
    if($deleteFile == "1")
    {
        //deleteFile
        $webhelper->DeleteFile("/media/", $oldfile);
        $newFielName = "";
        $newOrgFielName = "";
    }else
    {
        $newFielName = $oldfile;
        $newOrgFielName = $oldorgfile;
    }
}

$dbhelper->dbOpen();
$sql = "update media 
        set year = '$year', 
            month = '$month', 
            day = '$day',
            source = '$source',
            url = '$url',
            maintitle = '$maintitle',
            subtitle = '$subtitle',
            body = '$body',
            thumbnail = '$newThumbnailFielName',
            filename = '$newFielName',
            orgfilename = '$newOrgFielName'
        where pkid = $pkid";
$dbhelper->RunSQL($sql);
$dbhelper->dbClose();

$webhelper->AlertMessageAndGo("", "/admin/news/media/list.php" . $Parameter);
?>