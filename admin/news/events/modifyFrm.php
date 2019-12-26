<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/news/events/list.php";
$PageCode = "030201";
$webhelper->CheckAdminLogin($PageCode, urlencode($PageUrl), true);

//Parameter
$pkid = $webhelper->RequestFilter("pkid", 0, false);
$page = $webhelper->RequestFilter("page", 0, false);
$searchKey = $webhelper->RequestFilter("searchKey", -1, false);
$order = $webhelper->RequestFilter("order", 0, false);

//check parameter
if($webhelper->isNull($pkid)) $webhelper->AlertMessage("Error. Please try again.");
if($webhelper->isNull($page)) $page = 1;
if($webhelper->isNull($searchKey)) $searchKey = "";
if($webhelper->isNull($order)) $order = 0;

$Parameter = "?page=$page&searchKey=$searchKey&order=$order";

$dbhelper->dbOpen();
$sql = "select pkid, type, startyear, startmonth, startday, endyear, endmonth, endday, maintitle, subtitle, url, location1, location2, booth, body, thumbnail1, thumbnail2, filename, orgfilename, updatedate
        from events where pkid = $pkid";
$ViewData = $dbhelper->RunSQLReturnOneRow($sql);
$dbhelper->dbClose();

if($webhelper->isNull($ViewData))
    $webhelper->AlertMessageAndGo("Error. Please try again.", "/admin/news/events/list.php" . $Parameter);
