<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/news/video/list.php";
$PageCode = "020101";
$webhelper->CheckAdminLogin($PageCode, urlencode($PageUrl), true);

//Parameter
$pkids = $webhelper->RequestFilterMulti("pkids", 0, false);
$page = $webhelper->RequestFilter("page", 0, false);
$searchKey = $webhelper->RequestFilter("searchKey", -1, false);
$order = $webhelper->RequestFilter("order", 0, false);

//check parameter
if(count($pkids)<=0) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($page)) $page = 1;
if($webhelper->isNull($searchKey)) $searchKey = "";
if($webhelper->isNull($order)) $order = 0;

$Parameter = "?page=$page&searchKey=$searchKey&order=$order";

$pkid = implode(",", $pkids);

$dbhelper->dbOpen();
$sql = "select pkid, thumbnail1, thumbnail2, filename from video where pkid in( $pkid )";
$List = $dbhelper->RunSQLReturnRows($sql);

if($List != null && count($List) > 0)
{
    foreach ($List as $row)
    {
        if($row["thumbnail1"] != "")
            $webhelper->DeleteFile("/video/", $row["thumbnail1"]);
        if($row["thumbnail2"] != "")
            $webhelper->DeleteFile("/video/", $row["thumbnail2"]);
        if($row["filename"] != "")
            $webhelper->DeleteFile("/video/", $row["filename"]);
        
        $sql = "delete from video where pkid = " . $row["pkid"];
        $dbhelper->RunSQL($sql);
    }
}
$dbhelper->dbClose();

$webhelper->AlertMessageAndGo("", "/admin/news/video/list.php" . $Parameter);
?>