<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;

class WalletController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validate([
            'total_amount' => 'required|numeric',
            'available_amount' => 'required|numeric',
        ]);

        $validated['provider_id'] = auth()->user->provider_id;
        $wallet = Wallet::create($validated);
        return response()->json(['message' => 'Wallet created successfully', 'wallet' => $wallet]);
    }

    public function update(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'total_amount' => 'nullable|numeric|min:0',
            'available_amount' => 'nullable|numeric',
            'provider_id' => 'required'
        ]);

        // Find the wallet associated with this provider
        $wallet = Wallet::where('provider_id', $validated['provider_id'])->first();

        // Check if the wallet exists
        if (!$wallet) {
            return response()->json([
                'message' => 'Wallet not found'
            ], 404);
        }

        // Prepare update data
        $updateData = [];

        // Handle total_amount (if provided)
        if (isset($validated['total_amount'])) {
            $updateData['total_amount'] = $wallet->total_amount + $validated['total_amount'];
        }

        // Handle available_amount (increment/decrement)
        if (isset($validated['available_amount'])) {
            $updateData['available_amount'] = $wallet->available_amount + $validated['available_amount'];
        }

        // Update the wallet
        $wallet->update($updateData);

        return response()->json([
            'message' => 'Wallet updated successfully',
            'wallet' => $wallet
        ]);
    }

    public function show()
    {
        // Get the authenticated provider
        $provider = auth()->user()->provider_id;

        $wallet = Wallet::where('provider_id', $provider)->with('Provider')->first();

        // Check if the wallet exists
        if (!$wallet) {
            return response()->json([
                'message' => 'Wallet not found for this provider'
            ], 404);
        }

        return response()->json([
            'wallet' => $wallet
        ], 201);
    }
}
