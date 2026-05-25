<?php
header('Content-Type: text/plain');
echo "Current dir: " . __DIR__ . "\n";
echo "Parent dir: " . realpath(__DIR__ . '/..') . "\n";
echo "Parent contents:\n";
print_r(scandir(__DIR__ . '/..'));
echo "\nCurrent dir contents:\n";
print_r(scandir(__DIR__));
