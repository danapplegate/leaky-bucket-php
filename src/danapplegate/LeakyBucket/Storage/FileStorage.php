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
namespace danapplegate\LeakyBucket\Storage;

use danapplegate\LeakyBucket\TokenBucket;

/**
 * FileStorage.php - Basic persistent storage to the filesystem.
 *
 * @package     PHP Leaky Bucket
 * @author 		Dan Applegate <applegatedt@gmail.com>
 * @copyright   Copyright 2012 Dan Applegate
 * @since 		Mar 27, 2012
 * @license     MIT license
 */ 
class FileStorage implements StorageInterface {

    protected $path;

    protected static $defaults = array(
        'path' => null
    );

    public function __construct($options = array()) {
        $options = array_intersect_key($options, self::$defaults);
        $options = array_merge(self::$defaults, $options);
        if (!$options['path'])
            $options['path'] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'buckets';
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }
        if (!file_exists($this->path) && !mkdir($this->path, 0777, true)) {
            throw new \InvalidArgumentException;
        }
        if (!is_writable($this->path)) {
            throw new \InvalidArgumentException;
        }
    }

    public function readBucket(TokenBucket $bucket) {
        $filename = $this->_constructFilename($bucket->getName());
        if (!file_exists($filename)) {
            return false;
        }
        $mark_parts = explode(':', file_get_contents($filename));
        if (count($mark_parts) != 2) {
            // Unrecognized format
            return false;
        }
        list($time, $fill) = $mark_parts;
        $bucket->setLastTimestamp($time);
        $bucket->setFill($fill);

        return true;
    }

    public function writeBucket(TokenBucket $bucket) {
        $filename = $this->_constructFilename($bucket->getName());
        $mark_parts = array(
            'time' => $bucket->getLastTimestamp(),
            'fill' => $bucket->getFill()
        );
        file_put_contents($filename, implode(':', $mark_parts));

        return true;
    }

    protected function _constructFilename($name) {
        $filename = $this->path . DIRECTORY_SEPARATOR . $name;
        return $filename;
    }
}
