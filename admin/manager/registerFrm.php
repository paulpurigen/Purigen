<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/manager/list.php";
$PageCode = "010101";
$webhelper->CheckAdminLogin($PageCode, urlencode($PageUrl), true);

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

            if(!idcheck())
            {
                $("#id").css("border", "1px solid #ff0000");
                isOk = false;
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
            
            if(!emailcheck())
            {
                $("#email").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
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

            <h2>Manager Input</h2>
            
            <form method="post" name="regFrm" action="/admin/manager/register.php">
                <div class="add_user">

                    <div class="add_user_info">
                    </div>
                    <!-- //add_user_info -->

                    <div class="add_field">
                        <dl>
                            <dt>Username <em>*</em></dt>
                            <dd><input type="text" name="id" id="id" maxlength="20" style="width:330px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->

                    <div class="add_field">
                        <dl>
                            <dt>E-mail Address <em>*</em></dt>
                            <dd><input type="text" name="email" id="email" maxlength="50" style="width:330px"></dd>
                        </dl>
                        <!--<p>A valid e-mail address. All e-mails from th system will be sent to this address. The e-mail address is not made public and wil only be used if you wish to receive a new password or wish to receive certain news or notifications by e-mail</p>-->
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
                            <dt>ROLES</dt>
                            <dd><input type="checkbox" name="isAdministrator" value="1"> Administrator</dd>
                            <dd><input type="checkbox" name="isEditor" value="1"> Editor of Content</dd>
                            <dd><input type="checkbox" name="isCareers" value="1"> HR / Careers Editor</dd>
                        </dl>
                    </div>
                    <!-- //add_field -->

                    <div class="add_field">
                        <dl>
                            <dt>First Name <em>*</em></dt>
                            <dd><input type="text" name="firstname" id="firstname" maxlength="20" style="width:330px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->

                    <div class="add_field">
                        <dl>
                            <dt>Last Name <em>*</em></dt>
                            <dd><input type="text" name="lastname" id="lastname" maxlength="20" style="width:330px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->

                    <div class="add_field">
                        <dl>
                            <dt>Company <em>*</em></dt>
                            <dd><input type="text" name="company" id="company" maxlength="100" style="width:330px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->

                    <div class="add_field">
                        <dl>
                            <dt>Country <em>*</em></dt>
                            <dd><input type="text" name="country" id="country" maxlength="100" style="width:330px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->

                    <div class="add_field">
                        <dl>
                            <dt>State or Providence <em>*</em></dt>
                            <dd><input type="text" name="providence" id="providence" maxlength="100" style="width:330px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->
                    
                    <div class="add_field">
                        <dl>
                            <dt>City <em>*</em></dt>
                            <dd><input type="text" name="city" id="city" maxlength="100" style="width:330px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->
                    
                    <div class="add_field">
                        <dl>
                            <dt>Zip / Postal Code <em>*</em></dt>
                            <dd><input type="text" name="postcode" id="postcode" maxlength="50" style="width:330px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->

                    <div class="add_field">
                        <dl>
                            <dt>Phone</dt>
                            <dd><input type="text" name="phone" id="phone" maxlength="50" style="width:330px"></dd>
                        </dl>
                    </div>
                    <!-- //add_field -->

                </div>
                <!-- //add_user -->
            </form>
            <br>
            <div>
                <a href="javascript:checkFrm();" class="bt bt-green">Create Account</a>
            </div>

        </div>
        <!-- //contents -->

    </div>
    <!-- //container -->

<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/include/footer.php')?>