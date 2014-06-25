<?php
return array(
    // default mandatory
    'default' => array(
        'host' => 'localhost',
        'dbname' => 'kdbtest',
        'user' => 'kdbtest',
        'password' => 'kdbtest',
        'prefix' => '',
        'pdoattributes' => array(
            array(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC),
            array(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION),
            array(\PDO::ATTR_EMULATE_PREPARES, false)
        )
    ),
    'db1' => array(
       'host' => 'localhost',
        'dbname' => 'otherdatabase',
        'user' => 'root',
        'password' => 'superpass',
        'prefix' => 'test',
        'pdoattributes' => array(
            array(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC),
            array(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION),
            array(\PDO::ATTR_EMULATE_PREPARES, false)
        )

    )
);
