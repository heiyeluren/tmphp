<?php   
//---------------------------------------------------------------------   
//      MySQLi Master/Slave数据库读写操作类
//   
// 开发作者: heiyeluren   
// 版本历史:    
//          2006-09-20  基本单数据库操作功能, 25 个接口   
//          2007-07-30  支持单Master/多Slave数据库操作，29个接口   
//          2008-09-07  修正了上一版本的部分Bug   
//          2009-11-17  在Master/Slave类的基础上增加了强化单主机操作，   
//                      增加了部分简洁操作接口和调试接口，优化了部分代码，   
//                      本版本共42个接口
//			2009-11-26	增加了部分调试和性能监控接口
//			2009-12-13  整合到TMPHP项目中
//			2009-12-19  修改mysqli_free_result判断bug
//			2009-12-20  整个库移植为底层采用Mysqli API，增加接口，同时修改为PHP5对象方式
//
// 功能描述：自动支持Master/Slave 读/写 分离操作，支持多Slave主机   
//   
//-----------------------------------------------------------------------   
       
  
  
       
/**  
 * TM DB Mysqli 针对MySQL 4.1 以上的数据库处理类，使用 mysqli_* 库，同时采用PHP5方式
 *  
 * 描述：能够分别处理一台Master写操作，多台Slave读操作  
 */  
class TM_DB_Mysqli
{   
    /**  
     * 数据库配置信息  
     */  
    private $wdbConf = array();   
    private $rdbConf = array();   
    /**  
     * Master数据库连接  
     */  
    private $wdbConn = null;   
    /**  
     * Slave数据库连接  
     */  
    private $rdbConn = array();   
    /**  
     * 当前操作的数据库链接  
     */  
    private $currConn = null;   
    /**  
     * 是否只有一台Master数据库服务器  
     */  
    private $singleHost = true;   
    /**  
     * 数据库结果  
     */  
    private $dbResult;   
    /**  
     * 数据库查询结果集  
     */  
    private $dbRecord;   
  
    /**  
     * SQL语句  
     */  
    private $dbSql;   
    /**  
     * 数据库编码  
     */  
    private $dbCharset = "UTF8";   
    /**  
     * 数据库版本  
     */  
    private $dbVersion = "";   
  
  
    /**  
     * 初始化的时候是否要连接到数据库  
     */  
    private $isInitConn = false;   
    /**  
     * 是否要设置字符集  
     */  
    private $isCharset = false;   
    /**  
     * 数据库结果集提取方式  
     */  
    private $fetchMode = MYSQLI_ASSOC;
    /**  
     * 执行中发生错误是否记录日志  
     */  
    private $isLog = false;   
    /**  
     * 执行中的SQL是否记录，设定级别  
     *  
     * 0:不记录     
     * 1:记录insert    
     * 2:记录insert/update    
     * 3:记录insert/update/delete    
     * 4:记录select/insert/update/delete  
     */  
    private $logSqlLevel = 0;   
    /**  
     * 记录Log文件路径  
     */  
    private $logFile = '/tmp/db_mysqli_error.log';   
    /**  
     * 是否查询出错的时候终止脚本执行  
     */  
    private $isExit = false;   
    /**  
     * MySQL执行是否出错了  
     */  
    private $isError = false;   
    /**  
     * MySQL执行错误消息  
     */  
    private $errMsg  = '';   
    /**  
     * 是否记录SQL运行时间  
     */  
    private $isRuntime = true;   
    /**  
     * SQL执行时间  
     */  
    private $runTime = 0;   
  
  
  
  
    //------------------------   
    //   
    //  类本身操作方法   
    //   
    //------------------------   
  
    /**  
     * 设置类属性  
     *  
     * @param str $key  需要设置的属性名  
     * @param str $value 需要设置的属性值  
     * @return void  
     */  
    private function set($key, $value){   
        $this->$key = $value;   
    }   
  
    /**  
     * 读取类属性  
     *  
     * @param str $key  需要读取的属性名  
     * @return void  
     */  
    private function get($key){   
        return $this->$key;   
    }   
  
  
  
  
    //------------------------   
    //   
    //   基础底层操作接口   
    //   
    //------------------------   
  
    /**  
     * 构造函数  
     *   
     * 传递配置信息，配置信息数组结构：  
     * $masterConf = array(  
     *        "host"    => Master数据库主机地址  
     *        "user"    => 登录用户名  
     *        "pwd"    => 登录密码  
     *        "db"    => 默认连接的数据库  
     *    );  
     * $slaveConf = array(  
     *        "host"    => Slave1数据库主机地址|Slave2数据库主机地址|...  
     *        "user"    => 登录用户名  
     *        "pwd"    => 登录密码  
     *        "db"    => 默认连接的数据库  
     *    );  
     *  
     * @param bool $singleHost  是否只有一台主机  
     * @return void  
     */  
    public function __construct($masterConf, $slaveConf=array(), $singleHost = true){   
        //构造数据库配置信息   
        if (is_array($masterConf) && !empty($masterConf)){   
            $this->wdbConf = $masterConf;   
        }   
        if (!is_array($slaveConf) || empty($slaveConf)){   
            $this->rdbConf = $masterConf;   
        } else {   
            $this->rdbConf = $slaveConf;   
        }   
        $this->singleHost = $singleHost;   
        //初始化连接（一般不推荐）   
        if ($this->isInitConn){   
            $this->getDbWriteConn();   
            if (!$this->singleHost){   
                $this->getDbReadConn();   
            }   
        }   
    }   
  
