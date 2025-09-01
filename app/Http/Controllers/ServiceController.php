<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    private $imageBaseUrl;

    public function __construct()
    {
        $this->imageBaseUrl = url('storage'); // e.g., http://127.0.0.1:8000/storage
    }

    // Helper to prepend base URL only if not full URL
    private function fullImageUrl($image)
    {
        if (!$image) return null;
        if (str_starts_with($image, 'http')) return $image;
        return $this->imageBaseUrl . '/' . $image;
    }

    // READ all services
    public function index()
    {
        $services = Service::all()->map(function ($service) {
            $service->image = $this->fullImageUrl($service->image);
            return $service;
        });

        return response()->json($services);
    }

    // CREATE service
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'icon' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('services', 'public');
        }

        $service = Service::create([
            'name' => $request->name,
            'icon' => $request->icon,
            'image' => $imagePath,
        ]);

        $service->image = $this->fullImageUrl($service->image);

        return response()->json($service, 201);
    }

    // READ single service
    public function show(Service $service)
    {
        $service->image = $this->fullImageUrl($service->image);
        return response()->json($service);
    }

    // UPDATE service
    public function update(Request $request, Service $service)
    {
        $request->validate([
            'name' => 'required|string',
            'icon' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ]);

        // If new image uploaded, delete old and store new
        if ($request->hasFile('image')) {
            if ($service->image) {
                $oldPath = str_replace($this->imageBaseUrl . '/', '', $service->image);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $service->image = $request->file('image')->store('services', 'public');
        }

        $service->name = $request->name;
        $service->icon = $request->icon;
        $service->save();

        $service->image = $this->fullImageUrl($service->image);

        return response()->json($service);
    }

    // DELETE service
    public function destroy(Service $service)
    {
        if ($service->image) {
            $oldPath = str_replace($this->imageBaseUrl . '/', '', $service->image);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $service->delete();
        return response()->json(['message' => 'Service deleted successfully']);
    }
}
