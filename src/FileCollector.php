<?php

namespace App;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileCollector {
    protected array $files;
    public function __construct(
            string $directory, 
    ) {
        $recursiveIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($recursiveIterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->files[] = $file->getPathName();
            }
        }
    }

    public function getFiles(): array {
        return $this->files;
    }
}