    /**  
     * 获取Master的写数据连接  
     */  
    private function getDbWriteConn(){   
        //判断是否已经连接   
        if ($this->wdbConn && is_object($this->wdbConn)) {
            return $this->wdbConn;   
        }   
        //没有连接则自行处理
        $db = $this->connect($this->wdbConf['host'], $this->wdbConf['user'], $this->wdbConf['pwd'], $this->wdbConf['db']);
        if (!$db || !is_object($db)) {
            return false;   
        }   
        $this->wdbConn = $db;   
        return $this->wdbConn;   
    }   
  
    /**  
     * 获取Slave的读数据连接  
     */  
    private function getDbReadConn(){   
        //如果有可用的Slave连接，随机挑选一台Slave   
        if (is_array($this->rdbConn) && !empty($this->rdbConn)) {   
            $key = array_rand($this->rdbConn);   
            if (isset($this->rdbConn[$key]) && is_object($this->rdbConn[$key])) {   
                return $this->rdbConn[$key];   
            }   
        }   
        //连接到所有Slave数据库，如果没有可用的Slave机则调用Master   
        $arrHost = explode("|", $this->rdbConf['host']);   
        if (!is_array($arrHost) || empty($arrHost)){   
            return $this->getDbWriteConn();   
        }   
        $this->rdbConn = array();   
        foreach($arrHost as $tmpHost){   
            $db = $this->connect($tmpHost, $this->rdbConf['user'], $this->rdbConf['pwd'], $this->rdbConf['db']);   
            if ($db && is_object($db)){   
                $this->rdbConn[] = $db;   
            }   
        }   
        //如果没有一台可用的Slave则调用Master   
        if (!is_array($this->rdbConn) || empty($this->rdbConn)){   
            $this->errorLog("Not availability slave db connection, call master db connection");   
            return $this->getDbWriteConn();   
        }   
        //随机在已连接的Slave机中选择一台
        $key = array_rand($this->rdbConn);   
        if (isset($this->rdbConn[$key])  && is_object($this->rdbConn[$key])){   
            return $this->rdbConn[$key];   
        }   
        //如果选择的slave机器是无效的，并且可用的slave机器大于一台则循环遍历所有能用的slave机器   
        if (count($this->rdbConn) > 1){   
            foreach($this->rdbConn as $conn){   
                if (is_object($conn)){   
                    return $conn;   
                }   
            }   
        }   
        //如果没有可用的Slave连接，则继续使用Master连接   
        return $this->getDbWriteConn();
    }   
  
    /**  
     * 连接到MySQL数据库公共方法  
     */  
    private function connect($dbHost, $dbUser, $dbPasswd, $dbDatabase){   
        //连接数据库主机   
        $db = mysqli_connect($dbHost, $dbUser, $dbPasswd);   
        if (!$db) {   
            $this->errorLog("Mysqli connect ". $dbHost ." failed");   
            return false;   
        }   
        //选定数据库   
        if (!mysqli_select_db($db, $dbDatabase)) {   
            $this->errorLog("select db $dbDatabase failed", $db);   
            return false;   
        }   
        //设置字符集   
        if ($this->isCharset){   
            if ( $this->dbVersion == '' ){   
                $res = mysqli_query($db, "SELECT VERSION()");   
                $this->dbVersion = end(mysqli_fetch_array($res));
            }            
            if ($this->dbCharset!='' && preg_match("/^(5.|4.1)/", $this->dbVersion)){   
                if (mysqli_query($db, "SET NAMES '".$this->dbCharset."'") === false){
                	echo 'xxx';
                    $this->errorLog("Set db_host '$dbHost' charset=". $this->dbCharset ." failed.", $db);   
                    return false;   
                }   
            }   
        }
        return $db;   
    }   
  
    /**  
     * 关闭数据库连接  
     */  
    public function disconnect($dbConn=null, $closeAll=false){   
        //关闭指定数据库连接   
        if ($dbConn && is_object($dbConn)){   
            mysqli_close($dbConn);   
            $dbConn = null;   
        }   
        //关闭所有数据库连接   
        if ($closeAll){   
            if ($this->rdbConn && is_object($this->rdbConn)){   
                mysqli_close($this->rdbConn);   
                $this->rdbConn = null;   
            }   
            if (is_array($this->rdbConn) && !empty($this->rdbConn)){   
                foreach($this->rdbConn as $conn){   
                    if ($conn && is_object($conn)){   
                        mysqli_close($conn);   
                    }   
                }   
                $this->rdbConn = array();   
            }   
        }   
        return true;   
    }   
  
