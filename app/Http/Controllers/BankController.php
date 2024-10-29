<?php
namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BankController extends Controller
{
    public function index()
    {
        $banks = Bank::all();
        return response()->json($banks);
    }

    public function getWallets()
    {
        $wallets = Bank::where('bank_short', 'MIDG')->get();
        return response()->json($wallets);
    }

    public function getBanks()
    {
        $banks = Bank::where('bank_short', '!=', 'MIDG')->get();
        return response()->json($banks);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'bank_en' => 'required|string',
                'bank_ar' => 'required|string',
                'bank_short' => 'required|string',
                'logo' => 'required|string'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $bank = Bank::create($validatedData);
        return response()->json($bank, 201);
    }

    public function show($id)
    {
        $bank = Bank::find($id);
        if (!$bank) {
            return response()->json(['error' => 'Bank not found'], 404);
        }
        return response()->json($bank);
    }

    public function update(Request $request, $id)
    {
        $bank = Bank::find($id);
        if (!$bank) {
            return response()->json(['error' => 'Bank not found'], 404);
        }

        try {
            $validatedData = $request->validate([
                'bank_en' => 'sometimes|string',
                'bank_ar' => 'sometimes|string',
                'bank_short' => 'sometimes|string',
                'logo' => 'sometimes|string'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $bank->update($validatedData);
        return response()->json($bank);
    }

    public function destroy($id)
    {
        $bank = Bank::find($id);
        if (!$bank) {
            return response()->json(['error' => 'Bank not found'], 404);
        }
        $bank->delete();
        return response()->json(null, 201);
    }
}
