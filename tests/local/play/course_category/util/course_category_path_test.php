<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_declarativesetup\local\play\course_category\util;

global $CFG;

use core_course_category;
use dml_missing_record_exception;
use invalid_parameter_exception;
use local_declarativesetup\lib\adler_testcase;
use local_declarativesetup\local\play\course_category\exceptions\course_exists_exception;
use local_declarativesetup\local\play\course_category\exceptions\subcategory_exists_exception;
use Mockery;
use moodle_exception;

require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');

class course_category_path_test extends adler_testcase {
    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_constructor_with_null() {
        $path = new course_category_path(null);
        $this->assertEquals(0, count($path));
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_constructor_with_empty_string() {
        $path = new course_category_path('');
        $this->assertEquals(0, count($path));
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_constructor_with_spaces() {
        $path = new course_category_path('category1 / category2');
        $this->assertEquals(2, count($path));
        $this->assertEquals('category1', $path->get_path()[0]);
        $this->assertEquals('category2', $path->get_path()[1]);
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_constructor_without_spaces() {
        $path = new course_category_path('category1/category2');
        $this->assertEquals(2, count($path));
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_constructor_with_single_element() {
        $path = new course_category_path('category1');
        $this->assertEquals(1, count($path));
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_constructor_with_preceding_and_trailing_slashes() {
        $path = new course_category_path('/category1/category2/');
        $this->assertEquals(2, count($path));
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_constructor_with_spaces_around_slashes() {
        $path = new course_category_path(' category1 / category2 ');
        $this->assertEquals(2, count($path));
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_to_string_method() {
        $path = new course_category_path('category1/category2');
        $this->assertEquals('category1 / category2', $path->__toString());
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_get_path_method() {
        $path = new course_category_path('category1/category2');
        $this->assertEquals(['category1', 'category2'], $path->get_path());
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_count_method() {
        $path = new course_category_path('category1/category2');
        $this->assertEquals(2, $path->count());
    }

    private function setup_make_categories_list_mock() {
        $mock = Mockery::mock('alias:' . core_course_category::class);
        $mock->shouldReceive('make_categories_list')->andReturn([
            1 => 'category1 / category2',
            2 => 'category3 / category4',
        ]);
    }

    /**
     * @runInSeparateProcess
     *
     *  # ANF-ID: [MVP20, MVP21]
     */
    public function test_get_category_id_method_category_exists() {
        $this->setup_make_categories_list_mock();

        $path = new course_category_path('category1/category2');
        $this->assertEquals(1, $path->get_category_id());
    }

    /**
     * @runInSeparateProcess
     *
     *  # ANF-ID: [MVP20, MVP21]
     */
    public function test_get_category_id_method_category_does_not_exists() {
        $this->setup_make_categories_list_mock();

        $path = new course_category_path('category5/category6');
        $this->assertEquals(false, $path->get_category_id());
    }

    /**
     * @runInSeparateProcess
     *
     *  # ANF-ID: [MVP20, MVP21]
     */
    public function test_exists_method_category_exists() {
        $this->setup_make_categories_list_mock();

        $path = new course_category_path('category1/category2');
        $this->assertTrue($path->exists());
    }

    /**
     * @runInSeparateProcess
     *
     *  # ANF-ID: [MVP20, MVP21]
     */
    public function test_exists_method_category_does_not_exist() {
        $this->setup_make_categories_list_mock();

        $path = new course_category_path('category5/category6');
        $this->assertFalse($path->exists());
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_append_to_path_with_valid_path_part(): void {
        $path = new course_category_path('test/path');
        $path->append_to_path('new_part');
        $this->assertEquals('test / path / new_part', (string)$path);
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_append_to_path_with_empty_path_part(): void {
        $this->expectException(invalid_parameter_exception::class);
        $path = new course_category_path('test/path');
        $path->append_to_path('');
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_append_to_path_with_spaces_around_path_part(): void {
        $path = new course_category_path('test/path');
        $path->append_to_path(' new_part ');
        $this->assertEquals('test / path / new_part', (string)$path);
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_append_to_path_with_multiple_parts_in_path_part(): void {
        $path = new course_category_path('test/path');
        $path->append_to_path('new_part1/new_part2');
        $this->assertEquals('test / path / new_part1 / new_part2', (string)$path);
        $this->assertEquals(4, $path->count());
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_count_zero_exists_false(): void {
        $path = new course_category_path('');
        $this->assertEquals(0, $path->count());
        $this->assertFalse($path->exists());
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_create_with_count_zero(): void {
        $this->expectException(invalid_parameter_exception::class);
        $path = new course_category_path('');
        $path->create();
    }

    /**
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_create_with_empty_path(): void {
        $this->expectException(invalid_parameter_exception::class);
        $path = new course_category_path('');
        $path->create();
    }

    /**
     * @runInSeparateProcess
     *
     *  # ANF-ID: [MVP20, MVP21]
     */
    public function test_create_category_already_exists(): void {
        $this->setup_make_categories_list_mock();

        $path = new course_category_path('category1/category2');
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('category_already_exists');
        $path->create();
    }


    /**
     * @runInSeparateProcess
     *
     *  # ANF-ID: [MVP20, MVP21]
     */
    public function test_create_with_one_segment(): void {
        $mock = Mockery::mock('alias:' . core_course_category::class);
        $mock->shouldReceive('make_categories_list')->andReturn([]);
        $mock->shouldReceive('create')->andReturn((object)['id' => 1]);

        $path = new course_category_path('segment1');
        $this->assertEquals(1, $path->count());
        $this->assertFalse($path->exists());
        $this->assertIsInt($path->create());
    }

    /**
     * @runInSeparateProcess
     *
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_create_with_two_segments_first_exists(): void {
        $mock = Mockery::mock('alias:' . core_course_category::class);
        $mock->shouldReceive('make_categories_list')->andReturn([42 => 'segment1']);
        $mock->shouldReceive('create')->andReturn((object)['id' => 1]);

        $path = new course_category_path('segment1/segment2');
        $this->assertEquals(2, $path->count());
        $this->assertFalse($path->exists());
        $this->assertIsInt($path->create());
    }

    /**
     * @runInSeparateProcess
     *
     * # ANF-ID: [MVP20, MVP21]
     */
    public function test_create_with_two_segments_none_exists(): void {
        $mock = Mockery::mock('alias:' . core_course_category::class);
        $mock->shouldReceive('make_categories_list')->andReturn([]);
        $mock->shouldReceive('create')->andReturn((object)['id' => 1]);

        $path = new course_category_path('segment1/segment2');
        $this->assertEquals(2, $path->count());
        $this->assertFalse($path->exists());
        $this->assertIsInt($path->create());
    }

    public function test_get_moodle_category_object() {
        // set up
        $ccp = new course_category_path('testcategory');
        $this->assertFalse($ccp->exists());
        $ccp->create();
        $this->assertTrue($ccp->exists());

        $this->assertEquals('testcategory', $ccp->get_moodle_category_object()->name);
        $ccp->get_moodle_category_object();
    }

    public function test_delete(): void {
        // set up
        $ccp = new course_category_path('testcategory');
        $this->assertFalse($ccp->exists());
        $ccp->create();
        $this->assertTrue($ccp->exists());

        $ccp->delete();
        $this->assertFalse($ccp->exists());
    }

    public static function provide_delete_with_course_in_category(): array {
        return [
            'course delete' => ['course_mode' => 'delete'],
            'course move' => ['course_mode' => 'move'],
            'course fail' => ['course_mode' => 'fail'],
        ];
    }

    /**
     * @dataProvider provide_delete_with_course_in_category
     */
    public function test_delete_with_course_in_category(string $course_mode): void {
        // common set up
        $ccp_without_subcat = new course_category_path('testcategory');
        $ccp_without_subcat->create();
        $ccp_with_subcat = new course_category_path('testcategory2/subcategory');
        $ccp_with_subcat->create();
        $ccp_with_subcat_parent_cat = new course_category_path('testcategory2');

        $ccp_default = new course_category_path('default');
        $ccp_default->create();

        $course = $this->getDataGenerator()->create_course(['category' => $ccp_without_subcat->get_category_id()]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $ccp_with_subcat->get_category_id()]);

        get_course($course->id);  // just be sure the course can be loaded that way


        // testcases
        if ($course_mode == 'delete') {
            $ccp_without_subcat->delete(true, 'delete');
            $ccp_with_subcat_parent_cat->delete(true, 'delete');

            $this->assertFalse($ccp_without_subcat->exists());
            $this->assertFalse($ccp_with_subcat_parent_cat->exists());

            // check courses deleted
            try {
                get_course($course->id);
                $this->fail('Course not deleted');
            } catch (dml_missing_record_exception $e) {
                $this->assertEquals('invalidrecord', $e->errorcode);
            }
            try {
                get_course($course2->id);
                $this->fail('Course not deleted');
            } catch (dml_missing_record_exception $e) {
                $this->assertEquals('invalidrecord', $e->errorcode);
            }
        } else if ($course_mode == 'move') {
            $ccp_without_subcat->delete(true, $ccp_default->get_category_id());
            $ccp_with_subcat_parent_cat->delete(true, $ccp_default->get_category_id());

            $this->assertFalse($ccp_without_subcat->exists());
            $this->assertFalse($ccp_with_subcat_parent_cat->exists());

            // check courses now in default category
            $course = get_course($course->id);
            $this->assertEquals($ccp_default->get_category_id(), $course->category);
            $course2 = get_course($course2->id);
            $this->assertEquals($ccp_default->get_category_id(), $course2->category);
        } else if ($course_mode == 'fail') {
            try {
                $ccp_without_subcat->delete(true, 'dont delete');
                $this->fail('course_exists_exception not thrown');
            } catch (course_exists_exception $e) {
                $this->assertEquals($ccp_without_subcat->get_category_id(), get_course($course->id)->category);
            }
            try {
                $ccp_with_subcat_parent_cat->delete(true, 'dont delete');
                $this->fail('course_exists_exception not thrown');
            } catch (course_exists_exception $e) {
                $this->assertEquals($ccp_with_subcat->get_category_id(), get_course($course2->id)->category);
            }
        }
    }

    public function test_delete_with_subcategory_delete(): void {
        $ccp = new course_category_path('testcategory/subcategory');
        $ccp->create();
        $ccp_parent = new course_category_path('testcategory');

        $ccp_parent->delete(true, 'delete');
        $this->assertFalse($ccp->exists());
        $this->assertFalse($ccp_parent->exists());
    }

    public function test_delete_with_subcategory_dont_delete(): void {
        $ccp = new course_category_path('testcategory/subcategory');
        $ccp->create();
        $ccp_parent = new course_category_path('testcategory');

        try {
            $ccp_parent->delete(false, 'delete');
            $this->fail('subcategory_exists_exception not thrown');
        } catch (subcategory_exists_exception $e) {
            $this->assertTrue($ccp->exists());
            $this->assertTrue($ccp_parent->exists());
        }
    }

    public function test_multiple_with_same_base_category() {
        $sub_category_1_path = new course_category_path('testcategory/subcategory1');
        $sub_category_2_path = new course_category_path('testcategory/subcategory2');

        // create categories
        $sub_category_1_path->create();
        $sub_category_2_path->create();

        // check only one instance of base category exists
        $all_categories = core_course_category::make_categories_list();
        $this->assertEquals(1, count(array_filter($all_categories, function ($category) {
            return $category == 'testcategory';
        })));
    }
}