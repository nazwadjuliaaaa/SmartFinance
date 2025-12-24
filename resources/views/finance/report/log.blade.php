@extends('layouts.app')

@section('title', 'Laporan Keuangan - Log Transaksi')

@section('content')
    <div class="page-header">
        <h2 style="margin: 0;">Laporan Keuangan</h2>
        <p style="font-size: 1rem; color: #64748b; font-weight: normal; margin-top: 5px;">Riwayat lengkap transaksi masuk dan keluar.</p>
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

    <!-- 3. Transaction Log Content -->
    <h3 style="color: var(--primary-color); margin-bottom: 20px;">Log Transaksi Terakhir</h3>
    <div style="background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: #f8fafc;">
                <tr>
                    <th style="padding: 15px; text-align: left; color: #64748b; font-size: 0.85rem;">Tanggal</th>
                    <th style="padding: 15px; text-align: left; color: #64748b; font-size: 0.85rem;">Keterangan</th>
                    <th style="padding: 15px; text-align: right; color: #64748b; font-size: 0.85rem;">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $t)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 12px 15px; color: #334155;">{{ $t->transaction_date->format('d M Y') }}</td>
                    <td style="padding: 12px 15px;">
                        <span style="background: {{ $t->type == 'in' ? '#dcfce7' : '#fee2e2' }}; color: {{ $t->type == 'in' ? '#166534' : '#991b1b' }}; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; margin-right: 5px;">
                            {{ $t->type == 'in' ? 'MASUK' : 'KELUAR' }}
                        </span>
                        {{ $t->description ?? '-' }}
                    </td>
                    <td style="padding: 12px 15px; text-align: right; font-weight: bold; color: {{ $t->type == 'in' ? '#10b981' : '#ef4444' }};">
                        {{ $t->type == 'in' ? '+' : '-' }}Rp{{ number_format($t->amount, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" style="padding: 20px; text-align: center; color: #94a3b8;">Belum ada transaksi</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div style="padding: 10px;">
            {{ $transactions->links() }}
        </div>
    </div>
@endsection
