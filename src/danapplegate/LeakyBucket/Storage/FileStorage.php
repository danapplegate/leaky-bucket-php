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
use danapplegate\LeakyBucket\Exception\PermissionsException;

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

    private $path;

    public function __construct($path = null) {
        $this->path = $path;
        if (!$this->path) {
            $this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'buckets';
        }
    }

    public function init(TokenBucket $bucket) {
        if (!file_exists($this->path)) {
            mkdir($this->path, 0644, true);
        } else if (!is_writable($this->path)) {
            throw new PermissionsException("Could not create bucket directory in $this->path because it was not writable");
        }
    }

    public function start(TokenBucket $bucket) {
        $filename = $this->getFileName($bucket);
        $fh = fopen($filename, 'w');
        if (!$this->getExclusiveLock($fh)) {
            throw new \Exception('Could not obtain lock to start bucket');
        }
        $content = fread($fh, 1);
        if (!$content) {
            fwrite($fh, $this->formatFileContent($bucket->getFill()));
        }
        $this->releaseLock($fh);
    }

    private function formatFileContent($fill) {
        $time = microtime(true);
        return sprintf("%f:%f", $time, $fill);
    }

    private function getFileName(TokenBucket $bucket) {
        return $this->path . DIRECTORY_SEPARATOR . $bucket->getName();
    }

    private function getExclusiveLock($file) {
        return flock($file, LOCK_EX);
    }

    private function releaseLock($file) {
        return flock($file, LOCK_UN);
    }
}
