<?php

class BuildingsStorage extends GameStorageInterface
{
    protected $_table  = 'buildings';
    protected $_prefix = 'building_';

    public static function getInstance()
    {
        if(null === self::$_instance) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }
}