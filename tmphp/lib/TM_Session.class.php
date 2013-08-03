<?php
//==============================================================
// Session 操作类
//
// 功能：能够操作系统自带、Memcache、File 等存储方式的Session
// 作者: heiyeluren
// 创建: 2008-09-01 00:00
//		 2008-09-07 21:01	基本支持 memcache 和 File 的session存储
//		 2009-12-17 02:07	修改部分bug, 增加系统自带Session类，更改为PHP5对象方式和工厂模式调用
//
//==============================================================


/**
 * Session 功能工厂方法类
 *
 * 调用示例代码：
	try {
		//$s = TM_Session::factory(TM_Session::TYPE_SYSTEM);
		//$s = TM_Session::factory(TM_Session::TYPE_MEMCACHE, array('host'=>'127.0.0.1', 'port'=>11211));
		//$s = TM_Session::factory(TM_Session::TYPE_FILE, array('save_path'=>'/tmp/'));
		$s->start();
		$s->register('test1', 'value_test1');
		var_dump($s->is_registered('test1'));
		var_dump($s->get('test1'));
		$s->unregister('test1');
		var_dump($s->get('test1'));
		$s->register('test2', 'value_test2');
		$s->register('test3', 'value_test3');
		var_dump($s->getAll());
		var_dump($s->getSid());
		$s->destroy();
		var_dump($s->getAll());
	} catch (Exception $e) {
		echo $e->getMessage();
	}
 */
class TM_Session
{
	/**
	 * @var session类型为系统自带Sessoin
	 */
	const TYPE_SYSTEM		= 1;
	/**
	 * @var session类型为 Memcache Session
	 */
	const TYPE_MEMCACHE		= 2;	
	/**
	 * @var session类型为 File Session
	 */
	const TYPE_FILE			= 3;


	
	/**
	 * 保证对象不被clone
	 */
	public function __clone() {}

    /**
	 * 构造函数
	 */
	public function __construct() {}
	
	
	/**
	 * 工厂操作方法
	 *
	 * @param int $type 需要使用的Session类
	 * @param array $param 按照指定类需要独立传递的参数
	 * @param bool $start 是否初始化对象的时候启动Session
	 * @return object
	 */
	public static function factory($type = self::TYPE_SYSTEM, $param = array(), $start = false){
		if ($type == ''){
			$type = self::TYPE_SYSTEM;
		}
		switch($type) {
			case self::TYPE_SYSTEM :
				if (!function_exists('session_start')){
					throw new TM_Exception(__CLASS__ . " PHP session extension not install");
				}
				$obj = TM_Session_System::getInstance($start, $param);
				break;
			case self::TYPE_MEMCACHE :
				if (!function_exists('memcache_connect')){
					throw new TM_Exception(__CLASS__ . " PHP memcache extension not install");
				}				
				$obj = TM_Session_Memcache::getInstance($start, $param);
				break;
			case self::TYPE_FILE :
				$obj = TM_Session_File::getInstance($start, $param);
				break;
			default:
				throw new TM_Exception(__CLASS__ .": Session start $type not support");
		}
		return $obj;
	}	
	

}





//==================================================================
// 类名: System Session Class
// 功能: 基于PHP自带的 Session 功能封装的Class
// 描述: 使用PHP系统自带Session开发扩展的类
//
// 注意: 本类必须要求安装PHP时打开该功能，并且合理设置了 
//		php.ini 中关于 session.* 的各项配置选项
//
// 作者: heiyeluren
// 时间: 2009-12-17 00:40
//==================================================================
class TM_Session_System
{
	/**
	 * @var object 对象单例
	 */
	static $_instance = NULL;	
	
	
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
	public static function getInstance($isInit = false, $param = array()){
		if (!(self::$_instance instanceof self)){
			self::$_instance = new self($isInit, $param);
		}
		return self::$_instance;
	}	
   
