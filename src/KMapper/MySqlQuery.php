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

    private $options;
    private $result = null;

    public function __construct($sql = false, $params = null, array $options = array()) {
        if($sql != false){
            $this->options = $options;
            $settings = $this->options['connection']->getSettings();
            $sql = str_replace('#__', $settings['prefix'], $sql);
            
            if (is_array($params) && count($params) > 0) {
                $this->result = $this->execute($sql, $params);
            } else {
                $this->result = $this->query($sql);
            }
        }
    }
    /**
     * 
     * @return KDataObject
     */
    public function getResult(){
        return $this->result;
    }
    
    protected function execute($sql, $params) {
        
        $time_start = $this->getMicroTime();
      
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
        
        $response = ($success !== false) ? true : false;

        $lastid = $this->options['connection']->getPdo()->lastInsertId();
        $time_end = $this->getMicroTime();
        $query_time = $time_end - $time_start;
        
        $dataArray = null;
        if ($PdoSth) {
            //Update,Insert,Delete, Drop
            $numrows = $PdoSth->rowCount();
            //Select, Describe, Show, Explane
            if ($PdoSth instanceof \PDOStatement) {
                $numfields = $PdoSth->columnCount();
                if ($numfields != 0) {
                    $dataArray = $PdoSth->fetchAll();
                    $PdoSth = null;
                    $this->options['connection'] = null;
                }
            }
        } else if ($PdoSth == false) {
            $numrows = 0;
            $numfields = 0;
        }

        $res = new \KMapper\KDataObject($dataArray, $sql, $params, $numrows, $numfields, $lastid, $response, $query_time);
        return $res;
        
    }

    /**
     * 
     * @param string $sql
     * @return void
     */
    protected function query($sql) {

        $time_start = $this->getMicroTime();

        $result = $this->options['connection']->getPdo()->query($sql);
        
        if($this->options['connection']->getPdo()->getAttribute(\PDO::ATTR_ERRMODE) == \PDO::ERRMODE_EXCEPTION && $result === false){
            throw new \PDOException("ERROR: " . implode(":", $this->options['connection']->getPdo()->errorInfo()));
        }

        $response = ($result === false) ? false : true;

        $lastid = $this->options['connection']->getPdo()->lastInsertId();
        $time_end = $this->getMicroTime();
        $query_time = $time_end - $time_start;

        $dataArray = null;
        if ($result) {
            //Update,Insert,Delete, Drop
            $numrows = $result->rowCount();
            //Select, Describe, Show, Explane
            if ($result instanceof \PDOStatement) {
                $numfields = $result->columnCount();
                if ($numfields != 0) {
                    $dataArray = $result->fetchAll();
                    $result = null;
                    $this->options['connection'] = null;
                }
            }
        } else if ($result == false) {
            $numrows = 0;
            $numfields = 0;
        }

        $res = new \KMapper\KDataObject($dataArray, $sql, $params = null, $numrows, $numfields, $lastid, $response, $query_time);
        return $res;
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

}