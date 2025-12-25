<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class FinanceController extends Controller
{
    protected SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    public function index()
    {
        $userId = auth()->id();
        
        // Initial data
        $initials = $this->supabase->select('financial_initials', ['*'], ['user_id' => "eq.{$userId}"], 1);
        $initial = null;
        $startingCapital = 0;
        
        if (!empty($initials)) {
            $initial = (object) $initials[0];
            $startingCapital = (float)$initial->starting_capital;
        }
        
        // Get ALL income records with dates for aggregation
        $incomeRecords = $this->supabase->select('financial_records', ['amount', 'transaction_date'], ['user_id' => "eq.{$userId}", 'type' => 'eq.in']);
        $expenseRecords = $this->supabase->select('financial_records', ['amount'], ['user_id' => "eq.{$userId}", 'type' => 'eq.out']);
        
        $revenue = array_sum(array_column($incomeRecords, 'amount'));
        $expense = array_sum(array_column($expenseRecords, 'amount'));
        
        $omzet = $revenue;
        $in = $startingCapital + $revenue;
        $out = $expense;

        // Chart data - aggregate by month
        $months = [];
        $salesData = [];
        $profitData = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->translatedFormat('M');
            $year = $date->year;
            $month = $date->month;
            
            // Filter income records for this month
            $monthlyTotal = 0;
            foreach ($incomeRecords as $r) {
                $txDate = Carbon::parse($r['transaction_date']);
                if ($txDate->year == $year && $txDate->month == $month) {
                    $monthlyTotal += $r['amount'];
                }
            }
            
            $salesData[] = $monthlyTotal;
            $profitData[] = $monthlyTotal * 0.4; // Estimated 40% profit margin
        }

        // Today's revenue for doughnut chart
        $todayRevenue = 0;
        $today = Carbon::today()->format('Y-m-d');
        foreach ($incomeRecords as $r) {
            if (Carbon::parse($r['transaction_date'])->format('Y-m-d') === $today) {
                $todayRevenue += $r['amount'];
            }
        }
        
        $dailyTarget = 1000000;
        $achievementPct = ($dailyTarget > 0) ? min(100, ($todayRevenue / $dailyTarget) * 100) : 0;
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
        $initials = $this->supabase->select('financial_initials', ['*'], ['user_id' => "eq.{$userId}"], 1);
        $initial = $initials[0] ?? null;
        
        // Convert array to object for blade compatibility
        if ($initial) {
            $initial = (object) $initial;
            $initial->fixed_assets = is_string($initial->fixed_assets) ? json_decode($initial->fixed_assets, true) : $initial->fixed_assets;
            $initial->raw_materials = is_string($initial->raw_materials) ? json_decode($initial->raw_materials, true) : $initial->raw_materials;
        }
        
        return view('input_data_awal', compact('initial'));
    }

    public function storeInitialInput(Request $request)
    {
        if ($request->capital_cash) {
            $request->merge(['capital_cash' => str_replace('.', '', $request->capital_cash)]);
        }
        if ($request->liabilities) {
            $request->merge(['liabilities' => str_replace('.', '', $request->liabilities)]);
        }

        $request->validate([
            'capital_cash' => 'nullable|numeric',
            'capital_bank' => 'nullable|numeric',
            'assets_json' => 'nullable|json',
            'materials_json' => 'nullable|json',
            'liabilities' => 'nullable|numeric',
        ]);

        $capital = ($request->capital_cash ?? 0) + ($request->capital_bank ?? 0);

        $this->supabase->insert('financial_initials', [
            'user_id' => auth()->id(),
            'starting_capital' => $capital,
            'fixed_assets' => $request->assets_json,
            'raw_materials' => $request->materials_json,
            'initial_liabilities' => $request->liabilities,
        ]);

        // Sync products
        if ($request->materials_json) {
            $goods = json_decode($request->materials_json, true);
            foreach ($goods as $good) {
                $existing = $this->supabase->select('products', ['id'], [
                    'user_id' => "eq." . auth()->id(),
                    'name' => "eq." . $good['name']
                ], 1);
                
                if (empty($existing)) {
                    $this->supabase->insert('products', [
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
        $initials = $this->supabase->select('financial_initials', ['*'], ['user_id' => "eq.{$userId}"], 1);
        $initial = $initials[0] ?? null;
        if ($initial) $initial = (object) $initial;
        
        $startingCapital = $initial ? (float)$initial->starting_capital : 0;
        
        $incomeRecords = $this->supabase->select('financial_records', ['amount'], ['user_id' => "eq.{$userId}", 'type' => 'eq.in']);
        $expenseRecords = $this->supabase->select('financial_records', ['amount'], ['user_id' => "eq.{$userId}", 'type' => 'eq.out']);
        
        $allRevenue = array_sum(array_column($incomeRecords, 'amount'));
        $allExpense = array_sum(array_column($expenseRecords, 'amount'));
        
        $totalBalance = $startingCapital + $allRevenue - $allExpense;
        $growthPct = 0;
        $dailyAvg = 0;

        return view('recap_data', compact('initial', 'totalBalance', 'growthPct', 'dailyAvg'));
    }

    public function reportPnL()
    {
        $userId = auth()->id();
        $initials = $this->supabase->select('financial_initials', ['*'], ['user_id' => "eq.{$userId}"], 1);
        $initial = $initials[0] ?? null;
        if ($initial) $initial = (object) $initial;
        
        $incomeRecords = $this->supabase->select('financial_records', ['amount'], ['user_id' => "eq.{$userId}", 'type' => 'eq.in']);
        $expenseRecords = $this->supabase->select('financial_records', ['amount'], ['user_id' => "eq.{$userId}", 'type' => 'eq.out']);
        
        $grossRevenue = array_sum(array_column($incomeRecords, 'amount'));
        $cogs = array_sum(array_column($expenseRecords, 'amount'));
        $opex = 0;
        $netProfit = $grossRevenue - $cogs - $opex;

        return view('finance.report.pnl', compact('initial', 'grossRevenue', 'cogs', 'opex', 'netProfit'));
    }

    public function reportLog()
    {
        $userId = auth()->id();
        $initials = $this->supabase->select('financial_initials', ['*'], ['user_id' => "eq.{$userId}"], 1);
        $initial = $initials[0] ?? null;
        if ($initial) $initial = (object) $initial;
        
        $records = $this->supabase->select('financial_records', ['*'], ['user_id' => "eq.{$userId}"]);
        
        // Convert to collection-like for blade
        $transactions = collect($records)->map(function($r) {
            $obj = (object) $r;
            $obj->transaction_date = Carbon::parse($r['transaction_date']);
            return $obj;
        })->sortByDesc('transaction_date');

        return view('finance.report.log', compact('initial', 'transactions'));
    }

    public function reportInsight()
    {
        $userId = auth()->id();
        $initials = $this->supabase->select('financial_initials', ['*'], ['user_id' => "eq.{$userId}"], 1);
        $initial = $initials[0] ?? null;
        if ($initial) $initial = (object) $initial;
        
        // Get income records for this user
        $incomeRecords = $this->supabase->select('financial_records', ['id'], ['user_id' => "eq.{$userId}", 'type' => 'eq.in']);
        
        // Aggregate Top Products
        $topProducts = collect([]);
        if (!empty($incomeRecords)) {
            $recordIds = array_column($incomeRecords, 'id');
            
            // Get sale items for these records
            $allSaleItems = [];
            foreach ($recordIds as $rid) {
                $items = $this->supabase->select('sale_items', ['product_id', 'quantity'], ['financial_record_id' => "eq.{$rid}"]);
                $allSaleItems = array_merge($allSaleItems, $items);
            }
            
            // Group by product_id
            $grouped = [];
            foreach ($allSaleItems as $si) {
                $pid = $si['product_id'];
                if (!isset($grouped[$pid])) {
                    $grouped[$pid] = ['product_id' => $pid, 'total_qty' => 0];
                }
                $grouped[$pid]['total_qty'] += $si['quantity'];
            }
            
            // Get product names and create objects
            $topProductItems = [];
            foreach ($grouped as $pid => $data) {
                $products = $this->supabase->select('products', ['name'], ['id' => "eq.{$pid}"], 1);
                $productName = $products[0]['name'] ?? 'Unknown';
                
                $item = new \stdClass();
                $item->product = (object) ['name' => $productName];
                $item->total_qty = $data['total_qty'];
                $topProductItems[] = $item;
            }
            
            // Sort and take top 5
            usort($topProductItems, fn($a, $b) => $b->total_qty <=> $a->total_qty);
            $topProducts = collect(array_slice($topProductItems, 0, 5));
        }
        
        // Aggregate Busiest Days
        $busiestDays = collect([]);
        $allRecords = $this->supabase->select('financial_records', ['transaction_date'], ['user_id' => "eq.{$userId}", 'type' => 'eq.in']);
        
        if (!empty($allRecords)) {
            $dayCounts = [];
            foreach ($allRecords as $r) {
                $dayName = Carbon::parse($r['transaction_date'])->translatedFormat('l'); // Full day name
                if (!isset($dayCounts[$dayName])) {
                    $dayCounts[$dayName] = 0;
                }
                $dayCounts[$dayName]++;
            }
            
            // Sort by count
            arsort($dayCounts);
            
            foreach ($dayCounts as $dayName => $count) {
                $item = new \stdClass();
                $item->day_name = $dayName;
                $item->count = $count;
                $busiestDays->push($item);
            }
        }

        return view('finance.report.insight', compact('initial', 'topProducts', 'busiestDays'));
    }

    // Cash In Features
    public function cashInIndex()
    {
        $userId = auth()->id();
        $records = $this->supabase->select('financial_records', ['*'], ['user_id' => "eq.{$userId}", 'type' => 'eq.in']);
        
        $transactions = collect($records)->map(function($r) {
            $obj = (object) $r;
            $obj->transaction_date = Carbon::parse($r['transaction_date']);
            return $obj;
        })->sortByDesc('transaction_date');

        return view('kas_masuk', compact('transactions'));
    }

    public function cashInCreate()
    {
        $userId = auth()->id();
        $productsList = $this->supabase->select('products', ['*'], ['user_id' => "eq.{$userId}"]);
        $products = collect($productsList)->map(fn($p) => (object) $p);
        
        return view('tambah_pemasukan', compact('products'));
    }

    public function cashInStore(Request $request)
    {
        $cleanCash = str_replace('.', '', $request->amount_cash ?? '0');
        $cleanNonCash = str_replace('.', '', $request->amount_non_cash ?? '0');

        $request->merge([
            'amount_cash' => $cleanCash,
            'amount_non_cash' => $cleanNonCash
        ]);

        $request->validate([
            'date' => 'required|date',
            'amount_cash' => 'nullable|numeric',
            'amount_non_cash' => 'nullable|numeric',
        ]);

        $cash = $request->amount_cash ?? 0;
        $nonCash = $request->amount_non_cash ?? 0;
        $total = $cash + $nonCash;

        $record = $this->supabase->insert('financial_records', [
            'user_id' => auth()->id(),
            'type' => 'in',
            'amount' => $total,
            'cash_amount' => $cash,
            'non_cash_amount' => $nonCash,
            'transaction_date' => $request->date,
            'description' => 'Penjualan'
        ]);

        if ($request->has('items_json') && $record) {
            $items = json_decode($request->items_json, true);
            foreach ($items as $item) {
                $this->supabase->insert('sale_items', [
                    'financial_record_id' => $record['id'],
                    'product_id' => $item['id'],
                    'quantity' => $item['qty'],
                    'total_price' => $item['total']
                ]);
            }
        }

        return redirect()->route('finance.cash-in.index');
    }

    public function cashInEdit($id)
    {
        $userId = auth()->id();
        $records = $this->supabase->select('financial_records', ['*'], ['id' => "eq.{$id}", 'user_id' => "eq.{$userId}"], 1);
        
        if (empty($records)) {
            abort(404);
        }
        
        $record = (object) $records[0];
        $record->transaction_date = Carbon::parse($record->transaction_date);
        
        $productsList = $this->supabase->select('products', ['*'], ['user_id' => "eq.{$userId}"]);
        $products = collect($productsList)->map(fn($p) => (object) $p);

        return view('edit_pemasukan', compact('record', 'products'));
    }

    public function cashInUpdate(Request $request, $id)
    {
        $userId = auth()->id();
        
        $cleanCash = str_replace('.', '', $request->amount_cash ?? '0');
        $cleanNonCash = str_replace('.', '', $request->amount_non_cash ?? '0');

        $request->merge([
            'amount_cash' => $cleanCash,
            'amount_non_cash' => $cleanNonCash
        ]);

        $request->validate([
            'date' => 'required|date',
            'amount_cash' => 'nullable|numeric',
            'amount_non_cash' => 'nullable|numeric',
        ]);

        $cash = $request->amount_cash ?? 0;
        $nonCash = $request->amount_non_cash ?? 0;
        $total = $cash + $nonCash;

        $this->supabase->update('financial_records', [
            'transaction_date' => $request->date,
            'amount' => $total,
            'cash_amount' => $cash,
            'non_cash_amount' => $nonCash,
        ], ['id' => "eq.{$id}", 'user_id' => "eq.{$userId}"]);

        return redirect()->route('finance.cash-in.index')->with('success', 'Data berhasil diperbarui');
    }

    public function cashInDestroy($id)
    {
        $userId = auth()->id();
        
        $this->supabase->delete('sale_items', ['financial_record_id' => "eq.{$id}"]);
        $this->supabase->delete('financial_records', ['id' => "eq.{$id}", 'user_id' => "eq.{$userId}"]);

        return redirect()->route('finance.cash-in.index')->with('success', 'Data berhasil dihapus');
    }

    public function salesAnalysis()
    {
        $userId = auth()->id();
        
        // Get income records with dates
        $incomeRecords = $this->supabase->select('financial_records', ['id', 'amount', 'transaction_date'], ['user_id' => "eq.{$userId}", 'type' => 'eq.in']);
        $totalRevenue = array_sum(array_column($incomeRecords, 'amount'));
        $profit = $totalRevenue * 0.4;

        // Monthly aggregation
        $months = [];
        $revenueData = [];
        $profitData = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M');
            $year = $date->year;
            $month = $date->month;
            
            $monthlyTotal = 0;
            foreach ($incomeRecords as $r) {
                $txDate = Carbon::parse($r['transaction_date']);
                if ($txDate->year == $year && $txDate->month == $month) {
                    $monthlyTotal += $r['amount'];
                }
            }
            
            $revenueData[] = $monthlyTotal;
            $profitData[] = $monthlyTotal * 0.4;
        }

        // Top Products from sale_items
        $topProductLabels = collect([]);
        $topProductData = collect([]);
        
        if (!empty($incomeRecords)) {
            $recordIds = array_column($incomeRecords, 'id');
            $allSaleItems = [];
            
            foreach ($recordIds as $rid) {
                $items = $this->supabase->select('sale_items', ['product_id', 'quantity'], ['financial_record_id' => "eq.{$rid}"]);
                $allSaleItems = array_merge($allSaleItems, $items);
            }
            
            // Group by product_id
            $grouped = [];
            foreach ($allSaleItems as $si) {
                $pid = $si['product_id'];
                if (!isset($grouped[$pid])) {
                    $grouped[$pid] = ['product_id' => $pid, 'qty' => 0];
                }
                $grouped[$pid]['qty'] += $si['quantity'];
            }
            
            // Sort and take top 5
            usort($grouped, fn($a, $b) => $b['qty'] <=> $a['qty']);
            $top5 = array_slice($grouped, 0, 5);
            
            foreach ($top5 as $item) {
                $products = $this->supabase->select('products', ['name'], ['id' => "eq.{$item['product_id']}"], 1);
                $topProductLabels->push($products[0]['name'] ?? 'Unknown');
                $topProductData->push($item['qty']);
            }
        }

        return view('analisis_pemasukan', compact(
            'totalRevenue', 'profit', 'months', 'revenueData', 'profitData',
            'topProductLabels', 'topProductData'
        ));
    }

    // Cash Out Features
    public function cashOutIndex()
    {
        $userId = auth()->id();
        
        // Get expense records
        $records = $this->supabase->select('financial_records', ['*'], ['user_id' => "eq.{$userId}", 'type' => 'eq.out']);
        
        // Get all sale items for those records
        $items = collect([]);
        foreach ($records as $record) {
            $saleItems = $this->supabase->select('sale_items', ['*'], ['financial_record_id' => "eq.{$record['id']}"]);
            foreach ($saleItems as $si) {
                $products = $this->supabase->select('products', ['*'], ['id' => "eq.{$si['product_id']}"], 1);
                $product = $products[0] ?? ['name' => 'Unknown'];
                
                $itemObj = (object) $si;
                $itemObj->financialRecord = (object) $record;
                $itemObj->financialRecord->transaction_date = Carbon::parse($record['transaction_date']);
                $itemObj->product = (object) $product;
                $items->push($itemObj);
            }
        }

        return view('kas_keluar', compact('items'));
    }

    public function cashOutCreate()
    {
        $userId = auth()->id();
        $productsList = $this->supabase->select('products', ['*'], ['user_id' => "eq.{$userId}"]);
        $products = collect($productsList)->map(fn($p) => (object) $p);
        
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

        $total = array_sum(array_column($items, 'total'));

        $record = $this->supabase->insert('financial_records', [
            'user_id' => auth()->id(),
            'type' => 'out',
            'amount' => $total,
            'cash_amount' => $total,
            'non_cash_amount' => 0,
            'transaction_date' => $request->date,
            'description' => 'Pembelian Barang'
        ]);

        if ($record) {
            foreach ($items as $item) {
                $productName = $item['name'] . ' (' . $item['unit'] . ')';
                
                $existing = $this->supabase->select('products', ['*'], [
                    'user_id' => "eq." . auth()->id(),
                    'name' => "eq." . $productName
                ], 1);
                
                if (empty($existing)) {
                    $product = $this->supabase->insert('products', [
                        'user_id' => auth()->id(),
                        'name' => $productName,
                        'price' => $item['price'],
                    ]);
                    $productId = $product['id'];
                } else {
                    $productId = $existing[0]['id'];
                }

                $this->supabase->insert('sale_items', [
                    'financial_record_id' => $record['id'],
                    'product_id' => $productId,
                    'quantity' => $item['qty'],
                    'total_price' => $item['total']
                ]);
            }
        }

        return redirect()->route('finance.cash-out.index');
    }

    public function cashOutEdit($id)
    {
        $userId = auth()->id();
        
        $saleItems = $this->supabase->select('sale_items', ['*'], ['id' => "eq.{$id}"], 1);
        if (empty($saleItems)) {
            abort(404);
        }
        
        $si = $saleItems[0];
        $records = $this->supabase->select('financial_records', ['*'], ['id' => "eq.{$si['financial_record_id']}", 'user_id' => "eq.{$userId}"], 1);
        if (empty($records)) {
            abort(404);
        }
        
        $products = $this->supabase->select('products', ['*'], ['id' => "eq.{$si['product_id']}"], 1);
        
        $item = (object) $si;
        $item->financialRecord = (object) $records[0];
        $item->financialRecord->transaction_date = Carbon::parse($records[0]['transaction_date']);
        $item->product = (object) ($products[0] ?? ['name' => 'Unknown']);

        $productsList = $this->supabase->select('products', ['*'], ['user_id' => "eq.{$userId}"]);
        $allProducts = collect($productsList)->map(fn($p) => (object) $p);

        return view('edit_pengeluaran', ['item' => $item, 'products' => $allProducts]);
    }

    public function cashOutUpdate(Request $request, $id)
    {
        $userId = auth()->id();
        
        $request->validate([
            'date' => 'required|date',
            'qty' => 'required|numeric|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $totalPrice = $request->price * $request->qty;

        // Update sale item
        $this->supabase->update('sale_items', [
            'quantity' => $request->qty,
            'total_price' => $totalPrice,
        ], ['id' => "eq.{$id}"]);

        // Get the sale item to find the record
        $saleItems = $this->supabase->select('sale_items', ['*'], ['id' => "eq.{$id}"], 1);
        if (!empty($saleItems)) {
            $recordId = $saleItems[0]['financial_record_id'];
            
            // Sum all items for this record
            $allItems = $this->supabase->select('sale_items', ['total_price'], ['financial_record_id' => "eq.{$recordId}"]);
            $newTotal = array_sum(array_column($allItems, 'total_price'));
            
            $this->supabase->update('financial_records', [
                'transaction_date' => $request->date,
                'amount' => $newTotal,
                'cash_amount' => $newTotal,
            ], ['id' => "eq.{$recordId}", 'user_id' => "eq.{$userId}"]);
        }

        return redirect()->route('finance.cash-out.index')->with('success', 'Data pengeluaran berhasil diperbarui');
    }

    public function cashOutDestroy($id)
    {
        $userId = auth()->id();
        
        // Get the sale item first
        $saleItems = $this->supabase->select('sale_items', ['*'], ['id' => "eq.{$id}"], 1);
        if (empty($saleItems)) {
            abort(404);
        }
        
        $recordId = $saleItems[0]['financial_record_id'];
        
        // Delete the sale item
        $this->supabase->delete('sale_items', ['id' => "eq.{$id}"]);
        
        // Check remaining items
        $remaining = $this->supabase->select('sale_items', ['id'], ['financial_record_id' => "eq.{$recordId}"]);
        
        if (empty($remaining)) {
            // Delete the record if no items left
            $this->supabase->delete('financial_records', ['id' => "eq.{$recordId}", 'user_id' => "eq.{$userId}"]);
        } else {
            // Update total
            $allItems = $this->supabase->select('sale_items', ['total_price'], ['financial_record_id' => "eq.{$recordId}"]);
            $newTotal = array_sum(array_column($allItems, 'total_price'));
            
            $this->supabase->update('financial_records', [
                'amount' => $newTotal,
            ], ['id' => "eq.{$recordId}", 'user_id' => "eq.{$userId}"]);
        }

        return redirect()->route('finance.cash-out.index')->with('success', 'Data pengeluaran berhasil dihapus');
    }

    public function expenseAnalysis()
    {
        $userId = auth()->id();
        
        // Get expense records with dates
        $expenseRecords = $this->supabase->select('financial_records', ['id', 'amount', 'transaction_date'], ['user_id' => "eq.{$userId}", 'type' => 'eq.out']);
        $totalExpense = array_sum(array_column($expenseRecords, 'amount'));

        // Monthly aggregation
        $months = [];
        $expenseData = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->translatedFormat('M');
            $year = $date->year;
            $month = $date->month;
            
            $monthlyTotal = 0;
            foreach ($expenseRecords as $r) {
                $txDate = Carbon::parse($r['transaction_date']);
                if ($txDate->year == $year && $txDate->month == $month) {
                    $monthlyTotal += $r['amount'];
                }
            }
            
            $expenseData[] = $monthlyTotal;
        }

        // Top Expense Items from sale_items
        $topExpenseLabels = collect([]);
        $topExpenseData = collect([]);
        
        if (!empty($expenseRecords)) {
            $recordIds = array_column($expenseRecords, 'id');
            $allSaleItems = [];
            
            foreach ($recordIds as $rid) {
                $items = $this->supabase->select('sale_items', ['product_id', 'quantity'], ['financial_record_id' => "eq.{$rid}"]);
                $allSaleItems = array_merge($allSaleItems, $items);
            }
            
            // Group by product_id
            $grouped = [];
            foreach ($allSaleItems as $si) {
                $pid = $si['product_id'];
                if (!isset($grouped[$pid])) {
                    $grouped[$pid] = ['product_id' => $pid, 'qty' => 0];
                }
                $grouped[$pid]['qty'] += $si['quantity'];
            }
            
            // Sort and take top 5
            usort($grouped, fn($a, $b) => $b['qty'] <=> $a['qty']);
            $top5 = array_slice($grouped, 0, 5);
            
            foreach ($top5 as $item) {
                $products = $this->supabase->select('products', ['name'], ['id' => "eq.{$item['product_id']}"], 1);
                $topExpenseLabels->push($products[0]['name'] ?? 'Unknown');
                $topExpenseData->push($item['qty']);
            }
        }

        return view('analisis_pengeluaran', compact(
            'totalExpense', 'months', 'expenseData',
            'topExpenseLabels', 'topExpenseData'
        ));
    }

    public function profile()
    {
        return view('profile_pengguna');
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $userId = $user->id;

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'password' => 'nullable|min:6',
        ]);

        // Check unique email/username via Supabase
        $existingEmail = $this->supabase->select('users', ['id'], ['email' => "eq.{$request->email}"]);
        if (!empty($existingEmail) && $existingEmail[0]['id'] != $userId) {
            return back()->withErrors(['email' => 'Email sudah digunakan.']);
        }

        $existingUsername = $this->supabase->select('users', ['id'], ['username' => "eq.{$request->username}"]);
        if (!empty($existingUsername) && $existingUsername[0]['id'] != $userId) {
            return back()->withErrors(['username' => 'Username sudah digunakan.']);
        }

        $updateData = [
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('profile_photos', 'public');
            $updateData['profile_photo'] = $path;
        }

        $this->supabase->update('users', $updateData, ['id' => "eq.{$userId}"]);

        return back()->with('success', 'Profil berhasil diperbarui!');
    }
}
