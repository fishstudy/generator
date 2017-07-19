<?php
/**
 * Created by PhpStorm.
 * User: DONGYONGSHENG413
 * Date: 2016-04-08
 * Time: 10:03
 */

/**
 * 单独生成bmodel 使用范例:php createBModel.php -s user -b user/user_ext -t user_ext
 */
define('SERVICE_CLIENT_BIN_PATH', __DIR__);
require dirname(SERVICE_CLIENT_BIN_PATH) . '/vendor/autoload.php';

//引入数据库配置文件
require SERVICE_CLIENT_BIN_PATH . '/conf.php';

//命令行参数
$options = getopt("b:s:t:");
if (empty($options['s']) || empty($options['b'])) {
    exit("php createBModel.php -s user -b user/user_ext -t user_ext" . PHP_EOL .
        '-s:service(necessary,' . implode('|', array_keys($service)) . '; -b:bmodel(necessary); -t:table');
}
//判断s参数是否合法
if (!in_array($options['s'], array_keys($service))) {
    exit("param s error,please choose one(" . implode('|', array_keys($service)) . ')');
}

//定义BModel路径名
define('SERVICE_BMODEL_PATH', dirname(dirname(SERVICE_CLIENT_BIN_PATH)) . '/user-service/app/'. $service[$options['s']] .'/Modules/');

/**
 * 生成bmodel准备
 */
//接收的uri
$bUri = trim($options['b'], '/\\');
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
    if (!empty($options['t'])) {
        $options['t']     = preg_replace('/^t_/i', '', $options['t']);
        $model            = get_uri_specification($options['t']);
        $mSuffix          = 'Model';
        $bmodelCode       = '';
        $bmodelTplContent = file_get_contents(SERVICE_CLIENT_BIN_PATH . '/moduleTpl.txt');
        $bmodelCode       = str_replace(
            array('{{$service}}', '{{$nameSpace}}', '{{$className}}', '{{$modelName}}', '{{$model}}'),
            array($service[$options['s']], $bNameSpace, $bClassName4file, $model, $model . $mSuffix),
            $bmodelTplContent);
        file_put_contents($bFilename, $bmodelCode);
        echo 'create bmodel success' . PHP_EOL;
    } else {
        $bmodelTplContent = file_get_contents(SERVICE_CLIENT_BIN_PATH . '/simplemoduleTpl.txt');
        $bmodelCode       = str_replace(
            array('{{$nameSpace}}', '{{$className}}'),
            array($bNameSpace, $bClassName4file),
            $bmodelTplContent);
        file_put_contents($bFilename, $bmodelCode);
        echo 'create bmodel success' . PHP_EOL;
    }
} else {
    echo "target bmodel already exists!" . PHP_EOL;
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
