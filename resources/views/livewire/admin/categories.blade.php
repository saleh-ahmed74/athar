<section>
    <div class="admin-heading"><h1>{{ $showArchived ? __('Archived categories') : __('Categories') }}</h1><div class="table-actions"><button class="button secondary small" wire:click="toggleArchiveView">{{ $showArchived ? __('Active categories') : __('Archive') }}</button>@unless($showArchived)<button class="button small" wire:click="createCategory">{{ __('Add category') }}</button>@endunless</div></div>
    @if (session('status'))<p class="status-message">{{ session('status') }}</p>@endif
    @if (session('error'))<p class="alert">{{ session('error') }}</p>@endif
    @if ($showForm)
        <form class="admin-card admin-form" wire:submit="saveCategory"><h2>{{ $categoryId ? __('Edit category') : __('Add category') }}</h2>
            <div class="form-grid"><div class="field"><label>{{ __('Name') }} (EN)</label><input wire:model="name">@error('name')<span class="error">{{ $message }}</span>@enderror</div><div class="field"><label>{{ __('Name') }} (AR)</label><input dir="rtl" wire:model="nameAr">@error('nameAr')<span class="error">{{ $message }}</span>@enderror</div></div>
            <div class="form-grid"><div class="field"><label>{{ __('Description') }} (EN)</label><textarea rows="2" wire:model="description"></textarea></div><div class="field"><label>{{ __('Description') }} (AR)</label><textarea dir="rtl" rows="2" wire:model="descriptionAr"></textarea></div></div>
            <div class="field"><label>{{ __('Category image') }} (max 4 MB)</label><label class="upload-button" for="category-image">{{ __('Choose category image') }}</label><input class="visually-hidden" id="category-image" type="file" accept="image/jpeg,image/png,image/webp" wire:model="image">@error('image')<span class="error">{{ $message }}</span>@enderror@if ($image && $image->isPreviewable())<img class="settings-image-preview" src="{{ $image->temporaryUrl() }}" alt="{{ __('Preview') }}">@endif</div>
            <label class="checkbox-row"><input type="checkbox" wire:model="isActive">{{ __('Visible in store') }}</label>
            <div class="table-actions"><button class="button small" type="submit">{{ __('Save') }}</button><button class="button small secondary" type="button" wire:click="cancelForm">{{ __('Cancel') }}</button></div>
        </form>
    @endif
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>{{ __('Category') }}</th><th>{{ __('Products') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
    @forelse ($categories as $category)
        <tr><td data-label="{{ __('Category') }}"><div class="admin-product-cell">@php($categoryProductImage = $category->image_path ?: $category->products->first()?->image_path)@if($categoryProductImage)<img class="admin-thumb" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($categoryProductImage) }}" alt="">@else<span class="admin-thumb image-placeholder">A</span>@endif<div>{{ $category->name }}@if($category->name_ar)<span class="note">{{ $category->name_ar }}</span>@endif</div></div></td>
        <td data-label="{{ __('Products') }}">{{ $category->products_count }}</td><td data-label="{{ __('Status') }}">{{ $category->is_active ? __('Active') : __('Hidden') }}</td>
        <td data-label="{{ __('Actions') }}"><div class="table-actions">@if($showArchived)<button class="button secondary small" wire:click="restoreCategory({{ $category->id }})">{{ __('Restore') }}</button><button class="danger-button" wire:click="deleteCategory({{ $category->id }})" wire:confirm="{{ __('Permanently delete this category and all its products? Order history will remain.') }}">{{ __('Delete permanently') }}</button>@else<button class="button secondary small" wire:click="editCategory({{ $category->id }})">{{ __('Edit') }}</button><button class="danger-button" wire:click="archiveCategory({{ $category->id }})" wire:confirm="{{ __('Archive this category and all its products?') }}">{{ __('Archive') }}</button>@endif</div></td></tr>
    @empty<tr><td colspan="4">{{ __('No categories yet.') }}</td></tr>@endforelse
    </tbody></table></div><div style="margin-top:14px">{{ $categories->links() }}</div>
</section>
