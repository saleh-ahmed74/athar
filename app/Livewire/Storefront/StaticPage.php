<?php

namespace App\Livewire\Storefront;

use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StaticPage extends Component
{
    public string $page;

    public function mount(string $page): void
    {
        abort_unless(in_array($page, ['about', 'policy'], true), 404);
        $this->page = $page;
    }

    public function render(): View
    {
        $title = $this->page === 'about' ? __('Our story') : __('Terms and policies');

        $settings = StoreSetting::current();

        return view('livewire.storefront.static-page', ['settings' => $settings, 'policySections' => $settings->policy_sections ?? []])
            ->layout('layouts.storefront', ['title' => $title]);
    }
}
