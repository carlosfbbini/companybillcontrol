<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Bill;
use Illuminate\Support\Facades\DB;

class BillManager extends Component{

    use WithFileUploads;

    public $csv;
    public $filterCnpj = '';
    public $filterDate = '';
    public $filterByPaid = '';

    public function importCsv()
    {
        // Only validiate if $this->csv is null
        if (!$this->csv) {
            $this->validate([
                'csv' => 'required|file|mimes:csv,ods,txt',
            ]);
            
        }


        DB::beginTransaction();

        try {
            $extension = $this->csv->getClientOriginalExtension();

            if ($extension === 'csv' || $extension === 'txt') {
                $path = $this->csv->getRealPath();
                $file = fopen($path, 'r');
                $header = fgetcsv($file);
                $header = array_map('strtolower', $header);

                while ($row = fgetcsv($file)) {
                    if (count($header) !== count($row)) {
                        /* count them and make them same size */
                        $min = min(count($header), count($row));
                        $header = array_slice($header, 0, $min);
                        $row = array_slice($row, 0, $min);
                    }
                    $data = array_combine($header, $row);

                    // dd($data);
                    Bill::updateOrCreate(
                        [
                            'cnpj' => $data['cnpj'] ?? null,
                            'invoice' => $data['fatura'] ?? $data['invoice'] ?? null,
                            'installment' => $data['parcela'] ?? $data['installment'] ?? null,
                            'due_date' => $data['vencimento'] ?? $data['due_date'] ?? null,
                        ],
                        [
                            'company' => $data['company'] ?? 'Desconhecido',
                            'amount' => $this->castAmount($data['valor'] ?? $data['amount'] ?? '0'),
                            'paid' => isset($data['paid']) ? (bool)$data['paid'] : false,
                        ]
                    );
                }
                fclose($file);
            } elseif ($extension === 'ods') {
                // Requires "box/spout" package: composer require box/spout
                $path = $this->csv->getRealPath();
                $reader = \Box\Spout\Reader\Common\Creator\ReaderEntityFactory::createODSReader();
                $reader->open($path);

                foreach ($reader->getSheetIterator() as $sheet) {
                    $tagName = $sheet->getName();
                    if ($tagName !== date('m_y')) {
                        continue;
                    }

                    foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                        $cells = $row->toArray();
                        if ($rowIndex === 1) {
                            $header = array_map('strtolower', $cells);
                            continue;
                        }

                        // dd($header, $cells);
                        if (count($header) !== count($cells)) {
                            /* count them and make them same size */
                            $min = min(count($header), count($cells));
                            $header = array_slice($header, 0, $min);
                            $cells = array_slice($cells, 0, $min);
                            // continue; // Skip rows that don't match header column count
                        }
                        $data = array_combine($header, $cells);
                        /** Check if exists with same CNPJ and invoice and installment for due_date */
                        Bill::updateOrCreate(
                            [
                                'cnpj' => $data['cnpj'] ?? null,
                                'invoice' => $data['fatura'] ?? null,
                                'installment' => $data['parcela'] ?? null,
                                'due_date' => $data['vencimento'] ?? null,
                            ],
                            [
                                'company' => $data['company'] ?? 'Desconhecido',
                                'amount' => $this->castAmount($data['valor']),
                                'paid' => isset($data['paid']) ? (bool)$data['paid'] : false,
                            ]
                        );
                    }
                }
                $reader->close();
            }

            DB::commit();
            $this->dispatch('success', message: 'Bills imported successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            session()->flash('error', 'Error importing bills: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $query = Bill::query();
        if ($this->filterCnpj) {
            $query->where('cnpj', 'like', '%' . $this->filterCnpj . '%');
        }
        if ($this->filterDate) {
            $query->whereDate('due_date', $this->filterDate);
        }

        if ($this->filterByPaid !== ''){
            $query->where('paid', $this->filterByPaid);
        }

        $bills = $query->orderBy('due_date', 'asc')->orderBy('amount', 'desc')->get();
        return view('components.⚡bill-manager', compact('bills'));
    }


    public function togglePaid($billId)
    {
        $bill = Bill::find($billId);
        if ($bill) {
            $bill->paid = !$bill->paid;
            $bill->save();
        }
    }

    /**
     * Method to cast formatted amount to decimal
     * 
     */
    public function castAmount($amount)
    {        
        // Remove any non-numeric characters except for the decimal separator
        $cleaned = preg_replace('/[^\d,.-]/', '', $amount);
        // Replace comma with dot for decimal point
        $normalized = str_replace(',', '.', $cleaned);
        return floatval($normalized);   
    }

    /**
     * Mehtod to export bills into CSV file
     *
     * @return void
     */
    public function exportCsv(){
        $bills = Bill::all();
        // Save in public path inside exports folder with name bills_YYYYMMDD_HHMMSS.csv
        $filename = public_path('exports/bills_' . date('Ymd_His') . '.csv');
        // Create exports directory if not exists
        if (!file_exists(public_path('exports'))) {
            mkdir(public_path('exports'), 0755, true);
        }
        $handle = fopen($filename, 'w+');
        fputcsv($handle, ['company', 'cnpj', 'amount', 'due_date', 'paid', 'paid_at', 'invoice', 'installment']);

        foreach ($bills as $bill) {
            fputcsv($handle, [
                $bill->company,
                $bill->cnpj,
                $bill->amount,
                $bill->due_date,
                $bill->paid,
                $bill->paid_at,
                $bill->invoice,
                $bill->installment
            ]);
        }

        fclose($handle);
        return response()->download($filename);
    }

    public function importLastExportedCsv()
    {
        $files = glob(public_path('exports/bills_*.csv'));

        if (empty($files)) {
            session()->flash('error', 'No exported CSV files found.');
            return;
        }

        $latestFile = collect($files)->sortByDesc(function ($file) {
            return filemtime($file);
        })->first();

        $this->csv = new \Illuminate\Http\UploadedFile($latestFile, basename($latestFile));

        $this->importCsv();
    }
}
