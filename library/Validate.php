<?php
/**
 * 验证类
 * @author tabalt
 */
class Validate {

    /**
     * 错误信息
     * @author tabalt
     * @var string
     */
    private static $errMsg;

    /**
     * 验证规则中必须设置的项目列表
     * @author tabalt
     * @var array
     */
    private static $requiredItemList = array(
        'field' => '',  //验证的字段
        'name' => '',  //验证字段的名称
        'require' => false //是否必须 true 则必须　false非必须
    );

    /**
     * 验证规则中未设置条目的默认值列表
     * @author tabalt
     * @var array
     */
    private static $defaultItemValueList = array(
        'type' => false,  //默认类型 为空则不做判断
        'match' => false,  //匹配字段 为空则不做判断
        'limitLength' => false,  //长度限制 为空则不做判断
        'minLength' => false,  //最小长度 
        'maxLength' => false,  //最大长度 最小最大都不为空才做判断
        'minNumber' => false,  //最小数字大小
        'maxNumber' => false,  //最大数字大小 最小最大都不为空才做判断
        'regex' => false,  //正则表达式 为空则不做判断,
        'valueLimit' => false //允许的值列表 需传数组
    );

    /**
     * 默认的错误信息列表
     * @author tabalt
     * @var array
     */
    private static $defaultMsgTplList = array(
        'empty' => '{%name}不能为空', 
        'lengthLimit' => '{%name}的长度必须为{%limit}', 
        'lengthInterval' => '{%name}的长度必须在{%minLength}和{%maxLength}之间', 
        'numberInterval' => '{%name}的大小必须在{%minNumber}和{%maxNumber}之间', 
        'regex' => '{%name}的格式不正确'
    );

    /**
     * 默认的字段类型列表
     * @author tabalt
     * @var array
     */
    //TODO 完善默认字段类型 url chinese
    private static $defaultTypeList = array(
        'qq' => array(
            'minLength' => 5,  //最小长度
            'maxLength' => 11,  //最大长度
            'regex' => "/^[1-9][0-9]{4,10}$/" //正则表达式 为空则不做判断
        ), 
        'email' => array(
            'minLength' => 6,  //最小长度
            'maxLength' => 50,  //最大长度
            'regex' => "/^[\da-z]([_\.]?[\da-z])+@[\da-z]+\.[\da-z]+(\.[a-z]+)*$/i" //正则表达式 为空则不做判断
        ), 
        'password' => array(
            'minLength' => 6,  //最小长度
            'maxLength' => 16,  //最大长度
            'regex' => '/^[\w\d\`\~\!\@\#\$\%\^\&\*\(\)\_\+\=\-\{\}\|\]\[\:\"\'\;\<\>\?\/\.\,\\\]{6,16}$/'
        ), 
        'number' => array(
            'regex' => "/^[0-9]{1,}$/"
        ), 
        'url' => array(
            'regex' => '/^(http|https):\/\/([a-z\d_-]+\.)+[a-z]{2,}(:[\d]{2,})?(\/[a-z\d_=\/\%\#\&\-\$\.\?]*)?$/i'
        )
    );

    /**
     * 验证规则列表
     * @author tabalt
     * @var array
     */
    public static $rules;

    /**
     * 解析消息
     * @author tabalt
     * @param string $msgTpl　消息模板
     * @param array $rule 验证规则
     * @return string $msg
     */
    private static function parseMsg($msgTpl, $rule) {
        $searchList = array(
            '{%name}', 
            '{%minLength}', 
            '{%maxLength}', 
            '{%minNumber}', 
            '{%maxNumber}', 
            '{%limit}'
        );
        $replaceList = array(
            $rule['name'], 
            $rule['minLength'], 
            $rule['maxLength'], 
            $rule['minNumber'], 
            $rule['maxNumber'], 
            $rule['limitLength']
        );
        $msg = str_replace($searchList, $replaceList, $msgTpl);
        return $msg;
    }

    /**
     * 解析验证规则
     * @author tabalt
     * @param array $rule
     * @return boolean true|false
     */
    private static function parseRule($rule) {
        //未定义的项目列表
        $undefinedItemList = array_diff_key(self::$requiredItemList, $rule);
        if (!empty($undefinedItemList)) {
            self::$errMsg = "{$rule['field']}的验证规则缺少如下项目：" . implode('，', array_keys($undefinedItemList));
            return false;
        }
        return $rule;
    }

