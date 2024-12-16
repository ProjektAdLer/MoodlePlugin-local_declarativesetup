<?php

namespace local_declarativesetup\local\play\logos\models;

class logo_model {
    /**
     * @param string|null $logo_path Path to the logo image, empty string to delete the logo, null to not change the logo
     * @param string|null $logocompact_path Path to the compact logo image, empty string to delete the logo, null to not change the logo
     * @param string|null $favicon_path Path to the favicon image, empty string to delete the favicon, null to not change the favicon
     */
    public function __construct(public string|null $logo_path = null,
                                public string|null $logocompact_path = null,
                                public string|null $favicon_path = null) {}
}