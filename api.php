<?php
require_once __DIR__ . '/common.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$action = (string)($_GET['action'] ?? 'all');
$out = ['ok' => true, 'action' => $action, 'data' => null];

list($localGroups, $otherGroups) = cviSplitGroups();
$grouped = cviGroupedEvents();

function cviPublicEvent(array $ev): array
{
    $ev['status'] = cviEventStatus($ev);
    $ev['status_label'] = cviEventStatusLabel($ev['status']);
    $ev['type_label'] = cviEventTypeLabel($ev['type']);
    return $ev;
}

if ($action === 'groups') {
    $out['data'] = array_values(cviGroups());
} elseif ($action === 'events') {
    $out['data'] = array_map('cviPublicEvent', array_values(cviEvents()));
} elseif ($action === 'meta') {
    $out['data'] = [
        'site_name' => cfg('site_name', '中V资讯地图'),
        'version' => CVI_VERSION,
        'group_count' => count(cviGroups()),
        'event_count' => count(cviEvents()),
        'repo_url' => cfg('repo_url', ''),
        'updated_at' => date('c'),
    ];
} elseif ($action === 'all') {
    $out['data'] = [
        'meta' => [
            'site_name' => cfg('site_name', '中V资讯地图'),
            'version' => CVI_VERSION,
            'group_count' => count(cviGroups()),
            'event_count' => count(cviEvents()),
            'repo_url' => cfg('repo_url', ''),
            'updated_at' => date('c'),
        ],
        'groups' => array_values(cviGroups()),
        'events' => array_map('cviPublicEvent', array_values(cviEvents())),
    ];
} else {
    $out['ok'] = false;
    $out['error'] = 'unknown_action';
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
