<?php
require 'configure.php';

$db = GDB::instance();

$builder = $db->new_schema_builder();

try {
    $builder->drop_table('user');
} catch (\Exception $e) {}

var_dump($builder->table_exists('user'));

$def = new gdb\TableDefinition('user', array('no_id' => true, 'mysql.engine' => 'InnoDB'));

$def->string('username', array('limit' => 50, 'null' => false));
$def->integer('user_code', array('null' => false, 'mysql.size' => 'big'));
$def->datetime('created_at', array('null' => false));
$def->datetime('updated_at', array('null' => true));
$def->set_primary_key('username', 'user_code');

echo $builder->sql_for_table($def);

$builder->create_table($def);

$builder->remove_column('user', 'updated_at');
$builder->add_column('user', 'age', 'integer', array('null' => true, 'default' => 20));


$builder->add_index('user', array('age', 'created_at'), array('unique' => true));
$builder->remove_index('user', 'age_created_at_index');

var_dump($builder->table_exists('user'));
?>