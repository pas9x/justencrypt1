<?php

class DBConnection extends Closeable
{
    public $pdo;

    public function __construct(array $pdoArguments)
    {
        $this->pdo = createObject('PDO', $pdoArguments);
    }

    protected function throwError(PDOStatement $stmt) {
        $errorInfo = $stmt->errorInfo();
        $message = array();
        if (isset($errorInfo[0])) $message[] = 'SQLSTATE=' . $errorInfo[0];
        if (isset($errorInfo[1])) $message[] = 'code=' . $errorInfo[1];
        if (isset($errorInfo[2])) $message[] = $errorInfo[2];
        $message = empty($message) ? 'Unknown error during SQL-query' : implode(', ', $message);
        throw new DBExcepeion($message, $errorInfo);
    }

    /**
     * @param string $sql
     * @return DBStatement
     * @throws AlreadyClosedException
     * @throws Exception
     */
    public function query($sql) {
        $this->checkClosed();
        $arguments = func_get_args();
        unset($arguments[0]);
        $questCount = substr_count($sql, '?');
        if ($questCount !== count($arguments)) throw new Exception('Placeholders count in sql-query differs with function arguments count');
        $stmt = $this->pdo->prepare($sql);
        if (!($stmt instanceof PDOStatement)) {
            $msg = "Failed to prepare statement";
            $err = $this->pdo->errorInfo();
            if (isset($err[2])) $msg .= ': ' . $err[2];
            throw new Exception($msg);
        }
        for ($j=1; $j<=$questCount; $j++)
            if (is_null($arguments[$j])) $stmt->bindParam($j, $arguments[$j], PDO::PARAM_NULL);
            elseif (is_bool($arguments[$j])) $stmt->bindParam($j, $arguments[$j], PDO::PARAM_BOOL);
            elseif (is_int($arguments[$j])) $stmt->bindParam($j, $arguments[$j], PDO::PARAM_INT);
            else $stmt->bindParam($j, $arguments[$j], PDO::PARAM_STR);
        $ok = $stmt->execute();
        if ($ok) return new DBStatement($stmt);
        $this->throwError($stmt);
    }

    /**
     * @param string $sql
     * @return DBStatement
     * @throws AlreadyClosedException
     */
    public function directQuery($sql) {
        $this->checkClosed();
        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute();
        if ($ok) return new DBStatement($stmt);
        $this->throwError($stmt);
    }

    /**
     * @param string $tableName
     * @return DBStatement
     * @throws AlreadyClosedException
     * @throws Exception
     */
    public function insert($tableName) {
        $this->checkClosed();
        $arguments = func_get_args();
        $cnt = count($arguments);
        if ($cnt<2) throw new Exception('Nothing to insert');
        $quests = array_fill(0, $cnt-1, '?');
        $sql = "INSERT INTO $tableName VALUES(";
        $sql .= implode(', ', $quests);
        $sql .= ')';
        $arguments[0] = $sql;
        return call_user_func_array(array($this, 'query'), $arguments);
    }

    /**
     * @return int
     * @throws Exception
     */
    public function insertId() {
        $this->checkClosed();
        $stmt = $this->query('SELECT last_insert_rowid()');
        $row = $stmt->stmt->fetch();
        if (!is_array($row)) throw new Exception('Failed to retrieve last insert id (1)');
        $result = strval($row[0]);
        if (!preg_match('/^[0-9]{1,9}$/', $result)) throw new Exception('Failed to retrieve last insert id (2)');
        return intval($result);
    }


    public function close() {
        if (parent::closed()) return;
        $this->pdo = null;
        gc_collect_cycles();
        parent::close();
    }

    public function selectCount($sql)
    {
        $args = func_get_args();
        $stmt = call_user_func_array([$this, 'query'], $args);
        $row = $stmt->fetchNum('i');
        if (empty($row)) {
            throw new Exception("Query '$sql' returns empty result");
        }
        $result = $row[0];
        return $result;
    }

    public function selectCell($sql)
    {
        $args = func_get_args();
        $stmt = call_user_func_array([$this, 'query'], $args);
        $row = $stmt->fetchNum('s');
        if (!is_array($row)) {
            return null;
        }
        if (count($row) !== 1) {
            return null;
        }
        $result = $row[0];
        $row = $stmt->fetchNum();
        if (!empty($row)) {
            return null;
        }
        return $result;
    }

    public function hasResult($sql)
    {
        $args = func_get_args();
        $stmt = call_user_func_array([$this, 'query'], $args);
        $row = $stmt->fetchNum('i');
        return !empty($row);
    }
}
