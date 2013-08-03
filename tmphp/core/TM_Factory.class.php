<?php
/****************************************************
 *  描述：基础对象提取（模仿对象工厂模式）
 *  作者：heiyeluren
 *  创建：2007-04-12 16:07	实现基本需要工厂方法
 *  修改：2008-09-07 19:28	增加部分对象工厂方法
 *		  2009-12-16 11:09	修改对象名和异常处理方式
 *		  2009-12-20 20:36  删减修改方法，保留最常用方法
 *
 ****************************************************/

 

class TM_Factory
{
	/**
	 * 获取数据库访问对象
	 *
	 * @return mixed 成功返回对象，失败返回错误提示信息字符串
	 */
	function &get_db($arrConfig){
		$db =& new DB(_DB_HOST, _DB_USER, _DB_PASSWD, _DB_NAME, _DB_IS_PCONNECT);
		if ($db->isError($res = $db->connect())){
			return $res->getMessage();
		}
		return $db;
	}

	/**
	 * 获取常用 SMTP Socket 访问方式对象
	 *
	 * @param string $mailFrom 发件人地址
	 * @param string $mailTo 收件人地址，多个地址之间使用逗号分割，或者构造成一个一维数组
	 * @param string $mailSubject 邮件主题
	 * @param string $mailBody 邮件内容
	 * @param int $mailType 需要指定的MIME头，1为html(text/html)，2为txt(text/plain)，缺省是html
	 * @return mixed 成功返回对象，失败返回错误提示信息字符串
	 */
	function &get_smtp($mailFrom, $mailTo, $mailSubject, $mailBody, $mailType = 1){
		include_once("SMTP.class.php");
		$smtp =& new SMTP( _SMTP_HOST, _SMTP_PORT, _SMTP_USER, _SMTP_PASSWD, $mailFrom, $mailTo, $mailSubject, $mailBody, $mailType);
		if ($smtp->isError($res = $smtp->connect())){
			return $res->getMessage();
		}
		return $smtp;
	}


	/**
	 * 获取一个处理Master/Slave多数据库类对象
	 *
	 * $masterConf = array(
	 *		"host"	=> Master数据库主机地址
	 *		"user"	=> 登录用户名
	 *		"pwd"	=> 登录密码
	 *		"db"	=> 默认连接的数据库
	 *	);
	 * $slaveConf = array(
	 *		"host"	=> Slave1数据库主机地址|Slave2数据库主机地址|...
	 *		"user"	=> 登录用户名
	 *		"pwd"	=> 登录密码
	 *		"db"	=> 默认连接的数据库
	 *	)
	 *
	 * @return object
	 */
	function &get_db_multi(){
		//设定Master和Slave的主机地址数组
		//$masterConf = array();
		//$slaveConf = array();
		include_once("DBMulti.class.php");
		$db =& new DBMulti($masterConf, $slaveConf);
		return $db;
	}

	/**
	 * 获取一个缓存处理类对象
	 *
	 * @param int $cacheType 是调用哪个缓存，APC 还是 Memcache，定义请查看 Cache.class.php
	 * @param array $param 需要传递的参数，如果选择的是Memcache的话，那么需要传递一个Memcache的主机数组过去，例：
	 *			array(
	 *				array('192.168.0.1', 11211), 
	 *				array('192.168.0.2', 11211), 
	 *				array('192.168.0.3', 11211),
	 *			)
	 *
	 * @return object
	 */
	function &get_cache($cacheType = NULL, $param = array()){
		include_once("Cache.class.php");
		//$param = array();	//此处设定memcache主机或文件路径
		if ($cacheType){
			$cache =& new Cache($cacheType, $param);
		} else {
			$cache =& new Cache;
		}
		return $cache;
	}

	/**
	 * 获取一个Session操作对象
	 *
	 * @param int $type 需要存储到文件还是Memcache中，文件为 1 ，Memcache为2
	 * @param mixed $param 需要传递的参数，如果是文件类型，则可以传递保存Session的文件路径
	 * @return object
	 */
	function &get_session($type, $param = array()){
		include_once("Session.class.php");
		if ($type == SESS_TYPE_FILE){
			$session =& new Session(false, $param);
		} else {
			$session =& new Session;
		}
		return $session;
	}



}



