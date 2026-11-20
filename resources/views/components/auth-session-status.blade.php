@props(['status'])

@if ($status)
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
         {{ $attributes->merge(['class' => 'font-medium text-sm text-green-600 dark:text-green-400 flex items-center justify-between']) }}>
        <span>{{ $status }}</span>
        <button type="button" @click="show = false" class="ml-2 font-bold leading-none">&times;</button>
    </div>
@endif
