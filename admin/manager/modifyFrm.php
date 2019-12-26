<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/manager/list.php";
$PageCode = "010101";
$webhelper->CheckAdminLogin($PageCode, urlencode($PageUrl), true);

//Parameter
$pkid = $webhelper->RequestFilter("pkid", 0, false);
$page = $webhelper->RequestFilter("page", 0, false);
$searchKey = $webhelper->RequestFilter("searchKey", -1, false);
$filter = $webhelper->RequestFilter("filter", 0, false);
$order = $webhelper->RequestFilter("order", 0, false);

//check parameter
if($webhelper->isNull($pkid)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($page)) $page = 1;
if($webhelper->isNull($searchKey)) $searchKey = "";
if($webhelper->isNull($filter)) $filter = 0;
if($webhelper->isNull($order)) $order = 0;

$Parameter = "?page=$page&searchKey=$searchKey&filter=$filter&order=$order";

$dbhelper->dbOpen();
$sql = "select pkid, id, pass, email, status, isAdministrator, isEditor, isCareers, " .
       " firstname, lastname, company, country, providence, city, postcode, phone from admin where pkid = $pkid ";
$ViewData = $dbhelper->RunSQLReturnOneRow($sql);
$dbhelper->dbClose();

if($webhelper->isNull($ViewData))
    $webhelper->AlertMessageAndGo("Error. Please try again.", "/admin/manager/list.php" . $Parameter);
