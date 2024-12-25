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
            $tempFiles = [];

            // Use a single temporary directory for all images to improve cleanup management
            $tempDir = sys_get_temp_dir() . '/' . Str::uuid();
            mkdir($tempDir);

            try {
                foreach ($photoTypes as $photoType) {
                    if (!empty($validatedData[$photoType])) {
                        $decodedData = base64_decode($validatedData[$photoType], true);
                        if ($decodedData === false) {
                            throw new ValidationException("Invalid base64 data for $photoType");
                        }

                        // Save base64 image temporarily with faster write operation
                        $tempPath = $tempDir . '/' . $photoType . '.jpg';
                        file_put_contents($tempPath, $decodedData);
                        $tempFiles[] = $tempPath;

                        // Upload to cloud
                        $uploadResult = $this->cloudImageService->upload($tempPath);
                        if (!isset($uploadResult['secure_url'])) {
                            throw new \Exception("Upload failed for $photoType");
                        }

                        $uploadedUrls[$photoType] = $uploadResult['secure_url'];
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error during file upload: ' . $e->getMessage());
                // Clean up on error
                foreach ($tempFiles as $tempFile) {
                    @unlink($tempFile);
                }
                @rmdir($tempDir);
                throw $e;
            }

            // Clean up temp files and directory
            foreach ($tempFiles as $tempFile) {
                @unlink($tempFile);
            }
            @rmdir($tempDir);

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
                'data' => $paper,
                'success' => true
            ], 200);

        } catch (\Exception $e) {
            Log::error('Upload failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Upload failed: ' . $e->getMessage(),
                'success' => false
            ], 500);
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

}
