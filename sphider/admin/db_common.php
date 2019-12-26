<?php

    function get_def($database, $table, $fp) {        //      get structure
        global $debug, $db_con, $delimiter;

        $def  = "TRUNCATE TABLE $table $delimiter\n";
        $def .= "\r\n";
        gzwrite ($fp,$def);         //      now write all prepared structur commands into backup file
    }

    function get_content($database, $table, $fp) {        //      get content of data and write into gz file
        global $debug, $db_con, $delimiter;

        $sql_query = "SELECT * FROM $table";
        $result = $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }

        $num_rows = $result->num_rows;
        $insert = '';
        if ($num_rows) {    //  insert only, if content is available
            for($j=0; $j<$num_rows; $j++) {  //      content for later SQL-restore
                $rows = $result->fetch_array(MYSQLI_ASSOC);
                $insert .= "INSERT INTO $table VALUES (";        //      command for later SQL-restore
                foreach($rows as $row) {
                    if(!isset($row)) {
                        $insert .= "NULL,";
                    } else {
                    $insert .= "'".$db_con->real_escape_string($row)."',";
                    }
                }
                $insert .= ") $delimiter\r\n";  //  end insert for this table field
            }

            $insert  = preg_replace("/,$/","",$insert);
            $insert .= "\r\n";
            $insert = str_replace("',) ", "') ", $insert);                    //  correct for proper SQL directive
            $insert = str_replace(",) ", ") ", $insert);                    //    correct for proper SQL directive
            $insert = str_replace (",\r\n;", "$delimiter\n\n", $insert);    //      include a row delimiter
            //$insert = stripslashes($insert);
            gzwrite ($fp,$insert);              //  now write the complete content into backup file
        }
    }

    function clear_TCache() {
        global $debug, $db_con, $textcache_dir;

        $count = '0';
        if ($handle = opendir($textcache_dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    @unlink("".$textcache_dir."/".$file."");
                    $count++;
                }
            }
        }
        return ($count);
    }

    function clear_MCache() {
        global $debug, $db_con, $mediacache_dir;

        $count = '0';
        if ($handle = opendir($mediacache_dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    @unlink("".$mediacache_dir."/".$file."");
                    $count++;
                }
            }
        }
        return ($count);
    }

?>