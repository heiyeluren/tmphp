<?php
/********************************
 *  描述: 缺省Model
 *  作者: heiyeluren 
 *  创建: 2009-12-22 10:54
 *  修改：2009-12-22 11:31
 ********************************/



class IndexModel
{
	/**
	 * @var 控制器对象
	 */
	public $controller = NULL;
	/**
	 * @var 模型层对象
	 */
	public $model = NULL;
	/**
	 * @var 数据库对象
	 */
	public $db  = NULL;
	
	/**
	 * Model构造函数
	 *
	 * @param string $controllerName 控制器名
	 * @return void
	 */
	public function __construct($controller){
		if (!is_object($controller)){
			throw new TM_Exception("TM_Model: controller is empty");
		}
		$this->controller = $controller;
		$this->model = TM_Model::getInstance($controller);
		$this->db = $this->model->getDb();
		$this->db->query("set names ". $this->config['DataBase']['charset']);
	}


	/**
	 * 数据库操作: 生成一个表结构
	 *
	 * @param void
	 * @return bool
	 */
	function createTable(){
		//建立一个数据表
		return $this->db->query("CREATE TABLE user2 (
					`id` INT( 10 ) NOT NULL AUTO_INCREMENT ,
					`name` VARCHAR( 32 ) NOT NULL ,
					`email` VARCHAR( 32 ) NOT NULL ,
					PRIMARY KEY ( `id` ) 
					) ENGINE = MYISAM ;
			");
	}


	/**
	 * 数据库操作: 新增一个用户
	 *
	 * @param void
	 * @return int
	 */
	function addUser($name, $email){
		//插入一条记录
		$arrInsert = array("name"=>$name, "email"=>$email);
		return $this->db->insert($arrInsert, "user2");
	}


	/**
	 * 数据库操作: 更新用户
	 *
	 * @param void
	 * @return int
	 */
	function modifyUser($oldName, $newName){
		//更新记录
		$arrUpdate = array("name"=>$newName, "email"=>$newName.'@example.com');
		$this->db->update($arrUpdate, "name='{$oldName}'", 'user2');
	}


	/**
	 * 数据库操作: 统计所有用户
	 *
	 * @param void
	 * @return int
	 */
	function countUser(){
		//统计记录数
		return $this->db->count(array(), array(), 'user2');
	}


	/**
	 * 数据库操作: 读取所有用户列表
	 *
	 * @param void
	 * @return array
	 */
	function getUserList(){
		//读取所有记录
		return $this->db->getAll("select * from user2");
	}
	
}


