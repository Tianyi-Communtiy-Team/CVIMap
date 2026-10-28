<?php
/**
 * 从现有 MySQL 站点导出 data/groups.json 与 data/events.json
 *
 * 用法（CLI）：
 *   php tools/export_from_mysql.php
 *
 * 也可以直接改下面的常量，或用环境变量覆盖：
 *   CVI_DB_HOST / CVI_DB_NAME / CVI_DB_USER / CVI_DB_PASS
 */

$host = getenv('CVI_DB_HOST') ?: 'localhost';
$name = getenv('CVI_DB_NAME') ?: 'YOUR_DB_NAME';
$user = getenv('CVI_DB_USER') ?: 'YOUR_DB_USER';
$pass = getenv('CVI_DB_PASS') ?: 'YOUR_DB_PASSWORD';

$outDir = dirname(__DIR__) . '/data';

function cviOut($v)
{
    if ($v === null) return '';
    if (is_object($v) && method_exists($v, 'format')) return $v->format('Y-m-d');
    return is_scalar($v) ? (string)$v : '';
}

try {
    $pdo = new PDO(
        'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4',
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $ex) {
    fwrite(STDERR, '数据库连接失败：' . $ex->getMessage() . PHP_EOL);
    exit(1);
}

$groupFields = ['id', 'name', 'qq_group', 'province', 'city', 'level', 'activity_level', 'member_count', 'description', 'public_note', 'lat', 'lng'];
$eventFields = ['id', 'title', 'type', 'start_date', 'end_date', 'province', 'city', 'address', 'qq_group', 'description', 'public_note', 'lat', 'lng'];

function cviPickRow(array $row, array $fields): array
{
    $out = [];
    foreach ($fields as $f) {
        $out[$f] = array_key_exists($f, $row) ? cviOut($row[$f]) : '';
    }
    return $out;
}

$groups = [];
try {
    $stmt = $pdo->query("SELECT * FROM fans_groups WHERE status = 'approved' ORDER BY activity_level ASC, name ASC");
    foreach ($stmt as $row) {
        $g = cviPickRow($row, $groupFields);
        $g['activity_level'] = (int)$g['activity_level'];
        $g['member_count'] = (int)$g['member_count'];
        $g['lat'] = (float)$g['lat'];
        $g['lng'] = (float)$g['lng'];
        $g['backup_qq_group'] = array_key_exists('backup_qq_group', $row) ? cviOut($row['backup_qq_group']) : '';
        $groups[] = $g;
    }
} catch (PDOException $ex) {
    fwrite(STDERR, '读取 fans_groups 失败：' . $ex->getMessage() . PHP_EOL);
}

$events = [];
try {
    $stmt = $pdo->query("SELECT * FROM events WHERE status = 'approved' ORDER BY start_date ASC");
    foreach ($stmt as $row) {
        $e = cviPickRow($row, $eventFields);
        $e['lat'] = (float)$e['lat'];
        $e['lng'] = (float)$e['lng'];
        $events[] = $e;
    }
} catch (PDOException $ex) {
    fwrite(STDERR, '读取 events 失败：' . $ex->getMessage() . PHP_EOL);
}

$flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
file_put_contents($outDir . '/groups.json', json_encode($groups, $flags) . PHP_EOL);
file_put_contents($outDir . '/events.json', json_encode($events, $flags) . PHP_EOL);

echo '导出完成：群 ' . count($groups) . ' 条，活动 ' . count($events) . ' 条' . PHP_EOL;
