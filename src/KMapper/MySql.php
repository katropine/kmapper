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

namespace Katropine\KMapper;

/**
 * Execute query and return MySqlQuery object
 */
class MySql {

    /**
     * Unprotected, use if no data required from user. See: MySql::execute() method
     * 
     * @param string $sql
     * @param $options - [resource 'dbo',  bool 'nullAsString', bool 'recordTrackerDisable'] - 0ptional 
     * @return MySqlQuery
     */
    public static function query($sql, array $options = array()) {
        $options['recordTrackerDisable'] = ( isset($options['recordTrackerDisable']) ? $options['recordTrackerDisable'] : false);

        try {
            if ($sql == '') {
                throw new \Exception("Not a valid SQL.");
            }
            //just in case
            $options['connection'] = self::extractDbo($options);
            $MySql = new MySqlQuery($sql, null, $options);


            if (!$MySql instanceof MySqlQuery) {
                throw new \Exception("Faild to create MySqlQuery object.");
            }
        } catch (Exception $E) {
            die($E->__toString());
        }

        if (!$MySql->getResponse()) {
            throw new \Exception($MySql->getError());
        }
        return $MySql;
    }

    /**
     *
     * @param type $sql = "INSERT INTO table SET field1 = '?', field2 = '?';"
     * @param array $params = ('Val1', 1, 6)
     * @param array $options - [resource 'dbo',  bool 'nullAsString', bool 'recordTrackerDisable'] - 0ptional 
     * @return MySqlQuery result object
     */
    public static function execute($sql = '', array $params = array(), array $options = array()) {
        $options['nullAsString'] = ( isset($options['nullAsString']) ? $options['nullAsString'] : false);
        $options['recordTrackerDisable'] = ( isset($options['recordTrackerDisable']) ? $options['recordTrackerDisable'] : false);

        if ($sql == '') {
             throw new \Exception("Not a valid SQL.");
         }

         //just in case
         $options['connection'] = self::extractDbo($options);

         $MySql = new MySqlQuery($sql, $params, $options);


         if (!$MySql instanceof MySqlQuery) {
             throw new \Exception("Faild to create MySqlQuery object.");
         }
 

        if (!$MySql->getResponse()) {
            throw new \Exception($MySql->getError());
        }
        return $MySql->getResult();
    }

    /**
     * @param array $options - [resource 'dbo'] - 0ptional 
     * @return boolean
     *
     *  try{
      if(!MySql::transactionBegin()){
      throw new Exception("Can not open a Transaction");
      }
      $sqlTask = "INSERT INTO sometable SET
      somefield = '".$array['somefield']."'
      ";
      $last = MySql::query($sqlTask)->getLastID();

      if(!$last){
      throw new Exception("Query error");
      }
      $sqlHierarchy = "INSERT INTO Another_Table SET
      id_sometable = $last
      ";
      if(!MySql::query($sqlHierarchy)->getResponse()){
      throw new Exception("Query error");
      }
      if(!MySql::transactionCommit()){
      throw new Exception("Can not Commit");
      }
      }  catch (Exception $E){
      MySql::transactionRollback();
      die("<pre>".$E->__toString()."</pre>");
      }
     */
    public static function transactionBegin(array $options = array()) {
        $dbo = self::extractPdo($options);
        $dbo->beginTransaction();
    }

    /**
     * @param array $options - [resource 'dbo'] - 0ptional
     * @return boolean
     */
    public static function transactionCommit(array $options = array()) {
        $dbo = self::extractPdo($options);
        $dbo->commit();
    }

    /**
     * @param array $options - [resource 'dbo'] - 0ptional
     * @return boolean
     */
    public static function transactionRollback(array $options = array()) {
        $dbo = self::extractPdo($options);
        $dbo->rollBack();
    }

    private static function extractPdo(array $options = array()) {
        return (isset($options['connection']) && $options['connection']->getDbo()->getPdo() instanceof \PDO) ? $options['connection']->getDbo()->getPdo() : MySqlDbWrapper::getInstance()->getDbo()->getPdo();
    }

    private static function extractDbo(array $options = array()) {
        return (isset($options['connection']) && $options['connection']->getDbo() instanceof MySqlDbConnect) ? $options['connection']->getDbo() : MySqlDbWrapper::getInstance()->getDbo();
    }

}
