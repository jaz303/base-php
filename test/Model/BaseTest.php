<?php
class MBT_Model extends Model_Base {
    public static function table_name() {
        return 'my_table';
    }
}

class MBT_Model_Child_1 extends MBT_Model {}

class MBT_Model_Child_2 extends MBT_Model {
    public static function table_name() {
        return 'my_table_2';
    }
}

class Model_BaseTest extends Test_Unit
{
    public function setup() {
        $this->model = new MBT_Model;
    }
    
    public function test_new_model_instance_is_new_and_unsaved() {
        $model = new MBT_Model_Parent;
        _assert($model->is_new());
        _assert(!$model->is_saved());
    }
    
    public function test_new_model_instance_has_null_id() {
        $model = new MBT_Model_Parent;
        assert_null($model->get_id());
    }
    
    public function test_table_name_is_reported_correctly() {
        
        $model = new MBT_Model;
        assert_equal('my_table', MBT_Model::table_name());
        assert_equal('my_table', $model->get_table_name());
        
        $model = new MBT_Model_Child_1;
        assert_equal('my_table', MBT_Model_Child_1::table_name());
        assert_equal('my_table', $model->get_table_name());
        
        $model = new MBT_Model_Child_2;
        assert_equal('my_table_2', MBT_Model_Child_2::table_name());
        assert_equal('my_table_2', $model->get_table_name());
        
    }
    
}
?>