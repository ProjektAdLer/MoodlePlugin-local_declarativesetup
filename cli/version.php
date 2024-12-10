<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * CLI script to output plugin version or release.
 *
 * @package     local_declarativesetup
 * @subpackage  cli
 * @copyright   2024 Markus Heck
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\di;
use local_declarativesetup\local\moodle_core;

if (!defined('CLI_SCRIPT')) {
    define('CLI_SCRIPT', true);
}

require_once(__DIR__ . '/../../../config.php');
global $CFG;
require_once($CFG->libdir . '/clilib.php');

// Parse CLI options.
list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'release' => false,
    ],
    [
        'h' => 'help',
        'r' => 'release',
    ]
);

$help = "
CLI script to output plugin version or release.

Options:
--release (-r)  Output the plugin's release instead of the version.
--help (-h)     Display this help message.

Example:
php script.php --release
";

if ($options['help']) {
    cli_writeln($help);
    exit(0);
}

// Get the plugin info.
$plugininfo = di::get(moodle_core::class)::core_plugin_manager_instance()->get_plugin_info('local_declarativesetup');
$plugininfo->load_disk_version();

// Output version or release based on the presence of --release.
$output = $options['release'] ? $plugininfo->release : $plugininfo->versiondisk;
echo $output . PHP_EOL;
