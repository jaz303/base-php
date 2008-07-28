<?php
class MIMETest extends Test_Unit
{
    public function test_add() {
        assert_equal(MIME::DEFAULT_MIME_TYPE, MIME::for_extension('abc123'));
        MIME::add('abc123', 'foo/bar');
        assert_equal('foo/bar', MIME::for_extension('abc123'));
    }
    
    public function test_filename_lookup() {
        assert_equal('image/jpeg', MIME::for_filename('foo.jpeg'));
        assert_equal('image/moose', MIME::for_filename('foo.xyz', 'image/moose'));
        assert_equal(MIME::DEFAULT_MIME_TYPE, MIME::for_filename('foo.xyz'));
    }
    
    public function test_extension_lookup() {
        assert_equal('image/gif', MIME::for_extension('gif'));
        assert_equal('image/zebra', MIME::for_extension('xyz', 'image/zebra'));
        assert_equal(MIME::DEFAULT_MIME_TYPE, MIME::for_extension('xyz'));
    }
}
?>