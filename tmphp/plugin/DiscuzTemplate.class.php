<?php
/**
 * 模板类 - 使用 Discuz 模板引擎解析
 *
 * @package classes
 * @copyright Copyright (c) 2007-2008 (http://www.tblog.com.cn)
 * @author Akon(番茄红了) <aultoale@gmail.com>
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */


class DiscuzTemplate
{

    const DIR_SEP = DIRECTORY_SEPARATOR;

    /**
     * 模板实例
     *
     * @staticvar
     * @var object Template
     */
    protected static $_instance;

    /**
     * 模板参数信息
     *
     * @var array
     */
    protected $_options = array();

    /**
     * 单件模式调用方法
     *
     * @static
     * @return object Template
     */
    public static function getInstance()
    {
        if (!self::$_instance instanceof self)
            self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * 构造方法
     *
     * @return void
     */
    private function __construct()
    {
        $this->_options = array(
            'template_dir' => 'templates' . self::DIR_SEP, //模板文件所在目录
            'cache_dir' => 'templates' . self::DIR_SEP . 'cache' . self::DIR_SEP, //缓存文件存放目录
            'auto_update' => false, //当模板文件改动时是否重新生成缓存
            'cache_lifetime' => 0, //缓存生命周期(分钟)，为 0 表示永久
        );
    }

    /**
     * 设定模板参数信息
     *
     * @param  array $options 参数数组
     * @return void
     */
    public function setOptions(array $options)
    {
        foreach ($options as $name => $value)
            $this->set($name, $value);
    }

    /**
     * 设定模板参数
     *
     * @param  string $name  参数名称
     * @param  mixed  $value 参数值
     * @return void
     */
    public function set($name, $value)
    {
        switch ($name) {
            case 'template_dir':
                $value = $this->_trimpath($value);
                if (!file_exists($value))
                    $this->_throwException("未找到指定的模板目录 \"$value\"");
                $this->_options['template_dir'] = $value;
                break;
            case 'cache_dir':
                $value = $this->_trimpath($value);
                if (!file_exists($value))
                    $this->_throwException("未找到指定的缓存目录 \"$value\"");
                $this->_options['cache_dir'] = $value;
                break;
            case 'auto_update':
                $this->_options['auto_update'] = (boolean) $value;
                break;
            case 'cache_lifetime':
                $this->_options['cache_lifetime'] = (float) $value;
                break;
            default:
                $this->_throwException("未知的模板配置选项 \"$name\"");
        }
    }

    /**
     * 通过魔术方法设定模板参数
     *
     * @see    Template::set()
     * @param  string $name  参数名称
     * @param  mixed  $value 参数值
     * @return void
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * 获取模板文件
     *
     * @param  string $file 模板文件名称
     * @return string
     */
    public function getfile($file)
    {
        $cachefile = $this->_getCacheFile($file);
        if (!file_exists($cachefile))
            $this->cache($file);
        return $cachefile;
    }

    /**
     * 检测模板文件是否需要更新缓存
     *
     * @param  string  $file    模板文件名称
     * @param  string  $md5data 模板文件 md5 校验信息
     * @param  integer $md5data 模板文件到期时间校验信息
     * @return void
     */
    public function check($file, $md5data, $expireTime)
    {
        if ($this->_options['auto_update']
        && md5_file($this->_getTplFile($file)) != $md5data)
            $this->cache($file);
        if ($this->_options['cache_lifetime'] != 0
        && (time() - $expireTime >= $this->_options['cache_lifetime'] * 60))
            $this->cache($file);
    }

    /**
     * 对模板文件进行缓存
     *
     * @param  string  $file    模板文件名称
     * @return void
     */
    public function cache($file)
    {
        $tplfile = $this->_getTplFile($file);

        if (!is_readable($tplfile)) {
            $this->_throwException("模板文件 \"$tplfile\" 未找到或者无法打开");
        }

        //取得模板内容
        $template = file_get_contents($tplfile);

        //过滤 <!--{}-->
        $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);

        //替换语言包变量
        //$template = preg_replace("/\{lang\s+(.+?)\}/ies", "languagevar('\\1')", $template);

        //替换 PHP 换行符
        $template = str_replace("{LF}", "<?=\"\\n\"?>", $template);

        //替换直接变量输出
        $varRegexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)"
                    . "(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
        $template = preg_replace("/\{(\\\$[a-zA-Z0-9_\[\]\'\"\$\.\x7f-\xff]+)\}/s", "<?=\\1?>", $template);
        $template = preg_replace("/$varRegexp/es", "addquote('<?=\\1?>')", $template);
        $template = preg_replace("/\<\?\=\<\?\=$varRegexp\?\>\?\>/es", "addquote('<?=\\1?>')", $template);

