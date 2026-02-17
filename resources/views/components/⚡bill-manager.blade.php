<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div>
    <div class="my-8">
        <form wire:submit.prevent="importCsv" class="flex flex-col md:flex-row items-center gap-4">
            <input type="file" wire:model="csv" accept=".csv,.ods,.txt"
                class="border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300" />
            <button type="submit"
                class="px-4 py-2 rounded bg-green-500 text-white hover:bg-green-600 focus:outline-none focus:ring focus:ring-green-300">
                Import Bills
            </button>
            @error('csv') <span class="text-red-500">{{ $message }}</span> @enderror
        </form>
        {{-- Button to create a backup of bill data in CSV format --}}
        <button wire:click="exportCsv"
            class="mt-4 px-4 py-2 rounded bg-blue-500 text-white hover:bg-blue-600 focus:outline-none focus:ring focus:ring-blue-300">
            Export Bills
        </button>

        {{-- Button to import last bill exported --}}
        <button wire:click="importLastExportedCsv"
            class="mt-4 px-4 py-2 rounded bg-yellow-500 text-white hover:bg-yellow-600 focus:outline-none focus:ring focus:ring-yellow-300">
            Import Last Exported Bills
        </button>
    </div>

    <div class="mt-8 flex flex-col md:flex-row items-center gap-4">
        <label class="flex items-center gap-2">
            <span class="text-gray-700">Filter by CNPJ:</span>
            <input type="text" wire:model.live="filterCnpj" placeholder="CNPJ"
                class="border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300" />
        </label>
        <label class="flex items-center gap-2">
            <span class="text-gray-700">Filter by Date:</span>
            <input type="date" wire:model.change.live="filterDate"
                class="border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300" />
        </label>
        {{-- Add a select input with paid, not paid and select --}}
        <label class="flex items-center gap-2">
            <span class="text-gray-700">Filter by Paid:</span>
            <select wire:model.live="filterByPaid"
                class="border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-300">
                <option value="">All</option>
                <option value="1">Paid</option>
                <option value="0">Not Paid</option>
            </select>
        </label>
    </div>

    {{-- Div contains total of day and month --}}
    <div class="mt-8 flex flex-col md:flex-row items-center gap-4">
        <div class="bg-gray-100 p-4 rounded shadow w-full md:w-auto">
            <h3 class="text-lg font-semibold text-gray-700">Total Vencimento do dia</h3>
            <p class="text-2xl font-bold {{ $totalDueToday >= 0 ? 'text-gray-500' : 'text-red-500' }}">R$ {{ number_format($totalDueToday, 2, ',', '.') }}</p>
        </div>
        {{-- Total Vencimento do mês --}}
        <div class="bg-gray-100 p-4 rounded shadow w-full md:w-auto">
            <h3 class="text-lg font-semibold text-gray-700">Total do mês</h3>
            <p class="text-2xl font-bold text-red-500">R$ {{ number_format($totalMonthlyDue, 2, ',', '.') }}</p>
        </div>
        
        <div class="bg-gray-100 p-4 rounded shadow w-full md:w-auto">
            <h3 class="text-lg font-semibold text-gray-700">Total Vencido do mês</h3>
            <p class="text-2xl font-bold text-red-500">R$ {{ number_format($totalOverdue, 2, ',', '.') }}</p>
        </div>

        <div class="bg-gray-100 p-4 rounded shadow w-full md:w-auto">
            <h3 class="text-lg font-semibold text-gray-700">Total Pago</h3>
            <p class="text-2xl font-bold text-red-500">R$ {{ number_format($totalPaid, 2, ',', '.') }}</p>
        </div>

    </div>

    <table class="mt-8 w-full border-collapse">
        <thead>
            <tr class="bg-gray-100">
                <th class="px-4 py-2 text-left font-semibold text-gray-700 cursor-pointer" wire:click="sortBy('company')">
                    Company
                    @if($sortField === 'company')
                        <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                    @endif
                </th>
                <th class="px-4 py-2 text-left font-semibold text-gray-700 cursor-pointer" wire:click="sortBy('cnpj')">
                    CNPJ
                    @if($sortField === 'cnpj')
                        <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                    @endif
                </th>
                <th class="px-4 py-2 text-left font-semibold text-gray-700 cursor-pointer" wire:click="sortBy('invoice')">
                    Fatura
                    @if($sortField === 'invoice')
                        <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                    @endif
                </th>
                <th class="px-4 py-2 text-left font-semibold text-gray-700 cursor-pointer" wire:click="sortBy('installment')">
                    Parcela
                    @if($sortField === 'installment')
                        <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                    @endif
                </th>
                <th class="px-4 py-2 text-left font-semibold text-gray-700 cursor-pointer" wire:click="sortBy('amount')">
                    Valor
                    @if($sortField === 'amount')
                        <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                    @endif
                </th>
                <th class="px-4 py-2 text-left font-semibold text-gray-700 cursor-pointer" wire:click="sortBy('due_date')">
                    Vencimento
                    @if($sortField === 'due_date')
                        <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                    @endif
                </th>
                <th class="px-4 py-2 text-left font-semibold text-gray-700 cursor-pointer" wire:click="sortBy('paid')">
                    Pago
                    @if($sortField === 'paid')
                        <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                    @endif
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bills as $bill)
                <tr wire:key="bill-{{ $bill->id }}" class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2">{{ $bill->company }}</td>
                    <td class="px-4 py-2">{{ $bill->cnpj }}</td>
                    <td class="px-4 py-2">{{ $bill->invoice }}</td>
                    {{-- Center align --}}
                    <td class="px-4 py-2 text-center">{{ $bill->installment }}</td>
                    {{-- Value in BRL, right align --}}
                    <td class="px-4 py-2 text-right">R$ {{ number_format($bill->amount, 2, ',', '.') }}</td>
                    {{-- Date formatted d/m/Y --}}
                    <td class="px-4 py-2">{{ \Carbon\Carbon::parse($bill->due_date)->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 flex items-center">
                        <span class="mr-2">{{ $bill->paid ? 'Sim' : 'Não' }}</span>
                        <button wire:click="togglePaid({{ $bill->id }})"
                            class="ml-2 px-3 py-1 rounded text-white text-sm focus:outline-none focus:ring {{ $bill->paid ? 'bg-red-500 hover:bg-red-600 focus:ring-red-300' : 'bg-green-500 hover:bg-green-600 focus:ring-green-300' }}">
                            Marcar como: {{ $bill->paid ? 'Não pago' : 'Pago' }}
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>