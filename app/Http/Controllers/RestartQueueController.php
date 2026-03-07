<?php


namespace App\Http\Controllers;

use App\Models\QueueTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestartQueueController extends Controller
{

    public function index()
    {
        $todayCount = QueueTicket::whereDate('created_at', today())->count();

        return view('operator.restart', compact('todayCount'));
    }

    public function restart(Request $request)
    {
        $request->validate([
            'confirm_text' => 'required|in:RESTART',
        ]);

        $deletedCount = QueueTicket::whereDate('created_at', today())->count();

        DB::transaction(function () {
            QueueTicket::whereDate('created_at', today())->delete();
        });

        return redirect()
            ->route('counter.index')
            ->with('status', "Queue restarted. Deleted {$deletedCount} ticket(s) for today.");
        
    }
}