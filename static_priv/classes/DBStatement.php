<?php

class DBStatement {
    /**
     * @var PDOstatement
     */
    public $stmt;

    public function __construct(PDOStatement $stmt)
    {
        $this->stmt = $stmt;
    }

    protected function dbMask($mask) {
        static $types = array('s', 'i', 'f', 'h', 'b');
        $mask = trim(strval($mask));
        if ($mask==='') throw new Exception('Empty mask');
        $mask = explode(',', $mask);
        $result = array();
        foreach ($mask as $type) {
            $type = trim($type);
            if (!in_array($type, $types)) throw new Exception('Unsupported type: ' . $type);
            $result[] = $type;
        }
        return $result;
    }

    protected function cast(&$row, &$mask) {
        if (!is_array($row)) return $row;
        if (is_null($mask)) return $row;
        if (!is_array($mask)) $mask = $this->dbMask($mask);
        $num = 0;
        foreach ($row as $k=>$v) {
            if (!isset($mask[$num])) break;
            $type = $mask[$num];
            switch ($type):
                case 's': break;
                case 'i': $row[$k] = intval($v); break;
                case 'f': $row[$k] = floatval($v); break;
                case 'h': $row[$k] = escapeHTML($v); break;
                case 'b': $row[$k] = (boolean)$v; break;
                default: throw new Exception('Unknown type: ' . $type);
            endswitch;
            $num++;
        }
        return $row;
    }

    public function fetchAssoc($mask=null) {
        $row = $this->stmt->fetch(PDO::FETCH_ASSOC);
        $result = $this->cast($row, $mask);
        return $result;
    }

    function fetchAssocAll($mask=null) {
        if (!is_null($mask)) $mask = $this->dbMask($mask);
        $result = array();
        while (is_array($row=$this->fetchAssoc($mask))) $result[] = $row;
        return $result;
    }

    public function fetchNum($mask=null) {
        $row = $this->stmt->fetch(PDO::FETCH_NUM);
        $result = $this->cast($row, $mask);
        return $result;
    }

    function fetchNumAll($mask=null) {
        if (!is_null($mask)) $mask = $this->dbMask($mask);
        $result = array();
        while (is_array($row=$this->fetchNum($mask))) $result[] = $row;
        return $result;
    }
}
