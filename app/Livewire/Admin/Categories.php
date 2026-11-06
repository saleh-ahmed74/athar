<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Support\ImageUploadProcessor;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Categories extends Component
{
    use WithFileUploads, WithPagination;

    #[Url(as: 'archive', except: false)]
    public bool $showArchived = false;

    public ?int $categoryId = null;

    public string $name = '';

    public string $nameAr = '';

    public string $description = '';

    public string $descriptionAr = '';

    public bool $isActive = true;

    public mixed $image = null;

    public bool $showForm = false;

    public function createCategory(): void
    {
        $this->authorizeAdmin();
        abort_if($this->showArchived, 404);
        $this->resetForm();
        $this->showForm = true;
    }

    public function editCategory(int $id): void
    {
        $this->authorizeAdmin();
        $category = Category::query()->findOrFail($id);
        abort_if($category->archived_at, 404);
        $this->resetForm();
        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->nameAr = $category->name_ar ?? '';
        $this->description = $category->description ?? '';
        $this->descriptionAr = $category->description_ar ?? '';
        $this->isActive = $category->is_active;
        $this->showForm = true;
    }

    public function saveCategory(): void
    {
        $this->authorizeAdmin();
        $category = $this->categoryId ? Category::query()->findOrFail($this->categoryId) : new Category;
        abort_if($category->archived_at, 404);
        $slug = Str::slug($this->name);
        if ($slug === '') {
            $slug = 'category-'.Str::lower(Str::random(8));
        }
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'nameAr' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'descriptionAr' => ['nullable', 'string', 'max:2000'],
            'isActive' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:4096'],
        ]);
        validator(['slug' => $slug], ['slug' => [Rule::unique('categories', 'slug')->ignore($this->categoryId)]])->validate();
        $oldImage = $category->image_path;
        $category->fill([
            'name' => trim($validated['name']),
            'name_ar' => $validated['nameAr'] ?: null,
            'slug' => $slug,
            'description' => $validated['description'] ?: null,
            'description_ar' => $validated['descriptionAr'] ?: null,
            'is_active' => $validated['isActive'],
            'image_path' => $this->image ? ImageUploadProcessor::store($this->image, 'categories', 1200, 1200, 82) : $oldImage,
        ])->save();
        if ($this->image && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }
        $this->resetForm();
        session()->flash('status', __('Category saved.'));
    }

    public function archiveCategory(int $id): void
    {
        $this->authorizeAdmin();
        $category = Category::query()->findOrFail($id);
        abort_if($category->archived_at, 404);

        DB::transaction(function () use ($category): void {
            $category->forceFill(['archived_at' => now()])->save();
            $category->products()->whereNull('archived_at')->update(['archived_at' => now(), 'archived_with_category' => true]);
        });

        session()->flash('status', __('Category and its products archived.'));
    }

    public function restoreCategory(int $id): void
    {
        $this->authorizeAdmin();
        $category = Category::query()->findOrFail($id);
        abort_unless($category->archived_at, 404);

        DB::transaction(function () use ($category): void {
            $category->forceFill(['archived_at' => null])->save();
            $category->products()->where('archived_with_category', true)->update(['archived_at' => null, 'archived_with_category' => false]);
        });

        session()->flash('status', __('Category and its products restored.'));
    }

    public function deleteCategory(int $id): void
    {
        $this->authorizeAdmin();
        $category = Category::query()->with('products')->findOrFail($id);
        abort_unless($category->archived_at, 404);
        $images = [$category->image_path];

        foreach ($category->products as $product) {
            $images = array_merge($images, [$product->image_path], $product->gallery_paths ?? []);
        }

        DB::transaction(function () use ($category): void {
            $category->products()->delete();
            $category->delete();
        });
        Storage::disk('public')->delete(array_values(array_filter($images)));
        session()->flash('status', __('Category and its products permanently deleted.'));
    }

    public function toggleArchiveView(): void
    {
        $this->showArchived = ! $this->showArchived;
        $this->resetPage();
        $this->resetForm();
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        return view('livewire.admin.categories', [
            'categories' => Category::query()
                ->with(['products' => fn ($query) => $query->whereNotNull('image_path')->limit(1)])
                ->withCount('products')
                ->when($this->showArchived, fn ($query) => $query->whereNotNull('archived_at'), fn ($query) => $query->whereNull('archived_at'))
                ->orderBy('sort_order')
                ->paginate(20),
        ])->layout('layouts.admin', ['title' => __('Categories')]);
    }

    private function resetForm(): void
    {
        $this->reset(['categoryId', 'name', 'nameAr', 'description', 'descriptionAr', 'isActive', 'image']);
        $this->isActive = true;
        $this->showForm = false;
        $this->resetValidation();
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }
}
