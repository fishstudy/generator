<?php
    if(empty($_GET['option'])) {
?>
<html>
<head>
<script type="text/javascript" src="http://libs.baidu.com/jquery/2.0.0/jquery.js"></script>
</head>
    <body>
    <form action="/index.php" >
        <table>
            <tr>
                <td>请选择系统</td>
                <td>
                    <select name="system">
                        <option value="hftService">新飞拓service</option>
                        <option value="xfcrm">新飞经济系统</option>
                        <option value="jigouService">机构service</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>数据库名称</td>
                <td>
                    <select name="database">
                        <option value="zt_db">zt_db</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>请输入表名</td>
                <td>
                    <input name="table">
                </td>
            </tr>
            <tr>
                <td>生成文件的名称</td>
                <td>
                    <input name="filename"><strong>默认生成文件名和表名一致，也可以自定义contact
                        /contact
                    </strong>
                </td>
            </tr>
            <tr>
                <td>请选择生成的文件</td>
                <td>
                    <select name="fileType">
                        <option value="all">controller_modules_model</option>
                        <option value="Controller">controller（控制器）</option>
                        <option value="Modules">modules(逻辑层)</option>
                        <option value="Model">model</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>开发负责人</td>
                <td>
                    <select name="developerSelect" id="developerSelect">
                        <option value="">请选择</option>
                        <option value="yuxuefeng031">于雪锋</option>
                        <option value="wangkewei775">王珂玮</option>
                        <option value="dongyongsheng413">董永胜</option>
                        <option value="ex-wanjiangling213">万江铃</option>
                    </select>
                    <input name="developer" id="developer" type="hidden">
                    <input name="developeUM" id="developeUM" type="hidden">
                </td>
            </tr>
            <tr>
                <td>开发时间</td>
                <?php
                    $day = date("Y.m.d").'~'.date("m.d",strtotime('+3day'));
                ?>
                <td>
                    <input name="developDay" value="<?php
                    echo $day?>"><strong>2017.6.8~6.16</strong>
                </td>
            </tr>
            </table>

            <input type="submit" value="提交">
            <input type="hidden" name="option" value="create">
        </form>
    <script type="text/javascript">
        $(document).ready(function(){
        $("#developerSelect").change(function(){
             $('#developeUM').val($("#developerSelect").val());
             $('#developer').val($("#developerSelect").find("option:selected").text());
            });
        });
    </script>
    </body>
</html>
<?php
    } else {
        //创建文件
        define('SERVICE_CLIENT_BIN_PATH', __DIR__);
        //require dirname(SERVICE_CLIENT_BIN_PATH) . '/vendor/autoload.php';

        $createFile =require(SERVICE_CLIENT_BIN_PATH.'/createFile.php');

    }


