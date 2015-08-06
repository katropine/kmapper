<?php
/**
 * @package    KMapper - katropine
 * @author     Kristian Beres <kristian@katropine.com>
 * @copyright  Katropine (c) 2013, katropine.com
 * @since      Sep 24, 2013
 * @licence    MIT
 *
 * Copyright (c) 2013 Katropine, http://www.katropine.com/
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace KMapper;


/**
 * update @ Apr 11, 2013
 * 
 * Description of Mapper: Extend this class and in constructor set a value (table name) for $this->tableName = 'User'
 * For single table use. If relation one_to_meny override the view methods, delete and save
 * 
 * $UserMP = new UserMapper();
 * *implement in constructor for global entity query, or in a method 
 * 
 * $UserMP->setOptions(array('dbo', 'showErrors', 'nullAsString', 'recordTrackerDisable')); //Optional
 * $UserMP->setFetchFields(array("user.first_name", "user.last_name", "address.zip"));
 * $UserMP->setInnerJoin("address", "id_user", "user", "id");
 * $UserMP->setWhere("user.status != ? AND user.smart = ? AND (address.zip = ? OR address.zip = ?)", array('banned', false, '23000', '21000'));
 * $UserMP->setGroupBy("user.born_date");
 * $UserMP->setOrderBy("user.name ASC");
 * *
 * $UserDataObject = $UserMP->fetchAll(); 
 * $array = $UserDataObject->toArray();
 * 
 * $UserMP->setForceInsert(true); // will insert data ignoring the PRIMARY_KEY, default false (update by PRIMARY_KEY)
 * $UserMP->batchSave($array);
 * 
 * $array('id' => 123, name => 'katropine');
 * 
 * $UserMP->save($array); will update row with id 123, if 'id' is NULL new row will be added
 * 
 * $UserMP->setWhere("AND company_id=? AND role_id=?", array(35, 11)->save($array); update where id = 3 and company_id = 35 and role_id = 11
 * 
 * All setters will return self ($this):
 * $UserMP->setOptions(array())->setSelect(array())->setInnerJoin(..)->setWhere(..)->setGroupBy(..)->setOrderBy(..)->fetchAll();
 *
 * Table Prefix - set in kdbconfig.php
 * $Table = new KMapper\TableMapper('#__t1');
 *
 * Table Prefix, just for this table
 * $Table = new KMapper\TableMapper('#__t1');
 * $Table->setTablePrefix('myprefix_');
 * 
 * TODO: method for searchMatchAgainst
 * 
 * 
 * @author Kriss (kristian@katropine.com)
 * @since Aug 25, 2011
 */
class TableMapper {

    protected $tableName;
    protected $tableAlias;
    protected $tablePrimaryKeyName = 'id';
    protected $orderBy;
    protected $where;
    protected $aclWhere;
    protected $groupBy;
    protected $having;
    protected $forceInsert = false;
    protected $fieldsList = array('*');
    protected $lazyLoad = false;
    public $recordTrackerDisable = false;
    protected $options = array();
    protected $exists = false;
    private $connection = null;
    private $joinOrder;
    private $tablePrefix;
    /**
     * Sets flag befre sql is executed to continue or not
     * 
     * @var boolean 
     */
    private $trigger = true;
    const INSERT = 'INSERT INTO';
    const UPDATE = 'UPDATE';

    protected $arrayParams = array();

    /**
     * 
     * @param string $tableName
     * @param string $tableAlias - no `` around the alias
     */
    public function __construct($tableName = null, $tableAlias = null) {
        $this->tableName = $tableName;
        if ($tableAlias != '') {
            $this->tableAlias = "`" . $tableAlias . "`";
        } else {
            $this->tableAlias = null;
        }
        $this->where = null;
        $this->orderBy = null;
        $this->groupBy = null;
    }
        
    /**
     * If false the sql will not be executet, but a dummy sql will so it returns a empty KDataObject object
     *  
     * @param boolean not numeric
     * @return KDataObject
     */
    public function triggerExecute($trigger = false){
        // set to false on call, just in case
        $this->trigger = false;
        if($trigger === true){
            $this->trigger = true;
        }
        return $this;
    }
    /**
     * 
     * @param string $tableName
     * @param string $tableAlias
     * @return \KMapper\TableMapper
     */
    public function setFrom($tableName, $tableAlias = null) {
        return $this->setTable($tableName, $tableAlias);
    }
    /**
     * 
     * @param string $tableName
     * @param string $tableAlias
     * @return \KDB\TableMapper
     */
    public function setTable($tableName, $tableAlias = null) {
        $this->tableName = $tableName;
        if ($tableAlias != '') {
            $this->tableAlias = "`" . $tableAlias . "`";
        } else {
            $this->tableAlias = null;
        }
        return $this;
    }

