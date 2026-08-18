<?php

declare(strict_types=1);

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php scripts/check-coverage-threshold.php <clover.xml> <minimum-percent>\n");
    exit(2);
}

[$script, $coveragePath, $minimumInput] = $argv;

if (! is_readable($coveragePath)) {
    fwrite(STDERR, "Coverage report is not readable: {$coveragePath}\n");
    exit(2);
}

if (! is_numeric($minimumInput) || (float) $minimumInput < 0 || (float) $minimumInput > 100) {
    fwrite(STDERR, "Minimum coverage must be a number between 0 and 100.\n");
    exit(2);
}

$coverage = simplexml_load_file($coveragePath);
$metrics = $coverage?->project?->metrics;

if ($metrics === null) {
    fwrite(STDERR, "Coverage report does not contain project metrics.\n");
    exit(2);
}

$statements = (int) $metrics['statements'];
$coveredStatements = (int) $metrics['coveredstatements'];

if ($statements <= 0 || $coveredStatements < 0 || $coveredStatements > $statements) {
    fwrite(STDERR, "Coverage report contains invalid statement metrics.\n");
    exit(2);
}

$minimum = (float) $minimumInput;
$percentage = ($coveredStatements / $statements) * 100;

printf(
    "Statement coverage %.2f%% (%d/%d); required minimum %.2f%%.\n",
    $percentage,
    $coveredStatements,
    $statements,
    $minimum,
);

if ($percentage + PHP_FLOAT_EPSILON < $minimum) {
    fwrite(STDERR, "Coverage ratchet failed.\n");
    exit(1);
}