    /**
     * 构造函数
     *
     * @param bool $isInit - 是否实例化对象的时候启动Session
     */
    private function __construct($isInit = false, $param = array()){
        if ($isInit){
            $this->start();
        }
		if (is_array($param) && !empty($param) && $param['save_path']!=''){
			session_save_path($param['save_path']);
		}
    }

   
    /**
     * 启动Session操作
     *
     * @param int $expireTime - Session失效时间,缺省是0,当浏览器关闭的时候失效, 该值单位是秒
     */
    public function start($expireTime = 0){
    	if ($expireTime > 0){
    		session_cache_expire( (int)($expireTime/60) );
    	}
		return session_start();      
    }
    
    /**
     * 判断某个Session变量是否注册
     *
     * @param string $varName - 
     * @return bool 存在返回true, 不存在返回false
     */
    public function is_registered($varName){
		return session_is_registered($varName);
    }    
        
    /**
     * 注册一个Session变量
     *
     * @param string $varName - 需要注册成Session的变量名
     * @param mixed $varValue - 注册成Session变量的值
     * @return bool - 该变量名已经存在返回false, 注册成功返回true
     */
    public function register($varName, $varValue){
    	//session_register($varName);
		$_SESSION[$varName] = $varValue;
		return true;
    }
    
    /**
     * 销毁一个已注册的Session变量
     *
     * @param string $varName - 需要销毁的Session变量名
     * @return bool 销毁成功返回true
     */
    public function unregister($varName){
        unset($_SESSION[$varName]);
        unset($varName);
		@session_unregister($varName);
        return true;
    }
    
    /**
     * 销毁所有已经注册的Session变量
     *
     * @return 销毁成功返回true
     */
    public function destroy(){
    	$_SESSION = array();
		return session_destroy();
    }
    
    /**
     * 获取一个已注册的Session变量值
     *
     * @param string $varName - Session变量的名称
     * @return mixed - 不存在的变量返回false, 存在变量返回变量值
     */
    public function get($varName){
        if (!isset($_SESSION[$varName])){
            return false;
        }
        return $_SESSION[$varName];
    }    
    
    /**
     * 获取所有Session变量
     *
     * @return array - 返回所有已注册的Session变量值
     */
    public function getAll(){
        return $_SESSION;
    }
    
    /**
     * 获取当前的Session ID
     *
     * @return string 获取的SessionID
     */
    public function getSid(){
        return session_id();
    }
   
    
}





//==================================================================
// 类名: Memcache Session Class
// 功能: 自主实现基于Memcache存储的 Session 功能
// 描述: 这个类就是实现Session的功能, 基本上是通过设置客户端的Cookie来保存SessionID,
//         然后把用户的数据保存在服务器端,最后通过Cookie中的Session Id来确定一个数据是否是用户的, 
//         然后进行相应的数据操作, 目前的缺点是没有垃圾收集功能
//
//        本方式适合Memcache内存方式存储Session数据的方式，同时如果构建分布式的Memcache服务器，
//        能够保存相当多缓存数据，并且适合用户量比较多并发比较大的情况
// 注意: 本类必须要求PHP安装了Memcache扩展, 获取Memcache扩展请访问: http://pecl.php.net
//
// 作者: heiyeluren
// 时间: 2006-12-23
// 修改：2009-12-17 01:14
//==================================================================
class TM_Session_Memcache
{
    private $sessId                = '';
    private $sessKeyPrefix         = 'sess_';
    private $sessExpireTime        = 1800;
    private $cookieName			   = '__SessHandler';
    private $cookieExpireTime      = '';    
    private $memConfig             = array('host'=>'localhost', 'port'=>11211);
    private $memObject             = null;    
        
	/**
	 * @var object 对象单例
	 */
	static $_instance = NULL;	
	
	
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
	public static function getInstance($isInit = false, $param = array()){
		if (!(self::$_instance instanceof self)){
			self::$_instance = new self($isInit, $param);
		}
		return self::$_instance;
	}	
    
    /**
     * 构造函数
     *
     * @param bool $isInit - 是否实例化对象的时候启动Session
     */
    private function __construct($isInit = false, $param = array()){
        if ($isInit){
            $this->start();
        }
		if (is_array($param) && !empty($param)){
			$this->memConfig = $param;
		}
    }

    //-------------------------
    //   外部方法
    //-------------------------
    
