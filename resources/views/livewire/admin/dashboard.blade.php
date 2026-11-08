<section wire:poll.10s.visible>
    <div class="admin-heading">
        <div><p class="hero-eyebrow">{{ \App\Models\StoreSetting::current()->store_name ?: config('app.name') }}</p><h1>{{ __('Overview') }}</h1></div>
        <a class="button small" href="{{ route('admin.products') }}" wire:navigate>{{ __('Add product') }}</a>
    </div>
    <div class="filters dashboard-filters">
        <label class="field"><span>{{ __('From date') }}</span><input type="date" wire:model.live="dateFrom">@error('dateFrom')<span class="error">{{ $message }}</span>@enderror</label>
        <label class="field"><span>{{ __('To date') }}</span><input type="date" wire:model.live="dateTo">@error('dateTo')<span class="error">{{ $message }}</span>@enderror</label>
    </div>
    <div class="admin-stats dashboard-stats">
        <div class="stat-card"><span>{{ __('Products') }}</span><strong>{{ $productCount }}</strong></div>
        <div class="stat-card"><span>{{ __('Orders') }}</span><strong>{{ $orderCount }}</strong></div>
        <div class="stat-card expected-revenue"><span>{{ __('Expected revenue') }}</span><strong>{{ number_format((float) $expectedRevenue, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</strong><small>{{ __('Confirmed and shipped orders') }}</small></div>
        <div class="stat-card actual-revenue"><span>{{ __('Delivered revenue') }}</span><strong>{{ number_format((float) $actualRevenue, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</strong><small>{{ __('Completed deliveries') }}</small></div>
        <div class="stat-card return-loss"><span>{{ __('Returned order value (lost sales)') }}</span><strong>{{ number_format((float) $returnedValue, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</strong><small>{{ __('Returned orders') }}: {{ $statusCounts['returned'] ?? 0 }}</small></div>
        <div class="stat-card"><span>{{ __('Unread messages') }}</span><strong>{{ $messageCount }}</strong></div>
    </div>
    <div class="order-status-breakdown">
        @foreach ($statusOptions as $status)
            <button type="button" class="{{ $statusFilter === $status ? 'is-selected' : '' }}" aria-pressed="{{ $statusFilter === $status ? 'true' : 'false' }}" wire:click="filterOrdersByStatus('{{ $status }}')"><span>{{ __(ucfirst($status)) }}</span><strong>{{ $statusCounts[$status] ?? 0 }}</strong></button>
        @endforeach
    </div>
    <div class="section-head dashboard-orders-head"><h2>{{ __('Recent orders') }}@if($statusFilter)<span class="dashboard-filter-label"> · {{ __(ucfirst($statusFilter)) }}</span>@endif</h2><div class="table-actions">@if($statusFilter)<button class="text-link dashboard-filter-clear" type="button" wire:click="$set('statusFilter', '')">{{ __('Clear filter') }}</button>@endif<a class="text-link" href="{{ route('admin.orders') }}" wire:navigate>{{ __('View all') }}</a></div></div>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>{{ __('Order number') }}</th><th>{{ __('Customer') }}</th><th>{{ __('Date') }}</th><th>{{ __('Total') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
    @forelse ($recentOrders as $order)
        <tr><td data-label="{{ __('Order number') }}">{{ $order->order_number }}</td><td data-label="{{ __('Customer') }}">{{ $order->customer_name }}</td><td data-label="{{ __('Date') }}">{{ $order->created_at->format('d M Y') }}</td><td data-label="{{ __('Total') }}">{{ number_format((float) $order->total, 2) }} {{ \App\Models\StoreSetting::current()->currency_symbol }}</td><td data-label="{{ __('Status') }}"><span class="order-status">{{ __(ucfirst($order->status === 'processing' ? 'confirmed' : $order->status)) }}</span></td></tr>
    @empty
        <tr><td colspan="5">{{ __('No orders yet.') }}</td></tr>
    @endforelse
    </tbody></table></div>
</section>
