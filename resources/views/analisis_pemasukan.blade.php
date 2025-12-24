@extends('layouts.app')

@section('title', 'Analisis Pemasukan - Sistem Informasi Akuntansi')

@section('content')
    <h2 class="page-title">Transaksi Pemasukan</h2>
    
    <div style="margin-bottom: 20px;">
        <a href="{{ route('finance.cash-in.index') }}" style="margin-right: 20px; color: #94a3b8; text-decoration: none;">Kas Masuk</a>
        <a href="{{ route('finance.cash-in.analysis') }}" style="font-weight: bold; color: #1e293b; text-decoration: underline;">Analisis Pemasukan</a>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
        <div class="stat-card">
            <div class="stat-title">Total Pendapatan</div>
            <div class="stat-value">Rp{{ number_format($totalRevenue, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Keuntungan</div>
            <div class="stat-value">Rp{{ number_format($profit, 0, ',', '.') }}</div>
        </div>
    </div>

    <div style="display: flex; gap: 20px;">
        <div class="chart-container" style="flex: 2;">
            <h3 style="text-align: center; margin-bottom: 20px;">Tren Pendapatan dan Keuntungan</h3>
            <canvas id="trendChart"></canvas>
        </div>
        <div class="chart-container" style="flex: 1;">
            <h3 style="text-align: center; margin-bottom: 20px;">Top 5 Produk Terjual</h3>
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
                label: 'Pendapatan',
                data: @json($revenueData),
                borderColor: '#fb923c',
                tension: 0.4
            }, {
                label: 'Keuntungan',
                data: @json($profitData),
                borderColor: '#6366f1',
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
            labels: @json($topProductLabels),
            datasets: [{
                data: @json($topProductData),
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
