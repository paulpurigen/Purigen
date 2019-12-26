<?php
?>
<footer id="footer">
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
        function checkEmailAdd(email)
        {
            if(email.indexOf("@gmail.") >= 0)
            {
                return false;
            }
            if(email.indexOf("@yahoo.") >= 0)
            {
                return false;
            }
            if(email.indexOf("@hotmail.") >= 0)
            {
                return false;
            }
            if(email.indexOf("@outlook.") >= 0)
            {
                return false;
            }
            if(email.indexOf("@icloud.") >= 0)
            {
                return false;
            }
            if(email.indexOf("@aol.") >= 0)
            {
                return false;
            }
            return true;
        }
        function checkFooterMailFrm()
        {
            var f = document.footermailFrm;
            var isOk = true;
            $("#footer_firstname").css("border", "");
            $("#footer_lastname").css("border", "");
            $("#footer_email").css("border", "");
            if(f.firstname.value == "")
            {
                $("#footer_firstname").css("border", "1px solid #ff0000");
                isOk = false;
            }
            if(f.lastname.value == "")
            {
                $("#footer_lastname").css("border", "1px solid #ff0000");
                isOk = false;
            }
            if(f.email.value == "")
            {
                $("#footer_email").css("border", "1px solid #ff0000");
                isOk = false;
            }
            if(!checkEmail(f.email.value))
            {
                $("#footer_email").css("border", "1px solid #ff0000");
                isOk = false;
            }
            if(!checkEmailAdd(f.email.value))
            {
                showEmailDiv();
                isOk = false;
            }
            if(isOk)
            {
                f.submit();
            }
        }
        function showEmailDiv()
        {
            $("#error-email").show();
        }
        function hideEmailDiv()
        {
            $("#error-email").hide();
        }
    </script>
    <div class="footer-inner">
        <div class="footer-logo">
            <img src="/images/icon-purigen-white.svg" alt=""/> Nucleic Acid Purification – Pure and Simple&trade;
        </div>
        <!-- //footer-logo -->
        <div class="footer-menu">
            <dl>
                <dt>Products</dt>
                <dd><a href="/products/ionic-system.php">Ionic&trade; Purification System</a></dd>
                <dd><a href="/products/ionic-kits.php">Ionic&trade; Kits</a></dd>
            </dl>
            <dl>
                <dt>Technology</dt>
                <dd><a href="/technology/isotachophoresis.php">Purigen Isotachophoresis</a></dd>
            </dl>
            <dl>
                <dt>NEWS &amp; EVENTS</dt>
                <dd><a href="/news-events/news/list.php">News and Press</a></dd>
                <dd><a href="/news-events/events/list.php">Events</a></dd>
            </dl>
            <dl>
                <dt>Resources</dt>
                <dd><a href="/resources/literature.php">Literature</a></dd>
                <dd><a href="/resources/videos.php">Videos</a></dd>
            </dl>
            <dl>
                <dt>Support</dt>
                <dd><a href="/support/documentation.php">Documentation</a></dd>
                <dd><a href="/support/request-info.php">Request Information</a></dd>
            </dl>
            <dl>
                <dt>Company</dt>
                <dd><a href="/company/about-us.php">About Us</a></dd>
                <dd><a href="/company/careers.php">Careers</a></dd>
                <dd><a href="/company/location.php">Location and Contact</a></dd>
                <dd><a href="/company/privacy.php">Privacy and Cookies</a></dd>
            </dl>
        </div>
        <!-- //footer-menu -->
        <div class="footer-newsletter">
            <h3>Sign up for our e-newsletter to get the latest information on Purigen products and news.</h3>
            <p>Please enter your WORK email address only. Common addresses <i>i.e.,</i> yahoo, hotmail, gmail cannot be accepted.</p>
            <form name="footermailFrm" method="post" action="/support/signup_proc.php">
                <div class="footer-newsletter-field">
                    <input type="text" name="firstname" id="footer_firstname" maxlength="20" placeholder="First Name"  />
                    <input type="text" name="lastname" id="footer_lastname" maxlength="20" placeholder="Last Name" />
                </div>
                <div class="footer-newsletter-field">
                    <input type="text" name="email" id="footer_email" maxlength="50" placeholder="Enter work email address" /> <button type="button" onclick="checkFooterMailFrm();">SIGN UP</button>
                </div>
            </form>
        </div>
        <!-- //footer-newsletter -->
        <div class="footer-follow">
            <dl>
                <dt>FOLLOW US</dt>
                <dd><a href="https://twitter.com/purigenbio" target="_blank"><i class="fab fa-twitter-square fa-3x"></i></a></dd>
                <dd><a href="https://www.linkedin.com/company/purigenbiosystems" target="_blank"><i class="fab fa-linkedin fa-3x"></i></a></dd>
                <dd><a href="https://www.facebook.com/purigenbio/" target="_blank"><i class="fab fa-facebook-square fa-3x"></i></a></dd>
                <dd><a href="https://www.youtube.com/channel/UCpkZHg4ziC5rZzLKVImaFbQ" target="_blank"><i class="fab fa-youtube-square fa-3x"></i></a></dd>
            </dl>
        </div>
        <!-- //footer-follow -->
        <div class="footer-info">
            <img src="/images/common/logo-purigen-white.svg" alt="Purigen Biosystems">
        </div>
        <!-- //footer-copyright -->
        <div class="footer-copyright">
            <p><strong>FOR RESEARCH USE ONLY.</strong> Not for use in diagnostic procedures.</p>
            <ul>
                <li>PURIGEN BIOSYSTEMS &nbsp; | &nbsp; 5700 Stoneridge Drive, Suite 100, Pleasanton, CA 94588</li>
                <li><a href="/company/privacy.php">PRIVACY POLICY</a></li>
                <li><a href="/support/request-info.php">REQUEST INFO</a></li>
                <!--<li>TEL: +1 925 264-1364</li>-->
            </ul>
            <address><strong>© 2019 Purigen Biosystems, Inc.</strong> All rights reserved.</address>
        </div>
    </div>
    <!-- //footer-info -->
    </footer>
    <!-- //footer -->
</div>
<!-- //wrap -->
<div id="error-email" style="display: none" class="pop_wrap">
    <div>
        <div>
            <div class="pop_data">
                <h2 style="font-family:'Forza-Medium'; font-size:24px; text-align:center; padding-bottom:7px">Oops!</h2>
                <p style="font-family:'Roboto-Book'; font-size:15px">Please enter your work email address only.</p>
                <p>Common address i.e., yahoo, hotmail, gmail cannot be accepted.</p>
                <a href="javascript:hideEmailDiv();" class="btn_pop_close"></a>
            </div>
        </div>
    </div>
</div>
</body>
</html>