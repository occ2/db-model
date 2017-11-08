<?php
namespace occ2\DBModel;

/**
 * Parent for all database models
 * CHANGELOG
 * 1.0.0 - base CRUD functionality 
 * 1.1.0 - adding tree structure functions
 * @todo add re-sorting methods, add import CSV
 * @author Milan Onderka
 * @package occ2/db-model
 * @version 1.1.0
 * @abstract
 */
abstract class DBModel extends \Nette\Object implements IDBModel{
    const UNSET_PRIMARY_KEY=1;
    const COLUMN_MUST_BE_UNIQUE=2;
    const COLUMN_EMPTY_NOT_NULL=3;
    const COLUMN_RESTRICT_FOREIGN_KEY=4;
    const COLUMN_UNDEFINED_EXCEPTION=5;
    const MAX_NUMBER_REACHED=6;
    const MYSQL_DATE_FORMAT="%d.%m.%Y %H:%i:%s";
    const NETTE_DB_DATE_FORMAT="d.m.Y H:i:s";
    
    /**
     * container to database
     * @var \Nette\Database\Context
     */
    public $db;

    /**
     * @var array
     */
    public $config=[];

    /**
     * @var \Nette\Database\Table\Selection
     */
    public $table;
    
    /**
     * name of primary key collumn
     * @var string
     */
    public $primaryKey="id";
    
    /**
     * name of exception class which is throw during fails
     * @var string
     */
    public $exceptionClass;
    
    /**
     * tree parent row if table use tree sorting
     * @var string | null
     */
    public $treeParentRow=null;
    
    /**
     * @var null | string
     */
    public $licence;
    
    /**
     * @var array | null
     */
    public $modules;

    /**
     * constructor for DI
     * @param \Nette\DI\Container $container
     * @param array $config
     * @return void
     */
    public function __construct(\Nette\Database\Context $database,$config=[], $licence=null,$modules=null) {
        $this->db = $database;
        $this->config = $config;
        $this->exceptionClass = get_class($this) . "Exception";
        $this->licence = $licence;
        $this->modules = $modules;
        return;
    }
    
    /**
     * set parent row if tree structure of table
     * @param string $row
     * @return void
     */
    public function setTreeParent($row){
        $this->treeParentRow = $row;
        return;
    }
        
    /**
     * set parent row name for tree structure of table
     * @return string
     */
    public function getTreeParent(){
        return $this->treeParentRow;
    }
    
    /**
     * check if this item has some children items in tree structure
     * @param integer $id
     * @return boolean
     */
    public function hasChildren($id){
        if($this->getTable()
                ->where($this->treeParentRow,$id)
                ->count("*")==0){
                    return false;
                }
        else{
            return true;
        }
    }
    
    /**
     * get children items in tree structure
     * @param type $id
     * @return \Nette\Database\Table\Selection
     */
    public function getChildren($id){
        return $this->getTable()->where($this->treeParentRow,$id);
    }
    
    /**
     * get configuration of model
     * @return array
     */
    public function getConfig(){
        return $this->config;
    }
    
    /**
     * set configuration
     * @param array $config
     * @return void
     */
    public function setConfig($config=[]){
        $this->config=$config;
        return;
    }
    
    /**
     * add value to config
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function addConfig($key, $value) {
        $this->config["key"]=$value;
        return;
    }

    /**
     * clone new Database table
     * @return \Nette\Database\Selection
     */
    public function getTable() {
        return clone $this->table;
    }

    /**
     * set table
     * @param string $tableName
     * @return \Nette\Database\Table\selection
     */
    public function setTable($tableName) {
        $this->table = $this->db->table($tableName);
        return $this->table;
    }
    
    /**
     * set another exception class if needed
     * @param string $className
     * @return \DBModel
     */
    public function setExceptionClass($className){
        $this->exceptionClass = $className;
        return $this;
    }
    
    /**
     * exception class name
     * @return string
     */
    public function getExceptionClass() {
        return $this->exceptionClass;
    }
    
