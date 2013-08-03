<?php
/********************************
 *  描述: 框架应用配置文件
 *  作者: heiyeluren 
 *  创建: 2009-12-20 21:51
 *  修改：2009-12-22 12:18
 ********************************/


class AppConst
{
	//------------------------
	//
	//  常量和静态配置测试
	//
	//------------------------

	//测试常量定义
	const TEST_CONST	= 'test';


	//测试常量数组
	private static $_list = array(
		'key1'	=> 'val1',
		'key2'	=> 'val2',
	); 
	public static function getList($key = ''){
		if ($key == ''){
			return self::$_list;
		}
		if (!isset(self::$_list[$key])){
			return '';
		}
		return self::$_list[$key];
	}



	//------------------------------
	//
	//  图片上传常量和静态配置引导
	//
	//------------------------------

	//上传图片审核状态
	const IMG_CHECK_DEFAULT		= 1;	//缺省未审核状态
	const IMG_CHECK_PASSED		= 2;	//审核通过
	const IMG_CHECK_DELETE		= 3;	//审核删除
	const IMG_CHECK_HIDE		= 4;	//审核隐藏图片


	//上传图片文件保存目录
	const IMG_UPLOAD_PATH	= "/var/www/data/upload";



    //上传图片文件扩展名和MIME头限制
	private static $_graphValidatedTypes = array('.jpg', '.gif', '.png');
    public static function graphValidatedTypes(){
        return self::$_graphValidatedTypes;
    }
	private static $_graphValidatedMimes = array('image/gif', 'image/png', 'image/jpeg', 'image/pjpeg');
    public static function graphValidatedMimes(){
        return self::$_graphValidatedMimes;
    }


	//上传图片错误消息定义
	private static $_uploadErrors = array (
		1 => "系统繁忙请稍后重新上传", 
		2 => "添加水印图失败，请重试", 
		3 => "生成缩略图失败，请重新上传", 
		4 => "您上传的文件格式不正确", 
		5 => "您上传的文件过大", 
		6 => "您上传的文件个数不符合要求", 
		7 => "您今天上传的文件个数超过了要求" 
	);	
	public static function uploadError($mixed) {
		return self::$_uploadErrors [$mixed];
	}


}


