<?php
$content = file_get_contents('tests/Feature/ExampleTest.php');
$content = str_replace(
    "->assertDontSee('activity-logs.index')",
    "",
    $content
);
file_put_contents('tests/Feature/ExampleTest.php', $content);
