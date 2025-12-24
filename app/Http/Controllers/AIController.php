<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AIController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $userMessage = strtolower($request->message);
        
        // Fetch Context Data (Simple Stats)
        $userId = auth()->id();
        $totalRevenue = \App\Models\FinancialRecord::where('user_id', $userId)->where('type', 'in')->sum('amount');
        $totalExpense = \App\Models\FinancialRecord::where('user_id', $userId)->where('type', 'out')->sum('amount');
        $balance = $totalRevenue - $totalExpense;

        // Default Response
        $response = "Maaf, saya tidak mengerti pertanyaan Anda spesifiknya. Coba tanyakan tentang 'analisis', 'tips penjualan', atau 'status keuangan'.";

        // Logic Matching
        if (str_contains($userMessage, 'halo') || str_contains($userMessage, 'hi')) {
            $response = "Halo! Saya SmartAssistant. Ada yang bisa saya bantu untuk menganalisis keuangan Anda hari ini?";
        }
        
        
        elseif (preg_match('/target.*(\d+)+%/', $userMessage, $matches) || str_contains($userMessage, 'target')) {
            $percent = isset($matches[1]) ? intval($matches[1]) : 15; // Default 15% if specific number note found but 'target' mentioned
            $targetRevenue = $totalRevenue * (1 + ($percent / 100));
            $gap = $targetRevenue - $totalRevenue;
            
            $response = "Baik, saya mengerti tujuan Anda. \n\n" .
                        "🎯 **Analisis Target Kenaikan Omzet {$percent}%**:\n" .
                        "- Pendapatan Saat Ini: Rp" . number_format($totalRevenue, 0, ',', '.') . "\n" .
                        "- Target Pendapatan: Rp" . number_format($targetRevenue, 0, ',', '.') . "\n" .
                        "- Selisih yang harus dikejar: **Rp" . number_format($gap, 0, ',', '.') . "**\n\n" .
                        "💡 **Rekomendasi Strategis AI**:\n" .
                        "1. **Tingkatkan Frekuensi Transaksi**: Jika rata-rata transaksi harian Anda saat ini " . ($totalRevenue > 0 ? "stabil" : "rendah") . ", cobalah program loyalitas pelanggan.\n" .
                        "2. **Optimasi Margin**: Cek kembali harga pokok penjualan di menu 'Kas Keluar'. Penekanan biaya 5% saja bisa membantu pencapaian target.\n" .
                        "3. **Promosi Terarah**: Fokus pada produk dengan margin tertinggi Anda (misal: Apartemen Cilibende) untuk menutupi gap Rp" . number_format($gap, 0, ',', '.') . " ini.";
        }

        elseif (str_contains($userMessage, 'saran') || str_contains($userMessage, 'rekomendasi') || str_contains($userMessage, 'strategi')) {
            $ratio = $totalRevenue > 0 ? ($totalExpense / $totalRevenue) * 100 : 0;
            
            $response = "🤖 **Analisis Cerdas SmartFinance**:\n";
            
            if ($ratio > 70) {
                $response .= "⚠️ **Peringatan Efisiensi**: Pengeluaran Anda mencapai " . number_format($ratio, 1) . "% dari pendapatan. Ini cukup berisiko.\n" .
                             "✅ **Saran**: Lakukan audit pengeluaran non-esensial dan negosiasi ulang dengan supplier.";
            } elseif ($ratio < 50 && $totalRevenue > 0) {
                $response .= "✅ **Kinerja Sangat Baik**: Profit margin Anda sehat (>50%).\n" .
                             "🚀 **Saran**: Ini saat yang tepat untuk ekspansi! Pertimbangkan menambah varian produk baru atau investasi aset.";
            } else {
                $response .= "ℹ️ **Kinerja Stabil**: Keuangan Anda 'on-track'.\n" .
                             "💡 **Saran**: Fokus pada retensi pelanggan agar pendapatan tetap konsisten.";
            }
        }
        
        elseif (str_contains($userMessage, 'penjualan') || str_contains($userMessage, 'naik')) {
            $response = "Untuk meningkatkan penjualan, Anda bisa mencoba:\n1. Analisis produk terlaris di menu 'Analisis Pemasukan'.\n2. Buat promosi bundling untuk produk yang kurang laku.\n3. Perluas pemasaran digital.\n\nSaat ini total pendapatan Anda tercatat: Rp" . number_format($totalRevenue, 0, ',', '.');
        }

        elseif (str_contains($userMessage, 'rugi') || str_contains($userMessage, 'biaya') || str_contains($userMessage, 'pengeluaran')) {
            $response = "Jika pengeluaran terlalu tinggi (Saat ini: Rp" . number_format($totalExpense, 0, ',', '.') . "), coba evaluasi biaya operasional atau cari supplier bahan baku yang lebih murah. Cek detailnya di menu 'Analisis Pengeluaran'.";
        }

        elseif (str_contains($userMessage, 'untung') || str_contains($userMessage, 'laba') || str_contains($userMessage, 'profit')) {
            if ($balance > 0) {
                $response = "Kabar baik! Posisi keuangan Anda saat ini Surplus (Untung) sebesar Rp" . number_format($balance, 0, ',', '.') . ". Pertahankan kinerja ini!";
            } else {
                $response = "Perhatian, saat ini Anda mengalami Defisit (Rugi) sebesar Rp" . number_format(abs($balance), 0, ',', '.') . ". Segera evaluasi pengeluaran Anda.";
            }
        }

        elseif (str_contains($userMessage, 'analisis') || str_contains($userMessage, 'keuangan')) {
            $response = "Berdasarkan data: Pendapatan Anda Rp" . number_format($totalRevenue, 0, ',', '.') . " dan Pengeluaran Rp" . number_format($totalExpense, 0, ',', '.') . ". " . ($balance >= 0 ? "Kondisi Sehat." : "Perlu perbaikan cashflow.");
        }
        
        elseif (str_contains($userMessage, 'terima kasih') || str_contains($userMessage, 'makasih')) {
            $response = "Sama-sama! Semoga bisnis Anda semakin sukses. 🚀";
        }

        return response()->json(['response' => $response]);
    }
}
