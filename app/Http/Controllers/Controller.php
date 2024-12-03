<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\User;
use App\Models\ServiceProvider;
use App\Models\Booking;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function login(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required|string',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        // First try to find a regular user
        $user = User::where('phone', $request->phone)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            $token = $user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'user' => $user,
                'token' => $token,
                'type' => 'user'
            ], 201);
        }

        // If no user found or password doesn't match, try service provider
        $serviceProvider = ServiceProvider::where('phone', $request->phone)->first();

        if ($serviceProvider && Hash::check($request->password, $serviceProvider->password)) {
            $token = $serviceProvider->createToken('auth_token')->plainTextToken;
            return response()->json([
                'user' => $serviceProvider,
                'token' => $token,
                'type' => 'provider'
            ], 201);
        }

        // If neither found or passwords don't match
        return response()->json([
            'error' => 'The provided credentials are incorrect.'
        ], 401);
    }

    public function getTopSuggestedServices()
    {
        // Base query with relationships
        $query = Service::with(['SubCategory', 'Provider']);

        // Analyze booking frequency for each service
        $topServicesByBookings = Booking::select('service_id')
            ->groupBy('service_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(10)
            ->pluck('service_id');

        // Calculate services with highest sale percentages or sale amounts
        $topServicesBySales = Service::select('service_id')
            ->whereNotNull('sale_percentage')
            ->orWhereNotNull('sale_amount')
            ->orderByRaw('COALESCE(sale_percentage, 0) DESC')
            ->orderByRaw('COALESCE(sale_amount, 0) DESC')
            ->limit(10)
            ->pluck('service_id');

        // Combine and prioritize services
        $suggestedServiceIds = $topServicesByBookings->merge($topServicesBySales)->unique();

        // Filter and order the services
        $suggestedServices = $query->whereIn('service_id', $suggestedServiceIds)
            ->orderByRaw('FIELD(service_id, ' . $suggestedServiceIds->implode(',') . ')')
            ->get();

        // Transform the response with comprehensive details
        $response = [
            'data' => $suggestedServices->map(function ($service) {
                return [
                    'service_id' => $service->service_id,
                    'title' => $service->title,
                    'description' => $service->description,
                    'service_fee' => $service->service_fee,
                    'pictures' => $service->pictures,
                    'add_ons' => $service->add_ons,
                    'sale_amount' => $service->sale_amount,
                    'sale_percentage' => $service->sale_percentage,
                    'down_payment' => $service->down_payment,
                    'latitude' => $service->latitude,
                    'longitude' => $service->longitude,
                    'building' => $service->building,
                    'apartment' => $service->apartment,
                    'location_mark' => $service->location_mark,
                    'sub_category' => [
                        'sub_category_id' => $service->SubCategory->sub_category_id ?? null,
                        'name' => $service->SubCategory->name ?? null,
                    ],
                    'provider' => $service->Provider,
                ];
            }),
        ];

        return response()->json(['data' => $response,'success' => true],200, ['Content-Type' => 'application/vnd.api+json'],  JSON_UNESCAPED_SLASHES);
    }
}
