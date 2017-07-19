<?php
//1 验证数据
validateData();
//2 生成文件
createByFiletype($_GET['fileType'],$_GET['system']);
/**
 * 根据文件类型选择要生成的文件
 * @param $fileType
 */
function createByFiletype($fileType, $system){
    switch($fileType) {
        case 'all':
            include(SERVICE_CLIENT_BIN_PATH.'/'.$system.'/Create.php');
            break;
        case 'Controller':
            include(SERVICE_CLIENT_BIN_PATH.'/createController.php');
            break;
        case 'Modules';
            include(SERVICE_CLIENT_BIN_PATH.'/createModules.php');
            break;
        case 'Model';
            include(SERVICE_CLIENT_BIN_PATH.'/createModules.php');
            break;

    }
}

/**
 * 数据验证和数据处理
 */
function validateData(){
    if(empty($_GET['system'])){
        die('请选择要创建文件的系统');
    } else {
        if(!file_exists(SERVICE_CLIENT_BIN_PATH.'/'.$_GET['system'])){
            die('要创建文件的系统'.$_GET['system'] .'不存在');
        }
    }

    if(empty($_GET['table'])){
        die('要创建的文件的表不能为空');
    }

    if(empty($_GET['filename'])){
        $_GET['filename'] = $_GET['table'];
    }
    if(empty($_GET['developer'])) {
        die('开发负责人不能为空');
    }
    if(empty($_GET['developeUM'])) {
        die('开发负责人的ＵＭ账号不能为空');
    }
}