    /**
     * Connection name from kdbconfig.php
     * 
     * @param string $default
     */
    public function setDbConnection(\KMapper\MySqlDbConnect $MySqlDbConnect) {
        $this->connection = $MySqlDbConnect;
        return $this;
    }

    protected function exe($sql, $arrayValues = array()) {
        
        $options = array();
        if ($this->connection) {
            $options['connection'] = &$this->connection;
        }
        if($this->trigger){
            if (count($arrayValues) > 0) {
                return \KMapper\MySql::execute($sql, $arrayValues, $options);
            }
            return \KMapper\MySql::execute($sql, $this->arrayParams, $options);
        }else{
            return new \KMapper\KDataObject(array());
        }
    }

    /**
     * Set name of the priary key if it is not [id] lawcase
     * 
     * @param string 'id' | 'ID' | 'PID' .....
     * @return TableMapper	
     */
    public function setPrimaryKeyName($string = 'id') {
        $this->tablePrimaryKeyName = $string;
        return $this;
    }

    /**
     *
     * @param array $options 
     * @return TableMapper
     */
    public function setOptions(array $options = array()) {
        $this->options = $options;
        return $this;
    }

    public function setTablePrefix($string = null) {
        $this->tablePrefix = $string;
        return $this;
    }

    /**
     * !! Will not show methods from subclass in netBeans
     * 
     * Gets the Object of the class the static method is called in. 
     * 
     * @return TableMapper
     */
    public static function getInstance() {
        $class = get_called_class();
        return new $class;
    }

    /**
     * If data do NOT exists, set flag to false allow save $Mapper->notExists(array('id_user'=>'11', 'id_message' => '22'))->save($array)
     * 
     * @param array $array
     * @return TableMapper 
     */
    public function notExists(array $array) {

        $string = '';
        foreach ($array as $k => $v) {
            $string .= "AND " . $k . "=? ";
            $arr[] = $v;
        }
        //clone self not to corrupt WHERE clause
        $_this = clone($this);
        $_this->setWhere($string, $arr);
        $this->exists = ($_this->countAll() > 0) ? true : false;
        //return self
        return $this;
    }

    /**
     * Find if record exists 
     * 
     * @param array $array
     * @return boolean
     */
    public function exists(array $array) {
        $string = '';
        foreach ($array as $k => $v) {
            $string .= " AND " . $k . "=? ";
            $arr[] = $v;
        }
        //clone self not to corrupt WHERE clause
        $_this = clone($this);
        $_this->setWhere($string, $arr);
        return ($_this->countAll() > 0) ? true : false;
    }

    /**
     * Override for more custom approach
     * @param array
     * @return KDataObject DataObject 
     */
    public function save(array $array) {
        if (!is_object($this)) {
            throw new \Exception("ERROR: Static call to the " . __CLASS__ . "::save, object expected");
        }
        if ($this->exists === false) {
            if (isset($array[$this->tablePrimaryKeyName]) && $this->forceInsert == false) {
                $SqlOB = $this->buildSaveSql($array, self::UPDATE);
            } else {
                $SqlOB = $this->buildSaveSql($array, self::INSERT);
            }
            $this->forceInsert = false;

            return $this->exe($this->addTablePrefix($SqlOB->sql), $SqlOB->values);
        }else{
            $SqlOB = new \stdClass();
            $SqlOB->sql = '';
        }
        return $this->triggerExecute(false)->exe($this->addTablePrefix($SqlOB->sql));
        
    }

