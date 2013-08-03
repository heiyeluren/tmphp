<?php
/*******************************************
 *  描述：XML操作基础类
 *  作者：heiyeluren
 *  创建：2007-04-06 10:45
 *  修改：2007-04-11 19:57  基本功能实现
 *		  2009-12-16 10:39  更新异常处理机制
 *		  2009-12-19 23:01  增加了SimpleXML,DOM XML支持，修改为PHP5方式
 *
 *******************************************/


/**
 * XML 功能工厂方法类
 *
 * 调用示例代码：
	try {
		$s = TM_XML::factory(TM_XML::TYPE_SIMPLE_XML);
		//$s = TM_XML::factory(TM_XML::TYPE_XML_PARSER);
		//$s = TM_XML::factory(TM_XML::TYPE_DOM_XML);		
		$result = $s->parseFile('http://www.w3school.com.cn/example/xmle/note.xml');
		var_dump($result);
		$result = $s->getResult();
		var_dump($result);
		$str_xml = $s->getXml();
		var_dump($str_xml);
		$result = $s->parseString($str_xml);
		var_dump($result);
		
	} catch (Exception $e) {
		echo $e->getMessage();
	}
 */
class TM_XML
{
	/**
	 * @var XML处理类型是SimpleXML
	 */
	const TYPE_SIMPLE_XML		= 1;
	/**
	 * @var XML处理类型是XMLParser
	 */
	const TYPE_XML_PARSER		= 2;
	/**
	 * @var XML处理类型为DOM XML
	 */
	const TYPE_DOM_XML			= 3;
	
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
	 * @param int $type 需要使用的XML处理方式
	 * @return object 返回初始化的XML对象，不同的处理类，返回的处理对象不同，要分开独立处理，同时请参考PHP手册来操作响应的操作类
	 */
	public static function factory($type = self::TYPE_SIMPLE_XML){
		if ($type == ''){
			$type = self::TYPE_SIMPLE_XML;
		}
		switch($type) {
			case self::TYPE_SIMPLE_XML :
				if (!function_exists('simplexml_load_file')){
					throw new Exception(__CLASS__ . " PHP SimpleXML extension not install");
				}
				$obj = TM_SimpleXML::getInstance();
				break;
			case self::TYPE_XML_PARSER :
				if (!function_exists('xml_parser_create')){
					throw new Exception(__CLASS__ . " PHP XMLParser extension not install");
				}				
				$obj = TM_XMLParser::getInstance();
				break;
			case self::TYPE_DOM_XML :
				if (!class_exists('DOMDocument')){
					throw new Exception(__CLASS__ . " PHP DOM XML extension not install");
				}				
				$obj = TM_DOM_XML::getInstance();
				break;
			default:
				throw new Exception(__CLASS__ .": XML parse $type not support");
		}
		return $obj;
	}	
	

}



/**
 * 调用PHP5的 SimpleXML 进行XML处理
 * 
 * 描述：解析处理的结果返回对象，必须参考 SimpleXML 相关类和方法来操作最后结果，相关操作方法参考PHP手册
 */
