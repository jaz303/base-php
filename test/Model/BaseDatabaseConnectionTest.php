<?php
/*
 * These tests are really just propping up my non-understanding of how this 
 * new-fangled static:: stuff works... the docs aren't exactly clear.
 */

$GLOBALS['_GDB']['mbdct_test'] = $GLOBALS['_GDB']['default'];

class MBDCT_Model_Parent extends Model_Base
{
    public function get_connection() {
        return $this->db;
    }
    
    public function get_connection_2() {
        return $this->db;
    }
}

class MBDCT_Model_Child extends MBDCT_Model_Parent
{
    public static function db() {
        return GDB::instance('mbdct_test');
    }
    
    public function get_conection_2() {
        return parent::get_connection_2();
    }
}

class Model_BaseDatabaseConnectionTest extends Test_Unit
{
    public function test_sanity() {
        
        assert_not_equal(GDB::instance(), GDB::instance('mbdct_test'));
        
    }
    
    public function test_default_db_returns_default_gdb_instance() {
        
        $model = new MBDCT_Model_Parent;
        
        assert_equal(GDB::instance(), MBDCT_Model_Parent::db());
        assert_equal(GDB::instance(), $model->db);
        
    }
    
    public function test_child_db_returns_alternate_gdb_instance() {
        
        $model = new MBDCT_Model_Child;
        
        assert_equal(GDB::instance('mbdct_test'), MBDCT_Model_Child::db());
        assert_equal(GDB::instance('mbdct_test'), $model->db);
        
    }
    
    public function test_get_connection_works_correctly_with_inheritance() {
        
        $model = new MBDCT_Model_Child;
        
        assert_equal(GDB::instance('mbdct_test'), $model->get_connection());
        assert_equal(GDB::instance('mbdct_test'), $model->get_connection_2());
        
    }
}
?>