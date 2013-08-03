<?php
/********************************
 *  描述: TMPHP Response 类
 *  作者: heiyeluren 
 *  创建: 2009/12/1 23:58
 *  修改: 2009/12/13 3:23   创建基本操作方法
 *		  2009-12-22 0:04	修改bug
 ********************************/

class TM_Response
{
	/**
	 * @var object 对象单例
	 */
	static $_instance = NULL;
	/**
	 * @var array HTTP Cookie信息
	 */
	public $cookies = array();
	/**
	 * @var array HTTP头信息
	 */
	private $header = array();
	/**
	 * @var string 内容信息
	 */
	private $body = '';
	
	/**
	 * @var array HTTP 状态码列表
	 */
	static protected $statusTexts = array(
		'100' => 'Continue',
		'101' => 'Switching Protocols',
		'200' => 'OK',
		'201' => 'Created',
		'202' => 'Accepted',
		'203' => 'Non-Authoritative Information',
		'204' => 'No Content',
		'205' => 'Reset Content',
		'206' => 'Partial Content',
		'300' => 'Multiple Choices',
		'301' => 'Moved Permanently',
		'302' => 'Found',
		'303' => 'See Other',
		'304' => 'Not Modified',
		'305' => 'Use Proxy',
		'306' => '(Unused)',
		'307' => 'Temporary Redirect',
		'400' => 'Bad Request',
		'401' => 'Unauthorized',
		'402' => 'Payment Required',
		'403' => 'Forbidden',
		'404' => 'Not Found',
		'405' => 'Method Not Allowed',
		'406' => 'Not Acceptable',
		'407' => 'Proxy Authentication Required',
		'408' => 'Request Timeout',
		'409' => 'Conflict',
		'410' => 'Gone',
		'411' => 'Length Required',
		'412' => 'Precondition Failed',
		'413' => 'Request Entity Too Large',
		'414' => 'Request-URI Too Long',
		'415' => 'Unsupported Media Type',
		'416' => 'Requested Range Not Satisfiable',
		'417' => 'Expectation Failed',
		'500' => 'Internal Server Error',
		'501' => 'Not Implemented',
		'502' => 'Bad Gateway',
		'503' => 'Service Unavailable',
		'504' => 'Gateway Timeout',
		'505' => 'HTTP Version Not Supported',
	);	

	
	/**
	 * 保证对象不被clone
	 */
	private function __clone() {}

    /**
	 * 构造函数
	 */
	private function __construct() {}


	/**
	 * 获取对象唯一实例
	 *
	 * @param void
	 * @return object 返回本对象实例
	 */
	public static function getInstance(){
		if (!(self::$_instance instanceof self)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * 检查HTTP头信息是否已经发送
	 *
	 * @return bool
	 */
	public function isHederSent(){
		return headers_sent();
	}

	/**
	 * 设置一个需要发送的Cookie信息
	 *
	 * @param ... read more about PHP setcookie() function manual
	 * @return void
	 */
	public function setCookie($name, $value = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false){
	    $this->cookies[] = array(
	      'name'     => $name,
	      'value'    => $value,
	      'expire'   => $expire,
	      'path'     => $path,
	      'domain'   => $domain,
	      'secure'   => $secure ? true : false,
	      'httpOnly' => $httpOnly,
	    );		
		//setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
	}
	
	/**
	 * 获取已经设置的Cookie信息
	 *
	 * @return array
	 */
	public function getCookie(){
		return $this->cookies;		
	}

	/**
	 * 设置需要发送的HTTP头信息
	 *
	 * @param string $header
	 * @return void
	 */
	public function setHeader($header){
		$this->header[] = $header;
	}

	/**
	 * 获取已经设置的HTTP头信息
	 *
	 * @return array 
	 */
	public function getHeader(){
		return $this->header;
	}

	/**
	 * 设置一个需要输出的HTML内容
	 *
	 * @param string $content
	 * @return void
	 */
	public function setContent($content){
		$this->body = $content;
	}

	/**
	 * 附加一个HTML内容
	 *
	 * @param string $content
	 * @return void
	 */
	public function appendContent($content){
		$this->body .= $content;
	}

	/**
	 * 获取设置的HTML内容
	 *
	 * @param void
	 * @return string
	 */
	public function getContent(){
		return $this->body;
	}
	
	/**
	 * 单独发送一个HTTP Cookie信息
	 *
	 * @param ... read more about PHP setcookie() function manual
	 * @return void
	 */	
	public function sendHttpCookie($name, $value = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false){
		@setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
	}
		
	/**
	 * 单独发送一个HTTP头信息
	 *
	 * @param unknown_type $header
	 * @param unknown_type $code
	 */
	public function sendHttpHeader($header, $code = 0){
		if ($code > 0) {
			$status = 'HTTP/1.1 '.$code.' '.self::$statusTexts[$code];
			@header($status);
		}
		if ($header != ''){
			@header($header);			
		}
	}
	

	/**
	 * 发送所有组织好的HTTP输出内容
	 *
	 * @param void
	 * @return void
	 */
	public function send(){
		// http header
		if (!empty($this->header)){
			foreach($this->header as $head){
				@header($head);
			}
		}
	    // cookies
	    foreach ($this->cookies as $cookie) {
	      if (version_compare(phpversion(), '5.2', '>=')) {
	        setrawcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httpOnly']);
	      } else {
	        setrawcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain'], $cookie['secure']);
	      }
	    }
		// content
		if ($this->body != ''){
			echo $this->body;
		}
	}



}


