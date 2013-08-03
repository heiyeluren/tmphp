<?php
/*****************************************************
 *  描述: 框架常用函数
 *  作者: heiyeluren 
 *  创建: 2009/12/06 15:00
 *  修改：2009/12/13 04:18 构建基础使用的函数
 *		  2009-12-20 20:35 修改 __autoload() 的bug
 *
 *****************************************************/


/**
 * 类自动包含功能
 *
 * @param string 需要包含的类名
 * @return void
 */
function __autoload($class){
	$core		= TM_CORE_DIR .$class .".class.php";
	$lib		= TM_LIB_DIR . $class .".class.php";
	$helper		= TM_HELPER_DIR . $class .".class.php";
	$plugin		= TM_PLUGIN_DIR . $class .".class.php";
	$controller = APP_CONTROLLER_DIR . $class .".class.php";
	$model		= APP_MODEL_DIR . $class .".class.php";
	$inc		= '';

	//从框架中载入
	if (is_file($core)){
		$inc = $core;
	}
	//从基础类中载入
	elseif (is_file($lib)){
		$inc = $lib;
	}
	//从插件载入
	elseif (is_file($plugin)){
		$inc = $plugin;
	}
	//从小助手载入
	elseif (is_file($helper)){
		$inc = $helper;
	}
	//从控制器载入
	elseif (is_file($controller)){
		$inc = $controller;
	}
	//从model载入
	elseif (is_file($model)){
		$inc = $model;
	}
	//echo ($class);
	//echo ($inc);

	//如果没有找到文件
	if ($inc == '')	return false;

	//包含文件
	require_once($inc);
}

/**
 * 保存配置文件
 *
 * @param str $arrConfig 配置文件数组
 * @return array
 */
function __save_config($arrConfig){
	$_SERVER['TMPHP_CONFIG'] = $arrConfig;
	return $arrConfig;
}


/**
 * 获取配置数据
 *
 * @param void
 * @return array
 */
function __get_config(){
	if (!isset($_SERVER['TMPHP_CONFIG'])){
		return false;
	}
	return $_SERVER['TMPHP_CONFIG'];
}


/**
 * 加载插件(Plugin)
 *
 * @param string $plugin 插件名称或路径
 * @return void
 */
function load_plugin($plugin){
	$class	= TM_PLUGIN_DIR . $plugin .".class.php";
	if (!is_file($class) || !is_readable($class)){
		return false;
	}
	require_once($class);

	return true;
}


/**
 * 加载小助手(Helper)
 *
 * @param string $helper 小助手名称或路径
 * @return void
 */
function load_helper($helper){
	$class	= TM_HELPER_DIR . $helper .".class.php";
	if (!is_file($class) || !is_readable($class)){
		return false;
	}
	require_once($class);

	return true;
}


 