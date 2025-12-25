@extends('layouts.app')

@section('title', 'Edit Pemasukan')

@section('content')
    <h2 class="page-title">Edit Transaksi Penjualan</h2>

    <div class="input-section" style="border: 2px solid #facc15;">
        <h3 style="text-align: center; color: #b45309; margin-bottom: 30px;">Edit Data Pemasukan</h3>
        
        <form action="{{ route('finance.cash-in.update', $record->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="input-row">
                <label style="width: 150px; font-weight: bold;">Tanggal</label>
                <input type="date" name="date" class="input-field" value="{{ $record->transaction_date->format('Y-m-d') }}" required>
            </div>

            <div class="input-row">
                <label style="width: 150px; font-weight: bold;">Jumlah(Rp)</label>
                <input type="text" name="amount_cash" id="amount_cash" class="input-field" placeholder="Tunai" value="{{ number_format($record->cash_amount, 0, ',', '.') }}" style="margin-right: 20px;">
                <input type="text" name="amount_non_cash" id="amount_non_cash" class="input-field" placeholder="Non - Tunai" value="{{ number_format($record->non_cash_amount, 0, ',', '.') }}">
            </div>

            <div style="background: #fffbeb; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; color: #92400e;">
                ℹ️ <strong>Catatan:</strong> Saat ini Anda hanya dapat mengedit total nominal dan tanggal. Untuk mengubah detail barang, silakan hapus transaksi ini dan buat baru.
            </div>

            <button type="submit" class="btn-green" style="display: block; margin: 40px auto 0; width: 300px; text-align: center; background: #eab308;">Simpan Perubahan</button>
            <a href="{{ route('finance.cash-in.index') }}" style="display: block; text-align: center; margin-top: 15px; color: #64748b; text-decoration: none;">Batal</a>
        </form>
    </div>
@endsection

@section('scripts')
<script>
    // Format inputs to IDR
    document.querySelectorAll('#amount_cash, #amount_non_cash').forEach(input => {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value) {
                value = parseInt(value, 10).toLocaleString('id-ID');
            }
            this.value = value;
        });
    });
</script>
@endsection
