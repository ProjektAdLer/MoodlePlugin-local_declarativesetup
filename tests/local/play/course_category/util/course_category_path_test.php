<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_declarativesetup\local\play\course_category\util;

global $CFG;

use core_course_category;
use invalid_parameter_exception;
use local_declarativesetup\lib\adler_testcase;
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
        $mock->shouldReceive('create')->andReturn((object) ['id' => 1]);

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
        $mock->shouldReceive('create')->andReturn((object) ['id' => 1]);

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
        $mock->shouldReceive('create')->andReturn((object) ['id' => 1]);

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
}