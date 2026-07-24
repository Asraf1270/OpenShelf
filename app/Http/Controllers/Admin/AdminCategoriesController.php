<?php

namespace App\Http\Controllers\Admin;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCategoriesController extends AdminController
{
    public function index(Request $request): View|RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof RedirectResponse) {
            return $admin;
        }

        if ($request->isMethod('post')) {
            return $this->handleAction($request);
        }

        $this->syncCategoriesFromBooks();

        $categories = Category::query()
            ->orderBy('name')
            ->get()
            ->map(function (Category $category) {
                $category->count = Book::query()->where('category', $category->name)->count();

                return $category;
            });

        return view('admin.categories', compact('admin', 'categories'));
    }

    private function handleAction(Request $request): RedirectResponse
    {
        $action = $request->input('action');

        if ($action === 'add') {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:100'],
            ]);

            Category::query()->firstOrCreate(['name' => trim($validated['name'])]);

            return back()->with('success', 'Category added successfully.');
        }

        if ($action === 'edit') {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:categories,id'],
                'name' => ['required', 'string', 'max:100'],
            ]);

            $category = Category::query()->findOrFail($validated['id']);
            $oldName = $category->name;

            $category->update(['name' => trim($validated['name'])]);

            if ($oldName !== $category->name) {
                Book::query()->where('category', $oldName)->update(['category' => $category->name]);
            }

            return back()->with('success', 'Category updated.');
        }

        if ($action === 'delete') {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:categories,id'],
            ]);

            Category::query()->whereKey($validated['id'])->delete();

            return back()->with('success', 'Category deleted.');
        }

        if ($action === 'collect') {
            $before = Category::query()->count();
            $this->syncCategoriesFromBooks();
            $after = Category::query()->count();

            return back()->with('success', 'Sync complete. Collected ' . ($after - $before) . ' new categories.');
        }

        return back()->with('error', 'Unsupported action.');
    }

    private function syncCategoriesFromBooks(): void
    {
        $names = Book::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        foreach ($names as $name) {
            Category::query()->firstOrCreate(['name' => $name]);
        }
    }
}
