<?php
/************************************************
 *  描述：Socket操作基础类
 *  作者：heiyeluren
 *  创建：2007-04-04 15:51
 *  修改：2007-04-09 19:48	实现基本的Socket操作
 *		 2009-12-17 03:14	修改bug，更新代码为PHP5风格
 *
 ************************************************/


/**
 * Socket操作基础类
 *
 * 包含基本的客户端Socket操作方法
 *
 * //示例代码: 实现HTTP协议访问
	try {
		$s = new TM_Socket('www.baidu.com', 80);
		$s->connect();
		if ($s->isConnected()){
			$s->writeLine("GET / HTTP/1.0");
			$header  = "Host: www.baidu.com\r\n";
			$header .= "Accept-Language: zh-cn\r\n";
			$header .= "Content-Type: text/html;\r\n";
			$header .= "Connect: close\r\n\r\n";
			$s->write($header);
			$buf = '';
			$isHeader = 1;
			while (!$s->isEof()) {
				$line = $s->readLine();
				if ($line=="\n" || $line=="\r\n"){
					$isHeader = 0;
				}
				if (!$isHeader) {
					$buf = $s->readAll();
				}		
			}
			echo $buf;
		}
	} catch (TM_Exception $e) {
		echo  $e->getMessage() ." at Line ".$e->getLine();
	}
 */
class TM_Socket
{
	//错误代码提示码
	const SOCKET_ERR_NO					= -1;
	const SOCKET_ERR_ADDR_INVALID		= -2;
	const SOCKET_ERR_PORT_INVALID		= -3;
	const SOCKET_ERR_NOT_CONN			= -4;
	const SOCKET_ERR_SET_BUFF_FAILED	= -5;
	const SOCKET_ERR_WRITE_FAILED		= -6;
	const SOCKET_ERR_READ_FAILED		= -7;
	const SOCKET_ERR_DATA_ERR			= -8;
	const SOCKET_ERR_CONN_FAIL			= -9;

	
	/**
	 * Socket数据流指针
	 */
	private $fp = null;

	/**
	 * 是否使用阻塞模式
	 */
	private $blocking = true;

	/**
	 * 是否使用长连接
	 */
	private $persistent = false;

	/**
	 * 需要访问的IP或者域名
	 */
	private $addr = '';

	/**
	 * 访问的端口号
	 */
	private $port = 0;

	/**
	 * 连接超时时间
	 */
	private $connTimeout = 30;

	/**
	 * 数据流 读取/写入 超时时间
	 */
	private $streamTimeout = 30;

	/**
	 * 缺省每次数据流读取的数据大小
	 */
	private $readSize = 8192;

	/**
	 * 缺省每次数据流写入的数据大小
	 */
	private $writeSize = 8192;


	/**
	 * 获取错误代码对应的错误信息
	 *
	 * @param int $code 错误代码
	 * @return string 返回找到的对应错误代码的错误信息
	 */
	private function getError($code){
		$errMessage = array(
			self::SOCKET_ERR_NO					=> 'Unknow error',
			self::SOCKET_ERR_ADDR_INVALID		=> 'IP address or domain-name invalid',
			self::SOCKET_ERR_PORT_INVALID		=> 'Port number invalid or not a number',
			self::SOCKET_ERR_NOT_CONN			=> 'Not availability socket connection',
			self::SOCKET_ERR_SET_BUFF_FAILED	=> 'Cannot set write buffer',
			self::SOCKET_ERR_WRITE_FAILED		=> 'Socket write data failed',
			self::SOCKET_ERR_READ_FAILED		=> 'Socket read data failed',
			self::SOCKET_ERR_DATA_ERR			=> 'Fetch data error',
			self::SOCKET_ERR_CONN_FAIL			=> 'Connect to host fail',
		);
		if (!array_key_exists($code, $errMessage)){
			return '';
		}
		return $errMessage[$code];
	}


	//----------------------------------
	//
	//      基础初始操作方法
	//
	//----------------------------------

	/**
	 * 构造函数
	 * 
	 * @param string $addr 需要访问的IP地址或者域名
	 * @param int $port 需要访问的对方服务器的端口号
	 * @param bool $persistent 是否采用长连接连接到服务器，缺省为否
	 * @param int $connTimeout 服务器连接超时时间，缺省为30秒
	 * @param int $streamTimeout 数据流访问读取/写入的超时时间，缺省为30秒
	 */
	public function __construct($addr, $port, $persistent=false, $connTimeout=null, $streamTimeout=null){
		if ($connTimeout!==null && is_int($connTimeout)){
			$this->connTimeout = $connTimeout;
		}
		if ($streamTimeout!==null && is_int($streamTimeout)){
			$this->streamTimeout = $streamTimeout;
		}
		$this->addr = $addr;
		$this->port = intval($port);
		$this->persistent = $persistent;
	}

