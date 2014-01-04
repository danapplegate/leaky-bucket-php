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

    protected function setUp() {
        $this->bucket = new TokenBucket;
    }

    protected function tearDown() {
        unset($this->bucket);
    }

    public function testStartMethodStartsBucketTimer() {
        $this->assertNull($this->bucket->getStart());
        $this->bucket->start();
        $this->assertNotNull($this->bucket->getStart());
        $this->assertInternalType('float', $this->bucket->getStart());
    }

    /**
     * @expectedException \Exception
     */
    public function testSetMaxAfterBucketStartedFails() {
        $this->bucket->start();
        $this->bucket->setMax(100);
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
}