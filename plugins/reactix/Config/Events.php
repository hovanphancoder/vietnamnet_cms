<?php
use System\Libraries\Events;

// Register closure listener for PostsAddEvent with priority 10
Events::on('Backend\\PostsAddEvent', function($data) {
    print_r($data);
}, 10);
Events::on('Backend\\PostsEditEvent', function($data) {
    print_r($data);
    die;
}, 10);
