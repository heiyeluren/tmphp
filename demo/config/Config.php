<?php
//------------------------
//
//   应用配置文件示例
//
//------------------------


return array (
	//公共配置
	'Common' => array (
		'CharSet'			=> 'UTF-8',				//文档编码
		'TimeZone'			=> 'Asia/Chongqing',	//时区设置
		'UrlHtml'			=> '1',					//是否开启伪静态
		'AutoFilter'		=> '1',					//是否进行自动对POST.GET.COOKIE进行过滤
		'HostEnv'			=> 'dev',				//当前框架运行环境: 开发环境(dev)/测试环境(test)/运营生产环境(prod)
	),
	'Framework' => array (
		'ControllerName'	=> 'c',					//控制器变量名
		'ActionName'		=> 'a',					//Action 变量名
		'DefaultController' => 'index',				//缺省的控制器名
		'DefaultAction'		=> 'index',				//缺省的Action名
		'UrlReWrite'		=> '#^/([^/]*)/([^/]*)#',//UrlRewrite规则：/Controller/Action?param
	),
	//数据库配置
	'DataBase' => array (
		'driver'			=> 'DB_Mysql',			//数据库访问驱动
		'host'				=> 'localhost',			//数据库主机地址
		'user'				=> 'root',				//数据库连接账户名
		'pwd'				=> '',					//数据库连接密码
		'db'				=> 'test',				//数据库名
		'charset'			=> 'utf8',				//数据库字符集
	),
);

