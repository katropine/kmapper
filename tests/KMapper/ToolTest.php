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

    /**
     * Tools
     */
    public function testMatrixDiff(){
        $original = array(
          ["id"=>"1", "name"=>"Name1"],
          ["id"=>"2", "name"=>"Name2"],
          ["id"=>"3", "name"=>"Name3"],
        );
        // the haystack array

        $new = array(
          ["id"=>"1", "name"=>"Name1"],
          ["id"=>"2", "name"=>"Name2"],
          ["id"=>"3", "name"=>"Name3"],
          ["id"=>"4", "name"=>"Name4"],
        );

        $diff = Matrix::findDiff($original, $new);
        $this->assertEquals(4, $diff[0]['id']);

    }
}