    /**  
     * 选择数据库  
     */  
    public function selectDb($dbName, $dbConn=null){   
        //重新选择一个连接的数据库   
        if ($dbConn && is_object($dbConn)){   
            if (!mysqli_select_db($dbName, $dbConn)){
                $this->errorLog("Select database:$dbName failed.", $dbConn);   
                return false;   
            }   
            return true;   
        }   
        //重新选择所有连接的数据库   
        if ($this->wdbConn && is_object($this->wdbConn)){   
            if (!mysqli_select_db($dbName, $this->wdbConn)){   
                $this->errorLog("Select database:$dbName failed.", $this->wdbConn);   
                return false;   
            }   
        }   
        if (is_array($this->rdbConn && !empty($this->rdbConn))){   
            foreach($this->rdbConn as $conn){   
                if ($conn && is_object($conn)){   
                    if (!mysqli_select_db($dbName, $conn)){   
                        $this->errorLog("Select database:$dbName failed.", $conn);   
                        return false;   
                    }   
                }   
            }   
        }   
        return true;   
    }   
  
    /**  
     * 执行SQL语句（底层操作）  
     */  
    private function _query($sql, $isMaster=false){
        if (trim($sql) == ""){   
            $this->errorLog("Sql query is empty.");   
            return false;   
        }   
        //是否只有一台数据库机器   
        if ($this->singleHost){   
            $isMaster = true;
        }
  
        //获取执行SQL的数据库连接   
        if (!$isMaster){   
            $optType = trim(strtolower(substr(ltrim($sql), 0, 6)));   
        }   
        if ($isMaster || $optType!="select"){
            $dbConn = $this->getDbWriteConn();   
        } else {   
            $dbConn = $this->getDbReadConn();   
        }
        if (!$dbConn || !is_object($dbConn)){   
            $this->isError = true;   
            $this->errMsg  = 'Not availability db connection.';   
            $this->currConn = null;   
  
            $this->errorLog("Not availability db connection. Query SQL:". $sql);   
            if ($this->isExit) {   
                exit;   
            }   
            return false;   
        }   
        //记录执行的SQL   
        if ($this->logSqlLevel){   
            $isLog = false;   
            $logLevel = $this->logSqlLevel;   
            if ($logLevel==1 && in_array($optType, array('insert'))){   
                $isLog = true;   
            }   
            if ($logLevel==2 && in_array($optType, array('insert', 'update'))){   
                $isLog = true;   
            }   
            if ($logLevel==3 && in_array($optType, array('insert', 'update', 'delete'))){   
                $isLog = true;   
            }   
            if ($logLevel==4 && in_array($optType, array('insert', 'update', 'delete', 'select'))){   
                $isLog = true;   
            }   
            if ($isLog){   
                $this->errorLog($sql);   
            }   
        }   
  
        //执行查询   
        $this->currConn = $dbConn;   
        $this->dbSql = $sql;   
        $this->dbResult = null;   
        if ($this->isRuntime){   
            $startTime = $this->getTime();   
            $this->dbResult = @mysqli_query($dbConn, $sql);   
            $this->runTime = $this->getTime() - $startTime;   
        } else {   
            $this->dbResult = @mysqli_query($dbConn, $sql);   
        }   
        if ($this->dbResult === false){   
            $this->isError = true;   
            $this->errMsg  = 'MySQL errno:'. mysqli_errno($dbConn) .', error:'. mysqli_error($dbConn);   
            $this->errorLog("Query sql failed. SQL:".$sql, $dbConn);   
            if ($this->isExit) {   
                exit;   
            }   
            return false;   
        }   
  
        $this->isError = false;   
        $this->errMsg  = '';   
  
        return true;   
    }   
  
    /**  
     * 错误日志  
     */  
    private function errorLog($msg='', $conn=null){   
        if (!$this->isLog){   
            return;   
        }   
        if ($msg=='' && !$conn) {   
            return false;   
        }   
        $log = "MySQL Error: $msg";   
        if ($conn && is_object($conn)) {   
            $log .= " mysqli_msg:". mysqli_error($conn);   
        }   
        $log .= " [". date("Y-m-d H:i:s") ."]";   
        if ($this->logFile != ''){   
            error_log($log ."\n", 3, $this->logFile);   
        } else {   
            error_log($log);   
        }   
        return true;   
    }   
  
  
  
    //--------------------------   
    //   
    //       数据获取接口   
    //   
    //--------------------------   
    /**  
     * 获取SQL执行的全部结果集(二维数组)  
     *  
     * @param string $sql 需要执行查询的SQL语句  
     * @return 成功返回查询结果的二维数组,失败返回false, 数据空返回NULL  
     */  
    public function getAll($sql, $isMaster=false){
        if (!$this->_query($sql, $isMaster)){
            return false;   
        }   
        $this->dbRecord = array();   
        while ($row = @mysqli_fetch_array($this->dbResult, $this->fetchMode)) {   
            $this->dbRecord[] = $row;   
        }
		if (is_resource($this->dbResult)){
			@mysqli_free_result($this->dbResult);   
		}
        if (!is_array($this->dbRecord) || empty($this->dbRecord)){   
            return NULL;   
        }   
        return $this->dbRecord;   
    }   
  
