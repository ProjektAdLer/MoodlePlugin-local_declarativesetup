<?php

namespace local_declarativesetup\plugininfo;

use core\plugininfo\base;

class playbook extends base{
    public function is_uninstall_allowed(): bool {
        return true;
    }
}