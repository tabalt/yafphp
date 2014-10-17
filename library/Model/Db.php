<?php
/**
 * 模型基类
 * @author tabalt
 */
//TODO 数据表不存在时，异常处理不当
class Model_Db extends Model_Base {

    /**
     * 数据库连接对象
     * @author tabalt
     * @var Db
     */
    private $db;

    /**
     * 表名称
     * @author tabalt
     * @var sting
     */
    protected $tableName;

    /**
     * 表全名
     * @author tabalt
     * @var sting
     */
    protected $tableFullName;

    /**
     * 主键
     * @author tabalt
     * @var sting
     */
    protected $primaryKey;

    /**
     * 表字段列表
     * @author tabalt
     * @var array
     */
    protected $tableFieldList;

    /**
     * WHERE 条件
     * @author tabalt
     * @var array
     */
    protected $condition;

    /**
     * SQL查询数据
     * @author tabalt
     * @var array
     */
    protected $queryDataList;

    /**
     * SQL记录
     * @author tabalt
     * @var array
     */
    protected $sqlList;

    /**
     * 初始化sql查询的数据
     * @author tabalt
     */
    private function initSqlData() {
        $this->queryDataList = array(
            'field' => false, 
            'limit' => false, 
            'order' => false, 
            'group' => false, 
            'data' => array()
        );
        $this->condition = array(
            'tpl' => false, 
            'data' => array()
        );
    }

    /**
     * 从数据库中查询字段列表
     * @author tabalt
     * @return array $fieldList 字段列表
     */
    private function queryTableFields() {
        $sql = "SHOW COLUMNS FROM " . $this->getTableFullName();
        $result = $this->db->getResult($sql);
        if (!empty($result)) {
            $fieldList = array();
            foreach ($result as $fieldInfo) {
                $fieldList[$fieldInfo['Field']] = array(
                    'name' => $fieldInfo['Field'], 
                    'type' => $fieldInfo['Type'], 
                    'notnull' => (bool)($fieldInfo['Null'] === ''),  // not null is empty, null is yes
                    'default' => $fieldInfo['Default'], 
                    'primary' => (strtolower($fieldInfo['Key']) == 'pri'), 
                    'autoinc' => (strtolower($fieldInfo['Extra']) == 'auto_increment')
                );
                if (strtolower($fieldInfo['Key']) == 'pri') {
                    $this->primaryKey = $fieldInfo['Field'];
                }
                $fieldList['_fieldlist'][] = $fieldInfo['Field'];
            }
            //缓存字段信息
            $this->setCacheFieldList($fieldList);
            return $fieldList;
        } else {
            throw new Exception('table ' . $this->getTableFullName() . '\'s fieldList query error');
        }
    }

    /**
     * 从缓存中读取字段信息
     * @author tabalt
     * @param string $tableName 表名称
     * @return array $fieldList 字段列表
     */
    private function getCacheFieldList($tableName) {
        //TODO 从缓存中读取字段信息
        $fieldList = false;
        return $fieldList;
    }

    /**
     * 将字段信息设置到缓存中
     * @author tabalt
     * @param array $fieldList 字段列表
     * @return boolean true/false
     */
    private function setCacheFieldList($fieldList) {
        //TODO 将字段信息设置到缓存中
        return false;
    }

    /**
     * 取得表的字段列表
     * @author tabalt
     * @return array $fieldList 字段列表
     */
    private function getTableFieldInfoList() {
        $fieldList = array();
        if (defined('APP_DEBUG') && APP_DEBUG) {
            $fieldList = $this->queryTableFields();
        } else {
            $fieldList = $this->getCacheFieldList($this->getTableFullName());
            if (!empty($fieldList)) {
                //从缓存中返回 并设置主键
                foreach ($fieldList as $fieldInfo) {
                    if ($fieldInfo['primary']) {
                        $this->primaryKey = $fieldInfo['name'];
                    }
                }
            } else {
                $fieldList = $this->queryTableFields();
            }
        }
        return $fieldList;
    }
    
    /**
     * 驼峰 命名 转换
     * $type 0 ：UserRole => user_role
     * $type 1 ：user_role => UserRole
     * @author tabalt
     * @param string $name
     * @param int $type
     * @return string
     */
    private function parseName($name, $type = 0) {
        if ($type) {
            return ucfirst(preg_replace("/_([a-zA-Z])/e", "strtoupper('\\1')", $name));
        } else {
            return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
        }
    }

    /**
     * 构造函数
     * @author tabalt
     * @param array $config
     * @return void
     */
    public function __construct($tableName = null, $dbConfigPrefix = 'mysql') {
        if (empty($tableName)) {
            $tableName = str_replace('Model', '', get_class($this));
        }
        if (empty($tableName)) {
            throw new Exception('tableName is empty');
        }
        //获取mysql配置
        $config = Yaf_Registry::get('config');
        if (!isset($config[$dbConfigPrefix]['host'])) {
            throw new Exception("database config key `{$dbConfigPrefix}` is not exist");
        }
        $dbConfig = $config[$dbConfigPrefix];
        //设置表名
        $tablePrefix = isset($dbConfig['prefix']) ? $dbConfig['prefix'] : '';
        $this->tableName = $tablePrefix . $this->parseName($tableName);
        $this->tableFullName = '`' . $dbConfig['dbname'] . '`.`' . $this->tableName . '`';
        $this->db = new Db($dbConfig);
        //取得字段列表
        $this->tableFieldList = $this->getTableFieldInfoList();
        $this->initSqlData();
    }

