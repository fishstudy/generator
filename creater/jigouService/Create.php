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
    .'/lsf_service/jigou-service';
//定义MODEL_PATH常量
define('SERVICE_MODEL_PATH', $rootPath.'/app'.'/Jigou/Models/');
//定义Modules_PATH常量
define('SERVICE_MODULES_PATH', $rootPath.'/app' .'/Jigou/Modules/'.$subDir);
//定义CONTROLLER_PATH常量
define('SERVICE_RPC_CONTROLLER_PATH', $rootPath.'/app/Jigou/Controllers/' .$subDir);
define('SERVICE_RPC_TEST_PATH', $rootPath.'/tests/Jigou/Controllers/' .$subDir);
define('SERVICE_RPC_FIXTURE_PATH', $rootPath.'/tests/fixtures/database/');

$sTableCommet =  get_table_comment($dbh, $table);

//Model代码
create_models($dbh, $dbname, [$table], $connection,$subDir,$fileName);
create_modules($dbname,$subDir, $fileName);


/**
 * 生成controller准备
 */
//控制器根目录

createControllerFile($dbh, $dbname,$table,$fileName,$subDir, $developDay);
createTest($dbh, $dbname,$table,$fileName,$subDir, $developDay);
createFixture($dbh, $dbname,$table);
function createFixture($dbh, $dbname,$table){

    //类名
    $fixtureName      = SERVICE_RPC_FIXTURE_PATH . $dbname.'/t_'.$table.'.php';

    $fFilePath = dirname($fixtureName);
    if (!is_dir($fFilePath)) {
        mkdir($fFilePath, 0777, true);
    }
    if (!file_exists($fixtureName)) {
        $fixTplContent = file_get_contents(SERVICE_CLIENT_BIN_PATH .'/jigouService/FixtureTpl.txt');
        $sInit = '';
        $fields             = fields_create($dbh, $table);
        foreach($fields as $row) {
            $Type = str_pad($row['Type'],8,' ',STR_PAD_RIGHT);
            $vFieldName = str_pad("'".$row['fieldName']."'",24,' ',
                STR_PAD_RIGHT);
            $sInit .= "           ".$vFieldName." => ''" .", #"
                .$row['Comment'].PHP_EOL; ;
        }
        $sInit = rtrim($sInit,PHP_EOL);

        $fixtureCode     = str_replace(array(
            '{{$dbname}}',
            '{{$table}}',
            '{{$sInit}}'
        ),
            array(
                $dbname,
                $table,
                $sInit,
            ),
            $fixTplContent);
        file_put_contents($fixtureName, $fixtureCode);
        echo 'create fixture success' . '<br>'.$fixtureName.'<br><br><br>';
    } else {
        echo "target fixture already exists!" .'<br>'.$fixtureName .'<br><br><br>';
    }
}

function createTest($dbh, $dbname,$table,$fileName,$subDir, $developDay){
        if(!empty($subDir)) {
            $tNameSpace   = 'Tests\\App\\Jigou\\Controllers\\'.trim($subDir, '/');
            //命名空间
        } else {
            $tNameSpace    = 'Tests\\App\\Jigou\\Controllers'; //命名空间
        }

        $endfix    = 'Test';//文件的后缀
        //类名
        $tClassName      = $fileName . $endfix;
        if(!empty($subDir)) {
            $tFilename = SERVICE_RPC_TEST_PATH . $tClassName . '.php';
        } else {
            $tFilename = SERVICE_RPC_TEST_PATH . $tClassName . '.php';
        }
        $tFilePath = dirname($tFilename);
        if (!is_dir($tFilePath)) {
            mkdir($tFilePath, 0777, true);
        }
    if (!file_exists($tFilename)) {
        $sInit = '';
        $fields             = fields_create($dbh, $table, array_merge(['create_time', 'update_time', 'delete_time']));
        foreach($fields as $row) {
            $Type = str_pad($row['Type'],8,' ',STR_PAD_RIGHT);
            $vFieldName = str_pad("'".$row['fieldName']."'",24,' ',
                STR_PAD_RIGHT);
            $sInit .= "           ".$vFieldName." => ''" .", #"
                .$row['Comment'].PHP_EOL; ;
        }
        $sInit = rtrim($sInit,PHP_EOL);
        $controllTplContent = file_get_contents(SERVICE_CLIENT_BIN_PATH .'/jigouService/TestTpl.txt');

        $controllerCode     = str_replace(array(
            '{{$day}}',
            '{{$developer}}',
            '{{$developeUM}}',
            '{{$nameSpace}}',
            '{{$className}}',
            '{{$dbname}}',
            '{{$table}}',
            '{{$sInit}}'

        ),
            array(
                $developDay,
                $_GET['developer'],
                $_GET['developeUM'],
                $tNameSpace,
                $tClassName,
                $dbname,
                $table,
                $sInit,
            ),
            $controllTplContent);
        file_put_contents($tFilename, $controllerCode);
        echo 'create Test success' . '<br>'.$tFilename.'<br><br><br>';
    } else {
        echo "target Test already exists!" .'<br>'.$tFilename.'<br><br><br>';
    }
}

