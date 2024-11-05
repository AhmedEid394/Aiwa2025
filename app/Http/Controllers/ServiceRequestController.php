<?php

namespace App\Http\Controllers;

use App\Events\ServiceRequested;
use App\Events\ServiceRequestStatusUpdated;
use App\Models\ServiceProvider;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ServiceRequestController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'sub_category_id' => 'required|exists:sub_categories,sub_category_id',
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

        $serviceRequest = ServiceRequest::create($validatedData);
        event(new ServiceRequested($serviceRequest));
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
        $oldStatus = $serviceRequest->status;
        $serviceRequest->update($validatedData);
        if ($oldStatus !== $serviceRequest->status) {
            event(new ServiceRequestStatusUpdated($serviceRequest));
        }
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
        $user = auth()->user();

        // Determine user ID based on user type
        if ($user instanceof ServiceProvider) {
            $userId = $user->provider_id;
        } elseif ($user instanceof User) {
            $userId = $user->user_id;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Start query with user relationship loaded
        $query = ServiceRequest::all();

        // Apply additional filters if provided in the request
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Paginate results
        $serviceRequests = $query->paginate(15);

        return response()->json($serviceRequests, 200);
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
        event(new ServiceRequestStatusUpdated($serviceRequest));

        return response()->json($serviceRequest, 201);
    }
}