    /**  
     * 获取单行记录(一维数组)  
     *  
     * @param string $sql 需要执行查询的SQL语句  
     * @return 成功返回结果记录的一维数组,失败返回false, 数据空返回NULL  
     */  
    public function getRow($sql, $isMaster=false){   
        if (!$this->_query($sql, $isMaster)){   
            return false;   
        }   
        $this->dbRecord = array();   
        $this->dbRecord = @mysqli_fetch_array($this->dbResult, $this->fetchMode);   
		if (is_resource($this->dbResult)){
			@mysqli_free_result($this->dbResult);   
		} 
        if (!is_array($this->dbRecord) || empty($this->dbRecord)){   
            return NULL;   
        }   
        return $this->dbRecord;   
    }   
  
    /**  
     * 获取一列数据(一维数组)  
     *  
     * @param string $sql 需要获取的字符串  
     * @param string $field 需要获取的列,如果不指定,默认是第一列  
     * @return 成功返回提取的结果记录的一维数组,失败返回false, 数据空返回NULL  
     */  
    public function getCol($sql, $field='', $isMaster=false){   
        if (!$this->_query($sql, $isMaster)){   
            return false;   
        }   
        $this->dbRecord = array();   
        while($row = @mysqli_fetch_array($this->dbResult, $this->fetchMode)){   
            if (trim($field) == ''){   
                $this->dbRecord[] = current($row);   
            } else {   
                $this->dbRecord[] = $row[$field];   
            }   
        }   
		if (is_resource($this->dbResult)){
			@mysqli_free_result($this->dbResult);   
		} 
        if (!is_array($this->dbRecord) || empty($this->dbRecord)){   
            return NULL;   
        }   
        return $this->dbRecord;   
    }   
  
    /**  
     * 获取一个数据(当条数组)  
     *  
     * @param string $sql 需要执行查询的SQL  
     * @return 成功返回获取的一个数据,失败返回false, 数据空返回NULL  
     */  
    public function getOne($sql, $field='', $isMaster=false){   
        if (!$this->_query($sql, $isMaster)){   
            return false;   
        }   
        $this->dbRecord = array();   
        $row = @mysqli_fetch_array($this->dbResult, $this->fetchMode);   
		if (is_resource($this->dbResult)){
			@mysqli_free_result($this->dbResult);   
		} 
        if (!is_array($row) || empty($row)){   
            return NULL;   
        }   
        if (trim($field) != ''){   
            $this->dbRecord = $row[$field];   
        }else{   
            $this->dbRecord = current($row);   
        }   
        return $this->dbRecord;   
    }   
  
  
  
    /**  
     * 获取指定各种条件的记录  
     *  
     * @param string $table 表名(访问的数据表)  
     * @param string $field 字段(要获取的字段)  
     * @param string $where 条件(获取记录的条件语句,不包括WHERE,默认为空)  
     * @param string $order 排序(按照什么字段排序,不包括ORDER BY,默认为空)  
     * @param string $limit 限制记录(需要提取多少记录,不包括LIMIT,默认为空)  
     * @param bool $single 是否只是取单条记录(是调用getRow还是getAll,默认是false,即调用getAll)  
     * @return 成功返回记录结果集的数组,失败返回false  
     */  
    public function getRecord($table, $field='*', $where='', $order='', $limit='', $single=false, $isMaster=false){   
        $sql = "SELECT $field FROM $table";   
        $sql .= trim($where)!='' ? " WHERE $where " : $where;   
        $sql .= trim($order)!='' ? " ORDER BY $order " : $order;   
        $sql .= trim($limit)!='' ? " LIMIT $limit " : $limit;   
        if ($single){   
            return $this->getRow($sql, $isMaster);   
        }   
        return $this->getAll($sql, $isMaster);   
    }   
  
    /**  
     * 获取指点各种条件的记录(跟getRecored类似)  
     *  
     * @param string $table 表名(访问的数据表)  
     * @param string $field 字段(要获取的字段)  
     * @param string $where 条件(获取记录的条件语句,不包括WHERE,默认为空)  
     * @param array $order_arr 排序数组(格式类似于: array('id'=>true), 那么就是按照ID为顺序排序, array('id'=>false), 就是按照ID逆序排序)  
     * @param array $limit_arr 提取数据的限制数组()  
     * @return unknown  
     */  
    public function getRecordByWhere($table, $field='*', $where='', $arrOrder=array(), $arrLimit=array(), $isMaster=false){   
        $sql = " SELECT $field FROM $table ";   
        $sql .= trim($where)!='' ? " WHERE $where " : $where;   
        if (is_array($arrOrder) && !empty($arrOrder)){   
            $arrKey = key($arrOrder);   
            $sql .= " ORDER BY $arrKey " . ($arrOrder[$arrKey] ? "ASC" : "DESC");   
        }   
        if (is_array($arrLimit) && !empty($arrLimit)){   
            $startPos = intval(array_shift($arrLimit));   
            $offset = intval(array_shift($arrLimit));   
            $sql .= " LIMIT $startPos,$offset ";   
        }   
        return $this->getAll($sql, $isMaster);   
    }   
  
