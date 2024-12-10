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
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_declarativesetup\local\exceptions\exit_exception;
use local_declarativesetup\local\play\install_plugins\install_plugins;
use local_declarativesetup\local\play\install_plugins\models\install_plugins_model;

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
        'github-project' => null,
        'package-repo' => null,
        'version' => null,
        'moodle-name' => null,
    ],
    [
        'h' => 'help',
        'g' => 'github-project',
        'p' => 'package-repo',
        'v' => 'version',
        'm' => 'moodle-name',
    ]
);

$help = "
CLI script for installing plugins in Moodle.

Options:
--github-project (-g)  GitHub project in the form <user>/<repo>.
--package-repo (-p)    Path to the package repository.
--version (-v)         Plugin version or branch (e.g., 1.0.0).
--moodle-name (-m)     Moodle plugin name (e.g., mod_adleradaptivity).
--help (-h)            Display this help message.

Example:
php script.php --github-project=ProjektAdler/MoodlePluginModAdleradaptivity --version=1.0.0 --moodle-name=mod_adleradaptivity
";

if ($options['help']) {
    cli_writeln($help);
    exit(0);
}

if (empty($options['version']) || empty($options['moodle-name'])) {
    cli_writeln("Missing required parameters. Use --help for details.");
    throw new exit_exception();
}

// Validate and populate the model with either github-project or package-repo.
if (!empty($options['github-project']) && !empty($options['package-repo'])) {
    cli_writeln("Error: You can only specify either --github-project or --package-repo, not both.");
    throw new exit_exception();
}

if (empty($options['github-project']) && empty($options['package-repo'])) {
    cli_writeln("Error: You must specify either --github-project or --package-repo.");
    throw new exit_exception();
}

$plugin = new install_plugins_model(
    $options['version'],
    $options['moodle-name'],
    $options['github-project'] ?? null,
    $options['package-repo'] ?? null
);

$play = new install_plugins([$plugin]);
$changed = $play->play();

cli_writeln($changed ? "Plugin installed or updated." : "Plugin already installed.");
