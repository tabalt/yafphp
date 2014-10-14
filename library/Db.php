<?php
/**
 * 数据库操作类
 * @author tabalt
 */
class Db {

    /**
     * 数据库连接对象
     * @author tabalt
     * @var PDO
     */
    private $pdo;

    /**
     * PDOStatement
     * @author tabalt
     * @var PDOStatement
     */
    private $statement;

    /**
     * 异常信息
     * @author tabalt
     * @var string
     */
    private $message = '';

    /**
     * 构造函数
     * @author tabalt
     * @param array $config
     * @return void
     */
    public function __construct($config) {
        $dsn = 'mysql:host=' . $config['host'] . ';port=' . $config['port'] . ';dbname=' . $config['dbname'] . ';charset=' . $config['charset'];
        $attribute = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
            PDO::ATTR_PERSISTENT => isset($config['persistent']) ? $config['persistent'] : false
        );
        $this->pdo = new PDO($dsn, $config['username'], $config['password'], $attribute);
        //禁用prepared statements的仿真效果
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->execute('set names ' . $config['charset']);
    }

    /**
     * 执行sql语句
     * @author tabalt
     * @param string $sql
     * @param array $data
     * @return int/boolean $result 
     */
    public function execute($sql, $data = null) {
        $this->statement = $this->pdo->prepare($sql);
        $result = $this->statement->execute($data);
        return $result;
    }

    /**
     * 执行sql语句，返回结果数组
     * 主要用于执行SELECT语句
     * @author tabalt
     * @param string $sql
     * @param array $data
     * @return array/boolean $result
     */
    public function getResult($sql, $data = null) {
        if ($this->execute($sql, $data)) {
            $result = $this->statement->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 执行sql语句，返回影响的行数
     * 主要用于执行UPDATE、DELETE等语句
     * @author tabalt
     * @param string $sql
     * @param array $data
     * @return int/boolean $affectedRows
     */
    public function getAffectedRows($sql, $data = null) {
        if ($this->execute($sql, $data)) {
            return $this->statement->rowCount();
        } else {
            return false;
        }
    }

    /**
     * 执行sql语句，返回最后插入的ID
     * 主要用于执行INSERT语句
     * @author tabalt
     * @param string $sql
     * @param array $data
     * @return int $lastId
     */
    public function getLastInsertId($sql, $data = null) {
        $this->execute($sql, $data);
        $lastId = $this->pdo->lastInsertId();
        if (is_numeric($lastId)) {
            return $lastId;
        } else {
            return false;
        }
    }

    /**
     * 开启事务
     * @author tabalt
     */
    public function beginTransaction() {
        $this->pdo->beginTransaction();
    }

    /**
     * 提交事务
     * @author tabalt
     */
    public function commit() {
        $this->pdo->commit();
    }

    /**
     * 回滚事务
     * @author tabalt
     */
    public function rollback() {
        $this->pdo->rollback();
    }
}