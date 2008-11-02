<?php
class MBT_Model extends Model_Base
{
    
}

class Model_BaseTest extends Test_Unit
{
    public function setup() {
        $this->model = new MBT_Model;
    }
    
    public function test_new_model_instance_is_new_and_unsaved() {
        $model = new MBT_Model;
        _assert($model->is_new());
        _assert(!$model->is_saved());
    }
    
    public function test_new_model_instance_has_null_id() {
        $model = new MBT_Model;
        assert_null($model->get_id());
    }
}
?>