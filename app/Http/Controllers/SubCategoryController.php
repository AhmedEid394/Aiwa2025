<?php

namespace App\Http\Controllers;

use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubCategoryController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'category_id' => 'required|exists:categories,category_id',
                'name' => 'required|string|max:255',
                'image' => 'nullable|string',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|exists:sub_categories,sub_category_id',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $subCategory = SubCategory::create($validatedData);

        return response()->json($subCategory, 201);
    }

    public function show($id)
    {
        $subCategory = SubCategory::with(['category', 'services'])->find($id);
        if (!$subCategory) {
            return response()->json(['error' => 'Sub-category not found'], 404);
        }
        return response()->json($subCategory, 201);
    }

    public function update(Request $request, $id)
    {
        $subCategory = SubCategory::find($id);
        if (!$subCategory) {
            return response()->json(['error' => 'Sub-category not found'], 404);
        }

        try {
            $validatedData = $request->validate([
                'category_id' => 'sometimes|exists:categories,category_id',
                'name' => 'sometimes|string|max:255',
                'image' => 'nullable|string',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|exists:sub_categories,sub_category_id',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $subCategory->update($validatedData);

        return response()->json($subCategory, 201);
    }

    public function destroy($id)
    {
        $subCategory = SubCategory::find($id);
        if (!$subCategory) {
            return response()->json(['error' => 'Sub-category not found'], 404);
        }
        $subCategory->delete();
        return response()->json(null, 201);
    }

    public function index(Request $request)
    {
        $query = SubCategory::with('category');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $subCategories = $query->latest()->get();
        return response()->json($subCategories, 201);
    }

    public function services($id)
    {
        $subCategory = SubCategory::find($id);
        if (!$subCategory) {
            return response()->json(['error' => 'Sub-category not found'], 404);
        }

        $services = $subCategory->services()->paginate(15);
        return response()->json($services, 201);
    }
}
