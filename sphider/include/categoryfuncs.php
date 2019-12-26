<?php

    function get_categories_view() {
        global $db_con, $mysql_table_prefix, $debug;

        $categories['main_list'] = sqli_fetch_all('SELECT * FROM '.$mysql_table_prefix.'categories WHERE parent_num=0 ORDER BY category');

        if (is_array($categories['main_list'])) {
            foreach ($categories['main_list'] as $_key => $_val) {
                $categories['main_list'][$_key]['sub'] =  sqli_fetch_all('SELECT * FROM '.$mysql_table_prefix.'categories WHERE parent_num='.$_val['category_id']);
            }
        }
        return $categories;
    }

    function get_category_info($catid) {
        global $db_con, $mysql_table_prefix, $debug, $use_edidesc, $min_title, $min_desc;

        $categories['main_list'] = sqli_fetch_all("SELECT * FROM ".$mysql_table_prefix."categories ORDER BY category");

        if (is_array($categories['main_list'])) {
            foreach($categories['main_list'] as $_val) {
                $categories['categories'][$_val['category_id']] = $_val;
                $categories['subcats'][$_val['parent_num']][] = $_val;
            }
        }

        $categories['subcats'] = $categories['subcats'][$_REQUEST['catid']];

        /* count sites */
        if (is_array($categories['subcats'])) {
            foreach ($categories['subcats'] as $_key => $_val) {
                $categories['subcats'][$_key]['count'] = sqli_fetch_all('SELECT count(*) FROM '.$mysql_table_prefix.'site_category WHERE 	category_id='.(int)$_val['category_id']);
            }
        }

        /* make tree */
        $_parent = $catid;
        while ($_parent) {
            $categories['cat_tree'][] = $categories['categories'][$_parent];
            $_parent = $categories['categories'][$_parent]['parent_num'];
        }
        $categories['cat_tree'] = array_reverse($categories['cat_tree']);


        /* list category sites */
        $categories['cat_sites'] = sqli_fetch_all('SELECT url, title, short_desc FROM '.$mysql_table_prefix.'sites, '.$mysql_table_prefix.'site_category WHERE category_id='.$catid.' AND '.$mysql_table_prefix.'sites.site_id='.$mysql_table_prefix.'site_category.site_id order by title');

        $count = '0';
        if ($categories['cat_sites'] != '') {
            foreach ($categories['cat_sites'] as $value) {
                $thistitle      = '';
                $admin_title    = '';
                $thisdescr      = '';
                $admin_desc     = '';
                $admin_title    = $categories['cat_sites'][$count]['title'];        // try to fetch title as defined in admin settings for this site
                $admin_desc     = $categories['cat_sites'][$count]['short_desc'];   // try to fetch description as defined in admin settings for this site

                $thisurl    = ($categories['cat_sites'][$count][0]);
                $sql_query  = "SELECT * from ".$mysql_table_prefix."links where url like '$thisurl%'";
                $result = $db_con->query($sql_query);
                if ($debug > 0 && $db_con->errno) {
                    $file       = __FILE__ ;
                    $function   = __FUNCTION__ ;
                    $err_row    = __LINE__-5;
                    mysql_fault($db_con, $sql_query, $file, $function, $err_row);
                }
                $num_rows = $result->num_rows;;

                if ($num_rows) {    //      hopefully the webmaster included some title and description into the site header
                    $thisrow    = $result->fetch_array(MYSQLI_NUM);
                    $thistitle  = $thisrow[3];
                    $thisdescr  = $thisrow[4];

                    //  if no title was found, use admin edited title
                    if (strlen($thistitle) < 2 && $admin_title) {
                        $thistitle = $admin_title;
                    } else {
                        //  use admin edited title, if word count did not match minimum
                        if ($admin_title && $min_title != -1) {
                            if (substr_count(trim($thistitle), " ")+1 < $min_title) {
                                $thistitle = $admin_title;
                            }
                        }
                        //  if admin selected, always use admin edited title
                        if ($use_edidesc == 1 && $admin_title) {
                            $thistitle = $admin_title;
                            //  alternately use both titles (comment the above row)
                            //$thistitle .= $admin_title;
                        }
                    }

                    if (strlen($thistitle) < 2 ) {
                        $thistitle = "No title available for this site.";
                    }

                    //  if no description was found, use admin edited desc
                    if (strlen($thisdescr) < 2 && $admin_desc) {
                        $thisdescr = $admin_desc;
                    } else {
                        //  use admin edited description, if word count did not match minimum
                        if ($admin_desc && $min_desc != -1) {
                            if (substr_count(trim($thisdescr), " ")+1 < $min_desc) {
                                $thisdescr = $admin_desc;
                            }
                        }
                        //  if admin selected, always use admin edited description
                        if ($use_edidesc == 1 && $admin_desc) {
                            $thisdescr = $admin_desc;
                            //  alternately use both descriptions (comment the above row)
                            //$thisdescr.= $admin_desc;
                        }
                    }
                    if (strlen($thisdescr) < 2 ) {
                        $thisdescr = "No description available for this site.";
                    }

                    //      now include HTML title and description into array, so we may output them
                    $categories['cat_sites'][$count][1] = $thistitle;
                    $categories['cat_sites'][$count]['title'] = $thistitle;
                    $categories['cat_sites'][$count][2] = $thisdescr;
                    $categories['cat_sites'][$count]['short_desc'] = $thisdescr;
                }

                $count++;
            }
        }
        return $categories;
    }

    function findcats($url, $category, $catidx, $mysql_table_prefix) {
        global $db_con, $debug, $local ;

        $allcats = array ();
        $catlist = array ();
        $host = parse_url(blank_url($url));
        $hostname = $host[host];

        //  rebuild domain for localhost applications
        if ($hostname == 'localhost') {
            $host1 = str_replace($local,'',$url);
        }
        $pos = strpos($host1, "/");         //      on local server delete all behind the /
        if ($pos) {
            $host1 = substr($host1,0,$pos); //      build full adress again, now only local domain
        }
        if ($hostname == 'localhost') {
            $url = ("".$local."".$host1."/");
        }else {
            $url = ("$host[scheme]://".$hostname."/");
        }

        //  find according site_id
        $sql_query = "SELECT site_id from ".$mysql_table_prefix."sites where url like '$url%'";
        $result = $db_con->query($sql_query);
        if ($debug > 0 && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }
        $row = $result->fetch_array(MYSQLI_NUM);
        $site_id = $row[0];

        //  find cat_id for this domain
        $sql_query = "SELECT * from ".$mysql_table_prefix."site_category where site_id like '$site_id'";
        $result = $db_con->query($sql_query);
        if ($debug > 0 && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }
        $rows = $result->num_rows;;

        //  find category names
        if ($result->num_rows) {
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                if ($category == '-1') {  //  find all categories according to this domain
                    $sql_query1 = "SELECT category from ".$mysql_table_prefix."categories where category_id like '$row[1]'";
                    $res = $db_con->query($sql_query1);
                } else {
                    $sql_query1 = "SELECT category from ".$mysql_table_prefix."categories where parent_num = '$catidx'";
                    $res = $db_con->query($sql_query1);   //  find only sub-categories
                }
                if ($debug > 0 && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-6;
            mysql_fault($db_con, $sql_query1, $file, $function, $err_row);
                }
                $cat = $res->fetch_array(MYSQLI_NUM);
                $allcats[] = $cat[0];     //  collect all categories
            }
        }
        $catlist = array_unique($allcats);
        sort($catlist);
        return $catlist;
    }

    function blank_url($url) {

        $url = str_replace("&amp;", "&", $url);
        $url = str_replace(" ", "%20", $url);
        return $url;
    }

?>