    /**
     * set name of column which is primary key
     * @param string $primaryCol
     * @return \DBModel
     */
    public function setPrimary($primaryCol){
        $this->primaryKey=$primaryCol;
        return $this;
    }

    /**
     * get primary key column
     * @return string
     */
    public function getPrimary(){
        return $this->primaryKey;
    }
    
    /**
     * load one row identified by id
     * @param mixed $id identifier
     * @param bool $toArray convert result to array - default true
     * @return mixed
     */
    public function loadItem($id,$toArray=true){
        $res = $this->getTable()
             ->where($this->primaryKey,$id)
             ->fetch();
        if(!$res){
            return false;
        }
        
        if($toArray==true){
            return $res->toArray();
        }
        else{
            return $res;
        }
    }
    
    /**
     * @param object $e
     * @return void
     * @throws DBModelException
     */
    protected function handleException($e){
        if($e instanceof \Nette\Database\NotNullConstraintViolationException){
            throw new DBModelException("base.dbmodel.notNullException",self::COLUMN_EMPTY_NOT_NULL);
        }
        elseif($e instanceof \Nette\Database\UniqueConstraintViolationException){
            throw new DBModelException("base.dbmodel.uniqueColException",self::COLUMN_MUST_BE_UNIQUE); 
        }
        elseif($e instanceof \Nette\Database\ForeignKeyConstraintViolationException){
            throw new DBModelException("base.dbmodel.foreignKeyException",self::COLUMN_RESTRICT_FOREIGN_KEY);
        }
        elseif($e instanceof \BaseModule\DBModelException && $e->getCode()==self::MAX_NUMBER_REACHED){
            throw new $this->exceptionClass($e->getMessage(),self::MAX_NUMBER_REACHED);
        }
        elseif($e instanceof \BaseModule\DBModelException){
            throw $e;
        }
        else{
            throw new DBModelException("base.dbmodel.undefinedException",self::COLUMN_UNDEFINED_EXCEPTION);
        }
        return;
    }
    
    /**
     * change one value in collum and rows
     * @param mixed $id
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function changeItem($id,$key,$value){
        try {
            $this->getTable()
                  ->where($this->primaryKey,$id)
                  ->update([$key=>$value]);            
        } catch (\Exception $e) {
            $this->handleException($e);
        }
        return true;
    }
    
    /**
     * check if value exists in table row specified by key
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    public function valueExists($key,$value){
        $num = $this->getTable()->where($key,$value)->count();
        if($num!=0){
            return true;
        }
        else{
            return false;
        }
    }
    
    /**
     * intelligently save row to table
     * automaticly identify new or edited item
     * if required check uniqueness of required items
     * primary collumn must be set !!
     * @param array $data
     * @throws Exception
     * @return integer
     */
    public function saveItem($data){
        if($this->primaryKey==""){
            throw new $this->exceptionClass("base.dbmodel.unsetPrimaryKey",self::UNSET_PRIMARY_KEY);
        }
        $primary = isset($data[$this->primaryKey]) ? $data[$this->primaryKey] : null;
        if ($primary==null){
            return $this->addItem($data);
        }
        else{
            if(count($this->getTable()->where($this->primaryKey,$primary))==0){
                return $this->addItem($data);
            }
            else{
                return $this->updateItem($data);
            }
        }
    }
    
    /**
     * delete item
     * @param mixed $id
     * @return void
     */
    public function deleteItem($id){
        // if tree structure delete all children items
        if($this->treeParentRow!=null){
            $children = $this->getChildren($id)->fetchAll();
            if(count($children)>0){
                foreach($children as $child){
                    $this->deleteItem($child[$this->primaryKey]);
                }                
            }
        }
        
        // delete item
        try {
            $this->getTable()
                 ->where($this->primaryKey,$id)
                 ->delete();
        } catch (\Exception $e) {
            $this->handleException($e);
        }
        return true;
    }
    
