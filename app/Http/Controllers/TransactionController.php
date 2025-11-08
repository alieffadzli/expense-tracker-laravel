<?php

namespace App\Http\Controllers;

use App\enum\TransactionType;
use App\Models\DailyBalance;
use App\Models\InitialBalance;
use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

class TransactionController extends Controller
{
    public function createTransaction (Request $request)
    {
        $validate = Validator::make($request->all(), [
            'initial_balance_id' => 'required|exists:initial_balances,id',
            'user_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'transaction_type' => ['required', new Enum(TransactionType::class)],
            'amount' => 'required|numeric',
            'notes' => 'nullable|string',
            'edit_history' => 'nullable|array'
        ]);

        /**
         * state from request
         */
        $data = $validate->validated();
        $branchId = $data['branch_id'];
        $transactionType = $data['transaction_type'];
        $transactionAmount = $data['amount'];
        
        /**
         * get daily balance
         */
        $getLtsDailyBalance = DailyBalance::query()->where('branch_id', $branchId)->latest('id')->first();
        $getLtsOpeningBalance = $getLtsDailyBalance?->opening_balance;
        $getLtsClosingBalance = $getLtsDailyBalance?->closing_balance;

        /**
         * get initial balance
         */
        $getInitialBalance = InitialBalance::query()->where('branch_id', $branchId)->first();
        $useFirstOrLatest = $getLtsClosingBalance ?? $getInitialBalance->balance;
        
        /**
         * calc closing balance
         */
        $calcClosingBalance = $useFirstOrLatest + ($transactionType == 'income' ? $transactionAmount : -$transactionAmount);
        if ($calcClosingBalance < 0) return ResponseController::failsResponse('Transaction failed', 'Out of limit', null, 422);

        /**
         * get first date daily balance
         */
        $dailyBalanceFirstDate = Carbon::parse($getInitialBalance->created_at)->format('Y-m-d');
        $isSameDays = $dailyBalanceFirstDate == Carbon::now()->format('Y-m-d');

        /**
         * is date end now?
         */
        $now = Carbon::now()->format('Y-m-d');
        $latestDateDailyBalance = Carbon::parse($getLtsDailyBalance->created_at)->format('Y-m-d');

        /**
         * insert new transaction && daily balance
         */
        try {
            DB::beginTransaction();
            $newTransaction = Transaction::query()->create($data);
            $newDailyBalance = Transaction::query()->where('id', $newTransaction->id)->latest('id')->first()
                                                    ->dailyBalances()->create([
                                                        'opening_balance' => $isSameDays ? 0 : ($latestDateDailyBalance < $now ? $getLtsClosingBalance : $getLtsOpeningBalance),
                                                        'closing_balance' => $calcClosingBalance,
                                                        'branch_id' => $data['branch_id']
                                                    ]);
    
            DB::commit();
            return ResponseController::successResponse('Create transactions successful', [
                'message' => 'success',
                'data' => [
                    'new_transaction' => $newTransaction,
                    'new_daily_balance' => $newDailyBalance,
                ]
            ], 201);

        } catch (Exception $err) {
            DB::rollBack();
            return ResponseController::failsResponse('Create transactions successful', $err->getMessage(), null, 422);
        }
    }

    public function getTransactions (Request $request)
    {
        $hasBranchId = $request->branch_id;
        $hasDate = Carbon::parse($request->date);
        $hasClosingBalance = $request->closing_balance;

        $transactions = Transaction::query()
                                    ->when($hasBranchId, function ($query) use ($hasBranchId) {
                                        $query->with('dailyBalances')
                                                ->where('branch_id', $hasBranchId);
                                    })
                                    ->when($hasDate, function ($query) use ($hasDate) {
                                        $query->with('dailyBalances')
                                                ->whereDate('created_at', '=', $hasDate);
                                    })
                                    ->when($hasClosingBalance, function ($query) use ($hasClosingBalance)  {
                                        $query->whereHas('dailyBalances', function ($query) use ($hasClosingBalance) {
                                            $query->where('closing_balance', '>=', $hasClosingBalance );
                                        });
                                    })
                                    ->get();

        return ResponseController::successResponse('Success get transactions', [
            'get transactions' => $transactions,
            'date' => $hasDate
        ], 200);
    }
}
