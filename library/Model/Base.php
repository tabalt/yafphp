<?php
/**
 * 模型基类
 * @author tabalt
 */
class Model_Base {

    /**
     * 错误信息
     * @author tabalt
     * @var string
     */
    protected $errMsg = '';

    /**
     * 全局的字段列表
     * @author tabalt
     * @var array
     */
    protected $wholeFieldList;

    /**
     * 获取错误信息
     * @author tabalt
     * @return string $errMsg 错误信息
     */
    public function getError() {
        return $this->errMsg;
    }

    /**
     * 设置错误信息
     * @author tabalt
     * @param string $errMsg 错误信息
     */
    public function setError($errMsg) {
        $this->errMsg = $errMsg;
    }

    /**
     * 执行验证操作
     * @author tabalt
     * @param array $validateRules
     * @param array $data
     * @return boolean true/false
     */
    public function executeValidate($validateRules, $data = array()) {
        if (empty($validateRules)) {
            return true;
        }
        if (Validate::execute($validateRules, $data)) {
            return true;
        } else {
            $this->setError(Validate::getError());
            return false;
        }
    }

    /**
     * 执行过滤操作
     * @author tabalt
     * @param array $validateRules
     * @param array $data
     * @return array $data
     */
    public function executeFilter($filterRules, $data = array()) {
        $data = Filter::execute($filterRules, $data);
        return $data;
    }

    /**
     * 获取验证和过滤后的数据列表
     * @author tabalt
     * @param array $fieldList 字段列表
     * @param array $data
     */
    public function getDataList($fieldList = array(), $data = array()) {
        
        if (empty($fieldList) || !is_array($fieldList)) {
            $fieldList = $this->wholeFieldList;
        }
        //验证规则
        $validateRules = array();
        //过滤规则
        $filterRules = array();
        
        foreach ($fieldList as $field => $info) {
            $fieldInfo = isset($this->wholeFieldList[$field]) ? $this->wholeFieldList[$field] : array();
            $fieldInfo = array_merge($fieldInfo, $info);
            $validate = isset($fieldInfo['validate']) ? $fieldInfo['validate'] : array();
            $validate['field'] = $field;
            $validate['name'] = isset($fieldInfo['name']) ? $fieldInfo['name'] : '';
            $validate['require'] = isset($fieldInfo['require']) ? $fieldInfo['require'] : false;
            
            //过滤的默认值设置
            $validate['minLength'] = isset($validate['minLength']) ? $validate['minLength'] : false;
            $validate['maxLength'] = isset($validate['maxLength']) ? $validate['maxLength'] : false;
            $validate['minNumber'] = isset($validate['minNumber']) ? $validate['minNumber'] : false;
            $validate['maxNumber'] = isset($validate['maxNumber']) ? $validate['maxNumber'] : false;
            $validate['limitLength'] = isset($validate['limitLength']) ? $validate['limitLength'] : false;
            
            //TODO 字段别名
            $alias = isset($fieldInfo['alias']) ? $fieldInfo['alias'] : false;
            if ($alias){
                //$field = $alias;
            }
            $validateRules[$field] = $validate;
            $filterRules[$field] = isset($fieldInfo['filter']) ? $fieldInfo['filter'] : 'trim';
        }
        //验证
        if (!$this->executeValidate($validateRules, $data)) {
            return false;
        }
        //过滤
        $data = $this->executeFilter($filterRules, $data);
        //删除不需要的数据
        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $fieldList)) {
                unset($data[$key]);
            }
        }
        return $data;
    }
}