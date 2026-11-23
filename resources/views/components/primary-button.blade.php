<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-[#005088] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#003e6b] focus:bg-[#003e6b] active:bg-[#002d4f] focus:outline-none focus:ring-2 focus:ring-[#005088] focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
