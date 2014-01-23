<?php

/**
 * @package    ##__projectname 
 * @author     kristian@katropine.com
 * @copyright  Katropine (c) 2013, katropine.com
 * @since      Jan 23, 2014
 */
class MySqlTest extends PHPUnit_Framework_TestCase{

    public function testInsert(){
        // Arrange
        $DBO = \Katropine\KMapper\MySql::query("CREATE TABEL `kmapper_test` (`id` int(11) NOT NULL AUTO_INCREMENT, name varchar(150) CHARACTER SET utf8 DEFAULT NULL,PRIMARY KEY (`ID`)) ENGINE=InnoDB  DEFAULT CHARSET=latin1");

        // Act
        $b = $a->negate();

        // Assert
        $this->assertEquals(-1, $b->getAmount());
    } 

}
