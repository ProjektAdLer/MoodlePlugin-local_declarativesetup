<?php

namespace local_declarativesetup\local\play\course_category\util;

use core_course_category;
use Countable;
use invalid_parameter_exception;
use moodle_exception;

class course_category_path implements Countable {
    /**
     * @var string[]
     */
    private array $path;

    /**
     * @param string|null $path the path in moodle (with spaces around the /) or UNIX format (without spaces),
     * can be empty string or null to initialize an empty path
     */
    public function __construct(string|null $path) {
        if ($path === null || strlen($path) === 0) {
            $this->path = [];
        } else {
            $this->path = $this->split_and_trim_path($path);
        }
    }

    /**
     * @return string the path in moodle format (with spaces around the /)
     */
    public function __toString(): string {
        return implode(' / ', $this->path);
    }

    /**
     * @return string[] Returns the path as an array of strings.
     */
    public function get_path(): array {
        return $this->path;
    }

    public function count(): int {
        return count($this->path);
    }

    /**
     * @return bool Returns true if the category path exists in moodle, false otherwise.
     */
    public function exists(): bool {
        return $this->get_category_id() !== false;
    }

    /**
     * @return int Returns the ID of the created category (the last category in the path).
     * @throws invalid_parameter_exception if the path is empty
     * @throws moodle_exception if the category already exists
     */
    public function create(): int {
        if (count($this) === 0) {
            throw new invalid_parameter_exception('path must not be empty');
        }

        if ($this->exists()) {
            throw new moodle_exception('category_already_exists', 'local_declarativesetup');
        }

        $current_category_id = 0;  // top level category
        $current_category_path = '';

        foreach ($this->get_path() as $category_path_part) {
            $current_category_path .= ' / ' . $category_path_part;
            $current_category_path_obj = new course_category_path($current_category_path);
            if ($current_category_path_obj->exists()) {
                $current_category_id = $current_category_path_obj->get_category_id();
            } else {
                $current_category_id = core_course_category::create([
                    'name' => $category_path_part,
                    'parent' => $current_category_id,
                    'visible' => 1,
                ])->id;
            }
        }

        return $current_category_id;
    }

    /**
     * @throws moodle_exception
     */
    public function delete(): void {
        $this->get_moodle_category_object()->delete_full();
    }


    /**
     * @return int|bool Returns the ID of the category, or false if the category does not exist.
     */
    public function get_category_id(): int|bool {
        $categories = core_course_category::make_categories_list();
        return array_search($this, $categories);
    }

    /**
     * @throws moodle_exception
     */
    public function get_moodle_category_object(): core_course_category {
        return core_course_category::get($this->get_category_id(), MUST_EXIST, true);
    }

    /**
     * Append a path part to the end of the path.
     * @throws invalid_parameter_exception if $path_part is empty
     */
    public function append_to_path(string $path_part): void {
        if (strlen($path_part) === 0) {
            throw new invalid_parameter_exception('path_part must not be empty');
        }
        $this->path = array_merge($this->path, $this->split_and_trim_path($path_part));
    }

    /**
     * @param string $path The path to split and trim.
     * @return array Returns the path as an array of strings after splitting by '/' and trimming whitespace.
     */
    private function split_and_trim_path(string $path): array {
        // remove preceding and trailing /
        $path = trim($path, ' /');

        $path_parts = explode('/', $path);
        return array_map('trim', $path_parts);
    }
}