    /**
     * 取得传入的表名
     * @author tabalt
     * @return array $tableName 表名
     */
    public function getTableName() {
        return $this->tableName;
    }

    /**
     * 获取表全名
     * @author tabalt
     * @return array $realTableName 表全名
     */
    public function getTableFullName() {
        return $this->tableFullName;
    }

    /**
     * 获取主键名称
     * @return $primaryKey 主键
     */
    public function getPk() {
        return $this->primaryKey;
    }

    /**
     * 获取最后执行的sql语句
     * @author tabalt
     * @return $sqlList
     */
    public function getLastSql() {
        if (!empty($this->sqlList)) {
            return $this->sqlList[count($this->sqlList) - 1];
        } else {
            return false;
        }
    }

    /**
     * 获取字段列表
     * @author tabalt
     * @return array $fieldList
     */
    public function getTableFieldList() {
        if (isset($this->tableFieldList['_fieldlist'])) {
            return $this->tableFieldList['_fieldlist'];
        } else {
            return false;
        }
    }

    /**
     * 验证过滤后 设置data 失败则返回false
     * @author tabalt
     * @param array $fieldList 字段列表
     * @param array $data
     */
    public function validate($fieldList = array(), $data = array()) {
        $data = $this->getDataList($fieldList, $data);
        if (isset($data[$this->primaryKey])) {
            unset($data[$this->primaryKey]);
        }
        if (empty($data)) {
            return false;
        } else {
            $this->data($data);
            return true;
        }
    }

    /**
     * 指定WHERE条件 支持安全过滤
     * @param array $where 条件表达式
     * @return DbModel
     */
    public function where($where, $logic = 'and') {
        //获取logic
        $logic = isset($where['logic']) ? $where['logic'] : $logic;
        unset($where['logic']);
        $condition = array();
        if (is_array($where) && !empty($where)) {
            $tplList = $dataList = array();
            foreach ($where as $field => $value) {
                if (!is_array($value)) {
                    $tplList[$field] = "`{$field}` = :{$field}";
                    $dataList[":{$field}"] = $value;
                } else if (is_array($value)) {
                    $lgc = isset($value['logic']) ? $value['logic'] : 'and';
                    $lgc = strtoupper($lgc);
                    unset($value['logic']);
                    $tmpList = array();
                    $i = 1;
                    foreach ($value as $key => $val) {
                        if (is_array($val)) {
                            $tList = array();
                            $val = array_unique($val);
                            foreach ($val as $k => $v) {
                                $tList[] = ":{$field}_{$i}_{$k}";
                                $dataList[":{$field}_{$i}_{$k}"] = $v;
                            }
                            $tmpList[] = "`{$field}` {$key} (" . implode(',', $tList) . ")";
                        } else {
                            $tmpList[] = "`{$field}` {$key} :{$field}_{$i}";
                            $dataList[":{$field}_{$i}"] = $val;
                        }
                        $i++;
                    }
                    $tplList[$field] = "( " . implode(" {$lgc} ", $tmpList) . " )";
                }
            }
            $logic = strtoupper($logic);
            $this->condition['tpl'] = implode(" {$logic} ", $tplList);
            $this->condition['data'] = $dataList;
        }
        return $this;
    }

    /**
     * 设置查询字段
     * @author tabalt
     * @param mixed $fieldList
     */
    public function field($fieldList = false) {
        if (empty($fieldList)) {
            $fieldList = $this->getTableFieldList();
        }
        if (is_string($fieldList)) {
            $fieldList = explode(",", $fieldList);
        }
        $this->queryDataList['field'] = $fieldList;
        return $this;
    }

    /**
     * 设置order
     * @author tabalt
     * @param mixed $order
     */
    public function order($order = false) {
        $this->queryDataList['order'] = $order;
        return $this;
    }

    /**
     * 设置group
     * @author tabalt
     * @param mixed $group
     */
    public function group($group = false) {
        $this->queryDataList['group'] = $group;
        return $this;
    }

    /**
     * 设置limit
     * @author tabalt
     * @param mixed $limit
     */
    public function limit($limit = false) {
        $this->queryDataList['limit'] = $limit;
        return $this;
    }

    /**
     * 设置data
     * @author tabalt
     * @param mixed $order
     */
    public function data($data) {
        $this->queryDataList['data'] = $data;
        return $this;
    }

    /**
     * 设置或更新一个值
     * @author tabalt
     * @param mixed $order
     */
    public function setData($key, $value) {
        $this->queryDataList['data'][$key] = $value;
    }

