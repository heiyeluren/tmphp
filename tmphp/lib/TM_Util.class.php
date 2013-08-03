<?php
/************************************************************
 *  描述: TMPHP Util 类
 *  作者: heiyeluren 
 *  创建: 2007-04-02 16:59	基本常用操作实现
 *  修改: 2008-09-02 18:28	增加了部分操作函数
 *		  2009/12/13 03:48	进行类处理，削减了部分代码和函数
 *		  2009-12-20 20:31	增加IP地址定位函数
 *
 ************************************************************/




class TM_Util
{

	/**
	 * 二维数组排序
	 *
	 * @param $arr:数据
	 * @param $keys:排序的健值
	 * @param $type:升序/降序
	 *
	 * @return array
	 */
	public static function multi_array_sort($arr, $keys, $type = "asc") {
		if (!is_array($arr)) {
			return false;
		}
		$keysvalue = array();
		foreach($arr as $key => $val) {
			$keysvalue[$key] = $val[$keys];
		}
		if($type == "asc"){
			asort($keysvalue);
		}else {
			arsort($keysvalue);
		}
		reset($keysvalue);
		foreach($keysvalue as $key => $vals) {
			$keysort[$key] = $key;
		}
		$new_array = array();
		foreach($keysort as $key => $val) {
			$new_array[$key] = $arr[$val];
		}
		return $new_array;
	}


	/**
	 * 创建多级目录$dir
	 *
	 * @param $dir:path的绝对路径
	 * @return bool
	 */
	public static function mmkdir($dir) {
		$path = array();
		$dir = preg_replace("/\/*$/", "", $dir);
		while (!is_dir($dir) && strlen(str_replace("/", "", $dir))) {
			$path[] = $dir;
			$dir = preg_replace("/\/[\w-]+$/", "", $dir);
		}
		krsort($path);
		if (sizeof($path)) {
			foreach($path as $key=>$val) {
				@mkdir($val, 0777);
			}
		}
		return true;
	}

	/**
	 * 删除该目录下的所有文件
	 *
	 * @param $dir:目录
	 * @param $tag:true:同时删除该目录，false:仅仅删除该目录下的文件及子目录
	 * @return bool
	 */
	public static function m_rmdir($dir, $tag = false) {
		if ($handle = @opendir($dir)) {
			while (false !== ($file = @readdir($handle))) {
				if ($file != "." && $file != "..") {
					$ff = $dir . "/" . $file;
					if (is_file($ff)){
						@unlink($ff) ;
					}elseif (is_dir($ff)){
						m_rmdir($ff);
						@rmdir($ff);
					}
				}
			}
			closedir($handle);
		}
		if ($tag){
			@rmdir($dir);
		}
	}


	/**
	 * 几率函数 (在指定)
	 *
	 * @param int $probaility 概率
	 * @parm int $divisor 因子
	 *
	 *-------------------------------------------------------- 
	 * C version
	 * Flush expired data probability (Garbage Collection)
	 *
	 * @desc probability big probaility increase, divisor big probaility decrease
	 *
	 * int get_gc_probability(unsigned probaility, unsigned divisor){
	 *		int n;
	 *		struct timeval tv; 
	 *
	 *		gettimeofday(&tv , (struct timezone *)NULL);
	 *		srand((int)(tv.tv_usec + tv.tv_sec));
	 *		n = 1 + (int)( (float)divisor * rand() / (RAND_MAX+1.0) );
	 *		return (n <= probaility ? 1 : 0); 
	 *	}
	 */
	public static function getProbability($probaility=1, $divisor=3600){
		$n = 1 + (int)( mt_rand(0, $divisor)) / (float)(32768+1.0);
		return ($n <= $probaility ? true : false); 
	}




	/**
	 * 分表函数 (用于在导入数据时候使用)
	 *
	 * 附加说明：
	 *	1. 必须分表，MySQL的MyISAM引擎数据量达到一定数据级别以后查询性能下降的厉害，所以分表是必须的，推荐数据量是单表：50W - 200W 为佳
		2. 导入数据尽量不要使用 INSERT INTO 这种方式，性能较差，建议使用 load data infile 的方式性能能够提高 300%
		3. 数据表导入数据不要给字段加索引，可以再数据导入完成后再使用 alter table add index 的方式增加索引
	 */
	public static function segmentTable($key, $table_size = 50, $table_prefix = 'Tbl_'){
		if ($key == ''){
			return '';
		}
		$s = 0;
		for($i = 0; $i<12; $i++){
			$s += ord($key[$i]);
		}
		$num = $s % $table_size;
		return $table_prefix . $num;
	}



