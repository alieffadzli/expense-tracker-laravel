<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrganizationController extends Controller
{
    public function createOrganization (Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:organizations,name',
            'description' => 'required|string'
        ]);

        if ($validate->fails())
            return ResponseController::failsResponse('Validation error', $validate->errors(), null, 422);

        $data = $validate->validated();
        $currentUser = AuthController::getCurrentUser();
        $createOrganization = User::query()->where('id', $currentUser->id)
                                            ->first()
                                            ->organizations()->create($data);

        return ResponseController::successResponse('Create organization successful', $createOrganization, 201);
    }

    public function updateOrganization (Request $request, $id)
    {   
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string'
        ]);

        if ($validate->fails())
            return ResponseController::failsResponse('Validation error', $validate->errors(), null, 422);

        $data = $validate->validated();
        $currentUser = AuthController::getCurrentUser();

        if (!(Organization::query()->where('id', $id)->first())) {
            return ResponseController::failsResponse('No organization found', '', null, 401);
        }
        $updateOrganization = User::query()->where('id', $currentUser->id)
                                            ->first()
                                            ->organizations()->where('id', $id)
                                            ->update($data);

        return ResponseController::successResponse('Update organization successful', $updateOrganization, 201);
    }

    public function getOrganizationBranches ()
    {
        $organizationBranches = Organization::query()->get();

        return ResponseController::successResponse('Success get all organizations', $organizationBranches, 200);
    }
}
