<?php
require_once __DIR__ . '/common.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$action = (string)($_GET['action'] ?? 'all');
$out = ['ok' => true, 'action' => $action, 'data' => null];

global $CITY_CENTER_MAP, $PROVINCE_CENTER_MAP;

$stmt = db()->prepare("SELECT * FROM fans_groups WHERE status = 'approved' ORDER BY activity_level ASC, name ASC");
$stmt->execute();
$groups = $stmt->fetchAll();
fillGroupCoords($groups, $CITY_CENTER_MAP, $PROVINCE_CENTER_MAP);

$stmt = db()->prepare("SELECT * FROM events WHERE status = 'approved' ORDER BY start_date ASC");
$stmt->execute();
$events = $stmt->fetchAll();

$pick = function (array $row, array $keys) {
    $item = [];
    foreach ($keys as $k) {
        $item[$k] = $row[$k] ?? null;
    }
    return $item;
};

$groupList = [];
foreach ($groups as $g) {
    $item = $pick($g, [
        'id', 'name', 'qq_group', 'backup_qq_group', 'province', 'city',
        'level', 'member_count', 'activity_level', 'description',
        'public_note', 'lat', 'lng', 'created_at',
    ]);
    $item['id'] = (int)$item['id'];
    $item['activity_level'] = (int)($item['activity_level'] ?? 4);
    $item['activity_color'] = activityColor($item['activity_level']);
    $item['activity_label'] = activityLabel($item['activity_level']);
    $item['lat'] = (float)($item['lat'] ?? 0);
    $item['lng'] = (float)($item['lng'] ?? 0);
    $item['is_local'] = inferGroupIsLocal($g) ? 1 : 0;
    $groupList[] = $item;
}

$eventList = [];
foreach ($events as $e) {
    $item = $pick($e, [
        'id', 'title', 'type', 'province', 'city', 'address',
        'start_date', 'end_date', 'description', 'qq_group',
        'backup_qq_group', 'lat', 'lng', 'created_at',
    ]);
    $item['id'] = (int)$item['id'];
    $item['type_label'] = eventTypeLabel((string)($e['type'] ?? 'other'));
    $item['status'] = eventStatus((string)($e['start_date'] ?? ''), (string)($e['end_date'] ?? ''));
    $item['status_label'] = eventStatusLabel($item['status']);
    if (!empty($e['cover_file'])) {
        $item['cover_url'] = SITE_URL . '/' . ltrim((string)$e['cover_file'], '/');
    }
    $eventList[] = $item;
}

$meta = [
    'site_name' => getConfig('site_name', SITE_NAME),
    'version' => SITE_VERSION,
    'group_count' => count($groupList),
    'event_count' => count($eventList),
    'repo_url' => REPO_URL,
    'updated_at' => date('c'),
];

if ($action === 'groups') {
    $out['data'] = $groupList;
} elseif ($action === 'events') {
    $out['data'] = $eventList;
} elseif ($action === 'meta') {
    $out['data'] = $meta;
} elseif ($action === 'all') {
    $out['data'] = [
        'meta' => $meta,
        'groups' => $groupList,
        'events' => $eventList,
    ];
} else {
    $out['ok'] = false;
    $out['error'] = 'unknown_action';
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
