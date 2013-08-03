<?php
/*******************************************
 *  描述: 异常和错误对象基础类
 *  作者: heiyeluren
 *  创建: 2009/12/1 22:01
 *  修改: 2009/12/1 22:08
 *******************************************/

/**
 * PHP 5 基础类描述
class Exception
{
    protected $message = 'Unknown exception';   // 异常信息
    protected $code = 0;                        // 用户自定义异常代码
    protected $file;                            // 发生异常的文件名
    protected $line;                            // 发生异常的代码行号

    function __construct($message = null, $code = 0);

    final function getMessage();                // 返回异常信息
    final function getCode();                   // 返回异常代码
    final function getFile();                   // 返回发生异常的文件名
    final function getLine();                   // 返回发生异常的代码行号
    final function getTrace();                  // backtrace() 数组
    final function getTraceAsString();          // 已格成化成字符串的 getTrace() 信息

    //可重载的方法
    function __toString();                       // 可输出的字符串
}
*/



/**
 * TMPHP 框架异常基础类
 *
 */
class TM_Exception extends Exception
{  
    /**
	 * 异常类构造函数
	 *
	 * @param string $message 错误消息
	 * @param int $code 错误代码
	 * @return void
	 */
    public function __construct($message, $code = 0) {
		$message = "TMPHP Exception: ". $message;
        parent::__construct($message, $code);
    }

    /**
	 * 自定义输出格式
	 *
	 * @param void
	 * @return string
	 */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }


	/**
	 * 跳转函数
	 *
	 * @param string $url 需要跳转的目标URL
	 * @param string $msg 如果需要在页面里提示消息
	 * @return unknown
	 */
	public function redirect($url = '/', $msg = ''){
		if (headers_sent()){
			return $this->go($url, $msg);
		}
		header("Location: $url");
		exit;
	}

	/**
	 * HTML跳转
	 *
	 * @param string $path 需要跳转到的目标URL
	 * @return bool
	 */
	public function go($url = '', $msg = ''){
		$html =<<<EOT
<html>
<head>
<script type="text/javascript">
<!--
var url = "{$url}";
var msg = "{$msg}";
if (msg != '') alert(msg);
if (url == '') window.history.back();
else window.location.href = url;
-->
</script>
</head>
<body></body>
</html>
EOT;
		echo $html;
		exit;
	}


}