    /**
     * Save array of values 
     * 
     * $arrayarray[0]['name']
     * $arrayarray[1]['name']
     * 
     * USE IN TRANSACTION!!!
     * 
     * @param array $arrayarray
     * @return array array of MySql DataObjects 
     */
    public function batchSave(array $arrayarray) {

        if (!is_object($this)) {
            throw new \Exception("ERROR: Static call to the " . __CLASS__ . "::" . __METHOD__ . ", object expected");
        }
        $dataObject = '';
        $updete = array();
        $insert = array();
        $outcome = false;
        foreach ($arrayarray as $row) {
            if (isset($row[$this->tablePrimaryKeyName]) && 0 < (int) $row[$this->tablePrimaryKeyName] && $this->forceInsert == false) {
                $updete[] = $row;
            } else {
                $insert[] = $row;
            }
        }
        $i = 0;
        if (count($updete) > 0) {
            $SqlOB = $this->buildSaveSql($updete, self::UPDATE);
            foreach ($SqlOB->sql as $sql) {
                $dataObject = $this->exe($this->addTablePrefix($sql), $SqlOB->values[$i]);
                $i++;
                if(!$dataObject->isSuccess()){
                    throw new \Exception("Failed to execute: {$sql}");
                }
                $outcome = true;
            }
        }
        $j = 0;
        if (count($insert) > 0) {
            $SqlOB = $this->buildSaveSql($insert, self::INSERT);
            foreach ($SqlOB->sql as $sql) {
                $dataObject = $this->exe($this->addTablePrefix($sql), $SqlOB->values[$j]);
                $j++;
                if(!$dataObject->isSuccess()){
                    throw new \Exception("Failed to execute: {$sql}");
                }
                $outcome = true;
            }
        }
        
        $this->forceInsert = false;
        $DO = new \KMapper\KDataObject($array = array(), $sql = null, $params = null, count($arrayarray), $numFields = null, $lastId = null, $outcome, $queryTime = null);
        return $DO;
    }

    /**
     * Will ignore the ID ande save record like new entery
     * <p style="color:red;"><b>
     * !important: it will reset to false after SAVE
     * </b></p>
     * 
     * @param boolean $force 
     */
    public function setForceInsert($force = false) {
        $this->checkIntegrity();
        if (!is_bool($force)) {
            throw new \Exception("Boolean expected, " . gettype($force) . " set.");
        }
        $this->forceInsert = $force;
    }

    /**
     * 
     * @param type $offset
     * @param type $limit
     * @return KDataObject
     */
    public function fetch($offset, $limit) {
        $this->checkIntegrity();
        $sql = "SELECT {$this->getFetchFields()} FROM `{$this->tableName}` {$this->tableAlias} {$this->getJoins()} WHERE {$this->aclWhere} {$this->getWhere()} {$this->getOrderBy()} {$this->getGroupBy()} {$this->gethaving()} LIMIT " . $offset . ", " . $limit;
        return $this->exe($this->addTablePrefix($sql));
    }

    /**
     * 
     * @param type $id
     * @return KDataObject
     */
    public function fetchById($id) {
        $this->checkIntegrity();
        $partial = "{$this->tablePrimaryKeyName} = ?";
        $tableName = ($this->tableAlias != '')? $this->tableAlias : "`{$this->tableName}`";
        $sql = "SELECT {$this->getFetchFields()} FROM `{$this->tableName}` {$this->tableAlias} {$this->getJoins()} WHERE {$this->aclWhere} {$tableName}.{$partial} AND {$this->getWhere()} {$this->getGroupBy()} {$this->gethaving()} LIMIT 1";
        $newArray = array_merge(array($id), $this->arrayParams);
        return $this->exe($this->addTablePrefix($sql), $newArray);
    }

    /**
     * 
     * @return KDataObject
     */
    public function fetchAll() {
        $this->checkIntegrity();
        $sql = "SELECT {$this->getFetchFields()} FROM `{$this->tableName}` {$this->tableAlias} {$this->getJoins()} WHERE {$this->aclWhere} {$this->getWhere()} {$this->getOrderBy()} {$this->getGroupBy()} {$this->gethaving()}";
        return $this->exe($this->addTablePrefix($sql));
    }

    /**
     * 
     * @return KDataObject
     */
    public function fetchOne() {
        $this->checkIntegrity();
        $sql = "SELECT {$this->getFetchFields()} FROM `{$this->tableName}` {$this->tableAlias} {$this->getJoins()} WHERE {$this->aclWhere} {$this->getWhere()} {$this->gethaving()} LIMIT 1";
        return $this->exe($this->addTablePrefix($sql));
    }

