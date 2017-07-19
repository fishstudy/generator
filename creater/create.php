<?php
/**
 * Created by PhpStorm.
 * User: DONGYONGSHENG413
 * Date: 2016/9/26
 * Time: 16:00
 * 例子（指定文件夹）：php create.php -s user -t user_ext -b user/user_ext -c user/user_ext -d user_db
 * 例子（默认文件夹）：php create.php -s user -t user_ext
 * -t:必传，代表table名，可带前缀(如：t_user_ext)，也可不带前缀(如：user_ext)
 * -s:非必传，代表服务名，即conf.php中service变量的key值中的一个，例如user、pay，默认user
 * -c:非必传，代表controller名,（默认和table名一样）
 * -b:非必传，代表bmodel名,（默认和table名一样）
 * -d:非必传，代表model的数据库名，默认user_db
 * 执行结果：
 *  生成并覆盖model文件：app/User/Models/User/UserExt.php
 *  生成bmodel文件：app/User/Modules/User/UserExt.php 注：如原来有该文件则不会重复生成
 *  生成controller文件：app/User/Controllers/User/UserExt.php 注：如原来有该文件则不会重复生成
 *  注：生成的文件名均采用驼峰命名法
 */
/**
 * 一键生成controller、bmodel、model 使用范例:php create.php -s user -t user_ext -b user/user_ext -c user/user_ext -d user_db
 */
define('SERVICE_CLIENT_BIN_PATH', __DIR__);
require dirname(SERVICE_CLIENT_BIN_PATH) . '/vendor/autoload.php';
//引入配置文件
require SERVICE_CLIENT_BIN_PATH . '/conf.php';

