<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:categories',
                'image' => 'nullable|string',
                'description' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $category = Category::create($validatedData);

        return response()->json($category, 201);
    }

    public function show($id)
    {
        $category = Category::with('subCategories')->find($id);
        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }
        return response()->json($category, 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        try {
            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255|unique:categories,name,' . $id . ',category_id',
                'image' => 'nullable|string',
                'description' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $category->update($validatedData);

        return response()->json($category, 201);
    }

    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }
        $category->delete();
        return response()->json(null, 201);
    }

    public function index()
    {
        $categories = Category::with('subCategories')->get();
        return response()->json([
            'data' => $categories,
            'success' => true
        ], 201); 
    }
}