?>
<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/include/header.php')?>
    <script type="text/javascript">
        function checkEmail(email)
        {
            if (email.search(/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/) != -1)
            {
                return true;
            } else
            {
                return false;
            }
        }
        
        function idcheck()
        {
            var f =  document.regFrm;
            var isOk = true;
            
            if(f.id.value == "")
            {
                isOk = false;
            }
            
            if(isOk)
            {
                jQuery.ajax({
                    url: "/admin/manager/ajaxCheckId.php?id=" + f.id.value,
                    type: "post",
                    async   : false,
                    success: function (result) {
                        result = result.replace(/(^\s*)|(\s*$)/ig,'').replace(/\n/ig,'');
                        if (result == "T") {
                            isOk = true;
                        }else
                        {
                            isOk = false;
                        }
                    },
                    error: function () {
                        isOk = false;
                    }
                });
            }
            
            return isOk;
        }
        
        function emailcheck()
        {
            var f =  document.regFrm;
            var isOk = true;
            
            if(f.email.value == "")
            {
                isOk = false;
            }

            if(!checkEmail(f.email.value))
            {
                isOk = false;
            }
            
            if(isOk)
            {
                jQuery.ajax({
                    url: "/admin/manager/ajaxCheckEmail.php?email=" + f.email.value,
                    type: "post",
                    async   : false,
                    success: function (result) {
                        result = result.replace(/(^\s*)|(\s*$)/ig,'').replace(/\n/ig,'');
                        if (result == "T") {
                            isOk = true;
                        }else
                        {
                            isOk = false;
                        }
                    },
                    error: function () {
                        isOk = false;
                    }
                });
            }
            
            return isOk;
        }

        function checkFrm()
        {
            var f = document.regFrm;
            var isOk = true;
            
            $("#id").css("border", "");
            $("#email").css("border", "");
            $("#pass1").css("border", "");
            $("#pass2").css("border", "");
            $("#firstname").css("border", "");
            $("#lastname").css("border", "");
            $("#company").css("border", "");
            $("#country").css("border", "");
            $("#providence").css("border", "");
            $("#city").css("border", "");
            //$("#postcode").css("border", "");
            
            if(f.id.value == "")
            {
                $("#id").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            if(f.id.value != "<?=$ViewData["id"]?>")
            {
                if(!idcheck())
                {
                    $("#id").css("border", "1px solid #ff0000");
                    isOk = false;
                }
            }
            
            if(f.email.value == "")
            {
                $("#email").css("border", "1px solid #ff0000");
                isOk = false;
            }

            if(!checkEmail(f.email.value))
            {
                $("#email").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            if(f.email.value != "<?=$ViewData["email"]?>")
            {
                if(!emailcheck())
                {
                    $("#email").css("border", "1px solid #ff0000");
                    isOk = false;
                }
            }
            
            if(f.pass1.value != "" || f.pass2.value != "")
            {
                if(f.pass1.value == "")
                {
                    $("#pass1").css("border", "1px solid #ff0000");
                    isOk = false;
                }

                if(f.pass2.value == "")
                {
                    $("#pass2").css("border", "1px solid #ff0000");
                    isOk = false;
                }

                if(f.pass1.value != f.pass2.value)
                {
                    $("#pass1").css("border", "1px solid #ff0000");
                    $("#pass2").css("border", "1px solid #ff0000");
                    isOk = false;
                }
            }
            
            if(f.firstname.value == "")
            {
                $("#firstname").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            if(f.lastname.value == "")
            {
                $("#lastname").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            if(f.company.value == "")
            {
                $("#company").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            if(f.country.value == "")
            {
                $("#country").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            if(f.providence.value == "")
            {
                $("#providence").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            if(f.city.value == "")
            {
                $("#city").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            //if(f.postcode.value == "")
            //{
            //    $("#postcode").css("border", "1px solid #ff0000");
            //    isOk = false;
            //}
            
            if(isOk)
            {
                f.submit();
            }
        }
    </script>
    <div id="container">

        <div id="contents">

            <h2>Edit Account Info</h2>
            
            <form method="post" name="regFrm" action="/admin/manager/modify.php">
                <input type="hidden" name="pkid"  value="<?=$ViewData["pkid"]?>"/>
                <input type="hidden" name="page"  value="<?=$page?>"/>
                <input type="hidden" name="searchKey"  value="<?=$searchKey?>"/>
                <input type="hidden" name="filter"  value="<?=$filter?>"/>
                <input type="hidden" name="order"  value="<?=$order?>"/>

                <div class="add_user">

                    <div class="add_field">
                        <dl>
                            <dt>Username <em>*</em></dt>
                            <dd><input type="text" name="id" id="id" value="<?=$ViewData["id"]?>" maxlength="20" style="width:330px"></dd>
                        </dl>
                        <p>Spaces are allowed; punctuation is not allowed except for periods, hyphens, apostrophes, and underscores.</p>
                    </div>
                    <!-- //add_field -->

                    <div class="add_field">
                        <dl>
                            <dt>E-mail Address <em>*</em></dt>
                            <dd><input type="text" name="email" id="email" value="<?=$ViewData["email"]?>" maxlength="50" style="width:330px"></dd>
                        </dl>
                        <p>A valid e-mail address is required. All e-mails from this system will be sent to this address. This e-mail address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by e-mail.</p>
                    </div>
                    <!-- //add_field -->

                    <div class="add_field">
                        <dl>
                            <dt>Password <em>*</em></dt>
                            <dd><input type="password" name="pass1" id="pass1" maxlength="20" style="width:180px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->

                    <div class="add_field">
                        <dl>
                            <dt>Confirm Password <em>*</em></dt>
                            <dd><input type="password" name="pass2" id="pass2" style="width:180px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->

                    <div class="add_field">
                        <dl>
                            <dt>STATUS</dt>
                            <dd><input type="radio" name="status" <?=$webhelper->MakeSelectedValue("0", $ViewData["status"] , "checked=\"checked\"")?> value="0"> Blocked</dd>
                            <dd><input type="radio" name="status" <?=$webhelper->MakeSelectedValue("1", $ViewData["status"] , "checked=\"checked\"")?> value="1"> Active</dd>
                        </dl>
                    </div>
                    <!-- //add_field -->

                    <div class="add_field">
                        <dl>
                            <dt>ROLES</dt>
                            <dd><input type="checkbox" name="isAdministrator" <?=$webhelper->MakeSelectedValue("1", $ViewData["isAdministrator"] , "checked=\"checked\"")?> value="1"> Administrator</dd>
                            <dd><input type="checkbox" name="isEditor" <?=$webhelper->MakeSelectedValue("1", $ViewData["isEditor"] , "checked=\"checked\"")?> value="1"> Editor of Content</dd>
                            <dd><input type="checkbox" name="isCareers" <?=$webhelper->MakeSelectedValue("1", $ViewData["isCareers"] , "checked=\"checked\"")?> value="1"> HR / Careers Editor</dd>
                        </dl>
                    </div>
                    <!-- //add_field -->

                    <div class="add_field">
                        <dl>
                            <dt>First Name <em>*</em></dt>
                            <dd><input type="text" name="firstname" id="firstname"  value="<?=$ViewData["firstname"]?>" maxlength="20" style="width:330px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->

                    <div class="add_field">
                        <dl>
                            <dt>Last Name <em>*</em></dt>
                            <dd><input type="text" name="lastname" id="lastname" value="<?=$ViewData["lastname"]?>" maxlength="20" style="width:330px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->

                    <div class="add_field">
                        <dl>
                            <dt>Company <em>*</em></dt>
                            <dd><input type="text" name="company" id="company" value="<?=$ViewData["company"]?>" maxlength="100" style="width:330px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->

                    <div class="add_field">
                        <dl>
                            <dt>Country <em>*</em></dt>
                            <dd><input type="text" name="country" id="country" value="<?=$ViewData["country"]?>" maxlength="100" style="width:330px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->

                    <div class="add_field">
                        <dl>
                            <dt>State or Providence <em>*</em></dt>
                            <dd><input type="text" name="providence" id="providence" value="<?=$ViewData["providence"]?>" maxlength="100" style="width:330px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->

                     <div class="add_field">
                        <dl>
                            <dt>City <em>*</em></dt>
                            <dd><input type="text" name="city" id="city" value="<?=$ViewData["city"]?>" maxlength="100" style="width:330px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->
                    
                    <div class="add_field">
                        <dl>
                            <dt>Zip / Postal Code <em>*</em></dt>
                            <dd><input type="text" name="postcode" id="postcode" value="<?=$ViewData["postcode"]?>" maxlength="50" style="width:330px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->
                    
                    <div class="add_field">
                        <dl>
                            <dt>Phone</dt>
                            <dd><input type="text" name="phone" id="phone" value="<?=$ViewData["phone"]?>" maxlength="50" style="width:330px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->

                </div>
                <!-- //add_user -->
            </form>
            <br>
            <div>
                <a href="javascript:checkFrm();" class="bt bt-green">Save</a>
            </div>

        </div>
        <!-- //contents -->

    </div>
    <!-- //container -->

<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/include/footer.php')?>