<x-layouts.panel :title="$modul . ' · Koombaah'">    <div class="mx-auto flex max-w-3xl flex-col gap-4 px-4 py-10">
        <h1 class="text-2xl font-semibold tracking-tight">{{ $modul }}</h1>
        <p class="text-sm text-muted-foreground">Modul ini belum dibuat. Kamu bisa melihat halaman ini karena role-mu punya akses.</p>
        <div><x-ui.button href="{{ route('dashboard') }}" variant="outline" size="sm">← Kembali ke Dashboard</x-ui.button></div>
    </div>
</x-layouts.panel>