<head>

<title>A Revolution in DNA Extraction | Purigen Biosystems</title>

<meta name="description" content="Purigen Biosystems is revolutionizing DNA extraction and nucleic acid purification for life science research with its novel isotachophoresis technology." />

<link rel="canonical" href="https://www.purigenbio.com/" />
<meta content="A Revolution in DNA Extraction | Purigen Biosystems" property="og:title">
<meta content="A Revolution in DNA Extraction | Purigen Biosystems" name="twitter:title">
<meta content="The Purigen Ionic™ Purification System is a new DNA extraction system that isolates pure, native, nucleic acid without the use of harsh lysis steps or chaotropic salts." property="og:description">
<meta content="The Purigen Ionic™ Purification System is a new DNA extraction system that isolates pure, native, nucleic acid without the use of harsh lysis steps or chaotropic salts." name="twitter:description">
<meta content="https://www.purigenbio.com/images/common/logo-purigen-social.jpg" property="og:image">
<meta content="https://www.purigenbio.com/images/common/logo-purigen-social.jpg" name="twitter:image">
<meta content="website" property="og:type">
<meta content="Purigen Biosystems | A Revolution in DNA Extraction" property="og:site_name">
<meta content="summary" name="twitter:card">
<meta content="@purigenbio" name="twitter:site">
<meta content="1600" property="og:image:width">
<meta content="400" property="og:image:height">

<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$today = date("Ymd");

$dbhelper->dbOpen();

$sql = "select pkid, year, month, day, maintitle, subtitle from media where status = 1 order by year desc, month desc, day desc limit 4 ";

$NewsList = $dbhelper->RunSQLReturnRows($sql);

$sql = "select pkid, type, startyear, startmonth, startday, endyear, endmonth, endday, maintitle, subtitle, url, location1, location2, booth, thumbnail1 
        from events where status = 1 and concat(endyear,endmonth,endday) >= '$today' order by startyear asc, startmonth asc, startday asc limit 4";
$EventList = $dbhelper->RunSQLReturnRows($sql);
$dbhelper->dbClose();

?>

<?php include_once($_SERVER['DOCUMENT_ROOT'] . "/include/header.php") ?>

</head>

