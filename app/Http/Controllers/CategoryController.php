<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $space = $request->attributes->get('space');

        return view('categories.index', ['categories' => $space->categories()->with('parent')->orderBy('type')->orderBy('sort_order')->get(), 'canManage' => $space->type === 'personal' || $space->canManage($request->user())]);
    }

    public function store(Request $request)
    {
        $space = $request->attributes->get('space');
        abort_unless($space->type === 'personal' || $space->canManage($request->user()), 403);
        $data = $request->validate(['name' => 'required|max:80', 'type' => 'required|in:income,expense', 'parent_id' => 'nullable|integer', 'color' => 'required|max:20', 'icon' => 'nullable|max:40']);
        if (! empty($data['parent_id'])) {
            abort_unless($space->categories()->whereKey($data['parent_id'])->exists(), 404);
        } $data['user_id'] = $request->user()->id;
        $data['space_id'] = $space->id;
        Category::create($data);

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function update(Request $request, Category $category)
    {
        $space = $request->attributes->get('space');
        abort_unless($category->space_id === $space->id && ($space->type === 'personal' || $space->canManage($request->user())), 404);
        $category->update($request->validate(['name' => 'required|max:80', 'color' => 'required|max:20', 'is_active' => 'sometimes|boolean']));

        return back()->with('success', 'Kategori diperbarui.');
    }
}
