<?php
/**
 * Created by PhpStorm.
 * User: DONGYONGSHENG413
 * Date: 2016-03-15
 * Time: 15:55
 */

//数据库配置
$host = "dev-mysql-m.a.pa.com";       //数据库host
$user = "dev";                        //数据库用户名
$password = "dev";                        //数据库用户密码
$dbname = "user_db";                 //数据库名
$connection = 'user_db';                 //连接名

$db = array(       //所有数据库model生成的目录
    'user_db' => 'UserDB',//用户中心DB
    'user_1_db' => 'UserDB1',//用户中心DB
    'integral_db' => 'Integral',//积分DB
    'zt_db' => 'Ztdb',//好房拓DB
    'dishfuluser_0_db' => '', //满盘用户 0号DB
    'dishfuljifen_0_db' => '', //满盘积分 0号DB
    'datahouse_db' => 'DataHouseDB', //经纪人用户
);
$service = array(       //user-service所有模块，对应各个目录
    'user' => 'User',   //用户模块
    'pay' => 'Pay',    //支付模块
    'integral' => 'Integral', //积分模块
    'dishfuluser' => 'DishfulUser', //满盘用户模块
    'dishfuljifen' => 'DishfulJifen', //满盘积分模块
);

$default_service = 'user'; //默认service