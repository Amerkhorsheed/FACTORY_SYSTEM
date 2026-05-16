@props(['id', 'title', 'message', 'actionText' => 'تأكيد', 'actionStyle' => 'danger'])

@php
    $buttonClasses = [
        'primary' => 'bg-brand-600 hover:bg-brand-700 text-white',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white',
        'success' => 'bg-green-600 hover:bg-green-700 text-white',
    ][$actionStyle] ?? 'bg-brand-600 hover:bg-brand-700 text-white';
@endphp

<div x-data="{ show: false }"
     x-show="show"
     @open-modal.window="if ($event.detail === '{{ $id }}') show = true"
     @close-modal.window="if ($event.detail === '{{ $id }}') show = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     aria-labelledby="modal-title" role="dialog" aria-modal="true"
     style="display: none;">
    
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div x-show="show" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0" 
             x-transition:enter-end="opacity-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100" 
             x-transition:leave-end="opacity-0" 
             class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" aria-hidden="true"
             @click="show = false"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal panel -->
        <div x-show="show" 
             x-transition:enter="ease-out duration-300" 
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
             class="inline-block px-4 pt-5 pb-4 overflow-hidden text-right align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6" dir="rtl">
            
            <div class="sm:flex sm:items-start">
                <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto sm:mx-0 sm:h-10 sm:w-10 rounded-full {{ $actionStyle === 'danger' ? 'bg-red-100' : 'bg-brand-100' }}">
                    @if($actionStyle === 'danger')
                        <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    @else
                        <svg class="w-6 h-6 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @endif
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:mr-4 sm:text-right w-full">
                    <h3 class="text-lg font-bold leading-6 text-ink-900 font-cairo" id="modal-title">
                        {{ $title }}
                    </h3>
                    <div class="mt-2">
                        <p class="text-sm text-ink-500">
                            {{ $message }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-3">
                {{ $slot }}
                <button type="button" @click="show = false" class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium bg-white border rounded-md shadow-sm text-ink-700 border-ink-300 hover:bg-ink-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm font-cairo transition">
                    إلغاء
                </button>
            </div>
        </div>
    </div>
</div>
