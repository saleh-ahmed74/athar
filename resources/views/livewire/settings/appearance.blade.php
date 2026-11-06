<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<div class="account-settings-page">
    <x-settings.layout :heading="__('Appearance')" :subheading="__('The ATHAR store uses a light, readable theme across the site.')">
        <div class="account-settings-card"><h2>{{ __('Appearance') }}</h2><p>{{ __('The ATHAR store uses a light, readable theme across the site.') }}</p></div>
    </x-settings.layout>
</div>
