<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Announcement;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


// app/Http/Controllers/AnnouncementController.php
class AnnouncementController extends Controller
{
    // GET /api/announcements — public, lu par le bandeau
    public function index() {
        return response()->json(Announcement::active()->get());
    }

    // GET /api/admin/announcements — liste complète pour l'admin
    public function adminIndex() {
        return response()->json(Announcement::orderBy('order')->get());
    }

    // POST /api/admin/announcements
    public function store(Request $request) {
        $data = $request->validate(['message' => 'required|string|max:255', 'order' => 'integer']);
        return response()->json(Announcement::create($data), 201);
    }

    // PUT /api/admin/announcements/{id}
    public function update(Request $request, Announcement $announcement) {
        $data = $request->validate([
            'message' => 'string|max:255',
            'active'  => 'boolean',
            'order'   => 'integer',
        ]);
        $announcement->update($data);
        return response()->json($announcement);
    }

    // DELETE /api/admin/announcements/{id}
    public function destroy(Announcement $announcement) {
        $announcement->delete();
        return response()->json(null, 204);
    }
}