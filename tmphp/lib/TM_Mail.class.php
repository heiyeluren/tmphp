<?php
/******************************************************************************
 *  描述：邮件发送类
 *  作者：heiyeluren
 *  创建：2007-04-09 18:30
 *  修改：2007-04-11 16:09	基本实现三套独立的邮件发送方式
 *		 2009-12-19 20:47	修改Socket处理方式，整合原来三个发送类，修改代码风格为PHP5
 *****************************************************************************/


/**
 * Mail 功能工厂方法类
 *
 * 调用示例代码：
	try {
		$mail = array(
			'mail_from'		=>'test1@126.com', 
			'mail_to'		=>'test1@126.com,test2@163.com', 
			'mail_subject'	=>'test', 
			'mail_body'		=>'this mail is test', 
			'mail_format'	=>TM_Mail::FORMAT_TEXT, 
			'mail_encoding'	=>'UTF-8'
		);
		
		//使用SMTP发送邮件
		$s = TM_Mail::factory(TM_Mail::TYPE_SMTP, $mail, array('smtp_host'=>'smtp.126.com', 'smtp_port'=>25, 'smtp_user'=>'test1', 'smtp_pwd'=>'pwd1', 'debug'=>false));
		$ret = $s->send();
		var_dump($ret);
		
		//使用命令行发送邮件
		$s = TM_Mail::factory(TM_Mail::TYPE_COMMOND, $mail);
		$ret = $s->send();
		var_dump($ret);
				
		//使用PHP内置函数发送邮件
		$s = TM_Mail::factory(TM_Mail::TYPE_PHP, $mail);
		$ret = $s->send();
		var_dump($ret);		
		
	} catch (TM_Exception $e) {
		echo $e->getMessage();
	}
 */
class TM_Mail
{
	/**
	 * @var 邮件发送方式为SMTP
	 */
	const TYPE_SMTP			= 1;
	/**
	 * @var 邮件发送方式为系统命令
	 */
	const TYPE_COMMOND		= 2;	
	/**
	 * @var 邮件发送方式为PHP函数
	 */
	const TYPE_PHP			= 3;
	
	/**
	 * @var 邮件格式为HTML
	 */
	const FORMAT_HTML		= 1;
	/**
	 * @var 邮件各位是TEXT
	 */
	const FORMAT_TEXT		= 2;	


	
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
	 * @param int $type 需要使用的Mail类
	 * @param array $data  邮件体数组信息，一个数组，结构是：
	 * 		array(
	 * 			"mail_from"		=> 'from@domain.com',				//邮件发送者邮箱
	 * 			"mail_to"		=> 'to@domain.com,to2@domain.com',	//接收用户邮箱，多个接收者之间使用逗号分隔, 也可以传递一个一维数组
	 * 			"mail_subject"	=> 'Email Sbuject',					//邮件发送主题
	 * 			"mail_body"		=> 'Email contents',				//邮件内容
	 * 			"mail_format"	=> TM_Mail::FORMAT_HTML,			//邮件发送格式：TM_Mail::FORMAT_HTML(HTML格式) 或 TM_Mail::FORMAT_TEXT(文本格式)
	 * 			"mail_encoding"	=> 'UTF-8',							//邮件内容编码, 一般是 'UTF-8' 或 'GB2312'/'GBK'
	 * 		);
	 * @param array $param 邮件类需要的参数，STMP类需要SMTP主机信息，Command类需要命令路劲，可以参考类构造函数参数列表
	 * @return object
	 */
	public static function factory($type = self::TYPE_SMTP, $data = array(), $param = array()){
		if ($type == ''){
			$type = self::TYPE_SMTP;
		}
		switch($type) {
			case self::TYPE_SMTP :
				if (!function_exists('fsockopen')){
					throw new TM_Exception(__CLASS__ . " PHP fsockopen() function not available");
				}
				$obj = TM_Mail_Smtp::getInstance($data, $param);
				break;
			case self::TYPE_COMMOND :
				$obj = TM_Mail_Command::getInstance($data, $param);
				break;
			case self::TYPE_PHP :
				if (!function_exists('mail')){
					throw new TM_Exception(__CLASS__ . " PHP mail() function not available");
				}				
				$obj = TM_Mail_Php::getInstance($data, $param);
				break;
			default:
				throw new TM_Exception(__CLASS__ .": Mail use $type not support");
		}
		return $obj;
	}	
	

}



