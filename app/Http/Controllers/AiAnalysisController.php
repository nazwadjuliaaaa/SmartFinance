<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GeminiFinanceService;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Auth;

class AiAnalysisController extends Controller
{
    protected GeminiFinanceService $geminiService;
    protected SupabaseService $supabase;

    public function __construct(GeminiFinanceService $geminiService, SupabaseService $supabase)
    {
        $this->geminiService = $geminiService;
        $this->supabase = $supabase;
    }

    public function index()
    {
        return view('ai_analyst.index');
    }

    public function specificAnalysis(Request $request)
    {
        // Not implemented in MVP
    }

    public function getAnalysis()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $analysis = $this->geminiService->analyzeFinancialHealth($user);
        return response()->json($analysis);
    }

    public function getReport(Request $request)
    {
        $user = Auth::user();
        $period = $request->query('period', 'monthly');
        
        $report = $this->geminiService->generateFinancialReport($user, $period);
        return response()->json($report);
    }

    public function getStrategy()
    {
        $user = Auth::user();
        $userId = $user->id;
        
        // 1. Calculate Current Net Profit via Supabase
        $incomeRecords = $this->supabase->select('financial_records', ['amount'], ['user_id' => "eq.{$userId}", 'type' => 'eq.in']);
        $expenseRecords = $this->supabase->select('financial_records', ['amount'], ['user_id' => "eq.{$userId}", 'type' => 'eq.out']);
        
        $revenue = array_sum(array_column($incomeRecords, 'amount'));
        $expense = array_sum(array_column($expenseRecords, 'amount'));
        $currentProfit = $revenue - $expense;

        // 2. Check for latest strategy via Supabase
        $strategies = $this->supabase->select('business_strategies', ['*'], ['user_id' => "eq.{$userId}"]);
        
        // Filter active or accepted strategies
        $latestStrategy = null;
        foreach ($strategies as $s) {
            if (in_array($s['status'], ['active', 'accepted'])) {
                if (!$latestStrategy || $s['id'] > $latestStrategy['id']) {
                    $latestStrategy = $s;
                }
            }
        }

        // 3. Logic for Reuse
        if ($latestStrategy) {
            $strategyContent = is_string($latestStrategy['strategy_content']) 
                ? json_decode($latestStrategy['strategy_content'], true) 
                : $latestStrategy['strategy_content'];
            
            if ($latestStrategy['status'] === 'accepted') {
                return response()->json(array_merge($strategyContent, ['status' => 'accepted']));
            }
            
            if (abs($latestStrategy['based_on_profit'] - $currentProfit) < 1000) {
                return response()->json(array_merge($strategyContent, ['status' => 'active']));
            }
        }

        // 4. Generate New
        return $this->generateAndSaveStrategy($user, $currentProfit);
    }

    public function regenerateStrategy()
    {
        $user = Auth::user();
        $userId = $user->id;
        
        $incomeRecords = $this->supabase->select('financial_records', ['amount'], ['user_id' => "eq.{$userId}", 'type' => 'eq.in']);
        $expenseRecords = $this->supabase->select('financial_records', ['amount'], ['user_id' => "eq.{$userId}", 'type' => 'eq.out']);
        
        $revenue = array_sum(array_column($incomeRecords, 'amount'));
        $expense = array_sum(array_column($expenseRecords, 'amount'));
        $currentProfit = $revenue - $expense;

        return $this->generateAndSaveStrategy($user, $currentProfit);
    }

    public function acceptStrategy()
    {
        $user = Auth::user();
        $userId = $user->id;
        
        $strategies = $this->supabase->select('business_strategies', ['*'], ['user_id' => "eq.{$userId}"]);
        
        // Find latest
        $latestStrategy = null;
        foreach ($strategies as $s) {
            if (!$latestStrategy || $s['id'] > $latestStrategy['id']) {
                $latestStrategy = $s;
            }
        }

        if ($latestStrategy) {
            $this->supabase->update('business_strategies', ['status' => 'accepted'], ['id' => "eq.{$latestStrategy['id']}"]);
            return response()->json(['message' => 'Strategy accepted']);
        }

        return response()->json(['error' => 'No strategy found'], 404);
    }

    private function generateAndSaveStrategy($user, $profit)
    {
        $strategyData = $this->geminiService->analyzeBusinessStrategy($user);
        
        if (isset($strategyData['error'])) {
            return response()->json($strategyData);
        }

        // Save to Supabase
        $this->supabase->insert('business_strategies', [
            'user_id' => $user->id,
            'strategy_content' => json_encode($strategyData),
            'based_on_profit' => $profit,
            'status' => 'active'
        ]);

        return response()->json(array_merge($strategyData, ['status' => 'active']));
    }
}
