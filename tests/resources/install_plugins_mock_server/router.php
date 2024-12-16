<?php

$files = [
    'local_testplugin/0.1.0.zip',
    'local_testplugin/0.1.0.zip.md5',
    'local_testplugin/0.1.1.zip',
    'local_testplugin/0.1.1.zip.md5',
];

foreach ($files as $file) {
    if (preg_match('/' . preg_quote($file, '/') . '$/', $_SERVER["REQUEST_URI"])) {        header('HTTP/1.1 200 OK');
        header('Content-Type: application/octet-stream');
        readfile(__DIR__ . '/' . $file);
        return true;
    }
}

if (preg_match('/releases\/tags\/([0-9]+\.[0-9]+\.[0-9]+[a-zA-Z-_.]*)/', $_SERVER["REQUEST_URI"], $matches)) {
    $version_number = $matches[1];
    $response = file_get_contents(__DIR__ . '/release_tags_response.txt');
    $response = str_replace('VERSION_NUMBER', $version_number, $response);
    $response = str_replace("\r", '', $response);  // remove \r to be valid JSON
    header('HTTP/1.1 200 OK');
    header('Content-Type: application/json');
    echo $response;
    return true;
}

exit(1);
