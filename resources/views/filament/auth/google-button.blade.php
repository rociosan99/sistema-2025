<div class="mt-6">
    <a
        href="{{ route('auth.google.redirect', ['panel' => filament()->getCurrentPanel()->getId()]) }}"
        class="w-full inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold border border-gray-200 hover:bg-gray-50"
    >
        Continuar con Google
    </a>
</div>