@extends('layouts.app')

@section('title', 'Laporan Keuangan - Laba Rugi')

@section('content')
    <div class="page-header">
        <h2 style="margin: 0;">Laporan Keuangan</h2>
        <p style="font-size: 1rem; color: #64748b; font-weight: normal; margin-top: 5px;">Analisis Laba Rugi usaha Anda.</p>
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

    <!-- 2. Profit & Loss Content -->
    <h3 style="color: var(--primary-color); margin-bottom: 20px;">Analisis Laba Rugi</h3>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        
        <!-- Table Side -->
        <div>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 15px 0; color: #64748b;">Pendapatan Kotor (Omzet)</td>
                    <td style="padding: 15px 0; text-align: right; font-weight: bold; color: #1e293b;">Rp{{ number_format($grossRevenue, 0, ',', '.') }}</td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 15px 0; color: #ef4444;">(-) HPP & Biaya Operasional</td>
                    <td style="padding: 15px 0; text-align: right; font-weight: bold; color: #ef4444;">(Rp{{ number_format($cogs, 0, ',', '.') }})</td>
                </tr>
                <tr style="background-color: #f8fafc;">
                    <td style="padding: 15px 10px; font-weight: 800; color: var(--primary-color);">Laba Bersih</td>
                    <td style="padding: 15px 10px; text-align: right; font-weight: 800; color: #10b981; font-size: 1.2rem;">Rp{{ number_format($netProfit, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <!-- Chart Side placeholder -->
        <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; border-left: 2px solid #f1f5f9;">
            <div style="color: #64748b; margin-bottom: 10px;">Margin Keuntungan Bersih</div>
            <div style="font-size: 3rem; font-weight: 800; color: var(--primary-color);">
                {{ $grossRevenue > 0 ? number_format(($netProfit / $grossRevenue) * 100, 1) : 0 }}%
            </div>
            <p style="font-size: 0.9rem; color: #94a3b8; max-width: 200px;">Dari total omzet yang Anda dapatkan.</p>
        </div>
    </div>
@endsection
