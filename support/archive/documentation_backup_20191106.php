<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

//Parameter
$page = $webhelper->RequestFilter("page", 0, false);
$type = $webhelper->RequestFilter("type", 0, false);

//check parameter
if($webhelper->isNull($page)) $page = 1;
if($webhelper->isNull($type)) $type = 0;

$Parameter = "&type=$type";

$PageSize = 6;

$dbhelper->dbOpen();
$sql = "select pkid, type, title, body, thumbnail1, filename, orgfilename from document where status = 1";
if($type != "0")
{
    $sql .= " and type = '$type' ";
}
$sql .= " order by title ";

$List = $dbhelper->RunSQLReturnRowsSub($sql, $page, $PageSize, $TotalCount);
$pageList = $webhelper->getPaging($TotalCount, $page, $PageSize);
$dbhelper->dbClose();
?><head>    
<title>Documentation and Manuals | Purigen Biosystems</title>
<meta name="description" content="View and download the latest documentation, manuals, and guides from Purigen Biosystems." />
<link rel="canonical" href="https://www.purigenbio.com/support/documentation" />
<meta content="Documentation | Purigen Biosystems" property="og:title">
<meta content="Documentation | Purigen Biosystems" name="twitter:title">
<meta content="View and download the latest documentation, manuals, and guides from Purigen Biosystems." property="og:description">
<meta content="View and download the latest documentation, manuals, and guides from Purigen Biosystems." name="twitter:description">
<meta content="https://www.purigenbio.com/images/common/logo-purigen-social.jpg" property="og:image">
<meta content="https://www.purigenbio.com/images/common/logo-purigen-social.jpg" name="twitter:image">
<meta content="website" property="og:type">
<meta content="summary" name="twitter:card">
<meta content="@purigenbio" name="twitter:site">
<meta content="1600" property="og:image:width">
<meta content="400" property="og:image:height">

<?php include_once($_SERVER['DOCUMENT_ROOT'] . "/include/header.php") ?>
</head>



<div id="container">
    <script type="text/javascript">
        function goUrl(type)
        {
            location.href = "/support/documentation.php?type="+type;
        }
    </script>
    <div class="contents-visual contents-visual-documentation">
        <article>
            <h2>Download the latest support documents</h2>
            <p>Purigen Documentation</p>
        </article>
    </div>
    <!-- //contents-visual -->

    <div id="contents" class="contents-flex">
        <aside id="contents-aside">
            <div class="aside-category">
                <h3>Categories</h3>
                <ul>
                    <li>
                        <div class="checkbox-set"><input type="radio" name="type" value="0" <?=$webhelper->MakeSelectedValue("0", $type, "checked")?> onclick="goUrl('0');" id="category-all"/> <label for="category-all">ALL</label></div>
                    </li>
                    <li>
                        <div class="checkbox-set"><input type="radio" name="type" value="0" <?=$webhelper->MakeSelectedValue("1", $type, "checked")?> onclick="goUrl('1');" id="category-specs"/> <label for="category-specs">Specification Sheets</label></div>
                    </li>
                    <li>
                        <div class="checkbox-set"><input type="radio" name="type" value="0" <?=$webhelper->MakeSelectedValue("2", $type, "checked")?> onclick="goUrl('2');" id="category-guides"/> <label for="category-guides">User Guides</label></div>
                    </li>
                    <li>
                        <div class="checkbox-set"><input type="radio" name="type" value="0" <?=$webhelper->MakeSelectedValue("3", $type, "checked")?> onclick="goUrl('3');" id="category-site-prep"/> <label for="category-site-prep">Site Prep Guides</label></div>
                    </li>
                    <li>
                        <div class="checkbox-set"><input type="radio" name="type" value="0" <?=$webhelper->MakeSelectedValue("4", $type, "checked")?> onclick="goUrl('4');" id="category-protocols"/> <label for="category-protocols">Protocols</label></div>
                    </li>
                    <li>
                        <div class="checkbox-set"><input type="radio" name="type" value="0" <?=$webhelper->MakeSelectedValue("5", $type, "checked")?> onclick="goUrl('5');" id="category-inserts"/> <label for="category-inserts">Product Inserts</label></div>
                    </li>
                </ul>
            </div>
            <!-- //aside-category -->
        </aside>
        <!-- //contents-aside -->

        <div id="contents-section">
            <div class="contents-sorting">
                <ul>
                    <li class="<?=$webhelper->MakeSelectedValue("0", $type, "active")?>"><a href="javascript:goUrl('0');">All</a></li>
                    <li class="<?=$webhelper->MakeSelectedValue("1", $type, "active")?>"><a href="javascript:goUrl('1');">Spec Sheets</a></li>
                    <li class="<?=$webhelper->MakeSelectedValue("2", $type, "active")?>"><a href="javascript:goUrl('2');">User Guides</a></li>
                    <li class="<?=$webhelper->MakeSelectedValue("3", $type, "active")?>"><a href="javascript:goUrl('3');">Site Prep Guides</a></li>
                    <li class="<?=$webhelper->MakeSelectedValue("4", $type, "active")?>"><a href="javascript:goUrl('4');">Protocols</a></li>
                    <li class="<?=$webhelper->MakeSelectedValue("5", $type, "active")?>"><a href="javascript:goUrl('5');">Product Inserts</a></li>
                </ul>
            </div>
            <!-- //contents-sorting -->

            <div class="contents-title">
                <h2>Documentation</h2>
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
                            <div class="brochures-type">
                            <?php
                                if($row["type"] == 1)
                                {
                                    echo "Spec Sheet";
                                }else if($row["type"] == 2)
                                {
                                    echo "User Guide";
                                }else if($row["type"] == 3)
                                {
                                    echo "Site Prep Guide";
                                }else if($row["type"] == 4)
                                {
                                    echo "Protocol";
                                }else if($row["type"] == 5)
                                {
                                    echo "Product Insert";
                                }
                            ?>
                            </div>
                            <div class="brochures-thumb"><img src="/files/document/<?=$row["thumbnail1"]?>" alt="" width="276" height="356"/></div>
                            <div class="brochures-meta"><span><?=$webhelper->GetFileType($row["orgfilename"])?></span> <span><?=$webhelper->GetFileSize("/document/".$row["filename"])?></span></div>
                            <div class="brochures-title"><?=$row["title"]?></div>
                            <div class="brochures-desc"><?=$row["body"]?></div>
                            <div class="brochures-download"><a href="/include/download_document.php?pkid=<?=$row["pkid"]?>" class="button-big">download</a></div>
                        </div>
                    </li>
                    <?php
                            }
                        }
                    ?>
                </ul>
                
                <?=$webhelper->GetPageHtml($pageList, $page, "/support/documentation.php", $Parameter, $PageSize)?>
            </div>
            <!-- //brochures-list -->
        </div>
        <!-- //contents-section -->
    </div>
    <!-- //contents -->
</div>
<!-- //container -->
<?php include_once($_SERVER['DOCUMENT_ROOT'] . "/include/footer.php") ?>