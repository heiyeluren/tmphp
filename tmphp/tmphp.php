<?php
/*****************************************************
 *  描述: TMPHP 核心入口
 *  作者: heiyeluren 
 *  创建: 2009/12/1 22:40
 *  修改：2009/12/13 04:18  缺省基本定义和操作
 *		  2009-12-20 23:33  修改bug和初始化类操作方式
 *		  2009-12-24 12:54  移除视图类型定义
 *		  2009-12-28 00:41	增加URL Rewrite支持
 *****************************************************/

//判断版本
if (PHP_VERSION < '5.0.0'){
	die("TMPHP Error: Please use PHP Version >= 5.0");
}

//判断是否定义APP_DIR
if (!defined("APP_DIR")){
	die("TMPHP Error: Please define APP_DIR for app_name/view/index.php");
}

/**
 * 框架基本路径配置
 */
define("TM_PREFIX", "TM");	//框架基本类库通用前缀
if (!defined("TM_ROOT_DIR")){
define("TM_ROOT_DIR", str_replace("\\", "/", realpath(dirname(__FILE__))));			//框架根路径
}
define("TM_CORE_DIR", TM_ROOT_DIR . "/core/");										//核心类目录
define("TM_LIB_DIR", TM_ROOT_DIR . "/lib/");										//基础类目录
define("TM_HELPER_DIR", TM_ROOT_DIR . "/helper/");									//小助手目录
define("TM_PLUGIN_DIR", TM_ROOT_DIR . "/plugin/");									//插件目录

//应用路径
define("APP_CONTROLLER_DIR", APP_DIR ."/controller/");
define("APP_MODEL_DIR", APP_DIR ."/model/");
define("APP_VIEW_DIR", APP_DIR ."/view/");


//包含公共函数文件
include(TM_CORE_DIR ."global.func.php");



class TM_PHP
{
	/**
	 * @var object 对象单例
	 */
	static $_instance = NULL;


	/**
	 * 构造函数
	 *
	 * @param string $configFile 配置文件路径
	 */
	private function __construct(){}

	/**
	 * 保证对象不被clone
	 */
	private function __clone() {}


	/**
	 * 获取对象唯一实例
	 *
	 * @param string $configFile 配置文件路径
	 * @return object 返回本对象实例
	 */
	public static function getInstance(){
		if (!(self::$_instance instanceof self)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}


	/**
	 * 获取处理请求的URL路径 (SERVER_URI) 方式
	 *
	 * 
	 * @param array 配置数组
	 * @return array 返回parse好的数组
	 */
	public static function parseUri($arrConfig){
		//判断是否支持rewrite
		$isRewrite = false;
		if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())){
			$isRewrite = true;
		}

		//解析URI
		$route = array();
		$controllerName		= $arrConfig['Framework']['ControllerName'];
		$actionName			= $arrConfig['Framework']['ActionName'];
		$defaultController	= $arrConfig['Framework']['DefaultController'];
		$defaultAction		= $arrConfig['Framework']['DefaultAction'];
		$urlRewriteRule		= $arrConfig['Common']['UrlReWrite'];

		$ServerUri	= explode("?", $_SERVER['REQUEST_URI']);
		$urlRewriteRule!= '' ? preg_match($urlRewriteRule, $ServerUri[0], $module): 
							   $module = explode("/", $ServerUri[0]);

		//支持URL打开和没打开的情况
		if (!$isRewrite || $ServerUri[0]=='/'){
			$route['ControllerName'] = $_GET[$controllerName]=='' ? $defaultController : $_GET[$controllerName];
			$route['ActionName']	 = $_GET[$actionName]=='' ? $defaultAction : $_GET[$actionName];	
		} else {
			$route['ControllerName'] = isset($module[1])&&$module[1]!='' ? $module[1] : $defaultController;
			$route['ActionName']	 = isset($module[2])&&$module[2]!='' ? $module[2] : $defaultAction;
		}
		$route['param'] = isset($ServerUri[1]) ? $ServerUri[1] : '';

		return $route;
	}

	

	/**
	 * 路由分发
	 *
	 */
	public function dispatch($configFile){
		//不进行魔术过滤
		set_magic_quotes_runtime(0);

		try {
			//设置配置文件
			$config = TM_Config::factory($configFile);
			$arrConfig = $config->getData();
			if (!empty($arrConfig)){
				__save_config($arrConfig);
			}

			//读取控制器和action
			$route = self::parseUri($arrConfig);

			$controller = $route['ControllerName'] ."Controller";
			$action		= $route['ActionName'] ."Action";

			//包含控制器文件
			$controllerFile = APP_CONTROLLER_DIR . $controller .".class.php";
			if (!is_file($controllerFile) || !is_readable($controllerFile)){
				throw new TM_Exception("controller file $controllerFile not exist or not readable");
			}
			require($controllerFile);
			if (!class_exists($controller, false)){
				throw new TM_Exception("controller class $controller  not exist");
			}

			//判断 Action
			$con = new $controller($arrConfig, $controller, $action);
			if (!method_exists($con, $action)){
				throw new TM_Exception("controller class method $controller->$action() not exist");
			}

			//设置时区
			if(PHP_VERSION > '5.1.0') {
				@date_default_timezone_set($arrConfig['Common']['TimeZone']);
			}

			//进行Action操作
			return $con->$action();

		} catch (TM_Exception $exception){
			throw $exception;
		}


	}



	
}

