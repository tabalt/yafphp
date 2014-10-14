<?php
/**
 * 日志类
 * @author tabalt
 */
class Logger {

    /**
     * 日志文件名模板
     * @author tabalt
     * @var string
     */
    private static $fileTpl = null;

    /**
     * 日志内容模板
     * @author tabalt
     * @var string
     */
    private static $contentTpl = null;

    /**
     * 日志类型列表
     * @author tabalt
     * @var array
     */
    private static $typeList = array(
        'trace', 
        'debug', 
        'info', 
        'warning', 
        'error', 
        'notice', 
        'fatal', 
        'sql', 
        'api', 
        'cache', 
        'queue', 
        'exception', 
        'framework'
    );

    /**
     * 解析模板
     * @author tabalt
     * @param string $type
     * @param string $template
     * @return $template
     */
    private static function parseTemplate($type, $template) {
        $search = array(
            '%type', 
            '%n'
        );
        $replace = array(
            $type, 
            "\n"
        );
        $template = str_replace($search, $replace, $template);
        //时间处理
        $template = preg_replace('/%d{(.*)}/e', "date('\\1')", $template);
        return $template;
    }

    /**
     * 异步写入文件
     * @author tabalt
     * @param string $file
     * @param string $content
     * @return void
     */
    private static function writeToFile($fileName, $content, $type = 'w+') {
        //兼容sae写入kvdb
        if (defined('SAE_TMP_PATH')) {
            file_put_contents('saekv://' . $fileName, $content, FILE_APPEND);
            return ;
        }
        $fileHandle = fopen($fileName, $type);
        if ($fileHandle) {
            $startTime = microtime(true);
            do {
                $locked = flock($fileHandle, LOCK_EX);
                if (!$locked) {
                    usleep(round(rand(0, 100) * 1000));
                }
            } while ((!$locked) && ((microtime(true) - $startTime) < 1000));
            if ($locked) {
                fwrite($fileHandle, $content);
                flock($fileHandle, LOCK_UN);
            }
            fclose($fileHandle);
        }
    }

    /**
     * 设置配置
     * @author tabalt
     * @param array $config
     * @return void
     */
    public static function setConfig($config = array()) {
        //兼容sae平台的写入路径
        if (defined('SAE_TMP_PATH')) {
            $logPath = "saekv://log/";;
        } else {
            $logPath = APPLICATION_PATH . '/log/';
        }
        if (!isset($config['file_tpl']) || empty($config['file_tpl'])) {
            if (self::$fileTpl === null) {
                self::$fileTpl = $logPath . '%d{Ymd}/%type.log';
            }
        } else {
            self::$fileTpl = $logPath . $config['file_tpl'];
        }
        if (!isset($config['content_tpl']) || empty($config['content_tpl'])) {
            if (self::$contentTpl === null) {
                self::$contentTpl = '%d{Y-m-d H:i:s} [%type] %content in %file at %line%n';
            }
        } else {
            self::$contentTpl = $config['content_tpl'];
        }
    }

    /**
     * 写入日志
     * @author tabalt
     * @param string $type
     * @param string $content
     * @return void
     */
    public static function write($type, $content, $self = null) {
        //验证所需成员属性
        if (empty(self::$contentTpl) || empty(self::$fileTpl)) {
            self::setConfig();
        }
        //过滤日志类型
        if (!in_array(strtolower($type), self::$typeList)) {
            $type = 'info';
        }
        //获取back trace
        $debugBacktraceList = debug_backtrace();
        //验证是否为类内调用
        if ($self === true && isset($debugBacktraceList[1])) {
            //如果是类内调用, 取下标为1的元素
            $debugBacktrace = $debugBacktraceList[1];
        } else {
            //如果非类内调用, 取下标为0的元素
            $debugBacktrace = $debugBacktraceList[0];
        }
        //替换日志内容
        $content = str_replace('%content', $content, self::parseTemplate(strtoupper($type), self::$contentTpl));
        $search = array(
            '%file', 
            '%line'
        );
        $replace = array(
            $debugBacktrace['file'], 
            $debugBacktrace['line']
        );
        $content = str_replace($search, $replace, $content);
        $fileName = self::parseTemplate($type, self::$fileTpl);
        $dirName = dirname($fileName);
        if (!is_dir($dirName)) {
            mkdir($dirName);
        }
        //写入文件
        self::writeToFile($fileName, $content, 'a');
    }

    /**
     * 写入trace类型日志
     * @author tabalt
     * @see Logger::write
     * @param string $content
     * @return void
     */
    public static function trace($content) {
        self::write('trace', $content, true);
    }

    /**
     * 写入debug类型日志
     * @author tabalt
     * @see Logger::write
     * @param string $content
     * @return void
     */
    public static function debug($content) {
        self::write('debug', $content, true);
    }

    /**
     * 写入info类型日志
     * @author tabalt
     * @see Logger::write
     * @param string $content
     * @return void
     */
    public static function info($content) {
        self::write('info', $content, true);
    }

    /**
     * 写入warning类型日志
     * @author tabalt
     * @see Logger::write
     * @param string $content
     * @return void
     */
    public static function warning($content) {
        self::write('warning', $content, true);
    }

    /**
     * 写入error类型日志
     * @author tabalt
     * @see Logger::write
     * @param string $content
     * @return void
     */
    public static function error($content) {
        self::write('error', $content, true);
    }

    /**
     * 写入notice类型日志
     * @author tabalt
     * @see Logger::write
     * @param string $content
     * @return void
     */
    public static function notice($content) {
        self::write('notice', $content, true);
    }

    /**
     * 写入fatal类型日志
     * @author tabalt
     * @see Logger::write
     * @param string $content
     * @return void
     */
    public static function fatal($content) {
        self::write('fatal', $content, true);
    }

    /**
     * 写入sql类型日志
     * @author tabalt
     * @see Logger::write
     * @param string $content
     * @return void
     */
    public static function sql($content) {
        self::write('sql', $content, true);
    }

    /**
     * 写入cache类型日志
     * @author tabalt
     * @see Logger::write
     * @param string $content
     * @return void
     */
    public static function cache($content) {
        self::write('cache', $content, true);
    }

    /**
     * 写入queue类型日志
     * @author tabalt
     * @see Logger::write
     * @param string $content
     * @return void
     */
    public static function queue($content) {
        self::write('queue', $content, true);
    }

    /**
     * 写入exception类型日志
     * @author tabalt
     * @see Logger::write
     * @param string $content
     * @return void
     */
    public static function exception($content) {
        self::write('exception', $content, true);
    }

    /**
     * 写入api类型日志
     * @author tabalt
     * @see Logger::write
     * @param string $content
     * @return void
     */
    public static function api($content) {
        self::write('api', $content, true);
    }

    /**
     * 写入framework类型日志
     * @author tabalt
     * @see Logger::write
     * @param string $content
     * @return void
     */
    public static function framework($content) {
        self::write('framework', $content, true);
    }

}