    /**
     * 启动Session操作
     *
     * @param int $expireTime - Session失效时间,缺省是0,当浏览器关闭的时候失效, 该值单位是秒
     */
    public function start($expireTime = 0){
        $sessId = $_COOKIE[$this->cookieName];
        try {
	        if (!$sessId){
	            $this->sessId = $this->_getId();
	            $this->cookieExpireTime = ($expireTime > 0) ? time() + $expireTime : 0;
	            setcookie($this->cookieName, $this->sessId, $this->cookieExpireTime, "/", '');
	            $this->_initMemcacheObj();
	            $_SESSION = array();
	            $this->_saveSession();
	        } else {
	            $this->sessId = $sessId;
	            $_SESSION = $this->_getSession($sessId);
	        } 	        
		} catch (TM_Exception $e){
        	throw $e;
        }
    }
    
    /**
     * 判断某个Session变量是否注册
     *
     * @param string $varName - 
     * @return bool 存在返回true, 不存在返回false
     */
    public function is_registered($varName){
        if (!isset($_SESSION[$varName])){
            return false;
        }
        return true;
    }    
        
    /**
     * 注册一个Session变量
     *
     * @param string $varName - 需要注册成Session的变量名
     * @param mixed $varValue - 注册成Session变量的值
     * @return bool - 该变量名已经存在返回false, 注册成功返回true
     */
    public function register($varName, $varValue){
        if (isset($_SESSION[$varName])){
            return false;
        }
        try {
	        $_SESSION[$varName] = $varValue;
	        $this->_saveSession();
	        return true;
        } catch (TM_Exception $e){
        	throw $e;
        }        
    }
    
    /**
     * 销毁一个已注册的Session变量
     *
     * @param string $varName - 需要销毁的Session变量名
     * @return bool 销毁成功返回true
     */
    public function unregister($varName){
    	try {
	        unset($_SESSION[$varName]);
	        $this->_saveSession();
	        return true;
        } catch (TM_Exception $e){
        	throw $e;
        }        
    }
    
    /**
     * 销毁所有已经注册的Session变量
     *
     * @return 销毁成功返回true
     */
    public function destroy(){
    	try {
	        $_SESSION = array();
	        $this->_saveSession();
	        return true;    
        } catch (TM_Exception $e){
        	throw $e;
        }        
    }
    
    /**
     * 获取一个已注册的Session变量值
     *
     * @param string $varName - Session变量的名称
     * @return mixed - 不存在的变量返回false, 存在变量返回变量值
     */
    public function get($varName){
        if (!isset($_SESSION[$varName])){
            return false;
        }
        return $_SESSION[$varName];
    }    
    
    /**
     * 获取所有Session变量
     *
     * @return array - 返回所有已注册的Session变量值
     */
    public function getAll(){
        return $_SESSION;
    }
    
    /**
     * 获取当前的Session ID
     *
     * @return string 获取的SessionID
     */
    public function getSid(){
        return $this->sessId;
    }

   
    
    //-------------------------
    //   内部接口
    //-------------------------
    
    /**
     * 生成一个Session ID
     *
     * @return string 返回一个32位的Session ID
     */
    private  function _getId(){
        return md5(uniqid(microtime()));
    }
    
    /**
     * 获取一个保存在Memcache的Session Key
     *
     * @param string $sessId - 是否指定Session ID
     * @return string 获取到的Session Key
     */
    private function _getSessKey($sessId = ''){
        $sessKey = ($sessId == '') ? $this->sessKeyPrefix.$this->sessId : $this->sessKeyPrefix.$sessId;
        return $sessKey;
    }    
    /**
     * 检查保存Session数据的路径是否存在
     *
     * @return bool 成功返回true
     */
    private function _initMemcacheObj(){
		if (!is_object($this->memObject)){
			$this->memObject = new Memcache;
			if (!($this->memObject->connect($this->memConfig['host'], $this->memConfig['port']))){
				throw new TM_Exception(__CLASS__ .": Init memcache fail, can't connect memcache");
				return false;
			}
		}
        return true;
    }
    
    /**
     * 获取Session文件中的数据
     *
     * @param string $sessId - 需要获取Session数据的SessionId
     * @return unknown
     */
    private function _getSession($sessId = ''){
        $this->_initMemcacheObj();
        $sessKey = $this->_getSessKey($sessId);
        $sessData = $this->memObject->get($sessKey);
        if (!is_array($sessData) || empty($sessData)){
        	throw new TM_Exception(__CLASS__ .": ".'Failed: Session ID '. $sessKey .' session data not exists');
        }
        return $sessData;
    }
    
