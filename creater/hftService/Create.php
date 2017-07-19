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

$rootPath = dirname(dirname(dirname(SERVICE_CLIENT_BIN_PATH))) .'/lsf_service/hft-service/';
//定义MODEL_PATH常量
define('SERVICE_MODEL_PATH', $rootPath.'/app'.'/Models/'.$subDir);
//定义Modules_PATH常量
define('SERVICE_BMODEL_PATH', $rootPath.'/app' .'/Modules/'.$subDir);
//定义CONTROLLER_PATH常量
define('SERVICE_RPC_CONTROLLER_PATH', $rootPath.'/app/Controllers/'.$subDir);

//Model代码
$createResult = create_models($dbh, $dbname, [$table], $connection,$subDir);
print_r($createResult);

/**
 * 生成bmodel准备
 */
$bNameSpace    = 'App\\Modules\\'.trim($subDir,'/'); //命名空间
$bClassName = $fileName;
//接收的uri
//$bUri = !empty($options['b']) ? $options['b'] : $options['t'];
//$bUri = trim($bUri, '/\\');
////获取model参数
//$bUriTmp = explode('/', $bUri);
////控制器根目录
//echo 'bmodel path: ' . SERVICE_BMODEL_PATH . PHP_EOL;
//
////规范后的url  目录分隔符的首字母大写
//$bUriSpec   = get_uri_specification($bUri);
//$bPrefix    = "\\App\\" . $service[$options['s']] . '\\Modules\\'; //命名空间前缀
//$bSuffix    = '';
//$bNameSpace = ''; //命名空间
////类名
//$bClassName      = $bPrefix . $bUriSpec;
//$bClassName4file = mb_substr(trim($bClassName, '\\\/'), mb_strrpos($bClassName, '\\'));
////文件
//$bFilename = SERVICE_BMODEL_PATH . '/' . $bUriSpec . '.php';
//$bFilename = str_replace('\\', '/', $bFilename);
//echo "bmodel fileName: " . $bFilename . PHP_EOL;
//echo "bmodel className: " . $bClassName . PHP_EOL;
//$bNameSpace = mb_substr(trim($bClassName, '\\\/'), 0, mb_strrpos($bClassName, '\\') - 1);//当前类的命名空间
//echo 'bmodel namespace: ' . $bNameSpace . PHP_EOL;
//$bFilePath = dirname($bFilename);
//if (!is_dir($bFilePath)) {
//    mkdir($bFilePath, 0777, true);
//}
//
////BModel代码
//if (!file_exists($bFilename)) {
//    $bmodelCode       = '';
//    $bmodelTplContent = file_get_contents(SERVICE_CLIENT_BIN_PATH . '/moduleTpl.txt');
//    $bmodelCode       = str_replace(
//        array('{{$service}}', '{{$nameSpace}}', '{{$className}}', '{{$modelDirectory}}', '{{$modelName}}', '{{$model}}'),
//        array($service[$options['s']], $bNameSpace, $bClassName4file, $model_dir, $model, $model . 'Model'),
//        $bmodelTplContent);
//    file_put_contents($bFilename, $bmodelCode);
//    echo 'create bmodel success' . PHP_EOL;
//} else {
//    echo "target bmodel already exists!" . PHP_EOL;
//}

/**
 * 生成controller准备
 */
//控制器根目录

createControllerFile($dbh, $dbname,$table,$fileName,$subDir,
    $bNameSpace,$bClassName,$developDay);




/*********** 上面用到的方法  *************/
/**
 * 规范uri
 * @param $uri
 * @return string
 */
function get_uri_specification($uri)
{
    return ucfirst(preg_replace_callback('/[\/]{1}([a-zA-Z])/', function ($match) {
        return '\\' . strtoupper($match[1]);
    }, str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $uri)))));
}


/**
 * 获取表所有字段
 * @param $dbh
 * @param $table
 * @return array
 */
function fields($dbh, $table)
{
    try {
        $table = 't_' . preg_replace('/^t_/i', '', trim($table));
        $sql   = sprintf('desc %s', $table);
        $stmt  = $dbh->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $fields  = array();
        foreach ($results as $result) {
            $fields[] = $result['Field'];
        }
        return $fields;
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit;
    }
}

/**
 * 获取表所有字段
 * @param $dbh
 * @param $table
 * @return array
 */
function fields_types($dbh, $table, $filter_columns = array())
{
    try {
        $table = 't_' . preg_replace('/^t_/i', '', trim($table));
        $sql   = sprintf('desc %s', $table);
        $stmt  = $dbh->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $fields  = array();
        foreach ($results as $result) {
            if (in_array($result['Field'], $filter_columns)) {
                continue;
            }
            $fields[$result['Field']]           = $result;
            $fields[$result['Field']]['format'] = field_format($result['Type']);
        }
        return $fields;
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit;
    }
}

/**
 * 获取表所有字段
 * @param $dbh
 * @param $table
 * @return array
 */
function fields_create($dbh, $table, $filter_columns = array())
{
    try {
        $table = 't_' . preg_replace('/^t_/i', '', trim($table));
        $sql   = sprintf('SHOW FULL COLUMNS FROM %s', $table);
        $stmt  = $dbh->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $fields  = array();
        foreach ($results as $row) {
            if (in_array($row['Field'], $filter_columns)) {
                continue;
            }
            $fields[$row['Field']]['fieldName'] = $row['Field'];
            $length = strpos($row['Type'],'(');
            if($length === false) {
                $length = strlen($row['Type']);
            }
            $val_type = substr($row['Type'], 0, $length);
            $fields[$row['Field']]['Type'] = field_format($val_type);
            $fields[$row['Field']]['Comment'] = $row['Comment'];
        }
        return $fields;
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit;
    }
}

