<?php

declare(strict_types=1);

namespace App\Services\Scraping;

use App\DTO\ExpenseSummaryDTO;
use App\Enums\ExpenseType;
use App\Parsers\ExpenseSummaryParser;
use App\Parsers\HtmlTableParser;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ExpenseSummaryScraperService
{
    private string $baseUrl = 'https://www.municipioonline.com.br/ba/prefeitura/portoseguro/cidadao/despesa';

    public function __construct(private HtmlTableParser $tableParser) {}

    /**
     * @return array<int, ExpenseSummaryDTO>
     *
     * @throws ConnectionException
     */
    public function scrape(ExpenseType $tab, int $year, int $month): array
    {
        $html = $this->fetch($tab, $year, $month);

        $records = [];

        foreach ($this->tableParser->rows($html) as $row) {
            if ($row->getElementsByTagName('td')->length < 10) {
                continue;
            }

            $records[] = ExpenseSummaryParser::parseRow($row, $month, $year);
        }

        return $records;
    }

    /**
     * @throws ConnectionException
     */
    private function fetch(ExpenseType $tab, int $year, int $month): string
    {
        $jar = new CookieJar();

        $monthStr = mb_str_pad((string) $month, 2, '0', STR_PAD_LEFT);
        $yearStr = (string) $year;

        $url = $this->baseUrl.$tab->anchor();
        $key = $tab->formKey();

        $response = Http::withOptions(['cookies' => $jar])->get($url);

        $html = $response->body();

        $viewState = $this->getInputValue($html, '__VIEWSTATE');
        $eventValidation = $this->getInputValue($html, '__EVENTVALIDATION');
        $viewStateGenerator = $this->getInputValue($html, '__VIEWSTATEGENERATOR');

        $post = Http::withOptions(['cookies' => $jar])
            ->asForm()
            ->post($url, [
                '__VIEWSTATE' => $viewState,
                '__VIEWSTATEGENERATOR' => $viewStateGenerator,
                '__EVENTVALIDATION' => $eventValidation,

                "ctl00\$body\$ddlAno{$key}" => $yearStr,
                "ctl00\$body\$hfAno{$key}" => $yearStr,

                "ctl00\$body\$ddlMes{$key}" => $monthStr,
                "ctl00\$body\$hfMes{$key}" => $monthStr,

                "ctl00\$body\$btnFiltrar{$key}S" => 'Button',
            ]);

        return $post->body();
    }

    private function getInputValue(string $html, string $name): ?string
    {
        preg_match('/name="'.preg_quote($name, '/').'"[^>]*value="([^"]*)"/', $html, $matches);

        return $matches[1] ?? null;
    }
}