/**
 * 调用远程SMTP服务器发送邮件
 * 
 * 描述：
 * 调用远程SMTP服务器来发送邮件，基本兼容包括163/126等SMTP服务器协议支持
 * 必须要求有远程SMTP服务器的验证用户、验证密码等，因为存在远程Socket操作，发送速度一般
 *
 */
class TM_Mail_Smtp
{
	/**
	 * SMTP主机
	 */
	private $smtpHost = 'localhost';

	/**
	 * SMTP端口
	 */
	private $smtpPort = 25;

	/**
	 * 登录用户
	 */
	private $loginUser = '';

	/**
	 * 登录密码
	 */
	private $loginPasswd = '';

	/**
	 * 发件人
	 */
	private $mailFrom = '';

	/**
	 * 收件人
	 */
	private $mailTo = '';

	/**
	 * 邮件主题
	 */
	private $mailSubject = '';

	/**
	 * 邮件内容
	 */
	private $mailBody = '';

	/**
	 * 邮件内容MIME类型
	 */
	private $mailType = TM_Mail::FORMAT_HTML;
	/**
	 * 邮件内容编码
	 */
	private $mailEncoding = 'UTF-8';
	/**
	 * 是否打开调试模式
	 */
	private $debug		 = false;

	/**
	 * 网络连接
	 */
	private $conn;
	/**
	 * 当前SMTP协议命令
	 */
	private $protocolCMD = '';
	/**
	 * 当前协议服务器端返回值
	 */
	private $protocolRet = '';
	
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
	 * @param array $data 邮件内容数组 (参考 __construct() 参数定义)
	 * @param array $param SMTP配置数组 (参考 __construct() 参数定义)
	 * @return object 返回本对象实例
	 */
	public static function getInstance($data, $param){
		if (!(self::$_instance instanceof self)){
			self::$_instance = new self($data, $param);
		}
		return self::$_instance;
	}
		

	/**
	 * 构造函数
	 * 
	 * @param array $data  邮件体数组信息，一个数组，结构是：
	 * 		array(
	 * 			"mail_from"		=> 'from@domain.com',				//邮件发送者邮箱
	 * 			"mail_to"		=> 'to@domain.com,to2@domain.com',	//接收用户邮箱，多个接收者之间使用逗号分隔, 也可以传递一个一维数组
	 * 			"mail_subject"	=> 'Email Sbuject',					//邮件发送主题
	 * 			"mail_body"		=> 'Email contents',				//邮件内容
	 * 			"mail_format"	=> TM_Mail::FORMAT_HTML,			//邮件发送格式：TM_Mail::FORMAT_HTML(HTML格式) 或 TM_Mail::FORMAT_TEXT(文本格式)
	 * 			"mail_encoding"	=> 'UTF-8',							//邮件内容编码, 一般是 'UTF-8' 或 'GB2312'/'GBK'
	 * 		);
	 * @param array $param SMTP主机连接相关信息，一个数组，结构是：
	 * 		array(
	 * 			"smtp_host" 	=> 'smtp.domain.com',				//SMTP主机地址
	 * 			"smtp_port"		=> 25,								//SMTP主机端口
	 * 			"smtp_user"		=> 'user',							//SMTP验证用户
	 * 			"smtp_pwd"		=> 'password',						//SMTP验证密码
	 * 			"debug"			=> false,							//是否打开SMTP调试模式，打开后将查看到SMTP交互过程协议信息
	 * 		);
	 * 
	 * @return void
	 */
	private function __construct($data, $param) { 
		//构建SMTP连接参数
		$this->smtpHost		= $param['smtp_host']; 	//$smtpHost;
		$this->smtpPort		= $param['smtp_port']=='' ? 25 : $param['smtp_port'];	//$smtpPort = $smtpPort=="" ? 25 : $smtpPort;
		$this->loginUser	= base64_encode($param['smtp_user']);
		$this->loginPasswd	= base64_encode($param['smtp_pwd']);
		$this->debug 		= isset($param['debug']) ? $param['debug']  : $this->debug;		
		
		//构建邮件体
		$this->mailFrom		= $data['mail_from']; 	
		$this->mailTo		= $data['mail_to']; 	
		$this->mailSubject	= $data['mail_subject'];
		$this->mailBody		= $data['mail_body'];	
		$this->mailType		= $data['mail_format'];
		$this->mailEncoding	= $data['mail_encoding'];
	}

