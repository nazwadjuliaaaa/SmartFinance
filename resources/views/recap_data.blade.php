@extends('layouts.app')

@section('title', 'Laporan Keuangan - Ringkasan')

@section('content')
    <div class="page-header">
        <h2 style="margin: 0;">Laporan Keuangan</h2>
        <p style="font-size: 1rem; color: #64748b; font-weight: normal; margin-top: 5px;">Ringkasan kesehatan bisnis Anda secara keseluruhan.</p>
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
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-bottom: 50px;
        }
        .summary-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e293b 100%);
            color: white;
            border-radius: 20px;
            padding: 35px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: transform 0.3s ease;
        }
        .summary-card:hover {
            transform: translateY(-5px);
        }
        .summary-title {
            font-weight: 700;
            margin-bottom: 15px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 0.85rem;
        }
        .summary-value {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--accent-color);
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
    </style>

    <div class="report-tabs">
        <a href="{{ route('finance.recap') }}" class="tab-link {{ request()->routeIs('finance.recap') ? 'active' : '' }}">Ringkasan</a>
        <a href="{{ route('finance.report.pnl') }}" class="tab-link {{ request()->routeIs('finance.report.pnl') ? 'active' : '' }}">Analisis Laba Rugi</a>
        <a href="{{ route('finance.report.log') }}" class="tab-link {{ request()->routeIs('finance.report.log') ? 'active' : '' }}">Log Transaksi</a>
        <a href="{{ route('finance.report.insight') }}" class="tab-link {{ request()->routeIs('finance.report.insight') ? 'active' : '' }}">Insight Penjualan</a>
    </div>

    <!-- 1. Financial Summary Content -->
    <h3 style="color: var(--primary-color); margin-bottom: 20px;">Ringkasan Finansial</h3>
    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-title">Total Saldo Kas</div>
            <div class="summary-value" style="color: #fbbf24;">Rp{{ number_format($totalBalance, 0, ',', '.') }}</div>
            <div style="font-size: 0.8rem; margin-top: 10px; color: #cbd5e1;">Uang tunai tersedia</div>
        </div>
        <div class="summary-card" style="background: linear-gradient(135deg, #0f172a 0%, #334155 100%);">
            <div class="summary-title">Pertumbuhan (Bulan Ini)</div>
            <div class="summary-value" style="color: {{ $growthPct >= 0 ? '#4ade80' : '#f87171' }};">
                {{ $growthPct >= 0 ? '+' : '' }}{{ number_format($growthPct, 1) }}%
            </div>
            <div style="font-size: 0.8rem; margin-top: 10px; color: #cbd5e1;">Vs Bulan Lalu</div>
        </div>
        <div class="summary-card" style="background: linear-gradient(135deg, #1e293b 0%, #475569 100%);">
            <div class="summary-title">Rataan Penjualan Harian</div>
            <div class="summary-value" style="color: #60a5fa;">Rp{{ number_format($dailyAvg, 0, ',', '.') }}</div>
            <div style="font-size: 0.8rem; margin-top: 10px; color: #cbd5e1;">Performa Harian</div>
        </div>
    </div>
@endsection
