<?php

namespace App\Livewire\Storefront;

use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ContactPage extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $subject = '';

    public string $message = '';

    public string $website = '';

    public function send(): void
    {
        $key = 'contact:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages(['form' => __('Please wait before sending another message.')]);
        }
        RateLimiter::hit($key, 60);

        if ($this->website !== '') {
            $this->reset(['name', 'email', 'phone', 'subject', 'message', 'website']);

            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'website' => ['nullable', 'string', 'max:0'],
        ]);

        ContactMessage::query()->create(collect($validated)->except('website')->all());
        $this->reset(['name', 'email', 'phone', 'subject', 'message', 'website']);
        session()->flash('status', __('Your message has been sent. We will get back to you soon.'));
    }

    public function render(): View
    {
        return view('livewire.storefront.contact-page')->layout('layouts.storefront', ['title' => __('Contact us')]);
    }
}
