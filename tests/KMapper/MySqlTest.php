<?php

/** 
 * @author     kristian@katropine.com
 * @copyright  Katropine (c) 2013, katropine.com
 * @since      Jun 25, 2014
 */

require_once("../bootstrap.php");

use \KMapper\MySql;
use \KMapper\TableMapper;

class MySqlTest extends PHPUnit_Framework_TestCase{



    public function testCreateTableA(){

    	$DO = MySql::query("CREATE TABLE IF NOT EXISTS `test_table_a` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `name` varchar(255) NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

    	$this->assertEquals(true, $DO->isSuccess());
    } 
    public function testCreateTableB(){

    	$DO = MySql::query("CREATE TABLE IF NOT EXISTS `test_table_b` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `id_a` int(10) unsigned NULL,
			  `name` varchar(255) NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

    	$this->assertEquals(true, $DO->isSuccess());
    } 
    public function testSeedA(){
    	$array = array();
    	for($i = 0; $i <= 99; $i++){
            $array[] = array('name' => "test_{$i}");
    	}

    	$TM = new TableMapper('test_table_a');
    	try{
            MySql::transactionBegin();
            $DO = $TM->batchSave($array);
            if(!$DO->isSuccess()){
                throw new Exception("batchSave(test_table_a) failed!");
            }
            MySql::transactionCommit();
            $this->assertEquals(true, $DO->isSuccess());
    	}catch(PdoException $e){
            MySql::transactionRollback();
    	}
        
        $count = $TM->countAll();
    	$this->assertEquals(100, $count);
    }
    
    public function testSeedB(){
    	$array = array();
    	for($i = 0; $i <= 99; $i++){
            $array[] = array('id_a' => $i+1, 'name' => "test_{$i}");
    	}
        $TM = new TableMapper('test_table_b');
        try{
            MySql::transactionBegin();
            $DO = $TM->batchSave($array);
            if(!$DO->isSuccess()){
                throw new Exception("batchSave(test_table_b) failed!");
            }
            MySql::transactionCommit();
            $this->assertEquals(true, $DO->isSuccess());
    	}catch(PdoException $e){
            MySql::transactionRollback();
    	}
    	$count = $TM->countAll();
    	$this->assertEquals(100, $count);
    }
    
    public function testWhereSingleRow(){
        $TM = new TableMapper('test_table_b');
        $DO = $TM->setWhere("name=?", array('test_88'));
        $row = $DO->fetchOne()->toSingleRow();
        $this->assertEquals(89, $row['id']);
    }
    
    public function testInnerJoin(){
        $TM = new TableMapper('test_table_b', 'b');
        $DO = $TM->setSelect(array('b.name AS name_b', 'a.name AS name_a'))
            ->setInnerJoin('test_table_a', 'id', 'b', 'id_a', 'a')
            ->setWhere("a.id=?", array(array(22, \PDO::PARAM_INT)))
            ->fetchOne();
        $row = $DO->toSingleRow();
        $this->assertEquals($row['name_a'], $row['name_b']); 
        unset($TM);
        unset($DO);
    }
    
    public function testInsertRow(){
        $TM = new TableMapper('test_table_b', 'b');
        $DO = $TM->insert(array('name' => 'blabla'));

        $DO2 = $TM->setSelect()->setWhere("id=?", array($DO->getLastID()))->fetchOne();
        $row = $DO2->toSingleRow();
        $this->assertEquals('blabla', $row['name']); 
        
    }
    
    public function testUpdateRow(){
        $TM = new TableMapper('test_table_b', 'b');
        $DO = $TM->setWhere("name=?", array(array("blabla", \PDO::PARAM_STR)))->update(array('name' => 'blabla_777'));
        $this->assertTrue($DO->isSuccess());
        
        $TM2 = new TableMapper('test_table_b', 'b');
        $DO2 = $TM2->setSelect()->setWhere("name=?", array("blabla_777"))->fetchOne();
        $row = $DO2->toSingleRow();
        $this->assertEquals('blabla_777', $row['name']); 
    }
    
    public function testDeleteById(){
        $TM = new TableMapper('test_table_b', 'b');
        $DO = $TM->deleteById(11);
        
        $DO2 = $TM->fetchById(11);
        $row = $DO2->toSingleRow();
        $this->assertEquals(null, $row); 
    }
    
    public function testSave(){
        
        try{
            MySql::transactionBegin();
            $TM = new TableMapper('test_table_a');
            $DO = $TM->save(array('name' => 'testing'));
            if(!$DO->isSuccess()){
                throw new Exception("faild_1");
            }
            
            $TM2 = new TableMapper('test_table_a');
            $DO2 = $TM2->save(array('id_a' => $DO->getLastID(), 'name' => "testing"));
            if(!$DO2->isSuccess()){
                throw new Exception("faild_2");
            }
            
            $TM3 = new TableMapper('test_table_b');
            $row = $TM3->setWhere("name=? AND id_a=?", array('testing', $DO->getLastID()))->fetchAll()->toSingleRow();
            
            MySql::transactionCommit();
            $this->assertEquals('testing', $row['name']);
            
        }  catch (\PDOException $e){
            MySql::transactionRollback();
        }
    }
    
    public function testGetRowById(){
        $TM3 = new TableMapper('test_table_b');
        $DO3 = $TM3->fetchById(33);
        $row2 = $DO3->toSingleRow();
        $this->assertEquals('test_32', $row2['name']); 
        
        // with table alias
        $TM4 = new TableMapper('test_table_b', 'b');
        $DO4 = $TM4->fetchById(33);
        $row3 = $DO4->toSingleRow();
        $this->assertEquals('test_32', $row3['name']); 
    }
    
    
    /**
     * Dump tabel A
     */
    public function testDropTableA(){
    	$DO = MySql::query("DROP TABLE `test_table_a`");
    	$this->assertEquals(true, $DO->isSuccess());
    }
    /**
     * Dump table b
     */
    public function testDropTableB(){
    	$DO = MySql::query("DROP TABLE `test_table_b`");
    	$this->assertEquals(true, $DO->isSuccess());
    }

}
