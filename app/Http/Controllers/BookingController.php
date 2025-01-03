<?php

namespace App\Http\Controllers;

use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\ChatController;
use App\Events\BookingStatusUpdated;
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

        // Reload the booking with relations
        $booking = Booking::with(['service', 'service.provider'])
            ->where('booking_id', $booking->booking_id)
            ->first();

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
            'status' => 'sometimes|in:request,accepted,accepted but not payed,rejected,done',
        ]);
    } catch (ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
    }

    $oldStatus = $booking->status;
    $booking->update($validatedData);

    // Check for status change
    if ($oldStatus !== $booking->status) {
        event(new BookingStatusUpdated($booking));

        // If status is updated to 'done'
        if ($booking->status === 'done') {
            // Generate a random transaction reference
            $transactionRef = 'TR-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));

            // Create transaction record
            $transactionController = new TransactionController();
            $transactionRequest = new Request([
                'transaction_type' => 'cash_in',
                'service_id' => $booking->service_id,
                'booking_id' => $booking->booking_id,
                'amount' => $booking->total_price,
                'status' => 'completed',
                'transaction_reference' => $transactionRef
            ]);
            $transactionController->store($transactionRequest);

            // Get provider_id from service
            $providerId = $booking->service->provider_id;

            // Decrement available amount and increment total amount
            $walletController = new WalletController();
            $walletRequest = new Request([
                'total_amount' => $booking->total_price,
                'available_amount' => -$booking->total_price,
                'provider_id' => $providerId
            ]);
            $walletController->update($walletRequest);
        }

        // If status is updated to 'accepted'
        if ($booking->status === 'accepted') {
            // Calculate provider's earnings (86% of total price)
            $providerEarnings = $booking->total_price * 0.86;

            // Get provider_id from service
            $providerId = $booking->service->provider_id;

            // Increment available amount
            $walletController = new WalletController();
            $walletRequest = new Request([
                'available_amount' => $providerEarnings,
                'provider_id' => $providerId
            ]);
            $walletController->update($walletRequest);

            // Start chat between user and provider
            $chatController = new ChatController();
            $chatRequest = new Request([
                'user_id' => $booking->user_id,
                'user_type' => $booking->user_type,
                'provider_id' => $providerId
            ]);
            $chatController->startChat($chatRequest);
        }
    }


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
        $query = Booking::with(['service' => function($query) {
            $query->with('Provider'); // Assuming there's a provider relationship in Service model
        }]);

        // Check user type and filter accordingly
        if ($user instanceof User) {
            $query->where('user_id', $user->user_id)->where('user_type', 'user');
        } elseif ($user instanceof ServiceProvider) {
            $query->where('user_id', $user->provider_id)->where('user_type', 'Provider');
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get paginated results
        $bookings = $query->orderByRaw('created_at')->get();

        return response()->json(['data' => $bookings,'success' => true],200, ['Content-Type' => 'application/vnd.api+json'],  JSON_UNESCAPED_SLASHES);
    }

    public function getProviderWorkOrders()
    {

        $provider = auth()->user();

        if (!($provider instanceof ServiceProvider)) {
            return response()->json(['error' => 'Unauthorized. Only providers can access work orders'], 401);
        }

        $workOrders = Booking::whereHas('service', function ($query) use ($provider) {
            $query->where('provider_id', $provider->provider_id);
        })
            ->with(['service', 'service.provider'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $workOrders,'success' => true],200, ['Content-Type' => 'application/vnd.api+json'],  JSON_UNESCAPED_SLASHES);
    }


}
