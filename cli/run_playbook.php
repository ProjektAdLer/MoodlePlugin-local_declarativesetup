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
 * CLI script for local_declarativesetup.
 *
 * @package     local_declarativesetup
 * @subpackage  cli
 * @copyright   2024 Markus Heck (Projekt Adler) <markus.heck@hs-kempten.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\di;
use local_declarativesetup\local\exceptions\exit_exception;

if (!defined('CLI_SCRIPT')) { // required this way for tests
    define('CLI_SCRIPT', true);
}

require_once(__DIR__ . '/../../../config.php');
global $CFG;
require_once($CFG->libdir . '/clilib.php');

// Get the cli options.
list($options, $unrecognized) = cli_get_params(array(
    'help' => false,
    'playbook' => false, // Add the new parameter here
),
    array(
        'h' => 'help',
        'p' => 'playbook', // Add a short option for the new parameter
    ));

$help =
    "
Options:
--playbook (-p)         Specify the playbook (subplugin name) to run.
--help (-h)             Display this help message.

Example of usage:
php run_playbook.php --playbook=myplaybook
";

if ($unrecognized) {
    $unrecognized = implode("\n\t", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    cli_writeln($help);
    die();
}

$playbooks = di::get(core_component::class)::get_plugin_list_with_class('playbook', 'playbook', 'classes/playbook.php');
$subplugin_name = "playbook_" . $options['playbook'];
if (array_key_exists($subplugin_name, $playbooks)) {
    $playbook = new $playbooks[$subplugin_name]();
} else {
    cli_writeln(get_string('error_playbooknotfound', 'local_declarativesetup', $options['playbook']));
    throw new exit_exception(1);
}
new $playbooks[$subplugin_name]();

echo "done\n";