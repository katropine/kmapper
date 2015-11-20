<?php
/** 
 * @author     kristian@katropine.com
 * @copyright  Katropine (c) 2013, katropine.com
 * @since      Jun 25, 2014
 */

require_once("../bootstrap.php");

use KMapper\MySql;
use KMapper\TableMapper;
use KMapper\Tool\Matrix;

class ToolTest extends PHPUnit_Framework_TestCase{
    protected static $original = array(
          ["id"=>"1", "name"=>"Name1"],
          ["id"=>"2", "name"=>"Name2"],
          ["id"=>"3", "name"=>"Name3"],
    );
    

    protected static $new = array(
          ["id"=>"1", "name"=>"Changed"],
          ["id"=>"2", "name"=>"Name2"],
          ["id"=>"3", "name"=>"Name3"],
          ["id"=>"4", "name"=>"New"],
    );

    /**
     * Tools
     */
    public function testMatrixDiff(){
        $diff = Matrix::findDiff(self::$original, self::$new);
        $this->assertEquals(5, $diff[0]['id']+$diff[1]['id']);
    }
    public function testMatrixNew(){
        $diff = Matrix::findNew(self::$original, self::$new, 'id');
        $this->assertEquals(4, $diff[0]['id']);
    }
    public function testMatrixChanged(){
        $diff = Matrix::findDiff(self::$original, self::$new, 'id');
        $this->assertEquals(1, $diff[0]['id']);
    }
}