<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Data Keuangan Awal - Sistem Informasi Akuntansi</title>
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding-top: 40px;
        }

        .input-group-header {
            font-weight: bold;
            color: #1a202c;
            margin-bottom: 10px;
        }
        .dynamic-list {
            margin-bottom: 15px;
        }
        .dynamic-item {
            background: #f8fafc;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <h2 class="page-title" style="text-align: center;">Informasi Keuangan Awal</h2>
    
    <div class="input-section">
        <h3 style="text-align: center; color: var(--primary-color); margin-bottom: 40px;">Input Data Keuangan Awal</h3>
        
        @if ($errors->any())
            <div style="background-color: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('finance.store') }}" method="POST" id="initialForm">
            @csrf
            
            <!-- Modal Awal -->
            <div class="input-row">
                <div class="section-label">Modal Awal :</div>
                <div style="flex: 1;">
                    <input type="text" name="capital_cash" class="input-field" placeholder="Rp.. (Contoh: 1.000.000)" autocomplete="off" value="{{ isset($initial) ? number_format($initial->starting_capital, 0, ',', '.') : '' }}">
                </div>
            </div>

            <!-- Aset Tetap -->
            <div class="input-row" style="align-items: flex-start;">
                <div class="section-label">Aset Tetap :</div>
                <div style="flex: 1;">
                    <div style="display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;">
                        <input type="text" id="asset_name" class="input-field" placeholder="Nama" style="width: 140px;">
                        <input type="text" id="asset_price" class="input-field" placeholder="Harga Satuan (Rp..)" style="width: 140px;">
                        <input type="number" id="asset_qty" class="input-field" placeholder="Jml" style="width: 60px;">
                    </div>
                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <input type="date" id="asset_date" class="input-field" placeholder="Tanggal Pembelian">
                        <input type="number" id="asset_life" class="input-field" placeholder="Umur (Thn)" style="width: 100px;">
                        <button type="button" class="btn-add" onclick="addAsset()">Tambah</button>
                    </div>
                    <div id="assets_list" class="dynamic-list"></div>
                    <input type="hidden" name="assets_json" id="assets_json" value="[]">
                </div>
            </div>

            <!-- Barang yang Dijual -->
            <div class="input-row">
                <div class="section-label">Barang yang Dijual :</div>
                <div style="flex: 1;">
                    <div style="display: flex; gap: 10px; margin-bottom: 5px;">
                        <input type="text" id="product_name" class="input-field" placeholder="Nama Barang (misal: Dimsum)" style="flex: 2;">
                        <input type="text" id="product_price" class="input-field" placeholder="Harga Jual (Rp..)" style="flex: 1;">
                        <button type="button" class="btn-add" onclick="addProduct()">Tambah Barang</button>
                    </div>
                    <p style="font-size: 0.8rem; color: #666; margin-top: 5px; margin-bottom: 10px;">
                        *Tambahkan barang dan harga jualnya terlebih dahulu, lalu tambahkan detail bahan baku.
                    </p>
                    <div id="products_list" class="dynamic-list" style="margin-top: 10px;"></div>
                    <input type="hidden" name="materials_json" id="materials_json" value="[]">
                </div>
            </div>

            <!-- Kewajiban -->
            <div class="input-row">
                <div class="section-label">Kewajiban Awal(Opsional) :</div>
                <input type="text" name="liabilities" class="input-field" placeholder="Rp.." value="{{ isset($initial) ? number_format($initial->initial_liabilities, 0, ',', '.') : '' }}">
            </div>

            <button type="submit" class="btn-submit">REKAP DATA</button>
        </form>
    </div>

    <script>
        // Init data from server if editing
        let assets = @json($initial->fixed_assets ?? []);
        let products = @json($initial->raw_materials ?? []);

        // Ensure initialization after DOM load
        window.addEventListener('load', function() {
            if(assets.length > 0) renderAssets();
            if(products.length > 0) renderProducts();
        });

        // Format IDR Input
        document.querySelectorAll('input[name="capital_cash"], input[name="liabilities"], #asset_price, #product_price').forEach(input => {
            input.type = 'text'; 
            input.addEventListener('input', function(e) {
                let value = this.value.replace(/[^0-9]/g, '');
                if (value) {
                    value = parseInt(value, 10).toLocaleString('id-ID');
                }
                this.value = value;
            });
        });

        function addAsset() {
            const name = document.getElementById('asset_name').value;
            let price = document.getElementById('asset_price').value;
            const date = document.getElementById('asset_date').value;
            const life = document.getElementById('asset_life').value;
            const qty = document.getElementById('asset_qty').value;

            // Remove dots for storage
            let priceClean = price.replace(/\./g, '');

            if(name && priceClean && qty) {
                assets.push({name, price: priceClean, date, life, qty});
                renderAssets();
                document.getElementById('asset_name').value = '';
                document.getElementById('asset_price').value = '';
                document.getElementById('asset_date').value = '';
                document.getElementById('asset_life').value = '';
                document.getElementById('asset_qty').value = '';
            } else {
                alert('Nama, Harga, dan Jumlah wajib diisi!');
            }
        }

        function renderAssets() {
            const list = document.getElementById('assets_list');
            list.innerHTML = assets.map((a, i) => `
                <div class="dynamic-item">
                    <span>${a.name} (Qty: ${a.qty}) - Rp${parseInt(a.price).toLocaleString('id-ID')}</span>
                    <span style="color: red; cursor: pointer;" onclick="removeAsset(${i})">x</span>
                </div>
            `).join('');
            document.getElementById('assets_json').value = JSON.stringify(assets);
        }

        function removeAsset(index) {
            assets.splice(index, 1);
            renderAssets();
        }

        // --- Goods & Ingredients Logic ---

        function addProduct() {
            const name = document.getElementById('product_name').value;
            let price = document.getElementById('product_price').value;
            
            // Allow price to be empty (0) but better to ask. Assuming optional or required? 
            // User asked "tambahkan harga jual", implies it's needed.
            
            if(name && price) {
                let priceClean = price.replace(/\./g, '');
                
                // Check if product already exists
                if(products.some(p => p.name === name)) {
                    alert('Barang sudah ada!');
                    return;
                }
                products.push({name: name, price: priceClean, ingredients: []});
                renderProducts();
                document.getElementById('product_name').value = '';
                document.getElementById('product_price').value = '';
            } else {
                alert('Nama Barang dan Harga Jual wajib diisi!');
            }
        }

        function addIngredient(productIndex) {
            const nameInput = document.getElementById(`ing_name_${productIndex}`);
            const priceInput = document.getElementById(`ing_price_${productIndex}`);
            const unitInput = document.getElementById(`ing_unit_${productIndex}`);

            const name = nameInput.value;
            let price = priceInput.value;
            const unit = unitInput.value;

            if(name && price && unit) {
                let priceClean = price.replace(/\./g, '');
                products[productIndex].ingredients.push({
                    name: name,
                    price: priceClean,
                    unit: unit
                });
                renderProducts();
            } else {
                alert('Nama, Harga, dan Satuan bahan baku wajib diisi!');
            }
        }

        function removeIngredient(productIndex, ingredientIndex) {
            products[productIndex].ingredients.splice(ingredientIndex, 1);
            renderProducts();
        }

        function removeProduct(index) {
            products.splice(index, 1);
            renderProducts();
        }

        function formatIDR(num) {
            return parseInt(num).toLocaleString('id-ID');
        }

        // Helper to attach IDR formatter to dynamic inputs
        function attachIDRFormatter(inputId) {
            const input = document.getElementById(inputId);
            if(input) {
                input.addEventListener('input', function(e) {
                    let value = this.value.replace(/[^0-9]/g, '');
                    if (value) {
                        this.value = parseInt(value, 10).toLocaleString('id-ID');
                    } else {
                        this.value = '';
                    }
                });
            }
        }

        function renderProducts() {
            const list = document.getElementById('products_list');
            list.innerHTML = products.map((p, i) => `
                <div class="dynamic-item" style="flex-direction: column; align-items: flex-start; background: #fff; border: 1px solid #ddd;">
                    <div style="display: flex; justify-content: space-between; width: 100%; font-weight: bold; padding-bottom: 5px; border-bottom: 1px solid #eee; margin-bottom: 5px;">
                        <span>${p.name} - Rp${formatIDR(p.price)}</span>
                        <span style="color: red; cursor: pointer;" onclick="removeProduct(${i})">Hapus Barang</span>
                    </div>
                    
                    <div style="width: 100%; margin-bottom: 8px;">
                        <input type="text" id="ing_name_${i}" placeholder="Nama Bahan.." style="width: 30%; padding: 4px; border: 1px solid #ccc; font-size: 0.9em;">
                        <input type="text" id="ing_price_${i}" placeholder="Harga (Rp).." style="width: 30%; padding: 4px; border: 1px solid #ccc; font-size: 0.9em;">
                        <select id="ing_unit_${i}" style="width: 20%; padding: 4px; border: 1px solid #ccc; font-size: 0.9em;">
                            <option value="">Satuan</option>
                            <option value="Pcs">Pcs</option>
                            <option value="Liter">Liter</option>
                            <option value="Pack">Pack</option>
                            <option value="Kg">Kg</option>
                            <option value="Gram">Gram</option>
                            <option value="Meter">Meter</option>
                        </select>
                        <button type="button" onclick="addIngredient(${i})" style="padding: 4px 8px; background: #4a5568; color: white; border: none; cursor: pointer; font-size: 0.9em;">+</button>
                    </div>

                    <div style="width: 100%; font-size: 0.9em; color: #555;">
                        ${p.ingredients.length > 0 ? '' : '<i style="color: #999; font-size: 0.8em;">Belum ada bahan baku</i>'}
                        <div style="display: flex; flex-direction: column; gap: 3px;">
                            ${p.ingredients.map((ing, j) => `
                                <div style="background: #e2e8f0; padding: 2px 8px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; font-size: 0.85em;">
                                    <span>${ing.name} (@ Rp${formatIDR(ing.price)} / ${ing.unit})</span>
                                    <span style="color: #e53e3e; cursor: pointer; font-weight: bold;" onclick="removeIngredient(${i}, ${j})">&times;</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `).join('');
            
            // Re-attach listeners for new dynamic inputs
            products.forEach((p, i) => {
                attachIDRFormatter(`ing_price_${i}`);
            });

            document.getElementById('materials_json').value = JSON.stringify(products);
        }
    </script>
</body>
</html>
