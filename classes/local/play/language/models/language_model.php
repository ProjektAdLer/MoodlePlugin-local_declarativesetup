<?php

namespace local_declarativesetup\local\play\language\models;

class language_model {
    /**
     * @param string $language_code The language code of the language to enable/disable (e.g. "de"). For a list of all
     *  see Administration > Language > Language packs.
     * @param bool $enabled
     */
    public function __construct(public string $language_code,
                                public bool   $enabled = true) {}
}