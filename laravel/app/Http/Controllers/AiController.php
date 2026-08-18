<?php

namespace App\Http\Controllers;

use App\Services\Ai\AiService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function chat(Request $request, AiService $ai)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $reply = $ai->chat([
            ['role' => 'user', 'content' => $data['message']],
        ]);

        return response()->json(['reply' => $reply]);
    }
}
