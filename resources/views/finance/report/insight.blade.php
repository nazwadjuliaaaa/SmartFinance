@extends('layouts.app')

@section('title', 'Laporan Keuangan - Insight')

@section('content')
    <div class="page-header">
        <h2 style="margin: 0;">Laporan Keuangan</h2>
        <p style="font-size: 1rem; color: #64748b; font-weight: normal; margin-top: 5px;">Insight performa penjualan bisnis Anda.</p>
    </div>

    <!-- Navigation Tabs -->
    <style>
        .report-tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0;
        }
        .tab-link {
            text-decoration: none;
            color: #64748b;
            padding: 10px 20px;
            font-weight: 600;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .tab-link:hover {
            color: var(--primary-color);
        }
        .tab-link.active {
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
        }
    </style>
    <div class="report-tabs">
        <a href="{{ route('finance.recap') }}" class="tab-link {{ request()->routeIs('finance.recap') ? 'active' : '' }}">Ringkasan</a>
        <a href="{{ route('finance.report.pnl') }}" class="tab-link {{ request()->routeIs('finance.report.pnl') ? 'active' : '' }}">Analisis Laba Rugi</a>
        <a href="{{ route('finance.report.log') }}" class="tab-link {{ request()->routeIs('finance.report.log') ? 'active' : '' }}">Log Transaksi</a>
        <a href="{{ route('finance.report.insight') }}" class="tab-link {{ request()->routeIs('finance.report.insight') ? 'active' : '' }}">Insight Penjualan</a>
    </div>

    <!-- 4. Insights Content -->
    <h3 style="color: var(--primary-color); margin-bottom: 20px;">Insight Penjualan</h3>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        
        <!-- Top Products -->
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h4 style="margin-top: 0; margin-bottom: 15px; color: #334155; font-size: 1rem;">🏆 Produk Terlaris</h4>
            <ul style="list-style: none; padding: 0; margin: 0;">
                @foreach($topProducts as $index => $prod)
                <li style="display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 5px;">
                    <span style="color: #475569;">{{ $index + 1 }}. {{ $prod->product->name ?? 'Unknown' }}</span>
                    <span style="font-weight: bold; color: #1e293b;">{{ $prod->total_qty }} Terjual</span>
                </li>
                @endforeach
                @if($topProducts->isEmpty())
                <li style="color: #94a3b8; text-align: center; padding: 10px;">Belum ada penjualan produk.</li>
                @endif
            </ul>
        </div>

        <!-- Busiest Day -->
        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); text-align: center;">
            <h4 style="margin-top: 0; margin-bottom: 15px; color: #334155; font-size: 1rem;">📅 Hari Tersibuk</h4>
            @if($busiestDays->count() > 0)
                <div style="margin-top: 20px;">
                    <span style="font-size: 3rem; font-weight: 800; color: var(--primary-color); display: block;">{{ $busiestDays->first()->day_name }}</span>
                    <span style="color: #64748b; font-size: 0.9rem;">{{ $busiestDays->first()->count }} Transaksi</span>
                </div>
            @else
                <p style="color: #94a3b8; margin-top: 20px;">Belum cukup data</p>
            @endif
        </div>
    </div>

    <!-- AI Strategy Section -->
    <div style="margin-top: 30px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 25px; border-radius: 12px; color: white; position: relative; overflow: hidden;">
        <div style="position: relative; z-index: 2;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; color: #facc15; font-size: 1.2rem;">🤖 Analisis Strategi Bisnis (AI)</h3>
                <span style="background: rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; border: 1px solid rgba(255,255,255,0.2);">
                    Based on Net Revenue
                </span>
            </div>

            <div id="strategy-content" style="color: #cbd5e1;">
                <!-- Content will be loaded here by JavaScript -->
            </div>
            
            <div id="strategy-error" style="display: none; color: #f87171; text-align: center; margin-top: 10px;"></div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        fetchStrategy();
    });

    function fetchStrategy() {
        const strategyContent = document.getElementById('strategy-content');
        strategyContent.innerHTML = '<p class="text-center" style="text-align: center;"><em>Sedang menganalisis strategi terbaik untuk Anda...</em></p>';

        fetch('{{ route('ai.analyst.strategy') }}')
            .then(response => response.json())
            .then(data => {
                if(data.error) {
                    strategyContent.innerHTML = `<p style="color:red; text-align: center;">Error: ${data.error}</p>`;
                } else {
                    renderStrategy(data);
                }
            })
            .catch(err => {
                console.error(err);
                strategyContent.innerHTML = '<p style="color:red; text-align: center;">Gagal memuat strategi. Cek koneksi internet.</p>';
            });
    }

    function renderStrategy(data) {
        const strategyContent = document.getElementById('strategy-content');
        
        // Check Status for UI badges/buttons
        let statusBadge = '';
        let buttons = '';
        
        if (data.status === 'accepted') {
            statusBadge = '<span style="background: #22c55e; color: white; padding: 2px 8px; border-radius: 99px; font-size: 0.8rem; margin-left: 10px;">✅ Diterima</span>';
            buttons = '<p style="text-align: center; color: #4ade80; font-weight: bold; margin-top: 15px;">Misi Strategis Sedang Berjalan! 🚀</p>';
        } else {
            statusBadge = '<span style="background: #f59e0b; color: white; padding: 2px 8px; border-radius: 99px; font-size: 0.8rem; margin-left: 10px;">⏳ Saran Baru</span>';
            buttons = `
                <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                    <button onclick="acceptStrategy()" style="background: #22c55e; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: bold;">
                        ✅ Terima Saran
                    </button>
                    <button onclick="regenerateStrategy()" style="background: transparent; border: 1px solid #94a3b8; color: #cbd5e1; padding: 8px 16px; border-radius: 6px; cursor: pointer;">
                        🔄 Berikan Rekomendasi Lain
                    </button>
                </div>
            `;
        }

        strategyContent.innerHTML = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px; border-left: 4px solid #4ade80;">
                    <h4 style="margin-top: 0; color: #4ade80; margin-bottom: 10px;">🛡️ Strategi Bertahan ${statusBadge}</h4>
                    <p style="color: #cbd5e1; line-height: 1.6;">${data.defense_strategy}</p>
                </div>
                <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px; border-left: 4px solid #facc15;">
                    <h4 style="margin-top: 0; color: #facc15; margin-bottom: 10px;">🚀 Rekomendasi Ekspansi</h4>
                    <p style="color: #cbd5e1; line-height: 1.6;">${data.expansion_recommendation}</p>
                </div>
            </div>
            ${buttons}
        `;
    }

    function acceptStrategy() {
        if(!confirm('Apakah Anda yakin ingin menerima dan menjalankan strategi ini?')) return;
        
        fetch('{{ route('ai.analyst.accept') }}', { 
            method: 'POST',
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            fetchStrategy(); // Reload to update UI
        });
    }

    function regenerateStrategy() {
        const strategyContent = document.getElementById('strategy-content');
        strategyContent.innerHTML = '<p class="text-center" style="text-align: center;"><em>Sedang mencari ide strategi baru... 🤖</em></p>';

        fetch('{{ route('ai.analyst.regenerate') }}', { 
            method: 'POST',
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if(data.error) {
                strategyContent.innerHTML = `<p style="color:red; text-align: center;">Error: ${data.error}</p>`;
            } else {
                renderStrategy(data);
            }
        });
    }
</script>
@endsection
