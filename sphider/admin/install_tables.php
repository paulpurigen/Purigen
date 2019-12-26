<?php

    //error_reporting(E_ALL);
    if (!defined("_SECURE")) {
        define("_SECURE",1);    // define secure constant
    }

    include "admin_header.php";
    //include "$settings_dir/database.php";

	//	read all database configuration values
	$data = file("$settings_dir/database.php");
//echo "\r\n\r\n<br>Array:<br><pre>";print_r($data);echo "</pre>\r\n";

				preg_match("@\"(.*?)\"@si",$data[18], $meet);
	$db_count	= $meet[1];
				preg_match("@\"(.*?)\"@si",$data[21], $meet);
	$dba_act	= $meet[1];
				preg_match("@\"(.*?)\"@si",$data[24], $meet);
	$dbu_act	= $meet[1];
				preg_match("@\"(.*?)\"@si",$data[27], $meet);
	$dbs_act	= $meet[1];
				preg_match("@\"(.*?)\"@si",$data[30], $meet);
	$db1_slv	= $meet[1];
				preg_match("@\"(.*?)\"@si",$data[31], $meet);
	$db2_slv	= $meet[1];
				preg_match("@\"(.*?)\"@si",$data[32], $meet);
	$db3_slv	= $meet[1];
				preg_match("@\"(.*?)\"@si",$data[33], $meet);
	$db4_slv	= $meet[1];
				preg_match("@\"(.*?)\"@si",$data[34], $meet);
	$db5_slv	= $meet[1];

				preg_match("@\'(.*?)\'@si",$data[43], $meet);
	$database1	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[46], $meet);
	$mysql_user1	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[49], $meet);
	$mysql_password1	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[52], $meet);
	$mysql_host1	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[55], $meet);
	$mysql_table_prefix1 	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[58], $meet);
	$db1_set	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[61], $meet);
	$db1_act	= $meet[1];

				preg_match("@\'(.*?)\'@si",$data[69], $meet);
	$database2	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[72], $meet);
	$mysql_user2	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[75], $meet);
	$mysql_password2	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[78], $meet);
	$mysql_host2	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[81], $meet);
	$mysql_table_prefix2	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[84], $meet);
	$db2_set	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[87], $meet);
	$db2_act	= $meet[1];

				preg_match("@\'(.*?)\'@si",$data[95], $meet);
	$database3	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[98], $meet);
	$mysql_user3	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[101], $meet);
	$mysql_password3	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[104], $meet);
	$mysql_host3	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[107], $meet);
	$mysql_table_prefix3	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[110], $meet);
	$db3_set	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[113], $meet);
	$db3_act	= $meet[1];

				preg_match("@\'(.*?)\'@si",$data[121], $meet);
	$database4	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[124], $meet);
	$mysql_user4	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[127], $meet);
	$mysql_password4	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[130], $meet);
	$mysql_host4	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[133], $meet);
	$mysql_table_prefix4	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[136], $meet);
	$db4_set	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[139], $meet);
	$db4_act	= $meet[1];

				preg_match("@\'(.*?)\'@si",$data[147], $meet);
	$database5	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[150], $meet);
	$mysql_user5	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[153], $meet);
	$mysql_password5	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[156], $meet);
	$mysql_host5	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[159], $meet);
	$mysql_table_prefix5	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[162], $meet);
	$db5_set	= $meet[1];
				preg_match("@\'(.*?)\'@si",$data[165], $meet);
	$db5_act	= $meet[1];