	/**
	 * 连接到SMTP服务器
	 *
	 * @return mixed 成功返回true，失败返回错误对象
	 */
	public function connect(){
		$fp = @fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, 5);
		if (!$fp) {
			throw new TM_Exception("Can't connect smtp host ". $this->smtpHost .", error:$errno:$errstr");
		}
		//@stream_set_blocking($fp, true);		
		$this->conn = $fp;
		return true;
	}

	/**
	 * 发送SMTP协议指令
	 *
	 * @param string $cms 需要发送的指令信息
	 * @return mixed 成功返回发送内容字节数，失败返回错误对象
	 */
	public function sendCommand($cmd){
		$this->protocolCMD = $cmd;
		if (!is_resource($this->conn)){
			throw new TM_Exception("Not availability socket connection");
		}
		if (($size = @fputs($this->conn, $cmd)) === false){
			throw new TM_Exception("Socket connection write data failed");
		}
		$this->getLine();
		//打开调试模式
		if ($this->debug){
			echo $this->protocolCMD;
			echo $this->protocolRet;
		}
		return $size;
	}

	/**
	 * 获取一行返回信息
	 *
	 * @param string 返回消息中的一行，失败返回错误对象
	 */
	public function getLine(){
		if (!is_resource($this->conn)){
			throw new TM_Exception("Not availability socket connection");
		}
		if (($line = @fread($this->conn, 8192)) === false){
			throw new TM_Exception("Socket connection read data failed");
		}
		$this->protocolRet = $line;
		return $line;
	}

	/**
	 * 构造邮件信体内容
	 */
	public function getMailMessage(){
		$mailMessage = "";
		$this->mailFrom = trim($this->mailFrom);
		$mailMessage .= "From: " . $this->mailFrom ."\r\n";
		if (is_array($this->mailTo)){
			$this->mailTo = trim(implode(",", $this->mailTo));
		}
		$mailMessage .= "To: ". $this->mailTo ."\r\n";
		$mailMessage .= "Subject: ". ($this->mailSubject=="" ? "Not Subject" : trim($this->mailSubject)) ."\r\n";
		$mailMessage .= "Mime-Version: 1.0\n";
		$mailMessage .= "Content-Type: ". ($this->mailType==TM_Mail::FORMAT_HTML ? "text/html" : "text/plain") ."; charset=".$this->mailEncoding."\r\n";
		$mailMessage .= "X-Mailer: HeiyelurenMailer\r\n";
		$mailMessage .= "\r\n\r\n";
		$mailMessage .= ($this->mailBody=="" ? "Not Contents" : $this->mailBody);

		return $mailMessage;
	}


	/**
	 * 发送邮件
	 *
	 * @return 成功返回true，失败或者发生错发返回错误对象
	 */
	public function send(){
		try {
			//连接smtp
			$this->connect();
			$this->getLine();
			
			//获取邮件内容信息
			$mailMessage = $this->getMailMessage();
	
			//打开SMTP
			$this->sendCommand("EHLO HELO\r\n");
	
			//验证登录
			$this->sendCommand("AUTH LOGIN\r\n");
			$this->sendCommand($this->loginUser ."\r\n");
			$this->sendCommand($this->loginPasswd ."\r\n");
			if (!preg_match("/235|220/", $this->protocolRet)){
				throw new TM_Exception("SMTP user auth failed");
			}
	
			//提交发送用户信息
			$this->sendCommand("MAIL FROM:<". $this->mailFrom .">\r\n");
			if (!preg_match("/^(3|2)/", $this->protocolRet)){
				throw new TM_Exception("SMTP mail_from user invalid or protocol  not support, mail_from:". $this->mailFrom);
			}
			
			//发送给多个收件人
			$arrTo = explode(",", $this->mailTo);
			foreach ($arrTo as $to){
				$to = trim($to);
				$this->sendCommand("RCPT TO: <$to>\r\n");
				if (!preg_match("/^(3|2)/", $this->protocolRet)){
					throw new TM_Exception("SMTP protocol 'RCPT TO' error or invalid");
				}
			}
	
			//发送邮件信体
			$this->sendCommand("DATA\r\n");
			if (!preg_match("/^(3|2)/", $this->protocolRet)){
				throw new TM_Exception("SMTP protocol 'DATA' error or invalid");
			}
			$this->sendCommand($mailMessage ."\r\n.\r\n");
			if (!preg_match("/^(3|2)/", $this->protocolRet)){
				throw new TM_Exception("SMTP protocol send mail error or invalid");
			}
			//print_r($this);exit;
			if (!preg_match("/250/", $this->protocolRet)){
				throw new TM_Exception("Send mail failed");
			}
	
			//退出SMTP
			$this->sendCommand("QUIT\r\n");
	
			//关闭连接
			@fclose($this->conn);
			
			return true;
			
		} catch (TM_Exception $e) {
			throw $e;
		}
	}
	

}