    /**
     * 
     * @return int
     */
    public function countAll() {
        $this->checkIntegrity();
        $tableName = ($this->tableAlias) ? $this->tableAlias : $this->tableName;
        $sql = "SELECT COUNT({$tableName}.`{$this->tablePrimaryKeyName}`) AS REZ FROM `{$this->tableName}` {$this->tableAlias} {$this->getJoins()} WHERE {$this->aclWhere} {$this->getWhere()} {$this->gethaving()};";
        $RezDO = $this->exe($this->addTablePrefix($sql));
        $rez = $RezDO->toSingleRow();
        return $rez['REZ'];
    }

    /**
     * 
     * @param array $array array('user_id' => 21, 'username' => 'kriss') or array('user_id' => array(21, \PDO::PARAM_INT), 'username' => array('kriss', \PDO::PARAM_STR))
     * @return KDataObject
     * @throws \Exception
     */
    public function update(array $array) {
        $this->checkIntegrity();
        if ($this->getWhere() == ' 1 ') {
            throw new \Exception("WHERE clause is not set");
        }
        foreach ($array as $k => $v) {
            $params[] = $k . "=?";
            $values[] = $v;
        }
        $paramString = implode(',', $params);
        $sql = "UPDATE `{$this->tableName}` SET {$paramString} WHERE {$this->getWhere()};";
        $newArray = array_merge($values, $this->arrayParams);
        return $this->exe($this->addTablePrefix($sql), $newArray);
    }

    /**
     * 
     * @param array $array array('user_id' => 21, 'username' => 'kriss') or array('user_id' => array(21, \PDO::PARAM_INT), 'username' => array('kriss', \PDO::PARAM_STR))
     * @return KDataObject
     */
    public function insert(array $array) {
        $this->checkIntegrity();
        foreach ($array as $k => $v) {
            $params[] = $k . "=?";
            $values[] = $v;
        }
        $paramString = implode(',', $params);
        $sql = "INSERT INTO `{$this->tableName}` SET {$paramString};";
        return $this->exe($this->addTablePrefix($sql), $values);
    }

    /**
     * Delete, requires:
     * 
     * Set WHERE
     * $TableMapper->setWhere('id = ?', array(2));
     * @return KDataObject DataObject
     */
    public function delete() {
        $this->checkIntegrity();

        if ($this->getWhere() == ' 1 ') {
            throw new \Exception("WHERE clause is not set");
        }
        $sql = "DELETE FROM `{$this->tableName}` WHERE {$this->aclWhere} {$this->getWhere()};";
        return $this->exe($this->addTablePrefix($sql));
    }

    /**
     * Delete by ID
     * 
     * @param int $id
     * @return KDataObject DataObject
     */
    public function deleteById($id) {
        $this->checkIntegrity();
        $partial = "{$this->tablePrimaryKeyName} = ?";
        $newArray = array_merge($this->arrayParams, array($id));
        $sql = "DELETE FROM `{$this->tableName}` WHERE {$this->aclWhere} {$partial};";
        return $this->exe($this->addTablePrefix($sql), $newArray);
    }

    /**
     * Extend this class
     * 
     * @param strinh $idName index name
     * @param int $id  id vlue
     * @param int $offset 
     * @param int $limit
     * @return KDataObject DataObject 
     */
    public function fetchByForeignId($idName, $id, $offset = null, $limit = null) {
        $this->checkIntegrity();
        if ($limit != null) {
            $sqllimit = "LIMIT " . $offset . ", " . $limit;
        }
        $sql = "SELECT {$this->getFetchFields()} FROM `{$this->tableName}` {$this->getJoins()} WHERE `{$this->tableName}`.`{$idName}` = '" . $id . "' AND {$this->aclWhere} {$this->getWhere()} {$this->getGroupBy()} " . $sqllimit;
        return $this->exe($this->addTablePrefix($sql));
    }

    /*     * **************************************************************************** */

    public function getTableName() {
        return $this->tableName;
    }

    /**
     * Setter
     * 
     * $Mapper->setWhere("ID = '?' AND Name = '?' OR Name = '?'", array('5545', 'test', 'fubar')); 
     * or unsecure
     * $Mapper->setWhere("ID = '5545' AND Name = 'test' OR Name = 'fubar'");   !!!unsecure
     * 
     * @param string
     * @param array $arrayParams [optional]
     * @return TableMapper
     */
    public function setWhere($string = '', $arrayParams = false) {
        if (is_array($arrayParams) && count($arrayParams) > 0) {
            $this->arrayParams = array_merge($this->arrayParams, $arrayParams);
            //$string = self::buildPartial($string, $arrayParams);
        }
        $this->where .= $string . " ";
        return $this;
    }

