<?php

/*****************    Start of Sphider-plus scripts     ********************

 *

 *   Sphider-plus version 3.2019c created 2019.08.19

 *

 *   Based on original Sphider version 1.3.5

 *   released: 2009-12-13

 *   by Ando Saabas     http://www.sphider.eu

 *

 *   This program is licensed under the GNU GPL by:

 *    Rolf Kellner  [Tec]   tec@sphider-plus.eu

 *   Original Sphider GNU GPL licence by:

 *   Ando Saabas   ando(a t)cs.ioc.ee

 *

*

 *******************************************************************

 */

    //  for command line operation, correct the working directory

    $dir0 = str_replace('\\', '/', __DIR__);

    chdir($dir0);



    // define secure constant

    define("_SECURE",1);    // define secure constant



    include_once "search_ini.php";

    include_once "$include_dir/search_10.php";

?><head>    

<title>Search Results | Purigen Biosystems</title>

<meta name="description" content="Search results for Purigen Biosystems website." />

<?php

include_once($_SERVER['DOCUMENT_ROOT'] . "/include/header.php");

?>



<div id="container">							

    <div class="search-result">

        <div class="page-wrapper page-with-sidebar">

            <div class="page-inner" id="support-inner">

                <div id="left-content" class="support-no-hero search-page-content">

                    <section class="left-paragraph no-margin" id="search-page-form">

                        <form method="get" name="searchBodyFrm" id="support-form" action="/sphider/search-result.php">

                            <input type="hidden" name="search" value="1"/>

                           <fieldset id="search-field">

                               <input type="text" name="query_t" value="<?=$query_t?>" maxlength="100" />

                           </fieldset>

                            <div id="search-submit">

                               <a href="javascript:document.searchBodyFrm.submit();" class="btn green-btn bt-narrow">SEARCH</a>

                            </div>

                         </form>

                     </section>



                    <section class="left-paragraph" id="search-list">

                        <h3>SEARCH RESULTS</h3>

                        <div id="search-result-list">

<?php

    // file locaiton is  "/sphider/include/search_inc.php"

    include_once "$include_dir/search_inc.php";

?>

                        </div>

                    

                    <?php

                        if (isset($other_pages)) 

                        {

                            echo '<div class="pagination">';

                            if ($start > 1)

                            {

                               echo "<a href='$search_string?query_t="

                                  . (addmarks($query))

                                  . "&amp;search=1"

                                  . "&amp;start=$prev' class='prev'>"

                                  . $sph_messages['Previous']

                                  ."<i class='fa fa-chevron-left'></i></a>\n";

                            }

                            echo '<div class="numbers">';

                            foreach ($other_pages as $page_num)

                            {

                               if ($page_num != $start)

                               {

                                  echo "<a href='$search_string?query_t="

                                  . (addmarks($query))

                                  . "&amp;search=1"

                                  . "&amp;start=$page_num'>$page_num</a>\n";

                               }

                               else

                               {

                                  echo '<a href="#" class="is-active">'.$page_num.'</a>';

                               }

                            }

                            echo '</div>';

                            if ($next <= $pages)

                            {

                               echo "<a href='$search_string?query_t="

                                  . (addmarks($query))

                                  . "&amp;search=1"

                                  . "&amp;start=$next' class='next'>"

                                  . $sph_messages['Next']

                                  . "<i class='fa fa-chevron-right'></i></a>\n";

                            }

                            echo '</div>';

                        }

                    ?>

                        

                    </section>



                </div>

                <!-- left-content -->        

            </div>

            <!-- page-inner -->          

        </div>

    </div>

</div>

<!-- //container -->

<?php

include_once($_SERVER['DOCUMENT_ROOT'] . "/include/footer.php");

?>