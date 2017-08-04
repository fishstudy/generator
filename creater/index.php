<?php
$aSystem['hftService'] = [
    'all' => 'controller_modules_model',
    'Controller' => 'controller（控制器）',
    'Modules' => 'modules(逻辑层)',
    'Model' => 'model',
];
$aSystem['xfcrm'] = [
    'all' => 'controller_modules_ajax',
    'Controller' => 'controller（控制器）',
    'Ajax' => 'ajax(ajax控制器)',
    'Modules' => 'modules(逻辑层)',
];
$aSystem['jigouService'] = [
    'all' => 'controller_modules_model_test_fixture',
    'Controller' => 'controller（控制器）',
    'Modules' => 'modules(逻辑层)',
    'Model' => 'model',
    'Test' => 'Test单元测试',
    'Fixture' => 'Fixture基镜',
];
    if(empty($_GET['option'])) {

        ?>
        <html>
        <head>
            <script type="text/javascript" src="http://libs.baidu.com/jquery/2.0.0/jquery.js"></script>
        </head>
        <body>
        <div>
            <table align="center" border="0" width="90%"
                   style="background: #4FB4DE;font-weight: bolder;margin-top: 30px;margin-bottom: 30px;">
                <tbody>
                <tr>
                    <td align="center" width="100%" style="color:#ffffff">代码生成器</td>
                </tr>
                </tbody>
            </table>
        </div>


        <div>
        <form action="/index.php" >
        <table align="center" style="border:1px solid #4FB4DE;background: #c9f1ff" cellpadding="3" cellspacing="1" width="90%">
        <tr style="background:#FFFFFF">
            <td align="center">请 选 择 系 统</td>
            <td>
                <select name="system" id="system">
                    <option value="xfcrm">好房经济系统</option>
                    <option value="hftService">好房拓service</option>
                    <option value="jigouService">机构service</option>
                </select>
            </td>
        </tr>
        <tr style="background:#FFFFFF">
            <td align="center">数 据 库 名 称</td>
            <td>
                <select name="database">
                    <option value="zt_db">zt_db</option>
                </select>
            </td>
        </tr>
        <tr style="background:#FFFFFF">
            <td align="center">请 输 入 表 名</td>
            <td>
                <input name="table">
            </td>
        </tr>
        <tr style="background:#FFFFFF">
            <td align="center">生成文件名称</td>
            <td>
                <input name="filename"><strong>默认生成文件名和表名一致，也可以自定义contact
                    /contact
                </strong>
            </td>
        </tr>
        <tr style="background:#FFFFFF">
        <td align="center">选择生成文件</td>
        <td>
                <select name="fileType" id='fileType'>
                    <?php
                    foreach ($aSystem['xfcrm'] as $key => $val) {
                    ?>
                         <option value="<?php echo $key;?>"><?php echo $val;?></option>
                    <?php
                    }
                   ?>
                    </select>
                </td>
            </tr>
            <tr style="background:#FFFFFF">
                <td align="center">开 发 负 责 人</td>
                <td>
                    <select name="developerSelect" id="developerSelect">
                        <option value="">请选择</option>
                        <option value="ex-wanjiangling213">万江铃</option>
                        <option value="sunwei741">孙伟</option>
                        <option value="yuxuefeng031">于雪锋</option>
                    </select>
                    <input name="developer" id="developer" type="hidden">
                    <input name="developeUM" id="developeUM" type="hidden">
                </td>
            </tr>
            <tr style="background:#FFFFFF">
                <td align="center">开 发 时 间 段</td>
                <?php
                    $day = date("Y.m.d").'~'.date("m.d",strtotime('+3day'));
                ?>
                <td>
                    <input name="developDay" value="<?php
                    echo $day?>"><strong>2017.6.8~6.16</strong>
                </td>
            </tr>
            <tr style="background:#FFFFFF">
                <td align="center" colspan="2">
                    <input type="submit" value="提交">
                    <input type="hidden" name="option" value="create">
                </td>
            </tr>
            </table>
        </form>
    </div>
    <div style="margin-top: 30px;">
        <table>
            <tr>
                <td>
                    <strong>使用说明：</strong>
                </td>
            </tr>
            <tr>
                <td>
                    1：配置各个系统在本地的目录：<br>
                    $global_path = [<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;'hftService'=>'D:/webserver/hft-service',     &nbsp;&nbsp;&nbsp;&nbsp;#好房拓service<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;'xfcrm'=>'D:/webserver/xfcrm/backend',        &nbsp;&nbsp;&nbsp;&nbsp;#xfcrm 好房经济系统<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;'jigouService'=>'D:/webserver/jigou-service', &nbsp;&nbsp;#jigou-service<br>
                    ];
                </td>
            </tr>
        </table></div>
    <script type="text/javascript">
        $(document).ready(function(){
        $("#developerSelect").change(function(){
             $('#developeUM').val($("#developerSelect").val());
             $('#developer').val($("#developerSelect").find("option:selected").text());
            });

            $("#system").change(function(){
                   $.ajax({
                       url:"/index.php?option=ajax&selectsys="+$('#system').val(),
                       success:function(data) {
                           $('#fileType').html(data);
                       }
                   })
            });
        });
    </script>
    </body>
</html>
<?php
    } else if($_GET['option'] == 'create'){
        //创建文件
        define('SERVICE_CLIENT_BIN_PATH', __DIR__);
        $createFile =require(SERVICE_CLIENT_BIN_PATH.'/createFile.php');

    }else if($_GET['option'] == 'ajax'){
        $sys = $_GET['selectsys'];
        $html = '';
        foreach($aSystem[$sys] as $key=>$val) {
            $html .= '<option value="'. $key.'">'. $val.'</option>';
        }
        echo $html;
    }