    /**
     * NOT IMPLEMENTED
     * 
     * Use for Access Control, define in mapper constructor 
     * 
     * 
     * This will be a predefined condition in WHERE clause, it can NOT be overriden by setWhere();
     * 
     * $User = new LimitedUserMapper(); -> in constructor $this->setAclWhere("limited = true");
     * $User->setWhere("first_name = 'Fu'");
     * $User->setWhere("last_name = 'Bar'");
     * 
     * SQL:
     * SELECT * FROM user WHERE limited = true AND first_name = 'Fu' AND last_name = 'Bar';
     * 
     * $this->setAclWhere("ID = ? AND Name = ? OR Name = ?", array('5545', 'test', 'fubar')); 
     * or unsecure
     * $this->setAclWhere("ID = '5545' AND Name = 'test' OR Name = 'fubar'");   !!!unsecure
     * 
     * @param string
     * @param array $arrayParams [optional]
     * @return TableMapper
     */
    protected function setAclWhere($string = '', $arrayParams = false) {
//        if(is_array($arrayParams) && count($arrayParams) > 0){
//            $string = self::buildPartial($string, $arrayParams);
//        }
//        $this->aclWhere = $string. " AND ";
//	return $this;
        return '';
    }

    /**
     * WARNING: this can break the SQL if  WHERE clouse is set up with fields from other tables
     * consider changes to setWhere, setFetchFields  
     * 
     * @param bool $bool 
     * @return TableMapper
     */
    public function lazyLoad($bool) {
        $this->setSelect(array("`{$this->tableName}`.*"));
        $this->lazyLoad = (bool) $bool;
        return $this;
    }

    /**
     * LEFT OUTER JOIN `$table`.`$joinField` ON `$onTable`.`$onField`
     * 
     * @param string $table can have alias see $tableAlias
     * @param string $joinField
     * @param string $onTable
     * @param string $onField 
     * @param string $tableAlias optional - alias for $table 
     * @return TableMapper
     */
    public function setLeftJoin($table, $joinField, $onTable, $onField, $tableAlias = null) {
        if ($tableAlias) {
            $this->joinOrder[] = "LEFT OUTER JOIN `{$table}` `{$tableAlias}` ON `{$onTable}`.`{$onField}` = `{$tableAlias}`.`{$joinField}`";
        } else {
            $this->joinOrder[] = "LEFT OUTER JOIN `{$table}` ON `{$onTable}`.`{$onField}` = `{$table}`.`{$joinField}`";
        }
        return $this;
    }

    /**
     * RIGHT OUTER JOIN `$table`.`$joinField` ON `$onTable`.`$onField`
     * 
     * @param string $table can have alias see $tableAlias
     * @param string $joinField
     * @param string $onTable
     * @param string $onField 
     * @param string $tableAlias optional - alias for $table 
     * @return TableMapper
     */
    public function setRightJoin($table, $joinField, $onTable, $onField, $tableAlias = null) {
        if ($tableAlias) {
            $this->joinOrder[] = "RIGHT OUTER JOIN `{$table}` `{$tableAlias}` ON `{$onTable}`.`{$onField}` = `{$tableAlias}`.`{$joinField}`";
        } else {
            $this->joinOrder[] = "RIGHT OUTER JOIN `{$table}` ON `{$onTable}`.`{$onField}` = `{$table}`.`{$joinField}`";
        }
        return $this;
    }

    /**
     * INNER JOIN `$table`.`$joinField` ON `$onTable`.`$onField`
     * 
     * @param string $table can have alias see $tableAlias
     * @param string $joinField
     * @param string $onTable
     * @param string $onField 
     * @param string $tableAlias optional - alias for $table 
     * @return TableMapper
     */
    public function setInnerJoin($table, $joinField, $onTable, $onField, $tableAlias = null) {
        if ($tableAlias) {
            $this->joinOrder[] = "INNER JOIN `{$table}` `{$tableAlias}` ON `{$onTable}`.`{$onField}` = `{$tableAlias}`.`{$joinField}`";
        } else {
            $this->joinOrder[] = "INNER JOIN `{$table}` ON `{$onTable}`.`{$onField}` = `{$table}`.`{$joinField}`";
        }
        return $this;
    }

