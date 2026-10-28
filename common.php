<?php
declare(strict_types=1);

define('CVI_VERSION', '1.0.0');
define('CVI_ROOT', __DIR__);

function cviConfig(): array
{
    static $conf = null;
    if ($conf !== null) return $conf;
    $file = CVI_ROOT . '/config.php';
    if (!is_file($file)) $file = CVI_ROOT . '/config.example.php';
    $loaded = is_file($file) ? require $file : [];
    $conf = is_array($loaded) ? $loaded : [];
    return $conf;
}

function cfg(string $key, $default = null)
{
    $c = cviConfig();
    return array_key_exists($key, $c) ? $c[$key] : $default;
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cviJsonFile(string $relative, $fallback = [])
{
    $file = CVI_ROOT . '/' . ltrim($relative, '/');
    if (!is_file($file)) return $fallback;
    $raw = @file_get_contents($file);
    if ($raw === false || trim($raw) === '') return $fallback;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $fallback;
}

function cviCenters(): array
{
    static $centers = null;
    if ($centers === null) $centers = cviJsonFile('data/centers.json', ['province' => [], 'city' => []]);
    return $centers;
}

function cviNum($value): float
{
    if (is_numeric($value)) return (float)$value;
    return 0.0;
}

function cviFillCoords(array &$items): void
{
    $centers = cviCenters();
    $provinces = $centers['province'] ?? [];
    $cities = $centers['city'] ?? [];
    foreach ($items as $i => $item) {
        $lat = cviNum($item['lat'] ?? 0);
        $lng = cviNum($item['lng'] ?? 0);
        if ($lat !== 0.0 || $lng !== 0.0) {
            $items[$i]['lat'] = $lat;
            $items[$i]['lng'] = $lng;
            continue;
        }
        $city = trim((string)($item['city'] ?? ''));
        $province = trim((string)($item['province'] ?? ''));
        if ($city !== '' && isset($cities[$city])) {
            $items[$i]['lat'] = (float)$cities[$city][0];
            $items[$i]['lng'] = (float)$cities[$city][1];
            continue;
        }
        if ($province !== '' && isset($provinces[$province])) {
            $items[$i]['lat'] = (float)$provinces[$province][0];
            $items[$i]['lng'] = (float)$provinces[$province][1];
        }
    }
}

function cviNormalizeGroup(array $g, int $index): array
{
    $level = in_array(($g['level'] ?? ''), ['province', 'city'], true) ? $g['level'] : 'city';
    $activity = (int)($g['activity_level'] ?? 4);
    if ($activity < 1 || $activity > 5) $activity = 4;
    return [
        'id' => (int)($g['id'] ?? ($index + 1)),
        'name' => trim((string)($g['name'] ?? '')),
        'qq_group' => trim((string)($g['qq_group'] ?? '')),
        'backup_qq_group' => trim((string)($g['backup_qq_group'] ?? '')),
        'province' => trim((string)($g['province'] ?? '')),
        'city' => trim((string)($g['city'] ?? '')),
        'level' => $level,
        'activity_level' => $activity,
        'member_count' => (int)($g['member_count'] ?? 0),
        'description' => trim((string)($g['description'] ?? '')),
        'public_note' => trim((string)($g['public_note'] ?? '')),
        'lat' => cviNum($g['lat'] ?? 0),
        'lng' => cviNum($g['lng'] ?? 0),
    ];
}

function cviNormalizeEvent(array $ev, int $index): array
{
    $type = in_array(($ev['type'] ?? ''), ['offline', 'online', 'birthday', 'other'], true) ? $ev['type'] : 'other';
    $start = trim((string)($ev['start_date'] ?? ''));
    $end = trim((string)($ev['end_date'] ?? ''));
    if ($end === '') $end = $start;
    return [
        'id' => (int)($ev['id'] ?? ($index + 1)),
        'title' => trim((string)($ev['title'] ?? '')),
        'type' => $type,
        'start_date' => $start,
        'end_date' => $end,
        'province' => trim((string)($ev['province'] ?? '')),
        'city' => trim((string)($ev['city'] ?? '')),
        'address' => trim((string)($ev['address'] ?? '')),
        'qq_group' => trim((string)($ev['qq_group'] ?? '')),
        'description' => trim((string)($ev['description'] ?? '')),
        'public_note' => trim((string)($ev['public_note'] ?? '')),
        'lat' => cviNum($ev['lat'] ?? 0),
        'lng' => cviNum($ev['lng'] ?? 0),
    ];
}

function cviGroups(): array
{
    static $groups = null;
    if ($groups !== null) return $groups;
    $rows = cviJsonFile('data/groups.json', []);
    $out = [];
    $i = 0;
    foreach ($rows as $row) {
        if (!is_array($row)) continue;
        $g = cviNormalizeGroup($row, $i);
        if ($g['name'] === '') continue;
        $out[] = $g;
        $i++;
    }
    cviFillCoords($out);
    usort($out, function ($a, $b) {
        if ($a['activity_level'] !== $b['activity_level']) return $a['activity_level'] <=> $b['activity_level'];
        return strcmp($a['name'], $b['name']);
    });
    $groups = $out;
    return $groups;
}

function cviEvents(): array
{
    static $events = null;
    if ($events !== null) return $events;
    $rows = cviJsonFile('data/events.json', []);
    $out = [];
    $i = 0;
    foreach ($rows as $row) {
        if (!is_array($row)) continue;
        $ev = cviNormalizeEvent($row, $i);
        if ($ev['title'] === '') continue;
        $out[] = $ev;
        $i++;
    }
    cviFillCoords($out);
    usort($out, function ($a, $b) {
        return strcmp($a['start_date'], $b['start_date']);
    });
    $events = $out;
    return $events;
}

function cviIsLocalGroup(array $g): bool
{
    return trim($g['province']) !== '' || trim($g['city']) !== '';
}

function cviSplitGroups(): array
{
    $local = [];
    $other = [];
    foreach (cviGroups() as $g) {
        if (cviIsLocalGroup($g)) $local[] = $g;
        else $other[] = $g;
    }
    return [$local, $other];
}

function cviEventStatus(array $ev): string
{
    $today = date('Y-m-d');
    $start = $ev['start_date'];
    $end = $ev['end_date'];
    if ($start !== '' && $start > $today) return 'upcoming';
    if ($end !== '' && $end < $today) return 'ended';
    return 'ongoing';
}

function cviEventStatusLabel(string $status): string
{
    $labels = ['upcoming' => '计划中', 'ongoing' => '进行中', 'ended' => '已结束'];
    return $labels[$status] ?? '未知';
}

function cviEventTypeLabel(string $type): string
{
    $labels = ['offline' => '线下活动', 'online' => '线上活动', 'birthday' => '生日会', 'other' => '其他'];
    return $labels[$type] ?? '其他';
}

function cviActivityMark(int $level): string
{
    $marks = [1 => '★', 2 => '①', 3 => '②', 4 => '③', 5 => '④'];
    return $marks[$level] ?? '④';
}

function cviGroupedEvents(): array
{
    $ongoing = [];
    $upcoming = [];
    $ended = [];
    foreach (cviEvents() as $ev) {
        $status = cviEventStatus($ev);
        if ($status === 'upcoming') $upcoming[] = $ev;
        elseif ($status === 'ended') $ended[] = $ev;
        else $ongoing[] = $ev;
    }
    return ['ongoing' => $ongoing, 'upcoming' => $upcoming, 'ended' => $ended];
}

function cviTiandituKeys(): array
{
    $keys = cfg('tianditu_keys', []);
    $out = [];
    if (!is_array($keys)) return $out;
    foreach ($keys as $item) {
        $tk = trim((string)($item['tk'] ?? ''));
        if ($tk === '' || strpos($tk, 'YOUR_') === 0) continue;
        $out[] = ['tk' => $tk, 'sk' => trim((string)($item['sk'] ?? ''))];
    }
    return $out;
}

function cviBoundaryFile(string $which): string
{
    return $which === 'city' ? 'china-cities.geojson' : 'china-provinces.geojson';
}

function cviHasBoundary(string $which): bool
{
    return is_file(CVI_ROOT . '/' . cviBoundaryFile($which));
}

function cviSiteUrl(): string
{
    $url = trim((string)cfg('site_url', ''));
    if ($url !== '') return rtrim($url, '/');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '80') === '443');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    return ($https ? 'https://' : 'http://') . $host . ($path === '/' ? '' : $path);
}
