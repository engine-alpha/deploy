<?php

namespace App;

class HookSecret {
    private $secret;

    public function __construct(string $secret) {
        $this->secret = $secret;
    }

    public function getSecret(): string {
        return $this->secret;
    }

    public function __toString(): string {
        return "(redacted)";
    }
}