<?php

namespace App;

class HookSecret {
    private $secret;

    public function __construct(string $secret) {
        $this->secret = $secret;
    }

    public function getSecret() {
        return $this->secret;
    }
}