<?php

declare(strict_types=1);

namespace App\Services\Scraping;

use App\DTO\PayrollDTO;
use App\Parsers\PayrollParser;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;

class PayrollScraperService
{
    /**
     * @return array<int, PayrollDTO>
     */
    public function scrape(string $entity, int $month, int $year, int $regime): array
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:148.0) Gecko/20100101 Firefox/148.0',
                'Accept' => '*/*',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Referer' => 'https://transparencia.fatorsistemas.com.br/dados/folhapag.php?id='.$entity,
                'Connection' => 'keep-alive',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'no-cache',
            ])
            ->get('https://transparencia.fatorsistemas.com.br/dados/carregaFolha.php', [
                'id' => $entity,
                'mes' => $month,
                'ano' => $year,
                'regime' => $regime,
                'nm_servidor' => '',
            ]);

        $body = $response->body();

        $dom = new DOMDocument();
        @$dom->loadHTML($body);
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//tbody/tr');

        $records = [];

        if ($nodes) {
            foreach ($nodes as $node) {
                if (! ($node instanceof DOMElement)) {
                    continue;
                }

                if ($node->getElementsByTagName('td')->length < 13) {
                    continue;
                }

                $records[] = PayrollParser::parseRow($node, $month, $year);
            }
        }

        return $records;
    }
}
