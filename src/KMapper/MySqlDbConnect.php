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
 * Create a kmapper.php in the same DIR as KMapper.php
 * 
  return array(
  'default' => array(
  'host'        => 'localhost',
  'dbname'      => 'defaultdb',
  'user'        => 'root',
  'password'    => '123',
  'prefix'      => 'myprefix_'
  'pdoattributes' => array(
  array(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC),
  array(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION),
  array(\PDO::ATTR_EMULATE_PREPARES, false)
  )
  ),
  'db1' => array(
  'host'        => 'localhost',
  'dbname'      => 'db1',
  'user'        => 'root',
  'password'    => '123'
  )
  );

 * 
 * Multiple connections MySqlConnect
 * 
 * 
 * $options['connection'] = new  KMapper\MySqlConnect('db1');<br>
 * 
 * $DataObject = KMapper\MySql::query("SELECT * FROM t1", $options);<br>
 * $DataObject = KMapper\MySql::execute("SELECT * FROM t1 WHERE id = ?", array(12), $options);<br>
 * 
 * Single connection use
 *  
 * $DataObject = KMapper\MySql::query("SELECT * FROM t1");<br>
 * $DataObject = KMapper\MySql::execute("SELECT * FROM t1 WHERE id = ? AND age = ?", array(12, 25));<br>
 * 
 * $DataObject = KMapper\MySql::execute("SELECT * FROM t1 WHERE id = ? AND age = ?", array(12, 25));<br>
 *
 * or
 * 
 * $params = array(
 *      array(12, PDO::PARAM_INT),
 *      array(25, PDO::PARAM_INT),
 *      array('Kristian', PDO::PARAM_STR),
 * );
 * $DataObject = KMapper\MySql::execute("SELECT * FROM t1 WHERE id = ? AND age = ? AND name = ?", $params);<br>
 * 
 * Prefix
 * $DataObject = KMapper\MySql::query("SELECT * FROM #__t1"); // #__t1 -> myprefix_t1 
 * 
 * For more advanced table manipulation see KMapper\TableMapper(TABLE_NAME)
 *
 * @author Kriss (kristian@katropine.com)
 * @since Dec 22, 2011
 */
class MySqlDbConnect {

    private $db = null;
    private $settings = null;

    /**
     * 
     * @param string $dbSettingsName - db connection name from kdbconfig.php
     */
    public function __construct($dbSettingsName = 'default') {
        $cf = require '/../app/config/kmapper.php';
        $config = $cf[$dbSettingsName];
        try {
            $this->db = new \PDO("mysql:host={$config['host']};dbname={$config['dbname']}", $config['user'], $config['password'], array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
            $this->settings = $config;
        } catch (\PDOException $E) {
            die($E->getMessage());
        }
        if (isset($config['pdoattributes']) && $config['pdoattributes'] > 0) {
            foreach ($config['pdoattributes'] as $attr) {
                $this->db->setAttribute($attr[0], $attr[1]);
            }
        }
    }

    /**
     * Returns PDO object
     * 
     * @return \PDO
     */
    public function getPdo() {
        return $this->db;
    }

    public function getSettings() {
        return $this->settings;
    }

}