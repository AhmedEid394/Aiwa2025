<?php

namespace App\Http\Controllers;

use App\Models\Paper;
use App\Services\CloudImageService;
use Illuminate\Http\Request;
use App\Models\ServiceProvider;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaperController extends Controller
{
    protected $cloudImageService;
    protected const MAX_RETRIES = 3;
    protected const INITIAL_DELAY = 1; // seconds
    protected const MAX_DELAY = 5; // seconds

    public function __construct(CloudImageService $cloudImageService)
    {
        $this->cloudImageService = $cloudImageService;
    }

    public function uploadPapers(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'front_photo' => 'nullable|string',
                'back_photo' => 'nullable|string',
                'criminal_record_photo' => 'nullable|string',
            ]);

            $user = auth()->user();

            if ($user instanceof ServiceProvider) {
                $userType = 'Provider';
                $userId = $user->provider_id;
            } elseif ($user instanceof User) {
                $userType = 'user';
                $userId = $user->user_id;
            } else {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $uploadedUrls = [];
            $photoTypes = ['front_photo', 'back_photo', 'criminal_record_photo'];

            // Create unique temporary directory
            $tempDir = sys_get_temp_dir() . '/' . Str::uuid();
            if (!mkdir($tempDir)) {
                throw new \Exception('Failed to create temporary directory');
            }

            try {
                foreach ($photoTypes as $index => $photoType) {
                    if (empty($validatedData[$photoType])) {
                        continue;
                    }

                    try {
                        // Initial delay between uploads to prevent rate limiting
                        if ($index > 0) {
                            usleep(250000); // 0.25 second delay
                        }

                        // Process and validate base64 image
                        $imageData = $this->processBase64Image($validatedData[$photoType], $photoType);

                        // Save to temporary file
                        $tempFile = $this->createTempFile($imageData, $tempDir, $photoType);

                        try {
                            // Upload with retry logic
                            $result = $this->uploadWithRetry($tempFile, $photoType);

                            if ($result) {
                                $uploadedUrls[$photoType] = $result;
                                // Short delay after successful upload
                                usleep(250000); // 0.25 second delay
                            } else {
                                throw new \Exception("Upload failed for {$photoType}");
                            }

                        } catch (\Exception $e) {
                            if (stripos($e->getMessage(), 'timeout') !== false ||
                                stripos($e->getMessage(), 'abort') !== false) {
                                Log::error("Upload timeout for {$photoType}: " . $e->getMessage());
                                throw new \Exception("Upload timeout for {$photoType}");
                            }
                            throw $e;
                        }

                    } catch (\Exception $e) {
                        Log::error("Failed to process {$photoType}: " . $e->getMessage());
                        throw new \Exception("Failed to process {$photoType}: " . $e->getMessage());
                    }
                }

                try {
                    // Save to database
                    $paper = Paper::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'user_type' => $userType,
                        ],
                        array_merge($uploadedUrls, [
                            'user_type' => $userType,
                            'user_id' => $userId,
                        ])
                    );

                    return response()->json([
                        'message' => 'Papers uploaded successfully',
                        'data' => $paper,
                        'success' => true
                    ], 200);

                } catch (\Exception $e) {
                    Log::error('Database save error: ' . $e->getMessage());
                    throw new \Exception('Failed to save paper records: ' . $e->getMessage());
                }

            } catch (\Exception $e) {
                throw $e;
            } finally {
                // Clean up all temporary files and directory
                $this->cleanupTempFiles($tempDir);
            }

        } catch (\Exception $e) {
            Log::error('Papers upload failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Upload failed: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Upload file with improved retry logic
     *
     * @param string $tempFile
     * @param string $photoType
     * @return string|null
     * @throws \Exception
     */
    private function uploadWithRetry(string $tempFile, string $photoType): ?string
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

                throw new \Exception("Invalid upload result for {$photoType}");

            } catch (\Exception $e) {
                $lastException = $e;
                $attempts++;

                if (stripos($e->getMessage(), 'timeout') !== false ||
                    stripos($e->getMessage(), 'abort') !== false) {
                    Log::warning("Upload timeout on attempt {$attempts} for {$photoType}");
                    throw new \Exception("Upload timeout error for {$photoType}: " . $e->getMessage());
                }

                if ($attempts < self::MAX_RETRIES) {
                    Log::warning("Upload attempt {$attempts} failed for {$photoType}: {$e->getMessage()}");

                    // Progressive delay with cap
                    $delay = min($delay * 2, self::MAX_DELAY);
                    usleep($delay * 1000000); // Convert to microseconds
                }
            }
        }

        throw new \Exception("Max retry attempts reached for {$photoType}: " . $lastException->getMessage());
    }

    /**
     * Process base64 image string
     *
     * @param string $base64Image
     * @param string $photoType
     * @return string
     * @throws \Exception
     */
    private function processBase64Image(string $base64Image, string $photoType): string
    {
        // Remove data URI scheme if present
        if (strpos($base64Image, 'data:image') !== false) {
            $base64Image = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
        }

        // Decode base64
        $imageData = base64_decode($base64Image, true);
        if ($imageData === false) {
            throw new \Exception("Invalid base64 data for {$photoType}");
        }

        return $imageData;
    }

    /**
     * Create temporary file for image
     *
     * @param string $imageData
     * @param string $tempDir
     * @param string $photoType
     * @return string
     * @throws \Exception
     */
    private function createTempFile(string $imageData, string $tempDir, string $photoType): string
    {
        $tempFile = $tempDir . '/' . $photoType . '_' . Str::random(10) . '.jpg';
        if (!file_put_contents($tempFile, $imageData)) {
            throw new \Exception("Failed to create temporary file for {$photoType}");
        }

        return $tempFile;
    }

    /**
     * Clean up temporary files and directory
     *
     * @param string $tempDir
     * @return void
     */
    private function cleanupTempFiles(string $tempDir): void
    {
        if (is_dir($tempDir)) {
            $files = glob($tempDir . '/*');
            foreach ($files as $file) {
                @unlink($file);
            }
            @rmdir($tempDir);
        }
    }

    public function getPapers()
    {
        $user = auth()->user();

        if ($user instanceof ServiceProvider) {
            $user_type = 'Provider';
            $user_id = $user->provider_id;
        } elseif ($user instanceof User) {
            $user_type = 'user';
            $user_id = $user->user_id;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $papers = Paper::where('user_id', $user_id)
            ->where('user_type', $user_type)
            ->first();

        return response()->json([
            'data' => $papers,
            'success' => true
        ], 200, ['Content-Type' => 'application/vnd.api+json'],  JSON_UNESCAPED_SLASHES);
    }

    public function checkVerification()
    {
        $user = auth()->user();

        if ($user instanceof ServiceProvider) {
            // Check if provider has submitted and approved papers
            $verificationStatus = Paper::where('user_id', $user->provider_id)
                ->where('user_type', 'Provider')
                ->where('status', 'accepted')
                ->where('criminal_record_status', 'accepted')
                ->exists();

            return response()->json([
                'is_verified' => $verificationStatus,
                'success' => true
            ], 200, ['Content-Type' => 'application/vnd.api+json'],  JSON_UNESCAPED_SLASHES);
        } elseif ($user instanceof User) {
            // If you want to implement user verification logic
            // For now, returning false
            return response()->json([
                'is_verified' => false,
                'success' => true
            ], 200, ['Content-Type' => 'application/vnd.api+json'],  JSON_UNESCAPED_SLASHES);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function getUserPapers(Request $request, $id)
    {
        $papers = Paper::where('user_id', $id)
            ->where('user_type', 'Provider')
            ->first();

        return response()->json([
            'data' => $papers,
            'success' => true
        ], 200, ['Content-Type' => 'application/vnd.api+json'],  JSON_UNESCAPED_SLASHES);
    }

    public function getPendingPapers(Request $request)
    {
        // Retrieve all pending papers.
        $papers = Paper::where('status', 'pending')->get();
        $newPapers = [];
        // Loop through each paper and attach its ServiceProvider.
        foreach ($papers as $paper) {
            // Replace 'service_provider_id' with the actual field name if different.
            // Here we assume that the ServiceProvider model is imported and available.
            $serviceProvider = ServiceProvider::find($paper->user_id);

            // Option 1: Add a new property to the paper.
//            $paper->service_provider = $serviceProvider;

            // Option 2: If you want to format the output differently,
            // you could create a new array with the paper and its provider.
             $paper = [
                 'paper' => $paper,
                 'service_provider' => $serviceProvider
             ];
            $newPapers[] = $paper;
        }

        // Return the modified list of papers with their service providers.
        return response()->json([
            'data' => $newPapers,
            'success' => true
        ], 200, ['Content-Type' => 'application/vnd.api+json'], JSON_UNESCAPED_SLASHES);
    }

    public function changePapersStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'sometimes|string|in:accepted,rejected,pending',
            'notes' => 'nullable|string',
            'criminal_record_status' => 'sometimes|string|in:accepted,rejected,pending',
        ]);
        $paper = Paper::find($id);

        if (!$paper) {
            return response()->json(['error' => 'Paper not found'], 404);
        }


        $paper->update($request->all());

        return response()->json([
            'message' => 'Paper status updated successfully',
            'success' => true
        ], 200);
    }


}
