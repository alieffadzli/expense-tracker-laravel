<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InitialBalanceController extends Controller
{
    public function createInitialBalance (Request $request)
    {
        $validate = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'balance' => 'required|integer',
            'balance_limit' => 'required|integer',
            'notes' => 'required|string'
        ]);

        if ($validate->fails()) return ResponseController::failsResponse(
            'Validation Error', $validate->errors(), null, 422
        );

        $data = $validate->validated();
        if (!(Branch::query()->where('id', $data['branch_id'])->first())) return ResponseController::failsResponse(
            'No branch found', '', null, 401
        );

        $createInitialBalance = Branch::query()->where('id', $data['branch_id'])->first()
                                               ->initialBalance()->create($data);
        
        return ResponseController::successResponse('Create initial balance successful', $createInitialBalance, 201);
    }
}