/**
 * 调用本地命令来发送邮件
 * 
 * 描述：
 * 调用本地邮件服务器或邮件发送程序来发送邮件（本类是测试阶段，使用请谨慎，建议大批量发送邮件使用）
 * 缺省推荐使用qmail、sendmail来发送，最好做相应程序的修改，目前支持的是qmail
 *
 */
class TM_Mail_Command
{

	private $cmd 		= "/usr/bin/qmail";		//邮件路径可能是/usr/sbin/qmail、/usr/bin/sendmail
	private $mailForm 	= '';
	private $mailTo 	= '';
	private $mailSubject;
	private $mailBody 	= '';
	private $mailType 	= TM_Mail::FORMAT_HTML;
	private $mailEncoding = 'UTF-8';

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
	 * @param array $data 邮件内容数组 (参考 __construct() 参数定义)
	 * @param array $param 命令配置数组 (参考 __construct() 参数定义)
	 * @return object 返回本对象实例
	 */
	public static function getInstance($data, $param){
		if (!(self::$_instance instanceof self)){
			self::$_instance = new self($data, $param);
		}
		return self::$_instance;
	}
		
	/**
	 * 构造函数
	 * 
	 * @param array $data  邮件体数组信息，一个数组，结构是：
	 * 		array(
	 * 			"mail_from"		=> 'from@domain.com',				//邮件发送者邮箱
	 * 			"mail_to"		=> 'to@domain.com,to2@domain.com',	//接收用户邮箱，多个接收者之间使用逗号分隔, 也可以传递一个一维数组
	 * 			"mail_subject"	=> 'Email Sbuject',					//邮件发送主题
	 * 			"mail_body"		=> 'Email contents',				//邮件内容
	 * 			"mail_format"	=> TM_Mail::FORMAT_HTML,			//邮件发送格式：TM_Mail::FORMAT_HTML(HTML格式) 或 TM_Mail::FORMAT_TEXT(文本格式)
	 * 			"mail_encoding"	=> 'UTF-8',							//邮件内容编码, 一般是 'UTF-8' 或 'GB2312'/'GBK'
	 * 		);
	 * @param array $param 邮件发送命令相关信息，一个数组，结构是：
	 * 		array(
	 * 			"cmd" 	=> '/usr/bin/mail',				//邮件命令路径，一般是 /usr/bin/mail 或 /usr/bin/qmail
	 * 		);
	 * 
	 * @return void
	 */
	private function __construct($data, $param) { 
		//构建执行命令
		$this->cmd = isset($param['cmd']) ? $this->cmd : $param['cmd'];		
		
		//构建邮件体
		$this->mailFrom		= $data['mail_from']; 	
		$this->mailTo		= $data['mail_to']; 	
		$this->mailSubject	= $data['mail_subject'];
		$this->mailBody		= $data['mail_body'];	
		$this->mailType		= $data['mail_format'];	
		$this->mailEncoding	= isset($data['mail_encoding']) ? $data['mail_encoding'] : $this->mailEncoding;
	}

	
	/**
	 * 函数：user_send_validate_mail($email_addr, $subject,$to_uid,$content)
	 * 功能：使用qmail发送校验邮件函数（支持HTML）
	 * 参数：
	 * $email_addr		接受用户邮件地址
	 * $subject			邮件主题
	 * $to_uid			接受的用户
	 * $content			邮件内容
	 * 返回：成功返回true，失败返回false
	 */	
	public function send(){
		//检查命令是否存在
		if (!is_file($this->cmd) || !is_executable($this->cmd)){
			throw new TM_Exception("Mail send command ". $this->cmd ." not exist or not executable");
		}
		
		//构造指令
		$command =  $cmd." -f ".$this->mailFrom ." ". $this->mailTo; 

		//打开管道
		if (!($handle = @popen($command, "w"))){
			throw new TM_Exception("Open mail command ". $command ." failed");
		} 

		//往管道写数据
		$mailTo = is_array($this->mailTo) ? implode(",", $this->mailTo) : $this->mailTo;
		$mimeHeader = $this->mailType==TM_Mail::FORMAT_HTML ? "text/html" : "text/plain";
		@fwrite($handle, "From: ".$this->mailFrom."\n"); 
		@fwrite($handle, "Return-Path: ".$this->mailFrom."\n");
		@fwrite($handle, "To: ".$mailTo."\n");
		@fwrite($handle, "Subject: ".$this->mailSubject."\n");
		@fwrite($handle, "Mime-Version: 1.0\n");
		@fwrite($handle, "Content-Type: $mimeHeader; charset=\"".$this->mailEncoding."\"\n\n");
		@fwrite($handle, $this->mailBody);
		if (!@pclose($handle)){
			return true;
		}
		return false;
	}
	
}




