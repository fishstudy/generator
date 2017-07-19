<?php
/**
 * Created by PhpStorm.
 * User: DONGYONGSHENG413
 * Date: 2016/9/26
 * Time: 16:00
 * 示例，生成数据库user_db的user表的model：php createModel.php(生成数据库所有model)，php createModel.php -t user_ext -s user -d user_db
 * -t:必传，代表数据表名，可带前缀(如：t_user_ext)，也可不带前缀(如：user_ext)
 * -s:非必传，代表服务名，即conf.php中service变量的key值中的一个，例如user、pay，默认user
 * -d:非必传，代表数据库名，默认user_db
 * 执行结果：
 *  生成model文件：app/User/Models/User/UserExt.php，如果文件存在则不重复生成
 *  注：生成的文件名均采用驼峰命名法 */
/**
 * 使用范例:
 * 生成数据库所有model：php createModel.php -s user -d user_db
 * 生成数据库user_db的user表的model：php createModel.php -t user -s user -d user_db
 */
define('SERVICE_CLIENT_BIN_PATH', __DIR__);
require dirname(SERVICE_CLIENT_BIN_PATH) . '/vendor/autoload.php';
//引入配置文件
require SERVICE_CLIENT_BIN_PATH . '/conf.php';

$options = getopt("t:d:s:");
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
//判断s参数是否合法
if (empty($options['s'])) {
    $options['s'] = $default_service;
} else {
    if (!in_array($options['s'], array_keys($service))) {
        exit("param s error,please choose one(" . implode('|', array_keys($service)) . ')');
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
define('SERVICE_MODEL_PATH', dirname(dirname(SERVICE_CLIENT_BIN_PATH)) . '/user-service/app/' . $service[$options['s']] . '/Models/' . $tmp_model_dir);
if (!is_dir(SERVICE_MODEL_PATH)) {
    mkdir(SERVICE_MODEL_PATH, 0777, true);
}
$createResult = array('success' => '', 'fail' => '', 'nocreate' => '');

//创建数据库连接
try {
    $dbh = new PDO($dsn, $user, $password,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8", PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

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
    var_export($createResult);

} else {
    exit('end...');
}

/**
 * 获取数据库所有表
 * @param $dbh 数据库连接资源
 * @return array
 */
function list_tables($dbh)
{
    try {
        $stmt = $dbh->prepare('set names utf8');
        $stmt->execute();
        $sql  = sprintf('show tables');
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $tables = array();
        foreach ($results as $k => $table) {
            //容错处理，去除表前缀不是t_以及表名中有-的
            if (strpos($table, 't_') !== 0 || strstr($table, '-')) {
                continue;
            }
            $tables[] = $table;
        }
        return $tables;
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit;
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
 * 批量创建model
 * @param       $dbh
 * @param       $dbname
 * @param array $tables
 * @param       $replaceCfg
 * @return mixed
 */
function create_models($dbh, $dbname, $tables = [], $connection, $service, $model_dir)
{
    $modelTplString = file_get_contents(SERVICE_CLIENT_BIN_PATH . '/modelTpl.txt');
    foreach ($tables as $table) {
        //驼峰命名新文件名
        $modelFileName = str_replace(' ', '',
            ucwords(str_replace(array('-', '_'), ' ', strtolower(preg_replace('/^t_/i', '', trim($table))))));
        //控制器代码
        $file = SERVICE_MODEL_PATH . $modelFileName . '.php';
        if (!file_exists($file)) {
            $table = 't_' . preg_replace('/^t_/i', '', trim($table));
            //获取表主键
            $primaryKey = get_primary_fields($dbh, $dbname, $table);
            //获取表字段
            $fields   = fields($dbh, $table);
            $fillable = $fields;
            $bool     = file_put_contents($file,
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
                ), $modelTplString));
            if ($bool) {
                //echo "create file ok: ". $file. "\r\n";
                $createResult['success'][] = $file;
            } else {
                //echo "create file fail: ". $file. "\r\n";
                $createResult['fail'][] = $file;
            }
        } else {
            $createResult['fail'][] = $file;
        }
    }
    return $createResult;
}




