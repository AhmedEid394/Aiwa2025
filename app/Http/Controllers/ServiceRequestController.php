<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ServiceRequestController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,user_id',
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'date_of_done' => 'required|date',
                'location' => 'required|string',
                'expected_cost' => 'required|numeric|min:0',
                'pictures' => 'nullable|array',
                'status' => 'required|string|in:pending,accepted,rejected,completed',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $serviceRequest = ServiceRequest::create($validatedData);

        return response()->json($serviceRequest, 201);
    }

    public function show($id)
    {
        $serviceRequest = ServiceRequest::with('user')->find($id);
        if (!$serviceRequest) {
            return response()->json(['error' => 'Service request not found'], 404);
        }
        return response()->json($serviceRequest, 201);
    }

    public function update(Request $request, $id)
    {
        $serviceRequest = ServiceRequest::find($id);
        if (!$serviceRequest) {
            return response()->json(['error' => 'Service request not found'], 404);
        }

        try {
            $validatedData = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'date_of_done' => 'sometimes|date',
                'location' => 'sometimes|string',
                'expected_cost' => 'sometimes|numeric|min:0',
                'pictures' => 'nullable|array',
                'status' => 'sometimes|string|in:pending,accepted,rejected,completed',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $serviceRequest->update($validatedData);

        return response()->json($serviceRequest, 201);
    }

    public function destroy($id)
    {
        $serviceRequest = ServiceRequest::find($id);
        if (!$serviceRequest) {
            return response()->json(['error' => 'Service request not found'], 404);
        }
        $serviceRequest->delete();
        return response()->json(null, 201);
    }

    public function index(Request $request)
    {
        $query = ServiceRequest::with('user');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $serviceRequests = $query->paginate(15);
        return response()->json($serviceRequests, 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $serviceRequest = ServiceRequest::find($id);
        if (!$serviceRequest) {
            return response()->json(['error' => 'Service request not found'], 404);
        }

        try {
            $validatedData = $request->validate([
                'status' => 'required|string|in:pending,accepted,rejected,completed',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $serviceRequest->update($validatedData);

        return response()->json($serviceRequest, 201);
    }
}
