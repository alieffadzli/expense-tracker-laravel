<?php

namespace App\Http\Controllers;

use App\enum\TransactionType;
use App\Models\DailyBalance;
use App\Models\InitialBalance;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        if ($validate->fails()) 
            return ResponseController::failsResponse('Validation error', $validate->errors(), null, 422);

        $data = $validate->validated();

        /**
         * if transactions is not exists -> transaksi belum pernah dibuat dari branch tersebut
         */
        $getTransactions = Transaction::query()->where('branch_id', $data['branch_id'])->get();
        if (count($getTransactions) < 1) {
            $getBalance = InitialBalance::query()->where('branch_id', $data['branch_id'])->first()->balance;
            $totalBalance = $getBalance + ($data['transaction_type'] == 'income' ? $data['amount'] : -$data['amount']);

            if ($totalBalance < 0)
                return ResponseController::failsResponse('Failed to create transaction', 'Transaction is out of limit from balance', null, 422);

            $createTransaction = Transaction::query()->create($data);
            $createDailyBalance = DailyBalance::query()
                                                ->create([
                                                    'closing_balance' => $totalBalance,
                                                    'transaction_id' => $createTransaction->id,
                                                    'branch_id' => $data['branch_id']
                                                ]);

            return ResponseController::successResponse('Create transaction successful', [
                'new_transaction' => $createTransaction,
                'new_daily_balance' => $createDailyBalance
            ], 201);
        }

        /**
         * else jika transaksi pernah dibuat dari branch tersebut
         */
        $getLatestDailyBalance = DailyBalance::query()->where('branch_id', $data['branch_id'])->latest('id')->first();
        $totalBalance = $getLatestDailyBalance->closing_balance + ($data['transaction_type'] == 'income' ? $data['amount'] : -$data['amount']); 

        if ($totalBalance < 0)
            return ResponseController::failsResponse('Failed to create transaction', 'Transaction is out of limit from balance', null, 422);

        /**
         * is Date greather then yesterday
         */
        $isDateGteYesterday = false;
        $getFirstDailyBalance = DailyBalance::query()->first()->created_at;
        $isSameDays = Carbon::parse($getFirstDailyBalance)->format('Y-m-d') == Carbon::now()->format('Y-m-d');
        if (!$isSameDays) {
            $getLatestDailyBalance = DailyBalance::query()->where('branch_id', $data['branch_id'])->latest('id')->first();
            $getDate = Carbon::parse($getLatestDailyBalance->updated_at)->format('Y-m-d');
        
            if ($getDate < Carbon::now()->format('Y-m-d')) $isDateGteYesterday = !$isDateGteYesterday;            
        }

        /**
         * insert transaction
         */
        $createTransaction = Transaction::query()->create($data);

        /**
         * insert daily_balance
         */
        $getDailyBalance = DailyBalance::query()->where('branch_id', $data['branch_id'])->latest('id')->first();
        $createDailyBalance = DailyBalance::query()
                                            ->create([
                                                'opening_balance' => $isDateGteYesterday ?
                                                                     ($getDailyBalance->closing_balance ? $getDailyBalance->closing_balance : 0) : 
                                                                     ($getDailyBalance->opening_balance ? $getDailyBalance->opening_balance : 0),
                                                'closing_balance' => $totalBalance,
                                                'transaction_id' => $createTransaction->id,
                                                'branch_id' => $data['branch_id']
                                            ]);

        return ResponseController::successResponse('Create transactions successful', [
            'is_date_gte_yesterday' => $isDateGteYesterday,
            'new_transaction' => $createTransaction,
            'new_daily_balance' => $createDailyBalance
        ], 201);

        /**
         * DEBUG
         */
        // return ResponseController::successResponse('Create transactions successful', [
        //     'carbon' => Carbon::parse($getFirstDailyBalance)->format('Y-m-d') == Carbon::now()->format('Y-m-d'),
        //     'isDateGteYesterday' => $isDateGteYesterday
        // ], 201);
    }

    public function getTransactions (Request $request)
    {
        $hasBranchId = $request->branchId;
        $hasDate = Carbon::parse($request->date);

        $transactions = Transaction::query()
                                    ->when($hasBranchId, function ($query) use ($hasBranchId) {
                                        $query->with('dailyBalances')
                                                ->where('branch_id', $hasBranchId);
                                    })
                                    ->when($hasDate, function ($query) use ($hasDate) {
                                        $query->with('dailyBalances')
                                                ->whereDate('created_at', '=', $hasDate);
                                    })
                                    ->get();

        return ResponseController::successResponse('Success get transactions', [
            'get transactions' => $transactions,
            'date' => $hasDate
        ], 200);
    }


}
