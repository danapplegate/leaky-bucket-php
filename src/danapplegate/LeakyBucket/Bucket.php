<?php
/*
 *  Copyright (c) 2012 Dan Applegate <applegatedt@gmail.com>
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
namespace danapplegate\LeakyBucket;

use danapplegate\LeakyBucket\Storage\StorageInterface;
use danapplegate\LeakyBucket\Storage\FileStorage;

/**
 * Bucket.php - Provides basic mechanism for defining and manipulating a new 
 * leaky bucket.
 *
 * @package     PHP Leaky Bucket
 * @author 		Dan Applegate <applegatedt@gmail.com>
 * @copyright   Copyright 2012 Dan Applegate
 * @since 		Mar 27, 2012
 * @license     MIT license
 */ 
class Bucket {

    protected $fill;
    protected $name;
    protected $max;
    protected $rate;
    protected $start;
    protected $storage;

    protected static $defaults = array(
        'max' => 10,
        'rate' => 0.167,
        'fill' => 0,
        'name' => 'default',
        'prefix' => 'LeakyBucket'
    );

    public function __construct($options = array(), StorageInterface $storage = new FileStorage) {
        $options = array_intersect_key($options, self::$defaults);
        $options = array_merge(self::$defaults, $options);
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }
        $this->storage = $storage;
        $this->start = null;
        $this->name = $options['prefix'] . '_' . $this->name;
    }

    public function setMax($max) {
        if (isset($this->start)) {
            throw new \Exception;
        }
        $this->max = $max;
        $this->fill = ($this->fill > $this->max) ? $this->max : $this->fill;
    }

    public function getMax() {
        return $this->max;
    }

    public function setRate($rate) {
        if ($rate < 0) {
            throw new \InvalidArgumentException;
        }
        $this->rate = $rate;
    }

    public function getRate() {
        return $this->rate;
    }

    public function setFill($fill) {
        if ($fill < 0) {
            throw new \InvalidArgumentException;
        }
        $this->fill = $fill;
    }

    public function getFill() {
        return $this->fill;
    }

    public function start() {
        $this->start = microtime(true);
    }

    public function pour($weight = 1) {
        $this->_updateFill();
        if ($weight <= $this->fill) {
            $this->fill -= $weight;
            $this->storage->setMark($this->name, $this->fill);
            return true;
        } else {
            return false;
        }
    }

    protected function _updateFill() {
        $last_mark = $this->storage->getMark($this->name);
        if ($last_mark) {
            $elapsed = microtime(true) - $last_mark->time;
            $new_fill = ($new_fill > $this->max) ? $this->max : $last_mark->fill + ($elapsed * $this->rate);
            $this->fill = $new_fill;
        }
    }
}
