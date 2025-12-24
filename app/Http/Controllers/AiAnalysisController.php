<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GeminiFinanceService;
use Illuminate\Support\Facades\Auth;

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
}
