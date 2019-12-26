<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

//Parameter
$pkid = $webhelper->RequestFilter("pkid", 0, false);

//check parameter
if($webhelper->isNull($pkid)) $webhelper->AlertMessageAndHistoryBack("Error. Please try again.");

$dbhelper->dbOpen();
$sql = "select pkid, year, month, day, source, url, maintitle, subtitle, body, thumbnail, filename, orgfilename from media where status = 1 and pkid = '$pkid'";
$ViewData = $dbhelper->RunSQLReturnOneRow($sql);

if($webhelper->isNull($ViewData))
{
    $dbhelper->dbClose();
    $webhelper->AlertMessageAndHistoryBack("Error. Please try again.");
}

$sql = "select pkid from media where concat(year,month,day) < '$ViewData[year]$ViewData[month]$ViewData[day]' order by year desc, month desc, day desc limit 1";
$forward = $dbhelper->RunSQLReturnOneRow($sql);

$sql = "select pkid from media where concat(year,month,day) > '$ViewData[year]$ViewData[month]$ViewData[day]' order by year, month, day limit 1";
$back = $dbhelper->RunSQLReturnOneRow($sql);
$dbhelper->dbClose();
?><head>    

<title><?=$ViewData["maintitle"]?></title>
<meta name="description" content="<?=$ViewData["subtitle"]?>" />
<link rel='canonical' href='https://www.<?=$webhelper->Domain?>/news-events/news/view.php?pkid=<?=$ViewData["pkid"]?>' />
<meta charset="utf-8">

<?php include_once($_SERVER['DOCUMENT_ROOT'] . "/include/header.php") ?>

</head>

<div id="container">
    <div id="contents" class="none-visual">
        <div class="article-view">
            <div class="article-control">
                <a href="<?=$back != null ? "/news-events/news/view.php?pkid=".$back["pkid"] : "#"?>" class="article-back"><i class="fas fa-chevron-left"></i> Newer</a>
                <a href="<?=$forward != null ? "/news-events/news/view.php?pkid=".$forward["pkid"] : "#"?>" class="article-forward">Older <i class="fas fa-chevron-right"></i></a>
            </div>
            <!-- //article-control -->

            <div class="article-util">
                <div class="article-date"><?=$webhelper->GetMonthShortName($ViewData["month"])?> <?=$ViewData["day"]?>, <?=$ViewData["year"]?></div>
                <dl>
                    <dt>SHARE:</dt>
                    <dd>
                        <a href="http://twitter.com/home?status=<?=$ViewData["maintitle"]?> https://www.<?=$webhelper->Domain?>/news-events/news/view.php?pkid=<?=$ViewData["pkid"]?>" target="_blank"><i class="fab fa-twitter-square"></i></a>
                        <a href="http://linkedin.com/shareArticle?mini=true&url=https://www.<?=$webhelper->Domain?>/news-events/news/view.php?pkid=<?=$ViewData["pkid"]?>&title=<?=$ViewData["maintitle"]?>" target="_blank"><i class="fab fa-linkedin"></i></a>
                        <a href="http://www.facebook.com/sharer.php?s=100&p[url]=https://www.<?=$webhelper->Domain?>/news-events/news/view.php?pkid=<?=$ViewData["pkid"]?>&p[title]=<?= urlencode($ViewData["maintitle"])?>" target="_blank"><i class="fab fa-facebook-square"></i></a>
                    </dd>
                </dl>
            </div>
            <!-- //article-util -->

            <div class="article-title">
                <h2><?=$ViewData["maintitle"]?></h2>
                <h3><?=$ViewData["subtitle"]?></h3>
            </div>
            <!-- //article-title -->

            <article>
                <?=$ViewData["body"]?>
            </article>

            <div class="media-contact">
                <dl>
                    <dt>MEDIA CONTACT</dt>
                    <dd><strong>NICOLE LITCHFIELD</strong></dd>
                    <dd>+1 415-793-6468</dd>
                    <dd>&#110;&#105;&#099;&#111;&#108;&#101;&#064;&#098;&#105;&#111;&#115;&#099;&#114;&#105;&#098;&#101;&#046;&#099;&#111;&#109;</dd>
                </dl>
            </div>
            <!-- //media-contact-->

        </div>
        <!-- //article-view -->
    </div>
    <!-- //contents -->
</div>
<!-- //container -->
<?php include_once($_SERVER['DOCUMENT_ROOT'] . "/include/footer.php") ?>