/**
 * 使用内置 mail 函数进行邮件发送类
 *
 * 描述：调用PHP内置的mail()函数来发送邮件，请确保mail函数没有屏蔽，并且相关设置能够工作
 */
class TM_Mail_Php {

	private $mailForm 	= '';
	private $mailTo 	= '';
	private $mailSubject;
	private $mailBody 	= '';
	private $mailType 	= TM_Mail::FORMAT_HTML;
	private $mailEncoding = 'UTF-8';

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
	 * @param array $data 邮件内容数组 (参考 __construct() 参数定义)
	 * @param array $param 命令配置数组 (参考 __construct() 参数定义)
	 * @return object 返回本对象实例
	 */
	public static function getInstance($data, $param){
		if (!(self::$_instance instanceof self)){
			self::$_instance = new self($data, $param);
		}
		return self::$_instance;
	}
		
	/**
	 * 构造函数
	 * 
	 * @param array $data  邮件体数组信息，一个数组，结构是：
	 * 		array(
	 * 			"mail_from"		=> 'from@domain.com',				//邮件发送者邮箱
	 * 			"mail_to"		=> 'to@domain.com,to2@domain.com',	//接收用户邮箱，多个接收者之间使用逗号分隔, 也可以传递一个一维数组
	 * 			"mail_subject"	=> 'Email Sbuject',					//邮件发送主题
	 * 			"mail_body"		=> 'Email contents',				//邮件内容
	 * 			"mail_format"	=> TM_Mail::FORMAT_HTML,			//邮件发送格式：TM_Mail::FORMAT_HTML(HTML格式) 或 TM_Mail::FORMAT_TEXT(文本格式)
	 * 			"mail_encoding"	=> 'UTF-8',							//邮件内容编码, 一般是 'UTF-8' 或 'GB2312'/'GBK'
	 * 		);
	 * @param array $param 邮件发送命令相关信息，一个数组(备用)
	 * 
	 * @return void
	 */
	private function __construct($data, $param = array()) { 
		//构建邮件体
		$this->mailFrom		= $data['mail_from']; 	
		$this->mailTo		= $data['mail_to']; 
		$this->mailSubject	= $data['mail_subject'];
		$this->mailBody		= $data['mail_body'];	
		$this->mailType		= $data['mail_format'];	
	}
	