    /**
     * 把当前的Session数据保存到Memcache
     *
     * @param string $sessId - Session ID
     * @return 成功返回true
     */
    private function _saveSession($sessId = ''){
        $this->_initMemcacheObj();
        $sessKey = $this->_getSessKey($sessId);
        if (empty($_SESSION)){
            $ret = @$this->memObject->set($sessKey, $_SESSION, false, $this->sessExpireTime);
        }else{
            $ret = @$this->memObject->replace($sessKey, $_SESSION, false, $this->sessExpireTime);
        }
        if (!$ret){
        	throw new TM_Exception(__CLASS__ .': Failed: Save sessiont data failed, please check memcache server');
        }
        return true;
    }
  
}




//==================================================================
// 类名: File Session Class
// 功能: 自主实现基于文件存储的 Session 功能
// 描述: 这个类就是实现Session的功能, 基本上是通过设置客户端的Cookie来保存SessionID,
//         然后把用户的数据保存在服务器端,最后通过Cookie中的Session Id来确定一个数据是否是用户的, 
//         然后进行相应的数据操作, 目前的缺点是没有垃圾收集功能
//
//        本方式适合保存在普通文件、共享内存(SHM)、NFS服务器等基于文件存储的方式，推荐保存在共享 
//        内存当中，因为共享内存存取效率比较高，但是空间比较小，重启后就销毁了
//
// 作者: heiyeluren
// 时间: 2006-12-22
// 修改：2009-12-17 01:12
//==================================================================
class TM_Session_File
{
    public $sessId				= '';
    public $sessSavePath		= '/tmp/';
    public $isCreatePath		= true;
    public $sessExpireTime		= 1800;
    public $sessFilePrefix		= 'sess_';
    public $cookieName			= '__SessHandler';
    
    
	/**
	 * @var object 对象单例
	 */
	static $_instance = NULL;	
	
	
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
	public static function getInstance($isInit = false, $param = array()){
		if (!(self::$_instance instanceof self)){
			self::$_instance = new self($isInit, $param);
		}
		return self::$_instance;
	}	
	    
    /**
     * 构造函数
     *
     * @param bool $isInit - 是否实例化对象的时候启动Session
     */
    private function __construct($isInit = false, $param = array()){
    	if (is_array($param) && !empty($param) && $param['save_path'] != ''){
			$this->sessSavePath = $param['save_path'];
		}
        if ($isInit){
            $this->start();
        }
    }

    //-------------------------
    //   外部方法
    //-------------------------
    
    /**
     * 启动Session操作
     *
     * @param int $expireTime - Session失效时间,缺省是0,当浏览器关闭的时候失效, 该值单位是秒
     */
    public function start($expireTime = 0){
        $sessId = $_COOKIE[$this->cookieName];
        if (!$sessId){
            if (!$this->_checkSavePath()){
            	throw new TM_Exception(__CLASS__ .': Session save path '. $this->sessSavePath .' not or create path failed');
            }
            try {
	            $this->sessId = $this->_getId();
	            $this->sessExpireTime = ($expireTime > 0) ? time() + $expireTime : 0;
	            setcookie($this->cookieName, $this->sessId, $this->sessExpireTime, "/", '');            
	            $_SESSION = array();
	            $this->_writeFile();
	        } catch (TM_Exception $e){
	        	throw $e;
	        }            
        } else {
            $this->sessId = $sessId;
            $_SESSION = unserialize($this->_getFile($sessId));
        }        
    }
    
    /**
     * 判断某个Session变量是否注册
     *
     * @param string $varName - 
     * @return bool 存在返回true, 不存在返回false
     */
    public function is_registered($varName){
        if (!isset($_SESSION[$varName])){
            return false;
        }
        return true;
    }    
        
