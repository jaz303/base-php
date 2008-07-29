<?php
class FileTest extends Test_Unit
{
    public function setup() {
        file_put_contents('test.txt', 'this is test content');
        $this->path = getcwd() . '/test.txt';
        $this->file = new File($this->path);
    }
    
    public function teardown() {
        @unlink('test.txt');
    }
    
    public function test_size() {
        assert_equal(filesize('test.txt'), $this->file->size());
    }
    
    public function test_path() {
        assert_equal($this->path, $this->file->path());
    }
    
    public function test_directory() {
        assert_equal(getcwd(), $this->file->directory());
    }
    
    public function test_basename() {
        assert_equal('test.txt', $this->file->basename());
    }
    
    public function test_filename() {
        assert_equal('test', $this->file->filename());
    }
    
    public function test_extension() {
        assert_equal('txt', $this->file->extension());
    }
    
    public function test_type() {
        assert_equal('text/plain', $this->file->type());
    }
    
    public function test_exists() {
        _assert($this->file->exists());
        $f2 = new File('xyz.foo');
        _assert(!$f2->exists());
    }
    
    public function test_is_file() {
        _assert($this->file->is_file());
        mkdir('foo');
        $f2 = new File('foo');
        _assert(!$f2->is_file());
        rmdir('foo');
    }
    
    public function test_is_dir() {
        _assert(!$this->file->is_dir());
        mkdir('foo');
        $f2 = new File('foo');
        _assert($f2->is_dir());
        rmdir('foo');
    }
    
    public function test_read() {
        assert_equal('this is test content', $this->file->read());
    }
    
    public function test_move_to() {
        $this->file->move_to('foo.txt');
        assert_equal('foo.txt', $this->file->basename());
        assert_equal('foo', $this->file->filename());
        _assert(file_exists('foo.txt'));
        _assert(!file_exists('test.txt'));
        @unlink('foo.txt');
    }
    
    public function test_move_to_directory() {
        mkdir('foo');
        $this->file->move_to('foo/');
        assert_equal('foo', $this->file->directory());
        assert_equal('foo/test.txt', $this->file->path());
        assert_equal('test', $this->file->filename());
        assert_equal('txt', $this->file->extension());
        _assert(file_exists('foo/test.txt'));
        _assert(!file_exists('test.txt'));
        @unlink('foo/test.txt');
        @rmdir('foo');
    }
    
    public function test_move_to_with_extension_preservation() {
        $this->file->move_to('bar', true);
        assert_equal('bar.txt', $this->file->basename());
        assert_equal('bar', $this->file->filename());
        _assert(file_exists('bar.txt'));
        _assert(!file_exists('test.txt'));
        @unlink('bar.txt');
    }
}
?>