<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/news/video/list.php";
$PageCode = "020101";
$webhelper->CheckAdminLogin($PageCode, urlencode($PageUrl), true);

//Parameter
$pkid = $webhelper->RequestFilter("pkid", 0, false);
$page = $webhelper->RequestFilter("page", 0, false);
$searchKey = $webhelper->RequestFilter("searchKey", -1, false);
$order = $webhelper->RequestFilter("order", 0, false);
$oldthumbnail1 = $webhelper->RequestFilter("oldthumbnail1", 100, false);
$oldthumbnail2 = $webhelper->RequestFilter("oldthumbnail2", 100, false);
$oldfile = $webhelper->RequestFilter("oldFile", 100, false);
$oldorgfile = $webhelper->RequestFilter("oldOrgFile", 100, false);

$year = $webhelper->RequestFilter("year", 4, false);
$month = $webhelper->RequestFilter("month", 2, false);
$day = $webhelper->RequestFilter("day", 2, false);
$title = $webhelper->RequestFilter("title", 200, false);
$body = $webhelper->RequestFilter("body", -1, true);
$videourl = $webhelper->RequestFilter("videourl", 500, false);
$deleteThumbnail1 = $webhelper->RequestFilter("deleteThumbnail1", 1, false);
$deleteThumbnail2 = $webhelper->RequestFilter("deleteThumbnail2", 1, false);
$deleteFile = $webhelper->RequestFilter("deleteFile", 1, false);

//check parameter
if($webhelper->isNull($year)) $webhelper->AlertMessage("There is an error in your Year entry. Please try again..");
if($webhelper->isNull($month)) $webhelper->AlertMessage("There is an error in your Month entry. Please try again.");
if($webhelper->isNull($day)) $webhelper->AlertMessage("There is an error in your Day entry. Please try again.");
if($webhelper->isNull($title)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($videourl)) $webhelper->AlertMessage("Error. Please try again.");

if($webhelper->isNull($pkid)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($page)) $page = 1;
if($webhelper->isNull($searchKey)) $searchKey = "";
if($webhelper->isNull($order)) $order = 0;
if($webhelper->isNull($deleteThumbnail1)) $deleteThumbnail1 = 0;
if($webhelper->isNull($deleteThumbnail2)) $deleteThumbnail2 = 0;
if($webhelper->isNull($deleteFile)) $deleteFile = 0;

$Parameter = "?page=$page&searchKey=$searchKey&order=$order";

//file upload
$thumbnail1 = $webhelper->UploadFile("thumbnail1", "/video/", true);
if($thumbnail1 != null)
{ //upload new file so that delete old file;
    if($oldthumbnail1 != "")
        $webhelper->DeleteFile("/video/", $oldthumbnail1);
    $newThumbnailFielName1 = $thumbnail1["filename"];
}else
{
    if($deleteThumbnail1 == "1")
    {
        //deleteFile
        $webhelper->DeleteFile("/video/", $oldthumbnail1);
        $newThumbnailFielName1 = "";
    }else
    {
        $newThumbnailFielName1 = $oldthumbnail1;
    }
}

$thumbnail2 = $webhelper->UploadFile("thumbnail2", "/video/", true);
if($thumbnail2 != null)
{ //upload new file so that delete old file;
    if($oldthumbnail2 != "")
        $webhelper->DeleteFile("/video/", $oldthumbnail2);
    $newThumbnailFielName2 = $thumbnail2["filename"];
}else
{
    if($deleteThumbnail2 == "1")
    {
        //deleteFile
        $webhelper->DeleteFile("/video/", $oldthumbnail2);
        $newThumbnailFielName2 = "";
    }else
    {
        $newThumbnailFielName2 = $oldthumbnail2;
    }
}

$file = $webhelper->UploadFile("up_file", "/video/");
if($file != null)
{ //upload new file so that delete old file;
    if($oldfile != "")
        $webhelper->DeleteFile("/video/", $oldfile);
    $newFielName = $file["filename"];
    $newOrgFielName = $file["orgfilename"];
}else
{
    if($deleteFile == "1")
    {
        //deleteFile
        $webhelper->DeleteFile("/video/", $oldfile);
        $newFielName = "";
        $newOrgFielName = "";
    }else
    {
        $newFielName = $oldfile;
        $newOrgFielName = $oldorgfile;
    }
}

$dbhelper->dbOpen();
$sql = "update video 
        set year = '$year',
            month = '$month',
            day = '$day',
            title = '$title',
            body = '$body',
            videourl = '$videourl',
            thumbnail1 = '$newThumbnailFielName1',
            thumbnail2 = '$newThumbnailFielName2',
            filename = '$newFielName',
            orgfilename = '$newOrgFielName',
            updatedate = now()
        where pkid = $pkid";
$dbhelper->RunSQL($sql);
$dbhelper->dbClose();

$webhelper->AlertMessageAndGo("", "/admin/news/video/list.php" . $Parameter);
?>