    /**
     * NOT SUPPORTED BY MYSQL
     * 
     * FULL OUTER JOIN `$table`.`$joinField` ON `$onTable`.`$onField`
     * 
     * 
     * @param string $table can have alias see $tableAlias
     * @param string $joinField
     * @param string $onTable
     * @param string $onField 
     * @param string $tableAlias optional - alias for $table 
     * @return TableMapper
     */
    public function setFullJoin($table, $joinField, $onTable, $onField, $tableAlias = null) {
        if ($tableAlias) {
            $this->joinOrder[] = "FULL OUTER JOIN `{$table}` `{$tableAlias}` ON `{$onTable}`.`{$onField}` = `{$tableAlias}`.`{$joinField}`";
        } else {
            $this->joinOrder[] = "FULL OUTER JOIN `{$table}` ON `{$onTable}`.`{$onField}` = `{$table}`.`{$joinField}`";
        }
        return $this;
    }

    /**
     * Setter
     * 
     * $Mapper->setOrderBy("ID, Name DESC"); 
     * @param string $string 
     * @return TableMapper
     */
    public function setOrderBy($string) {
        $this->orderBy = $string;
        return $this;
    }

    /**
     * Setter
     * 
     * $Mapper->setGroupBy("ID");
     * @param string $string 
     * @return TableMapper
     */
    public function setGroupBy($string) {
        $this->groupBy = $string;
        return $this;
    }
    
    /**
     * Setter
     * 
     * @param string $string
     * @return \KMapper\TableMapper
     */
    public function setHaving($string){
        $this->having = $string;
        return $this;
    }

    /**
     * Generates string
     * 
     * @return string
     */
    protected function getOrderBy() {
        if ($this->orderBy != null) {
            return " ORDER BY " . $this->orderBy;
        }
        return '';
    }
    
    /**
     * Generates string
     * 
     * @return string
     */
    protected function getHaving(){
        if ($this->having != null) {
            return " HAVING " . $this->having;
        }
        return '';
    }


    /**
     * 
     * @param array $array ("PRIMARY_KEY AS ID", "Price")
     * @param boolean $resetBuffer true will clear default value and everride with new (prevent array merge)
     * @return TableMapper
     */
    public function setSelect($array = array(), $resetBuffer = false) {
        //remove initial value *
        if (count($this->fieldsList) == 1 && $this->fieldsList[0] == '*' && count($array) > 0) {
            $this->fieldsList = array();
        }
        if ($resetBuffer) {
            $this->fieldsList = $array;
        } else {
            $this->fieldsList = array_merge($this->fieldsList, $array);
        }
        return $this;
    }

    /**
     * Generetes string
     * 
     * @return string
     */
    protected function getFetchFields() {
        return implode(', ', $this->fieldsList);
    }

    /**
     * Generetes string
     * 
     * @return string
     */
    protected function getGroupBy() {
        if ($this->groupBy != '') {
            return " GROUP BY " . $this->groupBy;
        }
        return '';
    }

    /**
     * Generetes string
     * 
     * @return string
     */
    protected function getWhere() {
        $this->where = ltrim($this->where);
        if ($this->where != null) {
            if (strpos($this->where, 'AND') == 0) {
                $this->where = preg_replace('/AND/', '', $this->where, 1);
            }
            if (strpos($this->where, 'OR') == 0) {
                $this->where = preg_replace('/OR/', '', $this->where, 1);
            }
            return $this->where;
        } else {
            return " 1 ";
        }
    }

    /**
     * Will clear the WHERE buffer
     * @return KDataObject
     */
    public function clearWhere() {
        $this->where = '';
        return $this;
    }

    /**
     * fetchDuplicatesByFieldName
     * @param array array("firstname", "lastname")
     * @return KDataObject
     */
    public function fetchDuplicatesByFieldName(array $array) {
        $select = implode(', ', $array);
        $sql = "SELECT {$select}, COUNT(*) 
                FROM {$this->tableName} 
                GROUP BY {$select} 
                HAVING COUNT(*) > 1;
         ";
        return $this->exe($this->addTablePrefix($sql));
    }

    /**
     * get table columns
     * 
     * @return KDataObject
     */
    public function getColumns() {
        $sql = "SHOW COLUMNS FROM {$this->tableName}";
        return $this->exe($this->addTablePrefix($sql));
    }

