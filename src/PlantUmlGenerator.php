<?php

namespace App;

class PlantUmlGenerator 
{
    protected const START_TAG = "@startuml";
    protected const END_TAG = "@enduml";

    protected string $content = "";

    public function __construct(ClassCollector $classCollector) {
        $this->content .= self::START_TAG . PHP_EOL;
        $relations = [];
        foreach ($classCollector->classes as $class) {
            if ($class->extends) {
                $relations[] = "{$class->extends} <|-- {$class->name}";
            }
            foreach ($class->implements as $iface) {
                $relations[] = "{$iface} <|.. {$class->name}";
            }
        }
        foreach ($classCollector->classes as $class) {
            $declaration = match ($class->type) {
                'interface' => "interface {$class->name}",
                'abstract' => "abstract class {$class->name}",
                default => "class {$class->name}" 
            };
            $this->content .= "{$declaration} {" . PHP_EOL;
            foreach ($class->properties as [$vis, $name, $type]) {
                $this->content .= " {$vis} {$name} : {$type}" . PHP_EOL;
            }
            foreach ($class->methods as [$vis, $name, $params, $returnType]) {
                $paramList = implode(', ', $params);
                $this->content .= " {$vis} {$name}({$paramList}) : {$returnType}" . PHP_EOL;
            }
            $this->content .= "}" . PHP_EOL . PHP_EOL;
        }
        foreach ($classCollector->classes as $class) {
            foreach ($class->associations as $target) {
                $this->content .= "{$class->name} --> {$target}" . PHP_EOL;
            }
        }
        foreach (array_unique($relations) as $line) {
            $this->content .= $line . PHP_EOL;
        }
        $this->content .= self::END_TAG;
    }

    public function toFile(string $pathToFile): void {
        file_put_contents($pathToFile, $this->content);
    }
}
