@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 bg-white text-gray-800 focus:border-[#005088] focus:ring-[#005088] rounded-md shadow-sm']) }}>