//$line = __LINE__;echo "\r\n<br>$line GET Array:<br><pre>";print_r($_GET);echo "</pre>";
    $f  = '0';
    $db_num = '1';
    if (isset($_GET['f']))
    $f = $_GET['f'];
    if (isset($_GET['db_num']))
    $db_num = $_GET['db_num'];

    if ($db_num == '1') {
        $mysql_table_prefix = $mysql_table_prefix1;
        $db_con = idb_connect($mysql_host1, $mysql_user1, $mysql_password1, $database1);
    }
    if ($db_num == '2') {
        $mysql_table_prefix = $mysql_table_prefix2;
        $db_con = idb_connect($mysql_host2, $mysql_user2, $mysql_password2, $database2);
    }
    if ($db_num == '3') {
        $mysql_table_prefix = $mysql_table_prefix3;
        $db_con = idb_connect($mysql_host3, $mysql_user3, $mysql_password3, $database3);
    }
    if ($db_num == '4') {
        $mysql_table_prefix = $mysql_table_prefix4;
        $db_con = idb_connect($mysql_host4, $mysql_user4, $mysql_password4, $database4);
    }
    if ($db_num == '5') {
        $mysql_table_prefix = $mysql_table_prefix5;
        $db_con = idb_connect($mysql_host5, $mysql_user5, $mysql_password5, $database5);
    }

    //  check for 4 byte string support of MySQL server
    $character_set  = "utf8";
    $collation      = "utf8_bin";
    $vers = $db_con->server_info;

    $vers = str_replace(".", "", substr($vers, 0, 5));

    //  starting with version 5.5.3 MySQL server supports 4 byte strings
    if (trim($vers) >= "553") {
        $character_set  = "utf8mb4";
        $collation = "utf8mb4_bin";
    }

    echo "    <h1>Sphider-plus installation script to create all tables for database ".$db_num."</h1>
            <p>&nbsp;</p>
            ";
    if ($f == '0') {
        echo "<div id='settings'>
                <form class='cntr' name='form10' method='post' action='db_config.php?p=98'>
                    <fieldset>
                        <label for='submit'class='sml'>
                            Up to now, nothing worth happened.<br />
                            If you want to return without installing the tables:
                        </label>
                        <br />
                        <input class='sbmt' type='submit' value='Cancel' id='submit' title='Click once to return to database settings'/>
                    </fieldset>
                </form>
                <br />
                <form class='cntr' name='form11' method='post' action='install_tables.php?f=1&db_num=".$db_num."'>
                    <fieldset>
                        <label for='submit'class='sml'>
                            If you really want to create all tables for database ".$db_num."<br /><br />
                            <span class='red'>Attention:</span> Already existing tables with the prefix <strong>'".$mysql_table_prefix."'</strong> <br />
                            will be destroyed and the content of all tables will be lost !<br />
                            Also the default configuration will be placed into<br />
                            the 'Settings' menue for this set of tables!
                        </label>
                        <br /><br /><br /><input class='sbmt' type='submit' value='Install now' id='submit' title='Click once to install all tables' />
                    </fieldset>
                </form>
                <br />
            </div>
        </body>
    </html>
            ";
    } else {

        $sql_query = "DROP TABLE IF EXISTS `".$mysql_table_prefix."addurl`";
        $res = query_this($sql_query, $db_con);

        $sql_query = "CREATE TABLE IF NOT EXISTS `".$mysql_table_prefix."addurl`(
                          url varchar(1024) not null,
                          title varchar(255),
                          description varchar(255),
                          category_id int(11),
                          account varchar(255),
                          authent varchar(255),
                          created timestamp NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP)
                          ENGINE=InnoDB ROW_FORMAT=COMPRESSED CHARSET=$character_set COLLATE=$collation
                        ";
        $res = query_this($sql_query, $db_con);

        $sql_query = "DROP TABLE IF EXISTS `".$mysql_table_prefix."banned`";
        $res = query_this($sql_query, $db_con);

        $sql_query = "CREATE TABLE IF NOT EXISTS `".$mysql_table_prefix."banned` (
                          domain varchar(1024) not null,
                          created timestamp NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP)
                          ENGINE=InnoDB ROW_FORMAT=COMPRESSED CHARSET=$character_set COLLATE=$collation
                        ";
        $res = query_this($sql_query, $db_con);

        $sql_query = "DROP TABLE IF EXISTS `".$mysql_table_prefix."real_log`";
        $res = query_this($sql_query, $db_con);

        $sql_query = "CREATE TABLE IF NOT EXISTS `".$mysql_table_prefix."real_log`(
                          url varchar(1024) not null,
                          real_log mediumtext,
                          refresh integer not null primary key,
                          created timestamp NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP)
                          ENGINE=InnoDB ROW_FORMAT=COMPRESSED CHARSET=$character_set COLLATE=$collation
                        ";
        $res = query_this($sql_query, $db_con);

        $sql_query = "DROP TABLE IF EXISTS `".$mysql_table_prefix."sites`";
        $res = query_this($sql_query, $db_con);

        $sql_query = "CREATE TABLE IF NOT EXISTS `".$mysql_table_prefix."sites`(
                          site_id int auto_increment not null primary key,
                          url varchar(1024),
                          title varchar(255),
                          short_desc text,
                          indexdate date,
                          spider_depth int default -1,
                          required text not null,
                          disallowed text not null,
                          can_leave_domain bool,
                          db bool,
                          smap_url varchar(1024),
                          authent varchar(255),
                          use_prefcharset int default 0,
                          prior_level int default 1)
                          ENGINE=InnoDB ROW_FORMAT=COMPRESSED CHARSET=$character_set COLLATE=$collation
                        ";
        $res = query_this($sql_query, $db_con);

        $sql_query = "DROP TABLE IF EXISTS `".$mysql_table_prefix."links`";
        $res = query_this($sql_query, $db_con);

        $sql_query = "DROP TABLE IF EXISTS `".$mysql_table_prefix."links`";
        $res = query_this($sql_query, $db_con);

        $sql_query = "CREATE TABLE IF NOT EXISTS `".$mysql_table_prefix."links` (
                          link_id int auto_increment primary key not null,
                          site_id int,
                          url varchar(1024) not null,
                          title varchar(255),
                          description varchar(255),
                          fulltxt mediumtext,
                          indexdate date,
                          size float(2),
                          md5sum varchar(32),
                          visible int default 0,
                          level int,
                          click_counter INT NULL DEFAULT 0,
                          last_click INT NULL DEFAULT 0,
                          last_query VARCHAR(255),
                          ip varchar(255),
                          relo_count integer,
                          webshot MEDIUMBLOB)
                          ENGINE=InnoDB ROW_FORMAT=COMPRESSED CHARSET=$character_set COLLATE=$collation
                        ";
        $res = query_this($sql_query, $db_con);

        $sql_query = "DROP TABLE IF EXISTS `".$mysql_table_prefix."keywords`";
        $res = query_this($sql_query, $db_con);

        $sql_query = "CREATE TABLE IF NOT EXISTS `".$mysql_table_prefix."keywords`	(
                          keyword_id int primary key not null auto_increment,
                          keyword varchar(255) not null)
                          ENGINE=InnoDB ROW_FORMAT=COMPRESSED CHARSET=$character_set COLLATE=$collation
                        ";
        $db_con->query($sql_query);
        $res = query_this($sql_query, $db_con);

        for ($i=0;$i<=15; $i++) {
            $char = dechex($i);
            $sql_query = "DROP TABLE IF EXISTS `".$mysql_table_prefix."link_keyword$char`";
            $res = query_this($sql_query, $db_con);

            $sql_query = "CREATE TABLE IF NOT EXISTS `".$mysql_table_prefix."link_keyword$char` (
                              link_id int not null,
                              keyword_id int not null,
                              weight int(3),
                              domain int(4),
                              hits int(3),
                              indexdate datetime,
                              key linkid(link_id))
                              ENGINE=InnoDB ROW_FORMAT=COMPRESSED CHARSET=$character_set COLLATE=$collation
                            ";
            $res = query_this($sql_query, $db_con);
        }

        $sql_query = "DROP TABLE IF EXISTS `".$mysql_table_prefix."link_details`";
        $res = query_this($sql_query, $db_con);

        $sql_query = "CREATE TABLE IF NOT EXISTS `".$mysql_table_prefix."link_details` (
                          link_id int not null,
                          link_cont varchar(1024),
                          url varchar(1024),
                          title varchar(255),
                          indexdate datetime,
                          domain varchar(1024))
                          ENGINE=InnoDB ROW_FORMAT=COMPRESSED CHARSET=$character_set COLLATE=$collation
                        ";
        $res = query_this($sql_query, $db_con);

        $sql_query = "DROP TABLE IF EXISTS `".$mysql_table_prefix."categories`";
        $res = query_this($sql_query, $db_con);

        $sql_query = "CREATE TABLE IF NOT EXISTS `".$mysql_table_prefix."categories` (
                          category_id integer not null auto_increment primary key,
                          category text,
                          parent_num integer,
                          group_sel0 text,
                          group_sel1 text,
                          group_sel2 text,
                          group_sel3 text,
                          group_sel4 text)
                          ENGINE=InnoDB ROW_FORMAT=COMPRESSED CHARSET=$character_set COLLATE=$collation
                        ";
        $res = query_this($sql_query, $db_con);

        $sql_query = "DROP TABLE IF EXISTS `".$mysql_table_prefix."site_category`";
        $res = query_this($sql_query, $db_con);

        $sql_query = "CREATE TABLE IF NOT EXISTS `".$mysql_table_prefix."site_category` (
                          site_id integer,
                          category_id integer)
                          ENGINE=InnoDB ROW_FORMAT=COMPRESSED CHARSET=$character_set COLLATE=$collation
                        ";
        $res = query_this($sql_query, $db_con);

        $sql_query = "DROP TABLE IF EXISTS `".$mysql_table_prefix."temp`";
        $res = query_this($sql_query, $db_con);

        $sql_query = "CREATE TABLE IF NOT EXISTS `".$mysql_table_prefix."temp` (
                          link varchar(1024),
                          level integer,
                          id varchar (32),
                          relo_link varchar(1024),
                          relo_count integer)
                          ENGINE=InnoDB ROW_FORMAT=COMPRESSED CHARSET=$character_set COLLATE=$collation
                        ";
        $res = query_this($sql_query, $db_con);

        $sql_query = "DROP TABLE IF EXISTS `".$mysql_table_prefix."pending`";
        $res = query_this($sql_query, $db_con);

        $sql_query = "CREATE TABLE IF NOT EXISTS `".$mysql_table_prefix."pending` (
                          site_id integer,
                          temp_id varchar(32),
                          level integer,
                          count integer,
                          num integer)
                          ENGINE=InnoDB ROW_FORMAT=COMPRESSED CHARSET=$character_set COLLATE=$collation
                        ";
        $res = query_this($sql_query, $db_con);

        $sql_query = "DROP TABLE IF EXISTS `".$mysql_table_prefix."query_log`";
        $res = query_this($sql_query, $db_con);

        $sql_query = "CREATE TABLE IF NOT EXISTS `".$mysql_table_prefix."query_log` (
                          query varchar(255),
                          time timestamp,
                          elapsed float(2),
                          results int,
                          ip varchar(255),
                          media int)
                          ENGINE=InnoDB ROW_FORMAT=COMPRESSED CHARSET=$character_set COLLATE=$collation
                        ";
        $res = query_this($sql_query, $db_con);

        $sql_query = "DROP TABLE IF EXISTS `".$mysql_table_prefix."domains`";
        $res = query_this($sql_query, $db_con);

        $sql_query = "CREATE TABLE IF NOT EXISTS `".$mysql_table_prefix."domains` (
                          domain_id int auto_increment primary key not null,
                          domain varchar(1024))
                          ENGINE=InnoDB ROW_FORMAT=COMPRESSED CHARSET=$character_set COLLATE=$collation
                        ";
        $res = query_this($sql_query, $db_con);


        $sql_query = "DROP TABLE IF EXISTS `".$mysql_table_prefix."media`";
        $res = query_this($sql_query, $db_con);

        $sql_query = "CREATE TABLE IF NOT EXISTS `".$mysql_table_prefix."media` (
                          media_id int auto_increment not null primary key,
                          link_id int(11) NOT NULL,
                          link_addr varchar(1024) COLLATE $collation DEFAULT NULL,
                          media_link varchar(1024) COLLATE $collation DEFAULT NULL,
                          thumbnail MEDIUMBLOB,
                          title varchar(255) COLLATE $collation NOT NULL,
                          type varchar(255) COLLATE $collation NOT NULL,
                          size_x int(11) NOT NULL,
                          size_y int(11) NOT NULL,
                          click_counter int(11) DEFAULT '0',
                          last_click int(11) DEFAULT '0',
                          last_query varchar(255) COLLATE $collation DEFAULT NULL,
                          id3 mediumtext COLLATE $collation NOT NULL,
                          md5sum varchar(32),
                          name varchar(1024),
                          suffix varchar(32),
                          ip varchar(255))
                          ENGINE=InnoDB ROW_FORMAT=COMPRESSED CHARSET=$character_set  COLLATE=$collation
                        ";
        $res = query_this($sql_query, $db_con);

        //  check offset between MySQL clock and PHP clock
        //  yes, we've seen differences of up to 2 hours on some server . . .
        $remote_addr = '0.1.2.3';
        $sql_query = "INSERT into ".$mysql_table_prefix."query_log (query, time, elapsed, results, ip, media)
                                                            values ('sphider-plus', NOW(), '1', '2', '$remote_addr', '0')";
        $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }

        $sql_query = "SELECT * from ".$mysql_table_prefix."query_log where ip = '$remote_addr' order by time desc  limit 0,10 ";
        $result = $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }

        $row = $result->fetch_array(MYSQLI_NUM);
        $queried1   = $row[1];
        $queried2   = retMktimest($row[1]);
        $test1      = time();
        $test2      = date("Y-m-d H:i:s");
        $diff       = $test1-$queried2;
