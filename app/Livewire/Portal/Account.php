<?php

namespace App\Livewire\Portal;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.portal')]
class Account extends Component
{
    public function render()
    {
        return view('livewire.portal.account', [
            'customer' => auth()->user()->customer,
        ]);
    }
}
