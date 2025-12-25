@extends('layouts.app')

@section('title', 'Kas Masuk - Sistem Informasi Akuntansi')

@section('content')
    <h2 class="page-title">Transaksi Pemasukan</h2>
    
    <div style="margin-bottom: 20px;">
        <a href="{{ route('finance.cash-in.index') }}" style="margin-right: 20px; font-weight: bold; color: #1e293b; text-decoration: underline;">Kas Masuk</a>
        <a href="{{ route('finance.cash-in.analysis') }}" style="color: #94a3b8; text-decoration: none;">Analisis Pemasukan</a>
    </div>

    <a href="{{ route('finance.cash-in.create') }}" class="btn-green" style="margin-bottom: 20px; padding: 10px 20px; display:inline-block;">Tambah Pemasukan</a>

    <div class="recap-container" style="max-width: 100%;">
        <div class="table-header" style="grid-template-columns: 0.5fr 1.5fr 1.5fr 1.5fr 1.5fr 1fr;">
            <div>No</div>
            <div>Tanggal Transaksi</div>
            <div>Tunai</div>
            <div>Non - Tunai</div>
            <div>Total penjualan</div>
            <div style="text-align: center;">Aksi</div>
        </div>
        
        @foreach($transactions as $index => $t)
        <div class="table-row" style="grid-template-columns: 0.5fr 1.5fr 1.5fr 1.5fr 1.5fr 1fr;">
            <div>{{ $index + 1 }}</div>
            <div>{{ $t->transaction_date->format('d/m/Y') }}</div>
            <div>Rp{{ number_format($t->cash_amount, 0, ',', '.') }}</div>
            <div>Rp{{ number_format($t->non_cash_amount, 0, ',', '.') }}</div>
            <div>Rp{{ number_format($t->amount, 0, ',', '.') }}</div>
            <div style="display: flex; gap: 5px; justify-content: center;">
                <a href="{{ route('finance.cash-in.edit', $t->id) }}" style="text-decoration: none; background: #fbbf24; color: white; padding: 5px 8px; border-radius: 5px; font-size: 0.8rem;">✏️</a>
                
                <form action="{{ route('finance.cash-in.destroy', $t->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" style="background: #ef4444; color: white; border: none; padding: 5px 8px; border-radius: 5px; font-size: 0.8rem; cursor: pointer;">🗑️</button>
                </form>
            </div>
        </div>
        @endforeach

        <div style="margin-top: 20px; text-align: right; font-weight: bold;">
            Total Pendapatan Keseluruhan : Rp{{ number_format($transactions->sum('amount'), 0, ',', '.') }}
        </div>
    </div>
@endsection
