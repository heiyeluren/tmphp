<?php
/********************************
 *  描述: TMPHP 核心配置操作类
 *  作者: heiyeluren 
 *  创建: 2009/12/6 15:19
 *  修改: 2009/12/9 23:47
 ********************************/



/**
 * 配置文件工厂操作类
 *
 */
class TM_Config
{
	/**
	 * 保证对象不被clone
	 */
	private function __clone() {}

    /**
	 * 构造函数
	 */
	private function __construct() {}


	/**
	 * 配置文件工厂操作方法
	 *
	 * @param string $configFile 配置文件路径
	 * @return object
	 */
	public static function factory($configFile){
		if ($configFile == ''){
			throw new TM_Exception("config file param empty");
		}
		if (!is_file($configFile) || !is_readable($configFile)){
			throw new TM_Exception("config file $configFile not exist or don't readable");
		}
		$extension = strtolower(strrchr($configFile, '.'));
		switch($extension) {
			case '.ini':
				$obj = TM_Config_Ini::getInstance($configFile);
				break;
			case '.php':
				$obj = TM_Config_Php::getInstance($configFile);
				break;
			default:
				throw new TM_Exception("config file $configFile format not support");
		}
		return $obj;
	}

}




/**
 * 配置文件INI文件操作类
 *
 */
class  TM_Config_Ini
{
	/**
	 * @var object 对象单例
	 */
	static $_instance = NULL;
	/**
	 * @var string 配置文件路径
	 */
	private $file = '';
	/**
	 * @var array 配置文件数据
	 */
	private $data;



	/**
	 * 构造函数
	 *
	 * @param string $configFile 配置文件路径
	 */
	private function __construct($configFile){
		$this->file = $configFile;
		$this->_parse();
	}

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
	public static function getInstance($configFile){
		if (!(self::$_instance instanceof self)){
			self::$_instance = new self($configFile);
		}
		return self::$_instance;
	}



	/**
	 * 文件解析函数
	 *
	 * @param void
	 * @return void
	 */
	private function _parse(){
		$this->data = parse_ini_file($this->file, true);
	}

	/**
	 * 读取配置数据
	 *
	 * @param void
	 * @return array 返回的数据
	 */
	public function getData($key = ''){
		if ($key == ''){
			return $this->data;
		}
		return $this->data[$key];
	}


}



/**
 * 配置文件PHP数组操作类
 *
 */
class  TM_Config_Php
{
	/**
	 * @var object 对象单例
	 */
	static $_instance = NULL;
	/**
	 * @var string 配置文件路径
	 */
	private $file = '';
	/**
	 * @var array 配置文件数据
	 */
	private $data;



	/**
	 * 构造函数
	 *
	 * @param string $configFile 配置文件路径
	 */
	private function __construct($configFile){
		$this->file = $configFile;
		$this->_parse();
	}

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
	public static function getInstance($configFile){
		if (!(self::$_instance instanceof self)){
			self::$_instance = new self($configFile);
		}
		return self::$_instance;
	}



	/**
	 * 文件解析函数
	 *
	 * @param void
	 * @return void
	 */
	private function _parse(){
		$this->data = include($this->file);
	}

	/**
	 * 读取配置数据
	 *
	 * @param void
	 * @return array 返回的数据
	 */
	public function getData($key = ''){
		if ($key == ''){
			return $this->data;
		}
		return $this->data[$key];
	}


}



