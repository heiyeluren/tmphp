<?php
/********************************
 *  描述: 应用框架入口示例文件
 *  作者: heiyeluren 
 *  创建: 2009-12-20 22:25
 *  修改：2009-12-22 12:28	基本入口设置
 *		  2009-12-28 08:59	修改包含bug
 ********************************/


//定义基本路径常量
define("APP_DIR", str_replace("\\", "/", realpath(dirname(__FILE__)."/..")));		//应用路径
define("TM_ROOT_DIR", str_replace("\\", "/", realpath(APP_DIR . "../../tmphp/")));	//框架路径
$confFile =  APP_DIR .'/config/Config.php';											//配置文件

//设定包含文件路径
set_include_path(get_include_path() . PATH_SEPARATOR .TM_ROOT_DIR);

//包含基本入口文件和配置文件
require_once APP_DIR .'/config/AppConst.class.php';
require_once TM_ROOT_DIR .'/tmphp.php';


//分发处理
try {
	TM_PHP::getInstance()->dispatch($confFile); 
} catch (TM_Exception $te){
	echo $te->getMessage(); exit;
} catch (Exception $e){
	echo $e->getMessage(); exit;
}


