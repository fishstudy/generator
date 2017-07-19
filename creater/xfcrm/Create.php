<?php
/**
 * 执行结果：
 *  生成并覆盖model文件：app/User/Models/User/UserExt.php
 *  生成bmodel文件：app/User/Modules/User/UserExt.php 注：如原来有该文件则不会重复生成
 *  生成controller文件：app/User/Controllers/User/UserExt.php 注：如原来有该文件则不会重复生成
 *  注：生成的文件名均采用驼峰命名法
 */
/**
 * 一键生成controller、bmodel、model 使用范例:php create.php -s user -t user_ext -b user/user_ext -c user/user_ext -d user_db
 */
//引入配置文件
require SERVICE_CLIENT_BIN_PATH . '/conf.php';
require SERVICE_CLIENT_BIN_PATH . '/common.php';
//参数处理
$dbname = $_GET['database'];
$system = $_GET['system'];
$table = $_GET['table'];

$fileName = trim($_GET['filename']);
$developDay = trim($_GET['developDay']);
$dirTemp = explode('/',$fileName);
$count = count($dirTemp);
if($count == 1) {
    $fileName = $dirTemp[0];
    $subDir = '';
} else if($count == 2) {
    $fileName = $dirTemp[1];
    $subDir =  ucfirst($dirTemp[0]).'/';
} else {
    die('文件名称不合法');
}

