<?php
class GDB_TestModel {
    
    private $attribs;
    
    public function __construct($row = array()) {
        $this->set_attribs($row);
    }
    
    public function set_attribs($a) {
        $this->attribs = $a;
    }
    
    public function get_forename() { return $this->attribs['forename']; }
    public function get_surname() { return $this->attribs['surname']; }
    
}

class GDB_TestModelFactory {
    public static function factory($row) {
        return new GDB_TestModel($row);
    }
}

class GDB_Test extends DB_Test
{
    public function setup() {
        parent::setup();
        $this->data_inserted = false;
    }
    
    public function test_instance_returns_instance_of_gdb() {
        _assert(GDB::instance('default') instanceof GDB);
    }
    
    public function test_instance_with_no_parameters_returns_default_instance() {
        assert_equal(GDB::instance('default'), GDB::instance());
    }
    
    //
    // Query statements
    
    public function test_q_returns_result_object() {
        
        $r = $this->db->q("SELECT * FROM bpt_user");
        _assert($r instanceof GDBResult);
        
    }
    
    //
    // Execute statements
    
    public function test_x_returns_affected_rows() {
        
        $r = $this->db->x("INSERT INTO bpt_user (forename) VALUES ('Jason')");
        assert_equal(1, $r);
        
        $r = $this->db->x("INSERT INTO bpt_user (forename) VALUES ('Jason')");
        assert_equal(1, $r);
        
        $r = $this->db->x("UPDATE bpt_user SET forename = 'Jim'");
        assert_equal(2, $r);
        
        $r = $this->db->x("DELETE FROM bpt_user");
        assert_equal(2, $r);
        
    }
    
    public function test_x_affects_db() {
        $c = mysql_num_rows(mysql_query("SELECT * FROM bpt_user"));
        $r = $this->db->x("INSERT INTO bpt_user (forename) VALUES ('Jason')");
        assert_equal($c + $r, mysql_num_rows(mysql_query("SELECT * FROM bpt_user")));
    }
    
    //
    // Insert ID
    
    public function test_last_insert_id_returns_number() {
        $this->db->x("INSERT INTO bpt_user (forename) VALUES ('Jason')");
        $iid = $this->db->last_insert_id();
        _assert(is_int($iid));
        _assert($iid > 0);
    }
    
    //
    // Data type quoting
    
    public function test_quoting_int() {
        assert_equal('NULL', $this->db->quote_int(null));
        assert_equal(456, $this->db->quote_int(456));
        assert_equal(0, $this->db->quote_int("DROP TABLE users"));
    }
    
    public function test_quoting_float() {
        assert_equal('NULL', $this->db->quote_float(null));
        assert_equal(12.5, $this->db->quote_float(12.5));
        assert_identical('-123.45', $this->db->quote_float('-123.45'));
        assert_equal(0, $this->db->quote_float('DROP TABLE users'));
    }
    
    public function test_quoting_bool() {
        assert_equal('NULL', $this->db->quote_bool(null));
        assert_equal(1, $this->db->quote_bool(true));
        assert_equal(0, $this->db->quote_bool(false));
        assert_equal(1, $this->db->quote_bool("WASSUP"));
    }
    
    public function test_quoting_string() {
        assert_equal('NULL', $this->db->quote_string(null));
        assert_equal("'" . mysql_real_escape_string("anything' OR 'x'='x") . "'", 
                     $this->db->quote_string("anything' OR 'x'='x"));
    }
    
    public function test_quoting_date() {
        assert_equal('NULL', $this->db->quote_date(null));
        assert_equal("'1980-12-12'", $this->db->quote_date(array(1980, 12, 12)));
        assert_equal("'1980-12-12'", $this->db->quote_date(new Date(1980, 12, 12)));
    }
    
    public function test_quoting_datetime() {
        assert_equal('NULL', $this->db->quote_datetime(null));
        assert_equal("'1980-12-12T15:30:00'", $this->db->quote_datetime(array(1980, 12, 12, 15, 30, 0)));
        assert_equal("'1980-12-12T15:30:00'", $this->db->quote_datetime(new Date_Time(1980, 12, 12, 15, 30, 0)));
    }
    
    public function test_quoting_binary() {
        assert_equal('NULL', $this->db->quote_binary(null));
        // ...
    }
    
    //
    // Result type conversion
    
