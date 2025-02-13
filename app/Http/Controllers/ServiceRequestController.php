<?php

namespace App\Http\Controllers;

use App\Events\ServiceRequested;
use App\Events\ServiceRequestStatusUpdated;
use App\Models\ServiceProvider;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\Booking;
use App\Services\CloudImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ServiceRequestController extends Controller
{
    protected $cloudImageService;
    public function __construct(CloudImageService $cloudImageService)
    {
        $this->cloudImageService = $cloudImageService;
    }
    public function store(Request $request)
    {
        try {
            Log::info('Validating service request', ['request' => $request->all()]);
            $validatedData = $request->validate([
                'sub_category_id' => 'required|exists:sub_categories,sub_category_id',
                'title' => 'required|string|max:255',
                'date_of_done' => 'required|date',
                'description' => 'required|string',
                'location' => 'required|string',
                'building_number' => 'required|string',
                'apartment' => 'required|string',
                'location_mark' => 'required|string',
                'expected_cost' => 'required|numeric|min:0',
                'pictures' => 'array|max:5',
                'pictures.*' => 'image|mimes:jpeg,png,jpg|max:5120', // 5MB
                'status' => 'required|string|in:request,accepted,rejected,done,accepted but not payed',
            ]);
        } catch (ValidationException $e) {
            Log::error('Failed to validate service request', ['error' => $e->errors()]);
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
        $uploadedPictures = [];
        if ($request->hasFile('pictures')) {
            foreach ($request->file('pictures') as $picture) {
                $uploadedPicture = $this->cloudImageService->upload($picture->path());
                $uploadedPictures[] = $uploadedPicture['secure_url'];
            }
        }
        $validatedData['pictures'] = $uploadedPictures;
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
                'status' => 'sometimes|string|in:request,accepted,rejected,done,accepted but not payed',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
        $validatedData['provider_id'] = auth()->user()->provider_id;
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
                'status' => 'required|string|in:request,accepted,rejected,done,accepted but not payed',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
        $validatedData['provider_id'] = auth()->user()->provider_id;
        // Update service request status
        $serviceRequest->update($validatedData);
        event(new ServiceRequestStatusUpdated($serviceRequest));

        return response()->json([
            'service_request' => $serviceRequest
        ], 201);
    }

    public function getProviderAcceptedRequests(Request $request)
    {
        $user = auth()->user();
        if (!$user instanceof ServiceProvider) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $serviceRequests = ServiceRequest::where('provider_id', $user->provider_id)
            ->where('status', 'accepted')
            ->orWhere('status', 'accepted but not payed')
            ->latest()
            ->get();

        $service=[];
        foreach ($serviceRequests as $serviceRequest) {
            $serviceRequest->user=$serviceRequest->user();
            $service[]=$serviceRequest;
        }
        return response()->json([
            'data' => $service,
            'status' => 200,
        ], 200);
    }

    public function getAuthUserRequests(Request $request)
    {
        $user = auth()->user();
        if (!$user instanceof User) {
            $serviceRequests = ServiceRequest::where('user_id', $user->provider_id)->with('Provider')->
            where('user_type','Provider')->latest()->get();
            return response()->json([
                'data' => $serviceRequests,
                'status' => 200,
            ], 200);
        }

        $serviceRequests = ServiceRequest::where('user_id', $user->user_id)->
        where('user_type','user')->latest()->get();

        return response()->json([
            'data' => $serviceRequests,
            'status' => 200,
        ], 200);

    }

    public function checkRequestStatus(Request $request,$id)
    {
        $serviceRequest = ServiceRequest::find($id);
        if (!$serviceRequest) {
            return response()->json(['error' => 'Service request not found'], 404);
        }
        return response()->json([
            'status' => $serviceRequest->status,
            'service_request' => $serviceRequest
        ], 200);
    }

}
