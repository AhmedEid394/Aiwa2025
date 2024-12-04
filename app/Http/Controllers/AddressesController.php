<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $addresses = null;

            if ($user instanceof User) {
                $addresses = Address::where('user_id', $user->user_id)
                    ->where('user_type', 'user')
                    ->get();
            } else {
                $addresses = Address::where('user_id', $user->provider_id)
                    ->where('user_type', 'provider')
                    ->get();
            }

            return response()->json($addresses);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch addresses', 'details' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'city' => 'required|string',
                'street' => 'required|string',
                'building' => 'required|string',
                'apartment' => 'nullable|string',
                'location_mark' => 'nullable|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
            ]);

            $user = $request->user();

            $address = DB::transaction(function () use ($validatedData, $user) {
                return Address::create([
                    'user_id' => $user instanceof User? $user->user_id: $user->provider_id,
                    'user_type' => $user instanceof User ? 'user' : 'provider',
                    'city' => $validatedData['city'],
                    'street' => $validatedData['street'],
                    'building' => $validatedData['building'],
                    'apartment' => $validatedData['apartment'],
                    'location_mark' => $validatedData['location_mark'],
                    'latitude' => $validatedData['latitude'],
                    'longitude' => $validatedData['longitude'],
                ]);
            });

            return response()->json($address, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create address', 'details' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $address = Address::find($id);
            if (!$address) {
                return response()->json(['error' => 'Address not found'], 404);
            }

            return response()->json($address);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch address', 'details' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $address = Address::find($id);
            if (!$address) {
                return response()->json(['error' => 'Address not found'], 404);
            }

            $user = $request->user();
            if ($user->user_id !== $address->user_id) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $validatedData = $request->validate([
                'city' => 'required|string',
                'street' => 'required|string',
                'building' => 'required|string',
                'apartment' => 'nullable|string',
                'location_mark' => 'nullable|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
            ]);

            DB::transaction(function () use ($address, $validatedData) {
                $address->update($validatedData);
            });

            return response()->json($address);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update address', 'details' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $address = Address::where('address_id',$id)->first();
            if (!$address) {
                return response()->json(['error' => 'Address not found'], 404);
            }


            DB::transaction(function () use ($address) {
                $address->delete();
            });

            return response()->json(['message' => 'Address deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete address', 'details' => $e->getMessage()], 500);
        }
    }
}
