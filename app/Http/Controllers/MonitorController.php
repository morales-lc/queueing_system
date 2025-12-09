<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\QueueTicket;
use App\Models\MonitorMedia;

class MonitorController extends Controller
{
    public function index()
    {
        $cashierCounters = Counter::where('type', 'cashier')->orderBy('id')->get();
        $registrarCounters = Counter::where('type', 'registrar')->orderBy('id')->get();

        $nowServing = [
            'cashier' => [],
            'registrar' => [],
        ];

        foreach ($cashierCounters as $counter) {
            $nowServing['cashier'][$counter->id] = QueueTicket::where('counter_id', $counter->id)
                ->where('status', 'serving')
                ->latest()
                ->first();
        }
        foreach ($registrarCounters as $counter) {
            $nowServing['registrar'][$counter->id] = QueueTicket::where('counter_id', $counter->id)
                ->where('status', 'serving')
                ->latest()
                ->first();
        }

        // Get active media ordered by order column
        $mediaItems = MonitorMedia::where('is_active', true)
            ->orderBy('order')
            ->get();

        return view('monitor.index', compact('cashierCounters', 'registrarCounters', 'nowServing', 'mediaItems'));
    }

    public function mediaFragment()
    {
        $mediaItems = MonitorMedia::where('is_active', true)
            ->orderBy('order')
            ->get();
        return view('monitor._media', compact('mediaItems'));
    }
}
