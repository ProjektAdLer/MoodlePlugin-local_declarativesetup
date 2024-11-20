<?php

namespace local_adlersetup\local\play\language\models;

class language_model {
    public string $language_code;
    public bool $enabled;
    public function __construct(string $language_code, bool $enabled = true) {
        $this->language_code = $language_code;
        $this->enabled = $enabled;
    }
}