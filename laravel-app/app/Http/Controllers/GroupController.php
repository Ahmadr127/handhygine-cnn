<?php

namespace App\Http\Controllers;

use App\Models\MonitoringGroup;
use App\Models\Camera;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GroupController extends Controller
{
    public function index()
    {
        $groups = MonitoringGroup::withCount('cameras')->orderBy('id')->get();
        return view('groups.index', compact('groups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_grup' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'lokasi'    => 'nullable|string|max:100',
        ]);

        $group = MonitoringGroup::create($validated);

        return redirect()->route('groups.index')
            ->with('success', "Grup '{$group->nama_grup}' berhasil ditambahkan.");
    }

    public function show(MonitoringGroup $group)
    {
        $group->load('cameras.zones');
        $available_cameras = Camera::whereNull('group_id')->get();
        return view('groups.show', compact('group', 'available_cameras'));
    }

    public function destroy(MonitoringGroup $group)
    {
        // Stop grup di AI Service jika aktif
        try {
            Http::timeout(5)->post(config('services.handhygiene-cnn.url') . "/api/groups/{$group->id}/stop");
        } catch (\Exception $e) {}

        $group->delete();
        return redirect()->route('groups.index')->with('success', 'Grup berhasil dihapus.');
    }

    public function assignCamera(Request $request, MonitoringGroup $group)
    {
        $validated = $request->validate([
            'camera_id' => 'required|exists:cameras,id',
        ]);

        $camera = Camera::findOrFail($validated['camera_id']);
        $camera->update(['group_id' => $group->id]);

        return back()->with('success', "Kamera '{$camera->nama_kamera}' ditambahkan ke grup.");
    }

    public function removeCamera(MonitoringGroup $group, Camera $camera)
    {
        if ($camera->group_id === $group->id) {
            $camera->update(['group_id' => null]);
        }
        return back()->with('success', "Kamera dikeluarkan dari grup.");
    }

    public function start(MonitoringGroup $group)
    {
        try {
            $response = Http::timeout(10)
                ->post(config('services.handhygiene-cnn.url') . "/api/groups/{$group->id}/start");

            if ($response->successful()) {
                $group->update(['aktif' => true]);
                // Set aktif semua kamera di grup
                $group->cameras()->update(['aktif' => true]);
                return response()->json(['success' => true, 'message' => 'Grup dimulai']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'AI Service tidak tersedia'], 503);
        }

        return response()->json(['success' => false, 'message' => 'Gagal memulai grup'], 500);
    }

    public function stop(MonitoringGroup $group)
    {
        try {
            Http::timeout(10)->post(config('services.handhygiene-cnn.url') . "/api/groups/{$group->id}/stop");
        } catch (\Exception $e) {}

        $group->update(['aktif' => false]);
        $group->cameras()->update(['aktif' => false]);
        return response()->json(['success' => true, 'message' => 'Grup dihentikan']);
    }
}
