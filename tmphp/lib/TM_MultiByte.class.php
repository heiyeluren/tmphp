<?php
/*************************************************
 *  描述：多字节字符串处理类
 *  作者：heiyeluren
 *  创建：2007-04-03 12:06
 *  修改：2007-04-09 17:14  基本函数功能实现
 *		  2009/12/13 4:40   升级为PHP5支持方式
 *
 *************************************************/



/**
 * 多字节字符串处理类（GBK/GB2312，UTF8/Unicode）
 *
 * 包含多字节字符串处理的基本函数，方便多字节的操作
 */
class TM_MultiByte
{
	/**
	 * 判断内容里有没有中文(GBK)
	 */
	public static function GBIsChinese($s){
		return preg_match('/[\x80-\xff]./', $s);
	}

	/**
	 * 获取字符串长度(GBK)
	 */
	public static function GBStrlen($str){
		$count = 0;
		for($i=0; $i<strlen($str); $i++){
			$s = substr($str, $i, 1);
			if (preg_match("/[\x80-\xff]/", $s)) ++$i;
			++$count;
		}
		return $count;
	}

	/**
	 * 截取字符串子串(GBK)
	 *
	 * @param string $str 原始字符串
	 * @param int $len 需要截取字符串的长度
	 * @return string 返回截取到的字符串
	 */
	public static function GBSubstr($str, $len){
		$count = 0;
		for($i=0; $i<strlen($str); $i++){
			if($count == $len) break;
			if(preg_match("/[\x80-\xff]/", substr($str, $i, 1))) ++$i;
			++$count;        
		}
		return substr($str, 0, $i);
	}

	/**
	 * 截取字符串子串函数2（GB)
	 * 
	 * @param string $src 源字符串
	 * @param int $start 开始截取的位置
	 * @param int $length 需要截取字符串的长度
	 * @return string 返回截取的字符串
	 */

	public static function GBSubstr2($src, $start=0, $length=0){
		$suffix="";
		$len = strlen($src);
		if ( $len <= $length ) return $src; 
		
		$cut_length = 0;
		for( $idx = 0; $idx<$length; $idx++){ 
			$char_value = ord($src[$idx]); 
			if ( $char_value < 0x80 || ( $char_value & 0x40 ) )
				$cut_length++;
			else
				$cut_length = $cut_length + 3; 
		} 
		$curstr = substr($src, 0, $cut_length) ;
		preg_match('/^([\x00-\x7f]|.{3})*/', $curstr, $result);
		return  $result[0];
	}


	/**
	 * 统计字符串长度(UTF-8)
	 */
	public static function utfStrlen($str) {
		$count = 0;
		for($i=0; $i<strlen($str); $i++){
			$value = ord($str[$i]);
			if($value > 127) {
				$count++;
				if($value>=192 && $value<=223) $i++;
				elseif($value>=224 && $value<=239) $i = $i + 2;
				elseif($value>=240 && $value<=247) $i = $i + 3;
				else return self::raiseError("\"$str\" Not a UTF-8 compatible string", 0, __CLASS__, __METHOD__, __FILE__, __LINE__);
			}
			$count++;
		}
		return $count;
	}


	/**
	 * 截取字符串(UTF-8)
	 *
	 * @param string $str 原始字符串
	 * @param $position 开始截取位置
	 * @param $length 需要截取的偏移量
	 * @return string 截取的字符串
	 */
	public static function utfSubstr($str, $position, $length){
		$startPos = strlen($str);
		$startByte = 0;
		$endPos = strlen($str);
		$count = 0;
		for($i=0; $i<strlen($str); $i++){
			if($count>=$position && $startPos>$i){
				$startPos = $i;
				$startByte = $count;
			}
			if(($count-$startByte) >= $length) {
				$endPos = $i;
				break;
			}    
			$value = ord($str[$i]);
			if($value > 127){
				$count++;
				if($value>=192 && $value<=223) $i++;
				elseif($value>=224 && $value<=239) $i = $i + 2;
				elseif($value>=240 && $value<=247) $i = $i + 3;
				else return self::raiseError("\"$str\" Not a UTF-8 compatible string", 0, __CLASS__, __METHOD__, __FILE__, __LINE__);
			}
			$count++;

		}
		return substr($str, $startPos, $endPos-$startPos);
	}


	/**
	 * 中文字符串判断长度（支持GB2312/GBK/UTF-8/BIG5）
	 *
	 * @param string $str 要取长度的字串
	 * @param string $charset 字符串的字符集，包括有 utf-8|gb2312|gbk|big5 编码
	 * @return int 返回字符串的长度
	 */
	public static function CStrlen($str, $charset="gbk"){
		if(public static function_exists("mb_strlen")){
			//return mb_strlen($str, $charset);
		}
		$re['utf-8']	= "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
		$re['gb2312']	= "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
		$re['gbk']		= "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
		$re['big5']		= "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
		
		preg_match_all($re[$charset], $str, $match);
		return count($match[0]);
	}

	/**
	 * 中文字符串截取（支持GB2312/GBK/UTF-8/BIG5）
	 *
	 * @param string $str 要截取的字串
	 * @param int $start 截取起始位置
	 * @param int $length 截取长度
	 * @param string $charset 字符串的字符集，包括有 utf-8|gb2312|gbk|big5 编码
	 * @param bool $suffix 是否加尾缀
	 * @return string 返回接续字符串的结果
	 */
	public static function CSubstr($str, $start=0, $length, $charset="gbk", $suffix=false){
		if(public static function_exists("mb_substr")){
			return mb_substr($str, $start, $length, $charset);
		}
		$re['utf-8']	= "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
		$re['gb2312']	= "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
		$re['gbk']		= "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
		$re['big5']		= "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
		
		preg_match_all($re[$charset], $str, $match);
			$slice = join("", array_slice($match[0], $start, $length));

		if($suffix) {
			return $slice ."…";
		}
		return $slice;
	}

	/**
	 * 截取全角和半角混合的字符串以避免乱码
	 *
	 * @param $str_cut:需要截断的字符串 
	 * @param $length:允许字符串显示的最大长度
	 * @return string
	 */
	public static function cutSubstr($str_cut,$length){  

		if (strlen($str_cut) > $length){ 
			for($i=0; $i < $length; $i++) 
			if (ord($str_cut[$i]) > 128){
				$i++;
			} 
			$str_cut = substr($str_cut,0,$i); 
		} 
		return $str_cut; 
	}

	/**
	 * GBK 转 UTF8 编码
	 */
	public static function gb2Utf8($str){
		if (public static function_exists('iconv')){
			return iconv("GBK", "UTF-8", $str);
		}elseif(public static function_exists('mb_convert_encoding')){
			return mb_convert_encoding($str, 'UTF-8', 'GBK');
		}
		return $str;
	}

	/**
	 * UTF8 转 GBK 编码
	 */
	public static function utf2GB($str){
		if (public static function_exists('iconv')){
			return iconv("UTF-8", "GBK", $str);
		}elseif(public static function_exists('mb_convert_encoding')){
			return mb_convert_encoding($str, 'GBK', 'UTF-8');
		}
		return $str;	
	}
	
}


