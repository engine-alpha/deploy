<?php

namespace App;

use Aerys\Request;
use Aerys\Response;
use Amp\Beanstalk\BeanstalkClient;
use Kelunik\StatsD\StatsD;

class Hook {
    private $stats;
    private $client;
    private $secret;

    public function __construct(StatsD $stats, BeanstalkClient $client, HookSecret $secret) {
        $this->stats = $stats;
        $this->client = $client;
        $this->secret = $secret;
    }

    public function __invoke(Request $request, Response $response, array $args) {
        $this->stats->increment("github.hook.request");

        $owner = $args["owner"];
        $repository = $args["repository"];

        $event = $request->getHeader("x-github-event") ?? "";
        $signature = $request->getHeader("x-hub-signature") ?? "";

        $rawBody = yield $request->getBody();
        $payload = json_decode($rawBody);

        $hmac = "sha1=" . hash_hmac("sha1", $rawBody, $this->secret->getSecret());

        $response->setHeader("content-type", "text/plain");

        if (!hash_equals($hmac, $signature)) {
            $this->stats->increment("github.hook.invalid-signature");

            $response->setStatus(400);
            $response->end("Bad signature!");

            return;
        }

        $validEvents = ["push", "release"];

        if (!in_array($event, $validEvents, true)) {
            $this->stats->increment("github.hook.invalid-event");

            $response->end("Neither push nor release, aborting ...");

            return;
        }

        yield $this->client->put(json_encode([
            "owner" => $owner,
            "repository" => $repository,
            "event" => $event,
            "payload" => $payload,
        ]));

        $response->end("OK, scheduled.");
    }
}