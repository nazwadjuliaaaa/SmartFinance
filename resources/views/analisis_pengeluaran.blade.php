@extends('layouts.app')

@section('title', 'Analisis Pengeluaran - Sistem Informasi Akuntansi')

@section('content')
    <h2 class="page-title">Transaksi Pengeluaran</h2>
        
    <div style="margin-bottom: 20px;">
        <a href="{{ route('finance.cash-out.index') }}" style="margin-right: 20px; color: #94a3b8; text-decoration: none;">Kas Keluar</a>
        <a href="{{ route('finance.cash-out.analysis') }}" style="font-weight: bold; color: #1e293b; text-decoration: underline;">Analisis Pengeluaran</a>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
        <div class="stat-card">
            <div class="stat-title">Total Pengeluaran</div>
            <div class="stat-value">Rp{{ number_format($totalExpense, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Kerugian</div>
            <div class="stat-value">Rp{{ number_format($totalExpense * 0.4, 0, ',', '.') }}</div>
        </div>
    </div>

    <div style="display: flex; gap: 20px;">
        <div class="chart-container" style="flex: 2;">
            <h3 style="text-align: center; margin-bottom: 20px;">Tren Pengeluaran</h3>
            <canvas id="trendChart"></canvas>
        </div>
        <div class="chart-container" style="flex: 1;">
            <h3 style="text-align: center; margin-bottom: 20px;">Top 5 Barang Dibeli</h3>
            <canvas id="topProductsChart"></canvas>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    // Trend Chart
    const ctx = document.getElementById('trendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($months),
            datasets: [{
                label: 'Pengeluaran',
                data: @json($expenseData),
                borderColor: '#fb923c',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } }
        }
    });

    // Top Products Chart
    const ctx2 = document.getElementById('topProductsChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: @json($topExpenseLabels),
            datasets: [{
                data: @json($topExpenseData),
                backgroundColor: ['#1e3a8a', '#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe']
            }]
        },
        options: {
            responsive: true,
            cutout: '70%',
            plugins: { legend: { display: false } }
        }
    });
</script>
@endsection
