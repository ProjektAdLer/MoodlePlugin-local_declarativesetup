<?php

namespace local_declarativesetup\local;

class php {
    public static function file_put_contents(string $filename, string $data): int|false {
        return file_put_contents($filename, $data);
    }

    public static function file_get_contents(string $filename): string|false {
        return file_get_contents($filename);
    }
}