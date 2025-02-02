<?php

namespace App\Http\Livewire\Server\Proxy;

use App\Jobs\ProxyContainerStatusJob;
use App\Models\Server;
use Livewire\Component;

class Status extends Component
{
    public Server $server;

    public function get_status()
    {
        dispatch_sync(new ProxyContainerStatusJob(
            server: $this->server
        ));
        $this->server->refresh();
        $this->emit('proxyStatusUpdated');
    }
}