<div id="container">

    <div class="main-visual" id="main-visual">
        <ul class="main-visual-slide" id="main-visual-slide">
            <li class="active">
                <video id="main-video1" playsinline autoplay muted="muted"><!--  loop> -->
                    <source src="/videos/vid-purigen-ionic-dark-bg-test-01a.mp4" type="video/mp4">
                </video>
                <article>
                    <h3>Purigen Biosystems</h3>
                    <h2>Nucleic Acid Purification – Pure and Simple&trade;</h2>
                    <p>The Ionic&trade; Purification System isolates pure, native, nucleic acid without the use of harsh lysis steps or chaotropic salts.</p>
                    <a href="/products/ionic-system.php"><i class="fas fa-chevron-circle-right fa-2x"></i> &nbsp; LEARN MORE</a>
                </article>
            </li>
            <li>
                <video id="main-video2" playsinline muted="muted"><!--  loop> -->
                    <source src="/videos/vid-purigen-purification-02-1080-18sec.mp4" type="video/mp4">
                </video>
                <article>
                    <h3>No beads, columns, or surface binding</h3>
                    <h2>Simplified Charge-based DNA Extraction</h2>
                    <p>Isotachophoresis (ITP) separates and concentrates charged molecules in solution solely based on their electrophoretic mobility.</p>
                    <a href="/technology/isotachophoresis.php"><i class="fas fa-chevron-circle-right fa-2x"></i> &nbsp; LEARN MORE</a>
                </article>
            </li>
            <li>
                <video id="main-video3" playsinline muted="muted"><!--  loop> -->
                    <source src="/videos/vid-purigen-web-hero-05f-1080.mp4" type="video/mp4">
                </video>
                <article>
                    <h3>Visit our BOOTH 106 to see the</h3>
                    <h2>Ionic<span style="font-family: 'Forza-Light'; font-size: 28px;">&trade;</span> Purification System at ABRF</h2>
                    <p>Feb 29 – Mar 3 &nbsp; | &nbsp; Palm Springs, CA</p>
                    <a href="/news-events/events/list.php"><i class="fas fa-chevron-circle-right fa-2x"></i> &nbsp; LEARN MORE</a>
                </article>
            </li>
            <li>
                <video id="main-video4" playsinline muted="muted"><!--  loop> -->
                    <source src="/videos/vid-purigen-chips-hero-03h.mp4" type="video/mp4">
                </video>
                <article>
                    <h3>Purigen Kits</h3>
                    <h2>Simplify Your Purification Workflows</h2>
                    <p>Purigen protocols, fluidics chips and reagents maximize nucleic acid yield and quality with fewer manual steps.</p>
                    <a href="/products/ionic-kits.php"><i class="fas fa-chevron-circle-right fa-2x"></i> &nbsp; LEARN MORE</a>
                </article>
            </li>
        </ul>
        <div class="main-visual-page-wrapper">
            <i id="prev-click" onClick="prev_next_click(this.id)" class="fal fa-3x fa-angle-left"></i>
            <div class="main-visual-page">					
                <a href="#" class="active">slide 1</a>
                <a href="#">slide 2</a>
                <a href="#">slide 3</a>
                <a href="#">slide 4</a>					
            </div>
            <i id="next-click" onClick="prev_next_click(this.id)" class="fal fa-3x fa-angle-right"></i>
        </div>
        
        <script>
            function prev_next_click(clicked_id) {								
                if (clicked_id == "next-click") {
                    if ($('.active').next().length == 2) {
                        $('.active').next().click();
                    } else {
                        $('.main-visual-page a')[0].click();
                    }	
                } else if (clicked_id == "prev-click") {						
                    if ($('.active').prev().length == 0) {
                        $('.main-visual-page a')[3].click();							
                    } else {							
                        $('.active').prev().click();
                    }
                }
            };

            $(function() {
                $('video').on('ended',function(){
                    if ($('.active').next().length == 2) {
                        $('.active').next().click();
                    } else {
                        $('.main-visual-page a')[0].click();
                    }					      
                });

                $('.main-visual-page a').click(function(e) {
                    e.preventDefault();
                    let current = $(this).index();
                    let distance = current * $('#main-visual').width();
                    $(this).addClass('active').siblings().removeClass('active');
                    $('#main-visual-slide').css('transform', 'translateX(-'+ distance +'px)').find('video').each(function(i, el) {
                        if ($(el).attr('id') === 'main-video'+ parseInt(current+1) +'') {
                            $(el).trigger('play');
                        } else {
                            $(this).get(0).pause();
                            $(this).get(0).currentTime = 0;
                        }
                    });
                });
            });
        </script>
    </div>
    <!-- //main-visual -->

    <div class="feature-bar" style="background-color: #0f303f"><img src="images/svg/icon-purigen-white-square.svg" width="20" style="opacity:0.5; margin-right:10px"> &nbsp; VISIT US at ABRF 2020 &nbsp; - &nbsp; BOOTH 106 <span style="font-family:'Forza-Light'">&nbsp; - &nbsp; Palm Springs, CA &nbsp; - &nbsp; February 29 – March 3, 2020</div>

    <div class="main-product">
        <dl>
            <dt>Pure, Native, Nucleic Acid</dt>
            <dd>Biological samples are gently lysed without the use of chaotropic salts. Nucleic acids are not dehydrated or denatured.</dd>
        </dl>
        <dl>
            <dt>Higher Amplifiable Yields</dt>
            <dd>Extract up to 50% more nucleic acid from cultured cells. Achieve yields of 3.5x or more for FFPE samples versus column-based methods.</dd>
        </dl>
        <dl>
            <dt>No Beads, Columns, or Surface-binding</dt>
            <dd>Nucleic acids are purified solely based on electrophoretic mobility. Elimination of binding, washing, and stripping reduces sample loss and contamination.</dd>
        </dl>
        <dl>
            <dt>No Risk Downstream</dt>
            <dd>Purification is not biased toward nucleic acids of a certain length or GC content.</dd>
        </dl>
        <dl>
            <dt>Superior Workflow for FFPE</dt>
            <dd>Purification of nucleic acids from FFPE scrolls or slides requires less than 3 minutes of hands-on time per sample (lysis included). No chemical deparaffinization is required.</dd>
        </dl>
        <dl>
            <dt>Automated Nucleic Acid Purification</dt>
            <dd>Nucleic acids are automatically separated and concentrated from sample lysate on the Purigen system in 60 minutes.</dd>
        </dl>
    </div>
    <!-- //main-product -->

    <div class="main-movie">
        <div class="main-movie-inner">
            <h2>See the Purigen Technology in Action</h2>
            <p>
                The application of ITP to purify and quantify nucleic acids from complex biological samples was pioneered by Purigen co-founder and Stanford University Professor Juan G. Santiago. The Ionic Purification System is compatible with a range of samples including cells and FFPE.
            </p>
            <div class="main-movie-section">

                <iframe class="elementor-video-iframe" allowfullscreen="" src="https://www.youtube.com/embed/C9v9O0egC54?feature=oembed&amp;start&amp;end&amp;wmode=opaque&amp;loop=0&amp;controls=1&amp;mute=0&amp;rel=0&amp;modestbranding=0"></iframe>

            </div>
        </div>
    </div>
    <!-- //main-movie -->

    <div class="main-event">

        <h2>Upcoming Events</h2>
        <ul>
            <?php
                if($EventList != null && count($EventList)>0)
                {
                    foreach ($EventList as $row)
                    {
            ?>
            <li>
                <a href="<?=$row["url"]?>" target="_blank">

                    <div class="event-thumb"><img src="/files/events/<?=$row["thumbnail1"]?>" width="150" height="90" alt="Purigen Events" style="border: solid 3px #efefef"/></div>

                    <div class="event-date"><?=$webhelper->GetMonthShortName($row["startmonth"])?> <?=$row["startday"]?> – <?=$row["endday"]?>, <?=$row["startyear"]?></div>

                    <div class="event-title"><?=$row["maintitle"]?></div>
                    <div class="event-location"><?=$row["location1"]?></div>
                    <div class="event-section"><?=$row["booth"]?></div>
                </a>
            </li>

            <?php
                    }
                }
            ?>
        </ul>

        <div class="view-all"><a href="/news-events/events/list.php"><i class="fas fa-th-large"></i> &nbsp; VIEW ALL</a></div>

    </div>
    <!-- //main-event -->

    <div class="main-news">
        <h2>Latest News <br/> and Updates</h2>
        <dl>
            <dt>PRESS RELEASE</dt>
            <dd id="main-news-date"></dd>
        </dl>
        <article>
            <a href="javascript:void(0);" onclick="mainNewsPrev()"><!--<i class="fas fa-chevron-circle-left"></i>--></a>
            <p id="main-news-maintitle"></p>
            <<a href="javascript:void(0);" onclick="mainNewsNext()" id="mainNewsNext"><!--<i class="fas fa-chevron-circle-right"></i>--></a>
        </article>
        <div class="main-news-more"><a id="main-news-pkid" href="/news-events/news/view.php?pkid=<?=$row["pkid"]?>">READ MORE</a></div>
        
        <?php
            $month = array();
            $day = array();
            $year = array();
            $maintitle = array();
            $pkid = array();

            if($NewsList != null && count($NewsList)>0)
            {
                foreach ($NewsList as $row)
                {
                    array_push($month, $webhelper->GetMonthShortName($row["month"]));
                    array_push($day, $row["day"]);
                    array_push($year, $row["year"]);
                    array_push($maintitle, $row["maintitle"]);
                    array_push($pkid, $row["pkid"]);
                }
            }
        ?>
        
        <script type="text/javascript">                        
            var monthArray = <?php echo json_encode($month); ?>;
            var dayArray = <?php echo json_encode($day); ?>;
            var yearArray = <?php echo json_encode($year); ?>;
            var maintitleArray = <?php echo json_encode($maintitle); ?>;
            var pkidArray = <?php echo json_encode($pkid); ?>;            
            var idx = 0;
            
            mainNewsDisplay();

            var newsTimer = setInterval(autoClick, 5000);

            function autoClick()
            {
              $("#mainNewsNext").click();
            }

            function mainNewsPrev() {                
                clearInterval(newsTimer);newsTimer = setInterval(autoClick, 5000);
                idx--;mainNewsDisplay();return false;
            }

            function mainNewsNext() {
                clearInterval(newsTimer);newsTimer = setInterval(autoClick, 5000);
                idx++;mainNewsDisplay();return false;
            }
            function mainNewsDisplay() {                
                if (maintitleArray.length != 0) {
                    if (idx < 0) {
                        idx = maintitleArray.length - 1;
                    } else if (maintitleArray.length == idx || maintitleArray.length < idx) {
                        idx = 0;
                    }
                
                var newsDate = monthArray[idx] + " " + dayArray[idx] + ", " + yearArray[idx];
                var newsId = "/news-events/news/view.php?pkid=" + pkidArray[idx];

                document.getElementById("main-news-date").textContent=newsDate;
                document.getElementById("main-news-maintitle").textContent=maintitleArray[idx];
                document.getElementById("main-news-pkid").href=newsId;                 
                }
            }            
        </script>

        <div class="view-all"><a href="/news-events/news/list.php"><i class="fas fa-th-large"></i> &nbsp; VIEW ALL</a></div>
    </div>
    <!-- //main-news -->

    <div class="main-workflow">
        <h2>PURIGEN FFPE Workflow</h2>
        <p>Total hands-on time is 20 minutes per 8 samples</p>
        <div class="main-workflow-inner">
            <img src="/images/workflow-purigen-ffpe.svg" width="70%" alt="Purigen FFPE Workflow">
        </div>
        <i class="fas fa-chevron-left"></i>
        <i class="fas fa-chevron-right"></i>
    </div>
    <!-- //main-workflow -->

</div>

<!-- //container -->

<?php include_once($_SERVER['DOCUMENT_ROOT'] . "/include/footer.php") ?>