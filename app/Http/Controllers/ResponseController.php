<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ResponseController extends Controller
{
    public static function successResponse (String $message, $data, $statusCode = 201)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    public static function failsResponse (String $message, $reason, $data, $statusCode = 201)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'reason' => $reason,
            'data' => $data
        ], $statusCode);
    }
}
