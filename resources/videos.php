<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

//Parameter
$page = $webhelper->RequestFilter("page", 0, false);

//check parameter
if($webhelper->isNull($page)) $page = 1;

$Parameter = "";

$PageSize = 6;

$dbhelper->dbOpen();
$sql = "select pkid, year, month, day, title, body, videourl, thumbnail1, filename, orgfilename from video where status = 1 order by pkid desc ";
$List = $dbhelper->RunSQLReturnRowsSub($sql, $page, $PageSize, $TotalCount);
$pageList = $webhelper->getPaging($TotalCount, $page, $PageSize);
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
    <div class="contents-visual contents-visual-videos">
        <article>
            <h2>View our latest videos</h2>
            <p>Purigen Videos</p>
        </article>
    </div>
    <!-- //contents-visual -->

    <div id="contents" class="contents-flex">
        <div id="contents-section">
            <div class="contents-title">
                <h2>Videos</h2>
            </div>
            <!-- //contents-title -->

            <div class="brochures-list">
                <ul>
                <?php 
                    if($List != null && count($List) > 0)
                    {
                        $num=0; $i=0;
                        foreach ($List as $row)
                        {
                ?>
                    <li>
                        <div class="brochures">
                            <div class="brochures-type">video</div>
                            <div class="brochures-thumb"><img src="/files/video/<?=$row["thumbnail1"]?>" alt="" width="276" height="356"/></div>
                            <div class="brochures-meta"><span><?=$webhelper->GetMonthShortName($row["month"])?> <?=$row["day"]?> <?=$row["year"]?></span></div>
                            <div class="brochures-title"><?=$row["title"]?></div>
                            <div class="brochures-desc"><?=$row["body"]?></div>
                            <div class="brochures-download"><a href="/resources/video.php?pkid=<?=$row["pkid"]?>" class="button-big">play video</a></div>
                        </div>
                    </li>
                <?php
                        }
                    }
                ?>
                </ul>
                
                <?=$webhelper->GetPageHtml($pageList, $page, "/resources/videos.php", $Parameter, $PageSize)?>
            </div>
            <!-- //brochures-list -->
        </div>
        <!-- //contents-section -->
    </div>
    <!-- //contents -->
</div>
<!-- //container -->
<?php include_once($_SERVER['DOCUMENT_ROOT'] . "/include/footer.php") ?>