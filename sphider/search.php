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
/*
/********************************************************************
 *
 *       The following 'include' contains some start-up variables.
 *
 *       Usually these variables are not needed to be modified.
 *       Modifications are only required for applications,
 *       which should overwrite the Admin settings
 */
    include_once "search_ini.php";
/*
 *******************************************************************
 *
 *       The following 'include' contains the HTML header.
 *
 *       For embedded application, only the Sphider-plus significant
 *       part of the HTML header will be added. So, in this case,
 *       be aware to place this 'include' inside of your HTML header.
 *       For embedded application it is mandatory to active the according
 *       setting in Admin backend.
 *
 */
    include_once "$include_dir/search_10.php";
/*
 *******************************************************************
 *
 *       The following 'include' contains the 'NoJavaScript' warning message,
 *       as well as the headline presented on top of the Search form.
 */
    include_once "$include_dir/search_20.php";
/*
 *******************************************************************
 *
 *       The following 'include' will add the 'Search form'
 *       and, if requested, also the advanced options, as well as categories, etc.
 */
    include_once "$include_dir/search_30.php";
/*
 *******************************************************************
 *
 *       The following 'include' will add the result listings
 *       for text and media search, link search and all other search modes.
 */
    include_once "$include_dir/search_40.php";
/*
 *******************************************************************
 *
 *       The following 'include' contains the
 *       - Form for 'Suggest a new URL'
 *       - Link to Sphider-plus project page
 *       - HTML end tags
 *
 *       For embedded application, only the Sphider-plus significant
 *       part of the footer will be added. So, in this case,
 *       be aware to place this 'include' above your
 *       </body> and </html> tags.
 */
    include_once "$include_dir/search_50.php";
/*
 *****************    End of Sphider-plus scripts     ********************/
?>