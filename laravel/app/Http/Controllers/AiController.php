<?php

namespace App\Http\Controllers;

use App\Contracts\Ai\AiChat;
use App\Http\Requests\AiChatRequest;
use Illuminate\Http\JsonResponse;

class AiController extends Controller
{
    public function chat(AiChatRequest $request, AiChat $ai): JsonResponse
    {
        return response()->json(['reply' => $ai->chat([$request->toDto()])]);
    }
}
