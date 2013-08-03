<?php
/**************************************************
 *  描述: TMPHP 核心控制器类
 *  作者: heiyeluren 
 *  创建: 2009/12/1 23:21
 *  修改: 2009/12/08 23:53  创建缺省包含基本操作
 *		  2009-12-20 23:38	修改控制器类bug
 *		  2009-12-22 00:05  修改部分bug，增加跳转函数
 *
 **************************************************/


/**
 * 控制器核心操作类
 * 
 * 描述: 
 * 本类主要是提供给控制器继承，包含各种基本操作方法
 *
 */
class TM_Controller
{
	/**
	 * @var 存储PHP模板视图情况下的模板变量设置
	 */
	public $vars = array ();
	/**
	 * @var 控制器名
	 */
	public $controllerName;
	/**
	 * @var Action名
	 */	
	public $actionName;
	
	/**
	 * @var 配置文件数组
	 */	
	public $config;

	/**
	 * @var Request 对象
	 */
	public $request = NULL;
	/**
	 * @var Response 对象
	 */	
	public $response = NULL;

	/**
	 * @var 视图类型选择，缺省为原生PHP
	 */	
	public $viewType = TM_View::TYPE_PHP;


	/**
	 * 控制器构造函数
	 *
	 * @param array $config 配置数据数组
	 * @param string $controllerName 控制器名
	 * @param string $actionName Aciton名
	 */
	public function __construct($config, $controllerName, $actionName){
		if (empty($config)){
			throw new TM_Exception("TM_Controller: config is empty");
		}
		$this->config = $config;
		$this->controllerName = $controllerName;
		$this->actionName = $actionName;

		$this->request =  TM_Request::getInstance();
		$this->response = TM_Response::getInstance();
	}

	/**
	 * __set
	 *
	 * @param string $key   设置对象属性指定的Key
	 * @param mixed $value  设置对象属性指定Key => Value
	 * @return void
	 */
	public function __set($key, $value) {
		$this->vars [$key] = $value;
	}
	
	/**
	 * __get
	 *
	 * @param  $key   需要读取对象属性指定
	 * @return mixed 返回属性值
	 */
	public function __get($key) {
		return $this->vars[$key];
	}

	/**
	 * 获取 Request 对象
	 *
	 * @return object
	 */
	public function request(){
		return $this->request;
	}

	/**
	 * 获取 Response 对象
	 *
	 * @return object
	 */	
	public function response(){
		return $this->response;
	}


	/**
	 * 设置视图显示类型 (目前支持：php, smarty)
	 *
	 * @param string $viewType 支持的视图类型，必须 TM_View 支持
	 * @return void
	 */
	public function setViewType($viewType = ''){
		if ($viewType != ''){
			$this->viewType = $viewType;
		}
	}

	/**
	 * 查看目前支持的视图类型
	 *
	 * @param void
	 * @return string 返回目前设置的视图类型
	 */
	public function getViewType(){
		return $this->viewType;
	}


	/**
	 * 解析读取一个模板文件
	 *
	 * @param string $path View文件路径
	 * @param array $vars 模板变量数组，可以为空 则调用当前对象的 $this->vars 中的变量
	 * @param array $params 其他需要增加的参数值，比如Smarty中的设置
	 * 
	 * @return the response content
	 */
	public function render($path = '', $vars = array(), $params = array()) {
		try {
			//设置编码
			$this->response->setHeader( "Content-type: text/html;charset=".$this->config['Common']['CharSet'] );

			//如果是没有模板的输出
			if ($path == ''){
				$this->response->send();
				return true;
			}

			//开启页面压缩
			ob_start();

			//读取View文件
			$vars = empty($vars) ? $this->vars : $vars;
			$view = TM_View::factory($this, $this->getViewType(), $params);
			$view->renderFile($path, $vars);

			//输出最后结果
			$this->response->setContent(ob_get_clean());
			$this->response->send();

			return true;

		} catch (TM_Exception $exception){
			throw $exception;
		}

	}

	/**
	 * 访问GET/POST参数
	 *
	 * @param string $key 需要访问的变量名
	 * @param string $default 缺省值
	 * @return unknown
	 */
	public function getParam($key){
		return $this->request->getParam($key);
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


