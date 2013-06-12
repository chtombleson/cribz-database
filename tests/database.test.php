<?php
require_once(__DIR__ . '/bootstrap.php');

use Cribz\Database;
use Cribz\DatabaseException;

class DatabaseTest extends PHPUnit_Framework_TestCase {
    function testSingleDatabase() {
        $config = array(
            'default' => array(
                'driver' => 'sqlite',
                'database' => __DIR__ . '/test.db',
            ),
        );

        $database = new Database($config);

        $result = $database->executeSql("CREATE TABLE test(id integer PRIMARY KEY, name varchar)");
        $this->assertTrue($result);

        $result = $database->insert('test', array('id' => 1, 'name' => 'jim'));
        $this->assertTrue($result);

        $result = $database->select('test', array('id' => 1));
        $this->assertTrue($result);

        $row = $database->fetch();
        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'jim');

        $result = $database->update('test', array('name' => 'bob'), array('id' => 1));
        $this->assertTrue($result);

        $result = $database->select('test', array('id' => 1));
        $this->assertTrue($result);

        $row = $database->fetch();
        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'bob');

        $result = $database->delete('test', array('id' => 1));
        $this->assertTrue($result);


        unlink(__DIR__ . '/test.db');
    }

    function testMultipleDatabase() {
        $config = array(
            'default' => array(
                'driver' => 'sqlite',
                'database' => __DIR__ . '/test.db',
            ),
            'second' => array(
                'driver' => 'sqlite',
                'database' => __DIR__ . '/second.db',
            ),
        );

        $database = new Database($config);

        $database->setCurrentConnection('default');
        $result = $database->getCurrentConnection();
        $this->assertEquals($result, 'default');

        $result = $database->executeSql("CREATE TABLE test(id integer PRIMARY KEY, name varchar)");
        $this->assertTrue($result);

        $result = $database->insert('test', array('id' => 1, 'name' => 'jim'));
        $this->assertTrue($result);

        $result = $database->select('test', array('id' => 1));
        $this->assertTrue($result);

        $row = $database->fetch();
        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'jim');

        $result = $database->update('test', array('name' => 'bob'), array('id' => 1));
        $this->assertTrue($result);

        $result = $database->select('test', array('id' => 1));
        $this->assertTrue($result);

        $row = $database->fetch();
        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'bob');

        $result = $database->delete('test', array('id' => 1));
        $this->assertTrue($result);

        // Second db test
        $database->setCurrentConnection('second');
        $result = $database->getCurrentConnection();
        $this->assertEquals($result, 'second');

        $result = $database->executeSql("CREATE TABLE test(id integer PRIMARY KEY, name varchar)");
        $this->assertTrue($result);

        $result = $database->insert('test', array('id' => 1, 'name' => 'jim'));
        $this->assertTrue($result);

        $result = $database->select('test', array('id' => 1));
        $this->assertTrue($result);

        $row = $database->fetch();
        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'jim');

        $result = $database->update('test', array('name' => 'bob'), array('id' => 1));
        $this->assertTrue($result);

        $result = $database->select('test', array('id' => 1));
        $this->assertTrue($result);

        $row = $database->fetch();
        $this->assertEquals($row->id, 1);
        $this->assertEquals($row->name, 'bob');

        $result = $database->delete('test', array('id' => 1));
        $this->assertTrue($result);

        unlink(__DIR__ . '/test.db');
        unlink(__DIR__ . '/second.db');
    }
}
?>
