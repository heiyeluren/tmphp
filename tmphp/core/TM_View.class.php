<?php
/********************************
 *  描述: TMPHP 核心视图类
 *  作者: heiyeluren 
 *  创建: 2009/12/08 0:06
 *  修改: 2009/12/13 0:46  对原生的php/smarty/phplib模板进行支持
 *		  2009-12-24 4:12  增加针对DiscuzTemplate 的支持, 把模板类型定义整合到 TM_View 类
 *
 ********************************/


class TM_View 
{
	/**
	 * @var 视图类型为原生PHP
	 */
	const TYPE_PHP		= 'php';
	/**
	 * @var 视图类型为Smarty
	 */
	const TYPE_SMARTY	= 'smarty';
	/**
	 * @var 视图类型为Discuz模板
	 */
	const TYPE_DISCUZ	= 'discuz';
	/**
	 * @var 视图类型为PHPLib Template
	 */
	const TYPE_PHPLIB	= 'phplib';

	/**
	 * @var object 对象单例
	 */
	static $_instance = NULL;

	/**
	 * 保证对象不被clone
	 */
	private function __clone() {}

    /**
	 * 构造函数
	 */
	private function __construct() {}


	/**
	 * 视图工厂操作方法
	 *
	 * @param string $configFile 配置文件路径
	 * @return object
	 */
	public static function factory($controller, $viewType = '', $params = array()){
		$viewType = strtolower($viewType);
		if ($viewType == ''){
			$viewType = self::TYPE_PHP;
		}
		switch($viewType) {
			case self::TYPE_PHP:
				$obj = TM_View_Php::getInstance($controller, $params);
				break;
			case self::TYPE_SMARTY:
				$obj = TM_View_Smarty::getInstance($controller, $params);
				break;
			case self::TYPE_PHPLIB:
				$obj = TM_View_Phplib::getInstance($controller, $params);
				break;
			case self::TYPE_DISCUZ:
				$obj = TM_View_Discuz::getInstance($controller, $params);
				break;				
				
			default:
				throw new TM_Exception("View type $viewType not support");
		}
		return $obj;
	}



}




/**
 * 原生PHP文件模板视图类
 *
 * @desc 使用PHP原生程序作为模板
 */
class TM_View_Php
{

	/**
	 * @var object 对象单例
	 */
	static $_instance = NULL;
	/**
	 * @var object 控制器对象
	 */
	public $controller = NULL;
	/**
	 * @var bool 是否调试模式
	 */
	public $debug = false;	


	/**
	 * 保证对象不被clone
	 */
	private function __clone() {}

    /**
	 * 构造函数
	 */
	private function __construct($controller) {
		$this->controller = $controller;
	}


	/**
	 * 获取对象唯一实例
	 *
	 * @param void
	 * @return object 返回本对象实例
	 */
	public static function getInstance($controller, $params){
		if (!(self::$_instance instanceof self)){
			self::$_instance = new self($controller);
		}
		return self::$_instance;
	}
	
	/**
	 * 设置模板相应的调试模式
	 *
	 * @param bool $debug 是否调试模式，true or false
	 * @return void
	 */
	public function setDebug($debug = false){
		$this->debug = $debug;		
	}	

 
	/**
	 * 解析处理一个模板文件
	 *
	 * @param  string $filePath  模板文件路径
	 * @param  array  $vars 需要给模板变量赋值的变量
	 * @return void
	 */
	public function renderFile($filePath, $vars) {
		$filePath = APP_VIEW_DIR . $filePath;
		if(!is_file($filePath) || !is_readable($filePath)){
			throw new TM_Exception("View file ". $filePath ." not exist or not readable");
		}
		if (!empty($vars)){
			foreach($vars as $key => $value){ 
				$$key=$value; 
			}
		}
		require_once($filePath);
		if ($this->debug){
			var_dump($vars);
		}
	}

}




/**
 * Smarty文件模板视图类
 *
 * @desc 针对 Smarty Template 的模板View的模板加载
 *
 * 相关链接：
 *	Smarty官网：http://www.smarty.net/
 *	Smarty手册：http://www.phpchina.com/manual/smarty/
 *	Smarty入门：http://www.google.cn/search?q=%E8%8F%9C%E9%B8%9F%E5%AD%A6PHP%E4%B9%8BSmarty%E5%85%A5%E9%97%A8&btnG=Google+%E6%90%9C%E7%B4%A2
 */
class TM_View_Smarty
{

	/**
	 * @var object 对象单例
	 */
	static $_instance = NULL;
	/**
	 * @var object 控制器对象
	 */
	public $controller = NULL;
	/**
	 * @var array Smarty对象参数
	 */
	public $params = array();
	/**
	 * @var bool 是否调试模式
	 */
	public $debug = false;


	/**
	 * 保证对象不被clone
	 */
	private function __clone() {}

