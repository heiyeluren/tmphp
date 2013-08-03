<?php
/********************************
 *  描述: Crontab应用示例
 *  作者: heiyeluren 
 *  创建: 2009-12-22 11:22
 *  修改：2009-12-22 11:30
 ********************************/

require_once "boolloader.php";

//访问配置
print_r($config);

//访问数据库
$db->query("set names ". $config['DataBase']['charset']);

//其他操作
//code here...

