<?php

namespace Plugins\Reactix\Events;

class PluginDeactivateEvent
{
    protected $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    public function handle()
    {
        echo 'PluginDeactivateEvent';
        die;
    }
}


