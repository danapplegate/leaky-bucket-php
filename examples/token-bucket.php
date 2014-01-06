<?php
require_once __DIR__ . '/../vendor/autoload.php';

function displayBucket($bucket) {
    echo <<<BUCKET

Fill: {$bucket->getFill()}
Max: {$bucket->getMax()}
Last Timestamp: {$bucket->getLastTimestamp()}
Rate: {$bucket->getRate()}


BUCKET;
}

use danapplegate\LeakyBucket\TokenBucket;

echo "Token Bucket test" . PHP_EOL;

$bucket = new TokenBucket;
$bucket->start();

echo "Bucket created and started" . PHP_EOL;
displayBucket($bucket);

echo "How much to pour? ";
$weight = null;
fscanf(STDIN, "%f\n", $weight);

echo "Pouring $weight from bucket. Before:" . PHP_EOL;
displayBucket($bucket);
$successful = $bucket->pour($weight);
echo ($successful) ? 'Pour successful. ' : 'Pour unsuccessful. ';
echo "After:" . PHP_EOL;
displayBucket($bucket);