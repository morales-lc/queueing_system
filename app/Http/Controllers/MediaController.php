<?php

namespace App\Http\Controllers;

use App\Models\MonitorMedia;
use App\Events\MediaUpdated;
use App\Models\MonitorSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index()
    {
        $media = MonitorMedia::orderBy('order')->get();
        $settings = MonitorSetting::first();
        return view('media.index', compact('media', 'settings'));
    }

    public function updateMarquee(Request $request)
    {
        $data = $request->validate([
            'marquee_text' => 'required|string|max:2000',
        ]);

        $settings = MonitorSetting::firstOrCreate(['id' => 1]);
        $settings->marquee_text = $data['marquee_text'];
        $settings->save();

        event(new MediaUpdated('marquee'));

        return back()->with('success', 'Marquee text updated successfully!');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,mp4,avi,mov,webm|max:102400', // 100MB max
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        
        // Determine media type
        $imageExtensions = ['jpg', 'jpeg', 'png'];
        $videoExtensions = ['mp4', 'avi', 'mov', 'webm'];
        $gifExtensions = ['gif'];
        
        if (in_array(strtolower($extension), $imageExtensions)) {
            $type = 'image';
        } elseif (in_array(strtolower($extension), $videoExtensions)) {
            $type = 'video';
        } elseif (in_array(strtolower($extension), $gifExtensions)) {
            $type = 'gif';
        } else {
            return back()->withErrors(['file' => 'Unsupported file type.']);
        }

        // Generate unique filename
        $filename = Str::uuid() . '.' . $extension;
        
        // Store in public/media directory
        $path = $file->storeAs('media', $filename, 'public');

        // Get the highest order number and add 1
        $maxOrder = MonitorMedia::max('order') ?? -1;

        MonitorMedia::create([
            'filename' => $filename,
            'original_filename' => $originalName,
            'type' => $type,
            'path' => $path,
            'order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        event(new MediaUpdated('stored'));
        return back()->with('success', 'Media uploaded successfully!');
    }

    public function destroy($id)
    {
        $media = MonitorMedia::findOrFail($id);
        
        // Delete file from storage
        Storage::disk('public')->delete($media->path);
        
        $media->delete();
        event(new MediaUpdated('deleted'));
        return back()->with('success', 'Media deleted successfully!');
    }

    public function toggleActive($id)
    {
        $media = MonitorMedia::findOrFail($id);
        $media->is_active = !$media->is_active;
        $media->save();
        event(new MediaUpdated('toggled'));
        return back()->with('success', 'Media status updated!');
    }

    public function updateOrder(Request $request)
    {
        $request->validate([
            'orders_json' => 'required|string',
        ]);

        $orders = json_decode($request->input('orders_json'), true);
        if (!is_array($orders)) {
            return back()->withErrors(['orders_json' => 'Invalid order data.']);
        }

        foreach ($orders as $id => $order) {
            MonitorMedia::where('id', (int)$id)->update(['order' => (int)$order]);
        }

        event(new MediaUpdated('reordered'));
        return back()->with('success', 'Order updated successfully!');
    }
}