/**
 * 根据数据库字段类型转化为验证类型
 * @param $type
 * @return mixed
 */
function field_format($type)
{
    $formats = array(
        'tinyint'    => 'numeric',
        'smallint'   => 'numeric',
        'mediumint'  => 'numeric',
        'int'        => 'numeric',
        'bigint'     => 'numeric',
        'float'      => 'numeric',
        'double'     => 'numeric',
        'decimal'    => 'numeric',
        'char'       => 'string',
        'varchar'    => 'string',
        'tinytext'   => 'string',
        'text'       => 'string',
        'mediumtext' => 'string',
        'longtext'   => 'string',
        'date'       => 'string',
        'time'       => 'string',
        'datetime'   => 'string',
        'timestamp'  => 'string',
        'enum'       => 'string',
    );
    preg_match_all('/[a-zA-z]+/', $type, $matches);
    return $formats[$matches[0][0]];
}

/**
 * 拼接字段验证字符串
 * @param array $fields
 * @return string
 */
function fields_validate_format($fields = array())
{
    $str = '';
    foreach ($fields as $key => $field) {
        $str .= "'" . $key . "' => ['required' => true, 'type' => '" . $field['format'] . "', 'message' => ['required' => '缺少" . $key . "参数', 'type' => '" . $key . "参数类型错误']],\r\n\t\t\t";
    }
    return $str;
}

/**
 * 批量创建model
 * @param       $dbh
 * @param       $dbname
 * @param array $tables
 * @param       $replaceCfg
 * @return mixed
 */
function create_models($dbh, $dbname, $tables = [], $connection,$model_dir)
{
    if (!is_dir(SERVICE_MODEL_PATH)) {
        mkdir(SERVICE_MODEL_PATH, 0777, true);
    }
    $modelTplContent = file_get_contents(SERVICE_CLIENT_BIN_PATH .'/hftService/modelTpl.txt');
    foreach ($tables as $table) {
        //驼峰命名新文件名
        $modelFileName = str_replace(' ', '', ucwords(str_replace(array(
            '-',
            '_'
        ), ' ', strtolower(preg_replace('/^t_/i', '', trim($table))))));
        $file   = SERVICE_MODEL_PATH . $modelFileName . ' .php';
        if (!file_exists($file)) {

            $table         = 't_' . preg_replace('/^t_/i', '', trim($table));
            //获取表主键
            $primaryKey = get_primary_fields($dbh, $dbname, $table);
            //获取表字段
            $fields   = fields($dbh, $table);
            $fillable = $fields;
            //生成model文件
            $bool = file_put_contents($file,
                str_replace(array(
                    '{{$dbname}}',
                    '{{$directory}}',
                    '{{$connection}}',
                    '{{$table}}',
                    '{{$filename}}',
                    '{{$primaryKey}}',
                    '{{$columns}}',
                    '{{$fillable}}',

                ), array(
                    $dbname,
                    $model_dir ? '\\'.$model_dir : '',
                    $connection,
                    preg_replace('/^t_/i', '', trim($table)),
                    $modelFileName,
                    sprintf("'%s'", implode(', ', $primaryKey)),
                    $fields ? sprintf("'%s'", implode('\',' . "\r\n\t\t" . '\'', $fields)) : '\'*\'',
                    $fillable ? sprintf("'%s'", implode('\',' . "\r\n\t\t" . '\'', $fillable)) : '\'*\''
                ), $modelTplContent));
            if ($bool) {
                $createResult['success'][] = $file;
            } else {
                $createResult['fail'][] = $file;
            }
        } else {
            $createResult['fail'][] = $file;
        }
    }
    return $createResult;
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
function createControllerFile($dbh, $dbname,$table,$fileName,$subDir,
    $bNameSpace,$bClassName,$developDay){
    echo 'controller path: ' . SERVICE_RPC_CONTROLLER_PATH . PHP_EOL.'<br>';
    $cNameSpace    = 'App\\Http\\Controllers\\V1_0\\'.trim($subDir,'/'); //命名空间
    $endfix    = 'Controller';//文件的后缀
    //类名
    $cClassName      = $fileName . $endfix;
    $cFilename = SERVICE_RPC_CONTROLLER_PATH . $cClassName . '.php';
    $cFilePath = dirname($cFilename);
    if (!is_dir($cFilePath)) {
        mkdir($cFilePath, 0777, true);
    }

    if (!file_exists($cFilename)) {
        $controllTplContent = file_get_contents(SERVICE_CLIENT_BIN_PATH .'/hftService/controllerTpl.txt');
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
            '{{$bNameSpace}}',
            '{{$bClassName}}'
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
                $bNameSpace,
                $bClassName,
            ),
            $controllTplContent);
        file_put_contents($cFilename, $controllerCode);
        echo 'create controller success' . PHP_EOL;
    } else {
        echo "target controller already exists!" . PHP_EOL;
    }
}

/**
 * 获取数据表主键
 * @param $dbh
 * @param $dbname
 * @param $table
 * @return string
 */
function get_primary_fields($dbh, $dbname, $table)
{
    try {
        $table = 't_' . preg_replace('/^t_/i', '', trim($table));
        $sql   = sprintf("SELECT * FROM information_schema.KEY_COLUMN_USAGE
                         WHERE table_name='%s' AND TABLE_SCHEMA='%s' AND CONSTRAINT_NAME = 'PRIMARY'", $table, $dbname);
        $stmt  = $dbh->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $fields  = array();
        foreach ($results as $result) {
            $fields[] = $result['COLUMN_NAME'];
        }
        if (empty($fields)) {
            return ['iAutoId'];
        }
        return $fields;
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit;
    }
}
