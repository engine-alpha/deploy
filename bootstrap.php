<?php

namespace App;

use Aerys\Host;
use Amp\Beanstalk\BeanstalkClient;
use Auryn\Injector;

$config = json_decode(file_get_contents(__DIR__ . "/config.json"), true);

$injector = new Injector;

$injector->define(HookSecret::class, [
    ":secret" => $config["github.secret"],
]);

$injector->define(BeanstalkClient::class, [
    ":uri" => $config["beanstalk"],
]);

$router = \Aerys\router()
    ->route("POST", "github", $injector->make(Hook::class));

(new Host)
    ->expose("*", $config["app.port"] ?? 80)
    ->use($router);