<?php
/********************************
 *  描述: TMPHP 核心模型类
 *  作者: heiyeluren 
 *  创建: 2009/12/13 04:15
 *  修改: 2009-12-22 10:10	初始化Model
 *
 ********************************/


class TM_Model
{
	/**
	 * @var object 对象单例
	 */
	static $_instance = NULL;

	/**
	 * @var 控制器对象
	 */
	protected $controller = NULL;
	
	/**
	 * @var 配置文件数组
	 */	
	protected $config = array();
	 
	/**
	 * @var 数据库对象
	 */
	protected $db = NULL;


	/**
	 * Model构造函数
	 *
	 * @param array $config 配置数据数组
	 * @param string $controllerName 控制器名
	 * @param string $actionName Aciton名
	 */
	public function __construct($controller){
		if (!is_object($controller)){
			throw new TM_Exception("TM_Model: controller is empty");
		}
		$this->controller = $controller;
		$this->config = $this->controller->config;
	}

	/**
	 * 保证对象不被clone
	 */
	private function __clone() {}


	/**
	 * 获取对象唯一实例
	 *
	 * @param void
	 * @return object 返回本对象实例
	 */
	public static function getInstance($controller){
		if (!(self::$_instance instanceof self)){
			self::$_instance = new self($controller);
		}
		return self::$_instance;
	}


	/**
	 * 获取数据库访问对象
	 *
	 * @param void
	 * @return object
	 */
	public function getDb(){
		try {
			if (is_object($this->db)){
				return $this->db;
			}
			//定位数据库访问类
			$driver = $this->config['DataBase']['driver']=='' ? "DB_Mysql" : $this->config['DataBase']['driver'];
			$class  = TM_PREFIX ."_". $driver;
			
			//初始化数据库访问
			$dbConfig = array(
				"host"		=> $this->config['DataBase']['host'],
				"user"		=> $this->config['DataBase']['user'],
				"pwd"		=> $this->config['DataBase']['pwd'],
				"db"		=> $this->config['DataBase']['db'],
			);
			$this->db = new $class($dbConfig);

			return $this->db;

		} catch(TM_Exception $e) {
			throw $e;
		}
		
	}





}

