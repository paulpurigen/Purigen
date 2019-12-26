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
$sql = "select firstname from request_information where pkid = $pkid";
$ViewData = $dbhelper->RunSQLReturnOneRow($sql);
$dbhelper->dbClose();
?><head>    
<title>Thank You | Purigen Biosystems</title>
<meta name="description" content="Thank you for contacting us at Purigen Biosystems." />
    
<!-- Event snippet for Purigen Web Form conversion page -->
<script>
  gtag('event', 'conversion', {'send_to': 'AW-718871573/dvb3COuSv7IBEJW45NYC'});
</script>
    
<!-- Event snippet for Purigen Web Form conversion page -->
<script>
  gtag('event', 'conversion', {'send_to': 'AW-718871573/dvb3COuSv7IBEJW45NYC'});
</script>

<?php include_once($_SERVER['DOCUMENT_ROOT'] . "/include/header.php") ?>
</head>

<div id="container">
    <div class="contents-visual contents-visual-complete">
        <article class="title-type">
            <h2>Thank you, <?=$ViewData["firstname"]?>.</h2>
            <p style="font-size:18px; padding-top:10px">Thank you for your interest in Purigen. We have received your request. <br/>One of our representative will get back to you shortly.</p>
        </article>
    </div>
    <!-- //contents-visual -->

    <div id="contents">
        <div class="download-brochure">
            <p>Download the Purigen Ionic System brochure to learn more.</p>
            <div class="brochures brochures-center">
                <div class="brochures-thumb"><img src="/images/thumb/thmb-purigen-ionic-system-brochure-01b.jpg" alt="Purigen Ionic Purification System Brochure" width="276px" height="356px"/></div>
                <div class="brochures-title">Purigen Ionic&trade; Purification System</div>
                <div class="brochures-download"><a href="/media/pdf/BRH-Purigen-Ionic-System.pdf" class="button-big">download</a></div>
            </div>
        </div>
        <!-- //download-brochure -->
    </div>
    <!-- //contents -->
</div>
<!-- //container -->
<?php include_once($_SERVER['DOCUMENT_ROOT'] . "/include/footer.php") ?>