class TM_SimpleXML
{
	/**
	 * @var 解析结果集对象
	 */
	public $result = NULL;
	
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
	public static function getInstance(){
		if (!(self::$_instance instanceof self)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}		

	/**
	 * 构造函数
	 */
	public function __construct(){}

	/**
	 * 进行解析XML文件（可以是远程或者是本地文件）
	 * 
	 * @param string $fileName 本地文件名称或者是URL
	 * @return mixed 如果有返回则是错误对象，成功无返回
	 */
	public function parseFile( $fileName ){
		if (!preg_match('/^http/', $fileName)){
			if (!is_file($fileName) || !is_readable($fileName)){
				throw new Exception(__CLASS__ .": XML file $fileName not exist or not readable");
			}			
		}
		if (($this->result = simplexml_load_file($fileName)) === false){
			throw new Exception(__CLASS__ .": SimpleXML parse file error");
		}
		return $this->result;
	}

	/**
	 * 对XML字符串进行解析
	 *
	 * @param string $data 需要进行解析的XML字符串
	 * @return mixed 如果有返回则是错误对象，成功无返回
	 */
	public function parseString( $data ){
		if (trim($data) == ""){
			throw new Exception(__CLASS__ .": XML content is empty");
		}
		if (($this->result = simplexml_load_string($data)) === false){
			throw new Exception(__CLASS__ .": SimpleXML parse string error");
		}
		return $this->result;
	}

	/**
	 * 返回解析结果
	 */
	public function getResult(){
		return $this->result;
	}

	/**
	 * 返回XML数据
	 */
	public function getXml(){
		return $this->result->asXML();
	}

}





/**
 * 调用PHP5的 DOM XML 进行XML处理
 * 
 * 描述：解析处理的结果返回对象，必须参考 DOM XML 相关类和方法来操作最后结果，相关操作方法参考PHP手册
 */
class TM_DOM_XML
{
	/**
	 * @var 解析结果集对象
	 */
	public $result = NULL;
	
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
	public static function getInstance(){
		if (!(self::$_instance instanceof self)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}		

	/**
	 * 构造函数
	 */
	public function __construct(){}

	/**
	 * 进行解析XML文件（可以是远程或者是本地文件）
	 * 
	 * @param string $fileName 本地文件名称或者是URL
	 * @return mixed 如果有返回则是错误对象，成功无返回
	 */
	public function parseFile( $fileName ){
		if (!preg_match('/^http/', $fileName)){
			if (!is_file($fileName) || !is_readable($fileName)){
				throw new Exception(__CLASS__ .": XML file $fileName not exist or not readable");
			}			
		}
		$doc = new DOMDocument();
		if ($doc->load($fileName) === false){
			throw new Exception(__CLASS__ .": DOM XML load xml file error");
		}
		$this->result = $doc;
		return $this->result;
	}

	/**
	 * 对XML字符串进行解析
	 *
	 * @param string $data 需要进行解析的XML字符串
	 * @return mixed 如果有返回则是错误对象，成功无返回
	 */
	public function parseString( $data ){
		if (trim($data) == ""){
			throw new Exception(__CLASS__ .": XML content is empty");
		}
		$doc = new DOMDocument();
		if ($doc->loadXML($data) === false){
			throw new Exception(__CLASS__ .": DOM XML load xml string error");
		}
		$this->result = $doc;
		return $this->result;
	}

	/**
	 * 返回解析结果
	 */
	public function getResult(){
		return $this->result;
	}

	/**
	 * 返回XML数据
	 */
	public function getXml(){
		return $this->result->saveXML();
	}
	
	
	/**
	 * DOM XML 类特有函数：把节点数据返回一个数组
	 *
	 * @param object $domnode 一个需要转换成为数组的DOM Node
	 * @return array 返回最后处理的结果数组
	 */
	public function xml2array($domnode){
	    $nodearray = array();
	    $domnode = $domnode->firstChild;
	    while (!is_null($domnode))   {
	        $currentnode = $domnode->nodeName;
	        switch ($domnode->nodeType)  {
	            case XML_TEXT_NODE:
	                if(!(trim($domnode->nodeValue) == "")) $nodearray['cdata'] = $domnode->nodeValue;
	            break;
	            case XML_ELEMENT_NODE:
	                if ($domnode->hasAttributes() ) {
	                    $elementarray = array();
	                    $attributes = $domnode->attributes;
	                    foreach ($attributes as $index => $domobj) {
	                        $elementarray[$domobj->name] = $domobj->value;
	                    }
	                }
	            break;
	        }
	        if ( $domnode->hasChildNodes() ) {
	            $nodearray[$currentnode][] = $this->xml2array($domnode);
	            if (isset($elementarray)) {
	                $currnodeindex = count($nodearray[$currentnode]) - 1;
	                $nodearray[$currentnode][$currnodeindex]['@'] = $elementarray;
	            }
	        } else {
	            if (isset($elementarray) && $domnode->nodeType != XML_TEXT_NODE) {
	                $nodearray[$currentnode]['@'] = $elementarray;
	            }
	        }
	        $domnode = $domnode->nextSibling;
	    }
	    return $nodearray;
	}	

}




/**
 * 使用PHP5 的 XMLParser 进行XML解析
 * 
 * 描述: 解析后的结果保存在对象中，可以返回对象获取解析结果，具体数据操作直接访问对象相关属性来操作
 */
class TM_XMLParser
{
	private $path;
	private $result;
	private $index = 0;
	private $parser = null;
	private $xml = '';
	
	
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
	public static function getInstance(){
		if (!(self::$_instance instanceof self)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}		
	

	/**
	 * 构造函数
	 */
	public function __construct(){
		$this->path = "\$this->result";
	}

	/**
	 * 进行解析XML文件（可以是远程或者是本地文件）
	 * 
	 * @param string $fileName 本地文件名称或者是URL
	 * @return mixed 如果有返回则是错误对象，成功无返回
	 */
	public function parseFile( $fileName ){
		if (!preg_match('/^http/', $fileName)){
			if (!is_file($fileName) || !is_readable($fileName)){
				throw new Exception(__CLASS__ .": XML file $fileName not exist or not readable");
			}			
		}
		if (trim($data = file_get_contents($fileName)) == ''){
			throw new Exception(__CLASS__ .": XML file content is empty");
		}
		return $this->parseString($data);
	}

	/**
	 * 对XML字符串进行解析
	 *
	 * @param string $data 需要进行解析的XML字符串
	 * @return mixed 如果有返回则是错误对象，成功无返回
	 */
	public function parseString( $data ){
		if (!function_exists('xml_parser_create')){
			throw new Exception(__CLASS__ .": XML parser lib not install");
		}
		if (trim($data) == ""){
			throw new Exception(__CLASS__ .": XML content is empty");
		}

		$this->xml = $data;
		$this->parser = xml_parser_create();
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, 'startElement', 'endElement');
		xml_set_character_data_handler($this->parser, 'characterData');
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);

		xml_parse($this->parser, $data, true);
		xml_parser_free($this->parser);		
		
		return $this->result;		
	}

	/**
	 * 返回解析结果
	 */
	public function getResult(){
		return $this->retusl;
	}
	
	/**
	 * 返回XML数据
	 */
	public function getXml(){
		return $this->xml;
	}
	
		
	//--------------------------
	//    类内部调用方法
	//--------------------------

	/**
	 * 回调函数开始节点
	 */
	private function startElement( $parser, $tag, $attributeList ){
		$this->path .= "->".$tag;
		eval("\$data = ".$this->path.";");
		if (is_array($data)){
			$index = sizeof($data);
			$this->path .= "[".$index."]";
		} else if (is_object($data)){ 
			eval($this->path." = array(".$this->path.");");
			$this->path .= "[1]";
		}

		foreach($attributeList as $name => $value){
			eval($this->path."->".$name. " = '".self::cleanString($value)."';");
		}
	}

	/**
	 * 回调函数结束节点
	 */
	private function endElement( $parser, $tag ){
		$this->path = substr($this->path, 0, strrpos($this->path, "->"));
	}

	/**
	 * 回调函数基础数据
	 */
	private function characterData( $parser, $data ){
		if ($data = self::cleanString($data)){
			eval("$this->path .= \$data;");
		}
	}

	/**
	 * 替换xml中的特殊数据
	 */
	public static function cleanString( $string ){
		return trim(str_replace("'", "&#39;", $string)); 
	}
	
}

