<?php
/**
 * 数据过滤类
 * @author tabalt
 */
class Filter {

    /**
     * 过滤规则列表
     * @author tabalt
     * @var array
     */
    public static $rules;

    /**
     * 返回整型
     * @author tabalt
     * @param mixed $var
     * @return int $var
     */
    public static function int($var) {
        return intval(trim($var));
    }

    /**
     * 返回转义SQL后的字符串
     * @author tabalt
     * @param string $str
     * @return string $str
     */
    public static function escapeSql($str) {
        if (!get_magic_quotes_gpc()) {
            $str = addslashes(trim($str));
        }
        return $str;
    }

    /**
     * 返回转义HTML后的字符串
     * @author tabalt
     * @param string $str
     * @return string $str
     */
    public static function escapeHtml($str) {
        return htmlspecialchars(trim($str), ENT_QUOTES);
    }

    /**
     * 返回转义后的字符串
     * @author tabalt
     * @param string $str
     * @return string $str
     */
    public static function escape($str) {
        return self::escapeHtml(self::escapeSql(trim($str)));
    }

    /**
     * 返回解析转义HTML的字符串
     * @author tabalt
     * @param string $str
     * @return string $str
     */
    public static function unEscapeHtml($str) {
        return htmlspecialchars_decode(trim($str));
    }

    /**
     * 返回去除html标签后的字符串
     * @author tabalt
     * @param string $str
     * @return string $str
     */
    public static function stripTag($str) {
        return strip_tags(trim($str));
    }

    /**
     * 返回URL编码后的字符串
     * @author tabalt
     * @param string $str
     * @return string $str
     */
    public static function urlEncode($str) {
        return urlencode(trim($str));
    }

    /**
     * 返回URL解码后的字符串
     * @author tabalt
     * @param string $str
     * @return string $str
     */
    public static function urlDecode($str) {
        return urldecode(trim($str));
    }

    /**
     * 返回移除XSS代码后的富文本字符串
     * @author tabalt
     * @param string $str
     * @return string $str
     */
    public static function removeXss($str) {
        return Xss::remove(trim($str));
    }

    /**
     * 移除两端空格
     * @author pengming
     * @param $str
     * @return string
     */
    public static function trim($str){
        return trim($str);
    }

    /**
     * 获取过滤的数据
     * @author tabalt
     * @param string $key
     * @param mixed $value
     */
    public static function filterValue($key, $value) {
        //数组
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $data[$k] = self::filterValue($key, $v);
            }
        } else {
            $filterMethod = isset(self::$rules[$key]) ? self::$rules[$key] : 'escapeHtml';
            if ($filterMethod === false) {
                return $value;
            }
            if (!method_exists('Filter', $filterMethod)) {
                $filterMethod = 'escapeHtml';
            }
            $data = self::$filterMethod($value);
        }
        return $data;
    }

    /**
     * 数据过滤
     * @author tabalt
     * @param array $filterRules 过滤规则
     * @param array $data 要过滤的数据
     * @return array $data 过滤后的数据
     */
    public static function execute($filterRules = array(), $data = array()) {
        self::$rules = $filterRules;
        //设置默认数据源
        if (empty($data)) {
            $data = $_POST;
        }
        foreach ($data as $key => $value) {
            $value = str_replace("　", '', trim($value));
            $data[$key] = self::filterValue($key, $value);
        }
        return $data;
    }
}