	/**
	 * 使用 mail 函数发送普通邮件
	 *
	 *
	 */
	public function send(){
		$mail_to        = is_array($this->mailTo) ? implode(",", $this->mailTo) : $this->mailTo;
		$mail_from      = $this->mailForm;
		$mail_subject   = $this->mailSubject;
		$mail_body      = $this->mailBody;
		$mail_type      = $this->mailType==TM_Mail::FORMAT_HTML ? 'text/html' : 'text/plain';
		
		$header = "";
		$header .= "MIME-Version: 1.0\r\n";
		$header .= "Content-Type: ".$mail_type."; charset=".$this->mailEncoding."\r\n";
		$header .= "To: $mail_to\r\n";
		$header .= "From: $mail_from\r\n";
		$header .= "Reply-To: $mail_from\r\n";
		$header .= "X-Mailer: HeiyelurenMailer\r\n";

		return mail($mail_to, $mail_subject, $mail_body, $header); 
	}

	
	/**
	 * 使用 mail 函数发送带有附件的邮件(目前仅支持部分邮箱)
	 *
	 * @desc  本函数仅仅是个试验函数，用于特殊情况下时候
	 */
	public static function sendAttachment($to, $from, $subject, $body, $attachment, $is_html = true, $encoding = 'UTF-8'){
		$mail_to = implode(",", preg_split("/,|;|:|，|、/", preg_replace("/\s+/", "", $to)));
		$mail_from = $from;
		$mail_subject = mb_convert_encoding($subject, "UTF-8", "GBK");
		$body = base64_encode($body);
		$mail_attachment = chunk_split(base64_encode($attachment));
		$mail_boundary = uniqid( "");
		$atta_name = "cand_site_". date("Ymd") .".csv";

		$header = "";
		$header .= "MIME-Version: 1.0\r\n";
		$header .= "Content-type: multipart/mixed; boundary=".$mail_boundary."\r\n";
		$header .= "From: $mail_from\r\n";
		$header .= "Reply-To: $mail_from\r\n";
		$header .= "X-Mailer: HeiyelurenMailer\r\n";

		$mail_body = "";
		$mail_body .= "--$mail_boundary\r\n";

		$mail_body .= "Content-Type: ". ($is_html ? 'text/html' : 'text/plain') ."; charset=".$encoding."\r\n";
		$mail_body .= "Content-Disposition: inline\r\n";
		$mail_body .= "Content-Transfer-Encoding: base64\r\n";
		$mail_body .= "\r\n";
		$mail_body .= $body ."\r\n";
		$mail_body .= "\r\n";
		$mail_body .= "--$mail_boundary\r\n";
		//$mail_body .= "\r\n";
		$mail_body .= "Content-Type: text/plain; charset=".$encoding."\r\n";//name=$atta_name\r\n";
		$mail_body .= "Content-Disposition: attachment; filename=$atta_name\r\n";
		//还可以使用 Content-disposition: inline 内联方式 或者 attachment 附件方式
		$mail_body .= "Content-Transfer-Encoding: base64\r\n";
		$mail_body .= "\r\n";
		$mail_body .= $mail_attachment ."\r\n";
		$mail_body .= "\r\n";
		$mail_body .= "--$mail_boundary--";

		return mail($mail_to, $mail_subject, $mail_body, $header); 
	}
	
	
}


