<?php
/********************************
 *  描述: 缺省控制器示例
 *  作者: heiyeluren 
 *  创建: 2009-12-20 23:33
 *  修改：2009-12-22 11:56  构建基本函数
 *		  2009-12-28 09:41  修改rewrite访问
 ********************************/


/**
 * 缺省控制器类
 */
class indexController extends TM_Controller
{
	/**
	 * 缺省首页展示Action
	 *
	 * 访问URL：http://localhost/
	 *			http://localhost/?c=index&a=index
	 *			http://localhost/index/index
	 * @return void
	 */
	public function indexAction(){
		//设置页面展示数据
		$this->title = '这是一个缺省的首页';
		$this->list  = array(
			array("link"=>'?c=index&a=index', "link2"=>'/', "title"=>'首页 - 基本功能列表'),
			array("link"=>'?c=index&a=php&var1=xxx&var2=yyy', "link2"=>'/index/php?var1=xxx&var2=yyy', "title"=>'PHP模板和变量接收展示页'),
			array("link"=>'?c=index&a=smarty', "link2"=>'/index/smarty', "title"=>'Smarty模板展示页'),
			array("link"=>'?c=index&a=discuz', "link2"=>'/index/discuz', "title"=>'Discuz模板展示页'),
			array("link"=>'?c=index&a=phplib', "link2"=>'/index/phplib', "title"=>'PHPLIB模板展示页'),
			array("link"=>'?c=index&a=db', "link2"=>'/index/db', "title"=>'直接数据库操作展示页'),
			array("link"=>'?c=index&a=db2', "link2"=>'/index/db2', "title"=>'使用Model数据库操作展示页'),	
			array("link"=>'?c=index&a=go', "link2"=>'/index/go', "title"=>'跳转页面展示页'),
		);

		//设置模板进行展现
		$this->render('default.php');
	}

	/**
	 * 使用PHP模板展示Action
	 *
	 * 访问URL：http://localhost/?c=index&a=php&var1=xxx&var2=yyyy
	 *			http://localhost/index/php?var1=xxx&var2=yyyy
	 *
	 * @return void
	 */
	public function phpAction(){
		//设置页面展示数据
		$this->title = '这是一个PHP模板和变量接收展示页';
		$this->list  = array('数据列1', '数据列2', '数据列3', '数据里4');

		//获取传递GET参数
		$this->var1	= $this->getParam("var1");
		$this->var2	= $this->request->getQuery("var2");
		$this->var3 = $this->request->getPost("var3");

		//设置模板进行展现
		$this->render('php.php');
	}




	/**
	 * 使用Smarty模板展示Action
	 *
	 * 访问URL：http://localhost/?c=index&a=smarty
	 *			http://localhost/index/smarty
	 *
	 * 相关链接：
	 *	Smarty官网：http://www.smarty.net/
	 *	Smarty手册：http://www.phpchina.com/manual/smarty/
	 *	Smarty入门：http://www.google.cn/search?q=%E8%8F%9C%E9%B8%9F%E5%AD%A6PHP%E4%B9%8BSmarty%E5%85%A5%E9%97%A8&btnG=Google+%E6%90%9C%E7%B4%A2
	 *
	 * @return void
	 */
	public function smartyAction(){
		try {
			//设置页面展示数据
			$show['title'] = '这是一个Smarty模板展示页';
			$show['list']  = array('数据列1', '数据列2', '数据列3', '数据里4');

			//设置展现模板类型
			$this->setViewType(TM_View::TYPE_SMARTY);

			//组织Smarty展现设置参数
			$config = array(
				'compile_dir'	=> APP_DIR .'/cache/smarty/templates_c',
				'cache_dir'		=> APP_DIR .'/cache/smarty/cache',
				'config_dir'	=> APP_DIR .'/cache/smarty/configs',
			);

			//设置模板进行展现
			$this->render('smarty.tpl', $show, $config);

		} catch (TM_Exception $e) {
			echo "smarty action error: ". $e->getMessage();
		}
	}



	/**
	 * 使用Discuz模板展示Action
	 *
	 * 访问URL：http://localhost/?c=index&a=discuz
	 *			http://localhost/index/discuz
	 *
	 * 相关链接：
	 *  Discuz模板介绍：http://www.discuz.net/usersguide/advanced_styles.htm
	 *	Discuz模板语法说明：http://www.google.cn/search?q=discuz+%E6%A8%A1%E6%9D%BF%E8%AF%AD%E6%B3%95%E8%AF%B4%E6%98%8E
	 *
	 * @return void
	 */
	public function discuzAction(){
		try {
			//设置页面展示数据
			$show['title'] = '这是一个Discuz模板展示页';
			$show['list']  = array('数据列1', '数据列2', '数据列3', '数据里4');

			//设置展现模板类型
			$this->setViewType(TM_View::TYPE_DISCUZ);

			//组织Discuz模板展现设置参数
			$config = array(
				'cache_dir'		 => APP_DIR .'/cache/discuz/cache',	//指定缓存文件存放目录
				'auto_update'	 => true,							//当模板文件有改动时重新生成缓存 [关闭该项会快一些]
				'cache_lifetime' => 1,								//缓存生命周期(分钟)，为 0 表示永久 [设置为 0 会快一些]
			);

			//设置模板进行展现
			$this->render('discuz.tpl', $show, $config);

		} catch (TM_Exception $e) {
			echo "discuz action error: ". $e->getMessage();
		}
	}