?>
<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/include/header.php'); ?>
    <script type="text/javascript">
        function checkFrm()
        {
            var f = document.regFrm;
            var isOk = true;
            
            $("#type").css("border", "");
            $("#startyear").css("border", "");
            $("#startmonth").css("border", "");
            $("#startday").css("border", "");
            $("#endyear").css("border", "");
            $("#endmonth").css("border", "");
            $("#endday").css("border", "");
            $("#maintitle").css("border", "");
            $("#subtitle").css("border", "");
            $("#location1").css("border", "");
            
            if(f.type.value == "0")
            {
                $("#type").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            if(f.startyear.value == "")
            {
                $("#startyear").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            if(f.startmonth.value == "0")
            {
                $("#startmonth").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            if(f.startday.value == "0")
            {
                $("#startday").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            if(f.endyear.value == "")
            {
                $("#endyear").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            if(f.endmonth.value == "0")
            {
                $("#endmonth").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            if(f.endday.value == "0")
            {
                $("#endday").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            if(f.maintitle.value == "")
            {
                $("#maintitle").css("border", "1px solid #ff0000");
                isOk = false;
            }

            if(f.subtitle.value == "")
            {
                $("#subtitle").css("border", "1px solid #ff0000");
                isOk = false;
            }

            if(f.location1.value == "")
            {
                $("#location1").css("border", "1px solid #ff0000");
                isOk = false;
            }
            
            if(isOk)
            {
                f.submit();
            }
        }
        
        function del()
        {
            var f = document.regFrm;
            if(confirm("Are you sure you want to delete this record?"))
            {
                f.action = "/admin/news/events/delete.php";
                f.submit();
            }
        }
    </script>
    
    <div id="container">

        <div id="contents">

            <h2>upcoming events</h2>
            
            <form method="post" name="regFrm"  action="/admin/news/events/modify.php" enctype="multipart/form-data">
                <input type="hidden" name="pkid"  value="<?=$ViewData["pkid"]?>"/>
                <input type="hidden" name="pkids[]"  value="<?=$ViewData["pkid"]?>"/>
                <input type="hidden" name="page"  value="<?=$page?>"/>
                <input type="hidden" name="searchKey"  value="<?=$searchKey?>"/>
                <input type="hidden" name="order"  value="<?=$order?>"/>
                <input type="hidden" name="oldthumbnail1" value="<?=$ViewData["thumbnail1"]?>" />
                <input type="hidden" name="oldthumbnail2" value="<?=$ViewData["thumbnail2"]?>" />
                <input type="hidden" name="oldFile" value="<?=$ViewData["filename"]?>" />
                <input type="hidden" name="oldOrgFile" value="<?=$ViewData["orgfilename"]?>" />
                
                <table class="board_write">
                    <colgroup>
                        <col style="width:25%">
                        <col style="width:75%">
                    </colgroup>
                    <tr>
                        <th>type</th>
                        <td>
                            <select name="type" id="type">
                                <option value="0">- select one -</option>
                                <option value="1" <?=$webhelper->MakeSelectedValue("1", $ViewData["type"])?>>Conference</option>
                                <option value="2" <?=$webhelper->MakeSelectedValue("2", $ViewData["type"])?>>Seminar</option>
                                <option value="3" <?=$webhelper->MakeSelectedValue("3", $ViewData["type"])?>>Meeting</option>
                                <option value="4" <?=$webhelper->MakeSelectedValue("4", $ViewData["type"])?>>Workshop</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>start day</th>
                        <td>
                            <select name="startmonth" id="startmonth">
                                <option value="0"  <?=$webhelper->MakeSelectedValue("0", $ViewData["startmonth"])?>>- Month -</option>
                                <option value="01" <?=$webhelper->MakeSelectedValue("01", $ViewData["startmonth"])?>>January</option>
                                <option value="02" <?=$webhelper->MakeSelectedValue("02", $ViewData["startmonth"])?>>February</option>
                                <option value="03" <?=$webhelper->MakeSelectedValue("03", $ViewData["startmonth"])?>>March</option>
                                <option value="04" <?=$webhelper->MakeSelectedValue("04", $ViewData["startmonth"])?>>April</option>
                                <option value="05" <?=$webhelper->MakeSelectedValue("05", $ViewData["startmonth"])?>>May</option>
                                <option value="06" <?=$webhelper->MakeSelectedValue("06", $ViewData["startmonth"])?>>June</option>
                                <option value="07" <?=$webhelper->MakeSelectedValue("07", $ViewData["startmonth"])?>>July</option>
                                <option value="08" <?=$webhelper->MakeSelectedValue("08", $ViewData["startmonth"])?>>August</option>
                                <option value="09" <?=$webhelper->MakeSelectedValue("09", $ViewData["startmonth"])?>>September</option>
                                <option value="10" <?=$webhelper->MakeSelectedValue("10", $ViewData["startmonth"])?>>October</option>
                                <option value="11" <?=$webhelper->MakeSelectedValue("11", $ViewData["startmonth"])?>>November</option>
                                <option value="12" <?=$webhelper->MakeSelectedValue("12", $ViewData["startmonth"])?>>December</option>
                            </select>
                            <select name="startday" id="startday">
                                <option value="0"  <?=$webhelper->MakeSelectedValue("0", $ViewData["startday"])?>>- Day -</option>
                                <option value="01" <?=$webhelper->MakeSelectedValue("01", $ViewData["startday"])?>>01</option>
                                <option value="02" <?=$webhelper->MakeSelectedValue("02", $ViewData["startday"])?>>02</option>
                                <option value="03" <?=$webhelper->MakeSelectedValue("03", $ViewData["startday"])?>>03</option>
                                <option value="04" <?=$webhelper->MakeSelectedValue("04", $ViewData["startday"])?>>04</option>
                                <option value="05" <?=$webhelper->MakeSelectedValue("05", $ViewData["startday"])?>>05</option>
                                <option value="06" <?=$webhelper->MakeSelectedValue("06", $ViewData["startday"])?>>06</option>
                                <option value="07" <?=$webhelper->MakeSelectedValue("07", $ViewData["startday"])?>>07</option>
                                <option value="08" <?=$webhelper->MakeSelectedValue("08", $ViewData["startday"])?>>08</option>
                                <option value="09" <?=$webhelper->MakeSelectedValue("09", $ViewData["startday"])?>>09</option>
                                <option value="10" <?=$webhelper->MakeSelectedValue("10", $ViewData["startday"])?>>10</option>
                                <option value="11" <?=$webhelper->MakeSelectedValue("11", $ViewData["startday"])?>>11</option>
                                <option value="12" <?=$webhelper->MakeSelectedValue("12", $ViewData["startday"])?>>12</option>
                                <option value="13" <?=$webhelper->MakeSelectedValue("13", $ViewData["startday"])?>>13</option>
                                <option value="14" <?=$webhelper->MakeSelectedValue("14", $ViewData["startday"])?>>14</option>
                                <option value="15" <?=$webhelper->MakeSelectedValue("15", $ViewData["startday"])?>>15</option>
                                <option value="16" <?=$webhelper->MakeSelectedValue("16", $ViewData["startday"])?>>16</option>
                                <option value="17" <?=$webhelper->MakeSelectedValue("17", $ViewData["startday"])?>>17</option>
                                <option value="18" <?=$webhelper->MakeSelectedValue("18", $ViewData["startday"])?>>18</option>
                                <option value="19" <?=$webhelper->MakeSelectedValue("19", $ViewData["startday"])?>>19</option>
                                <option value="20" <?=$webhelper->MakeSelectedValue("20", $ViewData["startday"])?>>20</option>
                                <option value="21" <?=$webhelper->MakeSelectedValue("21", $ViewData["startday"])?>>21</option>
                                <option value="22" <?=$webhelper->MakeSelectedValue("22", $ViewData["startday"])?>>22</option>
                                <option value="23" <?=$webhelper->MakeSelectedValue("23", $ViewData["startday"])?>>23</option>
                                <option value="24" <?=$webhelper->MakeSelectedValue("24", $ViewData["startday"])?>>24</option>
                                <option value="25" <?=$webhelper->MakeSelectedValue("25", $ViewData["startday"])?>>25</option>
                                <option value="26" <?=$webhelper->MakeSelectedValue("26", $ViewData["startday"])?>>26</option>
                                <option value="27" <?=$webhelper->MakeSelectedValue("27", $ViewData["startday"])?>>27</option>
                                <option value="28" <?=$webhelper->MakeSelectedValue("28", $ViewData["startday"])?>>28</option>
                                <option value="29" <?=$webhelper->MakeSelectedValue("29", $ViewData["startday"])?>>29</option>
                                <option value="30" <?=$webhelper->MakeSelectedValue("30", $ViewData["startday"])?>>30</option>
                                <option value="31" <?=$webhelper->MakeSelectedValue("31", $ViewData["startday"])?>>31</option>
                            </select>
                            <input type="text" name="startyear" id="startyear" value="<?=$ViewData["startyear"]?>" maxlength="4" style="width:60px">
                        </td>
                    </tr>
                    <tr>
                        <th>end day</th>
                        <td>
                            <select name="endmonth" id="endmonth">
                                <option value="0"  <?=$webhelper->MakeSelectedValue("0", $ViewData["endmonth"])?>>- Month -</option>
                                <option value="01" <?=$webhelper->MakeSelectedValue("01", $ViewData["endmonth"])?>>January</option>
                                <option value="02" <?=$webhelper->MakeSelectedValue("02", $ViewData["endmonth"])?>>February</option>
                                <option value="03" <?=$webhelper->MakeSelectedValue("03", $ViewData["endmonth"])?>>March</option>
                                <option value="04" <?=$webhelper->MakeSelectedValue("04", $ViewData["endmonth"])?>>April</option>
                                <option value="05" <?=$webhelper->MakeSelectedValue("05", $ViewData["endmonth"])?>>May</option>
                                <option value="06" <?=$webhelper->MakeSelectedValue("06", $ViewData["endmonth"])?>>June</option>
                                <option value="07" <?=$webhelper->MakeSelectedValue("07", $ViewData["endmonth"])?>>July</option>
                                <option value="08" <?=$webhelper->MakeSelectedValue("08", $ViewData["endmonth"])?>>August</option>
                                <option value="09" <?=$webhelper->MakeSelectedValue("09", $ViewData["endmonth"])?>>September</option>
                                <option value="10" <?=$webhelper->MakeSelectedValue("10", $ViewData["endmonth"])?>>October</option>
                                <option value="11" <?=$webhelper->MakeSelectedValue("11", $ViewData["endmonth"])?>>November</option>
                                <option value="12" <?=$webhelper->MakeSelectedValue("12", $ViewData["endmonth"])?>>December</option>
                            </select>
                            <select name="endday" id="endday">
                                <option value="0"  <?=$webhelper->MakeSelectedValue("0", $ViewData["endday"])?>>- Day -</option>
                                <option value="01" <?=$webhelper->MakeSelectedValue("01", $ViewData["endday"])?>>01</option>
                                <option value="02" <?=$webhelper->MakeSelectedValue("02", $ViewData["endday"])?>>02</option>
                                <option value="03" <?=$webhelper->MakeSelectedValue("03", $ViewData["endday"])?>>03</option>
                                <option value="04" <?=$webhelper->MakeSelectedValue("04", $ViewData["endday"])?>>04</option>
                                <option value="05" <?=$webhelper->MakeSelectedValue("05", $ViewData["endday"])?>>05</option>
                                <option value="06" <?=$webhelper->MakeSelectedValue("06", $ViewData["endday"])?>>06</option>
                                <option value="07" <?=$webhelper->MakeSelectedValue("07", $ViewData["endday"])?>>07</option>
                                <option value="08" <?=$webhelper->MakeSelectedValue("08", $ViewData["endday"])?>>08</option>
                                <option value="09" <?=$webhelper->MakeSelectedValue("09", $ViewData["endday"])?>>09</option>
                                <option value="10" <?=$webhelper->MakeSelectedValue("10", $ViewData["endday"])?>>10</option>
                                <option value="11" <?=$webhelper->MakeSelectedValue("11", $ViewData["endday"])?>>11</option>
                                <option value="12" <?=$webhelper->MakeSelectedValue("12", $ViewData["endday"])?>>12</option>
                                <option value="13" <?=$webhelper->MakeSelectedValue("13", $ViewData["endday"])?>>13</option>
                                <option value="14" <?=$webhelper->MakeSelectedValue("14", $ViewData["endday"])?>>14</option>
                                <option value="15" <?=$webhelper->MakeSelectedValue("15", $ViewData["endday"])?>>15</option>
                                <option value="16" <?=$webhelper->MakeSelectedValue("16", $ViewData["endday"])?>>16</option>
                                <option value="17" <?=$webhelper->MakeSelectedValue("17", $ViewData["endday"])?>>17</option>
                                <option value="18" <?=$webhelper->MakeSelectedValue("18", $ViewData["endday"])?>>18</option>
                                <option value="19" <?=$webhelper->MakeSelectedValue("19", $ViewData["endday"])?>>19</option>
                                <option value="20" <?=$webhelper->MakeSelectedValue("20", $ViewData["endday"])?>>20</option>
                                <option value="21" <?=$webhelper->MakeSelectedValue("21", $ViewData["endday"])?>>21</option>
                                <option value="22" <?=$webhelper->MakeSelectedValue("22", $ViewData["endday"])?>>22</option>
                                <option value="23" <?=$webhelper->MakeSelectedValue("23", $ViewData["endday"])?>>23</option>
                                <option value="24" <?=$webhelper->MakeSelectedValue("24", $ViewData["endday"])?>>24</option>
                                <option value="25" <?=$webhelper->MakeSelectedValue("25", $ViewData["endday"])?>>25</option>
                                <option value="26" <?=$webhelper->MakeSelectedValue("26", $ViewData["endday"])?>>26</option>
                                <option value="27" <?=$webhelper->MakeSelectedValue("27", $ViewData["endday"])?>>27</option>
                                <option value="28" <?=$webhelper->MakeSelectedValue("28", $ViewData["endday"])?>>28</option>
                                <option value="29" <?=$webhelper->MakeSelectedValue("29", $ViewData["endday"])?>>29</option>
                                <option value="30" <?=$webhelper->MakeSelectedValue("30", $ViewData["endday"])?>>30</option>
                                <option value="31" <?=$webhelper->MakeSelectedValue("31", $ViewData["endday"])?>>31</option>
                            </select>
                            <input type="text" name="endyear" id="endyear" value="<?=$ViewData["endyear"]?>" maxlength="4" style="width:60px">
                        </td>
                    </tr>
                    <tr>
                        <th>main title</th>
                        <td><input type="text" name="maintitle" value="<?=$ViewData["maintitle"]?>" id="maintitle" maxlength="200" style="width:350px"></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td><textarea name="subtitle" id="subtitle" style="width:516px;height:70px"><?=$ViewData["subtitle"]?></textarea></td>
                    </tr>
                    <tr>
                        <th>link url</th>
                        <td><input type="text" name="url" value="<?=$ViewData["url"]?>" id="url" maxlength="100" style="width:350px"></td>
                    </tr>
                    <tr>
                        <th>location 1</th>
                        <td><input type="text" name="location1" value="<?=$ViewData["location1"]?>" id="location1" maxlength="100" style="width:350px"></td>
                    </tr>
                    <tr>
                        <th>location 2</th>
                        <td><input type="text" name="location2" value="<?=$ViewData["location2"]?>" id="location2" maxlength="100" style="width:350px"></td>
                    </tr>
                    <tr>
                        <th>booth number</th>
                        <td><input type="text" name="boothnum" value="<?=$ViewData["booth"]?>" id="boothnum" maxlength="50" style="width:115px"></td>
                    </tr>
                    <tr>
                        <th>body</th>
                        <td><textarea name="body" id="body" style="width:516px"><?=$ViewData["body"]?></textarea></td>
                    </tr>
                    <tr>
                        <th>thumbnail file</th>
                        <td>
                            <input type="file" name="thumbnail1">
                            <?php
                                if( $ViewData["thumbnail1"] != "" )
                                {
                                    echo "<div style=\"margin-top:10px\">";
                                    echo "<img src=\"/files/events/" . $ViewData["thumbnail1"] . "\">";
                                    echo "&nbsp;&nbsp;<input type=\"checkbox\" name=\"deleteThumbnail1\" value=\"1\"/> Delete File\n";
                                    echo "</div>";
                                }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>thumbnail file</th>
                        <td>
                            <input type="file" name="thumbnail2">
                            <?php
                                if( $ViewData["thumbnail2"] != "" )
                                {
                                    echo "<div style=\"margin-top:10px\">";
                                    echo "<img src=\"/files/events/" . $ViewData["thumbnail2"] . "\">";
                                    echo "&nbsp;&nbsp;<input type=\"checkbox\" name=\"deleteThumbnail2\" value=\"1\"/> Delete File\n";
                                    echo "</div>";
                                }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>upload file</th>
                        <td>
                           <input type="file" name="up_file">
                           <?php
                                if( $ViewData["filename"] != "" )
                                {
                                    echo "<div style=\"margin-top:10px\">";
                                    echo $ViewData["orgfilename"];
                                    echo "&nbsp;&nbsp;<input type=\"checkbox\" name=\"deleteFile\" value=\"1\"/> Delete File\n";
                                    echo "</div>";
                                }
                            ?>
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
                <div class="side side-right">
                    <a href="javascript:del();" class="bt bt-navy">delete</a>
                </div>
            </div>
            <!-- //form_submit -->

        </div>
        <!-- //contents -->

    </div>
    <!-- //container -->

<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/include/footer.php'); ?>