//命令行参数
$options = getopt("b:c:t:s:d:");
if (empty($options['t'])) {
    exit("demo: php create.php -s user -t user_ext -b user/user_ext -c user/user_ext -d user_db" . PHP_EOL .
        '-s:service(necessary,' . implode('|', array_keys($service)) . '; -b:bmodel(necessary); -t:table');
}
//判断s参数是否合法
if (empty($options['s'])) {
    $options['s'] = $default_service;
} else {
    if (!in_array($options['s'], array_keys($service))) {
        exit("param s error,please choose one(" . implode('|', array_keys($service)) . ')');
    }
}
//判断d参数是否合法
if (empty($options['d'])) {
    $options['d'] = $dbname;
    $model_dir = '';
} else {
    if (in_array($options['d'], array_keys($db))) {
        $model_dir = $options['d'] == $dbname ? '' :$db[$options['d']];
        $dbname    = $connection = $options['d'];
    } else {
        exit("param d error,please choose one(" . implode('|', array_keys($db)) . ')');
    }
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

$tmp_model_dir = $model_dir ? $model_dir.'/' : '';
define('SERVICE_MODEL_PATH', dirname(dirname(SERVICE_CLIENT_BIN_PATH)) . '/user-service/app/'. $service[$options['s']] .'/Models/'. $tmp_model_dir);
define('SERVICE_BMODEL_PATH', dirname(dirname(SERVICE_CLIENT_BIN_PATH)) . '/user-service/app/'. $service[$options['s']] .'/Modules/');
define('SERVICE_RPC_CONTROLLER_PATH', dirname(dirname(SERVICE_CLIENT_BIN_PATH)) . '/user-service/app/'. $service[$options['s']] .'/Controllers/');

if (!is_dir(SERVICE_MODEL_PATH)) {
    mkdir(SERVICE_MODEL_PATH, 0777, true);
}

//规范化model uri
$options['t'] = preg_replace('/^t_/i', '', $options['t']);
$model        = get_uri_specification($options['t']);
$mSuffix      = '';

//Model代码
if ($dbh) {
    //创建单个表的model
    if (!empty($options['t'])) {
        $createResult = create_models($dbh, $dbname, [$options['t']], $connection, $service[$options['s']], $model_dir);
    } //创建数据库所有表的model
    else {
        $tables = list_tables($dbh);
        if (is_array($tables) && $tables) {
            $createResult = create_models($dbh, $dbname, $tables, $connection, $service[$options['s']], $model_dir);
        } else {
            echo "no table";
        }
    }
    var_export($createResult) . PHP_EOL;
    echo PHP_EOL;
} else {
    exit('end...');
}

/**
 * 生成bmodel准备
 */
//接收的uri
$bUri = !empty($options['b']) ? $options['b'] : $options['t'];
$bUri = trim($bUri, '/\\');
//获取model参数
$bUriTmp = explode('/', $bUri);
//控制器根目录
echo 'bmodel path: ' . SERVICE_BMODEL_PATH . PHP_EOL;

//规范后的url  目录分隔符的首字母大写
$bUriSpec   = get_uri_specification($bUri);
$bPrefix    = "\\App\\" . $service[$options['s']] . '\\Modules\\'; //命名空间前缀
$bSuffix    = '';
$bNameSpace = ''; //命名空间
//类名
$bClassName      = $bPrefix . $bUriSpec;
$bClassName4file = mb_substr(trim($bClassName, '\\\/'), mb_strrpos($bClassName, '\\'));
//文件
$bFilename = SERVICE_BMODEL_PATH . '/' . $bUriSpec . '.php';
$bFilename = str_replace('\\', '/', $bFilename);
echo "bmodel fileName: " . $bFilename . PHP_EOL;
echo "bmodel className: " . $bClassName . PHP_EOL;
$bNameSpace = mb_substr(trim($bClassName, '\\\/'), 0, mb_strrpos($bClassName, '\\') - 1);//当前类的命名空间
echo 'bmodel namespace: ' . $bNameSpace . PHP_EOL;
$bFilePath = dirname($bFilename);
if (!is_dir($bFilePath)) {
    mkdir($bFilePath, 0777, true);
}

//BModel代码
if (!file_exists($bFilename)) {
    $bmodelCode       = '';
    $bmodelTplContent = file_get_contents(SERVICE_CLIENT_BIN_PATH . '/moduleTpl.txt');
    $bmodelCode       = str_replace(
        array('{{$service}}', '{{$nameSpace}}', '{{$className}}', '{{$modelDirectory}}', '{{$modelName}}', '{{$model}}'),
        array($service[$options['s']], $bNameSpace, $bClassName4file, $model_dir, $model, $model . 'Model'),
        $bmodelTplContent);
    file_put_contents($bFilename, $bmodelCode);
    echo 'create bmodel success' . PHP_EOL;
} else {
    echo "target bmodel already exists!" . PHP_EOL;
}

/**
 * 生成controller准备
 */
//接收的uri
$cUri     = !empty($options['c']) ? $options['c'] : $options['t'];
$cUri     = trim($cUri, '/\\');
$cUriTmp  = explode('/', $cUri);
$lastCUri = array_pop($cUriTmp);
//控制器根目录
echo 'controller path: ' . SERVICE_RPC_CONTROLLER_PATH . PHP_EOL;

//规范后的uri  目录分隔符的首字母大写
$cUriSpec   = get_uri_specification($cUri);
$cPrefix    = "\\App\\" . $service[$options['s']] . '\\Controllers\\'; //命名空间前缀
$cSuffix    = '';
$cNameSpace = ''; //命名空间
//类名
$cClassName      = $cPrefix . $cUriSpec;
$cClassName4file = mb_substr(trim($cClassName, '\\\/'), mb_strrpos($cClassName, '\\'));
//文件
$cFilename = SERVICE_RPC_CONTROLLER_PATH . '/' . $cUriSpec . '.php';
$cFilename = str_replace('\\', '/', $cFilename);
echo "controller fileName: " . $cFilename . PHP_EOL;
echo "controller className: " . $cClassName . PHP_EOL;
$cNameSpace = mb_substr(trim($cClassName, '\\\/'), 0, mb_strrpos($cClassName, '\\') - 1);//当前类的命名空间
echo 'controller namespace: ' . $cNameSpace . PHP_EOL;
$cFilePath = dirname($cFilename);
if (!is_dir($cFilePath)) {
    mkdir($cFilePath, 0777, true);
}

//控制器代码
if (!file_exists($cFilename)) {
    $controllerCode     = '';
    $controllTplContent = file_get_contents(SERVICE_CLIENT_BIN_PATH . '/controllerTpl.txt');
    $primaryKey         = get_primary_fields($dbh, $dbname, $options['t']);
    $fields             = fields_types($dbh, $options['t'],
        array_merge($primaryKey, ['create_time', 'update_time', 'delete_time']));
    $controllerCode     = str_replace(
        array('{{$service}}', '{{$nameSpace}}', '{{$className}}', '{{$bClassName}}', '{{$bmodel}}', '{{$field}}', '{{$primaryKey}}'),
        array(
            $service[$options['s']],
            $cNameSpace,
            $cClassName4file,
            substr($bClassName, 1),
            $bClassName4file . 'BModel',
            $fields ? fields_validate_format($fields) : '*',
            $primaryKey ? sprintf("%s", implode('\',' . '\'', $primaryKey)) : '\'*\'',
        ),
        $controllTplContent);
    file_put_contents($cFilename, $controllerCode);
    echo 'create controller success' . PHP_EOL;
} else {
    echo "target controller already exists!" . PHP_EOL;
}

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
 * 根据数据库字段类型转化为验证类型
 * @param $type
 * @return mixed
 */
function field_format($type)
{
    $formats = array(
        'tinyint'    => 'number',
        'smallint'   => 'number',
        'mediumint'  => 'number',
        'int'        => 'number',
        'bigint'     => 'number',
        'float'      => 'number',
        'double'     => 'number',
        'decimal'    => 'number',
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
function create_models($dbh, $dbname, $tables = [], $connection, $service, $model_dir)
{
    $modelTplContent = file_get_contents(SERVICE_CLIENT_BIN_PATH . '/modelTpl.txt');
    foreach ($tables as $table) {
        //驼峰命名新文件名
        $modelFileName = str_replace(' ', '', ucwords(str_replace(array(
            '-',
            '_'
        ), ' ', strtolower(preg_replace('/^t_/i', '', trim($table))))));
        $file          = SERVICE_MODEL_PATH . $modelFileName . '.php';
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
                    '{{$service}}',
                    '{{$directory}}',
                    '{{$connection}}',
                    '{{$table}}',
                    '{{$filename}}',
                    '{{$primaryKey}}',
                    '{{$columns}}',
                    '{{$fillable}}'
                ), array(
                    $service,
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