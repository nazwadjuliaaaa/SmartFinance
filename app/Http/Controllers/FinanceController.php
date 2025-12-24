<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;



class FinanceController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        $initial = \App\Models\FinancialInitial::where('user_id', $userId)->latest()->first();
        $startingCapital = $initial ? $initial->starting_capital : 0;
        
        $revenue = \App\Models\FinancialRecord::where('user_id', $userId)->where('type', 'in')->sum('amount');
        $expense = \App\Models\FinancialRecord::where('user_id', $userId)->where('type', 'out')->sum('amount');
        
        // Stats
        $omzet = $revenue;
        $in = $startingCapital + $revenue; 
        $out = $expense;

        // 1. Sales Trend (Last 6 Months) - reusing logic
        $months = [];
        $salesData = [];
        $profitData = []; // Estimated 40%

        for ($i = 5; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now()->subMonths($i);
            $monthName = $date->translatedFormat('M');
            $year = $date->year;
            $month = $date->month;
            
            $months[] = $monthName;

            $monthlyRevenue = \App\Models\FinancialRecord::where('user_id', $userId)
                ->where('type', 'in')
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month)
                ->sum('amount');
                
            $salesData[] = $monthlyRevenue;
            $profitData[] = $monthlyRevenue * 0.4;
        }

        // 2. Insight: Today's Achievement
        $todayRevenue = \App\Models\FinancialRecord::where('user_id', $userId)
            ->where('type', 'in')
            ->whereDate('transaction_date', \Carbon\Carbon::today())
            ->sum('amount');
            
        $dailyTarget = 1000000; // Example Target: 1 Million IDR
        $achievementPct = ($todayRevenue / $dailyTarget) * 100;
        if ($achievementPct > 100) $achievementPct = 100;
        $remainingPct = 100 - $achievementPct;

        return view('dashboard', compact(
            'initial', 'in', 'out', 'omzet',
            'months', 'salesData', 'profitData',
            'todayRevenue', 'dailyTarget', 'achievementPct', 'remainingPct'
        ));
    }

    public function showInitialInput()
    {
        $userId = auth()->id();
        $initial = \App\Models\FinancialInitial::where('user_id', $userId)->latest()->first();
        return view('input_data_awal', compact('initial'));
    }

    public function storeInitialInput(Request $request)
    {
        // Sanitize Input (Remove dots from thousands separator)
        if ($request->capital_cash) {
            $request->merge([
                'capital_cash' => str_replace('.', '', $request->capital_cash)
            ]);
        }
        
        if ($request->liabilities) {
            $request->merge([
                'liabilities' => str_replace('.', '', $request->liabilities)
            ]);
        }

        $request->validate([
            'capital_cash' => 'nullable|numeric',
            'capital_bank' => 'nullable|numeric',
            'assets_json' => 'nullable|json',
            'materials_json' => 'nullable|json',
            'liabilities' => 'nullable|numeric',
        ]);

        $capital = ($request->capital_cash ?? 0) + ($request->capital_bank ?? 0);

        $initial = \App\Models\FinancialInitial::create([
            'user_id' => auth()->id(),
            'starting_capital' => $capital,
            'fixed_assets' => json_decode($request->assets_json),
            'raw_materials' => json_decode($request->materials_json),
            'initial_liabilities' => $request->liabilities,
        ]);

        // Automatically sync Fixed Assets to Products for Sales Dropdown
        // Automatically sync Goods (from 'Barang yang Dijual') to Products for Sales Dropdown
        if ($request->materials_json) {
            $goods = json_decode($request->materials_json, true);
            foreach ($goods as $good) {
                // Check if product already exists to avoid duplicates (optional, but good practice)
                $exists = \App\Models\Product::where('user_id', auth()->id())
                            ->where('name', $good['name'])
                            ->exists();
                
                if (!$exists) {
                    \App\Models\Product::create([
                        'user_id' => auth()->id(),
                        'name' => $good['name'],
                        'price' => $good['price'] ?? 0, 
                    ]);
                }
            }
        }

        return redirect()->route('finance.recap');
    }

    public function showRecap()
    {
        $userId = auth()->id();
        $initial = \App\Models\FinancialInitial::where('user_id', $userId)->latest()->first();
        
        // 1. Financial Summary Data Only
        $startingCapital = $initial ? $initial->starting_capital : 0;
        $allRevenue = \App\Models\FinancialRecord::where('user_id', $userId)->where('type', 'in')->sum('amount');
        $allExpense = \App\Models\FinancialRecord::where('user_id', $userId)->where('type', 'out')->sum('amount');
        
        $totalBalance = $startingCapital + $allRevenue - $allExpense;

        // Growth Percentage
        $thisMonthRevenue = \App\Models\FinancialRecord::where('user_id', $userId)
            ->where('type', 'in')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount');

        $lastMonthRevenue = \App\Models\FinancialRecord::where('user_id', $userId)
            ->where('type', 'in')
            ->whereMonth('transaction_date', now()->subMonth()->month)
            ->whereYear('transaction_date', now()->subMonth()->year)
            ->sum('amount');
            
        $growthPct = 0;
        if ($lastMonthRevenue > 0) {
            $growthPct = (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;
        } elseif ($thisMonthRevenue > 0) {
            $growthPct = 100; 
        }

        // Daily Avg
        $daysPassed = now()->day;
        $dailyAvg = $daysPassed > 0 ? $thisMonthRevenue / $daysPassed : 0;

        return view('recap_data', compact('initial', 'totalBalance', 'growthPct', 'dailyAvg'));
    }

    public function reportPnL()
    {
        $userId = auth()->id();
        $initial = \App\Models\FinancialInitial::where('user_id', $userId)->latest()->first();
        
        $grossRevenue = \App\Models\FinancialRecord::where('user_id', $userId)->where('type', 'in')->sum('amount');
        $cogs = \App\Models\FinancialRecord::where('user_id', $userId)->where('type', 'out')->sum('amount');
        $opex = 0; 
        $netProfit = $grossRevenue - $cogs - $opex;

        return view('finance.report.pnl', compact('initial', 'grossRevenue', 'cogs', 'opex', 'netProfit'));
    }

    public function reportLog()
    {
        $userId = auth()->id();
        $initial = \App\Models\FinancialInitial::where('user_id', $userId)->latest()->first();
        
        $transactions = \App\Models\FinancialRecord::where('user_id', $userId)
            ->latest('transaction_date')
            ->paginate(10);

        return view('finance.report.log', compact('initial', 'transactions'));
    }

    public function reportInsight()
    {
        $userId = auth()->id();
        $initial = \App\Models\FinancialInitial::where('user_id', $userId)->latest()->first();
        
        $topProducts = \App\Models\SaleItem::whereHas('financialRecord', function($q) use ($userId) {
                $q->where('user_id', $userId)->where('type', 'in');
            })
            ->with('product')
            ->selectRaw('product_id, sum(quantity) as total_qty')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();
            
        $busiestDays = \App\Models\FinancialRecord::where('user_id', $userId)
            ->where('type', 'in')
            ->selectRaw('DAYNAME(transaction_date) as day_name, COUNT(*) as count')
            ->groupBy('day_name')
            ->orderByDesc('count')
            ->get();

        return view('finance.report.insight', compact('initial', 'topProducts', 'busiestDays'));
    }

    // Cash In Features
    public function cashInIndex()
    {
        $transactions = \App\Models\FinancialRecord::where('user_id', auth()->id())
            ->where('type', 'in')
            ->latest('transaction_date')
            ->get();
        return view('kas_masuk', compact('transactions'));
    }

    public function cashInCreate()
    {
        $products = \App\Models\Product::where('user_id', auth()->id())->get();
        // Dummy data seeding removed as per request
        return view('tambah_pemasukan', compact('products'));
    }

    public function cashInStore(Request $request)
    {
        // Sanitize Input (Remove dots from thousands separator)
        $cleanCash = str_replace('.', '', $request->amount_cash);
        $cleanNonCash = str_replace('.', '', $request->amount_non_cash);

        $request->merge([
            'amount_cash' => $cleanCash,
            'amount_non_cash' => $cleanNonCash
        ]);

        $request->validate([
            'date' => 'required|date',
            'amount_cash' => 'nullable|numeric',
            'amount_non_cash' => 'nullable|numeric',
            'items' => 'nullable|array' // array of {product_id, quantity}
        ]);

        $cash = $request->amount_cash ?? 0;
        $nonCash = $request->amount_non_cash ?? 0;
        $total = $cash + $nonCash;

        // Create record
        $record = \App\Models\FinancialRecord::create([
            'user_id' => auth()->id(),
            'type' => 'in',
            'amount' => $total,
            'cash_amount' => $cash,
            'non_cash_amount' => $nonCash,
            'transaction_date' => $request->date,
            'description' => 'Penjualan'
        ]);

        // Process items if split payment not used or just to log details
        // Note: The screenshot shows a "Tambah" button list logic. For simplicity, if we receive items array:
        if($request->has('items_json')) {
            $items = json_decode($request->items_json, true);
            foreach($items as $item) {
                \App\Models\SaleItem::create([
                    'financial_record_id' => $record->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['qty'],
                    'total_price' => $item['total']
                ]);
            }
        }

        return redirect()->route('finance.cash-in.index');
    }

    public function salesAnalysis()
    {
        $userId = auth()->id();

        // 1. Totals
        $records = \App\Models\FinancialRecord::where('user_id', $userId)->where('type', 'in')->get();
        $totalRevenue = $records->sum('amount');
        $profit = $totalRevenue * 0.4; // Estimated 40% margin

        // 2. Trend Chart (Last 6 Months)
        $trendData = [];
        $months = [];
        $revenueData = [];
        $profitData = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->format('M');
            $year = $date->format('Y');
            
            // Sum revenue for this month
            // Note: simple query inside loop for 6 items is acceptable for this scale
            $monthRevenue = \App\Models\FinancialRecord::where('user_id', $userId)
                ->where('type', 'in')
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $date->month)
                ->sum('amount');
            
            $months[] = $monthName;
            $revenueData[] = $monthRevenue;
            $profitData[] = $monthRevenue * 0.4; // Consistent 40% estimation
        }

        // 3. Top 5 Products
        // Join SaleItem with FinancialRecord to filter only 'in' (sales)
        $topProducts = \App\Models\SaleItem::whereHas('financialRecord', function($q) use ($userId) {
                $q->where('user_id', $userId)->where('type', 'in');
            })
            ->with('product')
            ->selectRaw('product_id, sum(quantity) as total_qty')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        $topProductLabels = $topProducts->map(fn($item) => $item->product->name ?? 'Unknown');
        $topProductData = $topProducts->pluck('total_qty');

        return view('analisis_pemasukan', compact(
            'totalRevenue', 
            'profit', 
            'months', 
            'revenueData', 
            'profitData',
            'topProductLabels',
            'topProductData'
        ));
    }

    // Cash Out Features
    public function cashOutIndex()
    {
        $items = \App\Models\SaleItem::whereHas('financialRecord', function($q) {
                $q->where('user_id', auth()->id())->where('type', 'out');
            })
            ->with(['financialRecord', 'product'])
            ->get()
            ->sortByDesc(function($item) {
                return $item->financialRecord->transaction_date;
            });

        return view('kas_keluar', compact('items'));
    }

    public function cashOutCreate()
    {
        // For cash out, products might be "items to buy" or inventory. 
        // Reusing Product model for simplicity or concept of 'Items'. 
        // If we want separate 'ExpenseItems', we could, but 'Product' works for 'Items' generally.
        // Let's create some dummy expense items if none exist? 
        // OR simpler: allow free text or select from Products if it's restocking.
        // The screenshot shows "Pilih Barang..." so it implies selecting predefined items.
        // We'll use the same Product model for now to keep it simple, assuming 'Stock' concept later.
        $products = \App\Models\Product::where('user_id', auth()->id())->get();
        return view('tambah_pengeluaran', compact('products'));
    }

    public function cashOutStore(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'items_json' => 'required|json'
        ]);

        $items = json_decode($request->items_json, true);
        
        if (empty($items)) {
            return back()->withErrors(['items' => 'Mohon tambahkan setidaknya satu barang pembelian.']);
        }

        $total = 0;
        foreach ($items as $item) {
            $total += $item['total'];
        }

        // Create Record
        // Assuming all as "Cash" or just "Amount" since user removed payment method selection.
        // We will default to storing in 'amount' and 'cash_amount' for consistency.
        $record = \App\Models\FinancialRecord::create([
            'user_id' => auth()->id(),
            'type' => 'out', // Expense
            'amount' => $total,
            'cash_amount' => $total, 
            'non_cash_amount' => 0,
            'transaction_date' => $request->date,
            'description' => 'Pembelian Barang'
        ]);

        // Process Items
        foreach($items as $item) {
            // Append Unit to Name to distinguish products
            $productName = $item['name'] . ' (' . $item['unit'] . ')';
            
            // Find or Create Product for tracking
            $product = \App\Models\Product::firstOrCreate(
                ['user_id' => auth()->id(), 'name' => $productName],
                ['price' => $item['price']] // Default price for future reference
            );

            \App\Models\SaleItem::create([
                'financial_record_id' => $record->id,
                'product_id' => $product->id,
                'quantity' => $item['qty'],
                'total_price' => $item['total']
            ]);
        }

        return redirect()->route('finance.cash-out.index');
    }

    public function expenseAnalysis()
    {
        $userId = auth()->id();
        $records = \App\Models\FinancialRecord::where('user_id', $userId)->where('type', 'out')->get();
        $totalExpense = $records->sum('amount');
        
        // 1. Expense Trend (Last 6 Months)
        $months = [];
        $expenseData = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now()->subMonths($i);
            $monthName = $date->translatedFormat('M');
            $year = $date->year;
            $month = $date->month;
            
            $months[] = $monthName;

            $monthlyExpense = \App\Models\FinancialRecord::where('user_id', $userId)
                ->where('type', 'out')
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month)
                ->sum('amount');
                
            $expenseData[] = $monthlyExpense;
        }

        // 2. Top 5 Purchased Application Items
        $topExpenses = \App\Models\SaleItem::whereHas('financialRecord', function($q) use ($userId) {
                $q->where('user_id', $userId)->where('type', 'out');
            })
            ->with('product')
            ->selectRaw('product_id, sum(quantity) as total_qty')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        $topExpenseLabels = $topExpenses->map(fn($item) => $item->product->name ?? 'Unknown');
        $topExpenseData = $topExpenses->pluck('total_qty');

        return view('analisis_pengeluaran', compact(
            'totalExpense', 
            'months', 
            'expenseData',
            'topExpenseLabels',
            'topExpenseData'
        ));
    }


    public function profile()
    {
        return view('profile_pengguna');
    }
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,'.$user->id,
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'password' => 'nullable|min:6',
        ]);

        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($user->profile_photo && \Illuminate\Support\Facades\Storage::exists('public/' . $user->profile_photo)) {
                \Illuminate\Support\Facades\Storage::delete('public/' . $user->profile_photo);
            }
            
            $path = $request->file('photo')->store('profile_photos', 'public');
            $user->profile_photo = $path;
        }

        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui!');
    }
}
