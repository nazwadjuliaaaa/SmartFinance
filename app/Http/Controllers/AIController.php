<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GeminiFinanceService;
use Illuminate\Support\Facades\Auth;

class AIController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiFinanceService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['response' => 'Silakan login terlebih dahulu.']);
        }

        // Call Gemini AI via Service
        $result = $this->geminiService->chat($request->message, $user);

        // Handle response format (Service might return array with 'text' or 'error')
        $responseText = $result['text'] ?? ($result['error'] ?? 'Maaf, terjadi kesalahan saat memproses data.');

        return response()->json(['response' => $responseText]);
    }
}