//创建数据库连接
try {
    $dsn = "mysql:host={$host};dbname={$dbname}";    //数据库dsn连接
    $dbh = new PDO($dsn, $user, $password,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8", PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit;
}

$rootPath = dirname(dirname(dirname(SERVICE_CLIENT_BIN_PATH)))
    .'/ananzu/xfcrm/backend';
//定义Modules_PATH常量
define('SERVICE_BMODEL_PATH', $rootPath.'/app' .'/Modules/'.$subDir);
//定义CONTROLLER_PATH常量
define('SERVICE_RPC_CONTROLLER_PATH', $rootPath.'/app/Http/Controllers/V2_0/'
    .$subDir);
$sTableCommet =  get_table_comment($dbh, $table);

//modulel代码
$createResult = create_modules($subDir,$fileName,$developDay);

//生成ajax
createAjaxFile($dbh, $dbname,$table,$fileName,$subDir, $developDay,$sTableCommet);

//生成controller
createControllerFile($dbh, $dbname,$table,$fileName,$subDir, $developDay,$sTableCommet);

/*********** 上面用到的方法  *************/







/**
 * 生成对应的Controller文件
 * @param $dbh 数据库连接
 * @param $dbname 数据库名称
 * @param $table  表名
 * @param $cFilename Controller 文件名
 * @param $cNameSpace Controller的命名空间
 * @param $cClassName Controller类名
 * @param $bNameSpace Controller所用的Module的命名空间
 * @param $bClassName Controller所用的Module的类名
 * @param $developer  开发者名称
 * @param $developeUM 开发者的UM账号
 * @param $developDay 开始时间
 */
function createControllerFile($dbh, $dbname,$table,$fileName,$subDir,
    $developDay,$sTableCommet){
    $bNameSpace    = 'App\\Modules\\'.trim($subDir,'/'); //命名空间
    $bClassName = ucfirst($fileName);
    //命名空间
    $cNameSpace    = 'App\\Http\\Controllers\\V2_0\\'.trim($subDir,'/'); //命名空间
    $endfix    = 'Controller';//文件的后缀
    $lowSubDir = strtolower(trim($subDir,'/'));
    $lowFile = strtolower($fileName);
    //类名
    $cClassName      = $fileName . $endfix;
    //路径不存在 创建
    $cFilename = SERVICE_RPC_CONTROLLER_PATH . $cClassName . '.php';
    $cFilePath = dirname($cFilename);
    if (!is_dir($cFilePath)) {
        mkdir($cFilePath, 0777, true);
    }
    //如果文件不粗在
    if (!file_exists($cFilename)) {
        $controllTplContent = file_get_contents(SERVICE_CLIENT_BIN_PATH
            .'/xfcrm/controllerTpl.txt');
        $primaryKey         = get_primary_fields($dbh, $dbname, $table);
        $fields             = fields_create($dbh, $table, array_merge(['create_time', 'update_time', 'delete_time']));
        $fieldDesc = '';
        $listDesc = '';
        $validate = '';
        foreach($fields as $row) {
            $Type = str_pad($row['Type'],8,' ',STR_PAD_RIGHT);
            $fieldName = str_pad($row['fieldName'],20,' ',STR_PAD_RIGHT);
            $fieldDesc .= '     * -  '.$Type . '    ' .$fieldName.
                '    #'.$row['Comment'].PHP_EOL;
            $listDesc .= '     *      '.'            ' .$fieldName.
                '    #'.$row['Comment'].PHP_EOL;
            $vFieldName = str_pad("'".$row['fieldName']."'",24,' ',
                STR_PAD_RIGHT);
            $vType = str_pad($row['Type']."'",10,' ',STR_PAD_RIGHT);
            $validate .= "           ".$vFieldName." => 'sometimes|"
                .$vType.", #".$row['Comment'].PHP_EOL; ;
        }
        $fieldDesc = rtrim($fieldDesc,PHP_EOL);
        $validate = rtrim($validate,PHP_EOL);
        $listDesc = rtrim($listDesc,PHP_EOL);
        $controllerCode     = str_replace(array(
            '{{$developDay}}',
            '{{$developer}}',
            '{{$developeUM}}',
            '{{$nameSpace}}',
            '{{$className}}',
            //'{{$field}}',
            '{{$primaryKey}}',
            '{{$fieldDesc}}',
            '{{$listDesc}}',
            '{{$validate}}',
            '{{$lowSubDir}}',
            '{{$lowFile}}',
            '{{$bNameSpace}}',
            '{{$bClassName}}',
            '{{$sTableCommet}}',
        ),
            array(
                $developDay,
                $_GET['developer'],
                $_GET['developeUM'],
                $cNameSpace,
                $cClassName,
                //$fields ? fields_validate_format($fields) : '*',
                $primaryKey ? sprintf("%s", implode('\',' . '\'', $primaryKey)) : '\'*\'',
                $fieldDesc,
                $listDesc,
                $validate,
                $lowSubDir,
                $lowFile,
                $bNameSpace,
                $bClassName,
                $sTableCommet,
            ),
            $controllTplContent);
        file_put_contents($cFilename, $controllerCode);
        echo 'create controller success' . '<br>'.$cFilename. '<br><br><br>';
    } else {
        echo "target controller already exists!" . '<br>'.$cFilename.'<br><br><br>';
    }

    //产生BaseController
    $baseClassName = "Base".$endfix;
    $baseFileName = SERVICE_RPC_CONTROLLER_PATH . $baseClassName . '.php';
    if(!file_exists($baseFileName)){
        $baseControllTpl = file_get_contents(SERVICE_CLIENT_BIN_PATH .'/xfcrm/BaseControllerTpl.txt');
        $controllerCode     = str_replace(
            array('{{$nameSpace}}',),
            array($cNameSpace,),
            $baseControllTpl
        );
        $bool = file_put_contents($baseFileName, $controllerCode);
        if($bool) {
            echo 'create BaseController success' . '<br>'.$baseFileName.
                '<br><br><br>';
        } else {
            echo 'target BaseController already exists!' . '<br>'.$baseFileName. '<br><br><br>';
        }

    }
}


/**
 * 批量创建model
 * @param       $dbh
 * @param       $dbname
 * @param array $tables
 * @param       $replaceCfg
 * @return mixed
 */
function create_modules($model_dir,$fileName,$developDay)
{
    $bClassName = ucfirst($fileName);
    $bNameSpace    = 'App\\Modules\\'.trim($model_dir,'/'); //命名空间
    $bFilename = SERVICE_BMODEL_PATH . $bClassName . '.php';
    $bFilePath = dirname($bFilename);
    if (!is_dir($bFilePath)) {
        mkdir($bFilePath, 0777, true);
    }
    $modelTplContent = file_get_contents(SERVICE_CLIENT_BIN_PATH .'/xfcrm/moduleTpl.txt');
    if (!file_exists($bFilename)) {
        //生成model文件
        $bool = file_put_contents($bFilename,
            str_replace(array(
                '{{$bClassName}}',
                '{{$bNameSpace}}',
                '{{$developDay}}',
                '{{$developer}}',
                '{{$developeUM}}',

            ), array(
               $bClassName,
               $bNameSpace,
               $developDay,
               $_GET['developer'],
               $_GET['developeUM'],
            ), $modelTplContent));
        if ($bool) {
            echo 'create module success' . '<br>'.$bFilename. '<br><br><br>';
        } else {
            echo "target module already exists!" . '<br>'.$bFilename. '<br><br><br>';
        }
    } else {
        echo "target module already exists!" . '<br>'.$bFilename. '<br><br><br>';
    }

}

/**
 * 生成对应的Controller文件
 * @param $dbh 数据库连接
 * @param $dbname 数据库名称
 * @param $table  表名
 * @param $cFilename Controller 文件名
 * @param $cNameSpace Controller的命名空间
 * @param $cClassName Controller类名
 * @param $bNameSpace Controller所用的Module的命名空间
 * @param $bClassName Controller所用的Module的类名
 * @param $developer  开发者名称
 * @param $developeUM 开发者的UM账号
 * @param $developDay 开始时间
 */
function createAjaxFile($dbh, $dbname,$table,$fileName,$subDir,$developDay,
    $sTableCommet){
    $bNameSpace    = 'App\\Modules\\'.trim($subDir,'/'); //命名空间
    $bClassName = ucfirst($fileName);
    //命名空间
    $jNameSpace    = 'App\\Http\\Controllers\\V2_0\\'.trim($subDir,'/').'\\Ajax';
    //命名空间
    $endfix    = 'Controller';//文件的后缀
    $lowSubDir = strtolower(trim($subDir,'/'));
    $lowFile = strtolower($fileName);
    $directory = ucfirst($fileName);
    //类名
    $jClassName      = $fileName . $endfix;
    //路径不存在 创建
    $jFilename = SERVICE_RPC_CONTROLLER_PATH . 'Ajax/'.$jClassName . '.php';
    $jFilePath = dirname($jFilename);
    if (!is_dir($jFilePath)) {
        mkdir($jFilePath, 0777, true);
    }
    //如果文件不粗在
    if (!file_exists($jFilename)) {
        $controllTplContent = file_get_contents(SERVICE_CLIENT_BIN_PATH
            .'/xfcrm/AjaxControllerTpl.txt');
        $primaryKey         = get_primary_fields($dbh, $dbname, $table);
        $fields             = fields_create($dbh, $table, array_merge(['create_time', 'update_time', 'delete_time']));
        $fieldDesc = '';
        $listDesc = '';
        $validate = '';
        foreach($fields as $row) {
            $Type = str_pad($row['Type'],8,' ',STR_PAD_RIGHT);
            $fieldName = str_pad($row['fieldName'],20,' ',STR_PAD_RIGHT);
            $fieldDesc .= '     * -  '.$Type . '    ' .$fieldName.
                '    #'.$row['Comment'].PHP_EOL;
            $listDesc .= '     *      '.'            ' .$fieldName.
                '    #'.$row['Comment'].PHP_EOL;
            $vFieldName = str_pad("'".$row['fieldName']."'",24,' ',
                STR_PAD_RIGHT);
            $vType = str_pad($row['Type']."'",10,' ',STR_PAD_RIGHT);
            $validate .= "           ".$vFieldName." => 'sometimes|"
                .$vType.", #".$row['Comment'].PHP_EOL; ;
        }
        $fieldDesc = rtrim($fieldDesc,PHP_EOL);
        $validate = rtrim($validate,PHP_EOL);
        $listDesc = rtrim($listDesc,PHP_EOL);
        $controllerCode     = str_replace(array(
            '{{$developDay}}',
            '{{$developer}}',
            '{{$developeUM}}',
            '{{$nameSpace}}',
            '{{$className}}',
            //'{{$field}}',
            '{{$primaryKey}}',
            '{{$fieldDesc}}',
            '{{$listDesc}}',
            '{{$validate}}',
            '{{$lowSubDir}}',
            '{{$lowFile}}',
            '{{$bNameSpace}}',
            '{{$bClassName}}',
            '{{$directory}}',
            '{{$sTableCommet}}',
        ),
            array(
                $developDay,
                $_GET['developer'],
                $_GET['developeUM'],
                $jNameSpace,
                $jClassName,
                //$fields ? fields_validate_format($fields) : '*',
                $primaryKey ? sprintf("%s", implode('\',' . '\'', $primaryKey)) : '\'*\'',
                $fieldDesc,
                $listDesc,
                $validate,
                $lowSubDir,
                $lowFile,
                $bNameSpace,
                $bClassName,
                $directory,
                $sTableCommet
            ),
            $controllTplContent);
        file_put_contents($jFilename, $controllerCode);
        echo 'create AjaxController success' . '<br>'.$jFilename. '<br><br><br>';
    } else {
        echo "target AjaxController already exists!" . '<br>'.$jFilename. '<br><br><br>';
    }
}

