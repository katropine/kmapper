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
 * MySql Decorator class, Do not call directlly, use MySql::query() or MySql::execute()
 *
 * @author Kriss
 * @see \KMapper\MySql
 */
class MySqlQuery {

    /**
     *
     * @var resource 
     */
    private $shownull = false;
    private $numrows;
    private $numfields;
    private $lastid;
    private $response;
    private $params = array();

    /**
     *
     * @var array matrix 
     */
    private $_dataArray;
    private $sql;
    //error @deprecated
    private $throwerror = false;
    private $error = false;
    private $rawError = '';
    private $options;
    public $errormsg;
    public $query_time;
    public $recordTrackerDisable = false;
    private $result = null;
    /**
     *
     * 
     */
    public function __construct($sql = false, $params = null, array $options = array()) {
        if($sql != false){
            $this->options = $options;
            $settings = $this->options['connection']->getSettings();
            if ($settings['prefix'] != '') {
                $sql = str_replace('#__', $settings['prefix'], $sql);
            }
            if (is_array($params) && count($params) > 0) {
                $this->result = $this->execute($sql, $params);
            } else {
                $this->result = $this->query($sql);
            }
        }
    }
    /**
     * 
     * @return MySqlResult
     */
    public function getResult(){
        return $this->result;
    }
    
    protected function execute($sql, $params) {
        
        $time_start = $this->getMicroTime();

        $this->sql = $sql;
        $this->params = $params;
      
        $PdoSth = $this->options['connection']->getPdo()->prepare($sql); 
        if (count($params) > 0) {
            foreach ($params as $k => $v) {
                if (is_array($v)) {
                    $key = array_shift(array_keys($v));
                    if(is_string($key)){
                        $val = array_shift(array_values($v));
                        $PdoSth->bindValue($key, $val, $v[0]);
                        
                    }else{
                        $PdoSth->bindValue($k + 1, $v[0], $v[1]);
                    }
                } else {
                    if(is_string($k)){
                        $PdoSth->bindValue($k, $v);
                    }else{
                        $PdoSth->bindValue($k + 1, $v);
                    }
                }

            }
        }
        $success = $PdoSth->execute();
        
        if($this->options['connection']->getPdo()->getAttribute(\PDO::ATTR_ERRMODE) == \PDO::ERRMODE_EXCEPTION && $PdoSth === false){
            throw new \PDOException("ERROR: " . implode(":", $this->options['connection']->getPdo()->errorInfo()));
        }
        
        $this->response = ($success !== false) ? true : false;

        $this->lastid = $this->options['connection']->getPdo()->lastInsertId();
        $time_end = $this->getMicroTime();
        $this->query_time = $time_end - $time_start;
        

        if ($PdoSth) {
            //Update,Insert,Delete, Drop
            $this->numrows = $PdoSth->rowCount();
            //Select, Describe, Show, Explane
            if ($PdoSth instanceof \PDOStatement) {
                $this->numfields = $PdoSth->columnCount();
                if ($this->numfields != 0) {
                    $this->_dataArray = $PdoSth->fetchAll();
                    $PdoSth = null;
                    $this->errormsg = "ERROR: " . implode(":", $this->options['connection']->getPdo()->errorInfo());
                    $this->options['connection'] = null;
                }
            }
        } else if ($PdoSth == false) {
            $this->numrows = 0;
            $this->numfields = 0;
            $this->error = true;
        }
 
        if (!$this->options['recordTrackerDisable']) {
            $this->recordTracker($sql);
        }
        $res = new \KMapper\MySqlResult($this->_dataArray);
        $this->_dataArray = null;
        return $res;
        
    }

