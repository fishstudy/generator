<?php
/**
 * Created by PhpStorm.
 * User: DONGYONGSHENG413
 * Date: 2016/9/26
 * Time: 16:00
 */
/**
 * 单独生成controller 使用范例:php createController.php -s user -c contract/contract_order
 */
define('SERVICE_CLIENT_BIN_PATH', __DIR__);
require dirname(SERVICE_CLIENT_BIN_PATH) . '/vendor/autoload.php';

//命令行参数
$options = getopt("c:s:");

if (empty($options['c']) || empty($options['s'])) {
    exit("demo: php createController.php -s user -c contract/contract_order" . PHP_EOL .
        '-s:service(necessary,' . implode('|', array_keys($service)) . '; -b:bmodel(necessary); -t:table');
}
//判断s参数是否合法
if (!in_array($options['s'], array_keys($service))) {
    exit("param s error,please choose one(" . implode('|', array_keys($service)) . ')');
}

/**
 * 生成controller准备
 */
//接收的uri
$system = trim($options['s'], '/\\');
define('SERVICE_RPC_CONTROLLER_PATH', dirname(dirname(SERVICE_CLIENT_BIN_PATH)) . '/user-service/app/'. $service[$options['s']] .'/Controllers/');

$cUri     = trim($options['c'], '/\\');
$cUriTmp  = explode('/', $cUri);
$lastCUri = array_pop($cUriTmp);
//控制器根目录
echo 'controller path: ' . SERVICE_RPC_CONTROLLER_PATH . PHP_EOL;

//规范后的uri  目录分隔符的首字母大写
$cUriSpec   = get_uri_specification($cUri);
$cPrefix    = "\\App\\" . $service[$options['s']] . "\\Controllers\\"; //命名空间前缀
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
    $controllTplContent = file_get_contents(SERVICE_CLIENT_BIN_PATH . '/simplecontrollerTpl.txt');
    $controllerCode     = str_replace(
        array('{{$service}}', '{{$nameSpace}}', '{{$className}}'),
        array($service[$options['s']], $cNameSpace, $cClassName4file,),
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