        //替换模板载入命令
        $template = preg_replace(
            "/[\n\r\t]*\{template\s+([a-z0-9_]+)\}[\n\r\t]*/is",
            "\r\n<? include(\$template->getfile('\\1')); ?>\r\n",
            $template
        );
        $template = preg_replace(
            "/[\n\r\t]*\{template\s+(.+?)\}[\n\r\t]*/is",
            "\r\n<? include(\$template->getfile(\\1)); ?>\r\n",
            $template
         );

        //替换特定函数
        $template = preg_replace(
            "/[\n\r\t]*\{eval\s+(.+?)\}[\n\r\t]*/ies",
            "stripvtags('<? \\1 ?>','')",
            $template
        );
        $template = preg_replace(
            "/[\n\r\t]*\{echo\s+(.+?)\}[\n\r\t]*/ies",
            "stripvtags('<? echo \\1; ?>','')",
            $template
        );
        $template = preg_replace(
            "/([\n\r\t]*)\{elseif\s+(.+?)\}([\n\r\t]*)/ies",
            "stripvtags('\\1<? } elseif(\\2) { ?>\\3','')",
            $template
        );
        $template = preg_replace(
            "/([\n\r\t]*)\{else\}([\n\r\t]*)/is",
            "\\1<? } else { ?>\\2",
            $template
        );

        //替换循环函数及条件判断语句
        $nest = 5;
        for ($i = 0; $i < $nest; $i++) {
            $template = preg_replace(
                "/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\}[\n\r]*(.+?)[\n\r]*\{\/loop\}[\n\r\t]*/ies",
                "stripvtags('<? if(is_array(\\1)) { foreach(\\1 as \\2) { ?>','\\3<? } } ?>')",
                $template
            );
            $template = preg_replace(
                "/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*(.+?)[\n\r\t]*\{\/loop\}[\n\r\t]*/ies",
                "stripvtags('<? if(is_array(\\1)) { foreach(\\1 as \\2 => \\3) { ?>','\\4<? } } ?>')",
                $template
            );
            $template = preg_replace(
                "/([\n\r\t]*)\{if\s+(.+?)\}([\n\r]*)(.+?)([\n\r]*)\{\/if\}([\n\r\t]*)/ies",
                "stripvtags('\\1<? if(\\2) { ?>\\3','\\4\\5<? } ?>\\6')",
                $template
            );
        }

        //常量替换
        $template = preg_replace(
            "/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/s",
            "<?=\\1?>",
            $template
        );

        //删除 PHP 代码断间多余的空格及换行
        $template = preg_replace("/ \?\>[\n\r]*\<\? /s", " ", $template);

        //其他替换
        $template = preg_replace(
            "/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/e",
            "transamp('\\0')",
            $template
        );
        $template = preg_replace(
            "/\<script[^\>]*?src=\"(.+?)\".*?\>\s*\<\/script\>/ise",
            "stripscriptamp('\\1')",
            $template
        );
        $template = preg_replace(
            "/[\n\r\t]*\{block\s+([a-zA-Z0-9_]+)\}(.+?)\{\/block\}/ies",
            "stripblock('\\1', '\\2')",
            $template
        );

