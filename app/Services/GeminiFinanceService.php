<?php

namespace App\Services;

use App\Models\FinancialRecord;
use App\Models\FinancialInitial;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiFinanceService
{
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent';
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    /**
     * Entry point to analyze financial health
     */
    public function analyzeFinancialHealth($user)
    {
        $context = $this->aggregateData($user);
        $prompt = $this->buildAnalysisPrompt($context);
        
        return $this->callGemini($prompt);
    }

    /**
     * Generate specific financial report
     */
    public function generateFinancialReport($user, $period = 'monthly')
    {
        $context = $this->aggregateData($user);
        $prompt = $this->buildReportPrompt($context, $period);
        
        return $this->callGemini($prompt);
    }

    /**
     * Handle general chat with financial context
     */
    public function chat($userMessage, $user)
    {
        $context = $this->aggregateData($user);
        $prompt = $this->buildChatPrompt($userMessage, $context);

        return $this->callGemini($prompt);
    }

    /**
     * Gather all relevant financial data into a structured array
     */
    protected function aggregateData($user)
    {
        // 1. Initial Data
        $initial = FinancialInitial::where('user_id', $user->id)->first();
        
        // 2. Transactions (Last 30 days)
        $records = FinancialRecord::where('user_id', $user->id)
            ->where('transaction_date', '>=', now()->subDays(60)) // Get 2 months for trend comparison
            ->get();

        $income = $records->where('type', 'income');
        $expense = $records->where('type', 'expense');

        // 3. Sales Data
        $saleItems = SaleItem::with('product')
            ->whereHas('financialRecord', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->get();

        $topProducts = $saleItems->groupBy('product_id')
            ->map(function ($items) {
                return [
                    'name' => $items->first()->product->name ?? 'Unknown',
                    'total_sold' => $items->sum('quantity'),
                    'total_revenue' => $items->sum('total_price'),
                ];
            })
            ->sortByDesc('total_revenue')
            ->take(5);

        return [
            'initial_capital' => $initial->starting_capital ?? 0,
            'current_assets' => $initial->fixed_assets ?? [],
            'cash_in_total' => $income->sum('amount'),
            'cash_out_total' => $expense->sum('amount'),
            'cash_in_trends' => $income->groupBy('transaction_date')->map->sum('amount'),
            'cash_out_trends' => $expense->groupBy('transaction_date')->map->sum('amount'),
            'top_products' => $topProducts,
            'recent_expenses' => $expense->take(10)->map(function ($r) {
                return "{$r->transaction_date->format('Y-m-d')}: {$r->description} ({$r->amount})";
            })->implode("\n"),
        ];
    }

    /**
     * Construct the prompt for general analysis
     */
    protected function buildAnalysisPrompt($data)
    {
        return "
        Bertindaklah sebagai Analis Keuangan Ahli untuk UMKM. Analisis data keuangan berikut dan berikan wawasan.
        Format respons Anda HARUS JSON valid dengan struktur:
        {
            'trends': 'Penjelasan tren pendapatan dan pengeluaran...',
            'forecasting': 'Prediksi 30 hari ke depan...',
            'anomalies': 'Daftar pengeluaran aneh jika ada...',
            'recommendations': 'Saran strategis untuk efisiensi...'
        }

        DATA KEUANGAN:
        - Modal Awal: Rp " . number_format($data['initial_capital']) . "
        - Total Pemasukan (60 Hari): Rp " . number_format($data['cash_in_total']) . "
        - Total Pengeluaran (60 Hari): Rp " . number_format($data['cash_out_total']) . "
        - Produk Terlaris: " . json_encode($data['top_products']) . "
        - Pengeluaran Terakhir: \n" . $data['recent_expenses'] . "
        ";
    }

    /**
     * Construct the prompt for formal reporting
     */
    protected function buildReportPrompt($data, $period)
    {
        return "
        Buatlah Laporan Keuangan Naratif (Laporan Laba Rugi Sederhana) untuk periode {$period}.
        Gunakan gaya bahasa formal namun mudah dimengerti. Sertakan:
        1. Pendahuluan
        2. Ringkasan Kinerja (Pemasukan vs Pengeluaran)
        3. Analisis Produk (Apa yang laku keras)
        4. Kesimpulan Kesehatan Keuangan

        DATA: " . json_encode($data);
    }

    /**
     * Construct the prompt for chat
     */
    protected function buildChatPrompt($userMessage, $data)
    {
        $financialSummary = "
        - Modal Awal: Rp " . number_format($data['initial_capital'], 0, ',', '.') . "
        - Total Pemasukan: Rp " . number_format($data['cash_in_total'], 0, ',', '.') . "
        - Total Pengeluaran: Rp " . number_format($data['cash_out_total'], 0, ',', '.') . "
        - Keuntungan Bersih: Rp " . number_format($data['cash_in_total'] - $data['cash_out_total'], 0, ',', '.') . "
        - Produk Terlaris: " . json_encode($data['top_products']) . "
        ";

        return "
        Kamu adalah 'SmartAssistant', asisten keuangan cerdas dari aplikasi SmartFinance.
        
        PERAN KAMU:
        Sebagai konsultan keuangan pribadi yang ramah, profesional, dan solutif. Kamu membantu pengguna memahami kondisi keuangan mereka, memberikan saran bisnis, dan menjawab pertanyaan seputar ekonomi.

        DATA KEUANGAN PENGGUNA (Gunakan ini sebagai konteks):
        {$financialSummary}

        INSTRUKSI KHUSUS:
        1.  **Gaya Bicara**: Natural, seperti manusia, empatik, dan tidak kaku. Jangan memberi jawaban seperti robot.
        2.  **Topik**: Jawab SEMUA pertanyaan yang berkaitan dengan:
            - Analisis data keuangan pengguna (misalnya: 'Apakah saya untung?', 'Bagaimana kondisi keuanganku?').
            - Tips bisnis dan manajemen keuangan (misalnya: 'Cara meningkatkan omzet', 'Tips hemat').
            - Pengetahuan umum ekonomi dan investasi.
        3.  **Larangan**:
            - JANGAN menjawab pertanyaan di luar topik keuangan (misalnya: resep masakan, rekomendasi film, politik).
            - Jika ditanya hal di luar topik, tolak dengan sopan dan jenaka, lalu arahkan kembali ke keuangan. Contoh: 'Waduh, saya kurang jago soal masak. Tapi kalau soal 'meracik' keuntungan bisnis, saya ahlinya! Ada yang mau ditanyakan soal keuanganmu?'
        4.  **Tanpa Kata Kunci**: Pengguna TIDAK perlu mengetik kata kunci khusus seperti 'analisis' atau 'tips'. Pahami maksud mereka dari kalimat biasa.
        
        PERTANYAAN PENGGUNA:
        '{$userMessage}'

        JAWABAN (Langsung berikan jawaban terbaikmu dalam Bahasa Indonesia):
        ";
    }

    /**
     * Send request to Gemini API
     */
    protected function callGemini($prompt)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            if ($response->failed()) {
                if ($response->status() == 429) {
                    return ['error' => '⏳ Kuota AI habis sementara. Mohon tunggu 1-2 menit sebelum mencoba lagi.'];
                }
                Log::error('Gemini API Error: ' . $response->body());
                return ['error' => 'Gagal menghubungi AI. Cek log/API Key.'];
            }

            $result = $response->json();
            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            // Try to parse JSON if expected
            $jsonStart = strpos($text, '{');
            $jsonEnd = strrpos($text, '}');
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonStr = substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
                return json_decode($jsonStr, true) ?? ['text' => $text]; // Fallback to raw text if decode fails
            }

            return ['text' => $text]; // Return raw text for reports

        } catch (\Exception $e) {
            Log::error('Gemini Service Exception: ' . $e->getMessage());
            return ['error' => 'Terjadi kesalahan sistem.'];
        }
    }
    /**
     * Analyze Business Strategy based on Net Profit
     */
    public function analyzeBusinessStrategy($user)
    {
        $context = $this->aggregateData($user);
        $netProfit = $context['cash_in_total'] - $context['cash_out_total'];
        $context['net_profit'] = $netProfit;

        $prompt = "
        Bertindaklah sebagai Konsultan Strategi Bisnis Senior.
        Analisis data keuangan berikut untuk memberikan strategi bisnis yang konkret.

        DATA KEUANGAN:
        - Total Pemasukan: Rp " . number_format($context['cash_in_total'], 0, ',', '.') . "
        - Total Pengeluaran: Rp " . number_format($context['cash_out_total'], 0, ',', '.') . "
        - Keuntungan Bersih (Omzet Bersih): Rp " . number_format($netProfit, 0, ',', '.') . "
        - Produk Terlaris: " . json_encode($context['top_products']) . "

        TUGAS:
        Berikan analisis dalam format JSON berikut:
        {
            'defense_strategy': 'Strategi untuk mempertahankan dan memaksimalkan bisnis saat ini berdasarkan produk terlaris dan margin saat ini.',
            'expansion_recommendation': 'Rekomendasi ide bisnis BARU atau ekspansi yang cocok dilakukan dengan modal dari keuntungan bersih saat ini (sebutkan estimasi modalnya).'
        }

        Pastikan sarannya praktis, masuk akal untuk skala UMKM, dan langsung pada intinya.
        ";

        return $this->callGemini($prompt);
    }
}
