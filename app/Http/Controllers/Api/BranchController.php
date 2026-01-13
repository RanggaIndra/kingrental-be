<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
            ->orWhere('address', 'like', '%' . $request->search . '%');
        }

        if ($request->has('all') && $request->all == ' true') {
            return BranchResource::collection($query->get());
        }

        return BranchResource::collection($query->latest()->paginate(10));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'contact_number' => 'required|string|max:20',
        ]);

        $branch = Branch::create($validated);

        return response()->json([
            'message' => 'Cabang berhasil ditambahkan',
            'data' => new BranchResource($branch)
        ], 201);
    }

    public function show($id)
    {
        $branch = Branch::findOrFail($id);
        return new BranchResource($branch);
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'contact_number' => 'sometimes|string|max:20',
        ]);

        $branch->update($validated);

        return response()->json([
            'message' => 'Cabang berhasil diperbaharui.',
            'data' => new BranchResource($branch)
        ]);
    }

    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);

        if ($branch->vehicles()->exists()) {
            return response()->json([
                'message' => 'Tidak dapat menghapus cabang ini karena masih memiliki unit kendaraan terdaftar.'
            ], 422);
        }

        $branch->delete();

        return response()->json(['message' => 'Cabang berhasil dihapus']);
    }
}
