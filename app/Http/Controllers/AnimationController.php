<?php

namespace App\Http\Controllers;

use App\Models\Animation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class AnimationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function animationIndex()
    {
        Log::info("--- ANIMATION INDEX ---");

        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Animation $animation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Animation $animation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Animation $animation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Animation $animation)
    {
        //
    }
}
