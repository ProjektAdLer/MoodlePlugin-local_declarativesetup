<?php

namespace local_declarativesetup\local\play\web_services\models;

use invalid_parameter_exception;

class web_services_model {
    const STATE_ENABLED = 1;
    const STATE_DISABLED = 0;
    const STATE_UNSET = -1;

    /**
     * @param int $enable_webservices one of {@link STATE_ENABLED}, {@link STATE_DISABLED}, {@link STATE_UNSET}
     * @param string[] $protocols_enable_list List of protocols to enable. A protocol is only allowed in one of both
     *  lists: {@link $protocols_enable_list} or {@link $protocols_disable_list}. Protocols not in any list are
     *  kept unchanged. To force the {@link $protocols_enable_list} to be the only enabled protocols, set
     *  {@link $protocols_disable_list} to ['*']. This will also prevent changing the enabled protocols in the moodle
     *  admin interface.
     * @param string[] $protocols_disable_list
     * @param int $enable_moodle_mobile_service one of {@link STATE_ENABLED}, {@link STATE_DISABLED}, {@link STATE_UNSET}
     * @throws invalid_parameter_exception
     */
    public function __construct(public int   $enable_webservices,
                                public array $protocols_enable_list,
                                public array $protocols_disable_list = [],
                                public int   $enable_moodle_mobile_service = self::STATE_UNSET) {
        // validation
        foreach ($protocols_disable_list as $protocol) {
            if (in_array($protocol, $protocols_enable_list)) {
                throw new invalid_parameter_exception('Protocol ' . $protocol . ' is in both enable and disable list');
            }
        }
        if (in_array('*', $protocols_disable_list) && count($protocols_disable_list) !== 1) {
            throw new invalid_parameter_exception('If * is in the disable list, it must be the only element');
        }
        if (in_array('*', $protocols_enable_list)) {
            throw new invalid_parameter_exception('* is not allowed in the enable list');
        }

        if (!in_array($enable_webservices, [self::STATE_ENABLED, self::STATE_DISABLED, self::STATE_UNSET])) {
            throw new invalid_parameter_exception('Invalid value for enable_webservices');
        }
        if (!in_array($enable_moodle_mobile_service, [self::STATE_ENABLED, self::STATE_DISABLED, self::STATE_UNSET])) {
            throw new invalid_parameter_exception('Invalid value for enable_moodle_mobile_service');
        }
    }
}