        //添加 md5 及过期校验
        $md5data = md5_file($tplfile);
        $expireTime = time();
        $template = "<? if (!class_exists('DiscuzTemplate')) die('Access Denied');"
                  . "\$template->getInstance()->check('$file', '$md5data', $expireTime);"
                  . "?>\r\n$template";

        //写入缓存文件
        $cachefile = $this->_getCacheFile($file);
        $makepath = $this->_makepath($cachefile);
        if ($makepath !== true)
            $this->_throwException("无法创建缓存目录 \"$makepath\"");
        file_put_contents($cachefile, $template);
    }

    /**
     * 将路径修正为适合操作系统的形式
     *
     * @param  string $path 路径名称
     * @return string
     */
    protected function _trimpath($path)
    {
        return str_replace(array('/', '\\', '//', '\\\\'), self::DIR_SEP, $path);
    }

    /**
     * 获取模板文件名及路径
     *
     * @param  string $file 模板文件名称
     * @return string
     */
    protected function _getTplFile($file)
    {
        return $this->_trimpath($this->_options['template_dir'] . self::DIR_SEP . $file);
    }

    /**
     * 获取模板缓存文件名及路径
     *
     * @param  string $file 模板文件名称
     * @return string
     */
    protected function _getCacheFile($file)
    {
        $file = preg_replace('/\.[a-z0-9\-_]+$/i', '.cache.php', $file);
        return $this->_trimpath($this->_options['cache_dir'] . self::DIR_SEP . $file);
    }

    /**
     * 根据指定的路径创建不存在的文件夹
     *
     * @param  string  $path 路径/文件夹名称
     * @return string
     */
    protected function _makepath($path)
    {
        $dirs = explode(self::DIR_SEP, dirname($this->_trimpath($path)));
        $tmp = '';
        foreach ($dirs as $dir) {
            $tmp .= $dir . self::DIR_SEP;
            if (!file_exists($tmp) && !@mkdir($tmp, 0777))
                return $tmp;
        }
        return true;
    }

    /**
     * 抛出一个错误信息
     *
     * @param string $message
     * @return void
     */
    protected function _throwException($message)
    {
        throw new Exception($message);
    }

}



/**
 * 模板替换中需要用到的函数
 *
 * @package functions
 * @copyright Copyright (c) 2007-2008 (http://www.tblog.com.cn)
 * @author Akon(番茄红了) <aultoale@gmail.com>
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */

function transamp($template) {
    $template = str_replace('&', '&amp;', $template);
    $template = str_replace('&amp;amp;', '&amp;', $template);
    $template = str_replace('\"', '"', $template);
    return $template;
}

function stripvtags($expr, $statement) {
    $expr = str_replace("\\\"", "\"", preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr));
    $statement = str_replace("\\\"", "\"", $statement);
    return $expr . $statement;
}

function addquote($var) {
    return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var));
}

function stripscriptamp($s) {
    $s = str_replace('&amp;', '&', $s);
    return "<script src=\"$s\" type=\"text/javascript\"></script>";
}

function stripblock($var, $s) {
    $s = str_replace('\\"', '"', $s);
    $s = preg_replace("/<\?=\\\$(.+?)\?>/", "{\$\\1}", $s);
    preg_match_all("/<\?=(.+?)\?>/e", $s, $constary);
    $constadd = '';
    $constary[1] = array_unique($constary[1]);
    foreach($constary[1] as $const) {
        $constadd .= '$__' . $const  .' = ' . $const . ';';
    }
    $s = preg_replace("/<\?=(.+?)\?>/", "{\$__\\1}", $s);
    $s = str_replace('?>', "\n\$$var .= <<<EOF\n", $s);
    $s = str_replace('<?', "\nEOF;\n", $s);
    return "<?\n$constadd\$$var = <<<EOF\n" . $s . "\nEOF;\n?>";
}


