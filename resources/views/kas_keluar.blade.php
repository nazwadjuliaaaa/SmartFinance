@extends('layouts.app')

@section('title', 'Kas Keluar - Sistem Informasi Akuntansi')

@section('content')
    <h2 class="page-title">Transaksi Pengeluaran</h2>
    
    <div style="margin-bottom: 20px;">
        <a href="{{ route('finance.cash-out.index') }}" style="margin-right: 20px; font-weight: bold; color: #1e293b; text-decoration: underline;">Kas Keluar</a>
        <a href="{{ route('finance.cash-out.analysis') }}" style="color: #94a3b8; text-decoration: none;">Analisis Pengeluaran</a>
    </div>

    <a href="{{ route('finance.cash-out.create') }}" class="btn-green" style="margin-bottom: 20px; padding: 10px 20px; display:inline-block;">Tambah Pengeluaran</a>

    <div class="recap-container" style="max-width: 100%;">
        <div class="table-header" style="grid-template-columns: 0.5fr 1.2fr 2fr 1.5fr 0.8fr 1fr 1.5fr;">
            <div>No</div>
            <div>Tanggal</div>
            <div>Nama Barang</div>
            <div>Harga Satuan</div>
            <div>Jumlah</div>
            <div>Satuan</div>
            <div>Total</div>
        </div>
        
        @foreach($items as $index => $item)
            @php
                $rawName = $item->product->name ?? 'Unknown';
                $name = $rawName;
                $unit = '-';
                if (preg_match('/^(.*)\s\((.*)\)$/', $rawName, $matches)) {
                    $name = $matches[1];
                    $unit = $matches[2];
                }
                $pricePerUnit = $item->quantity > 0 ? $item->total_price / $item->quantity : 0;
            @endphp
        <div class="table-row" style="grid-template-columns: 0.5fr 1.2fr 2fr 1.5fr 0.8fr 1fr 1.5fr;">
            <div>{{ $index + 1 }}</div>
            <div>{{ $item->financialRecord->transaction_date->format('d/m/Y') }}</div>
            <div>{{ $name }}</div>
            <div>Rp{{ number_format($pricePerUnit, 0, ',', '.') }}</div>
            <div>{{ $item->quantity }}</div>
            <div>{{ $unit }}</div>
            <div>Rp{{ number_format($item->total_price, 0, ',', '.') }}</div>
        </div>
        @endforeach

        <div style="margin-top: 20px; text-align: right; font-weight: bold;">
            Total Pengeluaran Keseluruhan : Rp{{ number_format($items->sum('total_price'), 0, ',', '.') }}
        </div>
    </div>
@endsection
