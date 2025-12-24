<?php

namespace App\Services;

use App\Models\FinancialRecord;
use App\Models\FinancialInitial;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiFinanceService
{
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
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
}
