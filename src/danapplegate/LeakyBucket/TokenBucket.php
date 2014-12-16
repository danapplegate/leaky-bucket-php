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
 * TokenBucket.php - Provides basic mechanism for defining and manipulating a new 
 * leaky bucket.
 *
 * @author 		Dan Applegate <applegatedt@gmail.com>
 * @copyright   Copyright 2012 Dan Applegate
 * @since 		Mar 27, 2012
 * @license     MIT license
 */ 
class TokenBucket {

    protected $fill;
    protected $name;
    protected $max;
    protected $rate;
    protected $lastTimestamp;
    protected $storage;

    protected static $defaults = array(
        'max' => 10,
        'rate' => 0.167,
        'fill' => 0,
        'name' => 'default',
        'prefix' => 'LeakyBucket'
    );

    /**
     * Construct a new leaky bucket object.
     *
     * @param $storage SotrageInterface Persister for this leaky bucket
     * @param $options array Configuration settings.
     */
    public function __construct(StorageInterface $storage = null, $options = array()) {
        $options = array_intersect_key($options, self::$defaults);
        $options = array_merge(self::$defaults, $options);
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }
        $this->storage = $storage;
        $this->name = $options['prefix'] . '_' . $this->name;
    }

    public function setStorage(StorageInterface $storage) {
        if ($this->storage) {
            throw new \Exception;
        }
        $this->storage = $storage;
    }

    public function getName() {
        return $this->name;
    }

    public function setMax($max) {
        if (isset($this->lastTimestamp)) {
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
        $this->fill = ($fill <= $this->max) ? $fill : $this->max;
    }

    public function getFill() {
        return $this->fill;
    }

    public function getLastTimestamp() {
        return $this->lastTimestamp;
    }

    public function setLastTimestamp($timestamp) {
        $timestamp = floatval($timestamp);
        if ((!is_float($timestamp)) || $timestamp <= 0)
            throw new \InvalidArgumentException;
        $this->lastTimestamp = $timestamp;
    }

    /**
     * Start should be called on any created bucket before it is used. If the 
     * bucket does not exist in the persistent storage engine at the time this 
     * is called, start will write the bucket using the storage engine and set 
     * the time of the last mark as the lastTimestamp of this bucket.
     *
     * If the bucket already exists in the storage medium, the values of the 
     * bucket will be updated to reflect those in the storage medium.
     *
     */
    public function start() {
        $this->storage->readBucket($this);
        if (!$this->lastTimestamp) {
            // This is the first time the bucket has been started, persist to 
            // storage
            $this->lastTimestamp = microtime(true);
            $this->storage->writeBucket($this);
        }
        $this->_updateFill();
    }

    public function pour($weight = 1) {
        $this->storage->readBucket($this);
        if ($weight <= $this->getFill()) {
            $newFill = $this->getFill() - $weight;
            $this->setFill($newFill);
            $this->setLastTimestamp(microtime(true));
            $this->storage->writeBucket($this);
            $this->_updateFill();
            return true;
        } else {
            return false;
        }
    }

    protected function _updateFill() {
        if ($this->getLastTimestamp()) {
            // Calculate the new fill
            $elapsed = microtime(true) - $this->getLastTimestamp();
            $this->setFill($this->getFill() + $this->getRate() * $elapsed);
        }
    }
}