/*
		echo "\r\n\r\n<br>mysqli row array:<br><pre>";print_r($row);echo "</pre>\r\n";
        echo "\r\n\r\n<br /> MySQL date/time: '$queried1'<br />\r\n";
        echo "\r\n\r\n<br /> PHP date/time: &nbsp;&nbsp;&nbsp;&nbsp;'$test2'<br />\r\n";
        echo "\r\n\r\n<br /> MySQL(as UNIX timestamp) '$queried2'<br />\r\n";
        echo "\r\n\r\n<br /> PHP: (as UNIX timestamp) &nbsp;&nbsp;'$test1'<br />\r\n";
        echo "\r\n\r\n<br /> Difference '$diff'<br />\r\n";
*/
        $sql_query = "TRUNCATE ".$mysql_table_prefix."query_log";
        $result = $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }

        if ($diff > "1") {
            echo "<p class='red em cntr'><br /><br />Attention: Sphider-plus recognized a server problem (clocks asynchronous).<br /><br /><br /></p>
                <p class='red em'><br />&nbsp;&nbsp;&nbsp;A test to read the MySQL database <span class='blue'>$db_num</span> answers at:&nbsp;&nbsp;<span class='blue'>$queried1</span><br />
                <br />&nbsp;&nbsp;&nbsp;Your PHP server (UNIX time stamp) responds with:&nbsp;&nbsp;<span class='blue'>$test2</span><br /><br />
                <br />&nbsp;&nbsp;&nbsp;Sphider-plus is unable to continue with a difference of <span class='blue'>$diff</span> seconds between MySQL and PHP clocks.<br />
                <br />&nbsp;&nbsp;&nbsp;Please contact your system administrator, and ask him to synchronize both clocks.<br />
                <br />&nbsp;&nbsp;&nbsp;Script execution aborted.<br /><br /></p>
    </body>
</html>
            ";

            $sql_query = "DROP TABLE    `".$mysql_table_prefix."addurl`, `".$mysql_table_prefix."banned`, `".$mysql_table_prefix."categories`, `".$mysql_table_prefix."domains`,
                                        `".$mysql_table_prefix."keywords`, `".$mysql_table_prefix."links`, `".$mysql_table_prefix."link_details`, `".$mysql_table_prefix."link_keyword0`,
                                        `".$mysql_table_prefix."link_keyword1`, `".$mysql_table_prefix."link_keyword2`, `".$mysql_table_prefix."link_keyword3`, `".$mysql_table_prefix."link_keyword4`,
                                        `".$mysql_table_prefix."link_keyword5`, `".$mysql_table_prefix."link_keyword6`, `".$mysql_table_prefix."link_keyword7`, `".$mysql_table_prefix."link_keyword8`,
                                        `".$mysql_table_prefix."link_keyword9`, `".$mysql_table_prefix."link_keyworda`, `".$mysql_table_prefix."link_keywordb`, `".$mysql_table_prefix."link_keywordc`,
                                        `".$mysql_table_prefix."link_keywordd`, `".$mysql_table_prefix."link_keyworde`, `".$mysql_table_prefix."link_keywordf`, `".$mysql_table_prefix."media`,
                                        `".$mysql_table_prefix."pending`, `".$mysql_table_prefix."query_log`, `".$mysql_table_prefix."real_log`, `".$mysql_table_prefix."sites`,
                                        `".$mysql_table_prefix."site_category`, `".$mysql_table_prefix."temp`";

            $res = query_this($sql_query, $db_con);
            die (); //  unable to continue
        }

        //  now copy the default configuration to the according settings folder for this db
        $source = "./settings/backup/Sphider-plus_default-configuration.php";
        $desti  = "".$settings_dir."/db".$db_num."/conf_".$mysql_table_prefix.".php";

		if (!file_exists($source)) {
			echo "<p class='red em cntr'>&nbsp;</p>";
			echo "<p class='red em cntr'>The file '$source' does not exist.</p>";
			echo "<p class='red em cntr'>&nbsp;</p>";
			echo "<p class='red em cntr'>Script execusion aborted.</p>";
			echo "<p class='red em cntr'>&nbsp;</p>
    </body>
</html>
			";
            die (); //  unable to proceed
		}

        if (!copy($source, $desti)) {   //  in case that copying of configuration failed
            echo "<p class='warnok em cntr'>
                Creating of tables successfully completed.</p>
                <br /><br />
                <p class='red em cntr'><br />Copying of default configuration failed.</p>
                <p class='red em cntr'><br />Unable to proceed with the configuration file and its destination.<br /><br /></p>
                <br />
        <br /><br /><br />
    </body>
</html>
            ";
            die (); //  unable to proceed
        }

        //  successfully created the tables and the configuration file
        echo "<p class='warnok em cntr'>Creating of database$db_num table set <span class='red'>$mysql_table_prefix</span> and default configuration successfully completed.</p>
        <br /><br /><br />";

        echo "
            <div id='settings'>
                <form class='cntr txt' name='form10' method='post' action='db_config.php?p=98'>
                    <fieldset>
                        <label for='submit'class='sml'>
                            Return to Database configuration:
                        </label>
                        <input class='sbmt' type='submit' value='Config' id='submit' title='Click once to return to Database configuration'/>
                    </fieldset>
                </form>
            </div>

            <br />
        </body>
    </html>
                    ";
    }

    // Error handler
    function query_this($sql_query, $db_con) {
        global $debug;

        $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }
        return ($db_con);
    }

    // Database1-5 connection
    function idb_connect($mysql_host, $mysql_user, $mysql_password, $database) {
        global $debug;

        $db_con = @new mysqli($mysql_host, $mysql_user, $mysql_password, $database);
        /* check connection */
        if ($db_con->connect_errno) {
            echo "<p>&nbsp;</p>
            <p><p class='warnadmin cntr'><br />&nbsp;No valid database found to start up.<br />&nbsp;Configure at least one database.<br /><br />
            <p>&nbsp;</p>
            ";

        }

        /* define character set to utf8 */
        if (!$db_con->set_charset("utf8")) {
            printf("Error loading character set utf8: %s\n", $db_con->error);

            /* Print current character set */
            $charset = $db_con->character_set_name();
            printf ("<br />Current character set is: %s\n", $charset);

            $db_con->close();
            exit;
        }

        //  get MySQL server version
        $vers = $db_con->server_info;
        $vers = str_replace(".", "", substr($vers, 0, 6));

        //  starting with version 5.5.14 MySQL server supports innodb_large_prefix
        if (trim($vers) >= "5514") {
            $sql_query = "set global innodb_file_per_table=TRUE";
            $db_con->query($sql_query); //  try it

            if (!$db_con->errno) { //  may not work on 'Shared Hosting' servers

                $sql_query = "set global innodb_file_format = BARRACUDA";
                $db_con->query($sql_query);

                $sql_query = "set global innodb_large_prefix = ON";
                $db_con->query($sql_query);

            }
        }

        return ($db_con);
    }

?>