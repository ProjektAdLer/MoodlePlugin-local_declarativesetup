<?php

use local_declarativesetup\local\cli\create_course_cat_and_assign_user_role;
use local_declarativesetup\local\exceptions\exit_exception;

if (!defined('CLI_SCRIPT')) {
    define('CLI_SCRIPT', true);
}

require_once(__DIR__ . '/../../../config.php');
global $CFG;
require_once($CFG->libdir . '/clilib.php');

$help = "Create a new course category and grant the user permission to create adler courses in it.

Options:
--username=STRING       User name
--role=STRING           Role name
--category_path=STRING  Category path (optional)

-h, --help              Print out this help
";

// Parse command line arguments
list($options, $unrecognized) = cli_get_params(
    array(
        'username' => false,
        'role' => false,
        'category_path' => false,
        'help' => false
    ),
    array(
        'h' => 'help'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_write(get_string('cliunknowoption', 'admin', $unrecognized) . "\n");
    echo $help;
    throw new exit_exception(1);
}

if (!empty($options['help'])) {
    echo $help;
} else {
    try {
        $manager = new create_course_cat_and_assign_user_role(
            trim($options['username']),
            trim($options['role']),
            trim($options['category_path'])
        );
        $category_id = $manager->execute();
    } catch (moodle_exception $e) {
        cli_writeln($e->getMessage());
        throw new exit_exception(1);
    }

    cli_writeln("Created category with ID $category_id and assigned user " . $options['username'] . " the role " . $options['role'] . " in it.");
}
