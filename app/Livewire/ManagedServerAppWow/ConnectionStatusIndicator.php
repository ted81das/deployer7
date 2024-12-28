<?php

namespace App\Livewire\ManagedServerAppWow;

use Livewire\Component;
use App\Models\ManagedServerAppWow;

class ConnectionStatusIndicator extends Component
{
    public ManagedServerAppWow $record;

    public function mount(ManagedServerAppWow $record)
    {
        $this->record = $record;
    }

    public function render()
    {
        return view('livewire.managed-server-app-wow.connection-status-indicator');
    }
}
