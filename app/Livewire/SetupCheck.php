<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SetupCheck extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }

    public function render()
    {
        return view('livewire.setup-check', [
            'driver'    => DB::connection()->getDriverName(),
            'dbVersion' => DB::selectOne('select version() as v')->v,
        ]);
    }
}