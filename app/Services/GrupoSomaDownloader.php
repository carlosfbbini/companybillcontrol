<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GrupoSomaDownloader
{
    /**
     * Check for new invoices and download their PDFs.
     *
     * @return array List of downloaded invoice file paths
     */
    public function downloadNewInvoices(): array
    {
        // Example: Replace with actual logic to fetch invoice data from Grupo Soma
        $invoices = $this->fetchNewInvoices();
        $downloaded = [];

        foreach ($invoices as $invoice) {
            $pdfUrl = $invoice['pdf_url'];
            $fileName = storage_path('app/invoices/' . $invoice['number'] . '.pdf');
            $response = Http::get($pdfUrl);
            if ($response->successful()) {
                file_put_contents($fileName, $response->body());
                $downloaded[] = $fileName;
            }
        }
        return $downloaded;
    }

    /**
     * Fetch new invoices from Grupo Soma (stub).
     * Replace this with actual API or scraping logic.
     *
     * @return array
     */
    protected function fetchNewInvoices(): array
    {
        // Example stub data
        return [
            [
                'number' => 'INV-2026-001',
                'pdf_url' => 'https://example.com/invoices/INV-2026-001.pdf',
            ],
            // ... more invoices ...
        ];
    }
}
