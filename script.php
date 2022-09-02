<?php
require('Agent.php');

$args = getopt('u:a:p:b', [
    'username',
    'password'
]);

if(isset($args['u']) && isset($args['p']))
{
    $agent = new Agent($args['u'], $args['p']);
    $agent->init();

    exit();    
}

exit('Use the script with following params: -u "username" -p "password"' . PHP_EOL);