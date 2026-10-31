<nav class="flex gap-1 border-b">
    @foreach ([['Akun Pengguna', 'pengaturan'], ['Cadangan Data', 'pengaturan.backup']] as [$label, $route])
        <a href="{{ route($route) }}" wire:navigate
           @class([
               '-mb-px border-b-2 px-3 py-2 text-sm',
               'border-primary font-medium' => $aktif === $route,
               'border-transparent text-muted-foreground hover:text-foreground' => $aktif !== $route,
           ])>{{ $label }}</a>
    @endforeach
</nav>