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
namespace danapplegate\LeakyBucket\Test;

use danapplegate\LeakyBucket\TokenBucket;

/**
 * TokenBucketTest - tests the functionality of the TokenBucket class. 
 *
 * @author      Dan Applegate <dan@skillshare.com>
 * @copyright   Copyright 2012-2014 Dan Applegate
 * @license     MIT license
 */
class TokenBucketTest extends \PHPUnit_Framework_TestCase {

    protected $bucket;

    protected function getStorageMockbuilder() {
        return $this->getMockBuilder(\danapplegate\LeakyBucket\Storage\FileStorage::class);
    }

    protected function getBucket($storage) {
        return new TokenBucket($storage);
    }

    protected function setUp() {
        // Construct valid mock storage object that expects to do nothing
        $mockStorage = $this->getStorageMockbuilder()
            ->getMock();
        $mockStorage
            ->expects($this->never())
            ->method($this->anything());
        $this->bucket = $this->getBucket($mockStorage);
    }

    protected function tearDown() {
        unset($this->bucket);
    }

    public function testStartMethodStartsBucketTimer() {
        $mockStorage = $this->getStorageMockbuilder()
            ->setMethods(['readBucket', 'writeBucket'])
            ->getMock();
        $bucket = $this->getBucket($mockStorage);
        $mockStorage
            ->expects($this->once())
            ->method('readBucket')
            ->with($this->identicalTo($bucket));
        $mockStorage
            ->expects($this->once())
            ->method('writeBucket')
            ->with($this->identicalTo($bucket));
        $this->assertNull($bucket->getLastTimestamp());
        $bucket->start();
        $this->assertNotNull($bucket->getLastTimestamp());
        $this->assertInternalType('float', $bucket->getLastTimestamp());
    }

    /**
     * @expectedException \Exception
     */
    public function testSetMaxAfterBucketStartedFails() {
        $mockStorage = $this->getStorageMockbuilder()
            ->setMethods(['readBucket', 'writeBucket'])
            ->getMock();
        $bucket = $this->getBucket($mockStorage);
        $mockStorage
            ->expects($this->once())
            ->method('readBucket')
            ->with($this->identicalTo($bucket));
        $bucket->start();
        $bucket->setMax(100);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetInvalidFillFails() {
        $this->bucket->setFill(-1);
    }

    public function testSetValidFillConstrainedByMax() {
        $this->bucket->setMax(100);
        $this->assertEquals(100, $this->bucket->getMax());
        $this->bucket->setFill(101);
        $this->assertEquals(100, $this->bucket->getFill());
    }

    public function testSetValidMaxReducesOverfillToMax() {
        $this->bucket->setMax(100);
        $this->bucket->setFill(90);
        $this->bucket->setMax(80);
        $this->assertEquals(80, $this->bucket->getFill());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetInvalidLastTimestampString() {
        $this->bucket->setLastTimestamp('test');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetInvalidLastTimestampNegative() {
        $this->bucket->setLastTimestamp('-123.45');
    }

    public function testSetValidLastTimestamp() {
        $this->bucket->setLastTimestamp('123.45');
        $this->assertEquals(123.45, $this->bucket->getLastTimestamp());
    }
}