/**
 * 创建指定表的modules
 * @param       $dbh
 * @param       $dbname
 * @param array $tables
 * @param       $replaceCfg
 * @return mixed
 */
function create_modules($dbname,$subDir,$filename){
    //创建文件夹
    if (!is_dir(SERVICE_MODULES_PATH)) {
        mkdir(SERVICE_MODULES_PATH, 0777, true);
    }
    //命名空间
    if(!empty($subDir)) {
        $moduleNameSpace    = 'App\\Jigou\\Modules\\'.trim($subDir,'/');
    } else {
        $moduleNameSpace    = 'App\\Jigou\\Modules\\'; //命名空间
    }
    //驼峰命名新文件名
    $file   = SERVICE_MODULES_PATH . $filename . '.php';
    $moduleName = $modelName = ucfirst($filename);
    $modelName = 'M'.$modelName;
   // echo $modelName;die();
    if($dbname == 'zt_db') {
        $modelNameSpace = 'App\\Jigou\\Models\\Zt\\'.$moduleName;
    } else {
        $modelNameSpace = 'App\\Jigou\\Models\\'.$moduleName;
    }
    if (!file_exists($file)) {
        $moduleTplContent = file_get_contents(SERVICE_CLIENT_BIN_PATH . '/jigouService/moduleTpl.txt');
        $moduleCode = str_replace(
            array(
                '{{$moduleNameSpace}}',
                '{{$moduleName}}',
                '{{$modelName}}',
                '{{$modelNameSpace}}',
                '{{$developer}}',
                '{{$developeUM}}',
            ), array(
                $moduleNameSpace,
                $moduleName,
                $modelName,
                $modelNameSpace,
                $_GET['developer'],
                $_GET['developeUM'],
            ), $moduleTplContent
        );
        $bool = file_put_contents($file, $moduleCode);
        if ($bool) {
            echo "create modules file success: <br>".$file."<br><br><br>";
        } else {
            echo "target modules already exists!<br>"  .$file
                .'<br><br><br>';
        }
    } else{
        echo "target modules already exists!<br>"  .$file
            .'<br><br><br>';
    }
}
/**
 * 创建指定表的model
 * @param       $dbh
 * @param       $dbname
 * @param array $tables
 * @param       $replaceCfg
 * @return mixed
 */
