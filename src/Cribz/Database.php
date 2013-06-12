<?php
/*
*The MIT License (MIT)
*
* Copyright (c) 2013 Christopher Tombleson <chris@cribznetwork.com>
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
*/
/**
* Cribz Database
*
* @package Cribz
* @subpackage Database
* @copyright Christopher Tombleson <chris@cribznetwork.com> 2013
*/
namespace Cribz;

/**
* Database class
* Database
*
* @author Christopher Tombleson <chris@cribznetwork.com>
*/
class Database {
    /**
    * Pool
    * Holds all the database connections
    *
    * @access protected
    * @var array
    */
    protected $pool = array();

    /**
    * Current Connection
    * Holds the current connection name
    *
    * @access private
    * @var string
    */
    private $currentConnection = null;

    /**
    * Constructor
    * Create a new instance of Cribz\Database
    *
    * Example connection array:
    *   $coninfo = array(
    *       'default' => array(
    *           'driver'    => 'mysql',
    *           'host'      => 'localhost',
    *           'database'  => 'test_db',
    *           'username'  => 'user',
    *           'password'  => 'pass',
    *       ),
    *       'postgres' => array(
    *           'driver'    => 'pgsql',
    *           'host'      => 'localhost',
    *           'database'  => 'test_db',
    *           'username'  => 'user',
    *           'password'  => 'pass',
    *       ),
    *       'sqlite' => array(
    *           'driver'    => 'sqlite',
    *           'database'  => 'test_db.db',
    *       ),
    *   );
    *
    * @access public
    * @param array  $coninfo    Array of database connection details
    * @param bool   $connect    Connect to all databases (Default is true)
    * @throws DatabaseException
    */
    public function __construct($coninfo, $connect = true) {
        if (!is_array($coninfo)) {
            throw new DatabaseException("The constructor takes an array as a parameter");
        }

        if (!isset($coninfo['default'])) {
            throw new DatabaseException("You must define default database connection details");
        }

        foreach ($coninfo as $pool => $info) {
            if (!isset($info['driver'])) {
                throw new DatabaseException("You must define a driver (sqlite, mysql, pgsql)");
            }

            if (strtolower($info['driver']) == 'mysql' || strtolower($info['driver']) == 'pgsql') {
                if (!isset($info['host'])) {
                    throw new DatabaseException("You must define a host");
                }

                if (!isset($info['database'])) {
                    throw new DatabaseException("You must define a databse");
                }

                if (!isset($info['username'])) {
                    throw new DatabaseException("You must define a username");
                }

                if (!isset($info['password'])) {
                    throw new DatabaseException("You must define a password");
                }

                $this->pool[$pool] = array(
                        'details' => $info,
                        'dsn' => $this->buildDsn($info),
                );

            } else if (strtolower($info['driver']) == 'sqlite') {
                if (!isset($info['database'])) {
                    throw new DatabaseException("You must define a database");
                }

                $this->pool[$pool] = array(
                    'details' => $info,
                    'dsn' => $this->buildDsn($info),
                );
            } else {
                throw new DatabaseException("Database driver " . $info['driver'] . " is not supported");
            }
        }

        if ($connect) {
            try {
                foreach ($this->pool as $name => $pool) {
                    if (strtolower($pool['details']['driver']) == 'mysql' || strtolower($pool['details']['driver']) == 'pgsql') {
                        if (isset($pool['details']['options'])) {
                            $this->pool[$name]['pdo'] = new \PDO($pool['dsn'], $pool['details']['username'], $pool['details']['password'], $pool['details']['options']);
                        } else {
                            $this->pool[$name]['pdo'] = new \PDO($pool['dsn'], $pool['details']['username'], $pool['details']['password']);
                        }
                    } else {
                        $this->pool[$name]['pdo'] = new \PDO($pool['dsn']);
                    }

                    $this->pool[$name]['connected'] = true;
                }
            } catch (\PDOException $e) {
                throw new DatabaseException($e->getMessage());
            }
        }
    }

