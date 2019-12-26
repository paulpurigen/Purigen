<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');
 
$webhelper = new WebHelper();
$dbhelper = new dbHelper();
 
//Parameter
$firstname = $webhelper->RequestFilter("firstname", 20, false);
$lastname = $webhelper->RequestFilter("lastname", 20, false);
$email = $webhelper->RequestFilter("email", 50, false);
 
//check parameter
if($webhelper->isNull($firstname)) $webhelper->AlertMessage("Please enter a valid First Name.");
if($webhelper->isNull($lastname)) $webhelper->AlertMessage("Please enter a valid Last Name.");
if($webhelper->isNull($email)) $webhelper->AlertMessage("Please enter a valid Email Address.");
 
$dbhelper->dbOpen();
$sql = "insert into email_list(firstname, lastname, email) values ('$firstname', '$lastname', '$email')";
$inserpkid = $dbhelper->RunSQLReturnID($sql);
$dbhelper->dbClose();

$subject="Sign up information from Web";
$content = "First Name: " . $firstname . "\n" .
           "Last Name: " . $lastname . "\n" .
           "Email: " . $email . "\n";
$headers = "From: Newsletter Sign Up from Web\r\n";

$result=mail("paul@purigenbio.com", $subject, $content, $headers);

$encpkid = $webhelper->AESEncrypt256($inserpkid);
$webhelper->AlertMessageAndGo("", "/support/thank-you-signup.php?pkid=$encpkid");
?>