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
    protected const MAX_RETRIES = 3;
    protected const INITIAL_DELAY = 1; // seconds
    protected const MAX_DELAY = 5; // seconds

    public function __construct(CloudImageService $cloudImageService)
    {
        $this->cloudImageService = $cloudImageService;
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
//                'sub_category_id' => 'required|exists:sub_categories,sub_category_id',
                'description' => 'required|string',
                'service_fee' => 'required|numeric|min:0',
                'pictures' => 'array|max:5',
                'pictures.*' => 'string',
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
        $validatedData['sub_category_id'] = $request->user()->sub_category_id;
        try {
            $uploadedPictures = [];

            if (!empty($validatedData['pictures'])) {
                foreach ($validatedData['pictures'] as $index => $base64Image) {
                    // Skip if already a URL
                    if (filter_var($base64Image, FILTER_VALIDATE_URL)) {
                        $uploadedPictures[] = $base64Image;
                        continue;
                    }

                    try {
                        // Initial delay between uploads to prevent rate limiting
                        if ($index > 0) {
                            usleep(250000); // 0.25 second delay
                        }

                        // Process base64 image
                        $imageData = $this->processBase64Image($base64Image);

                        // Create temporary file
                        $tempFile = $this->createTempFile($imageData);

                        try {
                            // Upload with retry logic
                            $result = $this->uploadWithRetry($tempFile, $index);

                            if ($result) {
                                $uploadedPictures[] = $result;
                                // Short delay after successful upload
                                usleep(250000); // 0.25 second delay
                            } else {
                                throw new \Exception('Upload failed - empty result');
                            }

                        } catch (\Exception $e) {
                            if (stripos($e->getMessage(), 'timeout') !== false ||
                                stripos($e->getMessage(), 'abort') !== false) {
                                Log::error('Upload timeout for image ' . $index . ': ' . $e->getMessage());
                                return response()->json([
                                    'error' => 'Upload timeout',
                                    'details' => 'The upload process took too long. Please try again.'
                                ], 408);
                            }
                            throw $e;
                        } finally {
                            // Clean up temporary file
                            if (file_exists($tempFile)) {
                                unlink($tempFile);
                            }
                        }

                    } catch (\Exception $e) {
                        Log::error('Failed to process image at index ' . $index . ': ' . $e->getMessage());
                        return response()->json([
                            'error' => 'Failed to process image at position ' . ($index + 1),
                            'details' => $e->getMessage()
                        ], 500);
                    }
                }
            }

            // Update pictures in validated data
            $validatedData['pictures'] = $uploadedPictures;
            $validatedData['provider_id'] = auth()->user()->provider_id;

            try {
                // Create service with shorter timeout
                $service = Service::create($validatedData);
                return response()->json($service, 201);

            } catch (\Exception $e) {
                Log::error('Service creation database error: ' . $e->getMessage());
                throw new \Exception('Failed to save service to database');
            }

        } catch (\Exception $e) {
            Log::error('Service creation error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create service',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload file with improved retry logic
     *
     * @param string $tempFile
     * @param int $index
     * @return string|null
     * @throws \Exception
     */
    private function uploadWithRetry(string $tempFile, int $index): ?string
    {
        $attempts = 0;
        $delay = self::INITIAL_DELAY;
        $lastException = null;

        while ($attempts < self::MAX_RETRIES) {
            try {
                $result = $this->cloudImageService->upload($tempFile);

                if (isset($result['secure_url'])) {
                    return $result['secure_url'];
                }

                throw new \Exception('Invalid upload result');

            } catch (\Exception $e) {
                $lastException = $e;
                $attempts++;

                // Check for specific timeout or abort errors
                if (stripos($e->getMessage(), 'timeout') !== false ||
                    stripos($e->getMessage(), 'abort') !== false) {
                    Log::warning("Upload timeout on attempt {$attempts} for image {$index}");
                    throw new \Exception('Upload timeout error: ' . $e->getMessage());
                }

                if ($attempts < self::MAX_RETRIES) {
                    Log::warning("Upload attempt {$attempts} failed for image {$index}: {$e->getMessage()}");

                    // Progressive delay with cap
                    $delay = min($delay * 2, self::MAX_DELAY);
                    usleep($delay * 1000000); // Convert to microseconds
                }
            }
        }

        throw new \Exception('Max retry attempts reached: ' . $lastException->getMessage());
    }

    /**
     * Process base64 image string
     *
     * @param string $base64Image
     * @return string
     * @throws \Exception
     */
    private function processBase64Image(string $base64Image): string
    {
        // Remove data URI scheme if present
        if (strpos($base64Image, 'data:image') !== false) {
            $base64Image = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
        }

        // Decode base64
        $imageData = base64_decode($base64Image);
        if (!$imageData) {
            throw new \Exception('Failed to decode base64 image');
        }

        return $imageData;
    }

    /**
     * Create temporary file for image
     *
     * @param string $imageData
     * @return string
     * @throws \Exception
     */
    private function createTempFile(string $imageData): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'service_img_') . '.jpg';
        if (!file_put_contents($tempFile, $imageData)) {
            throw new \Exception('Failed to create temporary file');
        }

        return $tempFile;
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

        // Get location parameters from request
        $userLat = $request->input('latitude');
        $userLng = $request->input('longitude');
        $maxDistance = $request->input('distance'); // Default to 50km if not provided

        // Apply sub_category filter
        if ($request->has('sub_category_id')) {
            $query->where('sub_category_id', $request->sub_category_id);

            // Add distance calculation using Haversine formula if coordinates are provided
            if ($userLat && $userLng) {
                $query->selectRaw("
                *,
                (
                    6371 * acos(
                        cos(radians(?)) *
                        cos(radians(latitude)) *
                        cos(radians(longitude) - radians(?)) +
                        sin(radians(?)) *
                        sin(radians(latitude))
                    )
                ) AS distance", [$userLat, $userLng, $userLat]
                )
                    ->whereRaw("
                6371 * acos(
                    cos(radians(?)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(latitude))
                ) <= ?", [$userLat, $userLng, $userLat, $maxDistance]
                    )
                    ->orderBy('distance', 'asc');
            }
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
                    'sale_amount' => $service->sale_amount??null,
                    'sale_percentage' => $service->sale_percentage??null,
                    'down_payment' => $service->down_payment??null,
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
