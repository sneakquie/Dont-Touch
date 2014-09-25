<?php

abstract class GameStorageInterface
{
    protected static $_instance = null;
    protected $_prefix  = '_';
    protected $_table   = null;
    protected $_storage = array();

    protected function __construct()
    {
        if(!is_string($this->_table)) {
            return;
        }
        $result = DBH::table($this->_table)->all();
        if(null === $result) {
            return;
        }
        foreach($result as $value) {
            $this->_storage[(integer) $value[$this->_prefix . 'id']] = $value;
        }
    }

    protected function __wakeup() {}
    protected function __clone() {}

    public function all()
    {
        return $this->_storage;
    }
    public function find($id)
    {
        $id = (integer) $id;
        return isset($this->_storage[$id]) ? $this->_storage[$id] : null;
    }
}