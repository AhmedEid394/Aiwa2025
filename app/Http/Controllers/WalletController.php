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
        // Get the authenticated provider's ID
        $providerId = auth()->user()->provider_id;

        // Find the wallet associated with this provider
        $wallet = Wallet::where('provider_id', $providerId)->first();

        // Check if the wallet exists
        if (!$wallet) {
            return response()->json([
                'message' => 'Wallet not found'
            ], 404);
        }

        // Validate the request data
        $validated = $request->validate([
            'total_amount' => 'required|numeric|min:0',
            'available_amount' => 'required|numeric|min:0|max:' . $request->total_amount,
        ]);

        // Update the wallet
        $wallet->update($validated);

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
