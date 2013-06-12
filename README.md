#Cribz\Database
PDO database class

This library can be used to interact with multiple database. Supported databases are SQLite, MySQL and PostgreSQL.

##Examples
For an example see the database.test.php in the tests directory.

##Connecting to a database
    <?php
    require_once(__DIR__ . '/vendor/autoload.php');
    use Cribz\Database;

    $config = array(
        // The default connection is required
        'default' => array(
            'driver'    => 'sqlite|mysql|pgsql', // PDO Database driver
            'host'      => 'database host',      // Only needed for MySQL & PostgreSQL
            'database'  => 'database name',      // Database name for MySQL & PostgreSQL, path to database file for SQLite
            'username'  => 'username',           // Only needed for MySQL & PostgreSQL
            'password'  => 'password',           // Only needed for MySQL & PostgreSQL
            'port'      => 3306,                 // Optional only needed if not using the standard port for MySQL & PostgreSQL
        ),
    );

    // This will connect to the database straight away
    $database = new Database($config);

    // This will not connect to the database straight away
    $database = new Database($config, false);

    // Then to connect to the database
    $database->connect();
    ?>

##Connecting to multiple databases
    <?php
    require_once(__DIR__ . '/vendor/autoload.php');
    use Cribz\Database;

    $config = array(
        // The default connection is required
        'default' => array(
            'driver'    => 'sqlite|mysql|pgsql', // PDO Database driver
            'host'      => 'database host',      // Only needed for MySQL & PostgreSQL
            'database'  => 'database name',      // Database name for MySQL & PostgreSQL, path to database file for SQLite
            'username'  => 'username',           // Only needed for MySQL & PostgreSQL
            'password'  => 'password',           // Only needed for MySQL & PostgreSQL
            'port'      => 3306,                 // Optional only needed if not using the standard port for MySQL & PostgreSQL
        ),
        'second_db' => array(
            'driver'    => 'sqlite|mysql|pgsql', // PDO Database driver
            'host'      => 'database host',      // Only needed for MySQL & PostgreSQL
            'database'  => 'database name',      // Database name for MySQL & PostgreSQL, path to database file for SQLite
            'username'  => 'username',           // Only needed for MySQL & PostgreSQL
            'password'  => 'password',           // Only needed for MySQL & PostgreSQL
            'port'      => 3306,                 // Optional only needed if not using the standard port for MySQL & PostgreSQL
        ),
    );

    // This will connect to the databases straight away
    $database = new Database($config);

    // This will not connect to the databases straight away
    $database = new Database($config, false);

    // Then to connect to the database default
    $database->connect('default');
    $database->connect('second_db');
    ?>

##License
See LICENSE