    /**
     * 获取一个值
     * @author tabalt
     * @param mixed $order
     */
    public function getData($key) {
        $data = $this->queryDataList['data'];
        return isset($data[$key]) ? $data[$key] : false;
    }

    /**
     * 执行sql语句
     * @author tabalt
     * @param string $sql
     * @param array $data
     * @param string $type
     */
    public function query($sql, $data = null, $type = false) {
        $this->initSqlData();
        $this->sqlList[] = $sql;
        $type = strtolower($type);
        try {
            if ($type == 'select') {
                $result = $this->db->getResult($sql, $data);
            } else if ($type == 'delete' || $type == 'update') {
                $result = $this->db->getAffectedRows($sql, $data);
            } else if ($type == 'insert') {
                $result = $this->db->getLastInsertId($sql, $data);
            } else {
                $result = $this->db->execute($sql, $data);
            }
            return $result;
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * 查询记录
     * @author tabalt
     */
    public function select() {
        $fieldList = $this->queryDataList['field'];
        if (empty($fieldList)) {
            $fieldList = $this->getTableFieldList();
        }
        foreach ($fieldList as $key => $fieldName) {
            if (false === strpos($fieldName, 'as')) {
                $fieldList[$key] = '`' . $fieldName . '`';
            } else {
                $fieldList[$key] = $fieldName;
            }
        }
        
        $conditionData = array();
        $where = '';
        //获取where条件
        if (!empty($this->condition['tpl'])) {
            $where = 'WHERE ' . $this->condition['tpl'];
            $conditionData = $this->condition['data'];
        }
        
        $limit = $this->queryDataList['limit'];
        if (!empty($limit)) {
            $limit = 'LIMIT ' . $limit;
        }
        $order = $this->queryDataList['order'];
        if (!empty($order)) {
            $order = 'ORDER BY ' . $order;
        }
        $group = $this->queryDataList['group'];
        if (!empty($group)) {
            $group = 'GROUP BY ' . $group;
        }
        $sqlTpl = "SELECT " . implode(',', $fieldList) . " FROM {$this->getTableFullName()} {$where} {$group} {$order} {$limit};";
		try {
            return $this->query($sqlTpl, $conditionData, 'select');
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * 添加记录
     * @author tabalt
     */
    public function add() {
        $data = $this->queryDataList['data'];
        $fieldStr = $valueStr = '';
        foreach ($data as $fieldName => $fieldValue) {
            $fieldStr .= '`' . $fieldName . '`,';
            $valueStr .= ':' . $fieldName . ',';
        }
        $sqlTpl = "INSERT INTO " . $this->getTableFullName() . " ( " . rtrim($fieldStr, ',') . " ) VALUES ( " . rtrim($valueStr, ',') . " ); ";
        try {
            return $this->query($sqlTpl, $data, 'insert');
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * 编辑记录
     * @author tabalt
     */
    public function edit() {
        $conditionData = array();
        $where = '';
        //获取where条件
        if (!empty($this->condition['tpl'])) {
            $where = 'WHERE ' . $this->condition['tpl'];
            $conditionData = $this->condition['data'];
        }
        if (empty($where)) {
            $this->setError('更新条件不能为空');
            return false;
        }
        
        $data = $this->queryDataList['data'];
        $editStr = '';
        foreach ($data as $fieldName => $fieldValue) {
            $editStr .= "`{$fieldName}` = :{$fieldName},";
        }
        
        $sqlTpl = "UPDATE " . $this->getTableFullName() . " SET " . rtrim($editStr, ',') . " $where ; ";
        try {
            return $this->query($sqlTpl, array_merge($data, $conditionData), 'update');
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * 删除记录
     * @author tabalt
     */
    public function delete() {
        $conditionData = array();
        $where = '';
        //获取where条件
        if (!empty($this->condition['tpl'])) {
            $where = 'WHERE ' . $this->condition['tpl'];
            $conditionData = $this->condition['data'];
        }
        if (empty($where)) {
            $this->setError('删除条件不能为空');
            return false;
        }
        $sqlTpl = "DELETE FROM " . $this->getTableFullName() . " $where ;";
        try {
            return $this->query($sqlTpl, $conditionData, 'delete');
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * 查询单条记录
     * @author tabalt
     */
    public function find() {
        $result = $this->limit(1)->select();
        if (isset($result[0])) {
            return $result[0];
        } else {
            return null;
        }
    }

    /**
     * 统计数量
     * @author tabalt
     */
    public function count() {
        $result = $this->field("count(`{$this->primaryKey}`) as count")->find();
        if (!empty($result) && isset($result['count'])) {
            return $result['count'];
        } else {
            return 0;
        }
    }

    /**
     * 开启事务
     * @author tabalt
     */
    public function beginTransaction() {
        $this->db->beginTransaction();
    }

    /**
     * 提交事务
     * @author tabalt
     */
    public function commit() {
        $this->db->commit();
    }

    /**
     * 回滚事务
     * @author tabalt
     */
    public function rollback() {
        $this->db->rollback();
    }
}