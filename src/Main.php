<?php

namespace App;

use App\ClassCollector;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Throwable;

/**
 * Main class
 */
class Main {
    public function __construct($argc, $argv) {
        $options = getopt("", ["source:", "format:", "output:"]);
        $sourceDir = $options["source"] ?? null;
        $format = $options["format"] ?? null;
        $diagramOut = $options["output"] ?? __DIR__;
        if (empty($sourceDir) || !is_dir($sourceDir)) {
            throw new NotDirectoryException("'$sourceDir' is not a directory!");
        }
        $this->run($sourceDir, $format, $diagramOut);
    }

    /**
     * run
     *
     * @param string $sourceDir
     * @param string|null $format
     * @param string $diagramOut
     * @return void
     */
    public function run(string $sourceDir, ?string $format, string $diagramOut):void {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $traverser = new NodeTraverser();
        $collector = new ClassCollector();
        $traverser->addVisitor($collector);
        foreach((new FileCollector($sourceDir))->getFiles() as $file) {
            try {
                $code = file_get_contents($file);
                $ast = $parser->parse($code);
                if (is_null($ast)) {
                    continue;
                }
                $traverser = new NodeTraverser();
                $traverser->addVisitor(new NameResolver());
                $traverser->addVisitor($collector);
                $traverser->traverse($ast);
            } catch (Throwable $e) {
                echo 'Parse error: ' . $e->getMessage() . PHP_EOL;
            }
        }
        $umlGenerator = new PlantUmlGenerator($collector);
        $umlGenerator->toFile($diagramOut . '/class_diagram.puml');
        if (!empty($format)) {
            $visual = new VisualDiagram($diagramOut, $format);
            $visual->setContent($diagramOut . '/class_diagram.puml');
            $visual->getDiagram();
        }
    }
}
