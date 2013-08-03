<?php
/********************************
 *  描述: Crontab入口示例文件
 *  作者: heiyeluren 
 *  创建: 2009-12-22 11:22
 *  修改：2009-12-22 11:45
 ********************************/


//定义基本路径常量
define("APP_DIR", str_replace("\\", "/", realpath(dirname(__FILE__)."/..")));		//应用路径
define("TM_ROOT_DIR", str_replace("\\", "/", realpath(APP_DIR . "../../tmphp/")));	//框架路径
$confFile =  APP_DIR .'/config/Config.ini';											//配置文件

//设定包含文件路径
set_include_path(get_include_path() . PATH_SEPARATOR .TMPHP_DIR);

//包含基本入口文件和配置文件
require_once APP_DIR .'/config/AppConst.class.php';
require_once TM_ROOT_DIR .'/core/TM_Exception.class.php';
require_once TM_ROOT_DIR .'/tmphp.php';


//读取配置
$c = TM_Config::factory($confFile);
$config = $c->getData();


//初始化数据库
$driver = $config['DataBase']['driver']=='' ? "DB_Mysql" : $config['DataBase']['driver'];
$class  = TM_PREFIX ."_". $driver;
$dbConfig = array(
	"host"		=> $config['DataBase']['host'],
	"user"		=> $config['DataBase']['user'],
	"pwd"		=> $config['DataBase']['pwd'],
	"db"		=> $config['DataBase']['db'],
);
$db = new $class($dbConfig);



