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
    public $bills;

    public function importCsv()
    {
        $this->validate([
            'csv' => 'required|file|mimes:csv,ods,txt',
        ]);

        DB::beginTransaction();

        try {
            $extension = $this->csv->getClientOriginalExtension();

            if ($extension === 'csv' || $extension === 'txt') {
                $path = $this->csv->getRealPath();
                $file = fopen($path, 'r');
                $header = fgetcsv($file);

                while ($row = fgetcsv($file)) {
                    $data = array_combine($header, $row);
                    Bill::create([
                        'company' => $data['company'],
                        'amount' => $data['amount'],
                        'due_date' => $data['due_date'],
                        'paid' => isset($data['paid']) ? (bool)$data['paid'] : false,
                    ]);
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
                            // Filter $header to only include expected columns
                            // $expectedColumns = ['cnpj', 'valor', 'vencimento', 'paid', 'pago', 'fatura', 'parcela'];
                            // $header = array_filter($header, function ($col) use ($expectedColumns) {
                            //     return in_array($col, $expectedColumns);
                            // });
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
            session()->flash('success', 'Bills imported successfully!');
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
        $this->bills = $query->orderBy('due_date', 'asc')->orderBy('amount', 'desc')->get();
        return view('components.⚡bill-manager');
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
}
