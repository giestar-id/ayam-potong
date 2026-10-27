<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk Nota {{ $sale->invoice_number }} - {{ $settings->business_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .thermal-receipt { box-shadow: none !important; border: none !important; width: 100% !important; max-width: 80mm !important; margin: 0 auto !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen py-8 text-gray-800 antialiased font-sans">
    <div class="no-print mx-auto flex max-w-sm justify-between px-4 pb-4">
        <a href="{{ route('sales.index') }}" class="btn text-xs">Kembali ke riwayat</a>
        <button onclick="window.print()" class="btn btn-primary text-xs">Cetak struk termal</button>
    </div>

    <div class="thermal-receipt mx-auto max-w-[380px] bg-white p-6 shadow-md rounded-2xl border border-gray-200 text-xs font-mono">
        <!-- Header Usaha -->
        <div class="text-center pb-4 border-b border-dashed border-gray-300">
            <h1 class="text-base font-bold uppercase tracking-wider text-gray-900">{{ $settings->business_name }}</h1>
            @if($settings->address)
                <p class="text-[11px] text-gray-500 mt-1 leading-tight">{{ $settings->address }}</p>
            @endif
            @if($settings->phone)
                <p class="text-[11px] text-gray-500 mt-0.5">Telp: {{ $settings->phone }}</p>
            @endif
        </div>

        <!-- Meta Transaksi -->
        <div class="py-3 border-b border-dashed border-gray-300 space-y-1 text-[11px]">
            <div class="flex justify-between">
                <span class="text-gray-500">No. Nota:</span>
                <span class="font-bold text-gray-900">{{ $sale->invoice_number }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Tanggal:</span>
                <span>{{ $sale->transaction_date->translatedFormat('d/m/Y') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Kasir:</span>
                <span>{{ $sale->user?->name ?? 'Kasir' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Pelanggan:</span>
                <span class="font-semibold">{{ $sale->customer?->name ?? 'Pelanggan Umum' }}</span>
            </div>
            <div class="flex justify-between items-center pt-1">
                <span class="text-gray-500">Status:</span>
                @if($sale->payment_status === 'paid')
                    <span class="px-2 py-0.5 rounded bg-green-100 text-green-800 font-bold text-[10px]">LUNAS</span>
                @else
                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-bold text-[10px]">PIUTANG / TEMPO</span>
                @endif
            </div>
        </div>

        <!-- Daftar Item Belanja -->
        <div class="py-3 border-b border-dashed border-gray-300">
            <table class="w-full text-left text-[11px]">
                <thead>
                    <tr class="text-gray-500 border-b border-gray-200">
                        <th class="pb-1">Item</th>
                        <th class="pb-1 text-center">Qty</th>
                        <th class="pb-1 text-right">Harga</th>
                        <th class="pb-1 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($sale->items as $item)
                        <tr>
                            <td class="py-1.5 font-medium pr-1">
                                {{ $item->product->name }}
                                @if($item->notes)
                                    <span class="block text-[10px] text-gray-400 italic font-sans">{{ $item->notes }}</span>
                                @endif
                            </td>
                            <td class="py-1.5 text-center whitespace-nowrap">{{ number_format($item->quantity, 2, ',', '.') }} {{ $item->unit }}</td>
                            <td class="py-1.5 text-right whitespace-nowrap">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="py-1.5 text-right font-semibold whitespace-nowrap">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Rincian Total & Pembayaran -->
        <div class="py-3 border-b border-dashed border-gray-300 space-y-1.5 text-[11px]">
            <div class="flex justify-between">
                <span class="text-gray-500">Subtotal</span>
                <span>Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</span>
            </div>
            @if((float) $sale->discount_amount > 0)
                <div class="flex justify-between text-red-600">
                    <span>Diskon</span>
                    <span>- Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="flex justify-between text-sm font-bold pt-1 border-t border-gray-200 text-gray-900">
                <span>TOTAL</span>
                <span>Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between pt-1">
                <span class="text-gray-500">Bayar (Tunai/DP)</span>
                <span>Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</span>
            </div>
            @if((float) $sale->change_amount > 0)
                <div class="flex justify-between font-semibold text-green-700">
                    <span>Kembalian</span>
                    <span>Rp {{ number_format($sale->change_amount, 0, ',', '.') }}</span>
                </div>
            @endif
            @if((float) $sale->remaining_debt > 0)
                <div class="flex justify-between font-bold text-amber-700 bg-amber-50 p-1.5 rounded">
                    <span>Sisa Piutang</span>
                    <span>Rp {{ number_format($sale->remaining_debt, 0, ',', '.') }}</span>
                </div>
                @if($sale->due_date)
                    <div class="flex justify-between text-[10px] text-gray-500 px-1.5">
                        <span>Jatuh Tempo:</span>
                        <span>{{ $sale->due_date->translatedFormat('d F Y') }}</span>
                    </div>
                @endif
            @endif
        </div>

        <!-- Riwayat Pembayaran Piutang (Jika Ada) -->
        @if($sale->debtPayments->count() > 0)
            <div class="py-2 border-b border-dashed border-gray-300 text-[10px]">
                <p class="font-bold text-gray-600 mb-1">Riwayat Cicilan Piutang:</p>
                @foreach($sale->debtPayments as $pay)
                    <div class="flex justify-between text-gray-500">
                        <span>{{ $pay->payment_date->translatedFormat('d/m/Y') }}</span>
                        <span>+ Rp {{ number_format($pay->amount, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Footer Struk -->
        <div class="pt-4 text-center text-[10px] text-gray-500 space-y-1">
            <p>{{ $settings->receipt_footer }}</p>
            <p class="text-[9px] text-gray-400">--- Simpan struk ini sebagai bukti transaksi ---</p>
        </div>
    </div>
</body>
</html>
