<?php

namespace local_declarativesetup\local\play\logos\models;

class logo_model {
    /** @var string|null Path to the logo image, empty string to delete the logo, null to not change the logo */
    public string|null $logo_path;
    /** @var string|null Path to the compact logo image, empty string to delete the logo, null to not change the logo */
    public string|null $logocompact_path;
    /** @var string|null Path to the favicon image, empty string to delete the favicon, null to not change the favicon */
    public string|null $favicon_path;

    /**
     * @param string|null $logo_path see {@link $logo_path}
     * @param string|null $logocompact_path see {@link $logocompact_path}
     * @param string|null $favicon_path see {@link $favicon_path}
     */
    public function __construct(string|null $logo_path = null,
                                string|null $logocompact_path = null,
                                string|null $favicon_path = null) {
        $this->logo_path = $logo_path;
        $this->logocompact_path = $logocompact_path;
        $this->favicon_path = $favicon_path;
    }
}