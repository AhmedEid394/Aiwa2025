<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\CloudImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    protected $cloudImageService;
    public function __construct(CloudImageService $cloudImageService)
    {
        $this->cloudImageService = $cloudImageService;
    }
    public function store(Request $request)
    {
        Log::info('ServiceController@store', ['request' => $request->all()]);
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'sub_category_id' => 'required|exists:sub_categories,sub_category_id',
                'description' => 'required|string',
                'service_fee' => 'required|numeric|min:0',
                'pictures' => 'array|max:5',
                'pictures.*' => 'image|mimes:jpeg,png,jpg|max:5120', // 5MB
                 'add_ons' => 'nullable|array',
                'sale_amount' => 'nullable|numeric|min:0',
                'sale_percentage' => 'nullable|numeric|min:0|max:100',
                'down_payment' => 'nullable|numeric|min:0',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'building' => 'required|string',
                'apartment' => 'required|string',
                'location_mark' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
        $validatedData['provider_id'] = auth()->user()->provider_id;
        // Handle file uploads
        $uploadedPictures = [];
        if ($request->hasFile('pictures')) {
            foreach ($request->file('pictures') as $picture) {
                $uploadedPicture = $this->cloudImageService->upload($picture->path());
                $uploadedPictures[] = $uploadedPicture['secure_url'];
            }
        }
        $validatedData['pictures'] = $uploadedPictures;
        $service = Service::create($validatedData);

        return response()->json($service, 201);
    }

    public function show($id)
    {
        $service = Service::with(['SubCategory', 'Provider'])->find($id);
        if (!$service) {
            return response()->json(['error' => 'Service not found'], 404);
        }
        return response()->json($service, 200);
    }

    public function update(Request $request, $id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        try {
            $validatedData = $request->validate([
                'title' => 'sometimes|string|max:255',
                'sub_category_id' => 'sometimes|exists:sub_categories,sub_category_id',
                'provider_id' => 'sometimes|exists:service_providers,provider_id',
                'description' => 'sometimes|string',
                'service_fee' => 'sometimes|numeric|min:0',
                'pictures' => 'nullable|array',
                'add_ons' => 'nullable|array',
                'sale_amount' => 'nullable|numeric|min:0',
                'sale_percentage' => 'nullable|numeric|min:0|max:100',
                'down_payment' => 'nullable|numeric|min:0',
                'latitude' => 'sometimes|numeric',
                'longitude' => 'sometimes|numeric',
                'building' => 'sometimes|string',
                'apartment' => 'sometimes|string',
                'location_mark' => 'sometimes|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $service->update($validatedData);

        return response()->json($service, 200);
    }

    public function destroy($id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['error' => 'Service not found'], 404);
        }
        $service->delete();
        return response()->json(null, 204);
    }

    public function index(Request $request)
    {
        $query = Service::with(['SubCategory', 'Provider']);

        // Apply filters if provided
        if ($request->has('sub_category_id')) {
            $query->where('sub_category_id', $request->sub_category_id);
        }

        if ($request->has('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        $services = $query->get();

        // Transforming the response with comprehensive details
        $response = [
            'data' => $services->map(function ($service) {
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
                    'created_at'=> $service->created_at,
                    'sub_category' => [
                        'sub_category_id' => $service->SubCategory->sub_category_id ?? null,
                        'name' => $service->SubCategory->name ?? null,
                        'image' => $service->SubCategory->image ?? null,
                    ],
                    'provider' => [
                        'provider_id' => $service->Provider->provider_id ?? null,
                        'f_name' => $service->Provider->f_name ?? null,
                        'l_name' => $service->Provider->l_name ?? null,
                        'company_name' => $service->Provider->company_name ?? null,
                        'profile_photo' => $service->Provider->profile_photo ?? null,
                    ],
                ];
            }),
            'success' => true,
        ];

        return response()->json($response, 200, [], JSON_UNESCAPED_SLASHES);
    }


    public function search(Request $request)
    {
        $query = Service::query();

        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'LIKE', "%{$keyword}%")
                    ->orWhere('description', 'LIKE', "%{$keyword}%");
            });
        }

        if ($request->has('min_price')) {
            $query->where('service_fee', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('service_fee', '<=', $request->max_price);
        }

        if ($request->has('latitude') && $request->has('longitude') && $request->has('radius')) {
            $lat = $request->latitude;
            $lon = $request->longitude;
            $radius = $request->radius;

            $query->whereRaw("(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < ?", [$lat, $lon, $lat, $radius]);
        }

        $services = $query->with(['SubCategory', 'Provider'])->get();
        return response()->json(['data' => $services, 'success' => true], 200, ['Content-Type' => 'application/vnd.api+json'],  JSON_UNESCAPED_SLASHES);
    }

}