    /**
     * 
     * @param string $sql
     * @return void
     */
    protected function query($sql) {

        $time_start = $this->getMicroTime();

        $this->sql = $sql;
        $result = $this->options['connection']->getPdo()->query($sql);
        
        if($this->options['connection']->getPdo()->getAttribute(\PDO::ATTR_ERRMODE) == \PDO::ERRMODE_EXCEPTION && $result === false){
            throw new \PDOException("ERROR: " . implode(":", $this->options['connection']->getPdo()->errorInfo()));
        }

        $this->response = ($result === false) ? false : true;

        $this->lastid = $this->options['connection']->getPdo()->lastInsertId();
        $time_end = $this->getMicroTime();
        $this->query_time = $time_end - $time_start;


        if ($result) {
            //Update,Insert,Delete, Drop
            $this->numrows = $result->rowCount();
            //Select, Describe, Show, Explane
            if ($result instanceof \PDOStatement) {
                $this->numfields = $result->columnCount();
                if ($this->numfields != 0) {
                    $this->_dataArray = $result->fetchAll();
                    $result = null;
                    $this->options['connection'] = null;
                }
            }
        } else if ($result == false) {
            $this->numrows = 0;
            $this->numfields = 0;
            $this->error = true;
        }
        if ($this->throwerror) {
            $this->getError();
            $this->errormsg = '';
            $this->error = false;
        }

        if (!$this->options['recordTrackerDisable']) {
            $this->recordTracker($sql);
        }
        $res = new \KMapper\MySqlResult($this->_dataArray);
        $this->_dataArray = null;
        return $res;
    }

    /**
     *
     * @return number of rows returned by last query
     */
    public function getNumRows() {
        return $this->numrows;
    }

    /**
     *
     * @return number of fields returned by last query
     */
    public function getNumFields() {
        return $this->numfields;
    }

    /**
     *
     * @return float Execution time of submited query
     */
    public function getQueryTime() {
        return $this->query_time;
    }

    /**
     *
     * @return int Last auto-increment value of table, after insert
     */
    public function getLastID() {
        return $this->lastid;
    }

    /**
     *
     * @return String Error Message
     */
    public function getError() {
        return $this->errormsg;
    }

    protected function getMicroTime() {
        list($usec, $sec) = explode(" ", microtime());
        return ((float) $usec + (float) $sec);
    }

    /**
     * Magic method __set is desabled
     */
    public function __set($index, $value) {
        die("The Class Property: '" . $index . "' can not be declared!");
    }

    /**
     * Magic method __get is desabled
     */
    public function __get($index) {
        die("Class Property: '" . $index . "' not declared!");
    }

    /**
     *
     * @return boolean false if  
     */
    public function getResponse() {
        return $this->response;
    }

    /*     * ****************************************************************************************************************************** */

    /**
     * Tracks INSERT/UPDATE query in TABLE_RECORD_TRACKER 
     *  
     * @param string $query 
     */
    private function recordTracker($query) {
        
    }

    /**
     * Simulates the query with replaced plaeholders
     * For testing purposes ONLY!!
     * 
     * @return string
     */
    public function getSql() {
        return self::buildSql($this->sql, $this->params);
    }

    protected static function buildSql($sqlpart, $params) {
        $params = array_map(array(__CLASS__, 'filterValue'), $params);
        $sqlpart = self::expandPlaceholders($sqlpart, $params);
        return $sqlpart;
    }

    /**
     * filter
     * 
     * @param mixed $value
     * @return mixed
     */
    protected static function filterValue($value) {
        if (get_magic_quotes_gpc()) {
            $value = stripslashes($value);
        }
        if (is_string($value) && !is_numeric($value)) {
            $value = trim($value);
            $value = "'" . $value . "'";
        }
        if (is_null($value)) {
            $value = "null";
        }
        return $value;
    }

    /**
     * replace placeholders (?) with values from params
     * 
     * @param string $sql with placeholders
     * @param array $params values for placeholders
     * @return string
     * @throws Exception if placeholders and values number mismatch
     */
    protected static function expandPlaceholders($sql, array $params) {
        $sql = (string) $sql;
        $params = array_values($params);
        $offset = 0;
        foreach ($params as $p) {
            if (is_array($p)) {
                $param = $p[0];
            } else {
                $param = $p;
            }
            $place = strpos($sql, '?', $offset);
            if ($place === false) {
                throw new \Exception('Parameter / Placeholder count mismatch. Not enough placeholders for all parameters.');
            }
            $sql = substr_replace($sql, $param, $place, 1);
            $offset = $place + strlen($param);
        }
        return $sql;
    }

}