	/**
	 * 通过IP获取真实地址
	 *
	 * 说明：必须保证存在 ip.dat 这个IP数据库，如果没有，请到如下地址下载放在本类所在目录：
	 *		 地址1：http://tmphp.googlecode.com/files/ip.dat
	 *		 地址2: http://heiyeluren.googlecode.com/files/ip.dat
	 *
	 *		 下载最新IP数据库请访问：http://www.cz88.net/
	 *
	 *
	 * @param string $ip ip地址
	 * @param string $ip_data IP数据库文件路径 (纯真IP)
	 * @return string 返回地址字符串
	 */
	public static function getIpLocation($ip, $ip_data = dirname(__FILE__). '/ip.dat') {
		//IP数据文件路径
		$dat_path = $ip_data!= '' ? $ip_data : dirname(__FILE__). '/ip.dat';

		//检测ip数据文件是否存在
		if (!is_file($dat_path)|| !is_readable($dat_path)){
			return false;
		}

		//检查IP地址
		if(!preg_match("/^d{1,3}.d{1,3}.d{1,3}.d{1,3}$/", $ip)) {
			return 'IP Address Error';
		}
		//打开IP数据文件
		if(!$fd = @fopen($dat_path, 'rb')){
			return 'IP date file not exists or access denied';
		}

		//分解IP进行运算，得出整形数
		$ip = explode('.', $ip);
		$ipNum = $ip[0] * 16777216 + $ip[1] * 65536 + $ip[2] * 256 + $ip[3];

		//获取IP数据索引开始和结束位置
		$DataBegin = fread($fd, 4);
		$DataEnd = fread($fd, 4);
		$ipbegin = implode('', unpack('L', $DataBegin));
		if($ipbegin < 0) $ipbegin += pow(2, 32);
		$ipend = implode('', unpack('L', $DataEnd));
		if($ipend < 0) $ipend += pow(2, 32);
		$ipAllNum = ($ipend - $ipbegin) / 7 + 1;
		
		$BeginNum = 0;
		$EndNum = $ipAllNum;

		//使用二分查找法从索引记录中搜索匹配的IP记录
		while($ip1num>$ipNum || $ip2num<$ipNum) {
			$Middle= intval(($EndNum + $BeginNum) / 2);

			//偏移指针到索引位置读取4个字节
			fseek($fd, $ipbegin + 7 * $Middle);
			$ipData1 = fread($fd, 4);
			if(strlen($ipData1) < 4) {
				fclose($fd);
				return 'System Error';
			}
			//提取出来的数据转换成长整形，如果数据是负数则加上2的32次幂
			$ip1num = implode('', unpack('L', $ipData1));
			if($ip1num < 0) $ip1num += pow(2, 32);
			
			//提取的长整型数大于我们IP地址则修改结束位置进行下一次循环
			if($ip1num > $ipNum) {
				$EndNum = $Middle;
				continue;
			}
			
			//取完上一个索引后取下一个索引
			$DataSeek = fread($fd, 3);
			if(strlen($DataSeek) < 3) {
				fclose($fd);
				return 'System Error';
			}
			$DataSeek = implode('', unpack('L', $DataSeek.chr(0)));
			fseek($fd, $DataSeek);
			$ipData2 = fread($fd, 4);
			if(strlen($ipData2) < 4) {
				fclose($fd);
				return 'System Error';
			}
			$ip2num = implode('', unpack('L', $ipData2));
			if($ip2num < 0) $ip2num += pow(2, 32);

			//没找到提示未知
			if($ip2num < $ipNum) {
				if($Middle == $BeginNum) {
					fclose($fd);
					return 'Unknown';
				}
				$BeginNum = $Middle;
			}
		}

		$ipFlag = fread($fd, 1);
		if($ipFlag == chr(1)) {
			$ipSeek = fread($fd, 3);
			if(strlen($ipSeek) < 3) {
				fclose($fd);
				return 'System Error';
			}
			$ipSeek = implode('', unpack('L', $ipSeek.chr(0)));
			fseek($fd, $ipSeek);
			$ipFlag = fread($fd, 1);
		}

		if($ipFlag == chr(2)) {
			$AddrSeek = fread($fd, 3);
			if(strlen($AddrSeek) < 3) {
				fclose($fd);
				return 'System Error';
			}
			$ipFlag = fread($fd, 1);
			if($ipFlag == chr(2)) {
				$AddrSeek2 = fread($fd, 3);
				if(strlen($AddrSeek2) < 3) {
					fclose($fd);
					return 'System Error';
				}
				$AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
				fseek($fd, $AddrSeek2);
			} else {
				fseek($fd, -1, SEEK_CUR);
			}

			while(($char = fread($fd, 1)) != chr(0))
				$ipAddr2 .= $char;

			$AddrSeek = implode('', unpack('L', $AddrSeek.chr(0)));
			fseek($fd, $AddrSeek);

			while(($char = fread($fd, 1)) != chr(0))
				$ipAddr1 .= $char;
		} else {
			fseek($fd, -1, SEEK_CUR);
			while(($char = fread($fd, 1)) != chr(0))
				$ipAddr1 .= $char;

			$ipFlag = fread($fd, 1);
			if($ipFlag == chr(2)) {
				$AddrSeek2 = fread($fd, 3);
				if(strlen($AddrSeek2) < 3) {
					fclose($fd);
					return 'System Error';
				}
				$AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
				fseek($fd, $AddrSeek2);
			} else {
				fseek($fd, -1, SEEK_CUR);
			}
			while(($char = fread($fd, 1)) != chr(0)){
				$ipAddr2 .= $char;
			}
		}
		fclose($fd);

		//最后做相应的替换操作后返回结果
		if(preg_match('/http/i', $ipAddr2)) {
			$ipAddr2 = '';
		}
		$ipaddr = "$ipAddr1 $ipAddr2";
		$ipaddr = preg_replace('/CZ88.NET/is', '', $ipaddr);
		$ipaddr = preg_replace('/^s*/is', '', $ipaddr);
		$ipaddr = preg_replace('/s*$/is', '', $ipaddr);
		if(preg_match('/http/i', $ipaddr) || $ipaddr == '') {
			$ipaddr = 'Unknown';
		}

		return $ipaddr;
	}



}