    /**  
     * 获取指定条数的记录  
     *  
     * @param string $table 表名  
     * @param int $startPos 开始记录  
     * @param int $offset 偏移量  
     * @param string $field 字段名  
     * @param string $where 条件(获取记录的条件语句,不包括WHERE,默认为空)  
     * @param string $order 排序(按照什么字段排序,不包括ORDER BY,默认为空)  
     * @return 成功返回包含记录的二维数组,失败返回false  
     */  
    public function getRecordByLimit($table, $startPos, $offset, $field='*', $where='', $oder='', $isMaster=false){   
        $sql = " SELECT $field FROM $table ";   
        $sql .= trim($where)!='' ? " WHERE $where " : $where;   
        $sql .= trim($order)!='' ? " ORDER BY $order " : $order;   
        $sql .= " LIMIT $startPos,$offset ";   
        return $this->getAll($sql, $isMaster);   
    }   
  
    /**  
     * 获取排序记录  
     *  
     * @param string $table 表名  
     * @param string $orderField 需要排序的字段(比如id)  
     * @param string $orderMethod 排序的方式(1为顺序, 2为逆序, 默认是1)  
     * @param string $field 需要提取的字段(默认是*,就是所有字段)  
     * @param string $where 条件(获取记录的条件语句,不包括WHERE,默认为空)  
     * @param string $limit 限制记录(需要提取多少记录,不包括LIMIT,默认为空)  
     * @return 成功返回记录的二维数组,失败返回false  
     */  
    public function getRecordByOrder($table, $orderField, $orderMethod=1, $field='*', $where='', $limit='', $isMaster=false){   
        //$order_method的值为1则为顺序, $order_method值为2则2则是逆序排列   
        $sql = " SELECT $field FROM $table ";   
        $sql .= trim($where)!='' ? " WHERE $where " : $where;   
        $sql .= " ORDER BY $orderField " . ( $orderMethod==1 ? "ASC" : "DESC");   
        $sql .= trim($limit)!='' ? " LIMIT $limit " : $limit;   
        return $this->getAll($sql, $isMaster);   
    }   
  
    /**  
     * 分页查询(限制查询的记录条数)  
     *  
     * @param string $sql 需要查询的SQL语句  
     * @param int $startPos 开始记录的条数  
     * @param int $offset 每次的偏移量,需要获取多少条  
     * @return 成功返回获取结果记录的二维数组,失败返回false  
     */  
    public function limit_query($sql, $startPos, $offset, $isMaster=false){   
        $start_pos = intval($startPos);   
        $offset = intval($offset);   
        $sql = $sql . " LIMIT $startPos,$offset ";   
        return $this->getAll($sql, $isMaster);   
    }   
  
  
    //--------------------------   
    //   
    //     无数据返回操作接口   
    //   
    //--------------------------   
    /**  
     * 执行执行非Select查询操作  
     *  
     * @param string $sql 查询SQL语句  
     * @return bool  成功返回SQL影响的数据函数，失败返回false
     */  
    public function execute($sql){   
        if (!$this->_query($sql, true)){   
            return false;   
        }
	    return @mysqli_affected_rows($this->currConn); 
    }  
  
    /**  
     * 自动执行操作(针对Insert/Update操作)  
     *  
     * @param string $table 表名  
     * @param array $field_array 字段数组(数组中的键相当于字段名,数组值相当于值, 类似 array( 'id' => 100, 'user' => 'heiyeluren')  
     * @param int $mode 执行操作的模式 (是插入还是更新操作, 1是插入操作Insert, 2是更新操作Update)  
     * @param string $where 如果是更新操作,可以添加WHERE的条件  
     * @return bool 执行成功返回影响的行数, 失败返回false  
     */  
    public function autoExecute($table, $arrField, $mode, $where='', $isMaster=false){   
        if ($table=='' || !is_array($arrField) || empty($arrField)){   
            return false;   
        }   
        //$mode为1是插入操作(Insert), $mode为2是更新操作   
        if ($mode == 1){   
            $sql = " INSERT INTO `$table` SET ";   
        } elseif ($mode == 2) {   
            $sql = " UPDATE `$table` SET ";   
        } else {   
            $this->errorLog("Operate type '$mode' is error, in call DB::autoExecute process table $table.");   
            return false;   
        }   
        foreach ($arrField as $key => $value){   
            $sql .= "`$key`='$value',";   
        }   
        $sql = rtrim($sql, ',');   
        if ($mode==2 && $where!=''){   
            $sql .= "WHERE $where";   
        }   
        return $this->execute($sql);   
    }
    
    /**  
     * 获取某个表的Count  
     *   
     * @param array $arrField 需要处理的where条件的key，value  
     * @param string $table 需要获取的表名  
     * @return 成功返回获取的一个整数值,失败返回false, 数据空返回NULL  
     */  
    public function getCount($arrField, $notFields, $table){   
        $sql = "SELECT COUNT(1) as cnt FROM ".$table." WHERE ";   
        foreach ($arrField as $key => $value)    {   
            $sql .= " `$key`='$value' AND ";   
        }   
        if (!empty($notFields)) {   
            foreach ($arrField as $key => $value)    {   
                $sql .= " `$key`!='$value' AND ";   
            }   
        }   
        $sql .= " 1 ";   
        $row = $this->getOne($sql);   
        if ($row===NULL || $row===false){   
            return $row;   
        }   
        if (is_array($row)){   
            return (int)current($row);   
        }   
        return (int)$row;   
    }     
  
