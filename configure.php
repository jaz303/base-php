<?php
// Demonstration configuration file for BasePHP
// This is all there is to it.

// You need a database connection to run the tests...
// If you're using MySQL, the default 'test' database should suffice, so just
// fill in your username/password.
$_GDB['default'] = array(
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'username'  => 'root',
    'password'  => '',
    'database'  => 'test'
);

$root = dirname(__FILE__);
ini_set("include_path", ".:{$root}/inc");

function base_php_autoloader($class) {
    
    // START-MAP
    static $map = array (
      'IllegalArgumentException' => 'base.php',
      'IllegalStateException' => 'base.php',
      'UnsupportedOperationException' => 'base.php',
      'IOException' => 'base.php',
      'NotFoundException' => 'base.php',
      'SecurityException' => 'base.php',
      'SyntaxException' => 'base.php',
      'Base' => 'base.php',
      'Callback' => 'base.php',
      'FunctionCallback' => 'base.php',
      'InstanceCallback' => 'base.php',
      'StaticCallback' => 'base.php',
      'Inflector' => 'base.php',
      'Date' => 'date.php',
      'Date_Time' => 'date.php',
      'Errors' => 'errors.php',
      'File' => 'file.php',
      'UploadedFile' => 'file.php',
      'UploadedFileError' => 'file.php',
      'MIME' => 'mime.php',
      'MoneyConversionException' => 'money.php',
      'Money' => 'money.php',
      'MoneyBank' => 'money.php',
      'ISO_Country' => 'iso/country.php',
      'ISO_Language' => 'iso/language.php',
      'GDBException' => 'gdb/gdb.php',
      'GDBQueryException' => 'gdb/gdb.php',
      'GDBIntegrityConstraintViolation' => 'gdb/gdb.php',
      'GDBForeignKeyViolation' => 'gdb/gdb.php',
      'GDBUniqueViolation' => 'gdb/gdb.php',
      'GDBCheckViolation' => 'gdb/gdb.php',
      'GDB' => 'gdb/gdb.php',
      'GDBMySQL' => 'gdb/gdb.php',
      'GDBResult' => 'gdb/gdb.php',
      'GDBResultMySQL' => 'gdb/gdb.php',
      'gdb\Migration' => 'gdb/migration.php',
      'gdb\SchemaBuilder' => 'gdb/schema_builder.php',
      'gdb\TableDefinition' => 'gdb/table_definition.php',
    );
    // END-MAP
    
    if (isset($map[$class])) require $map[$class];

}

spl_autoload_register('base_php_autoloader');
?>