<?php

namespace App;

class ClassInfo {
    public string $name;
    public array $properties = [];
    public array $methods = [];
    public ?string $extends = null;
    public array $implements = [];
    public string $type = 'class';  //class | inerface | abstract
    public array $associations = [];
}
