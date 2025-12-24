@extends('layouts.app')

@section('title', 'Tambah Pemasukan - Sistem Informasi Akuntansi')

@section('content')
    <h2 class="page-title">Transaksi Penjualan</h2>
    
    <div style="margin-bottom: 20px;">
        <a href="{{ route('finance.cash-in.index') }}" style="margin-right: 20px; font-weight: bold; color: #1e293b; text-decoration: underline;">Kas Masuk</a>
        <a href="{{ route('finance.cash-in.analysis') }}" style="color: #94a3b8; text-decoration: none;">Analisis Penjualan</a>
    </div>

    <div class="input-section" style="border: 2px solid #3b82f6;">
        <h3 style="text-align: center; color: var(--primary-color); margin-bottom: 30px;">Tambah Pemasukan</h3>
        
        <form action="{{ route('finance.cash-in.store') }}" method="POST" id="cashInForm">
            @csrf
            
            <div class="input-row">
                <label style="width: 150px; font-weight: bold;">Tanggal</label>
                <input type="date" name="date" class="input-field" required>
            </div>

            <div class="input-row">
                <label style="width: 150px; font-weight: bold;">Jumlah(Rp)</label>
                <input type="text" name="amount_cash" class="input-field" placeholder="Tunai" style="margin-right: 20px;">
                <input type="text" name="amount_non_cash" class="input-field" placeholder="Non - Tunai">
            </div>



            <div class="input-row">
                <label style="width: 150px; font-weight: bold;">Total Penjualan</label>
                <select id="product_select" class="input-field" style="margin-right: 10px; width: 220px;">
                    <option value="">Pilih Barang...</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" data-price="{{ $p->price }}">{{ $p->name }}</option>
                    @endforeach
                </select>
                <input type="number" id="qty_input" class="input-field" placeholder="Jumlah" style="width: 100px; margin-right: 10px;">
                <button type="button" class="btn-add" onclick="addItem()">Tambah</button>
            </div>

            <div id="items_list" style="margin-left: 150px; margin-bottom: 20px;"></div>
            <input type="hidden" name="items_json" id="items_json">

            <button type="submit" class="btn-green" style="display: block; margin: 40px auto 0; width: 300px; text-align: center;">Submit</button>
        </form>
    </div>
@endsection

@section('scripts')
<script>
    // Format inputs to IDR
    document.querySelectorAll('input[name="amount_cash"], input[name="amount_non_cash"]').forEach(input => {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value) {
                value = parseInt(value, 10).toLocaleString('id-ID');
            }
            this.value = value;
        });
    });

    let items = [];

    function addItem() {
        const select = document.getElementById('product_select');
        const qtyInput = document.getElementById('qty_input');
        const productId = select.value;
        const productName = select.options[select.selectedIndex].text;
        const price = select.options[select.selectedIndex].dataset.price;
        const qty = qtyInput.value;

        if(productId && qty > 0) {
            const total = price * qty;
            items.push({id: productId, name: productName, qty: qty, total: total});
            renderItems();
            qtyInput.value = '';
            select.value = '';
        }
    }

    function renderItems() {
        const list = document.getElementById('items_list');
        list.innerHTML = items.map((item, index) => `
            <div style="background: #f1f5f9; padding: 5px 10px; margin-bottom: 5px; border-radius: 5px; display: flex; justify-content: space-between; width: 400px;">
                <span>${item.name} x ${item.qty}</span>
                <span>Rp${new Intl.NumberFormat('id-ID').format(item.total)}</span>
                <span style="color: red; cursor: pointer;" onclick="removeItem(${index})">x</span>
            </div>
        `).join('');
        document.getElementById('items_json').value = JSON.stringify(items);
    }

    function removeItem(index) {
        items.splice(index, 1);
        renderItems();
    }
</script>
@endsection
