<?php
/*************************************************
 *  描述: TMPHP Input Filter 类
 *  作者: heiyeluren 
 *  创建: 2008-09-02 19:05
 *  修改: 2008-09-02 21:22	基本功能实现，包括POST/GET数据操作
 *		  2009/12/13 03:23	屏蔽了GET/POST操作，增加了部分过滤函数
 *
 ********************************/



/**
 * 数据校验检查过滤类
 *
 * @desc 包含基本的数据完整性、正确性、安全性、合法性检查的函数接口，一般提供直接调用
 */
class TM_InputFilter
{
	/**
	 * 数据来源类型
	 */
	/*const IV_POST				= 1;
	const IV_GET				= 2;
	const IV_REQUES				= 3;
	const IV_ENV				= 4;
	const IV_SERVER				= 5;
	*/

	/**
	 * 数据过滤类型
	 */
	const FILTER_UNSAFE_RAW		= 1;
	const FILTER_STRIPPED		= 2;
	const FILTER_COOKED			= 3;
	const FILTER_HTML			= 4;
	const FILTER_EMAIL			= 5;
	const FILTER_URL			= 6;
	const FILTER_NUMBER			= 7;
	const FILTER_TEXT			= 8;

	/**
	 * HTML处理类型
	 */
	const HTML_NO_TAGS			= 1;
	const HTML_SHOW_TAGS		= 2;
	const HTML_LITTLE_TAGS		= 3;
	const HTML_MOSTLY_TAGS		= 4;
	const HTML_TEXT_TAGS		= 5;



	/**
	 * 通用数据获取函数(能够调用缺省函数)
	 *
	 * @param int $arrData 需要获取的变量数组
	 * @param string $varName 变量名，需要获取的变量名称
	 * @param int $filter 需要调用的过滤器，缺省是 getStripped，参考相应的类常量，
	 *					  注意：如果设定是 UNSAFE，则会返回原始数据，这是很危险的
	 * @return mixed 返回处理后的结果
	 */
	function getData(&$arrData, $varName = '', $filter = ''){
		$var = '';
		if ($varName == ''){
			return $arrData;
		}
		if(!isset($arrData[$varName])){
			return '';
		}
		$var = $arrData[$varName];
		if (!$filter){
			switch($filter){
				case self::FILTER_UNSAFE_RAW: return $var;
				case self::FILTER_STRIPPED: return self::getStripped($var);
				case self::FILTER_COOKED: return self::getCooked($var);
				case self::FILTER_HTML: return self::getHtml($var);
				case self::FILTER_EMAIL: return self::getEmail($var);
				case self::FILTER_URL: return self::getUrl($var);
				case self::FILTER_NUMBER: return self::getNumber($var);
				case self::FILTER_TEXT: return self::filterText($var);
				default: return self::getStripped($var);
			}
		}
		return self::getStripped($var);		
	}

	/**
	 * 获取HTML过滤
	 *
	 * @param string $str 需要过滤的字符串
	 * @param int $htmlType 过滤的级别和类型，参考相应的类常量，缺省为过滤所有标记
	 * @return string 返回过滤的后的结果
	 */
	function getHtml($str, $htmlType = self::HTML_NO_TAGS){
		if (is_array($str) || is_object($str)){
			return $str;
		}
		switch($htmlType){
			//剔除所有HTML
			case self::HTML_NO_TAGS:
				$str = self::stripHtmlTag(self::filterScript($str), true);
				break;
			//把HTML转换为可显示
			case self::HTML_SHOW_TAGS:
				$str = self::filterHtmlWord($str);
				break;
			//保存部分危害性小的HTML标签
			case self::HTML_LITTLE_TAGS:
				$str = strip_tags(self::filterScript($str), '<h1><h2><h3><h4><h5><h6><strong><code><b><i><tt><sub><sup><big><small><hr><br><font>');
				break;
			//保存大部分HTML标签
			case self::HTML_MOSTLY_TAGS:
				$str = strip_tags(self::filterScript($str), '<p><h1><h2><h3><h4><h5><h6><strong><em><abbr><acronym><address><bdo><blockquote><cite><q><code><ins><del><dfn><kbd><pre><samp><var><br><a><base><img><area><map><ul><ol><li><dl><dt><dd><table><tr><td><th><tbody><thead><tfoot><col><colgroup><caption><b><i><tt><sub><sup><big><small><hr><div><span>');
				break;
			//保留所有HTML标签(除了script,iframe,object)
			case self::HTML_TEXT_TAGS:
				$str = self::escapeScript($str);
				break;	
			default:
				$str = self::stripHtmlTag(self::filterScript($str), true);
		}
		return $str;
	}