	/**
	 * 连接到服务器
	 * 
	 * @param bool $blocking 是否采用阻塞的方式进行数据流通信
	 * @return mixed 成功返回true，失败返回一个错误对象
	 */
	public function connect($blocking = true){
		if (!preg_match("/^[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}$/",$this->addr) && !preg_match("/^[a-z0-9]([a-z0-9-]+\.){1,4}[a-z]{2,5}$/i", $this->addr)){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_ADDR_INVALID));
		}
		if (!is_int($this->port) || $this->port<0 || $this->port>65535){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_PORT_INVALID));
		}
		$errCode = -1;
		$errMessage = '';
		if ($this->persistent){
			$this->fp = @pfsockopen($this->addr, $this->port, $errCode, $errMessage, $this->connTimeout);
		}else{
			$this->fp = @fsockopen($this->addr, $this->port, $errCode, $errMessage, $this->connTimeout);
		}
		if (!$this->fp){
			throw new TM_Exception($errMessage);
		}
		return $this->setStreamBlocking($blocking);
	}

	/**
	 * 关闭Socket连接
	 *
	 * @return mixed 成功返回true，失败返回错误对象
	 */
	public function disconnect(){
		if (!$this->fp || !is_resource($this->fp)){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_NOT_CONN));
		}
		@fclose($this->fp);
		$this->fp = null;
		return true;
	}

	/**
	 * 检查是否有可用的连接
	 */
	public function isConnected(){
		if (!$this->fp || !is_resource($this->fp)){
			return false;
		}	
		return true;
	}


	//----------------------------------
	//
	//   Socket 配置/获取 方法
	//
	//----------------------------------

	/**
	 * 设置流的阻塞方式（是否阻塞）
	 *
	 * @param bool $mode 是否阻塞，是为true，否为false
	 * @return mixed 成功返回设置结果，失败返回错误对象
	 */
	public function setStreamBlocking($mode){
		if (!$this->fp || !is_resource($this->fp)){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_NOT_CONN));
		}
		$this->blocking = $mode;
		return stream_set_blocking($this->fp, $mode);
	}

	/**
	 * 获取当前阻塞设置
	 * 
	 * @return bool 目前阻塞设置
	 */
	public function getStreamBlocking(){
		return $this->blocking;
	}

	/**
	 * 设置流获取超时时间
	 *
	 * @param int $seconds 超时时间的秒
	 * @param int $microseconds 超时时间的毫秒
	 * @return mixed 设置成功返回结果，失败返回错误对象
	 */
	public function setStreamTimeout($seconds, $microseconds=0){
		if (!$this->fp || !is_resource($this->fp)){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_NOT_CONN));
		}
		return stream_set_timeout($this->fp, $seconds, $microseconds);
	}

	/**
	 * 设置写入缓存大小
	 * 
	 * @param int $size 获取设置写缓存的大小
	 * @return mixed 设置成功返回结果，失败返回错误对象
	 */
	public function setWriteBuffer($size){
		if (!$this->fp || !is_resource($this->fp)){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_NOT_CONN));
		}
		if ($set = stream_set_write_buffer($this->fp, $size) == 0){
			return true;
		}
		throw new TM_Exception($this->getError(self::SOCKET_ERR_SET_BUFF_FAILED));
	}

	/**
	 * 获取当前Socket的状态
	 *
	 * @return mixed 设置成功返回结果，失败返回错误对象
	 */
	public function getSocketStatus(){
		if (!$this->fp || !is_resource($this->fp)){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_NOT_CONN));
		}
		return socket_get_status($this->fp);
	}

	/**
	 * 获取当前是否达到了流的尾部
	 *
	 * @return mixed 设置成功返回结果，失败返回错误对象
	 */
	public function isEof(){
		return  (!is_resource($this->fp) || feof($this->fp));
	}



	//----------------------------------
	//
	//     数据流 读取/写入 方法
	//
	//----------------------------------
	
	/**
	 * 写入数据到Socket流
	 *
	 * @param string $data 需要写入的数据
	 * @param int $blockSize 每次写入流的大小，可以不设置，默认为一次性写入
	 * @return mixed 设置成功返回写入成功的字节数，失败返回错误对象
	 */
	public function write($data, $blockSize = null){
		if (!$this->fp || !is_resource($this->fp)){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_NOT_CONN));
		}

		//没有设定块或者是windows系统直接写数据
		if (is_null($blockSize) && substr(PHP_OS, 0, 3)!='WIN'){
			if (($writeLen = @fwrite($this->fp, $data)) === false){
				throw new TM_Exception($this->getError(self::SOCKET_ERR_WRITE_FAILED));
			}
			return $writeLen;
		}
		
		//分块写入数据
		if (is_null($blockSize)){
			$blockSize = $this->writeSize;
		}
		$position = 0;
		$maxSize = strlen($data);

		while($position < $maxSize){
			$writeLen = @fwrite($this->fp, substr($data, $position, $blockSize));
			if ($writeLen === false){
				throw new TM_Exception($this->getError(self::SOCKET_ERR_WRITE_FAILED));
			}
			$position += $writeLen;
		}
		return $position;
	}

	/**
	 * writeAll是write的别名
	 */
	public function writeAll($data, $blockSize = null){
		return $this->write($data, $blockSize);
	}

	/**
	 * 写入一行数据到Socket流，结尾加上\r\n
	 * 
	 * @param string $data 需要写入的数据
	 * @return mixed 设置成功返回写入成功的字节数量，失败返回错误对象
	 */
	public function writeLine($data){
		if (!$this->fp || !is_resource($this->fp)){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_NOT_CONN));
		}
		if (($writeLen = @fwrite($this->fp, $data."\r\n")) === false){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_WRITE_FAILED));
		}
		return $writeLen;
	}
	
	/**
	 * 读取指定字节数量的数据流内容
	 *
	 * @param int $size 需要读取内容的字节数量，如果没有设置则使用缺省值
	 * @return mixed 设置成功返回数据内容，失败返回错误对象
	 */
	public function read($size = null){
		if (!$this->fp || !is_resource($this->fp)){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_NOT_CONN));
		}
		if (is_null($size) || !is_int($size)){
			$size = $this->readSize;
		}
		if (($data = @fread($this->fp, $size)) === false){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_READ_FAILED));
		}
		return $data;
	}

	/**
	 * 读取所有的数据内容
	 * 
	 * @param int $size 希望每次读取的数据长度
	 * @return mixed 设置成功返回读取的数据内容，失败返回错误对象
	 */
	public function readAll($size = null){
		if (!$this->fp || !is_resource($this->fp)){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_NOT_CONN));
		}
		if (is_null($size)){
			$size = $this->readSize;
		}
		while(!feof($this->fp)){
			if (($read = @fread($this->fp, $size)) === false){
				throw new TM_Exception($this->getError(self::SOCKET_ERR_READ_FAILED));
			}			
			$data .= $read;
		}
		return $data;
	}

	/**
	 * 读取一行数据信息（以\r\n结尾为一行，必须读取到一行）
	 * 
	 * @return mixed 设置成功返回读取结果，失败返回错误对象
	 */
	public function readLine(){
		if (!$this->fp || !is_resource($this->fp)){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_NOT_CONN));
		}
		
		$dataLine = '';
		$timeout = time() + $this->streamTimeout;
		if (!feof($this->fp) && time()<$timeout){
			if (false === ($line = @fgets($this->fp, $this->readSize))){
				throw new TM_Exception($this->getError(self::SOCKET_ERR_READ_FAILED));
			}
			$dataLine .= $line;
			/*if (substr($dataLine, -1) == "\n"){
				return rtrim($dataLine, "\r\n");
			}*/
		}
		return $dataLine;
	}

	/**
	 * 读取一行信息（如果超过了指定长度，则直接返回已读取数据）
	 * 
	 * @param int $size 如果不足一行则希望读取字节数，缺省为按照顾属性设置
	 * @return mixed 设置成功返回读取的数据内容，失败返回错误对象
	 */
	public function gets($size = null){
		if (!$this->fp || !is_resource($this->fp)){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_NOT_CONN));
		}
		if (is_null($size) || !is_int($size)){
			$size = $this->readSize;
		}
		if (($data = @fgets($this->fp, $size)) === false){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_READ_FAILED));
		}
		return $data;		
	}

	/**
	 * 读取一个以\0结尾的字符串
	 * 
	 * @return mixed 设置成功返回获取的数据内容，失败返回错误对象
	 */
	public function readString(){
		if (!$this->fp || !is_resource($this->fp)){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_NOT_CONN));
		}
		$string = '';
		while($char=@fgetc($this->fp) != "\0"){
			$string .= $char;
		}
		return $string;
	}

	/**
	 * 读取一个字节的数据
	 *
	 * @return mixed 设置成功返回一个字节数据内容，失败返回错误对象
	 */
	public function readByte(){
		if (!$this->fp || !is_resource($this->fp)){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_NOT_CONN));
		}
		return @fgetc($this->fp);
	}

	/**
	 * 读取一个整形二进制数据（4个字节）
	 *
	 * @return mixed 设置成功返回整形数字，失败返回错误对象
	 */
	public function readInt(){
		if (!$this->fp || !is_resource($this->fp)){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_NOT_CONN));
		}
		if (($read = @fread($this->fp, 4)) === false){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_READ_FAILED));
		}
		if (strlen($read) != 4){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_DATA_ERR));
		}
		return array_pop(unpack('I', $read));
	}

	/**
	 * 读取一个格式化的IP地址数据
	 *
	 * @return mixed 读取成功返回ip地址，失败返回错误对象
	 */
    public function readIPAddress(){
		if (!$this->fp || !is_resource($this->fp)){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_NOT_CONN));
		}
		if (($read = @fread($this->fp, 4)) === false){
			throw new TM_Exception($this->getError(self::SOCKET_ERR_READ_FAILED));
		}
        return sprintf("%s.%s.%s.%s", ord($read[0]), ord($read[1]), ord($read[2]), ord($read[3]));
    }

}


