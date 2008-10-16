<?php
require 'configure.php';

if (!mysql_connect($_GDB['default']['host'],
                   $_GDB['default']['username'],
                   $_GDB['default']['password'])) die("Couldn't connect to MySQL\n");

if (!mysql_select_db($_GDB['default']['database'])) die("Couldn't select DB\n");

mysql_query("DROP TABLE IF exists bpt_user");
mysql_query("
    CREATE TABLE bpt_user (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `forename` VARCHAR( 255 ) NULL ,
        `surname` VARCHAR( 255 ) NULL ,
        `is_active` TINYINT( 1 ) NULL ,
        `created_at` DATETIME NULL ,
        `updated_at` DATETIME NULL ,
        `born_on` DATE NULL ,
        `post_count` INT NULL ,
        `rating` FLOAT NULL ,
        `bio` TEXT NULL
    ) ENGINE = INNODB
");

class DB_Test extends Test_Unit
{
    public function setup() {
        $this->db = GDB::instance();
        try {
            $this->db->rollback();
        } catch (GDB_Exception $e) {}
    }
    
    public function teardown() {
        mysql_query("DELETE FROM bpt_user");
    }
}

set_time_limit(0);

$tests = new Test_Suite('BasePHP Unit Tests');
$tests->require_all('test');
$tests->auto_fill();

chdir('tmp');

$tests->run(new Test_ConsoleReporter);
?>