    /**  
     * 锁表表  
     *  
     * @param string $tblName 需要锁定表的名称  
     * @return mixed 成功返回执行结果，失败返回错误对象  
     */  
    public function lockTable($tblName){   
        return $this->_query("LOCK TABLES $tblName", true);   
    }   
  
    /**  
     * 对锁定表进行解锁  
     *  
     * @param string $tblName 需要锁定表的名称  
     * @return mixed 成功返回执行结果，失败返回错误对象  
     */  
    public function unlockTable($tblName){   
        return $this->_query("UNLOCK TABLES $tblName", true);   
    }   
  
    /**  
     * 设置自动提交模块的方式（针对InnoDB存储引擎）  
     * 一般如果是不需要使用事务模式，建议自动提交为1，这样能够提高InnoDB存储引擎的执行效率，如果是事务模式，那么就使用自动提交为0  
     *  
     * @param bool $autoCommit 如果是true则是自动提交，每次输入SQL之后都自动执行，缺省为false  
     * @return mixed 成功返回true，失败返回错误对象  
     */  
    public function setAutoCommit($autoCommit = false){   
        $autoCommit = ( $autoCommit ? 1 : 0 );   
        return $this->_query("SET AUTOCOMMIT = $autoCommit", true);   
    }   
  
    /**  
     * 开始一个事务过程（针对InnoDB引擎，兼容使用 BEGIN 和 START TRANSACTION）  
     *  
     * @return mixed 成功返回true，失败返回错误对象  
     */  
    public function startTransaction(){   
        if (!$this->_query("BEGIN")){   
            return $this->_query("START TRANSACTION", true);   
        }   
    }   
  
    /**  
     * 提交一个事务（针对InnoDB存储引擎）  
     *  
     * @return mixed 成功返回true，失败返回错误对象  
     */  
    public function commit(){   
        if (!$this->_query("COMMIT", true)){   
            return false;   
        }   
        return $this->setAutoCommit( true );   
    }   
  
    /**  
     * 发生错误，会滚一个事务（针对InnoDB存储引擎）  
     *  
     * @return mixed 成功返回true，失败返回错误对象  
     */  
  
    public function rollback(){   
        if (!$this->_query("ROLLBACK", true)){   
            return false;   
        }   
        return $this->setAutoCommit( true );   
    }   
    
    
    
  

  
    //--------------------------------   
    //   
    //     数据库操作简洁接口   
    //   
    //--------------------------------   
    
    /**  
     * 执行执行非Select查询操作  
     *  
     * @param string $sql 查询SQL语句  
     * @return bool  成功返回SQL影响的数据函数，失败返回false
     */ 
    public function exec($sql){
		return $this->execute($sql);
    }
  
    /**  
     * 查询结果：二维数组返回  
     *   
     * @param string $sql 需要执行的SQL  
     * @return mixed 如果是select操作成功返回二维数组，失败返回false，数据空返回NULL；
     * 			     如果是update/delete/insert操作，成功返回影响的行数，失败返回false
     */  
    public function query($sql){   
        $optType = trim(strtolower(substr(ltrim($sql), 0, 6)));   
        if (in_array($optType, array('update', 'insert', 'delete'))){   
            return $this->execute($sql);   
        }
        return $this->getAll($sql);   
    }   
  
    /**  
     * 插入数据  
     *  
     * @param array $field_array 字段数组(数组中的键相当于字段名,数组值相当于值, 类似 array( 'id' => 100, 'user' => 'heiyeluren')  
     * @param string $table 表名  
     * @return mixed 执行成功返回影响的行数, 失败返回false  
     */  
    public function insert($arrField, $table){   
        return $this->autoExecute($table, $arrField, 1);   
    }   
  
    /**  
     * 更新数据  
     *  
     * @param array $field_array 字段数组(数组中的键相当于字段名,数组值相当于值, 类似 array( 'id' => 100, 'user' => 'heiyeluren')  
     * @param string $table 表名  
     * @param string $where 如果是更新操作,可以添加WHERE的条件  
     * @return mixed 执行成功返回影响的行数, 失败返回false  
     */  
    public function update($arrField, $where, $table){   
        if (trim($where) == ''){   
            return false;   
        }   
        return $this->autoExecute($table, $arrField, 2, $where);   
    }
    
    /**
     * 删除数据 (操作危险，谨慎使用)
     *
     * @param mixed $where 	需要Where的数据，如果是一个字符串则直接拼接为 WHERE 条件后面的字符串
     * 						如果是一个数组，则传递一个一维数组，传递where条件的 字段 => 值 的方式
     * @param string $table 需要删除数据的表名
     * @return mixed 执行成功返回影响的行数, 失败返回false  
     */
    function delete($where, $table){
    	if (empty($where)){
    		return false;
    	}
        $sql = "DELETE FROM ".$table." WHERE ";
        if (is_string($where)){
        	$sql .= $where;
        } elseif (is_array($where)){
	        foreach ($where as $key => $value)    {   
	            $sql .= " `$key`='$value' AND ";   
	        }
	        $sql .= " 1 ";
        } else {
        	return false;
        }
		return $this->execute($sql);   	
    }
      
  
    /**  
     * 获取某个表的Count  
     *   
     * @param array $arrField 需要处理的where条件的key，value  
     * @param string $table 需要获取的表名  
     * @return 成功返回获取的一个整数值,失败返回false, 数据空返回NULL  
     */  
    public function count($arrField, $notFields, $table){ 
    	return $this->getCount($arrField, $notFields, $table);
    }

  
  
  
    //--------------------------   
    //   
    //    其他数据操作接口   
    //   
    //--------------------------   
  
