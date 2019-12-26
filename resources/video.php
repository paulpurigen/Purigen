<?php

include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

//Parameter
$pkid = $webhelper->RequestFilter("pkid", 0, false);

//check parameter
if($webhelper->isNull($pkid)) $webhelper->AlertMessageAndHistoryBack("Error. Please try again.");

$dbhelper->dbOpen();
$sql = "select pkid, year, month, day, title, body, videourl, thumbnail1, filename, orgfilename from video where status = 1 and pkid = '$pkid'";
$ViewData = $dbhelper->RunSQLReturnOneRow($sql);

if($webhelper->isNull($ViewData))
{
    $dbhelper->dbClose();
    $webhelper->AlertMessageAndHistoryBack("Error. Please try again.");
}
$dbhelper->dbClose();
?><head>    
<title>Videos | Purigen Biosystems</title>
<meta name="description" content="View and download the latest videos from Purigen Biosystems." />
<link rel="canonical" href="https://www.purigenbio.com/resources/videos" />
<meta content="Videos | Purigen Biosystems" property="og:title">
<meta content="Videos | Purigen Biosystems" name="twitter:title">
<meta content="View and download the latest videos from Purigen Biosystems." property="og:description">
<meta content="View and download the latest videos from Purigen Biosystems." name="twitter:description">
<meta content="https://www.purigenbio.com/images/common/logo-purigen-social.jpg" property="og:image">
<meta content="https://www.purigenbio.com/images/common/logo-purigen-social.jpg" name="twitter:image">
<meta content="website" property="og:type">
<meta content="Purigen Biosystems | A Revolution in DNA Extraction" property="og:site_name">
<meta content="summary" name="twitter:card">
<meta content="@purigenbio" name="twitter:site">
<meta content="1600" property="og:image:width">
<meta content="400" property="og:image:height">

<?php include_once($_SERVER['DOCUMENT_ROOT'] . "/include/header.php") ?>
</head>

<div id="container">
    <div class="technology-visual-outer">
        <div class="technology-visual">
            <h2><?=$ViewData["title"]?></h2>
            <div class="technology-visual-movie">
                <iframe class="elementor-video-iframe" allowfullscreen="true" src="https://www.youtube.com/embed/<?=str_replace('https://youtu.be/', '', $ViewData["videourl"])?>"></iframe>
            </div>
        </div>
    </div>
    <!-- //technology-visual -->
</div>
<!-- //container -->

<?php include_once($_SERVER['DOCUMENT_ROOT'] . "/include/footer.php") ?>