<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

//Parameter
$encpkid = $webhelper->RequestFilter("pkid", -1, false);

//check parameter
if($webhelper->isNull($encpkid)) $webhelper->AlertMessage("Error. Please try again.");
$pkid = $webhelper->AESDecrypt256($encpkid);

$dbhelper->dbOpen();
$sql = "select firstname from email_list where pkid = $pkid";
$ViewData = $dbhelper->RunSQLReturnOneRow($sql);
$dbhelper->dbClose();
?><head>    
<title>Thank You | Purigen Biosystems</title>
<meta name="description" content="Thank you for signing up for our newsletter." />
<link rel="canonical" href="https://www.purigenbio.com/support/thank-you-request" />
<meta content="Thank You | Purigen Biosystems" property="og:title">
<meta content="Thank You | Purigen Biosystems" name="twitter:title">
<meta content="Thank you for signing up for our newsletter." property="og:description">
<meta content="Thank you for signing up for our newsletter." name="twitter:description">
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
    <div class="contents-visual contents-visual-newsletter-complete">
        <article class="title-type">
            <h2>Thank you, <?=$ViewData["firstname"]?>!</h2>
            <p style="font-size:18px; padding-top:10px">Thank you for signing up for our newsletter.</p>
        </article>
    </div>
    <!-- //contents-visual -->

    <!-- //contents -->
</div>
<!-- //container -->
<?php include_once($_SERVER['DOCUMENT_ROOT'] . "/include/footer.php") ?>