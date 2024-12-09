<?php

namespace App\Services;

use Cloudinary\Api\ApiResponse;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Exception\ApiError;

class CloudImageService
{
    protected $cloud_name='dvajqycpe';
    protected $api_key='654326293351769';
    protected $api_secret='_eYHmps4mIv8RQW1qEbv7Hnpncw';
    public function __construct()
    {
        // Load Cloudinary configuration from environment variables
        Configuration::instance([
            'cloud' => [
                'cloud_name' => $this->cloud_name,
                'api_key' => $this->api_key,
                'api_secret' => $this->api_secret,
            ],
            'url' => [
                'secure' => true,
            ],
        ]);
    }

    /**
     * Upload an image to Cloudinary.
     *
     * @param string $imagePath The local path or URL of the image to upload.
     * @param array $options Optional parameters for the upload.
     * @return ApiResponse The upload result.
     * @throws ApiError If the upload fails.
     */
    public function upload(string $imagePath, array $options = []): ApiResponse
    {
        $uploadApi = new UploadApi();
        return $uploadApi->upload($imagePath, $options);
    }
}
