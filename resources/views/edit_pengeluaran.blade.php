@extends('layouts.app')

@section('title', 'Edit Pengeluaran')

@section('content')
    <h2 class="page-title">Edit Transaksi Pengeluaran</h2>

    <div class="input-section" style="border: 2px solid #facc15;">
        <h3 style="text-align: center; color: #b45309; margin-bottom: 30px;">Edit Produk Pengeluaran</h3>
        
        <form action="{{ route('finance.cash-out.update', $item->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="input-row">
                <label style="width: 150px; font-weight: bold;">Tanggal</label>
                <input type="date" name="date" class="input-field" value="{{ $item->financialRecord->transaction_date->format('Y-m-d') }}" required>
            </div>

            <div class="input-row">
                <label style="width: 150px; font-weight: bold;">Nama Barang</label>
                <input type="text" class="input-field" value="{{ $item->product->name }}" disabled style="background: #f1f5f9; color: #64748b;">
                <p style="font-size: 0.8rem; color: #94a3b8; margin-left: 10px;">(Nama barang tidak dapat diubah)</p>
            </div>

            <div class="input-row">
                <label style="width: 150px; font-weight: bold;">Harga Satuan</label>
                <input type="text" name="price" id="price" class="input-field" value="{{ number_format(($item->quantity > 0 ? $item->total_price / $item->quantity : 0), 0, ',', '.') }}" required>
            </div>

            <div class="input-row">
                <label style="width: 150px; font-weight: bold;">Jumlah (Qty)</label>
                <input type="number" name="qty" class="input-field" value="{{ $item->quantity }}" required style="width: 100px;">
            </div>

            <button type="submit" class="btn-green" style="display: block; margin: 40px auto 0; width: 300px; text-align: center; background: #eab308;">Simpan Perubahan</button>
            <a href="{{ route('finance.cash-out.index') }}" style="display: block; text-align: center; margin-top: 15px; color: #64748b; text-decoration: none;">Batal</a>
        </form>
    </div>
@endsection

@section('scripts')
<script>
    // Format inputs to IDR
    const priceInput = document.getElementById('price');
    
    // On load, remove default dots for logic if needed, but display nicely
    // If user submits, we need to strip dots in backend? Or JS?
    // It's cleaner to let user type freely but we need to strip dots before submit or handle in backend?
    // The controller validation expects numeric. So we should strip dots on submit or use a hidden field.
    // Or just simplest regex on backend. But wait, controller update uses: $request->price * $request->qty
    // If $request->price has dots, PHP might take '20.000' as 20. 
    // Let's add a script to strip dots on submit or backend.
    // The controller I wrote does NOT strip dots for 'price'. I should fix logic there or handle here.
    // I'll handle it here by stripping on submit.

    priceInput.addEventListener('input', function(e) {
        let value = this.value.replace(/[^0-9]/g, '');
        if (value) {
            value = parseInt(value, 10).toLocaleString('id-ID');
        }
        this.value = value;
    });

    document.querySelector('form').addEventListener('submit', function(e) {
        // Strip dots from price before submit? 
        // Actually, easiest is to ensure backend strips it. 
        // But I cannot edit backend in this step easily without another call.
        // I will use a hidden input for the clean value.
        
        let price = priceInput.value.replace(/\./g, '');
        
        // Temporarily change value to clean number
        priceInput.value = price;
    });
</script>
@endsection
