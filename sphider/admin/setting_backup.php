<?php
/****************************************************************************
 This script handles the import / export and delete functions for the Admin settings.
 Called by 'admin.php' via f=41, the backup files are processed.
 *****************************************************************************/

    error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE & ~E_STRICT);

    include "$settings_dir/database.php";

    //      get active database for this task
    if ($dba_act == '1') {
        $db_con = db_connect($mysql_host1, $mysql_user1, $mysql_password1, $database1);
        $mysql_table_prefix = $mysql_table_prefix1;
    }

    if ($dba_act == '2') {
        $db_con = db_connect($mysql_host2, $mysql_user2, $mysql_password2, $database2);
        $mysql_table_prefix = $mysql_table_prefix2;
    }

    if ($dba_act == '3') {
        $db_con = db_connect($mysql_host3, $mysql_user3, $mysql_password3, $database3);
        $mysql_table_prefix = $mysql_table_prefix3;
    }

    if ($dba_act == '4') {
        $db_con = db_connect($mysql_host4, $mysql_user4, $mysql_password4, $database4);
        $mysql_table_prefix = $mysql_table_prefix4;
    }

    if ($dba_act == '5') {
        $db_con = db_connect($mysql_host5, $mysql_user5, $mysql_password5, $database5);
        $mysql_table_prefix = $mysql_table_prefix5;
    }

    $source_file    = "".$settings_dir."/db".$dba_act."/conf_".$mysql_table_prefix.".php";   //  source file to be copied
    $set_path       = "./settings/backup/";     //  subfolder of .../admin/ where all backup files will be stored

    $now        = date("Y.m.d-H.i.s");          //  build current date and timestamp
    $filename   = "config_".$now."_db".$dba_act."_".$mysql_table_prefix.".php";

    $files  = array();
    $send2  = '';
    $del    = '';
    $file   = '';

    extract($_POST);
    extract($_REQUEST);

    $f      = '';
    $send2  = substr(trim($send2),0,16);
    $del    = substr(trim($del),0,1);
    $file   = cleaninput(substr(trim($file),0,255));

    //  create a new default configuration file?
    if ($send2 == "Create-default") {
    $filename = "Sphider-plus_default-configuration.php";
    $send2 = "Create";
    }
    //      Headline for Settings Import
    echo "
	  <div class='submenu cntr'>| Settings Backup Management |
      </div>
	  <div id='backup'>
	  <form name='setimport' id='setimport' method='post' action='admin.php?f=98'>
		<table class='panel w85 tblhead'>
            ";

    //      List available files
    if (!is_dir($set_path)) {
        mkdir($set_path, 0777);
    }

    $bgcolor	='odrow';
    $files 		= scandir($set_path);

    echo "<tr>
				<td class='headline x2 cntr'>Available backup files</td>
                <td class='headline cntr'>Manage</td>
			</tr>
            ";

    $count_confs = -1;  //  because the default configuration file is always available


    foreach ($files as $confname) {
        if (preg_match("/_/i",$confname)) {                     //show only files with a  _ in its name
            $confname = str_replace(".php", "", $confname);     //  suppress suffix
            $count_confs++ ;

            echo "<tr>
                <td class='$bgcolor'>$confname</td>
                <td class='$bgcolor'>
                    <input class='sbmt' type='button' name='lrestore'
                        onclick=\"confirm_rest_set('./admin.php?f=41&amp;file=$confname&amp;del=0');\" value='Restore'
                        title='Beware! Once started, the current configuration will be modified!'/>
                ";
            if (strpos($confname, "default")) {
                echo"    <input class='sbmt' type='button' name='delete'
                        onclick=\"confirm_protected('./admin.php?f=41&amp;file=$confname&amp;del=1');\" value=' --------- '
                        title='Original Sphider-plus file is undeletable'/>&nbsp;&nbsp;&nbsp;&nbsp;
                </td>
            </tr>
            ";
            } else {
                echo"    <input class='sbmt' type='button' name='delete'
                        onclick=\"confirm_del_set('./admin.php?f=41&amp;file=$confname&amp;del=1');\" value='&nbsp;Delete&nbsp;'
                        title='Click to delete this configuration file'
                        />&nbsp;&nbsp;&nbsp;&nbsp;
                </td>
            </tr>
			";
            }

            if ($bgcolor=='odrow') {
                $bgcolor='evrow';
            } else {
                $bgcolor='odrow';
            }
        }
    }

	echo "
		</table>
		<p>&nbsp;</p>
		<p>&nbsp;</p>
		";
    if($count_confs == 0){
        echo "<p class='evrow cntr w85'>No personal backup file of your actual settings exists. You should create a backup soon!</p>
        <p>&nbsp;</p>
		<p>&nbsp;</p>
		";
    }

    echo "<table class='w85 tblhead'>
			<tr>
				<td class='headline x2 cntr'>Create a new backup file</td>
				<td class='headline x2 cntr'>Manage</td>
            </tr>
			<tr>
				<td>&nbsp;</td><td>&nbsp;</td>
			</tr>
			<tr>
                <td class='$bgcolor'>
                    <input type='hidden' name='f' value='41'/>
                    Build a new backup file from actual settings
                </td>
                <td class='$bgcolor'>
                    <input class='sbmt' type='submit' name='send2' value='Create' title='Create another backup file from current conf.php'/>
                    &nbsp;<span class=\"mak_4\">&nbsp;&nbsp;<&nbsp;&nbsp;<&nbsp;&nbsp;<&nbsp;&nbsp;Usually to be used&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                </td>
            </tr>
			<tr>
				<td>&nbsp;</td><td>&nbsp;</td>
			</tr>
            <tr>
                <td class='$bgcolor'>
                    Build a new Sphider-plus default-configuration file from actual settings file
                </td>
                <td class='$bgcolor'>
                    <input class='sbmt_warn' type='button' name='send2'
                    onclick=\"confirm_new_config('./admin.php?f=41&amp;send2=Create-default');\" value='Create as default'
                    title='Click only, if you want to craete a new default configuration' />
                    <span class=\"mak_5\">&nbsp;&nbsp;Attention !</span>
                </td>
            </tr>
		</table>
      </form>
      <br /><br /><br />
    </div>
        ";

    //      Enter here to create a new Settings file
    if($send2 == "Create") {
        echo "<p class='headline x1 cntr'><span class='bd'><br />Creating . . .</span></p>
        ";
        if (!is_dir($set_path)) {
            mkdir($set_path, 0766);
        }

        if (!$hd1 = fopen($source_file, "r")) {
            print "Unable to open $source_file (source file)";
            fclose($hd1);
            exit;
        }

        $dest_file   = "$set_path$filename";
        if (!$hd2 = fopen($dest_file, "w")) {
            print "Unable to open $dest_file (destination file)";
            fclose($hd2);
            exit;
        }
        fclose($hd1);
        fclose($hd2);
        echo "<br />
      <p class='alert'><span class='em'>Starting to backup settings into file: $dest_file</p>
      <br />
        ";

        if (!copy($source_file,$dest_file)) {
            print "Unable to copy $source_file to $dest_file";
            exit;
        }
        echo "<body onload='JumpBottom()'>
        <p class='headline x1 cntr'><span class='bd'><br />Done</span></p>
        <p class='evrow cntr'>
        <br />
        <a class='bkbtn' href='admin.php?f=41' title='Go back to Settings Overview'>Complete this process</a></p>
         <p>&nbsp;<p>
    </div>
  </body>
</html>
            ";
        die ('');
    }

    //      Enter here to restore conf.php file
    if (strlen($file) > 1 && $del==0) {

        echo "<p class='headline x1 cntr'><span class='bd'><br />Restore from backup file</span></p>
        ";
        if (!is_dir($set_path)) {
            mkdir($set_path, 0766);
        }

        if (!$hd1 = fopen($source_file, "r")) {
            print "Unable to open $source_file (source file)";
            fclose($hd1);
            exit;
        }

        $dest_file   = "$set_path$file.php";
        if (!$hd2 = fopen($dest_file, "r")) {
            print "Unable to open $dest_file (destination file)";
            fclose($hd2);
            exit;
        }
        fclose($hd1);
        fclose($hd2);
        echo "<br />
        <p class='alert'><span class='em'>
        <body onload='JumpBottom()'>
        Starting to backup settings to file: $dest_file</p>
        <br />
            ";

        if (!copy($dest_file, $source_file)) {
            print "Unable to copy $source_file to $dest_file";
            exit;
        }

        echo "
      <p class='evrow cntr'>
      <br />
      <a class='bkbtn' href='admin.php?f=98' title='Go back to Admin'>Complete this process</a></p>
      <p>&nbsp;<p>
    </div>
  </body>
</html>
            ";
        die ('');

    }

    //      Enter here to delete Settings files
    if (strlen($file) > 1 && $del==1) {
        echo "<p class='headline x1 cntr'><span class='bd'><br />Deleting . . .</span></p>
        ";
        if (is_dir($set_path)) {
            if ($dh = opendir($set_path)) {
                while (($this_file = readdir($dh)) !== false) {
                    if ($this_file == "$file.php") {
                        if (!strpos($this_file, "default")) {
                            @unlink("$set_path/$this_file");    //    delete this file
                        }
                    }
                }
                closedir($dh);
            }
        }
        if (!strpos($file, "default")) {
            echo "<body onload='JumpBottom()'>
        <p class='odrow bd cntr'>Backup file '$file' deleted.</p>
        <br />
            ";
        }
        echo "
        <p class='evrow cntr'>
        <br />
        <a class='bkbtn' href='admin.php?f=41' title='Go back to Settings Overview'>Complete this process</a></p>
        <p>&nbsp;<p>
	  </div>
    </body>
</html>
            ";
        die ('');
    } else {
        echo "
	<p class='evrow cntr'>
    <br />
    <a class='bkbtn' href='admin.php?f=settings' title='Go back to Settings Overview'>Back to Settings Overview</a></p>
   </div>
  </body>
</html>
            ";
        die ('');
    }

?>
