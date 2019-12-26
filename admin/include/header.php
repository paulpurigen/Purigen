<?php
    $FirstPageCode = substr($PageCode, 0, 2);
    $SecodeCode = substr($PageCode, 2, 2);
    $ThirdCode = substr($PageCode, 4, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
    <meta name="viewport" content="user-scalable=yes, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, width=device-width, target-densitydpi=medium-dpi">
    <title>Admin Portal | Purigen Biosystems</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />
    <link rel="stylesheet" type="text/css" href="/admin/css/admin.css">
    <script src="/admin/scripts/jquery-1.11.3.min.js"></script>
    <script src="/admin/scripts/jquery.easing.1.3.min.js"></script>
    <script src="/admin/scripts/admin.ui.js"></script>
</head>
<body>

<div id="wrap">
    <div id="header">

        <div class="header_top">
            <h1><a href="/">Purigen Biosystems</a></h1>
            <div class="header_log">
                <a href="/admin/logout.php" class="bt bt-navy">LOGOUT</a>
            </div>
            <!-- //header_log -->
            <div class="header_greeting">
                You are logged in as <strong><?=$webhelper->GetAdminLoginName()?></strong>
            </div>
            <!-- //header_greeting -->
        </div>
        <!-- //header_top -->

        <div class="header_gnb">
            <ul class="header_gnb_1depth l_clear">
                <li class="gnb-manage <?=$FirstPageCode == "01" ? "is-active" : ""?>"><a href="/admin/manager/list.php">MANAGE</a></li>
                <li class="gnb-resources <?=$FirstPageCode == "02" ? "is-active" : ""?>"><a href="/admin/resources/literature/list.php">RESOURCES</a></li>
                <li class="gnb-support <?=$FirstPageCode == "04" ? "is-active" : ""?>"><a href="/admin/support/document/list.php">SUPPORT</a></li>
                <li class="gnb-news <?=$FirstPageCode == "03" ? "is-active" : ""?>"><a href="/admin/news/media/list.php">NEWS / EVENTS</a></li>
            </ul>

            <div class="header_gnb_2depth">
                <div class="header_gnb_2depth_inner header_gnb_2depth-manage l_clear">
                    <ul>
                        <li><a href="/admin/manager/list.php">Manager Accounts</a></li>
                        <li><a href="/admin/webforms/request_information/list.php">Request Information</a></li>
                        <li><a href="/admin/webforms/mailing/list.php">Newsletter Sign-up</a></li>
                    </ul>
                </div>
                <!-- ///header_gnb_2depth-manage -->

                <div class="header_gnb_2depth_inner header_gnb_2depth-resources l_clear">
                    <ul>
                        <li><a href="/admin/resources/literature/list.php">Literature</a></li>
                        <li><a href="/admin/news/video/list.php">Videos</a></li>
                    </ul>
                </div>
                <!-- ///header_gnb_2depth-resources -->
                
                <div class="header_gnb_2depth_inner header_gnb_2depth-support l_clear">
                    <ul>
                        <li><a href="/admin/support/document/list.php">Documentation</a></li>
                    </ul>
                </div>
                <!-- ///header_gnb_2depth-support -->

                <div class="header_gnb_2depth_inner header_gnb_2depth-news l_clear">
                    <ul>
                        <li><a href="/admin/news/media/list.php">News</a></li>
                        <li><a href="/admin/news/events/list.php">Events</a></li>
                    </ul>
                </div>
                <!-- ///header_gnb_2depth-news -->
                
            </div>
            <!-- //header_gnb_sub -->
        </div>
        <!-- //header_gnb -->
    </div>
    <!-- //header-->
    