	/**
	 * 替换所有的 <,>,',",& 为HTML实体
	 *
	 * @param string $str 需要过滤的字符串
	 * @return string 返回过滤的后的结果
	 */
	function getHtmlFull($str){
		if (is_array($str) || is_object($str)){
			return $str;
		}
		return self::filterHtmlWord($str);
	}

	/**
	 * 对字符串进行严格的剔除操作(会剔除所有HTML，ASC码小于7的控制字符，SQL注入字符转义)
	 *
	 * @param string $str 需要剔除的原始串
	 * @reutrn string 返回剔除后的串
	 */
	function getStripped($str){
		if (is_array($str) || is_object($str)){
			return $str;
		}
		return self::filterSql(self::getHtml(preg_replace('/([\x00-\x07])/', "", $str), self::HTML_NO_TAGS));
	}

	/**
	 * 对字符串进行严格的转换操作(会转换所有HTML为能显示的，ASC码小于7的控制字符转换为空格)
	 *
	 * @param string $str 需要转换的原始串
	 * @reutrn string 返回转换后的串
	 */
	function getCooked(){
		if (is_array($str) || is_object($str)){
			return $str;
		}
		return self::getHtml(preg_replace("/([\x00-\x07])/", "&nbsp;", $str), self::HTML_SHOW_TAGS);
	}

	/**
	 * 处理Email地址
	 *
	 * @param string $str 需要处理的原始串
	 * @param bool $strict 是否采取严格方式，如果是，那么Email地址不合法则会返回空
	 * @return 处理后的串
	 */
	function getEmail($str, $strict = false){
		if (is_array($str) || is_object($str)){
			return $str;
		}
		if ($strict){
			if (!preg_match("/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/", $str)){
				return '';
			}
			return $str;
		}
		return preg_replace("/(^[a-zA-Z0-9\.@_\-])/", "", $str);
	}

	/**
	 * 处理URL地址
	 *
	 * @param string $str URL地址串
	 * @return string 如果不是合法的URL，将返回空字符串
	 */
	function getUrl($str){
		if (is_array($str) || is_object($str)){
			return $str;
		}
		if (!preg_match("/^http:\/\/[\w]+\.[\w]+[\S]*/", $str)){
			return '';
		}
		return $str;
	}

	/**
	 * 处理数字
	 *
	 * @param string $str 要处理的数字串
	 * @return string 将会保留数字，科学计数法相应的字符，其他都会被剔除
	 */
	function getNumber($str){
		if (is_array($str) || is_object($str)){
			return $str;
		}
		if (is_numeric($str)){
			return $str;
		}
		return preg_replace("/(^[0-9\.+E])/", "", $str);
	}

	/**
	 * 针对大段文本进行过滤
	 *
	 */	
	public static function filterText($str) {
		//filter base
		$str = trim ( $str );
		$str = preg_replace ( '/[\a\f\n\e\0\r\t\x0B]/is', "", $str );
		$str = htmlspecialchars ( $str, ENT_QUOTES );

		//filterTag
		$str = str_ireplace ( "javascript", "j&#097;v&#097;script", $str );
		$str = str_ireplace ( "alert", "&#097;lert", $str );
		$str = str_ireplace ( "about:", "&#097;bout:", $str );
		$str = str_ireplace ( "onmouseover", "&#111;nmouseover", $str );
		$str = str_ireplace ( "onclick", "&#111;nclick", $str );
		$str = str_ireplace ( "onload", "&#111;nload", $str );
		$str = str_ireplace ( "onsubmit", "&#111;nsubmit", $str );
		$str = str_ireplace ( "<script", "&#60;script", $str );
		$str = str_ireplace ( "onerror", "&#111;nerror", $str );
		$str = str_ireplace ( "document.", "&#100;ocument.", $str );

		//filterCommon
		$str = str_replace ( "&#032;", " ", $str );
		$str = preg_replace ( "/\\\$/", "&#036;", $str );
		$str = stripslashes ( $str );

		return $str;
	}






