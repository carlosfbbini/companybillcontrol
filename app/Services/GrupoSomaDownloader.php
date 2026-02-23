<?php

namespace App\Services;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class GrupoSomaDownloader
{
    private string $baseUrl = "https://boletos.somagrupo.com.br";
    private string $urlLogin = 'validate_login.php';
    private $serverUrl = 'http://selenium:4444/wd/hub'; // URL do Selenium Server
    private string $urlSeeInvoices = "visualiza_boleto.php";
    private string $urlDownload = 'boleto.php';
    private string $urlPdf = 'soma/PDF';

    /**
     * Check for new invoices and download their PDFs.
     *
     * @return array List of downloaded invoice file paths
     */
    public function downloadNewInvoices(): array
    {
        
        $credentials = [
            '37536320000170' => env('CNPJ_37536320000170', 'Y29kaWdvOjUxMzU3OC1JTlRFUklPUkFOTkFT'), /* Amanda */
            '41896144000155' => env('CNPJ_41896144000155', ''), /* Carlos */
        ];

        $tokens = [
            '37536320000170' => null,
            '41896144000155' => null
        ];
        try{
            foreach ($credentials as $username => $password){
                $response = $this->login($username, $password, $tokens[$username]);

                $rows = $this->getRows($response);

                $urlsToDownload =  $this->getUrls($rows);

                $this->createBills($rows, $username, $urlsToDownload);
            }
        }catch(\Exception $e){
            // Log the error or handle it as needed
            // throw new \Exception("An error occurred while downloading invoices: " . $e->getMessage());
            dd($e);
        }
        
        return [];
    }

    protected function login($userName, $password, ?string $token = null): Response|PromiseInterface{

        if (!is_null($token)){
            $url = sprintf('%s/%s?cnpj=%s&t=%s', $this->baseUrl, $this->urlSeeInvoices, $userName, $token);
            $response = Http::timeout(60)->withoutVerifying()->get($url);
            if ($response->failed()){
                dd($response, $response->body());
            }
            return $response;
        }

        $url = sprintf('%s/%s', $this->baseUrl, $this->urlLogin);
        $params = [
            'cnpj' => $userName,
            'senha' => $password,
        ];   

        $response = Http::timeout(60)->asForm()->withoutVerifying()->post($url, $params);
        
        if ($response->failed()){
            dd($response, $response->body());
        }

        return $response;
    }

    protected function getRows($response){
        $html = $response->body();
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $table = null;
        $tables = $dom->getElementsByTagName('table');
        foreach ($tables as $t) {
            if ($t->getAttribute('id') === 'datatable-buttons2') {
                $table = $t;
                break;
            }
        }

        $rowsData = [];
        if ($table) {
            $rows = $table->getElementsByTagName('tr');
            foreach ($rows as $row) {
                $cellsData = [];
                $cells = $row->getElementsByTagName('td');
                foreach ($cells as $cell) {
                    $params = [];
                    $inputs = $cell->getElementsByTagName('input');
                    foreach ($inputs as $input) {
                        $inputsData = [
                            'name' => trim($input->getAttribute('name')),
                            'value' => trim($input->getAttribute('value')),
                        ];
                        $params[$inputsData['name']] = $inputsData['value'];
                    }

                    $cellsData[] = [
                        'text' => trim($cell->textContent),
                        'action' => $cell->getElementsByTagName('form')->length > 0 ? $cell->getElementsByTagName('form')->item(0)->getAttribute('action') : null,
                        'params' => $params
                    ];
                }
                if (!empty($cellsData)) {
                    $rowsData[] = $cellsData;
                }
            }
        }

        return $rowsData;
    }

    protected function getUrls($rows){
        $pdfUrlsDownload = [];

        foreach ($rows as $rowIndex => $row){
            foreach ($row as $cellIndex => $cell){
                if (!isset($cell['action'], $cell['params'])) continue;

                /** URL do PDF é  $this->baseUrl/$this->urlPdf/(CNPJ)_(Fatura)(Parcela).pdf*/
                $pdfName = sprintf('%s_%s%s.pdf', $cell['params']['cnpj'], $cell['params']['fatura'], $cell['params']['parcela']);
                $url = sprintf('%s/%s/%s', $this->baseUrl, $this->urlPdf, $pdfName);

                $pdfUrlsDownload[$rowIndex][$cellIndex] = $url;
            }
        }

        return $pdfUrlsDownload;
    }

    protected function createBills($rows, $cnpj, $urlsToDownload){
        $bills = [];
        foreach ($rows as $rowIndex => $row){
            foreach ($row as $cellIndex => $cell){
                // if (!isset($urlsToDownload[$rowIndex][$cellIndex])) continue;
                /**
                 * Cabeçalho = [
                 *      NOTA, PARCELA, SÉRIE, VALOR, VENCIMENTO, BANCO, D.ATRASO, BOLETO
                 * ];
                */

                try {
                    
                    $bills[] = [
                        'company' => '',
                        'cnpj' => $cnpj,
                        'amount' => str_replace(['R$', '.'], '', $cell[3]['text']),
                        'due_date' => \DateTime::createFromFormat('d/m/Y', $cell[4]['text'])->format('Y-m-d'),
                        'paid' => false,
                        'bill_path' => $urlsToDownload[$rowIndex][$cellIndex] ?? null,
                        'invoice' => $cell[0]['text'] ?? null,
                        'installment' => $cell[1]['text'] ?? null
                    ]; 
                } catch (\Exception $e) {
                   dd($e);
                }
            }
        }
        dd($bills);
    }
}
