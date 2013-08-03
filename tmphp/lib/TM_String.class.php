<?php
/******************************************************
 *  描述: TMPHP String 类
 *  作者: heiyeluren 
 *  创建: 2009-03-26 23:13  基本操作函数
 *  修改: 2009-11-24 23:44	增加了部分函数 
 *		  2009/12/13 03:51	削减函数，剩下与字符串相关
 *
 ******************************************************/




class TM_String
{


	/**
	 * php解码JS中的escape编码的内容
	 * 
	 */
	public static function unescape($str) {
		$str = rawurldecode($str);
		preg_match_all("/%u.{4}|&#x.{4};|&#\d+;|&#\d+?|.+/U",$str,$r);
		$ar = $r[0];
		foreach($ar as $k=>$v) {
			if(substr($v,0,2) == "%u")
				$ar[$k] = iconv("UCS-2","GBK",pack("H4",substr($v,-4)));
				elseif(substr($v,0,3) == "&#x")
				$ar[$k] = iconv("UCS-2","GBK",pack("H4",substr($v,3,-1)));
				elseif(substr($v,0,2) == "&#") {
				$ar[$k] = iconv("UCS-2","GBK",pack("n",preg_replace("/[^\d]/","",$v)));
			}
		}
		$src=join("",$ar);
		$src=mb_convert_encoding($src,'UTF-8', 'GBK');
		return $src;
	} 

	/**
	 * 取代 unescape 函数
	 *
	 */
	public static function phpUnescape($escstr)    
	{    
	    preg_match_all("/%u[0-9A-Za-z]{4}|%.{2}|[0-9a-zA-Z.+-_]+/", $escstr, $matches);    
	    $ar = &$matches[0];    
	    $c = "";    
	    foreach($ar as $val)    
	    {    
	        if (substr($val, 0, 1) != "%")    
	        {    
	            $c .= $val;    
	        } elseif (substr($val, 1, 1) != "u")    
	        {    
	            $x = hexdec(substr($val, 1, 2));    
	            $c .= chr($x);    
	        }     
	        else   
	        {    
	            $val = intval(substr($val, 2), 16); 
	            if ($val < 0x7F) // 0000-007F    
	            {    
	                $c .= chr($val);    
	            } elseif ($val < 0x800) // 0080-0800    
	            {    
	                $c .= chr(0xC0 | ($val / 64));    
	                $c .= chr(0x80 | ($val % 64));    
	            }     
	            else // 0800-FFFF    
	            {    
	                $c .= chr(0xE0 | (($val / 64) / 64));    
	                $c .= chr(0x80 | (($val / 64) % 64));    
	                $c .= chr(0x80 | ($val % 64));    
	            }     
	        }     
	    }     
	    return $c;    
	}   

	/**
	 * 生成一个32位长度之内的Hash字符串
	 */
	public static function hash_str($len){
		return substr(md5(uniqid(rand(), true)), 0, $len);
	}

	/**
	 * 十六进制转换为二进制内容
	 *
	 * @param string $hex 十六进制的内容
	 * @return string 二进制的内容返回
	 */
	public static function hex2bin($hex){
		return pack("H*", $hex);
	}

