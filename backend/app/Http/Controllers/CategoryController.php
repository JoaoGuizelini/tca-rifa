<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function __construct(protected CategoryService $service) {}

    public function index()
    {
        Gate::authorize('viewAny', Category::class);

        return response()->json($this->service->all([], [], 'name'));
    }

    public function store(CategoryRequest $request)
    {
        Gate::authorize('create', Category::class);
        $this->service->store($request->validated());

        return response()->noContent();
    }

    public function update(CategoryRequest $request, string $id)
    {
        $category = $this->service->find($id);
        abort_unless($category, 404);
        Gate::authorize('update', $category);
        $this->service->update($request->validated(), $id);

        return response()->noContent();
    }

    public function destroy(Request $request, string $id)
    {
        Gate::authorize('delete', Category::class);
        $this->service->remove($id);

        return response()->noContent();
    }
}