    /**  
     * 获取上次插入操作的的ID  
     *  
     * @return int 如果没有连接或者查询失败,返回0, 成功返回ID  
     */  
    public function getLastId(){   
        $dbConn = $this->getDbWriteConn();   
        if (($lastId = mysqli_insert_id($dbConn)) > 0){   
            return $lastId;   
        }   
        return $this->getOne("SELECT LAST_INSERT_ID()", '', true);   
    }   
  
    /**  
     * 获取记录集里面的记录条数 (用于Select操作)  
     *  
     * @return int 如果上一次无结果集或者记录结果集为空,返回0, 否则返回结果集数量  
     */  
    public function getNumRows($res=null){   
        if (!$res || !is_resource($res)){   
            $res = $this->dbResult;   
        }   
        return mysqli_num_rows($res);   
    }   
  
    /**  
     * 获取受到影响的记录数量 (用于Update/Delete/Insert操作)  
     *  
     * @return int 如果没有连接或者影响记录为空, 否则返回影响的行数量  
     */  
    public function getAffectedRows(){   
        $dbConn = $this->getDbWriteConn();   
        if ( ($affetedRows = mysqli_affected_rows($dbConn)) <= 0){   
            return $affetedRows;   
        }   
        return $this->getOne("SELECT ROW_COUNT()", "", true);           
    }   
    
  
  
  
  
    //--------------------------   
    //   
    //    相应配合操作接口   
    //   
    //--------------------------   
  
    /**  
     * 获取最后一次查询的SQL语句  
     *  
     * @return string 返回最后一次查询的SQL语句  
     */  
    public function getLastSql(){   
        return $this->dbSql;   
    }   
  
    /**  
     * 返回SQL最后操作的数据库记录结果集  
     *  
     * @return mixed 最后结果集，可能是数组或者普通单个元素值  
     */  
    public function getDBRecord(){   
        return $this->dbRecord;   
    }   
  
    /**  
     * 获取当前操作的数据库连接资源  
     *  
     * @return resouce 返回当前正在执行操作的数据库链接资源  
     */  
    public function getCurrConnection(){   
        return $this->currConn;   
    }   
  
    /**  
     * SQL 执行是否出错  
     *   
     * @return bool   
     */  
    public function isError(){   
        return $this->isError;   
    }   
  
    /**  
     * SQL 执行错误消息  
     *   
     * @return string  
     */  
    public function getError(){   
        return $this->errMsg;   
    }   
  
    /**  
     * 获取执行时间  
     *  
     * @return float  
     */  
    public function getRunTime(){   
        if ($this->isRuntime){   
            return sprintf("%.6f sec",$this->runTime);   
        }   
        return 'NULL';   
    }   
  
  
    /**  
     * 获取当前时间函数  
     *  
     * @param void  
     * @return float $time  
     */  
    public function getTime(){   
        list($usec, $sec) = explode(" ", microtime());   
        return ((float)$usec + (float)$sec);   
    }  
	

	/**
	 * 获取 real_escape_string 的字符串
	 *
	 * @param string $str 需要过滤处理的字符串
	 * @return string
	 */
	function getEscapeString($str){
		if (get_magic_quotes_gpc()){
			$str = stripslashes($str);
		}
		return mysqli_real_escape_string($this->currConn, $str);
	}
  
  
 
  


    //--------------------------   
    //   
    //    开发调试监控接口   
    //   
    //--------------------------   


