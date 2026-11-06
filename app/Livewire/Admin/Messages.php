<?php

namespace App\Livewire\Admin;

use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Messages extends Component
{
    use WithPagination;

    public function markRead(int $id): void
    {
        $message = ContactMessage::query()->findOrFail($id);

        if ($message->read_at === null) {
            $message->forceFill(['read_at' => now()])->save();
        }
    }

    public function deleteMessage(int $id): void
    {
        ContactMessage::query()->findOrFail($id)->delete();
        session()->flash('status', __('Message deleted.'));
    }

    public function render(): View
    {
        return view('livewire.admin.messages', [
            'messages' => ContactMessage::query()->latest()->paginate(15),
        ])->layout('layouts.admin', ['title' => __('Messages')]);
    }
}
