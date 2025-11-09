<?php

namespace local_declarativesetup\external;

use core\di;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use dml_exception;
use local_declarativesetup\local\cli\create_course_cat_and_assign_user_role as manager_create_course_cat_and_assign_user_role;
use moodle_database;
use moodle_exception;


class create_course_cat_and_assign_user_role extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'elements' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'username' => new external_value(
                                PARAM_TEXT,
                                'User name',
                                VALUE_REQUIRED
                            ),
                            'role' => new external_value(
                                PARAM_TEXT,
                                'Role name',
                                VALUE_REQUIRED
                            ),
                            'category_path' => new external_value(
                                PARAM_TEXT,
                                'Category path (optional) If not provided, a default category path will be generated',
                                VALUE_OPTIONAL
                            ),
                        )
                    )
                )
            )
        );
    }

    public static function execute_returns(): external_function_parameters {
        return new external_function_parameters([
            'data' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'username' => new external_value(
                            PARAM_TEXT,
                            'User name that was assigned the role',
                            VALUE_REQUIRED),
                        'role' => new external_value(
                            PARAM_TEXT,
                            'Role that was assigned',
                            VALUE_REQUIRED),
                        'category_path' => new external_value(
                            PARAM_TEXT,
                            'Category path that was created',
                            VALUE_OPTIONAL),
                        'category_id' => new external_value(
                            PARAM_INT,
                            'ID of the category the user was assigned the role in',
                            VALUE_REQUIRED),
                    ),
                    'Result data for each processed element'
                ),
            )
        ]);
    }

    /**
     * Creates course categories and assigns user roles
     *
     * @param array $elements Array of elements with username, role and category_path
     * @return array Results of the operations
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function execute(array $elements): array {
        $transaction = di::get(moodle_database::class)->start_delegated_transaction();

        try {
            foreach ($elements as $element) {
                $manager = new manager_create_course_cat_and_assign_user_role(
                    $element['username'],
                    $element['role'],
                    $element['category_path'] ?? null
                );
                $category_path = $manager->execute();

                $result[] = [
                    'username' => $element['username'],
                    'role' => $element['role'],
                    'category_path' => $category_path->get_path(),
                    'category_id' => $category_path->get_category_id(),
                ];
            }
        } catch (moodle_exception $e) {
            $transaction->rollback($e);
        }

        $transaction->allow_commit();
        return ['data' => $result];
    }
}