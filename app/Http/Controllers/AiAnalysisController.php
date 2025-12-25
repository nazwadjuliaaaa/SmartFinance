<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GeminiFinanceService;
use Illuminate\Support\Facades\Auth;
use App\Models\BusinessStrategy;
use App\Models\FinancialRecord;

class AiAnalysisController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiFinanceService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function index()
    {
        return view('ai_analyst.index');
    }

    public function specificAnalysis(Request $request)
    {
        // For feature-specific requests (e.g. "Explain this chart")
        // Not implemented in MVP, but good to have the hook
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
        
        // 1. Calculate Current Net Profit (same logic as Service)
        $revenue = FinancialRecord::where('user_id', $user->id)->where('type', 'in')->sum('amount');
        $expense = FinancialRecord::where('user_id', $user->id)->where('type', 'out')->sum('amount');
        $currentProfit = $revenue - $expense;

        // 2. Check for latest strategy
        $latestStrategy = BusinessStrategy::where('user_id', $user->id)
            ->whereIn('status', ['active', 'accepted'])
            ->latest()
            ->first();

        // 3. Logic for Reuse
        if ($latestStrategy) {
            // If user has accepted this strategy, we keep showing it until they explicitly request a new one
            if ($latestStrategy->status === 'accepted') {
                return response()->json(array_merge($latestStrategy->strategy_content, ['status' => 'accepted']));
            }
            
            // If profit hasn't changed significantly (exact match for now), reuse it
            // Note: in floating point, exact match might be risky, but for currency it's usually fine or we use ranges. 
            // Let's use exact match for simple MVP string comparison after format, or just abs diff < 1000
            if (abs($latestStrategy->based_on_profit - $currentProfit) < 1000) {
                 return response()->json(array_merge($latestStrategy->strategy_content, ['status' => 'active']));
            }
        }

        // 4. Generate New (if no strategy exists, or profit changed)
        return $this->generateAndSaveStrategy($user, $currentProfit);
    }

    public function regenerateStrategy()
    {
        $user = Auth::user();
        
        // Calculate Profit
        $revenue = FinancialRecord::where('user_id', $user->id)->where('type', 'in')->sum('amount');
        $expense = FinancialRecord::where('user_id', $user->id)->where('type', 'out')->sum('amount');
        $currentProfit = $revenue - $expense;

        // Force generate new
        return $this->generateAndSaveStrategy($user, $currentProfit);
    }

    public function acceptStrategy()
    {
        $user = Auth::user();
        $latestStrategy = BusinessStrategy::where('user_id', $user->id)->latest()->first();

        if ($latestStrategy) {
            $latestStrategy->update(['status' => 'accepted']);
            return response()->json(['message' => 'Strategy accepted']);
        }

        return response()->json(['error' => 'No strategy found'], 404);
    }

    private function generateAndSaveStrategy($user, $profit)
    {
        $strategyData = $this->geminiService->analyzeBusinessStrategy($user);
        
        // Check if API failed
        if (isset($strategyData['error'])) {
            return response()->json($strategyData);
        }

        // Save to DB
        BusinessStrategy::create([
            'user_id' => $user->id,
            'strategy_content' => $strategyData,
            'based_on_profit' => $profit,
            'status' => 'active'
        ]);

        return response()->json(array_merge($strategyData, ['status' => 'active']));
    }
}