function create_models($dbh, $dbname, $tables = [], $connection,$model_dir,$filename)
{

    $modelTplContent = file_get_contents(SERVICE_CLIENT_BIN_PATH .'/jigouService/modelTpl.txt');
    if($dbname == 'zt_db'){
        $dbDir = 'Zt';
    }
    //$model_dir
    $modelDir = SERVICE_MODEL_PATH.$dbDir.'/';
    if (!is_dir($modelDir)) {
        mkdir($modelDir, 0777, true);
    }
    foreach ($tables as $table) {
        //驼峰命名新文件名
        $file   = $modelDir. $filename . '.php';
        if (!file_exists($file)) {

            $table         = 't_' . preg_replace('/^t_/i', '', trim($table));
            //获取表主键
            $primaryKey = get_primary_fields($dbh, $dbname, $table);
            //获取表字段
            $fields   = fields($dbh, $table);
            $fieldDesc = '';
            if(!empty($fields)) {
                foreach($fields as $field)
                $fieldDesc .= '       '."'".$field."',".PHP_EOL;
            } else {
                //$fieldDesc = '';
            }
            $fieldDesc = trim($fieldDesc,PHP_EOL);
            //生成model文件
            $bool = file_put_contents($file,
                str_replace(array(
                    '{{$dbname}}',
                    '{{$dbDir}}',
                    '{{$directory}}',
                    '{{$connection}}',
                    '{{$table}}',
                    '{{$filename}}',
                    '{{$primaryKey}}',
                    '{{$fieldDesc}}',


                ), array(
                    $dbname,
                    $dbDir,
                    $model_dir ? '\\'.$model_dir : '',
                    $connection,
                    preg_replace('/^t_/i', '', trim($table)),
                    $filename,
                    sprintf("'%s'", implode(', ', $primaryKey)),
                    $fieldDesc
                ), $modelTplContent));
            if ($bool) {
                echo 'create model file success:<br>'.$file.'<br><br><br>';
            } else {
                echo "target model already exists!<br>"  .$file
                    .'<br><br><br>';
            }
        } else {
            echo "target model already exists!<br>"  .$file
                .'<br><br><br>';
        }
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
function createControllerFile($dbh, $dbname,$table,$fileName,$subDir, $developDay){
    if(!empty($subDir)) {
        $cNameSpace    = 'App\\Jigou\\Controllers\\'.trim($subDir,'/'); //命名空间
    } else {
        $cNameSpace    = 'App\\Jigou\\Controllers'; //命名空间
    }

    $endfix    = '';//文件的后缀
    //类名
    $cClassName      = $fileName . $endfix;
    $cFilename = SERVICE_RPC_CONTROLLER_PATH . $cClassName . '.php';
    $cFilePath = dirname($cFilename);
    if (!is_dir($cFilePath)) {
        mkdir($cFilePath, 0777, true);
    }

    $subDir = trim($subDir,'/');
    if(!empty($subDir)) {
        $subDir = ucfirst($subDir);
    }
    $moduleName = ucfirst($fileName);

    // echo $modelName;die();$subDir
    if(!empty($subDir)) {
        $moduleNameSpace = 'App\\Jigou\\Modules\\'.$subDir.'\\'.$moduleName;
    } else {
        $moduleNameSpace = 'App\\Jigou\\Modules\\'.$moduleName;
    }
    $moduleName = 'M'.$moduleName;
    if (!file_exists($cFilename)) {
        $controllTplContent = file_get_contents(SERVICE_CLIENT_BIN_PATH .'/jigouService/controllerTpl.txt');
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
        //'{{$field}}',
        //$fields ? fields_validate_format($fields) : '*',
        $controllerCode     = str_replace(array(
            '{{$developDay}}',
            '{{$developer}}',
            '{{$developeUM}}',
            '{{$nameSpace}}',
            '{{$className}}',
            '{{$primaryKey}}',
            '{{$fieldDesc}}',
            '{{$listDesc}}',
            '{{$validate}}',
            '{{$bNameSpace}}',
            '{{$bClassName}}'
        ),
            array(
                $developDay,
                $_GET['developer'],
                $_GET['developeUM'],
                $cNameSpace,
                $cClassName,
                $primaryKey ? sprintf("%s", implode('\',' . '\'', $primaryKey)) : '\'*\'',
                $fieldDesc,
                $listDesc,
                $validate,
                $moduleNameSpace,
                $moduleName,
            ),
            $controllTplContent);
        file_put_contents($cFilename, $controllerCode);
        echo 'create controller success' . '<br>'.$cFilename.'<br><br><br>';
    } else {
        echo "target controller already exists!" . '<br>'.$cFilename.'<br><br><br>';
    }
}