    /**
     * add item
     * @param array $data
     * @return integer
     * @throws exception
     */
    public function addItem($data){
        try {
            return $this->getTable()->insert($data);
        } catch (\Exception $e) {
            $this->handleException($e);
        }       
    }
    
    /**
     * update item
     * @param array $data
     * @return void
     * @throws exception
     */
    public function updateItem($data){
        try {
            return $this->getTable()->where($this->primaryKey,$data[$this->primaryKey])->update($data);
        } catch (\Exception $exc) {
            $this->handleException($exc);
        }
    }
    
    /**
     * delete all items in table
     * @return void
     */
    public function deleteAll(){
        try {
            $this->getTable()->delete();
        } catch (\Exception $e) {
            $this->handleException($e);
        }
        return;
    }
    
    /**
     * create random string
     * @param int $length
     * @return string
     */
    public function randomString($length){
            return \Nette\Utils\Random::generate($length);

    }

    /**
     * alias to loadItem
     * @param mixed $id identifier
     * @param bool $toArray convert result to array - default true
     * @return mixed
     */
    public function get($id,$toArray=true){
        return $this->loadItem($id, $toArray);
    }
    
    /**
     * alias to saveItem
     * @param array $data
     * @throws Exception
     * @return integer
     */
    public function set(array $data){
        return $this->saveItem($data);
    }
    
    
    /**
     * alias to deleteItem
     * @param mixed $id
     * @return void
     */
    public function delete($id){
        return $this->deleteItem($id);
    }
    
    /**
     * alias to deleteAll
     * @return type
     */
    public function truncate(){
        return $this->deleteAll();
    }
    
    /**
     * alias to getTable
     * @return \Nette\Database\Selection
     */
    public function all(){
        return $this->getTable();
    }
    
    // TODO
    //public function importCSV($csvString, $append=false, $delimiter=",",$skipEmptyLines=true,$trimFields=true){
    //    $data = $this->parseCSV($csvString, $delimiter, $skipEmptyLines, $trimFields);
    //}
    
    /**
     * parse csv string to array
     * @param string $csv_string
     * @param string $delimiter
     * @param boolean $skip_empty_lines
     * @param boolean $trim_fields
     * @return array
     */
    protected function parseCSV($csv_string, $delimiter=",",$skip_empty_lines=true,$trim_fields=true){
        $enc = preg_replace('/(?<!")""/', '!!Q!!', $csv_string);
        $enc = preg_replace_callback('/"(.*?)"/s',
            function ($field) {
                return urlencode(utf8_encode($field[1]));
            },$enc
        );
        $lines = preg_split($skip_empty_lines ? ($trim_fields ? '/( *\R)+/s' : '/\R+/s') : '/\R/s', $enc);
        return array_map(
            function ($line) use ($delimiter, $trim_fields) {
                $fields = $trim_fields ? array_map('trim', explode($delimiter, $line)) : explode($delimiter, $line);
                return array_map(
                    function ($field) {
                        return str_replace('!!Q!!', '"', utf8_decode(urldecode($field)));
                    },$fields
                );
            }, $lines);
    }
    
    /**
     * test licence restriction
     * @param type $maxNumber
     * @throws DBModelException
     * @return;
     */
    public function licenseRestriction($maxNumber){
        if($this->getTable()
                //->where($this->primaryKey . ">?",0)
                ->count("*") >= $maxNumber){
            throw new $this->exceptionClass("base.maxNumberReached",self::MAX_NUMBER_REACHED);
        } 
        return; 
    }
    
    /**
     * convert \Nette\Database\Table\Selection to associative array
     * @param \Nette\Database\Table\Selection $source
     * @param string $fields comma separated list of fields 
     * @return array
     */
    public function toArray(\Nette\Database\Table\Selection $source,$fields=""){
        if($fields!=""){
            $source->select($fields); 
        }
        else{
            $source->select("*");            
        }
        return array_map('iterator_to_array', $source->fetchAll());
    }
}
