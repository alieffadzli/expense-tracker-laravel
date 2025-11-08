<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    public function createBranch (Request $request)
    {
        $validate = Validator::make($request->all(), [
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:225|unique:branches,name',
            'description' => 'required|string'
        ]);

        if ($validate->fails())
            return ResponseController::failsResponse('Validation error', $validate->errors(), null, 422);

        $data = $validate->validated();

        $currentUser = AuthController::getCurrentUser();
        $createBranch = Organization::query()->where('id', $data['organization_id'])->first()
                                             ->branches()->create([
                                                                'author_id' => $currentUser->id,
                                                                'name' => $data['name'],
                                                                'description' => $data['description']
                                                            ]);

        return ResponseController::successResponse('Create branch successful', $createBranch, 201);
    }

    public function updateBranch (Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:225',
            'description' => 'required|string'
        ]);

        if ($validate->fails())
            return ResponseController::failsResponse('Validation error', $validate->errors(), null, 422);

        $data = $validate->validated();

        $currentUser = AuthController::getCurrentUser();
        $createBranch = Organization::query()->where('id', $data['organization_id'])->first()
                                             ->branches()->where('id', $id)->first()
                                             ->update([
                                                    'author_id' => $currentUser->id,
                                                    'name' => $data['name'],
                                                    'description' => $data['description']
                                                ]);

        return ResponseController::successResponse('Create branch successful', $createBranch, 201);
    }
}
