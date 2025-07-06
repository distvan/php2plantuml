<?php 

namespace App;

use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\UnionType;

class ClassCollector extends NodeVisitorAbstract {
    public array $classes = [];

    public function enterNode(Node $node) {
        if ($node instanceof Class_ && $node->name ||
            $node instanceof Interface_ ||
            $node instanceof Trait_
        ) {
            $info = new ClassInfo();
            
            //Fully qualified name
            if (property_exists($node, 'namespacedName')) {
                $info->name = $node->namespacedName->toString();
            } elseif ($node->name){
                $info->name = $node->name->toString();
            } else {
                $info->name = 'AnonymousClass';
            }

            //Determine type
            if ($node instanceof Interface_) {
                $info->type = 'interface';
            } elseif ($node instanceof Class_ && $node->isAbstract()) {
                $info->type = 'abstract';
            }
            
            //Inheritance and interfaces
            if ($node instanceof Class_ && $node->extends) {
                $info->extends = $node->extends->toString();
            }
            if ($node instanceof Class_ || $node instanceof Interface_) {
                foreach ($node->implements as $impl) {
                    $info->implements[] = $impl->toString();
                }
            }

            //Properties
            if ($node instanceof Class_) {
                foreach ($node->getProperties() as $prop) {
                    foreach($prop->props as $p) {
                        $visibility = $this->getVisibility($prop);
                        $name = $p->name->toString();
                        $type = $prop->type ? $this->getTypeAsString($prop->type) : 'mixed';                 
                        $info->properties[] = [$visibility, $name, $type];
                        if ($this->isClassType($type)) {
                            foreach ($this->extractTypes($type) as $associatedClass) {
                                $info->associations[] = $associatedClass;
                            }
                        }
                    }
                }
            }

            //Methods
            foreach ($node->getMethods() as $method) {
                $visibility = $this->getVisibility($method);
                $name = $method->name->toString();
                $params = [];
                foreach ($method->getParams() as $param) {
                    $paramName = '$' . $param->var->name;
                    $paramType = $param->type ? $this->getTypeAsString($param->type) : 'mixed';
                    $params[] = "{$paramName}: {$paramType}";
                }
                $returnType = $method->getReturnType() ? $this->getTypeAsString($method->getReturnType()) : 'void';
                $info->methods[] = [$visibility, $name, $params, $returnType];
            }

            $this->classes[] = $info;            
        }
    }

    private function isClassType(string $type):bool {
        $type = strtolower(trim($type));
        //remove array markers
        $type = preg_replace('/\[\]$/', '', $type);
        $type = preg_replace('/^array<(.+)>$/', '$1', $type);
        $builtIns = [
            'int', 'float', 'string', 'bool', 'array', 'object', 
            'callable', 'mixed', 'void', 'iterable', 'null', 
            'false', 'true', 'self', 'static', 'parent'
        ];
        return !in_array($type, $builtIns);
    }

    private function extractTypes(string $type): array {
        $type = str_replace('?', '', $type);

        $delimiters = ['|', '&'];
        foreach ($delimiters as $delimiter) {
            if (str_contains($type, $delimiter)) {
                return array_filter(
                    explode($delimiter, $type),
                    fn($t) => $this->isClassType(trim($t))
                );
            }
        }
        return $this->isClassType($type) ? [$type] : [];
    }

    private function getTypeAsString($type): string {
        $value = 'mixed';
        
        if ($type instanceof NullableType) {
            $value = '?' . $this->getTypeAsString($type->type);
        } elseif ($type instanceof Identifier) {
            $value = $type->toString();
        } elseif ($type instanceof FullyQualified || $type instanceof Name) {
            $value = $type->toString();
        } elseif (is_string($type)) {
            $value = $type;
        } elseif ($type instanceof UnionType) {
            $value = implode('|', array_map([$this, 'getTypeAsString'], $type->types));
        } elseif ($type instanceof IntersectionType) {
            $value = implode('&', array_map([$this, 'getTypeAsString'], $type->types));
        }
        
        return $value;
    }

    private function getVisibility($node): string {
        if ($node->isPrivate()) {
            return '-';
        }
        if ($node->isProtected()) {
            return '#';
        }
        return '+';
    }
}
