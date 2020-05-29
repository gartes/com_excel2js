<?php

class updateTable
{
    function __construct($table, $key, $keyVal = null)
    {

        $this->params = JComponentHelper::getParams('com_excel2vm');
        $this->conf = new stdClass;
        $this->conf->sql_protect = $this->params->get('sql_protect', 0);
        $this->db = JFactory::getDBO();
        $this->conf->table = $table;
        $this->conf->key = $key;
        $fields = $this->db->getTableColumns($table);
        foreach ($fields as $name => $type) {
            $this->$name = $name != $this->conf->key ? null : $keyVal;
        }
    }

    function __destruct()
    {
        unset($this->db);
    }

    function show()
    {
        $obj = new stdClass();
        $array = get_object_vars($this);
        foreach ($array as $name => $val) {
            if ($name != 'conf' AND $name != 'db') {
                $obj->$name = $val;
            }
        }
        echo '<pre>';
        print_r($obj);
        echo '</pre>';
    }

    function save()
    {
        if ($this->{$this->conf->key}) {
            $this->db->setQuery("SELECT {$this->conf->key} FROM {$this->conf->table} WHERE {$this->conf->key} = '{$this->{$this->conf->key}}'");
            if ($this->db->loadResult()) {
                return $this->update();
            } else {
                return $this->insert();
            }
        } else {
            return $this->insert();
        }
    }

    function update($updateNull = false)
    {
        $this->db->updateObject($this->conf->table, $this, $this->conf->key, $updateNull);

        return $this->db->getAffectedRows();
    }

    function insert()
    {
        $fields = array();
        $values = array();

        $statement = 'INSERT INTO `' . $this->conf->table . '` (%s) VALUES (%s)';

        foreach (get_object_vars($this) as $k => $v) {
            if (is_array($v) or is_object($v) or $v === null) {
                continue;
            }

            if ($k[0] == '_') {
                continue;
            }

            $fields[] = '`' . $k . '`';
            $values[] = $this->db->quote($v);
        }

        $this->db->setQuery(sprintf($statement, implode(',', $fields), implode(',', $values)));
        $this->db->execute();

        $id = $this->db->insertid();

        return $id;
    }

    function delete($cid = null, $key = null)
    {
        $cid = $cid ? (array)$cid : JRequest::getVar('cid', array(0), 'post', 'array');
        $k = $key ? $key : $this->conf->key;
        $cid = implode(',', (array)$cid);
        $this->db->setQuery("DELETE FROM {$this->conf->table} WHERE $k IN($cid)");
        if (!$this->db->execute()) {
            JError::raiseError(500, $this->db->getErrorMsg());
        }

        return "Удаленo: " . $this->db->getAffectedRows();
    }

    function load($keyVal = null, $key = null)
    {
        $key = ($key !== null) ? $key : $this->conf->key;
        $keyVal = ($keyVal !== null) ? $keyVal : $this->$key;

        if ($keyVal === null) {
            return false;
        }

        $this->db->setQuery("SELECT * FROM {$this->conf->table} WHERE {$key} = '{$keyVal}'");

        if ($result = $this->db->loadAssoc()) {
            return $this->bind($result);
        } else {
            return false;
        }
    }

    function bind($array)
    {

        $params = JComponentHelper::getParams('com_excel2vm');
        $custom_clear = $params->get('custom_clear', '-');
        $array = is_object($array) ? get_object_vars($array) : $array;
        if (!is_array($array)) return;
        foreach ($array as $name => $val) {
            if ($this->conf->sql_protect) {
                $this->check_sql_injection($val);
            }

            if (property_exists($this, $name)) {
                if (trim($val) == $custom_clear) {
                    $val = '';
                }
                $this->$name = trim($val);
            }
        }


    }

    function check_sql_injection($data)
    {
        $data = strtolower($data);
        $data = str_replace("/*", "", $data);
        $data = str_replace("*/", "", $data);
        $restricted = array('union', 'extractvalue', 'information_schema', 'database', 'substring', 'between', 'ascii');
        foreach ($restricted as $word) {
            if (strstr($data, $word)) {
                echo "Зафиксирована попытка sql-инъекции:<br>$data";
                file_put_contents(JPATH_ROOT . DS . 'components' . DS . 'com_excel2vm' . DS . 'error.txt', date("Y-m-d H:i:s", time()) . " - Зафиксирована попытка sql-инъекции - $data", FILE_APPEND);
                exit();
            }
        }
    }

    function reset($all = 0)
    {
        foreach (get_object_vars($this) as $name => $value) {
            if ($name != $this->conf->key AND $name != 'db' AND $name != 'conf' OR ($name == $this->conf->key AND $all)) {
                $this->$name = null;
            }
        }
    }

    function dateFormat($property_name, $format)
    {
        if (!$property_name or !$format) {
            return false;
        }
        @$time = strtotime($this->$property_name);
        if ($format == 'mysql') {
            $this->$property_name = date("Y-m-d H:i:s", $time);
        } else {
            $this->$property_name = date($format, $time);
        }
    }
}
