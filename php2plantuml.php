<?php

require './vendor/autoload.php';

use App\Main;
use App\NotDirectoryException;

try {
    $main = new Main($argc, $argv);
} catch (NotDirectoryException $e) {
    echo $e->getMessage() . " " . PHP_EOL;
    echo "Usage: php php2plantuml.php --source=<path-to-directory> --format=<png|svg|pdf> --output=<path-to-diagram-output>" .PHP_EOL;
} 
