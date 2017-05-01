<?php

class Tmp
{
    public static function checkKey($key)
    {
        return preg_match('/^[a-zA-Z0-9]{32}$/', $key) === 1;
    }

    public static function validateKey($key)
    {
        if (!self::checkKey($key)) {
            throw new InvalidArgumentException('Invalid key format');
        }
    }

    public static function add($value, $name, $lifetime = 0, $encrypt = false, $key = null)
    {
        if ($lifetime === 0) {
            $expire = time() + SEC_DAY;
        } else {
            $expire = time() + $lifetime;
        }
        if (empty($key)) {
            $key = keygen(32);
        } else {
            self::validateKey($key);
        }
        if (!checkLength($name, 1, 255)) {
            throw new InvalidArgumentException('$name argument should has length between 1 and 255');
        }
        if (!is_bool($encrypt)) {
            throw new Exception('Invalid type of $encrypt argument: ' . gettype($encrypt));
        }
        $value = serialize($value);
        if ($encrypt) $value = encrypt($value);
        $db = getDB();
        $db->insert('tmp', $key, $name, $value, $expire, $lifetime, $encrypt);
        return $key;
    }

    public static function get($key, $name, $defaultValue = null, $dontProlong = false)
    {
        self::validateKey($key);
        $hasDefaultValue = func_num_args() > 1;
        $stmt = dbQuery('SELECT name, `value`, expire, autoprolong, encrypted FROM tmp WHERE `key`=?', $key);
        $row = $stmt->fetchAssoc('s,s,i,i,b');
        if (empty($row)) {
            if ($hasDefaultValue) {
                return $defaultValue;
            } else {
                throw new Exception("Temporary entry with key=$key not found");
            }
        }
        $now = time();
        $entryName = $row['name'];
        $value = unserialize($row['encrypted'] ? decrypt($row['value']) : $row['value']);
        $expire = $row['expire'];
        $autoprolong = $row['autoprolong'];
        if ($entryName !== $name) {
            throw new Exception("Attempt to read key $key with alien name $name");
        }
        if ($now >= $expire) {
            self::del($key);
            if ($hasDefaultValue) {
                return $defaultValue;
            } else {
                throw new Exception("Temporary entry with key=$key expired");
            }
        }
        if ($autoprolong > 0 && !$dontProlong && ($expire-($autoprolong/2))<=$now) {
            $expire = $now + $autoprolong;
            dbQuery('UPDATE tmp SET expire=? WHERE `key`=?', $expire, $key);
        }
        return $value;
    }

    public static function set($key, $newValue)
    {
        self::validateKey($key);
        $stmt = dbQuery('SELECT expire, autoprolong, encrypted FROM tmp WHERE `key`=?', $key);
        $row = $stmt->fetchAssoc('i,i,b');
        if (empty($row)) {
            throw new Exception("Temporary entry with key=$key not found");
        }
        $expire = $row['expire'];
        $autoprolong = $row['autoprolong'];
        $now = time();
        if ($expire < $now) {
            self::del($key);
            throw new Exception("Temporary entry with key=$key expired");
        }
        if ($autoprolong > 0) {
            $expire = $now + $autoprolong;
        }
        $value = serialize($newValue);
        if ($row['encrypted']) {
            $value = encrypt($value);
        }
        dbQuery('UPDATE tmp SET `value`=?, expire=? WHERE `key`=?', $value, $expire, $key);
    }

    public static function del($key)
    {
        self::validateKey($key);
        dbQuery('DELETE FROM tmp WHERE `key`=?', $key);
    }

    public static function cleanup()
    {
        dbQuery('DELETE FROM tmp WHERE expire<?', time()-1);
    }
}