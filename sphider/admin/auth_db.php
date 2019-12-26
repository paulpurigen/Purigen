<?php

    error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE & ~E_STRICT);

    if (!defined("_SECURE")) {
        define("_SECURE",1);    // define secure constant
    }

    include "settings/authentication.php";
    session_start();

    if (isset($_POST['db_user']) && isset($_POST['db_pass'])) {

        $username = (substr(trim($_POST['db_user']),0,255));

        //      prevent SQL-injection
        $username = str_replace('\\','\\\\', $username);
        $username = str_replace('"','\"', $username);

        //	prevent XSS-attack, Shell-execute and JavaScript execution
        if (preg_match("/cmd|CREATE|DELETE|DROP|eval|EXEC|File|INSERT|printf/i",$username)) {
            $username = '';
        }
        if (preg_match("/LOCK|PROCESSLIST|SELECT|shell|SHOW|SHUTDOWN/i",$username)) {
            $username = '';
        }
        if (preg_match("/SQL|SYSTEM|TRUNCATE|UNION|UPDATE|DUMP/i",$username)) {
            $username = '';
        }
        if (preg_match("/java|vbscri|embed|onclick|onmouseover|onfocus/i",$username)) {
            $username = '';
        }

        $password = substr(trim($_POST['db_pass']),0,255);
        //      prevent SQL-injection
        $password = str_replace('\\','\\\\', $password);
        $password = str_replace('"','\"', $password);

        //	prevent XSS-attack, Shell-execute and JavaScript execution
        if (preg_match("/cmd|CREATE|DELETE|DROP|eval|EXEC|File|INSERT|printf/i",$password)) {
            $password = '';
        }
        if (preg_match("/LOCK|PROCESSLIST|SELECT|shell|SHOW|SHUTDOWN/i",$password)) {
            $password = '';
        }
        if (preg_match("/SQL|SYSTEM|TRUNCATE|UNION|UPDATE|DUMP/i",$password)) {
            $password = '';
        }
        if (preg_match("/java|vbscri|embed|onclick|onmouseover|onfocus/i",$password)) {
            $password = '';
        }

		$_SESSION['db_admin']       = '';
		$_SESSION['db_admin_pw']    = '';

		//if ($username == $db_admin && $password == $db_admin_pw) {
        if (crypt($username, $db_admin) == $db_admin && crypt($password, $db_admin_pw) == $db_admin_pw) {
            $_SESSION['db_admin']       = $username;
            $_SESSION['db_admin_pw']    = $password;
		}

		//if(isset($_SESSION['db_admin']) && isset($_SESSION['db_admin_pw']) &&$_SESSION['db_admin'] == $db_admin && $_SESSION['db_admin_pw'] == $db_admin_pw  || $_SERVER['REMOTE_ADDR']=="") {
		if(isset($_SESSION['db_admin']) && isset($_SESSION['db_admin_pw']) && crypt($_SESSION['db_admin'], $db_admin) == $db_admin && crypt($_SESSION['db_admin_pw'], $db_admin_pw) == $db_admin_pw  || $_SERVER['REMOTE_ADDR']=="") {
			@session_regenerate_id();
			header("Location: db_config.php?p=98");	//	passed
		} else {
			@session_regenerate_id();
			header("Location: db_config.php?p=99");	//	error
		}

    } else {
        echo "    <div class='submenu cntr'>| Database Management|</div>
            <br />
            <div class='panel x3'>
                <form class='txt' action='auth_db.php' method='post'>
                    <fieldset>
                        <legend>[ Database configuration login ]</legend>
                        <label for='user'>[ Name ]</label>
                        <input type='text' name='db_user' id='db_user' size='15' maxlength='15'
                        title='Required - Enter your database user name here' onfocus='this.value=\"\"' value=''/>
                        <label for='pass'>[ Password for database config ]</label>
                        <input type='password' name='db_pass' id='db_pass' size='15' maxlength='15'
                        title='Required - Enter your database password here' onfocus='this.value=\"\"' value=''/>
                    </fieldset>
                    <fieldset><legend>[ Log In ]</legend>
                        <input class='sbmt' type='submit' id='submit' value='&nbsp;Login &raquo;&raquo; ' title='Click to confirm'/>
                    </fieldset>
                </form>
            </div>
            <br />
            <br />
        </body>
    </html>
        ";
        exit();
    }

?>