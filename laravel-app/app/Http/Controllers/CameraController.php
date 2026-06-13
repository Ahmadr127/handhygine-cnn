<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CameraController extends Controller
{
    public function index()
    {
        $cameras = Camera::withCount('monitoringLogs')->orderBy('id')->get();
        return view('cameras.index', compact('cameras'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kamera' => 'required|string|max:100',
            'tipe'        => 'required|in:usb,rtsp,file',
            'source'      => 'required|string',
        ]);

        // Simpan ke DB lokal (karena AI service & Laravel pakai DB yang sama, tidak perlu sync via API)
        $camera = Camera::create($validated);

        return redirect()->route('cameras.index')
            ->with('success', "Kamera '{$camera->nama_kamera}' berhasil ditambahkan.");
    }

    public function destroy(Camera $camera)
    {
        // Stop di AI Service dulu
        try {
            Http::timeout(5)->delete(config('services.handhygiene-cnn.url') . "/api/cameras/{$camera->id}");
        } catch (\Exception $e) {}

        $camera->delete();
        return redirect()->route('cameras.index')
            ->with('success', 'Kamera berhasil dihapus.');
    }

    public function start(Camera $camera)
    {
        try {
            $response = Http::timeout(10)
                ->post(config('services.handhygiene-cnn.url') . "/api/cameras/{$camera->id}/start");

            if ($response->successful()) {
                $camera->update(['aktif' => true]);
                return response()->json(['success' => true, 'message' => 'Kamera dimulai']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'AI Service tidak tersedia'], 503);
        }

        return response()->json(['success' => false, 'message' => 'Gagal memulai kamera'], 500);
    }

    public function stop(Camera $camera)
    {
        try {
            Http::timeout(10)->post(config('services.handhygiene-cnn.url') . "/api/cameras/{$camera->id}/stop");
        } catch (\Exception $e) {}

        $camera->update(['aktif' => false]);
        return response()->json(['success' => true, 'message' => 'Kamera dihentikan']);
    }

    public function scanUsb()
    {
        try {
            $response = Http::timeout(15)->get(config('services.handhygiene-cnn.url') . '/api/cameras/scan/usb');
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => 'AI Service tidak tersedia'], 503);
        }
    }

    // ── Zone Management ──────────────────────────────────────────────────────

    public function zones(Camera $camera)
    {
        $zones = Zone::where('camera_id', $camera->id)->get();
        return view('cameras.zones', compact('camera', 'zones'));
    }

    public function storeZone(Request $request, Camera $camera)
    {
        $validated = $request->validate([
            'nama_zona'      => 'required|string|max:50',
            'tipe_zona'      => 'required|in:sanitizer,wastafel,pintu',
            'polygon_points' => 'required|array|min:3',
        ]);

        if (!$camera->group_id) {
            return response()->json(['success' => false, 'message' => 'Kamera belum masuk ke grup mana pun.'], 400);
        }

        $zone = Zone::create([
            'group_id'       => $camera->group_id,
            'camera_id'      => $camera->id,
            'nama_zona'      => $validated['nama_zona'],
            'tipe_zona'      => $validated['tipe_zona'],
            'polygon_points' => $validated['polygon_points'],
        ]);

        // Sync ke AI Service
        try {
            Http::timeout(5)->post(
                config('services.handhygiene-cnn.url') . "/api/cameras/{$camera->id}/zones",
                [
                    'group_id'       => $zone->group_id,
                    'nama_zona'      => $zone->nama_zona,
                    'tipe_zona'      => $zone->tipe_zona,
                    'polygon_points' => $zone->polygon_points,
                ]
            );
        } catch (\Exception $e) {}

        return response()->json(['success' => true, 'zone' => $zone]);
    }

    public function destroyZone(Zone $zone)
    {
        $zone->delete();
        return response()->json(['success' => true]);
    }
}