    /**
     * 设置验证规则
     * @author tabalt
     * @param array $validateRules
     * @return boolean true|false
     */
    public static function setRules($validateRules) {
        //格式化验证规则
        $ruleList = array();
        foreach ($validateRules as $key => $rule) {
            //处理规则
            $rule = self::parseRule($rule);
            if (!$rule) {
                return false;
            }
            $ruleList[$rule['field']] = $rule;
        }
        self::$rules = $ruleList;
        return true;
    }

    /**
     * 获取错误信息
     * @author tabalt
     * @return string $errMsg
     */
    public static function getError() {
        return self::$errMsg;
    }

    /**
     * 数据验证
     * @author tabalt
     * @param array $validateRules 验证规则
     * @param array $data 要验证的数据
     * @return boolean true/false
     */
    public static function execute($validateRules = array(), $data = array()) {
        //设置默认数据源
        if (empty($data)) {
            $data = $_POST;
        }
        //设置验证规则
        if (!empty($validateRules)) {
            $result = self::setRules($validateRules);
            if (!$result) {
                //验证规则有误
                return false;
            }
        }
        //规则验证
        foreach (self::$rules as $rule) {
            //TODO 考虑数据为数组的情况
            $fieldValue = isset($data[$rule['field']]) ? $data[$rule['field']] : false;
            $fieldValue = str_replace("　", '', trim($fieldValue));
            //值是否为空
            $isValueEmpty = false;
            if ($fieldValue === false || $fieldValue === '') {
                $isValueEmpty = true;
            }
            
            //是否必须 true 必须 如果为空 报错；false 非必须 如果为空 则continue
            if ($rule['require'] && $isValueEmpty) {
                self::$errMsg = self::parseMsg(self::$defaultMsgTplList['empty'], $rule);
                return false;
            } else if (!$rule['require'] && $isValueEmpty) {
                continue;
            }
            
            //验证类型type type不存在则报错 存在以新定义规则为准 合并type预设的规则
            if (isset($rule['type']) && !empty($rule['type'])) {
                if (!array_key_exists($rule['type'], self::$defaultTypeList)) {
                    self::$errMsg = "需验证的类型{$rule['type']}不存在";
                    return false;
                }
                //以新定义规则为准 合并type预设的规则 
                $rule = array_merge(self::$defaultTypeList[$rule['type']], $rule);
            }
            //覆盖默认值列表
            $rule = array_merge(self::$defaultItemValueList, $rule);
            //验证匹配match
            if ($rule['match']) {
                $matchValue = isset($data[$rule['match']]) ? trim($data[$rule['match']]) : false;
                if (!array_key_exists($rule['match'], self::$rules) || $matchValue === false) {
                    self::$errMsg = "要匹配的字段{$rule['match']}不存在";
                    return false;
                }
                if ($fieldValue !== $matchValue) {
                    self::$errMsg = "{$rule['name']}和" . self::$rules[$rule['match']]['name'] . "不匹配";
                    return false;
                }
            }
            
            //验证长度限制
            if ($rule['limitLength']) {
                if (Helper::stringLength($fieldValue) != $rule['limitLength']) {
                    self::$errMsg = self::parseMsg(self::$defaultMsgTplList['lengthLimit'], $rule);
                    return false;
                }
            }
            
            //验证长度区间
            if ($rule['minLength'] && $rule['maxLength']) {
                if (Helper::stringLength($fieldValue) < $rule['minLength'] || Helper::stringLength($fieldValue) > $rule['maxLength']) {
                    self::$errMsg = self::parseMsg(self::$defaultMsgTplList['lengthInterval'], $rule);
                    return false;
                }
            }
            
            //验证大小区间
            if ($rule['minNumber'] && $rule['maxNumber']) {
                if (intval($fieldValue) < $rule['minNumber'] || intval($fieldValue) > $rule['maxNumber']) {
                    self::$errMsg = self::parseMsg(self::$defaultMsgTplList['numberInterval'], $rule);
                    return false;
                }
            }
            
            //验证正则
            if ($rule['regex']) {
                if (!preg_match($rule['regex'], $fieldValue)) {
                    self::$errMsg = self::parseMsg(self::$defaultMsgTplList['regex'], $rule);
                    return false;
                }
            }
            
            //验证允许的值列表
            if ($rule['valueLimit'] && is_array($rule['valueLimit'])) {
                if (!in_array($fieldValue, $rule['valueLimit'])) {
                    self::$errMsg = "{$rule['name']}的值不在允许的范围";
                    return false;
                }
            }
        }
        return true;
    }
}
