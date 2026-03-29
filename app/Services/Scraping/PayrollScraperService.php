<?php

declare(strict_types=1);

namespace App\Services\Scraping;

use App\DTO\PayrollDTO;
use App\Parsers\HtmlTableParser;
use App\Parsers\PayrollParser;
use Illuminate\Support\Facades\Http;

class PayrollScraperService
{
    public function __construct(private HtmlTableParser $tableParser)
    {
    }

    /**
     * @return array<int, PayrollDTO>
     */
    public function scrape(string $entity, int $month, int $year, int $regime): array
    {
        $response = $this->fetch($entity, $month, $year, $regime);

        $records = [];

        foreach ($this->tableParser->rows($response->body()) as $row) {
            if ($row->getElementsByTagName('td')->length < 13) {
                continue;
            }

            $records[] = PayrollParser::parseRow($row, $month, $year);
        }

        return $records;
    }

    private function fetch(
        string $entity,
        int $month,
        int $year,
        int $regime
    ): \Illuminate\Http\Client\Response|\GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Promises\LazyPromise {
        return Http::timeout(30)
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
    }
}
