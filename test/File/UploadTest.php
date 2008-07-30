<?php
class File_UploadTest extends Test_Unit
{
    public function setup() {
        file_put_contents('test.tmp', 'this is test content');
        
        $file = array(
            'tmp_name'          => getcwd() . '/test.tmp',
            'type'              => 'text/fake',
            'size'              => 100,
            'name'              => 'filename.txt',
            'error'             => UPLOAD_ERR_OK
        );
        
        $this->file = new File_Upload($file);
    }
    
    public function teardown() {
        @unlink('test.tmp');
    }
    
    public function test_size() {
        assert_equal(100, $this->file->size());
    }
    
    public function test_type() {
        assert_equal('text/fake', $this->file->type());
    }
    
    public function test_path_info_before_move() {
        assert_equal('filename.txt', $this->file->basename());
        assert_equal('filename', $this->file->filename());
        assert_equal('txt', $this->file->extension());
    }
    
    public function test_path_info_after_move() {
        $this->file->move_to('foo.text');
        assert_equal('foo.text', $this->file->basename());
        assert_equal('foo', $this->file->filename());
        assert_equal('text', $this->file->extension());
        @unlink('foo.text');
    }
    
    public function test_success_when_error_ok() {
        _assert($this->file->success());
        assert_equal(UPLOAD_ERR_OK, $this->file->error());
        _assert($this->file->exists());
        _assert($this->file->is_file());
        _assert($this->file->upload_attempted());
    }
    
    public function test_not_success_when_error() {
        
        $this->file = new File_Upload(array(
            'tmp_name'      => 'foo.bar',
            'type'          => 'blah',
            'size'          => 100,
            'name'          => 'foo.baz',
            'error'         => UPLOAD_ERR_NO_FILE
        ));
        
        _assert(!$this->file->success());
        assert_not_equal(UPLOAD_ERR_OK, $this->file->error());
        _assert(!$this->file->exists());
        _assert(!$this->file->is_file());
        _assert(!$this->file->upload_attempted());
    }
    
}
?>