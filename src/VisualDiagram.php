<?php

namespace App;

use App\DiagramFormat;

class VisualDiagram 
{
    private const SERVICE_URL = 'https://kroki.io/plantuml/';
    private string $content = '';

    public function __construct(
        private string $outputDir=__DIR__, 
        private string $format=DiagramFormat::PNG
    ) {
    }

    public function getDiagram(): void {
        
        $payload = json_encode(['diagram_source' => $this->content]);

        $ch = curl_init($this->getServiceUrl());
        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $imageData = curl_exec($ch);

        if (curl_errno($ch)) {
            echo "cURL eror: " . curl_error($ch) . PHP_EOL;
        } else {
            file_put_contents($this->outputDir . '/class_diagram.' . $this->format, $imageData);
        }

        curl_close($ch);
    }

    private function getServiceUrl(): string {
        return self::SERVICE_URL . $this->format;
    }

    public function setContent(string $pathToFile):void {
        $this->content = file_get_contents($pathToFile);
    }
}
