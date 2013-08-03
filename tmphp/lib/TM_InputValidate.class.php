<?php
/*********************************************
 *  描述: TMPHP Input Validate 类
 *  作者: heiyeluren 
 *  创建: 2007-04-02 16:59
 *  修改: 2007-04-05 09:30	实现基本的校验和判断
 *		  2009/12/13 3:23	修改部分函数的操作方式
 *
 *********************************************/



/**
 * 变量和数据检查类
 *
 * @desc 主要针对各种变量和获取的数据进行有效性检查
 */
class TM_InputValidate
{

	
	
	/**
	 * 检测一个变量是否为空
	 */
	public static function isEmpty($value){
		return (empty($value) || $value=="");
	}

	/**
	 * 是否包含空白、控制字符
	 */
	public static function isSpace($value){
		return preg_match( '/[\s\a\f\n\e\0\r\t\x0B]/is', $value);
	}


	/**
	 * email地址合法性检测
	 */
	public static function isEmail($value){
		return preg_match("/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/", $value);
		//return preg_match ( '/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,4})(\]?)$/', $value )
	}

	/**
	 * URL地址合法性检测
	 */
	public static function isUrl($value){
		return preg_match("/^http:\/\/[\w]+\.[\w]+[\S]*/", $value);
	}

	/**
	 * 是否是一个合法域名
	 */
	public static function isDomainName($str){
		return preg_match("/^[a-z0-9]([a-z0-9-]+\.){1,4}[a-z]{2,5}$/i", $str);
	}

	/**
	 * 检测IP地址是否合法
	 */
	public static function isIpAddr($ip){
		return preg_match("/^[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}$/", $ip);
	}

	/**
	 * 邮编合法性检测
	 */
	public static function isPostalCode($value){
		return (is_numeric($value) && (strlen($value)==6));
	}

	/**
	 * 电话(传真)号码合法性检测
	 */
	public static function isPhone($value){
		return preg_match("/^(\d){2,4}[\-]?(\d+){6,9}$/", $value);
		//return preg_match ( "/^\d{11}$|^\d{3}-\d{7,8}$|^\d{4}-\d{7,8}$/", $tel );
	}

	/**
	 * 手机号码合法性检查
	 */
	 public static function isMobile($str){
		return preg_match("/^(13|15|18)\d{9}$/i", $str);
	 }

	/**
	 * 身份证号码合法性检测
	 */
	public static function isIdCard($value){
		return preg_match("/^(\d{17}[\dx])$/i", $value);
	}

	/**
	* 严格的身份证号码合法性检测(按照身份证生成算法进行检查)
	*/
	public static function chkIdCard($value){
		if (strlen($value) != 18){
			return false;
		}
		$wi = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2); 
		$ai = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'); 
		$value = strtoupper($value);
		$sigma = '';
		for ($i = 0; $i < 17; $i++) {
			$sigma += ((int) $value{$i}) * $wi[$i]; 
		} 
		$parity_bit = $ai[($sigma % 11)];
		if ($parity_bit != substr($value, -1)){
			return false;
		}
		return true;
	}

	/**
	 * 检测是否包含特殊字符
	 */
	public static function chkSpecialWord($value){
		return preg_match('/>|<|,|\[|\]|\{|\}|\?|\/|\+|=|\||\'|\\|\"|:|;|\~|\!|\@|\#|\*|\$|\%|\^|\&|\(|\)|`/i', $value);
	}
	


	/**
	 * 检测一个文件是否存在（可以是本地文件或者是HTTP协议的文件）
	 *
	 * @param string $inputPath 文件路径（可以是一个URL或者是本地文件路径）
	 * @return mixed 返回false文件不存在，返回true文件存在，返回对象说明有错误
	 */
	public static function fileExist($inputPath){
		return is_file($inputPath);
	}

	/**
	 * 检测一个用户名的合法性
	 * 
	 * @param string $str 需要检查的用户名字符串
	 * @param int $chkType 要求用户名的类型，
	 * @		  1为英文、数字、下划线，2为任意可见字符，3为中文(GBK)、英文、数字、下划线，4为中文(UTF8)、英文、数字，缺省为1
	 * @return bool 返回检查结果，合法为true，非法为false
	 */
	public static function checkName($str, $chkType=1){
		switch($chkType){
			case 1:
				$result = preg_match("/^[a-zA-Z0-9_]+$/i", $str);
				break;
			case 2:
				$result = preg_match("/^[\w\d]+$/i", $str);
				break;
			case 3:
				$result = preg_match("/^[_a-zA-Z0-9\0x80-\0xff]+$/i", $str);
				break;
			case 4:
				$result = preg_match("/^[_a-zA-Z0-9\u4e00-\u9fa5]+$/i", $str);
				break;
			default:
				$result = preg_match("/^[a-zA-Z0-9_]+$/i", $str);
				break;
		}
		return $result;
	}


}

