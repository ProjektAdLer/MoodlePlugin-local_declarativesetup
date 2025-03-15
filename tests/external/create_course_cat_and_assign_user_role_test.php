<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_declarativesetup\external;

use context_coursecat;
use local_declarativesetup\lib\adler_externallib_testcase;
use invalid_parameter_exception;
use local_declarativesetup\local\play\course_category\util\course_category_path;

global $CFG;
require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');

class create_course_cat_and_assign_user_role_test extends adler_externallib_testcase {

    /**
     * Data provider for parameter validation
     */
    public function parameter_data_provider(): array {
        return [
            'valid parameters' => [
                [
                    'elements' => [
                        [
                            'username' => 'admin',
                            'role' => 'manager',
                            'category_path' => 'test/category'
                        ]
                    ]
                ]
            ],
            'multiple elements' => [
                [
                    'elements' => [
                        [
                            'username' => 'admin',
                            'role' => 'manager',
                            'category_path' => 'test/category1'
                        ],
                        [
                            'username' => 'teacher',
                            'role' => 'editingteacher',
                            'category_path' => 'test/category2'
                        ]
                    ]
                ]
            ],
            'without optional category_path' => [
                [
                    'elements' => [
                        [
                            'username' => 'admin',
                            'role' => 'manager'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider parameter_data_provider
     */
    public function test_execute_parameters(array $data) {
        create_course_cat_and_assign_user_role::validate_parameters(
            create_course_cat_and_assign_user_role::execute_parameters(),
            $data
        );
        $this->assertTrue(true); // If we get here, validation passed
    }

    /**
     * Data provider for return value validation
     */
    public function returns_data_provider(): array {
        return [
            'single result' => [
                [
                    'data' => [
                        [
                            'username' => 'admin',
                            'role' => 'manager',
                            'category_path' => 'test/category',
                            'category_id' => 123
                        ]
                    ]
                ]
            ],
            'multiple results' => [
                [
                    'data' => [
                        [
                            'username' => 'admin',
                            'role' => 'manager',
                            'category_path' => 'test/category1',
                            'category_id' => 123
                        ],
                        [
                            'username' => 'teacher',
                            'role' => 'editingteacher',
                            'category_path' => 'test/category2',
                            'category_id' => 124
                        ]
                    ]
                ]
            ],
            'result with null category_path' => [
                [
                    'data' => [
                        [
                            'username' => 'admin',
                            'role' => 'manager',
                            'category_path' => null,
                            'category_id' => 123
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider returns_data_provider
     */
    public function test_execute_returns(array $data) {
        create_course_cat_and_assign_user_role::validate_parameters(
            create_course_cat_and_assign_user_role::execute_returns(),
            $data
        );
        $this->assertTrue(true); // If we get here, validation passed
    }

    public function test_execute_with_nonexistent_user() {
        $this->resetAfterTest(true);

        $this->expectException(invalid_parameter_exception::class);
        $this->expectExceptionMessage('user_not_found');

        create_course_cat_and_assign_user_role::execute([
            [
                'username' => 'nonexistent_user',
                'role' => 'manager'
            ]
        ]);
    }

    public function test_execute_with_nonexistent_role() {
        $this->resetAfterTest(true);

        // Create a test user
        $user = $this->getDataGenerator()->create_user();

        $this->expectException(invalid_parameter_exception::class);
        $this->expectExceptionMessage('role_not_found');

        create_course_cat_and_assign_user_role::execute([
            [
                'username' => $user->username,
                'role' => 'nonexistent_role'
            ]
        ]);
    }

    public function test_execute_success() {
        global $DB;
        $this->resetAfterTest(true);

        // Create a test user
        $user = $this->getDataGenerator()->create_user();

        // Make sure the course manager role exists
        $role = $DB->get_record('role', ['shortname' => 'manager']);
        $this->assertNotEmpty($role, 'The manager role is required for this test');

        // Execute the function
        $result = create_course_cat_and_assign_user_role::execute([
            [
                'username' => $user->username,
                'role' => 'manager',
                'category_path' => 'Test Category/Subcategory'
            ]
        ]);

        // Check result structure
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']);
        $this->assertEquals($user->username, $result['data'][0]['username']);
        $this->assertEquals('manager', $result['data'][0]['role']);
        $this->assertEquals('Test Category/Subcategory', $result['data'][0]['category_path']);
        $this->assertIsInt($result['data'][0]['category_id']);

        // Verify category was created
        $category_path = new course_category_path('Test Category/Subcategory');
        $this->assertTrue($category_path->exists());

        // Verify user role assignment
        $context = \context_coursecat::instance($result['data'][0]['category_id']);
        $role_assignments = $DB->get_records('role_assignments', [
            'roleid' => $role->id,
            'userid' => $user->id,
            'contextid' => $context->id
        ]);
        $this->assertCount(1, $role_assignments);
    }

    public function test_execute_multiple_elements() {
        global $DB;
        $this->resetAfterTest(true);

        // Create test users
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Make sure required roles exist
        $manager_role = $DB->get_record('role', ['shortname' => 'manager']);
        $this->assertNotEmpty($manager_role, 'The manager role is required for this test');

        // Execute function with multiple elements
        $result = create_course_cat_and_assign_user_role::execute([
            [
                'username' => $user1->username,
                'role' => 'manager',
                'category_path' => 'Test Category 1'
            ],
            [
                'username' => $user2->username,
                'role' => 'manager',
                'category_path' => 'Test Category 2'
            ]
        ]);

        // Check result structure
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(2, $result['data']);

        // Verify first element
        $this->assertEquals($user1->username, $result['data'][0]['username']);
        $this->assertEquals('manager', $result['data'][0]['role']);
        $this->assertEquals('Test Category 1', $result['data'][0]['category_path']);

        // Verify second element
        $this->assertEquals($user2->username, $result['data'][1]['username']);
        $this->assertEquals('manager', $result['data'][1]['role']);
        $this->assertEquals('Test Category 2', $result['data'][1]['category_path']);

        // Verify both categories were created
        $category_path1 = new course_category_path('Test Category 1');
        $category_path2 = new course_category_path('Test Category 2');
        $this->assertTrue($category_path1->exists());
        $this->assertTrue($category_path2->exists());

        // Verify both role assignments
        $context1 = context_coursecat::instance($result['data'][0]['category_id']);
        $role_assignments1 = $DB->get_records('role_assignments', [
            'roleid' => $manager_role->id,
            'userid' => $user1->id,
            'contextid' => $context1->id
        ]);
        $this->assertCount(1, $role_assignments1);

        $context2 = context_coursecat::instance($result['data'][1]['category_id']);
        $role_assignments2 = $DB->get_records('role_assignments', [
            'roleid' => $manager_role->id,
            'userid' => $user2->id,
            'contextid' => $context2->id
        ]);
        $this->assertCount(1, $role_assignments2);
    }
}