    /**
    * Connect
    * Connect to database
    *
    * @acces public
    * @param string $conn   Database connection to connect to (Default is default)
    * @throws DatabaseException
    * @return bool true on success
    */
    public function connect($conn = 'default') {
        if (!in_array($conn, array_keys($this->pool))) {
            throw new DatabaseException("Pool: " . $conn . ", connection does not exist");
        }

        if ($this->isConnected($conn)) {
            return true;
        }

        try {
            $info = $this->pool[$conn];

            switch (strtolower($info['details']['driver'])) {
                case 'mysql' || 'pgsql':
                    if (isset($info['details']['options'])) {
                        $this->pool[$conn]['pdo'] = new \PDO($info['dsn'], $info['details']['username'], $info['details']['password'], $info['details']['options']);
                    } else {
                        $this->pool[$conn]['pdo'] = new \PDO($info['dsn'], $info['details']['username'], $info['details']['password']);
                    }
                    break;

                case 'sqlite':
                    $this->pool[$conn]['pdo'] = new \PDO($info['dsn']);
                    break;
            }

            $this->pool[$conn]['connected'] = true;
            return true;
        } catch (\PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
    * Is Connected
    * Check if pool/connection is connected to the database
    *
    * @access public
    * @param string $conn   Database connection to check (Default is default)
    * @return bool true if connected, otherwise false
    */
    public function isConnected($conn = 'default') {
        if (!isset($this->pool[$conn]['connected'])) {
            return false;
        }

        return $this->pool[$conn]['connected'];
    }

    /**
    * Execute SQL
    * Execute a sql query
    *
    * @access public
    * @param string $sql        SQL Query to execute
    * @param array  $params     Array of placeholder values
    * @param string $conn       Database connection to use
    * @throws DatabaseException
    * @return bool true on success
    */
    public function executeSql($sql, $params = array(), $conn = null) {
        if (is_null($conn)) {
            $conn = $this->getCurrentConnection();
        }

        $this->pool[$conn]['pdo_statement'] = $this->pool[$conn]['pdo']->prepare($sql);
        $result = $this->pool[$conn]['pdo_statement']->execute($params);

        if (!$result) {
            $errorInfo = $this->pool[$conn]['pdo_statement']->errorInfo();
            throw new DatabaseException("Unable to execute SQL: " . $errorInfo[2]);
        }

        return true;
    }

    /**
    * Statement Error
    * Get statement error info
    *
    * @access public
    * @param string $conn   Database connection to check for errors (Default is default)
    * @return array with error info
    */
    public function statementError($conn = null) {
        if (is_null($conn)) {
            $conn = $this->getCurrentConnection();
        }

        if (isset($this->pool[$conn]['pdo_statement'])) {
            $errorInfo = $this->pool[$conn]['pdo_statement']->errorInfo();
            return array(
                'sqlstate_code' => $errorInfo[0],
                'driver_code' => $errorInfo[1],
                'driver_message' => $errorInfo[2],
            );
        }
    }

    /**
    * Database Error
    * Get database error info
    *
    * @access public
    * @param string $conn   Database connection to check for errors (Default is default)
    * @return array with error info
    */
    public function databaseError($conn = null) {
        if (is_null($conn)) {
            $conn = $this->getCurrentConnection();
        }

        if ($this->isConnected($conn)) {
            $errorInfo = $this->pool[$conn]['pdo']->errorInfo();
            return array(
                'sqlstate_code' => $errorInfo[0],
                'driver_code' => $errorInfo[1],
                'driver_message' => $errorInfo[2],
            );
        }
    }

    /**
    * Set Current Connection
    * Set the database connection to use
    *
    * @access public
    * @param string $conn   Database connection to use
    * @throws DatabaseException
    */
    public function setCurrentConnection($conn) {
        if (!in_array($conn, array_keys($this->pool))) {
            throw new DatabaseException("Pool: " . $conn . ", connection does not exist");
        }

        $this->currentConnection = $conn;
    }

    /**
    * Get Current Connection
    * Get the name of the current database connection that is being used
    *
    * @access public
    * @return string current connection name
    */
    public function getCurrentConnection() {
        if (empty($this->currentConnection)) {
            return 'default';
        }

        return $this->currentConnection;
    }

    /**
    * Begin Transaction
    * Start a database transaction
    *
    * @access public
    * @param string $conn   Database connection to use (Default is default)
    * @throws DatabaseException
    * @return bool true on success
    */
    public function beginTransaction($conn = null) {
        if (is_null($conn)) {
            $conn = $this->getCurrentConnection();
        }

        $result = $this->pool[$conn]['pdo']->beginTransaction();

        if (!$result) {
            throw new DatabaseException("Unable to begin transaction");
        }

        $this->pool[$conn]['transaction'] = true;
        return true;
    }

    /**
    * Commit
    * Commit a transaction
    *
    * @access public
    * @param string $conn   Database connection to use (Default is default)
    * @throws DatabaseException
    * @return bool true on succes
    */
    public function commit($conn = null) {
        if (is_null($conn)) {
            $conn = $this->getCurrentConnection();
        }

        $result = $this->pool[$conn]['pdo']->commit();

        if (!$result) {
            throw new DatabaseException("Unable to commit transaction");
        }

        $this->pool[$conn]['transaction'] = false;
        return true;
    }

    /**
    * Rollback
    * Rollback a transaction
    *
    * @access public
    * @param string $conn   Database connection to use (Default is default)
    * @throws DatabaseException
    * @return bool true on success
    */
    public function rollback($conn = null) {
        if (is_null($conn)) {
            $conn = $this->getCurrentConnection();
        }

        $result = $this->pool[$conn]['pdo']->rollback();

        if (!$result) {
            throw new DatabaseException("Unable to rollback transaction");
        }

        $this->pool[$conn]['transaction'] = false;
        return true;
    }

    /**
    * In Transaction
    * Check if connection is in a transaction
    *
    * @access public
    * @param string $conn   Database connectio to check
    * @return bool true if in transaction, otherwise false
    */
    public function inTransaction($conn = null) {
        if (is_null($conn)) {
            $conn = $this->getCurrentConnection();
        }

        if (!isset($this->pool[$conn]['transaction'])) {
            return false;
        }

        return $this->pool[$conn]['transaction'];
    }

    /**
    * Select
    * Run a select query on the database
    *
    * Example select query:
    *   select('users', array('name' => 'bob'), array('email' => 'bob@example.com'), array('id' => 'desc'));
    *
    * Generated SQL: SELECT * FROM users WHERE name=? AND email LIKE %?% ORDER BY id DESC
    * Executed SQL: SELECT * FROM users WHERE name='bob' AND email LIKE '%bob@example.com%' ORDER BY id DESC
    *
    * @access public
    * @param string $table  Name of table to query
    * @param array  $where  Field => value array to be used as where statements
    * @param array  $like   Field => value array to be used as like statements
    * @param array  $order  Field => order array to be used as order by statement
    * @param int    $limit  Limit number of records
    * @param int    $offset Offset the reccords
    * @param string $conn   Database connection to use
    * @throws DatabaseException
    * @return bool true on success, false otherwise
    */
    public function select($table, $where = array(), $like = array(), $order = array(), $limit = null, $offset = null, $conn = null) {
        if (is_null($conn)) {
            $conn = $this->getCurrentConnection();
        }

        $sql = "SELECT * FROM " . $table;
        $params = array();

        if (!empty($where)) {
            $sql .= " WHERE ";

            foreach ($where as $field => $value) {
                if (is_array($value)) {
                    $sql .= $field . " IN (";

                    foreach ($value as $val) {
                        $sql .= "?,";
                        $params[] = $val;
                    }

                    $sql .= rtrim($sql, ",") . ") AND ";
                } else {
                    $sql .= $field . "=? AND ";
                    $params[] = $value;
                }
            }

            $sql = rtrim($sql, " AND ");
        }

        if (!empty($like)) {
            $sql .= (!empty($where)) ? " AND " : "";

            foreach ($like as $field => $value) {
                $sql .= $field . " LIKE %?% AND ";
                $params[] = $value;
            }

            $sql = rtrim($sql, " AND ");
        }

        if (!empty($order)) {
            $sql .= " ORDER BY ";

            foreach ($order as $field => $value) {
                $sql .= $field . " " . strtoupper($value) . ", ";
            }

            $sql = rtrim($sql, ", ");
        }

        if (!is_null($limit)) {
            $sql .= " LIMIT " . (int) $limit;
        }

        if (!is_null($offset)) {
            $sql .= " OFFSET " . (int) $offset;
        }

        return $this->executeSql($sql, $params, $conn);
    }

    /**
    * Insert
    * Insert a record into the database
    *
    * Example insert query:
    *   insert('users', array('name' => 'bob', 'email' => 'bob@example.com'));
    *
    * Generated SQL: INSERT INTO users (name,email) VALUES (?,?)
    * Executed SQL: INSERT INTO users (name,email) VALUES ('bob', 'bob@example.com')
    *
    * @access public
    * @param string $table      Name of table to insert into
    * @param array  $record     Field => value array for data to be inserted
    * @param string $conn       Database connection to use
    * @throws DatabaseException
    * @return bool true on success, false otherwise
    */
    function insert($table, $record, $conn = null) {
        if (is_null($conn)) {
            $conn = $this->getCurrentConnection();
        }

        $sql = "INSERT INTO " . $table . "(";
        $params = array();

        foreach (array_keys($record) as $field) {
            $sql .= $field . ", ";
        }

        $sql = rtrim($sql, ", ");

        $sql .= ") VALUES (";

        foreach (array_values($record) as $value) {
            $sql .= "?, ";
            $params[] = $value;
        }

        $sql = rtrim($sql, ", ") . ")";
        return $this->executeSql($sql, $params, $conn);
    }

    /**
    * Update
    * Update a record into the database
    *
    * Example update query:
    *   update('users', array('name' => 'bob', 'email' => 'bob1@example.com'), array('id' => 1));
    *
    * Generated SQL: UPDATE users SET name=?, email=? WHERE id=?
    * Executed SQL: UPDATE users SET name='bob', email='bob1@example.com' WHERE id=1
    *
    * @access public
    * @param string $table      Name of table to update
    * @param array  $record     Field => value array for data to be inserted
    * @param array  $where      Field => value array to be used as where statements
    * @param string $conn       Database connection to use
    * @throws DatabaseException
    * @return bool true on success, false otherwise
    */
    function update($table, $record, $where, $conn = null) {
        if (is_null($conn)) {
            $conn = $this->getCurrentConnection();
        }

        $sql = "UPDATE " . $table . " SET ";
        $params = array();

        foreach ($record as $field => $value) {
            $sql .= $field . "=?, ";
            $params[] = $value;
        }

        $sql = rtrim($sql, ", ");

        $sql .= " WHERE ";

        foreach ($where as $field => $value) {
            if (is_array($value)) {
                $sql .= $field . " IN (";

                foreach ($value as $val) {
                    $sql .= "?,";
                    $params[] = $val;
                }

                $sql .= rtrim($sql, ",") . ") AND ";
            } else {
                $sql .= $field . "=? AND ";
                $params[] = $value;
            }
        }

        $sql = rtrim($sql, " AND ");
        return $this->executeSql($sql, $params, $conn);
    }

    /**
    * Delete
    * Delete a record from the database
    *
    * Example delete query:
    *   delete('users', array('id' => 1));
    *
    * Generated SQL: DELETE FROM users WHERE id=?
    * Executed SQL: DELETE FROM users WHERE id=1
    *
    * @access public
    * @param string $table  Name of table to delete from
    * @param array  $where  Field => value array to be used as where statements
    * @throws DatabaseException
    * @return bool true on success, false otherwise
    */
    function delete($table, $where, $conn = null) {
        if (is_null($conn)) {
            $conn = $this->getCurrentConnection();
        }

        $sql = "DELETE FROM " . $table . " WHERE ";
        $params = array();

        foreach ($where as $field => $value) {
            if (is_array($value)) {
                $sql .= $field . " IN (";

                foreach ($value as $val) {
                    $sql .= "?,";
                    $params[] = $val;
                }

                $sql .= rtrim($sql, ",") . ") AND ";
            } else {
                $sql .= $field . "=? AND ";
                $params[] = $value;
            }
        }

        $sql = rtrim($sql, " AND ");
        return $this->executeSql($sql, $params, $conn);
    }

    /**
    * Fetch
    * Fetch a row from the resultset
    *
    * @access public
    * @param int    $fetch      PDO Fetch style
    * @param string $conn       Database connection to use
    * @returns mixed row from resultset
    */
    function fetch($fetch=\PDO::FETCH_OBJ, $conn = null) {
        if (is_null($conn)) {
            $conn = $this->getCurrentConnection();
        }

        return $this->pool[$conn]['pdo_statement']->fetch($fetch);
    }

    /**
    * FetchAll
    * Fetch all rows from the resultset
    *
    * @access public
    * @param int    $fetch      PDO Fetch style. (optional, default is PDO::FETCH_OBJ)
    * @param string $conn       Database connection to use
    * @returns array rows from resultset
    */
    function fetchAll($fetch=\PDO::FETCH_OBJ, $conn = null) {
        if (is_null($conn)) {
            $conn = $this->getCurrentConnection();
        }

        return $this->pool[$conn]['pdo_statement']->fetchAll($fetch);
    }

    /**
    * Row Count
    * Gets number of rows selected or row affected
    *
    * @access public
    * @param string $conn   Database connection to use
    * @return int number of rows selected or row affected
    */
    function rowCount($conn = null) {
        if (is_null($conn)) {
            $conn = $this->getCurrentConnection();
        }

        return $this->pool[$conn]['pdo_statement']->rowCount();
    }

    /**
    * Build Dsn
    * Build a PDO DSN string
    *
    * @access private
    * @param array $coninfo     Array with database connection details
    * @return string dsn string
    */
    private function buildDsn($coninfo) {
        switch (strtolower($coninfo['driver'])) {
            case 'sqlite':
                $dsn = 'sqlite:' . $coninfo['database'];
                break;

            case 'mysql':
                $dsn  = 'mysql:host=' . $coninfo['host'] . ';dbname=' . $coninfo['database'];
                $dsn .= empty($coninfo['port']) ? ';port=3306' : ';port=' . $coninfo['port'];
                break;

            case 'pgsql':
                $dsn  = 'pgsql:host=' . $coninfo['host'] . ';dbname=' . $coninfo['database'];
                $dsn .= empty($coninfo['port']) ? ';port=5432' : ';port=' . $coninfo['port'];
                break;
        }

        return $dsn;
    }
}
?>