    /**
     * 注册一个Session变量
     *
     * @param string $varName - 需要注册成Session的变量名
     * @param mixed $varValue - 注册成Session变量的值
     * @return bool - 该变量名已经存在返回false, 注册成功返回true
     */
    public function register($varName, $varValue){
        if (isset($_SESSION[$varName])){
            return false;
        }
        try {
	        $_SESSION[$varName] = $varValue;
	        $this->_writeFile();
	        return true;
        } catch (TM_Exception $e){
        	throw $e;
        }        
    }
    
    /**
     * 销毁一个已注册的Session变量
     *
     * @param string $varName - 需要销毁的Session变量名
     * @return bool 销毁成功返回true
     */
    public function unregister($varName){
        try {    	
 	       unset($_SESSION[$varName]);
	        $this->_writeFile();
	        return true;
        } catch (TM_Exception $e){
        	throw $e;
        }       
    }
    
    /**
     * 销毁所有已经注册的Session变量
     *
     * @return 销毁成功返回true
     */
    public function destroy(){
        try {
        	$_SESSION = array();        	
        	$this->_writeFile();
        	return true;
        } catch (TM_Exception $e){
        	throw $e;
        }
    }
    
    /**
     * 获取一个已注册的Session变量值
     *
     * @param string $varName - Session变量的名称
     * @return mixed - 不存在的变量返回false, 存在变量返回变量值
     */
    public function get($varName){
        if (!isset($_SESSION[$varName])){
            return false;
        }
        return $_SESSION[$varName];
    }    
    
    /**
     * 获取所有Session变量
     *
     * @return array - 返回所有已注册的Session变量值
     */
    public function getAll(){
        return $_SESSION;
    }
    
    /**
     * 获取当前的Session ID
     *
     * @return string 获取的SessionID
     */
    public function getSid(){
        return $this->sessId;
    }

    /**
     * 获取服务器端保存的Session数据的路径
     *
     * @return string 保存Session的路径
     */
    public function getSavePath(){
        return $this->sessSavePath;
    }
    
    /**
     * 设置保存Session数据的路径
     *
     * @param string $savePath - 需要保存Session数据的绝对路径
     */
    public function setSavePath($savePath){
        $this->sessSavePath = $savePath;
    }    
    
    
    //-------------------------
    //   内部接口
    //-------------------------
    
    /**
     * 生成一个Session ID
     *
     * @return string 返回一个32位的Session ID
     */
    private function _getId(){
        return md5(uniqid(microtime()));
    }
    
    /**
     * 检查保存Session数据的路径是否存在
     *
     * @return bool 成功返回true
     */
    private function _checkSavePath(){
        if (file_exists($this->sessSavePath)){
            return true;
        }
        if (!$this->isCreatePath){
            return false;
        }
        if (!@mkdir($this->sessSavePath)){
        	throw new TM_Exception(__CLASS__ .': Failed: Session cache path '. $this->sessSavePath .'is not exists, create failed');
        }
        @chmod($this->sessSavePath, 0777);        
        return true;
    }
    
    /**
     * 获取Session文件中的数据
     *
     * @param string $sessId - 需要获取Session数据的SessionId
     * @return unknown
     */
    private function _getFile($sessId = ''){
        $sessId = ($sessId == '') ? $this->sessId : $sessId;
        $sessFile = $this->sessSavePath . $this->sessFilePrefix . $sessId;
        if (!file_exists($sessFile)){
        	throw new TM_Exception(__CLASS__ .': Failed: Session file '. $sessFile .' not exists');
        }
        return file_get_contents($sessFile);
    }
    
    /**
     * 把当前的Session数据写入到数据文件
     *
     * @param string $sessId - Session ID
     * @return 成功返回true
     */
    private function _writeFile($sessId = ''){
        $sessId = ($sessId == '') ? $this->sessId : $sessId;
        $sessFile = $this->sessSavePath . $this->sessFilePrefix . $sessId;
        $sessStr = serialize($_SESSION);
        if (!$fp = @fopen($sessFile, "w+")){
        	throw new TM_Exception(__CLASS__ .': Failed: Open session save file '. $sessFile .' failed');
        }
        if (!@fwrite($fp, $sessStr)){
        	throw new TM_Exception(__CLASS__ .': Failed: Write session data to '. $sessFile .' failed');
        }
        @fclose($fp);
        return true;
    }

}

