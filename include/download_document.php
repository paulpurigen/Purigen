<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');
 
$webhelper = new WebHelper();
$dbhelper = new dbHelper();
 
//Parameter
$pkid = $webhelper->RequestFilter("pkid", 0, false);
 
//check parameter
if($webhelper->isNull($pkid)) $webhelper->AlertMessage("Access error.");
 
$dbhelper->dbOpen();
$sql = "select pkid, filename, orgfilename from document where status = 1 and pkid = " . $pkid;
$ViewData = $dbhelper->RunSQLReturnOneRow($sql);
$dbhelper->dbClose();
 
if($ViewData == null)
    $webhelper->AlertMessageAndHistoryBack("Data is deleted or do not exist.");
 
$webhelper->DownloadFile("/document/", $ViewData["filename"], $ViewData["orgfilename"]);
?>