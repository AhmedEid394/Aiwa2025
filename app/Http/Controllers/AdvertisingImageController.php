<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdvertisingImage;

class AdvertisingImageController extends Controller
{

  public function store(Request $request)
{
    $request->validate([
        'image_path' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    // Check if the request contains a file.
    if (!$request->hasFile('image_path')) {
        return response()->json([
            'success' => false,
            'message' => 'No file uploaded.',
        ], 400);
    }

    // Get the uploaded file.
    $file = $request->file('image_path');

    // Convert the image to a Base64 string.
    $base64Image = base64_encode(file_get_contents($file->getRealPath()));

    // Store the Base64 string in the database.
    $advertisingImage = AdvertisingImage::create([
        'image_path' => $base64Image,
        'status' => $request->status ?? 1,
    ]);

    // Return JSON response with the saved data.
    return response()->json([
        'success' => true,
        'message' => 'Image uploaded successfully as Base64.',
        'data' => $advertisingImage,
    ], 201);
}
public function update(Request $request, $id)
{
    // Find the AdvertisingImage by ID
    $advertisingImage = AdvertisingImage::find($id);

    // Check if the AdvertisingImage exists
    if (!$advertisingImage) {
        return response()->json([
            'success' => false,
            'message' => 'Advertising image not found.',
        ], 404);
    }

    // Validate the request data
    $request->validate([
        'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'status' => 'nullable|boolean',
    ]);

    // Check if a new image was uploaded
    if ($request->hasFile('image_path')) {
        $file = $request->file('image_path');
        $base64Image = base64_encode(file_get_contents($file->getRealPath()));
        $advertisingImage->image_path = $base64Image;
    }

    // Update the status if provided
    if ($request->has('status')) {
        $advertisingImage->status = $request->status;
    }

    // Save the updated record
    $advertisingImage->save();

    // Return JSON response with the updated data
    return response()->json([
        'success' => true,
        'message' => 'Image updated successfully.',
        'data' => $advertisingImage,
    ], 200);
}

public function destroy($id)
{
    $advertisingImage = AdvertisingImage::find($id);

    // Check if the AdvertisingImage exists
    if (!$advertisingImage) {
        return response()->json([
            'success' => false,
            'message' => 'Advertising image not found.',
        ], 404);
    }

    // Delete the image record from the database
    $advertisingImage->delete();

    // Return JSON response indicating successful deletion
    return response()->json([
        'success' => true,
        'message' => 'Image deleted successfully.',
    ], 200);
}

public function index()
{
    // Retrieve all advertising images from the database.
    $advertisingImages = AdvertisingImage::all();

    // Return the images as a JSON response.
    return response()->json([
        'success' => true,
        'message' => 'Advertising images retrieved successfully.',
        'data' => $advertisingImages,
    ], 200);
}

}
