<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PermissionType;

class PermissionTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $permissionTypes = PermissionType::all();
        return response()->json($permissionTypes, 201);
        
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|unique:permission_types|max:255',
                'description' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $permissionType = PermissionType::create($validatedData);
        return response()->json($permissionType, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $permissionType = PermissionType::find($id);

        if (!$permissionType) {
            return response()->json(['message' => 'Permission type not found'], 404);
        }
        return response()->json($permissionType, 201);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|unique:permission_types,name|max:255',
                'description' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422); // Return validation errors
        }
    
        $permissionType = PermissionType::find($id);
        if (!$permissionType) {
            return response()->json(['message' => 'Permission type not found'], 404); // Not found
        }
    
        $permissionType->update($validatedData); // Update with validated data
    
        return response()->json([
            'message' => 'Permission type updated successfully',
            'data' => $permissionType
        ], 200); // Successful response
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $permissionType = PermissionType::find($id);
    
        if (!$permissionType) {
            return response()->json([
                'message' => 'Permission type not found'
            ], 404);
        }
    
        try {
            $permissionType->delete();
    
            return response()->json([
                'message' => 'Permission type deleted successfully'
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the permission type',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    

}
