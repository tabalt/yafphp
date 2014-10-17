<?php
/**
 * 工具类 封装常用功能
 * @author tabalt
 */
class Helper {

    /**
     * 验证Refer，防范csrf攻击
     * @author tabalt
     * @param mixed $whiteHostList 白名单域名或ip
     * @return boolean true/false
     */
    public static function checkReferer($whiteHostList = array()) {
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false;
        if (empty($referer)) {
            return false;
        }
        // referer 必须以 http 或 https 开头
        if (strpos($referer, 'http://') !== 0 && strpos($referer, 'https://') !== 0) {
            return false;
        }
        //设置默认域名
        if (empty($whiteHostList)) {
            $whiteHostList = array(
                $_SERVER['HTTP_HOST']
            );
        } else if (is_string($whiteHostList)) {
            $whiteHostList = array(
                $whiteHostList
            );
        }
        //refer 主机地址判断
        $refererHost = parse_url($referer, PHP_URL_HOST);
        if (is_array($whiteHostList) && in_array($refererHost, $whiteHostList)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 输出http状态码
     * @author tabalt
     * @param intval $code
     */
    public static function httpStatus($code) {
        $codeList = array(
            100 => 'Continue', 
            101 => 'Switching Protocols', 
            200 => 'OK', 
            201 => 'Created', 
            202 => 'Accepted', 
            203 => 'Non-Authoritative Information', 
            204 => 'No Content', 
            205 => 'Reset Content', 
            206 => 'Partial Content', 
            300 => 'Multiple Choices', 
            301 => 'Moved Permanently', 
            302 => 'Found', 
            303 => 'See Other', 
            304 => 'Not Modified', 
            305 => 'Use Proxy', 
            307 => 'Temporary Redirect', 
            400 => 'Bad Request', 
            401 => 'Unauthorized', 
            402 => 'Payment Required', 
            403 => 'Forbidden', 
            404 => 'Not Found', 
            405 => 'Method Not Allowed', 
            406 => 'Not Acceptable', 
            407 => 'Proxy Authentication Required', 
            408 => 'Request Timeout', 
            409 => 'Conflict', 
            410 => 'Gone', 
            411 => 'Length Required', 
            412 => 'Precondition Failed', 
            413 => 'Request Entity Too Large', 
            414 => 'Request-URI Too Long', 
            415 => 'Unsupported Media Type', 
            416 => 'Requested Range Not Satisfiable', 
            417 => 'Expectation Failed', 
            500 => 'Internal Server Error', 
            501 => 'Not Implemented', 
            502 => 'Bad Gateway', 
            503 => 'Service Unavailable', 
            504 => 'Gateway Timeout', 
            505 => 'HTTP Version Not Supported'
        );
        $code = intval($code);
        if (array_key_exists($code, $codeList)) {
            header('HTTP/1.1 ' . $code . ' ' . $codeList[$code]);
            header('status: ' . $code . ' ' . $codeList[$code]);
        }
    }

    /**
     * 获取客户端IP
     * PHP 5 >= 5.2.0
     * @author tabalt
     * @param boolean $onlyRemoteAddr 是否直接返回REMOTE_ADDR
     * @return string/boolean $clientIp
     */
    public static function getClientIp($onlyRemoteAddr = true) {
        //是否直接返回REMOTE_ADDR
        if ($onlyRemoteAddr) {
            return $_SERVER['REMOTE_ADDR'];
        }
        
        //验证是否为非私有IP
        if (filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
            return $_SERVER['REMOTE_ADDR'];
        }
        
        //验证HTTP头中是否有HTTP_X_FORWARDED_FOR
        if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        
        //定义客户端IP
        $clientIp = '';
        
        //获取", "的位置
        $position = strrpos($_SERVER['HTTP_X_FORWARDED_FOR'], ', ');
        
        //验证$position
        if (false === $position) {
            $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $clientIp = substr($_SERVER['HTTP_X_FORWARDED_FOR'], $position + 2);
        }
        
        //验证$clientIp是否为合法IP
        if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
            return $clientIp;
        } else {
            return false;
        }
    }

    /**
     * 将gbk编码转换为utf-8编码,参数可以是字符串或者多维数组
     * @author tabalt
     * @param string | array $transcoding
     */
    public static function gbk2Utf8($transcoding) {
        //转码字符串
        if (is_string($transcoding)) {
            return mb_convert_encoding($transcoding, 'UTF-8', 'GBK');
        }
        //转码数组
        if (is_array($transcoding)) {
            $data = array();
            foreach ($transcoding as $key => $value) {
                $data[$key] = self::gbk2Utf8($value);
            }
            return $data;
        }
    }

    /**
     * 求字符串的长度 中文算一个字符
     * @author tabalt
     * @param string $str
     */
    public static function stringLength($str) {
        if (empty($str)) {
            return 0;
        }
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, 'utf-8');
        } else {
            preg_match_all("/./u", $str, $ar);
            return count($ar[0]);
        }
    }

    /**
     * 获取二维数组的指定列
     * @author tabalt
     * @param array $array 一般为数据库查询出的结果数组
     * @param string $key 
     * @return array $data 处理后的结果
     */
    public static function getArrayColumn($array, $key) {
        $data = array();
        foreach ($array as $info) {
            if (isset($info[$key])) {
                $data[] = $info[$key];
            }
        }
        return $data;
    }

    /**
     * 获取按新key重置的数组
     * @author tabalt
     * @param array $array 一般为数据库查询出的结果数组
     * @param string $key 
     * @return array $data 处理后的结果
     */
    public static function getResetArrayByKey($array, $key) {
        $data = array();
        foreach ($array as $info) {
            if (isset($info[$key])) {
                $data[$info[$key]] = $info;
            }
        }
        return $data;
    }

    /**
     * 获取请求的数据
     * @author tabalt
     * @param string $method
     * @return $data
     */
    public static function requestDataList($method) {
        $methodList = array(
            'get', 
            'post', 
            'server', 
            'cookie', 
            'session'
        );
        $data = array();
        if (in_array($method, $methodList)) {
            if (isset($GLOBALS['_' . strtoupper($method)])) {
                $data = $GLOBALS['_' . strtoupper($method)];
            }
        }
        return $data;
    }

    /**
     * 获取请求参数的值
     * @author tabalt
     * @param string $method
     * @param string $key
     */
    public static function requestValue($method, $key, $defaultValue = false) {
        $data = self::requestDataList($method);
        $value = false;
        if (isset($data[$key])) {
            $value = trim($data[$key]);
        }
        if ($value !== false) {
            return $value;
        } else {
            return $defaultValue;
        }
    }

    /**
     * 从GET参数中取值
     * @author tabalt
     * @param string $key
     * @param mixed $defaultValue
     */
    public static function getValue($key, $defaultValue = false) {
        return self::requestValue('get', $key, $defaultValue);
    }

    /**
     * 从POST参数中取值
     * @author tabalt
     * @param string $key
     * @param mixed $defaultValue
     */
    public static function postValue($key, $defaultValue = false) {
        return self::requestValue('post', $key, $defaultValue);
    }

    /**
     * 创建签名
     * @author tabalt
     * @param array $data 要传递的数据
     * @param string $entry_secret 密钥
     * @return string $sign 生成的签名串
     */
    public static function createSign($data, $entrySecret) {
        ksort($data);
        $sign = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sign .= self::createSign($value, $entrySecret);
            } else {
                $sign .= $value;
            }
        }
        $sign = md5($sign . $entrySecret);
        return $sign;
    }

    /**
     * 生成年月数组
     * @author tabalt
     * @param int $startYear 开始年份
     * @param int $startMonth 开始年的开始月份
     * @param int $endYear 结束年份
     * @param int $endMonth 结束年的 结束月份
     */
    public static function getYearMonthList($startYear, $startMonth, $endYear = 'this', $endMonth = 'this') {
        $yearMonthList = array();
        
        if ($endYear == 'this') {
            $endYear = date('Y');
        }
        if ($endMonth == 'this') {
            $endMonth = date('m');
        }
        for ($year = $startYear; $year <= $endYear; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                if (($year == $startYear) && ($month < $startMonth) || ($year == $endYear) && ($month > $endMonth)) {
                    continue;
                } else {
                    $yearMonthList[$year][] = $month;
                }
            }
        }
        return $yearMonthList;
    }

    /**
     * 获取月份的边界时间戳
     * @param string $key
     * @param int $time
     */
    public static function getMouthBorder($key = false, $time = false) {
        $time = $time ? $time : time();
        $y = date('Y', $time);
        $m = date('m', $time);
        $d = date('t', $time);
        $border = array(
            "start_time" => mktime(0, 0, 0, $m, 1, $y), 
            "end_time" => mktime(23, 59, 59, $m, $d, $y)
        );
        if ($key == 'start_time') {
            return $border['start_time'];
        } else if ($key == 'end_time') {
            return $border['end_time'];
        } else {
            return $border;
        }
    }

    /**
     * 生成年月字符串数组：[201311,201312]
     * @author tabalt
     * @param int $startYear 开始年份
     * @param int $startMonth 开始年的开始月份
     * @param int $endYear 结束年份
     * @param int $endMonth 结束年的 结束月份
     */
    public static function getYearMonthStrList($startYear, $startMonth, $endYear = 'this', $endMonth = 'this') {
        $yearMonthList = self::getYearMonthList($startYear, $startMonth, $endYear, $endMonth);
        $yearMonthStrList = array();
        foreach ($yearMonthList as $year => $months) {
            foreach ($months as $month) {
                $yearMonthStrList[] = $year . $month;
            }
        }
        return $yearMonthStrList;
    }

    /**
     * 从url中取得搜索条件
     * @author tabalt
     * @param array $fields 传入的字段
     * @return array
     */
    public static function getSearchCondition($fieldList) {
        $condition = array();
        if (is_array($fieldList) && !empty($fieldList)) {
            foreach ($fieldList as $v) {
                if (!is_array($v) && self::getValue($v) != '') {
                    $condition[$v] = self::getValue($v);
                }
            }
        }
        return $condition;
    }

    /**
     * 生成uuid
     * @author tabalt 
     * @param string $prefix
     * @return string
     */
    public static function createUuid($prefix = "") {
        $str = md5(uniqid(mt_rand(), true));
        $uuid = substr($str, 0, 8) . '-';
        $uuid .= substr($str, 8, 4) . '-';
        $uuid .= substr($str, 12, 4) . '-';
        $uuid .= substr($str, 16, 4) . '-';
        $uuid .= substr($str, 20, 12);
        return $prefix . $uuid;
    }

    /**
     * 截取长度
     * @author tabalt
     * @param string $string
     * @param int $length
     * @param string $tail
     */
    public static function cutString($string, $length, $tail = '...') {
        if (mb_strlen($string, 'utf-8') > $length) {
            return mb_substr($string, 0, $length, 'utf-8') . $tail;
        } else {
            return $string;
        }
    }

}