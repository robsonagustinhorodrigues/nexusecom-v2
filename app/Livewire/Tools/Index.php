<?php

namespace App\Livewire\Tools;

use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.tools.index');
    }
}