    /**
	 * 构造函数
	 *
	 * @param object $controller 控制器对象
	 *
	 * @param array $params 需要传递的选项参数
	 *
	 * 参数说明：
	 * $params = array(
		'template_dir'		=> 'view/',					//指定模板文件存放目录，缺省为 view 目录
		'cache_dir'			=> 'cache/smarty/cache',	//指定缓存文件存放目录
		'compile_dir'		=> 'cache/smarty',			//Smarty编译目录
		'config_dir'		=> '',						//Smarty配置文件目录, 缺省为空
		'left_delimiter'	=> '{{',					//模板变量的左边界定符, 缺省为 {{
		'right_delimiter'	=> '}}',					//模板变量的右边界定符，缺省为 }}
	   );

	 */
	private function __construct($controller, $params) {
		$this->controller = $controller;
		$this->params = $params;
	}


	/**
	 * 获取对象唯一实例
	 *
	 * @param void
	 * @return object 返回本对象实例
	 */
	public static function getInstance($controller, $params){
		if (!(self::$_instance instanceof self)){
			self::$_instance = new self($controller, $params);
		}
		return self::$_instance;
	}
	
	/**
	 * 设置模板相应的调试模式
	 *
	 * @param bool $debug 是否调试模式，true or false
	 * @return void
	 */
	public function setDebug($debug = false){
		$this->debug = $debug;		
	}

 
	/**
	 * 解析处理一个模板文件
	 *
	 * @param  string $filePath  模板文件路径
	 * @param  array  $vars 需要给模板变量赋值的变量
	 * @return void
	 */
	public function renderFile($filePath, $vars) {
		//加载Smarty
		load_plugin("Smarty/Smarty");
		$smarty = new Smarty;

		//判断是否传递配置参数
		if ( empty($this->params) || !isset($this->params['compile_dir']) || !isset($this->params['config_dir']) || !isset($this->params['cache_dir'])){
			throw new TM_Exception("Smarty template engine configure [compile_dir,config_dir,cache_dir] not set, please  TM_View->factory() entry params"); 
		}

		//设置Smarty参数
		$smarty->template_dir 	 = !isset($this->params['template_dir']) ? APP_VIEW_DIR : $this->params['template_dir'];
		$smarty->compile_dir  	 = $this->params['compile_dir']; 
		$smarty->config_dir   	 = $this->params['config_dir'];
		$smarty->cache_dir    	 = $this->params['cache_dir'];
		$smarty->left_delimiter  = !isset($this->params['left_delimiter']) ? "{{" : $this->params['left_delimiter'];
		$smarty->right_delimiter = !isset($this->params['right_delimiter']) ? "}}" : $this->params['right_delimiter'];
		$smarty->debugging		 = $this->debug;

		//检查模板文件
		if(!is_file($filePath) || !is_readable($filePath)){
			throw new TM_Exception("View file ". $filePath ." not exist or not readable");
		}
		//设置模板变量
		if (!empty($vars)){
			foreach($vars as $key => $value){
				$smarty->assign($key, $value);
			}
		}
		$smarty->display($filePath);
	}


}




/**
 * PHPlib Template 文件模板视图类 (暂不推荐使用本模板引擎)
 *
 * @desc 针对 PHPLib Template 的模板View的模板加载, 暂时不支持 block 使用，所以不推荐使用本模板。
 *
 * 相关链接：
 * PHPLIB 官网：http://sourceforge.net/projects/phplib/
 * PHPlib Tempalte 手册：http://www.sanisoft.com/phplib/manual/template.php
 * PHPlib Template 使用：http://www.google.cn/search?q=phplib+template
 */
class TM_View_Phplib
{

	/**
	 * @var object 对象单例
	 */
	static $_instance = NULL;
	/**
	 * @var object 控制器对象
	 */
	public $controller = NULL;
	/**
	 * @var array PHPLib Template对象参数
	 */
	public $params = array();
	/**
	 * @var bool 是否调试模式
	 */
	public $debug = false;	


	/**
	 * 保证对象不被clone
	 */
	private function __clone() {}

    /**
	 * 构造函数
	 *
	 * @param object $controller 控制器对象
	 *
	 * @param array $params 需要传递的选项参数
	 *
	 * 参数说明：
	 * $params = array(
	 *		'root'	=> 'view/'		//指定模板文件存放目录，缺省为 view 目录
	 * }
	 */
	private function __construct($controller, $params) {
		$this->controller = $controller;
		$this->params = $params;
	}


	/**
	 * 获取对象唯一实例
	 *
	 * @param void
	 * @return object 返回本对象实例
	 */
	public static function getInstance($controller, $params){
		if (!(self::$_instance instanceof self)){
			self::$_instance = new self($controller, $params);
		}
		return self::$_instance;
	}

