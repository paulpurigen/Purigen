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
$oldthumbnail1 = $webhelper->RequestFilter("oldthumbnail1", 100, false);
$oldthumbnail2 = $webhelper->RequestFilter("oldthumbnail2", 100, false);
$oldfile = $webhelper->RequestFilter("oldFile", 100, false);
$oldorgfile = $webhelper->RequestFilter("oldOrgFile", 100, false);

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
$deleteThumbnail1 = $webhelper->RequestFilter("deleteThumbnail1", 1, false);
$deleteThumbnail2 = $webhelper->RequestFilter("deleteThumbnail2", 1, false);
$deleteFile = $webhelper->RequestFilter("deleteFile", 1, false);

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

if($webhelper->isNull($pkid)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($page)) $page = 1;
if($webhelper->isNull($searchKey)) $searchKey = "";
if($webhelper->isNull($order)) $order = 0;
if($webhelper->isNull($deleteThumbnail1)) $deleteThumbnail1 = 0;
if($webhelper->isNull($deleteThumbnail2)) $deleteThumbnail2 = 0;
if($webhelper->isNull($deleteFile)) $deleteFile = 0;

$Parameter = "?page=$page&searchKey=$searchKey&order=$order";

//file upload
$thumbnail1 = $webhelper->UploadFile("thumbnail1", "/events/", true);
if($thumbnail1 != null)
{ //upload new file so that delete old file;
    if($oldthumbnail1 != "")
        $webhelper->DeleteFile("/events/", $oldthumbnail1);
    $newThumbnailFielName1 = $thumbnail1["filename"];
}else
{
    if($deleteThumbnail1 == "1")
    {
        //deleteFile
        $webhelper->DeleteFile("/events/", $oldthumbnail1);
        $newThumbnailFielName1 = "";
    }else
    {
        $newThumbnailFielName1 = $oldthumbnail1;
    }
}

//file upload
$thumbnail2 = $webhelper->UploadFile("thumbnail2", "/events/", true);
if($thumbnail2 != null)
{ //upload new file so that delete old file;
    if($oldthumbnail2 != "")
        $webhelper->DeleteFile("/events/", $oldthumbnail2);
    $newThumbnailFielName2 = $thumbnail2["filename"];
}else
{
    if($deleteThumbnail2 == "1")
    {
        //deleteFile
        $webhelper->DeleteFile("/events/", $oldthumbnail2);
        $newThumbnailFielName2 = "";
    }else
    {
        $newThumbnailFielName2 = $oldthumbnail2;
    }
}

$file = $webhelper->UploadFile("up_file", "/events/");
if($file != null)
{ //upload new file so that delete old file;
    if($oldfile != "")
        $webhelper->DeleteFile("/events/", $oldfile);
    $newFielName = $file["filename"];
    $newOrgFielName = $file["orgfilename"];
}else
{
    if($deleteFile == "1")
    {
        //deleteFile
        $webhelper->DeleteFile("/events/", $oldfile);
        $newFielName = "";
        $newOrgFielName = "";
    }else
    {
        $newFielName = $oldfile;
        $newOrgFielName = $oldorgfile;
    }
}

$dbhelper->dbOpen();
$sql = "update events 
            set type = '$type',
                startyear = '$startyear',
                startmonth = '$startmonth',
                startday = '$startday',
                endyear = '$endyear',
                endmonth = '$endmonth',
                endday = '$endday',
                maintitle = '$maintitle',
                subtitle = '$subtitle',
                url = '$url',
                location1 = '$location1',
                location2 = '$location2',
                booth = '$boothnum',
                body = '$body',
                thumbnail1 = '$newThumbnailFielName1',
                thumbnail2 = '$newThumbnailFielName2',
                filename = '$newFielName',
                orgfilename = '$newOrgFielName',
                updatedate = now()
            where pkid = $pkid";
$dbhelper->RunSQL($sql);
$dbhelper->dbClose();

$webhelper->AlertMessageAndGo("", "/admin/news/events/list.php" . $Parameter);
?>