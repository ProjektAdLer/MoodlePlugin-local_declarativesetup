<?php

namespace local_declarativesetup\local\play\language\models;

class language_model {
    /**
     * @var string $language_code The language code of the language to enable/disable (e.g. "de"). For a list of all
     * see Administration > Language > Language packs.
 */
    public string $language_code;
    public bool $enabled;

    /**
     * @param string $language_code see {@link $language_code}
     * @param bool $enabled see {@link $enabled}
     */
    public function __construct(string $language_code, bool $enabled = true) {
        $this->language_code = $language_code;
        $this->enabled = $enabled;
    }
}