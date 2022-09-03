<?php
require('Agent.php');

$args = getopt('u:a:p:b:e::', [
    'username',
    'password'
]);

if(isset($args['u']) && isset($args['p']))
{
    $exportMode = isset($args['e']);
    
    $agent = new Agent($args['u'], $args['p'], $exportMode);
    $agent->init();

    exit();
}

exit('Use the script with following params: -u "username" -p "password" -e (optional)' . PHP_EOL);