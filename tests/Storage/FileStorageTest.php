<?php
/*
 *  Copyright (c) 2012-2014 Dan Applegate <applegatedt@gmail.com>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of
 *  this software and associated documentation files (the "Software"), to deal in
 *  the Software without restriction, including without limitation the rights to
 *  use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 *  the Software, and to permit persons to whom the Software is furnished to do so,
 *  subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 *  FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 *  COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 *  IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 *  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace danapplegate\LeakyBucket\Test\Storage;

use danapplegate\LeakyBucket\Storage\FileStorage;
use danapplegate\LeakyBucket\TokenBucket;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStream;

class FileStorageTest extends \PHPUnit_Framework_TestCase {

    private $bucket;

    public function setUp() {
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('tmp'));
        $this->bucket = new TokenBucket(null, array(
            'name' => 'testName',
            'prefix' => 'FileStorageTest'
        ));
    }

    public function testInitCreatesCorrectDirectoryIfDirectoryNotExists() {
        $this->assertFalse(vfsStreamWrapper::getRoot()->hasChild('buckets'));
        $fileStorage = new FileStorage(vfsStream::url('tmp/buckets'));
        $fileStorage->init($this->bucket);
        $this->assertTrue(vfsStreamWrapper::getRoot()->hasChild('buckets'));
    }

    public function testInitThrowsPermissionsExceptionIfCantWriteToPath() {
        $this->setExpectedException('danapplegate\\LeakyBucket\\Exception\\PermissionsException');
        $this->assertTrue(mkdir(vfsStream::url('tmp/notWritable'), 0555));
        $this->assertFalse(is_writable(vfsStream::url('tmp/notWritable')));
        $fileStorage = new FileStorage(vfsStream::url('tmp/notWritable'));
        $fileStorage->init($this->bucket);
    }

    public function testStartCreatesCorrectlyFormattedBucketfile() {
        $fileStorage = new FileStorage(vfsStream::url('tmp'));
        $fileStorage->start($this->bucket);
        $files = scandir(vfsStream::url('tmp'));
        $this->assertEquals(3, count($files));
        $realFiles = array_diff($files, array('.', '..'));
        $file = array_shift($realFiles);
        $this->assertRegExp('/FileStorageTest(.*)testName/', $file);
        $contents = file_get_contents(vfsStream::url("tmp/$file"));
        $this->assertRegExp('/\d+\.\d+:\d+/', $contents);
    }
}