    public function test_type_conversion() {
        
        $this->db->x("
            INSERT INTO bpt_user
                (created_at, born_on, is_active)
            VALUES
                ('2008-10-16T00:31:13', '1980-12-12', 1)
        ");
        
        $r = $this->db->q("SELECT * FROM bpt_user")->stack();
        $u = $r[0];
        
        _assert($u['is_active'] === true);
        
        _assert($u['created_at'] instanceof Date_Time);
        assert_equal(2008, $u['created_at']->year());
        assert_equal(10  , $u['created_at']->month());
        assert_equal(16  , $u['created_at']->day());
        assert_equal(0   , $u['created_at']->hour());
        assert_equal(31  , $u['created_at']->minute());
        assert_equal(13  , $u['created_at']->second());
        
        _assert($u['born_on'] instanceof Date);
        assert_equal(1980, $u['born_on']->year());
        assert_equal(12  , $u['born_on']->month());
        assert_equal(12  , $u['born_on']->day());
        
    }
    
    //
    // Auto-quoting
    
    //
    // Result object behaviour
    
    public function test_accessing_value_by_offset() {
        $r = $this->query_test_data();
        _assert(is_numeric($r->value(0)));
        assert_equal('A', $r->value(1));
        assert_equal('B', $r->value(2));
    }
    
    public function test_accessing_value_by_fieldname() {
        $r = $this->query_test_data();
        _assert(is_numeric($r->value('id')));
        assert_equal('A', $r->value('forename'));
        assert_equal('B', $r->value('surname'));
    }
    
    public function test_stacking_is_reentrant() {
        $r = $this->query_test_data();
        assert_equal($r->stack(), $r->stack());
    }
    
    public function test_page_is_1_and_rpp_is_null_when_not_paginating() {
        $r = $this->query_test_data();
        assert_equal(1, $r->page());
        assert_equal(null, $r->rpp());
    }
    
    public function test_page_and_rpp_are_set_when_paginating() {
        $r = $this->query_test_data()->paginate(2, 2);
        assert_equal(2, $r->page());
        assert_equal(2, $r->rpp());
    }
    
    public function test_page_defaults_to_1_when_paginating() {
        $r = $this->query_test_data()->paginate(2);
        assert_equal(1, $r->page());
        assert_equal(2, $r->rpp());
    }

    public function test_pagination_is_reentrant() {
        
        $r1 = $this->query_test_data()->paginate(3);
        assert_equal($r1->stack(), $r1->stack());

        $r2 = $this->query_test_data()->paginate(3, 2);
        assert_equal($r2->stack(), $r2->stack());
        
    }
    
    public function test_pagination_works() {
        
        $r2 = $this->query_test_data()->key('forename')->paginate(3, 1)->stack();
        assert_equal(array('A', 'C', 'E'), array_keys($r2));
        
        $r3= $this->query_test_data()->key('forename')->paginate(3, 2)->stack();
        assert_equal(array('G'), array_keys($r3));
        
    }
    
    public function test_row_count_returns_total_results_even_when_paginating() {
        
        $r1 = $this->query_test_data();
        assert_equal(4, $r1->row_count());
        
        $r2 = $this->query_test_data()->paginate(2, 1);
        assert_equal(4, $r2->row_count());
        
    }
    
    public function test_page_count_is_1_when_not_paginated_and_data() {
        assert_equal(1, $this->query_test_data()->page_count());
    }
    
    public function test_page_count_is_0_when_not_paginated_and_no_data() {
        assert_equal(0, $this->db->q("SELECT * FROM bpt_user WHERE 1 = 0")->page_count());
    }
    
    public function test_page_count_is_correct_when_paginating() {
        assert_equal(2, $this->query_test_data()->paginate(3)->page_count());
        assert_equal(0, $this->db->q("SELECT * FROM bpt_user WHERE 1 = 0")->paginate(100)->page_count());
    }
    
    public function test_stacking_objects_with_constructor() {
        $res = $this->query_test_data();
        $res->mode('object', 'GDB_TestModel');
        $all = $res->stack();
        _assert($all[0] instanceof GDB_TestModel);
        assert_equal('A', $all[0]->get_forename());
        assert_equal('B', $all[0]->get_surname());
    }
    
    public function test_stacking_objects_with_setter_method() {
        $res = $this->query_test_data();
        $res->mode('object', 'GDB_TestModel', array('method' => 'set_attribs'));
        $all = $res->stack();
        _assert($all[0] instanceof GDB_TestModel);
        assert_equal('A', $all[0]->get_forename());
        assert_equal('B', $all[0]->get_surname());
    }
    
    public function test_stacking_objects_with_static_factory() {
        $res = $this->query_test_data();
        $res->mode('object', 'GDB_TestModelFactory', array('factory' => 'factory'));
        $all = $res->stack();
        _assert($all[0] instanceof GDB_TestModel);
        assert_equal('A', $all[0]->get_forename());
        assert_equal('B', $all[0]->get_surname());
    }
    
    public function test_stacking_objects_by_value() {
        $res = $this->query_test_data();
        $res->mode('value', 'forename');
        $all = $res->stack();
        assert_equal(array('A', 'C', 'E', 'G'), $all);
    }
    
    public function test_keying_objects() {
        $res = $this->query_test_data();
        $res->key('forename');
        $all = $res->stack();
        foreach (array(array('A', 'B'), array('C', 'D'), array('E', 'F'), array('G', 'H')) as $item) {
            _assert(isset($all[$item[0]]));
            assert_equal($item[0], $all[$item[0]]['forename']);
            assert_equal($item[1], $all[$item[0]]['surname']);
        }
    }
    
    // TODO: test classname from database column
    
    public function test_countable() {
        assert_equal(4, count($this->query_test_data()));
    }
    
    //
    // Transaction workflow
    
    public function test_begin_commit() {
        $this->db->begin();
        $this->db->commit();
    }
    
    public function test_beginning_more_than_one_transaction_throws() {
        $this->db->begin();
        try {
            $this->db->begin();
            fail();
        } catch (GDB_Exception $e) {
            pass();
            $this->db->rollback();
        }
    }
    
    //
    // helpers
    
    private function query_test_data() {
        
        if (!$this->data_inserted) {
            
            $data = array(
                "('A', 'B')",
                "('C', 'D')",
                "('E', 'F')",
                "('G', 'H')"
            );

            foreach ($data as $qs) {
                $this->db->x("INSERT INTO bpt_user (forename, surname) VALUES $qs");
            }
            
            $this->data_inserted = true;
            
        }
        
        return $this->db->q("SELECT id, forename, surname FROM bpt_user ORDER BY forename ASC");
        
    }
    
}
?>