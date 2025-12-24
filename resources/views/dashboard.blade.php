@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h2 class="page-title">Dashboard</h2>
    <p class="welcome-text">Halo, {{ Auth::user()->name ?? 'User' }}!</p>

    <div class="stats-grid">
        @php
            // Values are passed from controller
        @endphp

        <div class="stat-card">
            <div class="stat-title">Modal Awal</div>
            <div class="stat-value">Rp{{ number_format($initial->starting_capital ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card active">
            <div class="stat-title">Omzet</div>
            <div class="stat-value">Rp{{ number_format($omzet, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Kas Masuk</div>
            <div class="stat-value">Rp{{ number_format($in, 0, ',', '.') }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Kas Keluar</div>
            <div class="stat-value">Rp{{ number_format($out, 0, ',', '.') }}</div>
        </div>
    </div>

    <div style="display: flex; gap: 20px;">
        <div class="chart-container" style="flex: 2;">
            <h3 style="text-align: center; margin-bottom: 20px;">Analisis Penjualan</h3>
            <canvas id="salesChart"></canvas>
        </div>
        <div class="chart-container" style="flex: 1;">
            <h3 style="text-align: center; margin-bottom: 20px;">Pencapaian Hari Ini</h3>
            <div style="text-align: center; margin-bottom: 10px; font-weight: bold; color: var(--primary-color);">
                Rp{{ number_format($todayRevenue, 0, ',', '.') }} / Rp{{ number_format($dailyTarget, 0, ',', '.') }}
            </div>
            <canvas id="doughnutChart"></canvas>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    // Sales Chart (Line)
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($months),
            datasets: [{
                label: 'Pendapatan',
                data: @json($salesData),
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
            plugins: {
                legend: { position: 'top' }
            }
        }
    });

    // Insight Chart (Doughnut)
    const ctx2 = document.getElementById('doughnutChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Tercapai', 'Belum Tercapai'],
            datasets: [{
                data: [@json($achievementPct), @json($remainingPct)],
                backgroundColor: ['#1e3a8a', '#e2e8f0']
            }]
        },
        options: {
            responsive: true,
            cutout: '70%',
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>
@endsection