    /**
     * Will clear the WHERE buffer, first condition
     */
    protected function clearAclWhere() {
        $this->aclWhere = '';
    }

    /**
     * Check dependencies
     * 
     * @throws Exception if no tabel name specified
     */
    protected function checkIntegrity() {
        if ($this->tableName == null) {
            throw new \Exception('<b>tableName must be set in constructor</b>');
        }
    }

    /**
     * Generetes string
     *
     * @param array $params
     * @param self::CONST $sqlType
     * @return stdClass 
     */
    protected function buildSaveSql($params, $sqlType = false) {
        $sql = "";
        try {
            if (!$sqlType) {
                throw new \Exception("<b>SQL type is not set</b>");
            }
            $values = array();
            $SQLPrepared = new \stdClass();
            if (isset($params[0]) && is_array($params[0])) {
                // batch save: array(array)
                if (count($params) > 0) {
                    if ($sqlType == self::INSERT) {
                        $sql .= $sqlType . " `" . $this->tableName . "` ";
                        $keys = array_keys($params[0]);
                        foreach ($params as $row) {
                            $valuesPlaceHolder = array();
                            foreach ($row as $k => $v) {
                                $values[] = $v;
                                $valuesPlaceHolder[] = '?';
                            }

                            $valuesPlaceHolders[] = "(" . implode(",", $valuesPlaceHolder) . ")";
                        }

                        $stringColumns = implode(",", $keys);
                        $stringValues = implode(",", $valuesPlaceHolders);
                        $sql .= "({$stringColumns}) VALUES {$stringValues}";
                        $SQLPrepared->sql[] = $sql;
                        $SQLPrepared->values[] = $values;
                    } else {
                        foreach ($params as $row) {
                            $sql = $sqlType . " `" . $this->tableName . "` SET ";

                            foreach ($row as $key => $val) {
                                if ($key != $this->tablePrimaryKeyName) {
                                    if ($val === null) {
                                        $sql .= $key . " = ?, ";
                                        $values[] = null;
                                    } else {
                                        $sql .= $key . " = ?, ";
                                        $values[] = $val;
                                    }
                                }
                            }
                            $sql = substr($sql, 0, strlen($sql) - 2);
                            $sql .= " WHERE {$this->tablePrimaryKeyName} = ?";
                            if ($this->getWhere() != " 1 ") {
                                $sql .= $this->getWhere();
                                $values = array_merge((array) $values, (array) $this->arrayParams);
                            }
                            $values = array_merge((array) $values, (array) $row[$this->tablePrimaryKeyName]);
                            $SQLPrepared->sql[] = $sql;
                            $SQLPrepared->values[] = $values;
                        }
                    }
                }

                return $SQLPrepared;
            } else if (is_array($params)) {
                // save array()

                $sql .= $sqlType . " `" . $this->tableName . "` SET ";
                if (count($params) > 0) {
                    foreach ($params as $key => $val) {
                        if ($key != $this->tablePrimaryKeyName) {
                            if ($val === null) {
                                $sql .= $key . " = ?, ";
                                $values[] = null;
                            } else {
                                $sql .= $key . " = ?, ";
                                $values[] = $val;
                            }
                        }
                    }

                    $sql = substr($sql, 0, strlen($sql) - 2);
                    if ($sqlType == self::UPDATE) {
                        $values = array_merge((array) $values, (array) $params[$this->tablePrimaryKeyName]);
                        $sql .= " WHERE {$this->tablePrimaryKeyName} = ?";
                    }

                    $SQLPrepared->sql = $sql;
                    $SQLPrepared->values = $values;
                    return $SQLPrepared;
                }
            }
            return false;
        } catch (\Exception $E) {
            die($E->__toString());
        }
    }
    /**
     *  @deprecated
     *  prefix is not responsibility of TableMapper anymore
     */
    protected function addTablePrefix($sql) {
        
        if ($this->tablePrefix != null) {
            return str_replace("#__", $this->tablePrefix, $sql);
        }
        return $sql;
    }

    /**
     * Generates string
     * 
     * @return string
     */
    protected function getJoins() {
        if ($this->lazyLoad === false) {
            $joinString = '';
            if (count($this->joinOrder) > 0) {
                foreach ($this->joinOrder as $join) {
                    $joinString .= $join;
                }
            }
            return $joinString;
        }
        return '';
    }

}
