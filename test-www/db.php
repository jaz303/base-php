<?php
require '../configure.php';

$db = GDB::instance();

$d = new Date;

// 
// $db->insert('user', array(
//     's:username' => 'jaz303',
//     'i:user_code' => 20,
//     'dt:created_at' => new Date_Time
// ));

$db->update('user', array('i:age' => 15), 'username = "jaz303"');
$db->update('user', array('i:age' => 15), array('s:username' => 'jaz303', 'i:age' => 20));
$db->update('user', array('i:age' => 15), 's:username', 'jaz303');
$db->update('user', array('i:age' => 15), 'username = {s} AND created_at > {d}', array('jaz303', new Date));
?>