<?php
namespace occ2\DBModel;

/**
 * DBModel interface
 * @author Milan Onderka
 * @package occ2/db-model
 * @version 1.1.0
 */
interface IDBModel{
    /**
     * @param \Nette\Database\Context $database
     * @param array $config
     */
    public function __construct(\Nette\Database\Context $database,$config=[], $licence=null,$modules=null);
    
    /**
     * @param array $config
     */
    public function setConfig($config=[]);
    
    /**
     * @return array
     */
    public function getConfig();
    
    /**
     * @param string $key
     * @param mixed $value
     */
    public function addConfig($key,$value);
    
    /**
     * @param string $primaryCol
     */
    public function setPrimary($primaryCol);
    
    /**
     * @return string
     */
    public function getPrimary();
    
    /**
     * @return \Nette\Database\Selection
     */
    public function getTable();
    
    /**
     * @param string $tableName
     */
    public function setTable($tableName);
    
    /**
     * @param string $className
     */
    public function setExceptionClass($className);
    
    /**
     * @return string $className
     */
    public function getExceptionClass();
    
    /**
     * @param int $id
     * @param boolean $toArray
     */
    public function loadItem($id,$toArray=true);
    
    /**
     * @param mixed $id
     * @param string $key
     * @param mixed $value
     */
    public function changeItem($id,$key,$value);
    
    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function valueExists($key,$value);
    
    /**
     * @param mixed $data
     */
    public function saveItem($data);
    
    /**
     * @param mixed $id
     */
    public function deleteItem($id);
    
    /**
     * @param mixed $data
     */
    public function addItem($data);
    
    /**
     * @param mixed $data
     */
    public function updateItem($data);
    
    public function deleteAll();
    
    /**
     * @param string $row
     */
    public function setTreeParent($row);

    /**
     * @return string row
     */
    public function getTreeParent();
    
    /**
     * @param integer $id
     * @return boolean
     */
    public function hasChildren($id);
    
    /**
     * @param type $id
     */
    public function getChildren($id);
}
