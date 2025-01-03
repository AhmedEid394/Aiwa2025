<?php

namespace App\Http\Controllers;

use App\Models\ServiceProvider;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;


class TransactionController extends Controller
{
    // Display a list of transactions
    public function index()
    {
        $user = auth()->user();
        $userId=null;
        if ($user instanceof ServiceProvider) {
            $userId= $user->provider_id;
        } elseif ($user instanceof User) {
            $userId= $user->user_id;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $query = Transaction::with(['service']);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        return response()->json($transactions);
    }

    // Store a new transaction
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'transaction_type' => 'required|in:cash_out,cash_in',
                'service_id' => 'required|exists:services,service_id',
                'booking_id' => 'nullable|exists:bookings,booking_id',
                'amount' => 'required|numeric',
                'status' => 'required|in:pending,completed,failed,refunded',
                'transaction_reference' => 'nullable|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
        $user = auth()->user();
        if ($user instanceof ServiceProvider) {
            $validatedData['user_type'] = 'Provider';
            $validatedData['user_id']=$user->provider_id;
        } elseif ($user instanceof User) {
            $validatedData['user_type'] = 'user';
            $validatedData['user_id']=$user->user_id;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $transaction = Transaction::create($validatedData);

        return response()->json(['message' => 'Transaction created', 'data' => $transaction], 201);
    }

    // Display a specific transaction
    public function show($id)
    {
        $transaction = Transaction::find($id);
        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }
        return response()->json($transaction, 201);
    }

    // Update a transaction
    public function update(Request $request, $id)
    {
        $transaction = Transaction::find($id);
        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }
        try {
            $validatedData = $request->validate([
                'amount' => 'sometimes|required|numeric',
                'status' => 'sometimes|required|in:pending,completed,failed,refunded',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $transaction->update($validatedData);
        return response()->json(['message' => 'Transaction updated', 'data' => $transaction, 201]);
    }

    // Delete a transaction
    public function destroy($id)
    {
        $transaction = Transaction::find($id);
        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }
        $transaction->delete();

        return response()->json(null, 201);
    }
}
