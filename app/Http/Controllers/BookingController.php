<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,user_id',
                'service_id' => 'required|exists:services,service_id',
                'add_ons' => 'nullable|array',
                'location' => 'required|string',
                'building_number' => 'required|string',
                'apartment' => 'nullable|string',
                'location_mark' => 'nullable|string',
                'booking_date' => 'required|date',
                'booking_time' => 'required|date_format:H:i',
                'service_price' => 'required|numeric|min:0',
                'total_price' => 'required|numeric|min:0',
                'promo_code' => 'nullable|string',
                'status' => 'required|in:pending,confirmed,completed,cancelled',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $booking = Booking::create($validatedData);

        return response()->json($booking, 201);
    }

    public function show($id)
    {
        $booking = Booking::with(['user', 'service'])->find($id);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }
        return response()->json($booking, 201);
    }

    public function update(Request $request, $id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        try {
            $validatedData = $request->validate([
                'add_ons' => 'nullable|array',
                'location' => 'sometimes|string',
                'building_number' => 'sometimes|string',
                'apartment' => 'nullable|string',
                'location_mark' => 'nullable|string',
                'booking_date' => 'sometimes|date',
                'booking_time' => 'sometimes|date_format:H:i',
                'service_price' => 'sometimes|numeric|min:0',
                'total_price' => 'sometimes|numeric|min:0',
                'promo_code' => 'nullable|string',
                'status' => 'sometimes|in:pending,confirmed,completed,cancelled',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $booking->update($validatedData);

        return response()->json($booking, 201);
    }

    public function destroy($id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }
        $booking->delete();
        return response()->json(null, 201);
    }

    public function index()
    {
        $bookings = Booking::with(['user', 'service'])->paginate(15);
        return response()->json($bookings, 201);
    }
}