	/**
	 * 调试信息内部输出接口
	 *
	 * @param string $title 需要输出的标题
	 * @param array $index 需要输出的内容数组（二维数组）
	 * @return void
	 */
	private function _debug_print_table($title, $index, $content_pre = false){
        echo "<b>".$title."</b>\n";
		//二维数组
		if (isset($index[0])){
			echo "<table border=1 width=1000><tr bgcolor=#D0D0D0>";   
			foreach($index[0] as $k => $v){   
				echo "<td>$k</td>";   
			} 
			echo "</tr>";   
			foreach($index as $v){   
				echo "<tr>";   
				foreach($v as $_v){   
					$_v = $_v == NULL ? '&nbsp;' : $_v;   
					echo !$content_pre ? "<td>$_v</td>" : "<td><pre>$_v</pre></td>";   
				}   
				echo "</tr>";   
			} 
	        echo "</tr></table><br /><br />";  
		}
		//一维数组
		else {
            echo "<table border=1 width=1000><tr bgcolor=#D0D0D0>";   
            foreach($index as $k => $v){   
                echo "<td>$k</td>";   
            }   
            echo "</tr><tr>";   
            foreach($index as $k => $v){   
                $v = $v == NULL ? '&nbsp;' : $v;   
                echo !$content_pre ? "<td>$v</td>" : "<td><pre>$v</pre></td>";  
            }   
            echo "</tr></table><br /><br />";   
		}
	}
  
  
    /**  
     * 输出数据表结构信息  
     *  
     * @param void  
     * @return void  
     */  
    private function debug_table_info($table_name = ''){
		 $curr_sql = $this->getLastSql();

        //SQL性能信息   
        echo "<br /><br /><hr size=1>";   
        $explain = $this->getAll("explain ".$curr_sql);
        if (!empty($explain)){
			$this->_debug_print_table('SQL Explain:', $explain);
        }
		
		//总体SQL详细操作性能信息
		$version = $this->getOne("select version()");
		if (preg_match("/^(5.)/", $version) && substr($version,0,6) >= '5.0.37'){
			if ($this->execute("set profiling = 1")){
				$this->execute($curr_sql);
				$index = $this->getAll("show profile cpu,block io,memory,swaps for query 1");
				$this->execute("set profiling = 0");
				$this->_debug_print_table("SQL Performance Profiling:", $index);
				$index = $this->getAll("show profiles");
				$this->_debug_print_table("", $index);
			}
		}

		//获取刚才操作的表名
        $table_name = isset($explain[0]) ? $explain[0]['table'] : $explain['table'];   
        if ($table_name == ''){   
            return;   
        }   
  
        //索引列表   
        $index = $this->getAll("show index from $table_name"); 
		$this->_debug_print_table("Table '$table_name' Index:", $index);
    
        //表结构   
        $index = $this->getAll("desc $table_name");   
		$this->_debug_print_table("Table '$table_name' Fileds:", $index);
  
        //创建表语句   
        $explain = $this->getRow("show create table $table_name");   
        if (!empty($explain)){   
			$this->_debug_print_table("Table '$table_name' Create:", $explain, true);        
		}      
    }   
  
    /**  
     * 输出数据库服务器运行信息  
     *  
     * @param void  
     * @return void  
     */  
    private function debug_server_info(){   
        echo "<br /><br /><hr size=1><br />";   
        //进程信息   
        $index = $this->getAll("show full processlist");   
		$this->_debug_print_table("Database Process List", $index);  

        //变量信息   
        $index = $this->getAll("show global variables");   
		$this->_debug_print_table("Database Variables List", $index); 
  
        //状态信息   
        $index = $this->getAll("show global status");   
		$this->_debug_print_table("Database Status Info", $index);  

        //服务器打开表信息   
        $index = $this->getAll("show open tables");   
		$this->_debug_print_table("Database Open tables List", $index); 		
	}   

  
     /**  
     * 输出所有类属性和值  
     *   
     * @return void  
     */ 
    private function debug_class_var(){   
        $class = get_class($this);   
        //其他类属性   
        echo "<br /><b>$class Attribute:</b> <pre>\n";   
        echo "<hr size=1><br /><br />";   
        $vars = get_object_vars($this);   
        print_r($vars);            
        echo "</pre>\n<br /><br />";         
    }   
  
  
    /**  
     * 输出所有类方法名  
     *   
     * @return void  
     */  
    private function debug_class_method() {   
        $class = get_class($this);   
        $arr = get_class_methods($class);   
        echo "<h3>$class method list</h3><br />\n";   
        foreach ($arr as $method) {   
            echo "\t  $method() <br />\n";   
        }   
    }   
  
  
    /**  
     * 输出所有类属性和SQL  
     *  
     * @param bool $format 使用哪种打印方式， false为 print_r, true为var_dump  
     * @return void  
     */  
    private function debug_sql_info(){   
        //基本信息   
        $mysqli_err = $this->getError() == '' ? 'NULL' : $this->getError();   
        echo "<h3>DBCommon Debug info</h3> <hr size=1><br />\n";   
        echo "<b>Execute SQL&nbsp;: </b> ". $this->getLastSql() ."  <br />\n";   
        echo "<b>MySQL Error&nbsp;: </b> ". $mysqli_err ."  <br />\n";   
        echo "<b>SQL Runtime&nbsp;: </b> ". $this->getRunTime() ." <br />\n";   
    }   
  
    /**  
     * 调试函数  
     *  
     * @param bool $format 使用哪种打印方式， false为 print_r, true为var_dump  
     * @return void  
     */  
    public function debug($level = 1){   
        switch($level){   
            case 0: $this->debug_class_var(); $this->debug_class_method(); break;   
            case 1: $this->debug_sql_info(); break;   
            case 2: $this->debug_sql_info(); $this->debug_table_info(); break;   
            case 3: $this->debug_sql_info(); $this->debug_table_info(); $this->debug_server_info(); break;   
            default: $this->debug_sql_info();   
        }   
    }   
  
    /**  
     * 调试函数: 打印类属性  
     *  
     * @param void  
     * @return void  
     */  
    public function v(){   
        $this->debug_class_var();   
    }   
  
    /**  
     * 调试函数: 打印类方法  
     *  
     * @param void  
     * @return void  
     */  
    public function m(){   
        $this->debug_class_method();   
    }   
  
  
}   

