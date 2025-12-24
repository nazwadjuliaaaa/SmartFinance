@extends('layouts.app')

@section('title', 'Tambah Pengeluaran - Sistem Informasi Akuntansi')

@section('content')
    <h2 class="page-title">Transaksi Pembelian</h2>
    
    <div style="margin-bottom: 20px;">
        <a href="{{ route('finance.cash-out.index') }}" style="margin-right: 20px; font-weight: bold; color: #1e293b; text-decoration: underline;">Kas Keluar</a>
        <a href="{{ route('finance.cash-out.analysis') }}" style="color: #94a3b8; text-decoration: none;">Analisis Pembelian</a>
    </div>

    <div class="input-section" style="border: 2px solid #3b82f6;">
        <h3 style="text-align: center; color: var(--primary-color); margin-bottom: 30px;">Tambah Pengeluaran</h3>
        
        <form action="{{ route('finance.cash-out.store') }}" method="POST" id="cashOutForm">
            @csrf
            
            <div class="input-row">
                <label style="width: 150px; font-weight: bold;">Tanggal</label>
                <input type="date" name="date" class="input-field" required>
            </div>

            <!-- Itemized Purchase Section -->
            <div class="input-row" style="flex-direction: column; align-items: flex-start;">
                <label style="font-weight: bold; margin-bottom: 15px; color: var(--primary-color); border-bottom: 2px solid #eee; width: 100%; padding-bottom: 5px;">Pembelian Barang</label>
                
                <!-- Input Controls -->
                <div style="display: flex; gap: 10px; width: 100%; margin-bottom: 10px; flex-wrap: wrap;">
                    <input type="text" id="item_name" class="input-field" placeholder="Nama Barang" style="flex: 2;">
                    <input type="text" id="item_price" class="input-field" placeholder="Harga Satuan (Rp)" style="flex: 1;">
                    <select id="item_unit" class="input-field" style="width: 120px;">
                        <option value="">Satuan</option>
                        <option value="Pcs">Pcs</option>
                        <option value="Kg">Kg</option>
                        <option value="Liter">Liter</option>
                        <option value="Pack">Pack</option>
                        <option value="Dus">Dus</option>
                        <option value="Lusin">Lusin</option>
                        <option value="Meter">Meter</option>
                        <option value="Unit">Unit</option>
                        <option value="Pasang">Pasang</option>
                        <option value="Lembar">Lembar</option>
                    </select>
                    <input type="number" id="item_qty" class="input-field" placeholder="Jml" style="width: 80px;">
                    <button type="button" class="btn-add" onclick="addItem()">Tambah</button>
                </div>

                <div id="items_list" style="width: 100%; margin-top: 10px;"></div>
                <input type="hidden" name="items_json" id="items_json">
            </div>

            <div style="margin-top: 20px; text-align: right; font-weight: bold; font-size: 1.1rem; color: var(--primary-color);">
                Total Pengeluaran: <span id="total_display">Rp0</span>
            </div>

            <button type="submit" class="btn-green" style="display: block; margin: 40px auto 0; width: 300px; text-align: center;">Submit</button>
        </form>
    </div>
@endsection

@section('scripts')
<script>
    let items = [];

    // Format IDR Input
    document.getElementById('item_price').addEventListener('input', function(e) {
        let value = this.value.replace(/[^0-9]/g, '');
        if (value) {
            value = parseInt(value, 10).toLocaleString('id-ID');
        }
        this.value = value;
    });

    function addItem() {
        const nameInput = document.getElementById('item_name');
        const priceInput = document.getElementById('item_price');
        const unitInput = document.getElementById('item_unit');
        const qtyInput = document.getElementById('item_qty');

        const name = nameInput.value;
        const priceStr = priceInput.value; // IDR Formatted
        const unit = unitInput.value;
        const qty = qtyInput.value;

        if(name && priceStr && unit && qty > 0) {
            const price = parseInt(priceStr.replace(/\./g, ''));
            const total = price * qty;
            
            items.push({
                name: name,
                price: price,
                unit: unit,
                qty: parseFloat(qty),
                total: total
            });

            renderItems();
            
            // Reset inputs
            nameInput.value = '';
            priceInput.value = '';
            unitInput.value = '';
            qtyInput.value = '';
            nameInput.focus();
        } else {
            alert('Mohon lengkapi semua data barang!');
        }
    }

    function renderItems() {
        const list = document.getElementById('items_list');
        list.innerHTML = items.map((item, index) => `
            <div style="background: #f8fafc; padding: 10px; margin-bottom: 5px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0;">
                <div style="flex: 1;">
                    <div style="font-weight: bold; color: #1e293b;">${item.name}</div>
                    <div style="font-size: 0.9rem; color: #64748b;">
                        ${item.qty} ${item.unit} x Rp${new Intl.NumberFormat('id-ID').format(item.price)}
                    </div>
                </div>
                <div style="font-weight: bold; color: #0f172a; margin-right: 15px;">
                    Rp${new Intl.NumberFormat('id-ID').format(item.total)}
                </div>
                <button type="button" onclick="removeItem(${index})" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 1.2rem;">&times;</button>
            </div>
        `).join('');

        document.getElementById('items_json').value = JSON.stringify(items);
        updateTotal();
    }

    function removeItem(index) {
        items.splice(index, 1);
        renderItems();
    }

    function updateTotal() {
        const total = items.reduce((sum, item) => sum + item.total, 0);
        document.getElementById('total_display').innerText = 'Rp' + new Intl.NumberFormat('id-ID').format(total);
    }
</script>
@endsection
