<aside class="fixed inset-y-0 right-0 z-40 flex w-sidebar translate-x-full flex-col border-l border-slate-200 bg-white shadow-xl transition lg:translate-x-0 no-print"
       :class="$store.sidebar.open ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'">
    <div class="border-b border-slate-100 px-5 py-5">
        <p class="text-xs font-semibold text-slate-400">{{ __('ui.labels.factory_system') }}</p>
        <p class="mt-1 text-lg font-black text-brand-700">{{ config('app.name') }}</p>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4">
        @if(auth()->user()->hasRole('customer'))
            <a href="{{ route('portal.dashboard') }}" class="nav-item {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">{{ __('ui.modules.dashboard') }}</a>
            <a href="{{ route('portal.orders.index') }}" class="nav-item {{ request()->routeIs('portal.orders.*') ? 'active' : '' }}">{{ __('ui.modules.orders') }}</a>
            <a href="{{ route('portal.invoices.index') }}" class="nav-item {{ request()->routeIs('portal.invoices.*') ? 'active' : '' }}">{{ __('ui.modules.invoices') }}</a>
            <a href="{{ route('portal.profile') }}" class="nav-item {{ request()->routeIs('portal.profile') ? 'active' : '' }}">{{ __('ui.modules.profile') }}</a>
        @else
            @can('erp.dashboard.view')
                <a href="{{ route('erp.dashboard') }}" class="nav-item {{ request()->routeIs('erp.dashboard') ? 'active' : '' }}">{{ __('ui.modules.dashboard') }}</a>
            @endcan
            @can('products.view')
                <div class="nav-group-label">{{ __('ui.groups.operations') }}</div>
                <a href="{{ route('products.index') }}" class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}">{{ __('ui.modules.inventory') }}</a>
                <a href="{{ route('stock-movements.index') }}" class="nav-item {{ request()->routeIs('stock-movements.*') ? 'active' : '' }}">{{ __('ui.modules.stock_movements') }}</a>
            @endcan
            @can('customers.view')
                <a href="{{ route('customers.index') }}" class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">{{ __('ui.modules.customers') }}</a>
            @endcan
            @can('orders.view')
                <a href="{{ route('orders.index') }}" class="nav-item {{ request()->routeIs('orders.*') ? 'active' : '' }}">{{ __('ui.modules.orders') }}</a>
            @endcan
            @can('shipments.view')
                <a href="{{ route('shipments.index') }}" class="nav-item {{ request()->routeIs('shipments.*') ? 'active' : '' }}">{{ __('ui.modules.distribution') }}</a>
            @endcan
            @can('invoices.view')
                <a href="{{ route('invoices.index') }}" class="nav-item {{ request()->routeIs('invoices.*') ? 'active' : '' }}">{{ __('ui.modules.invoices') }}</a>
                <a href="{{ route('payments.index') }}" class="nav-item {{ request()->routeIs('payments.*') ? 'active' : '' }}">{{ __('ui.modules.payments') }}</a>
            @endcan
            @can('erp.reports.view')
                <div class="nav-group-label">{{ __('ui.groups.finance') }}</div>
                <a href="{{ route('erp.expenses.index') }}" class="nav-item {{ request()->routeIs('erp.expenses.*') ? 'active' : '' }}">{{ __('ui.modules.expenses') }}</a>
                <a href="{{ route('erp.reports.sales') }}" class="nav-item {{ request()->routeIs('erp.reports.*') ? 'active' : '' }}">{{ __('ui.modules.reports') }}</a>
            @endcan
            @can('system.users.view')
                <div class="nav-group-label">{{ __('ui.groups.admin') }}</div>
                <a href="{{ route('admin.users.index') }}" class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">{{ __('ui.modules.users') }}</a>
            @endcan
            @can('system.settings.view')
                <a href="{{ route('admin.settings.index') }}" class="nav-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">{{ __('ui.modules.settings') }}</a>
            @endcan
            @can('system.audit_log.view')
                <a href="{{ route('admin.audit-log.index') }}" class="nav-item {{ request()->routeIs('admin.audit-log.*') ? 'active' : '' }}">{{ __('ui.modules.audit_log') }}</a>
            @endcan
        @endif
    </nav>
</aside>
<div class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden no-print" x-show="$store.sidebar.open" x-transition @click="$store.sidebar.close()"></div>
