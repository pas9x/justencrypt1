<?php

class Options
{
    protected $namespace = 'generic';
    protected $options = [];

    public function __construct($namespace = null)
    {
        if (!empty($namespace)) {
            $this->namespace = $namespace;
        }
        $this->reload();
    }

    /**
     * @throws Exception
     */
    public function reload()
    {
        $db = getDB();
        $stmt = $db->query('SELECT optionName, optionValue FROM options WHERE namespace=?', $this->namespace);
        $this->options = [];
        while (is_array($row = $stmt->fetchAssoc())) {
            $optionName = $row['optionName'];
            $optionValue = unserialize($row['optionValue']);
            $this->options[$optionName] = $optionValue;
        }
    }

    /**
     * @param string $optionName
     * @param mixed $defaultValue
     * @return mixed
     * @throws Exception
     */
    public function get($optionName, $defaultValue = null)
    {
        if (isset($this->options[$optionName])) {
            return $this->options[$optionName];
        }
        $hasDefaultValue = func_num_args() > 1;
        if (!$hasDefaultValue) {
            throw new Exception("Option '$optionName' not found in namespace '{$this->namespace}'");
        }
        return $defaultValue;
    }

    /**
     * @param string $optionName
     * @param mixed $optionValue
     * @throws Exception
     */
    public function set($optionName, $optionValue)
    {
        $serializedValue = serialize($optionValue);
        $db = getDB();
        $db->query("DELETE FROM options WHERE optionName=? AND namespace=?", $optionName, $this->namespace);
        $db->insert('options', $optionName, $this->namespace, $serializedValue);
        $this->options[$optionName] = $optionValue;
    }

    /**
     * @param string $optionName
     * @throws Exception
     */
    public function del($optionName)
    {
        $db = getDB();
        $db->query("DELETE FROM options WHERE optionName=? AND namespace=?", $optionName, $this->namespace);
        unset($this->options[$optionName]);
    }

    /**
     * @return array
     */
    public function all()
    {
        return array_keys($this->options);
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }
}
