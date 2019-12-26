<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

//Parameter
$page = $webhelper->RequestFilter("page", 0, false);
$year = $webhelper->RequestFilter("year", -1, false);

//check parameter
if($webhelper->isNull($page)) $page = 1;
if($webhelper->isNull($year)) $year = date("Y");

$Parameter = "&year=$year";
$PageSize = 5;

$dbhelper->dbOpen();
$sql = "select distinct year from media where status = 1 order by year desc";
$YearList = $dbhelper->RunSQLReturnRows($sql);

//$sql = "select pkid, year, month, day, maintitle, subtitle from media where status = 1 and year = '$year' order by year desc, month desc, day desc ";
$sql = "select pkid, year, month, day, source, maintitle, subtitle from media where status = 1 order by year desc, month desc, day desc ";
$List = $dbhelper->RunSQLReturnRowsSub($sql, $page, $PageSize, $TotalCount);
$pageList = $webhelper->getPaging($TotalCount, $page, $PageSize);
$dbhelper->dbClose();
?><head>    
<title>News and Press | Purigen Biosystems</title>
<meta name="description" content="The latest news and updates for Purigen Biosystems' innovative approach to library prep and DNA extraction." />
<link rel="canonical" href="https://www.purigenbio.com/news-events/news/list" />
<meta content="News and Press | Purigen Biosystems" property="og:title">
<meta content="News and Press | Purigen Biosystems" name="twitter:title">
<meta content="The latest news and updates for Purigen Biosystems' innovative approach to library prep and DNA extraction." property="og:description">
<meta content="The latest news and updates for Purigen Biosystems' innovative approach to library prep and DNA extraction." name="twitter:description">
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



<div id="container" style="overflow-x: hidden;">
    <div class="contents-visual contents-visual-news">
        <article>
            <h2>The latest news and updates</h2>
            <p>Purigen News and Press</p>
            <!--<a href="#">View All</a>-->
        </article>
    </div>
    <!-- //contents-visual -->
    
    <div id="contents" class="contents-pd0">
        <div class="contents-title">
            <!--<div class="contents-tab">
                <ul>
                <?php
                    if($YearList != null && count($YearList)>0)
                    {
                        foreach ($YearList as $row)
                        {
                ?>
                    <li class="<?=$webhelper->MakeSelectedValue($row["year"], $year, "active")?>"><a href="/news-events/news/list.php?year=<?=$row["year"]?>"><?=$row["year"]?></a></li>
                <?php
                        }
                    }
                ?>
                    <li></li>
                </ul>
            </div>-->
            <h2>News and Press</h2>
        </div>
        <!-- //contents-title -->

        <div class="news-list">
            <ul>
            <?php
                if($List != null && count($List)>0)
                {
                    foreach ($List as $row)
                    {
            ?>
                <li>
                    <div class="upcoming-event-contents">
                        <div class="news-date"><?=$webhelper->GetMonthShortName($row["month"])?> <?=$row["day"]?>, <?=$row["year"]?></div>
                        <div class="source">SOURCE: <?=$row["source"]?></div>
                        <div class="news-title"><?=$row["maintitle"]?></div>
                        <article class="news-article"><?=$row["subtitle"]?></article>
                        <div class="upcoming-event-view"><a href="/news-events/news/view.php?pkid=<?=$row["pkid"]?>" class="button-big"><strong>READ</strong> </a></div>
                    </div>
                </li>
            <?php
                    }
                }
            ?>
            </ul>
            
            <?=$webhelper->GetPageHtml($pageList, $page, "/news-events/news/list.php", $Parameter, $PageSize)?>
        </div>
        <!-- //events -->

    </div>
    <!-- //contents -->
</div>
<!-- //container -->
<?php include_once($_SERVER['DOCUMENT_ROOT'] . "/include/footer.php") ?>