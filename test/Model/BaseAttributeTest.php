<?php
class MBT_Model_Parent extends Model_Base
{
    public static function defaults() {
        return array(
            'forename'      => 'Jason',
            'surname'       => 'Frame',
            'born_on'       => new Date(1980, 12, 12),
            'authorised'    => false
        );
    }
    
    public static function attributes() {
        return array(
            'forename'      => 'string',
            'surname'       => 'string',
            'born_on'       => 'date',
            'authorised'    => 'bool'
        );
    }
}

class MBT_Model_Child extends MBT_Model_Parent
{
    public static function defaults() {
        return array_merge(parent::defaults(), array(
            'position'      => 'Captain',
            'authorised'    => true
        ));
    }
    
    public static function attributes() {
        return array_merge(parent::attributes(), array(
            'position'      => 'string'
        ));
    }
    
    private $non_attribute;
    
    public function get_non_attribute() {
        return $this->non_attribute;
    }
    
    public function set_non_attribute($v) {
        $this->non_attribute = strtoupper($v);
    }
}

class Model_BaseAttributeTest extends Test_Unit
{
    public function setup() {
        $this->model = new MBT_Model_Parent;
    }
    
    public function test_new_modeL_instance_gets_correct_default_attributes() {
        
        $model      = new MBT_Model_Parent;
        $attribs    = $model->get_attributes();
        
        assert_equal('Jason', $attribs['forename']);
        assert_equal('Frame', $attribs['surname']);
        _assert($attribs['born_on']->equals(new Date(1980, 12, 12)));
        assert_equal(false, $attribs['authorised']);
        assert_equal(4, count($attribs));
        
    }
    
    public function test_new_child_model_instance_gets_correct_default_attributes() {
        
        $model      = new MBT_Model_Child;
        $attribs    = $model->get_attributes();
        
        assert_equal('Jason', $attribs['forename']);
        assert_equal('Frame', $attribs['surname']);
        _assert($attribs['born_on']->equals(new Date(1980, 12, 12)));
        assert_equal(true, $attribs['authorised']);
        assert_equal('Captain', $attribs['position']);
        assert_equal(5, count($attribs));
        
    }
    
    public function test_can_get_attributes() {
        
        $model = new MBT_Model_Child;
        
        assert_equal('Jason', $model->get_forename());
        assert_equal('Frame', $model->get_surname());
        _assert($model->get_born_on()->equals(new Date(1980, 12, 12)));
        assert_equal(true, $model->get_authorised());
        assert_equal('Captain', $model->get_position());
        
    }
    
    public function test_can_set_attributes() {
        
        $model = new MBT_Model_Child;
        
        $model->set_forename('Jim');
        assert_equal('Jim', $model->get_forename());
        
    }
    
    public function test_boolean_check_is_sane() {
        
        $model = new MBT_Model_Child;
        
        $model->set_forename('foo');
        _assert($model->is_forename());
        
        $model->set_forename('');
        _assert(!$model->is_forename());
        
        $model->set_authorised(true);
        _assert($model->is_authorised());
        
        $model->set_authorised(false);
        _assert(!$model->is_authorised());
        
    }
    
    public function test_magic_access_of_non_existant_attribute_throws() {
        
        $model = new MBT_Model_Child;
        
        try {
            $model->get_foo();
            fail();
        } catch (Model_NoSuchAttributeError $nsae) {
            pass();
        }
        
        try {
            $model->is_foo();
            fail();
        } catch (Model_NoSuchAttributeError $nsae) {
            pass();
        }
        
        try {
            $model->set_foo('bar');
            fail();
        } catch (Model_NoSuchAttributeError $nsae) {
            pass();
        }
        
    }
    
    public function test_array_access_delegates_to_getter() {
        
        $model = new MBT_Model_Child;

        // Test delegation to magic getter
        assert_equal($model->get_forename(), $model['forename']);
        
        // Test delegation to defined getter
        $model->set_non_attribute('foobar');
        assert_equal($model->get_non_attribute(), $model['non_attribute']);
        
    }
    
    public function test_array_set_delegates_to_setter() {
        
        $model = new MBT_Model_Child;
        
        // Test delegation to magic setter
        $model['forename'] = 'Jim';
        assert_equal('Jim', $model->get_forename());
        
        // Test delegation to defined setter
        $model['non_attribute'] = 'foo';
        assert_equal('FOO', $model->get_non_attribute());
        
    }
    
    public function test_array_offset_exists_is_sane() {
        
        $m1 = new MBT_Model_Parent;
        
        _assert(isset($m1['forename']));
        _assert(!isset($m1['position']));
        
        $m2 = new MBT_Model_Child;
        
        _assert(isset($m2['forename']));
        _assert(isset($m2['position']));
        
    }
    
    public function test_cannot_unset_attributes() {
        $m1 = new MBT_Model_Parent;
        try {
            unset($m1['forename']);
            fail();
        } catch (Error_UnsupportedOperation $euo) {
            pass();
        }
    }
    
    public function test_array_access_of_non_existant_attribute_throws() {
        
        $model = new MBT_Model_Child;
        
        try {
            $x = $model['foo'];
            fail();
        } catch (Model_NoSuchAttributeError $nsae) {
            pass();
        }
        
        try {
            $model['foo'] = 'bar';
            fail();
        } catch (Model_NoSuchAttributeError $nsae) {
            pass();
        }
        
    }
    
    public function test_attribute_quoting() {
        
        $model      = new MBT_Model_Child;
        $db         = $model->db;
        $attribs    = $model->get_quoted_attributes();
        
        assert_equal($db->quote_string($model['forename']),         $attribs['forename']);
        assert_equal($db->quote_string($model['surname']),          $attribs['surname']);
        assert_equal($db->quote_date($model['born_on']),            $attribs['born_on']);
        assert_equal($db->quote_bool($model['authorised']),         $attribs['authorised']);
        assert_equal($db->quote_string($model['position']),         $attribs['position']);
        
        //
        // Now try NULLs
        
        $fields = array('forename', 'surname', 'born_on', 'authorised', 'position');
        
        foreach ($fields as $f) $model[$f] = null;
        $attribs = $model->get_quoted_attributes();
        foreach ($fields as $f) assert_equal('NULL', $attribs[$f]);
        
    }
}
?>