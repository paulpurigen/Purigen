<!DOCTYPE html>
<head>    
<title>Request Information | Purigen Biosystems</title>
<meta name="description" content="Contact us to request information on Purigen Ionic Purification System and Kits." />
<link rel="canonical" href="https://www.purigenbio.com/support/request-info" />
<meta content="Request Information | Purigen Biosystems" property="og:title">
<meta content="Request Information | Purigen Biosystems" name="twitter:title">
<meta content="Contact us to request information on Purigen Ionic Purification System and Kits." property="og:description">
<meta content="Contact us to request information on Purigen Ionic Purification System and Kits." name="twitter:description">
<meta content="https://www.purigenbio.com/images/common/logo-purigen-social.jpg" property="og:image">
<meta content="https://www.purigenbio.com/images/common/logo-purigen-social.jpg" name="twitter:image">
<meta content="website" property="og:type">

<?php include_once($_SERVER['DOCUMENT_ROOT'] . "/include/header.php") ?>

</head>

<script type="text/javascript">

    function checkFrm()

    {
        var f = document.requestFrm;
        var isOk = true;
        $("#firstname").css("border", "");
        $("#lastname").css("border", "");
        $("#email").css("border", "");
        $("#phone").css("border", "");
        $("#organization").css("border", "");
        $("#job_title").css("border", "");
        $("#country").css("border", "");
        $("#state").css("border", "");
        $("#main_application").css("border", "");
        $("#purchase_timeline").css("border", "");
        $("#note").css("border", "");
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
        if(f.email.value == "")
        {
            $("#email").css("border", "1px solid #ff0000");
            isOk = false;
        }
        if(f.phone.value == "")
        {
            $("#phone").css("border", "1px solid #ff0000");
            isOk = false;
        }
        if(f.organization.value == "")
        {
            $("#organization").css("border", "1px solid #ff0000");
            isOk = false;
        }
        if(f.job_title.value == "")
        {
            $("#job_title").css("border", "1px solid #ff0000");
            isOk = false;
        }
        if(f.country.value == "")
        {
            $("#country").css("border", "1px solid #ff0000");
            isOk = false;
        }
        if(f.state.value == "")
        {
            $("#state").css("border", "1px solid #ff0000");
            isOk = false;
        }
        if(f.main_application.value == "")
        {
            $("#main_application").css("border", "1px solid #ff0000");
            isOk = false;
        }
        if(f.purchase_timeline.value == "")
        {
            $("#purchase_timeline").css("border", "1px solid #ff0000");
            isOk = false;
        }
        if(f.note.value == "")
        {
            $("#note").css("border", "1px solid #ff0000");
            isOk = false;
        }
        if(isOk)
        {
            f.submit();
        }else
        {
            showDiv();
        }
    }
    function showDiv()
    {
        $("#error-msg").show();
    }
    function hideDiv()
    {
        $("#error-msg").hide();
    }
    
</script>

<div id="container">

    <div id="contents" class="none-visual">
        <div class="contents-title">
            <h2>Request Information</h2>
            <p>Please fill out the form below to get in contact with one of our sales or support associate.</p>
        </div>

        <!-- //contents-title -->

        <form name="requestFrm" method="post" action="/support/request-info-proc.php" >
            <div class="form">
                <div class="form-required">* Required</div>
                <div class="form-row">
                    <dl>
                        <dt><label for="">FIRST NAME*</label></dt>
                        <dd><input type="text" name="firstname" id="firstname" maxlength="20"/></dd>
                    </dl>
                    <dl>
                        <dt><label for="">LAST NAME*</label></dt>
                        <dd><input type="text" name="lastname" id="lastname" maxlength="20"/></dd>
                    </dl>
                </div>
                <div class="form-row">
                    <dl>
                        <dt><label for="">WORK EMAIL* <em>(please do not enter web-based emails like yahoo, gmail, etc.)</em></label></dt>
                        <dd><input type="text" name="email" id="email" maxlength="50"/></dd>
                    </dl>
                    <dl>
                        <dt><label for="">PHONE*</label></dt>
                        <dd><input type="text" name="phone" id="phone" maxlength="50"/></dd>
                    </dl>
                </div>
                <div class="form-row">
                    <dl>
                        <dt><label for="">ORGANIZATION / INSTITUTION*</label></dt>
                        <dd><input type="text" name="organization" id="organization" maxlength="100"/></dd>
                    </dl>
                    <dl>
                        <dt><label for="">JOB TITLE*</label></dt>
                        <dd><input type="text" name="job_title" id="job_title" maxlength="100"/></dd>
                    </dl>
                </div>

                <div class="form-row">
                    <dl>
                        <dt><label for="">COUNTRY*</label></dt>
                        <dd><input type="text" name="country" id="country" maxlength="50"/></dd>
                    </dl>
                    <dl>
                        <dt><label for="">STATE / PROVINCE*</label></dt>
                        <dd><input type="text" name="state" id="state" maxlength="50"/></dd>
                    </dl>
                </div>
                <div class="form-row">
                    <dl>
                        <dt><label for="">MAIN APPLICATION*</label></dt>
                        <dd><input type="text" name="main_application" id="main_application" maxlength="100"/></dd>
                    </dl>
                    <!--<dl>
                            <dt><label for="">MAIN PRODUCT INTEREST*</label></dt>
                            <dd><input type="text"/></dd>
                    </dl>-->
                </div>
                <div class="form-row">
                    <dl>
                        <dt><label for="">PURCHASE TIMELINE*</label></dt>
                        <dd><input type="text" name="purchase_timeline" id="purchase_timeline" maxlength="100"/></dd>
                    </dl>
                    <dl></dl>
                </div>
                <div class="form-agree">
                    <div class="checkbox-set"><input type="checkbox" name="isreceive" value="1" id="category-brochures"/> <label for="category-brochures">Yes, I'd like to receive the latest news about Purigen.</label></div>
                </div>
                <div class="form-row">
                    <dl>
                        <dt><label for="">REQUEST OR QUESTION*</label></dt>
                        <dd><textarea name="note" id="note"></textarea></dd>
                    </dl>
                </div>
                <div class="form-info">By submitting, you are agreeing to our <a href="/company/privacy.php">Privacy Policy</a>.</strong></div>
                <div class="form-submit">
                    <button type="button" onclick="checkFrm();">SUBMIT</button>
                </div>
            </div>
        </form>
        <!-- //form -->
    </div>
    <!-- //contents -->
</div>

<!-- //container -->

<div id="error-msg" style="display: none" class="pop_wrap">
    <div>
        <div>
            <div class="pop_data">
                <h2 style="font-family:'Forza-Medium'; font-size:24px; text-align:center; padding-bottom:7px">Oops!</h2>
                <p style="font-family:'Roboto-Book'; font-size:15px">Missing or incorrect information entered.</p>
                <p>Please enter a valid entry for each required field highlighted in red.</p>
                <a href="javascript:hideDiv();" class="btn_pop_close"></a>
            </div>
        </div>
    </div>
</div>

<?php include_once($_SERVER['DOCUMENT_ROOT'] . "/include/footer.php") ?>