    /**
     * 生成指定长度随机数字
     *
     * @param int $length 需要生成的长度
	 * 
	 * @return int
     */
    public static function randNum($length){
        $hash = '';
		$chars='0123456789';
        $max = strlen($chars) - 1;
        mt_srand((double)microtime() * 1000000);
        for($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
        return (int)$hash;
    }

    /**
     * 生成指定长度随机字符串
     *
     * @param int $length 需要生成的长度
	 *
	 * @paran str
     */
    public static function randStr($length){
        $hash = '';
		$chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $max = strlen($chars) - 1;
        mt_srand((double)microtime() * 1000000);
        for($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
        return $hash;
    }


    /**
     * 可逆加密函数
     * 
	 * Desc: 可接受任何字符，安全度非常高，运算速度快
	 * 
	 * @param str $txt  要加密的字符串内容
	 * @param str $key  密钥，必须与解密钥保持一致
	 * 
	 * @param str 返回加密后的字符串
     */
    public static function encrypt($txt, $key = '+secure_key+')
    {
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
        $ikey ="-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
        $nh1 = rand(0,64);
        $nh2 = rand(0,64);
        $nh3 = rand(0,64);
        $ch1 = $chars{$nh1};
        $ch2 = $chars{$nh2};
        $ch3 = $chars{$nh3};
        $nhnum = $nh1 + $nh2 + $nh3;
        $knum = 0;$i = 0;
        while(isset($key{$i})) $knum +=ord($key{$i++});
        $mdKey = substr(md5(md5(md5($key.$ch1).$ch2.$ikey).$ch3),$nhnum%8,$knum%8 + 16);
        $txt = base64_encode($txt);
        $txt = str_replace(array('+','/','='),array('-','_','.'),$txt);
        $tmp = '';
        $j=0;$k = 0;
        $tlen = strlen($txt);
        $klen = strlen($mdKey);
        for ($i=0; $i<$tlen; $i++) {
            $k = $k == $klen ? 0 : $k;
            $j = ($nhnum+strpos($chars,$txt{$i})+ord($mdKey{$k++}))%64;
            $tmp .= $chars{$j};
        }
        $tmplen = strlen($tmp);
        $tmp = substr_replace($tmp,$ch3,$nh2 % ++$tmplen,0);
        $tmp = substr_replace($tmp,$ch2,$nh1 % ++$tmplen,0);
        $tmp = substr_replace($tmp,$ch1,$knum % ++$tmplen,0);
        return $tmp;
    }

    /**
     * encrypt 对应的解密函数
     * 
	 * Desc: 可接受任何字符，安全度非常高，运算速度快
	 * 
	 * @param str $txt  由encrypt 生成的密码字符串
	 * @param str $key  密钥，必须与加密钥保持一致
	 * 
	 * @param str 返回原文字符串
     */
    public static function decrypt($txt, $key = '+secure_key+')
    {
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
        $ikey ="-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
        $knum = 0;$i = 0;
        $tlen = strlen($txt);
        while(isset($key{$i})) $knum +=ord($key{$i++});
        $ch1 = $txt{$knum % $tlen};
        $nh1 = strpos($chars,$ch1); 
        $txt = substr_replace($txt,'',$knum % $tlen--,1);
        $ch2 = $txt{$nh1 % $tlen};
        $nh2 = strpos($chars,$ch2);
        $txt = substr_replace($txt,'',$nh1 % $tlen--,1);
        $ch3 = $txt{$nh2 % $tlen};
        $nh3 = strpos($chars,$ch3);
        $txt = substr_replace($txt,'',$nh2 % $tlen--,1);
        $nhnum = $nh1 + $nh2 + $nh3;
        $mdKey = substr(md5(md5(md5($key.$ch1).$ch2.$ikey).$ch3),$nhnum % 8,$knum % 8 + 16);
        $tmp = '';
        $j=0; $k = 0;
        $tlen = strlen($txt);
        $klen = strlen($mdKey);
        for ($i=0; $i<$tlen; $i++) {
            $k = $k == $klen ? 0 : $k;
            $j = strpos($chars,$txt{$i})-$nhnum - ord($mdKey{$k++});
            while ($j<0) $j+=64;
            $tmp .= $chars{$j};
        }
        $tmp = str_replace(array('-','_','.'),array('+','/','='),$tmp);
        return base64_decode($tmp);
    }
	


	/**
	 * 字符串加密函数(只能使用 crypt_dec 函数进行解密)
	 *
	 * @paran string $str 想要加密的字符串
	 * @return string 返回加密后的字符串
	 */
	public static function crypt_enc($str){
		list($s1, $s2) = sscanf('YWJjZGVmLT0vW118IyQlQCFhZHNmJioqKigpXyshfkBhc2Rmc2Rme306OyciLC4vPz48PFwxMjMz', "%32s%32s");
		return bin2hex($s1.base64_encode(~$str).$s2);
	}

	/**
	 * 字符串解密函数(只能解使用 crypt_enc 函数加密的字符串)
	 *
	 * @paran string $str 已加密的字符串
	 * @return string 返回明文字符串
	 */
	function crypt_dec($str){
		return ~base64_decode(substr(pack("H*", $str), 32, -32));
	}



    /**
     * 获取中间加入了数字的时间字符串  (与 recoverKeyTime 配合使用，用于抽奖、积分上传等场合)
     *
     * 数据格式：
     * +----------------------------------------------+
     * | 偏移量位 | 随机串 | 时间戳 | 随机串 | 校验位 |
     * +----------------------------------------------+
     * 
     * @param int $time 当前时间，不设定则自动获取
     * @return 返回一个24位的数字串
     */
    public static function makeTimeKey($randlen = 8, $time = ''){
        //生成随机串
        $hash = '';
        $length = 8;
        $chars = '0123456789';
        $max = strlen($chars) - 1;
        mt_srand((double)microtime() * 1000000);
        for($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }

        //把时间戳压到随机串
        $rand = $hash;
        $pos = mt_rand(1, $randlen);
        $time = $time!='' ? $time : time();
        $s = $pos . substr($rand, 0, $pos) . $time . substr($rand, $pos);

        //生成最后一位校验码
        $len = strlen($s);
        for($t=0, $i=0; $i<$len; $i++){
            $t = $t + ($i*$s[$i]);
        }
        $sigma = $t % 10;
        $key = $s . $sigma;

        return $key;
    }

    /**
     * genTimeKey 中设置的时间中获取串中的时间戳 (与 getTimeKey 配合使用，用于抽奖、积分上传等场合)
     *
     * @param str $s 数字字符串
     * @return 时间戳
     */
    public static function recoverKeyTime($s){
        //判断校验位
        $len = strlen($s) - 1;
        for($t=0, $i=0; $i<$len; $i++){
            $t = $t + ($i*$s[$i]);
        }
        $sigma = $t % 10;
        if ($sigma != substr($s, -1)){
            return -1;
        }
        //读取首位
        $pos = (int)substr($s, 0, 1);
        if ($pos==0) return -2;

        //读取时间
        $time = substr($s, $pos+1, 10);
        if ($time < strtotime('2008-01-01') || $time > strtotime('2012-12-31')){
            return -3;
        }
        return $time;
    }


}