	/**
	 * 使用PHPLIB模板展示Action (暂时不支持 block 使用，所以不推荐使用本模板)
	 *
	 * 访问URL：http://localhost/?c=index&a=phplib
	 *			http://localhost/index/phplib
	 *
	 * 相关链接：
	 * PHPLIB 官网：http://sourceforge.net/projects/phplib/
	 * PHPlib Tempalte 手册：http://www.sanisoft.com/phplib/manual/template.php
	 * PHPlib Template 使用：http://www.google.cn/search?q=phplib+template
	 *
	 * @return void
	 */
	public function phplibAction(){
		try {
			//设置页面展示数据
			$show['title'] = '这是一个PHPLIB模板展示页';
			$show['list1'] = '数据列1';
			$show['list2'] = '数据列2';
			$show['list3'] = '数据列3';
			$show['list4'] = '数据里4';

			//设置展现模板类型
			$this->setViewType(TM_View::TYPE_PHPLIB);

			//设置模板进行展现
			$this->render('phplib.tpl', $show, $config);

		} catch (TM_Exception $e) {
			echo "discuz action error: ". $e->getMessage();
		}
	}



	/**
	 * 数据库访问Action
	 *
	 * 访问URL：http://localhost/?c=index&a=db
	 *			http://localhost/index/db
	 *
	 * @return void
	 */
	public function dbAction(){
		try {
			//直接连接数据库
			$driver = $this->config['DataBase']['driver']=='' ? "DB_Mysql" : $this->config['DataBase']['driver'];
			$class  = TM_PREFIX ."_". $driver;
			$dbConfig = array(
				"host"		=> $this->config['DataBase']['host'],
				"user"		=> $this->config['DataBase']['user'],
				"pwd"		=> $this->config['DataBase']['pwd'],
				"db"		=> $this->config['DataBase']['db'],
			);
			$db = new $class($dbConfig);

			//从Model访问数据库
			$model = TM_Model::getInstance($this);
			$db = $model->getDb();

			
			//设置数据库编码
			$db->query("set names ". $this->config['DataBase']['charset']);

			//建立一个数据表
			$db->query("CREATE TABLE user (
						`id` INT( 10 ) NOT NULL AUTO_INCREMENT ,
						`name` VARCHAR( 32 ) NOT NULL ,
						`email` VARCHAR( 32 ) NOT NULL ,
						PRIMARY KEY ( `id` ) 
						) ENGINE = MYISAM ;
				");

			//插入一条记录
			$arrInsert = array("name"=>'heiyeluren', "email"=>'heiyeluren@example.com');
			$db->insert($arrInsert, "user");

			//更新记录
			$arrUpdate = array("name"=>'test', "email"=>'test@example.com');
			$db->update($arrUpdate, "name='heiyeluren'", 'user');

			//统计记录数
			$total = $db->count(array('name'=>'test'), array(), 'user');

			//读取所有记录
			$list = $db->getAll("select * from user");

			//设置展现模板数据
			$this->title = "数据库访问展现页";
			$this->total = $total;
			$this->list  = $list;

			//设置模板进行展现
			$this->render('db.php');

		} catch (TM_Exception $e) {
			echo "db action error: ". $e->getMessage();
		}
	}



	/**
	 * 使用自己编写的Model进行数据库访问Action
	 *
	 * 访问URL：http://localhost/?c=index&a=db2
	 *			http://localhost/index/db2
	 *
	 * @return void
	 */
	public function db2Action(){
		try {
			//实例化自己编写的Model
			$model = new IndexModel($this);
			
			//建立一个数据表
			$model->createTable();

			//插入一条记录
			$model->addUser('heiyeluren', 'heiyeluren@example.com');

			//更新记录
			$model->modifyUser('heiyeluren', 'test');

			//统计记录数
			$total = $model->countUser();

			//读取所有记录
			$list = $model->getUserList();

			//设置展现模板数据
			$this->title = "数据库访问展现页";
			$this->total = $total;
			$this->list  = $list;

			//设置模板进行展现
			$this->render('db.php');

		} catch (TM_Exception $e) {
			echo "db action error: ". $e->getMessage();
		}
	}




	/**
	 * 跳转展现Action
	 *
	 * 访问URL：http://localhost/?c=index&a=go
	 *			http://localhost/index/go
	 *
	 * @return void
	 */	
	public function goAction(){
		$this->go('/', 'This is redirect index page action');
	}



	/**
	 * 性能测试Aciton
	 *
	 * 访问URL：http://localhost/?c=index&a=time
	 8			http://localhost/index/time
	 *
	 * @return void
	 */	
	public function timeAction(){
		echo time();
	}




}
