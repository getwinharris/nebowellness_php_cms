<?php
require __DIR__ . '/../app/bootstrap.php';

use App\Services\ProjectMapService;

$root = $argv[1] ?? dirname(__DIR__);
$output = $argv[2] ?? $root . '/.agents/map.mmd';

$mmd = ProjectMapService::renderSystematicMermaid();
if (file_put_contents($output, $mmd) !== strlen($mmd)) {
    fwrite(STDERR, "Unable to write code map.\n");
    exit(1);
}

$scan = ProjectMapService::scan();
$summary = $scan['summary'] ?? [];
echo json_encode($summary, JSON_PRETTY_PRINT) . "\n";
echo "Wrote $output (" . strlen($mmd) . " bytes)\n";