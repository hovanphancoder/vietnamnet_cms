<?php

namespace Plugins\Reactix\Events;
class PluginActivateEvent
{
    protected $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    public function handle()
    {
        try {
            $posttype = PATH_PLUGINS . 'reactix/Posttypes/comment.json';
            $json = file_get_contents($posttype);
            $posttype = posttype_add($json);
            
        } catch (\Throwable $e) {
            // swallow errors in activate hook
        }
    }
}