	//----------------------------
	//
	//   标准数据过滤方法
	//
	//----------------------------

	/**
	 * 剔除slashes操作
	 */
	public static function stripslashes($value){
		if (!get_magic_quotes_gpc()){
			return $value;
		}
		return is_array ( $value ) ? array_map ( 'stripslashes', $value ) : stripslashes ( $value );
	}

	/**
	 * 剔除非打印字符（包括控制字符）
	 */
	public static function filterSpace($value){
		return preg_replace ( '/[\a\f\n\e\0\r\t\x0B]/is', "", $value);
	}

	/**
	 * 过滤特殊字符
	 */
	public static function filterSpecialWord($value){
		return preg_replace('/>|<|,|\[|\]|\{|\}|\?|\/|\+|=|\||\'|\\|\"|:|;|\~|\!|\@|\#|\*|\$|\%|\^|\&|\(|\)|`/i', "", $value);
	}

	/**
	 * 过滤SQL注入攻击字符串
	 *
	 * @param string $str 需要过滤的字符串
	 * @param resource $db 数据库连接，可以为空
	 * @return string
	 */
	public static function filterSql($str, $db = null){
		if (!get_magic_quotes_gpc()){
			if ($db){
				return mysql_real_escape_string($str, $db);
			} 
			return function_exists('mysql_escape_string') ? mysql_escape_string($str) : addslashes($str);
		}
		return $str;		
	}

	/**
	 * 过滤HTML标签
	 *
	 * @param string text - 传递进去的文本内容
	 * @param bool $strict - 是否严格过滤（严格过滤将把所有已知HTML标签开头的内容过滤掉）
	 * @return string 返回替换后的结果
	 */
	public static function stripHtmlTag($text, $strict=false){
		$text = strip_tags($text);
		if (!$strict){
			return $text;
		}
		$html_tag = "/<[\/|!]?(html|head|body|div|span|DOCTYPE|title|link|meta|style|p|h1|h2|h3|h4|h5|h6|strong|em|abbr|acronym|address|bdo|blockquote|cite|q|code|ins|del|dfn|kbd|pre|samp|var|br|a|base|img|area|map|object|param|ul|ol|li|dl|dt|dd|table|tr|td|th|tbody|thead|tfoot|col|colgroup|caption|form|input|textarea|select|option|optgroup|button|label|fieldset|legend|script|noscript|b|i|tt|sub|sup|big|small|hr)[^>]*>/is";
		return preg_replace($html_tag, "", $text);
	}

	/**
	 * 转换HTML的专有字符
	 */
	 public static function filterHtmlWord($text){
		if (function_exists('htmlspecialchars')){
			return htmlspecialchars($text);
		}
		$search = array("&", '"', "'", "<", ">");
		$replace = array("&amp;", "&quot;", "&#039;", "&lt;", "&gt;");
		return str_replace($search, $replace, $text);
	 }

	 /**
	  * 剔除JavaScript、CSS、Object、Iframe
	  */
	 public static function filterScript($text){
		$text = preg_replace("/(javascript:)?on(click|load|key|mouse|error|abort|move|unload|change|dblclick|move|reset|resize|submit)/i","&111n\\2",$text);
		$text = preg_replace ("/<style.+<\/style>/iesU", '', $text);
		$text = preg_replace ("/<script.+<\/script>/iesU", '', $text);
		$text = preg_replace ("/<iframe.+<\/iframe>/iesU", '', $text);
		$text = preg_replace ("/<object.+<\/object>/iesU", '', $text);
		return $text;
	 }

	/**
	 * 过滤JAVASCRIPT不安全情况
	 */
	public static function escapeScript($string){
		$string = preg_replace("/(javascript:)?on(click|load|key|mouse|error|abort|move|unload|change|dblclick|move|reset|resize|submit)/i","&111n\\2",$string);
		$string = preg_replace("/<script(.*?)>(.*?)<\/script>/si","",$string);
		$string = preg_replace("/<iframe(.*?)>(.*?)<\/iframe>/si","",$string);
		$string = preg_replace ("/<object.+<\/object>/iesU", '', $string);
		return $string;
	}

	/**
	 * 过滤一个IP地址
	 *
	 */
	public static function filterIp($key){
		$key = preg_replace("/[^0-9.]/", "", $key);
		return preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $key) ? $key : "0.0.0.0";
	}




}