	/**
	 * 设置模板相应的调试模式
	 *
	 * @param bool $debug 是否调试模式，true or false
	 * @return void
	 */
	public function setDebug($debug = false){
		$this->debug = $debug;		
	}

 
	/**
	 * 解析处理一个模板文件
	 *
	 * @param  string $filePath  模板文件路径
	 * @param  array  $vars 需要给模板变量赋值的变量
	 * @return void
	 */
	public function renderFile($filePath, $vars) {
		//初始化模板对象
		load_plugin("Phplib_Template");
		$phplib = new Phplib_Template();
		
		//设置PHPLib Template参数
		$template_root 	 = !isset($this->params['root']) ? APP_VIEW_DIR : $this->params['root'];
		$phplib->set_root($template_root);
		$phplib->debug = $this->debug;	

		//检查模板文件
		if(!is_file($filePath) || !is_readable($filePath)){
			throw new TM_Exception("View file ". $filePath ." not exist or not readable");
		}
		//设置模板变量
		$phplib->set_file('main', $filePath); 
		if (!empty($vars)){
			$phplib->set_var($vars);
		}
		$phplib->parse('mains', 'main'); 
		$phplib->p('mains', 'main'); 
	}


}



/**
 * Discuz模板视图类
 *
 * @desc 针对 Discuz Template 的模板View的模板加载
 *
 * @特殊说明：使用Discuz模板，必须打开短标记支持，在 php.ini 中 把 short_open_tag 设置为 On
 *
 * 相关链接：
 *  Discuz模板介绍：http://www.discuz.net/usersguide/advanced_styles.htm
 *	Discuz模板语法说明：http://www.google.cn/search?q=discuz+%E6%A8%A1%E6%9D%BF%E8%AF%AD%E6%B3%95%E8%AF%B4%E6%98%8E
 */
class TM_View_Discuz
{

	/**
	 * @var object 对象单例
	 */
	static $_instance = NULL;
	/**
	 * @var object 控制器对象
	 */
	public $controller = NULL;
	/**
	 * @var array 模板对象参数
	 */
	public $params = array();
	/**
	 * @var bool 是否调试模式
	 */
	public $debug = false;


	/**
	 * 保证对象不被clone
	 */
	private function __clone() {}

    /**
	 * 构造函数
	 *
	 * @param object $controller 控制器对象
	 *
	 * @param array $params 需要传递的选项参数
	 *
	 * 参数说明：
	 * $params = array(
		'template_dir'	 => 'templates/',		//指定模板文件存放目录
		'cache_dir'		 => 'templates/cache',	//指定缓存文件存放目录
		'auto_update'	 => true,				//当模板文件有改动时重新生成缓存 [关闭该项会快一些]
		'cache_lifetime' => 1,					//缓存生命周期(分钟)，为 0 表示永久 [设置为 0 会快一些]
	   );

	 */
	private function __construct($controller, $params) {
		$this->controller = $controller;
		$this->params = $params;
	}


	/**
	 * 获取对象唯一实例
	 *
	 * @param void
	 * @return object 返回本对象实例
	 */
	public static function getInstance($controller, $params){
		if (!(self::$_instance instanceof self)){
			self::$_instance = new self($controller, $params);
		}
		return self::$_instance;
	}
	
	/**
	 * 设置模板相应的调试模式
	 *
	 * @param bool $debug 是否调试模式，true or false
	 * @return void
	 */
	public function setDebug($debug = false){
		$this->debug = $debug;		
	}

 
	/**
	 * 解析处理一个模板文件
	 *
	 * @param  string $filePath  模板文件路径
	 * @param  array  $vars 需要给模板变量赋值的变量
	 * @return void
	 */
	public function renderFile($filePath, $vars) {
		try {
			//加载DiscuzTemplate
			load_plugin("DiscuzTemplate");
			$template = DiscuzTemplate::getInstance(); //使用单件模式实例化模板类

			//判断是否传递配置参数
			if ( empty($this->params) || !isset($this->params['cache_dir']) ){
				throw new TM_Exception("Discuz template engine configure [cache_dir] not set, please  TM_View->factory() entry params"); 
			}

			//设置模板参数
			$this->params['cache_dir'] 			= $this->params['cache_dir'];
			$this->params['template_dir']		= isset($this->params['template_dir']) ? $this->params['template_dir']: APP_VIEW_DIR;
			$this->params['auto_update'] 		= isset($this->params['auto_update']) ? $this->params['auto_update'] : true;
			$this->params['cache_lifetime'] 	= isset($this->params['cache_lifetime']) ? $this->params['cache_lifetime'] : 1;

			$template->setOptions($this->params); //设置模板参数

			//检查模板文件
			if(!is_file($filePath) || !is_readable($filePath)){
				throw new TM_Exception("View file ". $filePath ." not exist or not readable");
			}
			//设置模板变量
			if (!empty($vars)){
				foreach($vars as $key => $value){ 
					$$key=$value; 
				}
			}
			//输出到模板文件
			include($template->getfile($filePath));

		} catch (Exception $e){
			throw new TM_Exception($e->getMessage());
		} catch (TM_Exception $e){
			throw $e;
		}
	}


}


