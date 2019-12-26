<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/lib/common.php');

$webhelper = new WebHelper();
$dbhelper = new dbHelper();

$PageUrl = "/admin/news/video/list.php";
$PageCode = "020101";
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
$sql = "select pkid, year, month, day, title, body, videourl, thumbnail1, thumbnail2, filename, orgfilename, updatedate from video where pkid = $pkid ";
$ViewData = $dbhelper->RunSQLReturnOneRow($sql);
$dbhelper->dbClose();

if($webhelper->isNull($ViewData))
    $webhelper->AlertMessageAndGo("Error. Please try again.", "/admin/news/video/list.php" . $Parameter);
?>
<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/include/header.php')?>

    <script type="text/javascript">
        function checkFrm()
        {
            var f = document.regFrm;
            var isOk = true;
            
            $("#year").css("border", "");
            $("#month").css("border", "");
            $("#day").css("border", "");
            $("#title").css("border", "");
            $("#videourl").css("border", "");
            
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
            
            if(f.videourl.value == "")
            {
                $("#videourl").css("border", "1px solid #ff0000");
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
                f.action = "/admin/news/video/delete.php";
                f.submit();
            }
        }
    </script>
    
    <div id="container">

        <div id="contents">

            <h2>video</h2>
            
            <form method="post" name="regFrm" action="/admin/news/video/modify.php" enctype="multipart/form-data">
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
                        <th>year</th>
                        <td><input type="text" name="year" id="year" value="<?=$ViewData["year"]?>" value="<?=$nowYear?>" maxlength="4" style="width:60px"></td>
                    </tr>
                    <tr>
                        <th>month</th>
                        <td>
                            <select name="month" id="month">
                                <option value="0"  <?=$webhelper->MakeSelectedValue("0", $ViewData["month"])?>>- Month -</option>
                                <option value="01" <?=$webhelper->MakeSelectedValue("01", $ViewData["month"])?>>January</option>
                                <option value="02" <?=$webhelper->MakeSelectedValue("02", $ViewData["month"])?>>February</option>
                                <option value="03" <?=$webhelper->MakeSelectedValue("03", $ViewData["month"])?>>March</option>
                                <option value="04" <?=$webhelper->MakeSelectedValue("04", $ViewData["month"])?>>April</option>
                                <option value="05" <?=$webhelper->MakeSelectedValue("05", $ViewData["month"])?>>May</option>
                                <option value="06" <?=$webhelper->MakeSelectedValue("06", $ViewData["month"])?>>June</option>
                                <option value="07" <?=$webhelper->MakeSelectedValue("07", $ViewData["month"])?>>July</option>
                                <option value="08" <?=$webhelper->MakeSelectedValue("08", $ViewData["month"])?>>August</option>
                                <option value="09" <?=$webhelper->MakeSelectedValue("09", $ViewData["month"])?>>September</option>
                                <option value="10" <?=$webhelper->MakeSelectedValue("10", $ViewData["month"])?>>October</option>
                                <option value="11" <?=$webhelper->MakeSelectedValue("11", $ViewData["month"])?>>November</option>
                                <option value="12" <?=$webhelper->MakeSelectedValue("12", $ViewData["month"])?>>December</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>day</th>
                        <td>
                            <select name="day" id="day">
                                <option value="0"  <?=$webhelper->MakeSelectedValue("0", $ViewData["day"])?>>- Day -</option>
                                <option value="01" <?=$webhelper->MakeSelectedValue("01", $ViewData["day"])?>>01</option>
                                <option value="02" <?=$webhelper->MakeSelectedValue("02", $ViewData["day"])?>>02</option>
                                <option value="03" <?=$webhelper->MakeSelectedValue("03", $ViewData["day"])?>>03</option>
                                <option value="04" <?=$webhelper->MakeSelectedValue("04", $ViewData["day"])?>>04</option>
                                <option value="05" <?=$webhelper->MakeSelectedValue("05", $ViewData["day"])?>>05</option>
                                <option value="06" <?=$webhelper->MakeSelectedValue("06", $ViewData["day"])?>>06</option>
                                <option value="07" <?=$webhelper->MakeSelectedValue("07", $ViewData["day"])?>>07</option>
                                <option value="08" <?=$webhelper->MakeSelectedValue("08", $ViewData["day"])?>>08</option>
                                <option value="09" <?=$webhelper->MakeSelectedValue("09", $ViewData["day"])?>>09</option>
                                <option value="10" <?=$webhelper->MakeSelectedValue("10", $ViewData["day"])?>>10</option>
                                <option value="11" <?=$webhelper->MakeSelectedValue("11", $ViewData["day"])?>>11</option>
                                <option value="12" <?=$webhelper->MakeSelectedValue("12", $ViewData["day"])?>>12</option>
                                <option value="13" <?=$webhelper->MakeSelectedValue("13", $ViewData["day"])?>>13</option>
                                <option value="14" <?=$webhelper->MakeSelectedValue("14", $ViewData["day"])?>>14</option>
                                <option value="15" <?=$webhelper->MakeSelectedValue("15", $ViewData["day"])?>>15</option>
                                <option value="16" <?=$webhelper->MakeSelectedValue("16", $ViewData["day"])?>>16</option>
                                <option value="17" <?=$webhelper->MakeSelectedValue("17", $ViewData["day"])?>>17</option>
                                <option value="18" <?=$webhelper->MakeSelectedValue("18", $ViewData["day"])?>>18</option>
                                <option value="19" <?=$webhelper->MakeSelectedValue("19", $ViewData["day"])?>>19</option>
                                <option value="20" <?=$webhelper->MakeSelectedValue("20", $ViewData["day"])?>>20</option>
                                <option value="21" <?=$webhelper->MakeSelectedValue("21", $ViewData["day"])?>>21</option>
                                <option value="22" <?=$webhelper->MakeSelectedValue("22", $ViewData["day"])?>>22</option>
                                <option value="23" <?=$webhelper->MakeSelectedValue("23", $ViewData["day"])?>>23</option>
                                <option value="24" <?=$webhelper->MakeSelectedValue("24", $ViewData["day"])?>>24</option>
                                <option value="25" <?=$webhelper->MakeSelectedValue("25", $ViewData["day"])?>>25</option>
                                <option value="26" <?=$webhelper->MakeSelectedValue("26", $ViewData["day"])?>>26</option>
                                <option value="27" <?=$webhelper->MakeSelectedValue("27", $ViewData["day"])?>>27</option>
                                <option value="28" <?=$webhelper->MakeSelectedValue("28", $ViewData["day"])?>>28</option>
                                <option value="29" <?=$webhelper->MakeSelectedValue("29", $ViewData["day"])?>>29</option>
                                <option value="30" <?=$webhelper->MakeSelectedValue("30", $ViewData["day"])?>>30</option>
                                <option value="31" <?=$webhelper->MakeSelectedValue("31", $ViewData["day"])?>>31</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>title</th>
                        <td><input type="text" name="title" value="<?=$ViewData["title"]?>" id="title" maxlength="200" style="width:350px"></td>
                    </tr>
                    <tr>
                        <th>description</th>
                        <td><textarea name="body" id="body" style="width:516px;height:250px"><?=$ViewData["body"]?></textarea></td>
                    </tr>
                    <tr>
                        <th>thumbnail file</th>
                        <td>
                            <input type="file" name="thumbnail1">
                            <?php
                                if( $ViewData["thumbnail1"] != "" )
                                {
                                    echo "<div style=\"margin-top:10px\">";
                                    echo "<img src=\"/files/video/" . $ViewData["thumbnail1"] . "\">";
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
                                    echo "<img src=\"/files/video/" . $ViewData["thumbnail2"] . "\">";
                                    echo "&nbsp;&nbsp;<input type=\"checkbox\" name=\"deleteThumbnail2\" value=\"1\"/> Delete File\n";
                                    echo "</div>";
                                }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>file</th>
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
                    <tr>
                        <th>youtube url</th>
                        <td><input type="text" name="videourl" value="<?=$ViewData["videourl"]?>" id="videourl" maxlength="500" style="width:350px"></td>
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


<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/admin/include/footer.php')?>