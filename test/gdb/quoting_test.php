<?php
class QuotingTest extends ztest\UnitTestCase
{
    public function setup() {
        $this->db = new GDB;
    }
    
    public function test_quote_string() {
        assert_equal('NULL', $this->db->quote_string(null));
        assert_equal("'foo\\'bar'", $this->db->quote_string("foo'bar"));
    }
    
    public function test_quote_binary() {
        assert_equal('NULL', $this->db->quote_binary(null));
        assert_equal("'foo\\'bar'", $this->db->quote_binary("foo'bar"));
    }
    
    public function test_quote_boolean() {
        assert_equal('NULL', $this->db->quote_boolean(NULL));
        assert_equal('1', $this->db->quote_boolean(true));
        assert_equal('0', $this->db->quote_boolean(false));
    }
    
    public function test_quote_integer() {
        assert_equal("123", $this->db->quote_integer(123));
        assert_equal("-456", $this->db->quote_integer(-456));
    }
    
    public function test_quote_float() {
        assert_equal('1.23', $this->db->quote_float(1.23));
        assert_equal('-10.1', $this->db->quote_float(-10.1));
    }
    
    public function test_auto_quote_query_numerical_indexing() {
        assert_equal('1 2 3 4 5', $this->db->auto_quote_query('{i} {i} {i} {i} {i}', array(1, 2, 3, 4, 5)));
    }
    
    public function test_auto_quote_query_associative_indexing() {
        assert_equal('1 2 2 1 4', $this->db->auto_quote_query(
            '{i:foo} {i:bar} {i:bar} {i:foo} {i:baz}',
            array(
                'foo' => 1,
                'bar' => 2,
                'baz' => 4
            )
        ));
    }
    
    public function test_auto_quote_query_type_quoting() {
        assert_equal("'foo' 1 1.23 1", $this->db->auto_quote_query('{s} {i} {f} {b}', 'foo', 1, 1.23, true));
    }
    
    public function test_auto_quote_query_comparisons() {
        assert_equal('= 10', $this->db->auto_quote_query('{=i}', array(10)));
        assert_equal('<> 12', $this->db->auto_quote_query('{!=i}', array(12)));
    }
    
    public function test_auto_quote_query_array_membership() {
        assert_equal('IN (1,2,3)', $this->db->auto_quote_query('{=i}', array(array(1,2,3))));
        assert_equal('IN (NULL)', $this->db->auto_quote_query('{=i}', array(array())));
        assert_equal('NOT IN (4,5,6)', $this->db->auto_quote_query('{!=i}', array(array(4,5,6))));
        assert_equal('NOT IN (NULL)', $this->db->auto_quote_query('{!=i}', array(array())));
    }
    
    public function test_auto_quote_query_null_comparisons() {
        assert_equal('IS NULL', $this->db->auto_quote_query('{=i}', array(null)));
        assert_equal('IS NOT NULL', $this->db->auto_quote_query('{!=i}', array(null)));
    }
    
    public function test_auto_quote_array() {
        
        $out = $this->db->auto_quote_array(array(
            's:s1'              => 'foobar',
            'str:s2'            => 'bar',
            'string:s3'         => 'moose',
            'b:b1'              => true,
            'bool:b2'           => false,
            'boolean:b3'        => true,
            'f:f1'              => 1.23,
            'float:f2'          => 2.46,
            'i:i1'              => 100,
            'int:i2'            => 120,
            'integer:i3'        => 140,
            'x:x1'              => 'sadasd',
            'binary:x2'         => '1315ft4g'
        ));
        
        $expect = array(
            's1'        => $this->db->quote_string('foobar'),
            's2'        => $this->db->quote_string('bar'),
            's3'        => $this->db->quote_string('moose'),
            'b1'        => $this->db->quote_boolean(true),
            'b2'        => $this->db->quote_boolean(false),
            'b3'        => $this->db->quote_boolean(true),
            'f1'        => $this->db->quote_float(1.23),
            'f2'        => $this->db->quote_float(2.46),
            'i1'        => $this->db->quote_integer(100),
            'i2'        => $this->db->quote_integer(120),
            'i3'        => $this->db->quote_integer(140),
            'x1'        => $this->db->quote_binary('sadasd'),
            'x2'        => $this->db->quote_binary('1315ft4g')
        );
        
        foreach ($expect as $k => $v) {
            assert_equal($v, $out[$k]);
        }
        
    }
}
?>