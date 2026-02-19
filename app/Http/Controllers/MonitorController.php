<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\QueueTicket;
use App\Models\MonitorMedia;
use App\Models\MonitorSetting;

class MonitorController extends Controller
{
    protected function todayTickets()
    {
        return QueueTicket::whereDate('created_at', today());
    }

    public function index()
    {
        $cashierCounters = Counter::where('type', 'cashier')->orderBy('id')->get();
        $registrarCounters = Counter::where('type', 'registrar')->orderBy('id')->get();

        $nowServing = [
            'cashier' => [],
            'registrar' => [],
        ];

        foreach ($cashierCounters as $counter) {
            $nowServing['cashier'][$counter->id] = $this->todayTickets()->where('counter_id', $counter->id)
                ->where('status', 'serving')
                ->latest()
                ->first();
        }
        foreach ($registrarCounters as $counter) {
            $nowServing['registrar'][$counter->id] = $this->todayTickets()->where('counter_id', $counter->id)
                ->where('status', 'serving')
                ->latest()
                ->first();
        }

        // Get active media ordered by order column
        $mediaItems = MonitorMedia::where('is_active', true)
            ->orderBy('order')
            ->get();

        $settings = MonitorSetting::first();
        $marqueeText = $settings->marquee_text ?? 'Welcome to Our Service Center! Please wait for your number to be called. Thank you for your patience and cooperation.';

        return view('monitor.index', compact('cashierCounters', 'registrarCounters', 'nowServing', 'mediaItems', 'marqueeText'));
    }

    public function marquee()
    {
        $settings = MonitorSetting::first();
        $marqueeText = $settings->marquee_text ?? 'Welcome to Our Service Center! Please wait for your number to be called. Thank you for your patience and cooperation.';

        return response()->json([
            'marqueeText' => $marqueeText,
        ]);
    }

    public function mediaFragment()
    {
        $mediaItems = MonitorMedia::where('is_active', true)
            ->orderBy('order')
            ->get();
        return view('monitor._media', compact('mediaItems'));
    }
}
