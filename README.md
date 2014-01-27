KMapper MySql Database Layer
================================

KMapper library is a PHP toolkit for accessing and manipulate MySql database. It provides a query builder class called TableMapper. KMapper is a PDO wrapper library.
Data is returned as associative array. 


Usage Instructions
================================
First create a kmapper.php file in your /app/config or /application/config or /config directory with content.
* For non MVC framework use define kmapper.php config path with define('KMAPPER_CONFIG_LOCATION', '/my/cistom/path')

```php
return array(
    // default mandatory
    'default' => array(
        'host' => 'localhost',
        'dbname' => 'kdbtest',
        'user' => 'root',
        'password' => 'superpass',
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
``` 

Execute first query:
--------------------

```php
$DataObject = \KMapper\MySql::query("SELECT * FROM t1");
$DataObject = \KMapper\MySql::execute(
    "SELECT * FROM `#__t1` WHERE id = ? AND age = ?", 
    array(
        array(12,\PDO::PARAM_INT), 
        array(25, \PDO::PARAM_INT)
    )
);
$DataObject = \KMapper\MySql::execute(
    "SELECT * FROM `#__t1` WHERE id = :id AND age = :age", 
    array(
        array(':id' => 12,\PDO::PARAM_INT), 
        array(':age' => 25, \PDO::PARAM_INT)
    )
);
```

Multiple database connections
-----------------------------

```php
$options['connection'] = new  KMapper\MySqlConnect('db1');

$DataObject = \KMapper\MySql::query("SELECT * FROM #__t1", $options);
$DataObject = \KMapper\MySql::execute("SELECT * FROM #__t1 WHERE id = ?", array(12), $options);
```

Query Builder
-------------
```php

$UserMP = new \KMapper\TabelMapper("#__user", 'usr');

$UsersDataObject = $UserMP->setSelect(array("usr.first_name", "usr.last_name", "addr.zip"));
                    // table1, joinField1, onTable2, onField2, table1Alias
                    ->setInnerJoin("#__address", "id_user", "usr", "id", "addr");
                    ->setWhere("usr.status != ? AND usr.smart = ? AND (addr.zip = ? OR addr.zip = ?)", array('banned', false, '23000', '21000'));
                    ->setOrderBy("usr.name ASC")
                    ->fetchAll();

var_dump($UsersDataObject->toArray());
var_dump($UsersDataObject->toJson());
```

Insert & Update
--------------

```php
$UserMP = new \KMapper\TabelMapper("#__user");

$data = array(
    'first_name' => "Fu",
    'last_name'  => "Bar" 
);
// INSERT, no id provided
$UserMP->save($data);


$data = array(
    'id' => 22,
    'first_name' => "Fu",
    'last_name'  => "Bar" 
);
// UPDATE where id = 22
$UserMP->save($data);
```
In case "id" is not the primary key name, key has to be defined:
```php
$UserMP = new \KMapper\TabelMapper("#__user");
$UserMP->setPrimaryKeyName('mu_unstandard_id')->save($data);
```

Mutiple UPDATE & INSERT
---------------------
```php
$UserMP = new \KMapper\TabelMapper("#__user");

$data = array
    array('first_name' => 'Kriss', 'last_name' => 'Kristiansen'),
    array('first_name' => 'Johnny', 'last_name' => 'Johnosn')

);

$UserMP->batchSave($data);
```

Transactions
------------
```php
try{
    \KMapper\MySql::transactionBegin());

    $last = \KMapper\MySql::query($sqlTask)->getLastID();

    if(!$last){
        throw new \Exception("Could not insert");
    }

    if(!\KMapper\MySql::query($sqlHierarchy)->isSuccess()){
        throw new \Exception("Query error");
    }

    \KMapper\MySql::transactionCommit());
    
}  catch (PDOException $E){
    \KMapper\MySql::transactionRollback();
}  catch (Exception $E){
    \KMapper\MySql::transactionRollback();
}
```

Include with Composer
---------------------

```
"repositories": [
    {
        "type": "package",
        "package": {
            "name": "katropine/kmapper",
            "version": "master",
            "source": {
                "url": "https://github.com/katropine/kmapper.git",
                "type": "git",
                "reference": "master"
            },
            "autoload": {
                "classmap": ["/"]
            }
        }
    }
],
"require": {
    "katropine/kmapper" : "dev-master"
}
```
