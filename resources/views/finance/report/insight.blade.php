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
@endsection
