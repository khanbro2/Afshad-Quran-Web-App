<?php

namespace App\Http\Controllers;

use App\Models\QuranChatLog;
use Illuminate\View\View;

class QuranChatPageController extends Controller
{
    public function index(): View
    {
        return view('ai.quran-chat');
    }

    public function history(): View
    {
        return view('ai.quran-chat-history', [
            'logs' => QuranChatLog::query()
                ->latest()
                ->paginate(30),
        ]);
    }
}
