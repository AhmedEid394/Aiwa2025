<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Models\ServiceProvider;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'service_id' => 'required|exists:services,service_id',
                'add_ons' => 'nullable|array',
                'building_number' => 'required|string',
                'apartment' => 'required|string',
                'location_mark' => 'required|string',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'booking_date' => 'required|date',
                'booking_time' => 'required|date_format:H:i',
                'service_price' => 'required|numeric|min:0',
                'total_price' => 'required|numeric|min:0',
                'promo_code' => 'nullable|string',
                'status' => 'required|in:request,accepted,rejected,done',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
        
        $user = auth()->user();

        if ($user instanceof ServiceProvider) {
            $validatedData['user_type'] = 'Provider';
            $validatedData['user_id'] = $user->provider_id;
        } elseif ($user instanceof User) {
            $validatedData['user_type'] = 'user';
            $validatedData['user_id'] = $user->user_id;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $booking = Booking::create($validatedData);

        return response()->json($booking, 201);
    }

    public function show($id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        // Load the appropriate relationship based on user_type
        if ($booking->user_type === 'user') {
            $booking->load('user');
        } else {
            $booking->load('provider');
        }
        $booking->load('service');

        return response()->json($booking, 200);
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
                'building_number' => 'sometimes|string',
                'apartment' => 'sometimes|string',
                'location_mark' => 'sometimes|string',
                'latitude' => 'sometimes|numeric',
                'longitude' => 'sometimes|numeric',
                'booking_date' => 'sometimes|date',
                'booking_time' => 'sometimes|date_format:H:i',
                'service_price' => 'sometimes|numeric|min:0',
                'total_price' => 'sometimes|numeric|min:0',
                'promo_code' => 'nullable|string',
                'status' => 'sometimes|in:request,accepted,rejected,done',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $booking->update($validatedData);

        return response()->json($booking, 200);
    }

    public function destroy($id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }
        $booking->delete();
        return response()->json(null, 204);
    }

        public function index()
    {
        $user = auth()->user();
        
        // Initialize the query with service relation
        $query = Booking::with(['service', 'service.provider']);
        
        // Check user type and filter accordingly
        if ($user instanceof User) {
            $query->where('user_id', $user->user_id)->where('user_type', 'user');
        } elseif ($user instanceof ServiceProvider) {
            $query->where('user_id', $user->provider_id)->where('user_type', 'Provider');
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Get paginated results
        $bookings = $query->paginate(15);
        
        return response()->json($bookings, 200);
    }
}
