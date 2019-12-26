<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/resources/sheets/list.php";
$PageCode = "020201";
$webhelper->CheckAdminLogin($PageCode, urlencode($PageUrl), true);

$nowYear = date("Y");
?>
<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/include/header.php'); ?>

    <script type="text/javascript">
        function checkFrm()
        {
            var f = document.regFrm;
            var isOk = true;
            
            $("#year").css("border", "");
            $("#month").css("border", "");
            $("#day").css("border", "");
            $("#title").css("border", "");
            
            if(f.year.value == "")
            {
                $("#year").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            if(f.month.value == "0")
            {
                $("#month").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            if(f.day.value == "0")
            {
                $("#day").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            if(f.title.value == "")
            {
                $("#title").css("border", "1px solid #ff0000");
                isOk = false;
            }

            if(isOk)
            {
                f.submit();
            }
        }
    </script>
    
    <div id="container">

        <div id="contents">

            <h2>sheets</h2>
            
            <form method="post" name="regFrm" action="/admin/resources/sheets/register.php" enctype="multipart/form-data">

                <table class="board_write">
                    <colgroup>
                        <col style="width:25%">
                        <col style="width:75%">
                    </colgroup>
                    <tr>
                        <th>year</th>
                        <td><input type="text" name="year" id="year" value="<?=$nowYear?>" maxlength="4" style="width:60px"></td>
                    </tr>
                    <tr>
                        <th>month</th>
                        <td>
                            <select name="month" id="month">
                                <option value="0">- Month -</option>
                                <option value="01">January</option>
                                <option value="02">February</option>
                                <option value="03">March</option>
                                <option value="04">April</option>
                                <option value="05">May</option>
                                <option value="06">June</option>
                                <option value="07">July</option>
                                <option value="08">August</option>
                                <option value="09">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>day</th>
                        <td>
                            <select name="day" id="day">
                                <option value="0">- Day -</option>
                                <option value="01">01</option>
                                <option value="02">02</option>
                                <option value="03">03</option>
                                <option value="04">04</option>
                                <option value="05">05</option>
                                <option value="06">06</option>
                                <option value="07">07</option>
                                <option value="08">08</option>
                                <option value="09">09</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                                <option value="13">13</option>
                                <option value="14">14</option>
                                <option value="15">15</option>
                                <option value="16">16</option>
                                <option value="17">17</option>
                                <option value="18">18</option>
                                <option value="19">19</option>
                                <option value="20">20</option>
                                <option value="21">21</option>
                                <option value="22">22</option>
                                <option value="23">23</option>
                                <option value="24">24</option>
                                <option value="25">25</option>
                                <option value="26">26</option>
                                <option value="27">27</option>
                                <option value="28">28</option>
                                <option value="29">29</option>
                                <option value="30">30</option>
                                <option value="31">31</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>title</th>
                        <td><input type="text" name="title" id="title" maxlength="200" style="width:350px"></td>
                    </tr>
                    <tr>
                        <th>description</th>
                        <td><textarea name="body" id="body" style="width:516px;height:250px"></textarea></td>
                    </tr>
                    <tr>
                        <th>thumbnail file</th>
                        <td>
                            <input type="file" name="thumbnail1">
                        </td>
                    </tr>
                    <tr>
                        <th>thumbnail file</th>
                        <td>
                            <input type="file" name="thumbnail2">
                        </td>
                    </tr>
                    <tr>
                        <th>file</th>
                        <td>
                           <input type="file" name="up_file">
                        </td>
                    </tr>
                </table>
                <!-- //board_write -->
            </form>
            
            <div class="form_submit l_clear">
                <div class="side side-left">
                    <a href="javascript:checkFrm();" class="bt bt-green">save</a>
                    <a href="javascript:history.back();" class="bt bt-green">cancel</a>
                </div>
            </div>
            <!-- //form_submit -->

        </div>
        <!-- //contents -->

    </div>
    <!-- //container -->


<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/include/footer.php')?>