<?php

declare(strict_types=1);

namespace App\Parsers;

use DOMDocument;
use DOMElement;
use DOMXPath;

class HtmlTableParser
{
    /**
     * @return array<int, DOMElement>
     */
    public function rows(string $html, string $xpath = '//tbody/tr'): array
    {
        $dom = new DOMDocument();

        @$dom->loadHTML($html);

        $nodes = (new DOMXPath($dom))->query($xpath);

        if (! $nodes) {
            return [];
        }

        $rows = [];

        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $rows[] = $node;
            }
        }

        return $rows;
    }
}
