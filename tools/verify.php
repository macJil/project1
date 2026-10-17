<?php
// Read-only checks. Run from a terminal: php tools/verify.php [--database]
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../class/RiskAnalyzer.php';
require_once __DIR__ . '/../includes/map-boundary.php';
$root = dirname(__DIR__);
$failures = 0;
$checks = 0;
function check($ok, $message) {
    global $failures, $checks;
    $checks++;
    echo ($ok ? 'PASS ' : 'FAIL ') . $message . PHP_EOL;
    if (!$ok) $failures++;
}
check(PHP_VERSION_ID >= 80000, 'PHP 8.0 or newer');
foreach (['pdo_mysql', 'curl', 'json', 'session'] as $extension) check(extension_loaded($extension), 'Extension: ' . $extension);
$files = glob($root . '/*.php');
foreach (['class','includes','api','users/admin','users/user','tools'] as $folder) {
    $files = array_merge($files, glob($root . '/' . $folder . '/*.php'));
}
if (is_callable('exec')) {
    foreach ($files as $file) {
        $output = []; $code = 0;
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $code);
        check($code === 0, 'PHP syntax: ' . substr($file, strlen($root) + 1));
        if ($code !== 0) echo implode(PHP_EOL, $output) . PHP_EOL;
    }
} else {
    echo "SKIP PHP syntax checks: exec is disabled. Run php -l on the PHP files separately.\n";
}
foreach (['assets/css/bootstrap.min.css','assets/css/style.css','assets/js/bootstrap.bundle.min.js',
    'assets/js/location-map.js','assets/js/report-maps.js','assets/js/weather.js','assets/js/bulk-select.js',
    'assets/vendor/leaflet/leaflet.js','assets/vendor/leaflet/leaflet.css','assets/map/irisan.geojson'] as $asset) {
    check(is_file($root . '/' . $asset) && filesize($root . '/' . $asset) > 0, 'Local asset: ' . $asset);
}
$tiles = glob($root . '/assets/map-tiles/*/*/*.png');
check(count($tiles) >= 110, 'Offline tile set: at least 110 PNG files');
$validTiles = true;
foreach ($tiles as $tile) {
    $handle = fopen($tile, 'rb'); $signature = fread($handle, 8); fclose($handle);
    if ($signature !== "\x89PNG\r\n\x1a\n") $validTiles = false;
}
check($validTiles, 'Tile files have PNG signatures');
check(inside_irisan(16.421, 120.5595), 'Pilot point is inside Irisan');
check(!inside_irisan(0, 0), 'Outside point is rejected');
$risk = new RiskAnalyzer();
foreach ([[0,0,0,'low'],[10,10,10,'normal'],[25,25,25,'medium'],[50,50,50,'high'],
    [0,25,25,'normal'],[0,50,50,'medium'],[0,0,150,'high'],[-1,0,0,null],[20,10,30,null],[null,0,0,null]] as $case) {
    check($risk->analyze($case[0],$case[1],$case[2]) === $case[3], 'Rainfall case: ' . json_encode($case));
}
$end = intdiv(time(), 3600) * 3600;
$reading = ['rainfall_1h'=>0,'rainfall_24h'=>0,'rainfall_72h'=>0,'observed_at'=>gmdate('Y-m-d H:i:s',$end),
    'rainfall_window_end'=>gmdate('Y-m-d H:i:s',$end),'rule_version'=>RiskAnalyzer::VERSION,'source'=>'openmeteo','risk_level'=>'low'];
check($risk->assess($reading,$end+60)['status'] === 'current', 'Fresh complete assessment');
check($risk->assess($reading,$end+RiskAnalyzer::MAX_AGE)['status'] === 'outdated', 'Three-hour freshness boundary');
$reading['source']='manual'; $reading['risk_level']='high';
check($risk->assess($reading,$end+60)['category'] === 'high', 'Admin saved risk override');
$reading['rule_version']=null;
check($risk->assess($reading,$end+60)['status'] === 'incomplete', 'Imported/legacy data is not a fresh assessment');
if (in_array('--database', $argv, true)) {
    require_once $root . '/config.php';
    try {
        $pdo = new PDO('mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        check(true, 'Database connection');
        $expected = ['users'=>['id','username','password','role','full_name','email','phone'],
            'locations'=>['id','name','purok','landmark','lat','lng','active','susceptibility'],
            'events'=>['id','location_id','user_id','type','risk_level','observed_at','rainfall_1h','rainfall_24h','rainfall_72h',
                'temperature','humidity','wind_speed','weather_code','source','archived','created_at','rainfall_window_end',
                'rule_version','provider_payload','adjustment_log','message','report_type','status','contact_phone',
                'contact_email','house_landmark','occurred_at','reviewed_by','reviewed_at']];
        foreach ($expected as $table=>$columns) {
            $actual = $pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_COLUMN);
            check(!array_diff($columns,$actual), 'Required columns: ' . $table);
        }
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        check(count($tables) === 3, 'Expected three-table database');
    } catch (PDOException $error) {
        check(false, 'Database unavailable or columns missing. Check config.php and database/schema.sql; do not overwrite an existing database.');
    }
} else {
    echo "SKIP database checks. Run again with --database after configuring MySQL.\n";
}
echo "$checks checks; $failures failures.\n";
echo "This script does not write data or test browser rendering, login/CRUD flows, or live providers. Complete docs/TESTING.md.\n";
exit($failures ? 1 : 0);
