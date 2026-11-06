<?php

namespace App\Livewire\Admin;

use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Dashboard extends Component
{
    #[Url(as: 'from', except: '')]
    #[Validate('nullable|date')]
    public string $dateFrom = '';

    #[Url(as: 'to', except: '')]
    #[Validate('nullable|date')]
    public string $dateTo = '';

    public string $statusFilter = '';

    public function filterOrdersByStatus(string $status): void
    {
        if (! in_array($status, ['pending', 'confirmed', 'shipped', 'delivered', 'returned', 'cancelled'], true)) {
            abort(422);
        }

        $this->statusFilter = $this->statusFilter === $status ? '' : $status;
    }

    public function render(): View
    {
        $orders = Order::query()
            ->when($this->dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $this->dateTo));
        $aggregates = (clone $orders)->selectRaw("COUNT(*) as order_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status IN ('confirmed', 'processing') THEN 1 ELSE 0 END) as confirmed_count,
            SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_count,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
            SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned_count,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
            SUM(CASE WHEN status IN ('confirmed', 'processing', 'shipped') THEN total ELSE 0 END) as expected_revenue,
            SUM(CASE WHEN status = 'delivered' THEN total ELSE 0 END) as actual_revenue,
            SUM(CASE WHEN status = 'returned' THEN total ELSE 0 END) as returned_value")->first();

        $statusCounts = collect(['pending', 'confirmed', 'shipped', 'delivered', 'returned', 'cancelled'])
            ->mapWithKeys(fn (string $status) => [$status => (int) ($aggregates->{$status.'_count'} ?? 0)]);

        return view('livewire.admin.dashboard', [
            'productCount' => Product::query()->count(),
            'orderCount' => (int) $aggregates->order_count,
            'statusCounts' => $statusCounts,
            'messageCount' => ContactMessage::query()->whereNull('read_at')->count(),
            'expectedRevenue' => (float) $aggregates->expected_revenue,
            'actualRevenue' => (float) $aggregates->actual_revenue,
            'returnedValue' => (float) $aggregates->returned_value,
            'statusOptions' => ['pending', 'confirmed', 'shipped', 'delivered', 'returned', 'cancelled'],
            'recentOrders' => (clone $orders)
                ->when($this->statusFilter !== '', function ($query): void {
                    if ($this->statusFilter === 'confirmed') {
                        $query->whereIn('status', ['confirmed', 'processing']);
                    } else {
                        $query->where('status', $this->statusFilter);
                    }
                })
                ->with('items.product')->latest()->limit(8)->get(),
        ])->layout('layouts.admin', ['title' => __('Overview')]);
    }
}
