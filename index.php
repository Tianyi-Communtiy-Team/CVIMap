<?php
require_once __DIR__ . '/common.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$isLogged = isLoggedIn();
$currentUser = currentUser();
$isAdmin = isAdmin();

$hiddenGroupIds = [];
if ($isLogged && !empty($currentUser['id'])) {
    $hiddenGroupIds = hiddenGroupIds((int)$currentUser['id']);
}
$hiddenGroupMap = [];
foreach ($hiddenGroupIds as $hid) {
    $hiddenGroupMap[(int)$hid] = true;
}

$stmt = db()->prepare("SELECT * FROM fans_groups WHERE status = 'approved' ORDER BY activity_level ASC, name ASC");
$stmt->execute();
$allApprovedGroupsRaw = $stmt->fetchAll();

$groups = [];
$otherGroups = [];
foreach ($allApprovedGroupsRaw as $g) {
    if (isset($hiddenGroupMap[(int)$g['id']])) continue;
    if (inferGroupIsLocal($g)) {
        $groups[] = $g;
    } else {
        $otherGroups[] = $g;
    }
}

$allApprovedGroups = [];
foreach ($allApprovedGroupsRaw as $g) {
    if (isset($hiddenGroupMap[(int)$g['id']])) continue;
    $allApprovedGroups[] = $g;
}

$stmt = db()->prepare("SELECT * FROM events WHERE status = 'approved' ORDER BY start_date ASC");
$stmt->execute();
$allEvents = $stmt->fetchAll();

$ongoingEvents = [];
$upcomingEvents = [];
$endedEvents = [];
foreach ($allEvents as $ev) {
    $status = eventStatus($ev['start_date'], $ev['end_date']);
    if ($status === 'ongoing') $ongoingEvents[] = $ev;
    elseif ($status === 'upcoming') $upcomingEvents[] = $ev;
    else $endedEvents[] = $ev;
}

$actLabels = [1 => '★ 最活跃', 2 => '① 很活跃', 3 => '② 较活跃', 4 => '③ 一般', 5 => '④ 相对安静'];
$evTypeLabels = ['offline' => '线下活动', 'online' => '线上活动', 'birthday' => '生日会', 'other' => '其他'];

$itemProps = [];
foreach ($allApprovedGroups as $g) {
    $rows = [];
    $rows[] = ['群名称', (string)$g['name']];
    $rows[] = ['主群号', (string)$g['qq_group']];
    if (!empty($g['backup_qq_group'])) $rows[] = ['备用群号', (string)$g['backup_qq_group']];
    $rows[] = ['地区', trim((string)($g['province'] ?? '') . ($g['city'] ? ' · ' . $g['city'] : '')) ?: '非地区性群'];
    $rows[] = ['层级', ((string)$g['level'] === 'city') ? '市级群' : '省级群'];
    if (!empty($g['member_count'])) $rows[] = ['成员数', (string)(int)$g['member_count'] . ' 人'];
    $rows[] = ['活跃度', $actLabels[(int)$g['activity_level']] ?? '③ 一般'];
    if (!empty($g['description'])) $rows[] = ['简介', (string)$g['description']];
    if (!empty($g['public_note'])) $rows[] = ['公告', (string)$g['public_note']];
    $rows[] = ['收录时间', (string)$g['created_at']];
    $itemProps['group:' . (int)$g['id']] = [
        'kind' => 'group',
        'id' => (int)$g['id'],
        'title' => (string)$g['name'],
        'rows' => $rows,
        'can_edit' => ($isAdmin || canManageGroup((int)$g['id'])) ? 1 : 0,
        'edit_url' => 'edit_group.php?id=' . (int)$g['id'],
    ];
}
foreach ($allEvents as $ev) {
    $rows = [];
    $rows[] = ['活动名称', (string)$ev['title']];
    $rows[] = ['类型', $evTypeLabels[(string)$ev['type']] ?? '其他'];
    $rows[] = ['时间', (string)$ev['start_date'] . ' ~ ' . (string)$ev['end_date']];
    $rows[] = ['地区', trim((string)($ev['province'] ?? '') . ($ev['city'] ? ' · ' . $ev['city'] : '')) ?: '线上 / 全国'];
    if (!empty($ev['address'])) $rows[] = ['地址', (string)$ev['address']];
    if (!empty($ev['qq_group'])) $rows[] = ['主群号', (string)$ev['qq_group']];
    if (!empty($ev['backup_qq_group'])) $rows[] = ['备用群号', (string)$ev['backup_qq_group']];
    if (!empty($ev['description'])) $rows[] = ['简介', (string)$ev['description']];
    if (!empty($ev['public_note'])) $rows[] = ['公告', (string)$ev['public_note']];
    $rows[] = ['收录时间', (string)$ev['created_at']];
    $itemProps['event:' . (int)$ev['id']] = [
        'kind' => 'event',
        'id' => (int)$ev['id'],
        'title' => (string)$ev['title'],
        'rows' => $rows,
        'can_edit' => $isAdmin ? 1 : 0,
        'edit_url' => 'edit_event.php?id=' . (int)$ev['id'],
    ];
}

$siteName = getConfig('site_name', SITE_NAME);

global $CITY_CENTER_MAP, $PROVINCE_CENTER_MAP;

fillGroupCoords($groups, $CITY_CENTER_MAP, $PROVINCE_CENTER_MAP);
fillGroupCoords($otherGroups, $CITY_CENTER_MAP, $PROVINCE_CENTER_MAP);
fillGroupCoords($allApprovedGroups, $CITY_CENTER_MAP, $PROVINCE_CENTER_MAP);

$TIANDITU_KEYS = function_exists('getTiandituKeys') ? getTiandituKeys() : [];
if (empty($TIANDITU_KEYS) && isset($GLOBALS['TIANDITU_KEYS_DEFAULT'])) {
    $TIANDITU_KEYS = $GLOBALS['TIANDITU_KEYS_DEFAULT'];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= e($siteName) ?>">
<meta name="theme-color" content="#66ccff">
<meta name="format-detection" content="telephone=no,email=no,address=no">
<?php $favIcon = getConfig('favicon', ''); if ($favIcon !== ''): ?><?php if (preg_match('#^https?://#i', $favIcon)): ?><link rel="icon" href="<?= e($favIcon) ?>"><?php else: ?><?php $favPath = __DIR__ . '/' . ltrim($favIcon, '/'); ?><link rel="icon" href="favicon.php<?= is_file($favPath) ? '?v=' . filemtime($favPath) : '' ?>"><?php endif; ?><?php endif; ?>
<title><?= e($siteName) ?></title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
html, body { height: 100%; overflow: hidden; -webkit-tap-highlight-color: transparent; }

#map { position: absolute; top: 58px; left: 0; right: 0; bottom: 0; z-index: 1; }

.loading-bar {
    position: fixed; top: 58px; left: 0; right: 0; height: 3px;
    background: transparent; z-index: 1200; pointer-events: none;
    opacity: 1; transition: opacity .6s ease;
}
.loading-bar.done { opacity: 0; }
.loading-bar .bar {
    height: 100%; width: 0%;
    background: linear-gradient(90deg, #66ccff, #4aa3e8);
    transition: width .4s ease;
    box-shadow: 0 0 8px rgba(102,204,255,.8);
}
.loading-bar .label {
    position: absolute; top: 6px; left: 50%; transform: translateX(-50%);
    background: rgba(255,255,255,.95); padding: .2rem .8rem; border-radius: 20px;
    font-size: .72rem; color: #3a5b6b;
    box-shadow: 0 2px 8px rgba(0,0,0,.08); white-space: nowrap;
    transition: opacity .6s ease;
}
.loading-bar.done .label { opacity: 0; }

.header {
    position: fixed; top: 0; left: 0; right: 0; z-index: 1100;
    height: 58px;
    background: rgba(255,255,255,.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(102,204,255,.25);
    padding: 0 2rem;
    display: flex; align-items: center; justify-content: space-between;
    gap: 1rem; box-sizing: border-box;
}
.header .logo {
    font-size: 1.2rem; font-weight: 700; color: #1a2e3b;
    text-decoration: none;
    display: flex; align-items: center; gap: .5rem;
    flex-shrink: 0;
}
.header .version-badge {
    font-size: .66rem; font-weight: 700; color: #fff;
    background: #66ccff; padding: .15rem .55rem; border-radius: 20px;
}
.header .nav { display: flex; gap: .3rem; align-items: center; flex-wrap: wrap; justify-content: flex-end; }
.header .nav a {
    color: #3a5b6b; text-decoration: none; font-size: .86rem;
    padding: .5rem .85rem; border-radius: .7rem;
    transition: .25s; font-weight: 500; white-space: nowrap;
    display: inline-flex; align-items: center; gap: .4rem;
}
.header .nav a:hover { background: rgba(102,204,255,.15); color: #1a2e3b; }

.header-toggle {
    display: none;
    background: none; border: none; font-size: 1.5rem; color: #1a2e3b;
    cursor: pointer; padding: .3rem .6rem; border-radius: .6rem;
}
.header-toggle:hover { background: rgba(102,204,255,.15); }

.header-menu {
    display: none;
    position: fixed; top: 58px; right: 0; left: 0; z-index: 1090;
    background: rgba(255,255,255,.98); backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(102,204,255,.25);
    box-shadow: 0 8px 24px rgba(0,0,0,.08);
    max-height: 0; overflow: hidden;
    transition: max-height .3s ease;
}
.header-menu.open { max-height: 50vh; overflow-y: auto; }

.topbar-fab {
    display: none;
    position: fixed; top: 8px; right: 14px; z-index: 1300;
    width: 44px; height: 44px; border-radius: 50%;
    border: none; background: #66ccff; color: #fff;
    font-size: 1.3rem; line-height: 1; font-weight: 700;
    cursor: pointer; user-select: none;
    box-shadow: 0 6px 24px rgba(102,204,255,.5);
    align-items: center; justify-content: center;
    transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
}
.topbar-fab:hover { transform: scale(1.07); }
.topbar-fab.pinned { box-shadow: 0 0 0 3px rgba(255,255,255,.95), 0 6px 24px rgba(102,204,255,.55); }

.header-menu .menu-item {
    display: flex; align-items: center; gap: .6rem;
    padding: .9rem 1.4rem; color: #3a5b6b; text-decoration: none;
    font-size: .92rem; border-bottom: 1px solid rgba(102,204,255,.1);
}
.header-menu .menu-item:last-child { border-bottom: none; }
.header-menu .menu-item:hover { background: rgba(102,204,255,.08); color: #1a2e3b; }
.header-menu .menu-item-gh { font-weight: 600; }
.header-menu .menu-gh-icon { width: 16px; height: 16px; flex: none; }

.tianyi-deco {
    position: fixed; right: -2vw; bottom: -3vh; z-index: 6;
    height: min(52vh, 480px); aspect-ratio: 440/1004;
    pointer-events: none; user-select: none;
    background: rgba(102,204,255,.1); filter: blur(2px);
    -webkit-mask: url('tianyi.png') no-repeat center bottom / contain;
    mask: url('tianyi.png') no-repeat center bottom / contain;
}

.sidebar-toggle {
    display: none;
    position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 1500;
    width: 56px; height: 56px; border-radius: 50%;
    background: #66ccff; color: #fff; border: none;
    box-shadow: 0 6px 24px rgba(102,204,255,.5);
    font-size: 1.4rem; cursor: pointer;
    align-items: center; justify-content: center;
}

.legend {
    position: absolute; bottom: 1rem; left: 1rem; z-index: 1000;
    background: rgba(255,255,255,.95); backdrop-filter: blur(8px);
    border-radius: 1rem; padding: .9rem 1.1rem;
    box-shadow: 0 4px 20px rgba(0,0,0,.1);
    font-size: .78rem; max-width: 200px;
}
.legend-title { font-weight: 800; color: #1a2e3b; margin-bottom: .5rem; font-size: .82rem; }
.legend-item { display: flex; align-items: center; gap: .45rem; margin-bottom: .35rem; }
.legend-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }
.legend-swatch { width: 18px; height: 12px; border-radius: 3px; flex-shrink: 0; border: 1.5px solid; }

.attribution {
    position: absolute; bottom: 1rem; right: 1rem; z-index: 1000;
    background: rgba(255,255,255,.95); backdrop-filter: blur(8px);
    border-radius: 1rem; padding: .5rem .9rem;
    box-shadow: 0 4px 20px rgba(0,0,0,.1);
    display: flex; align-items: center; gap: .6rem;
    font-size: .68rem; color: #3a5b6b; line-height: 1.4;
    max-width: 320px;
}
.attribution img { height: 32px; width: auto; display: block; }
.attribution .txt { display: flex; flex-direction: column; gap: .1rem; }
.attribution .txt .row1 { font-weight: 600; color: #1a2e3b; font-size: .72rem; }
.attribution .txt .row2 { font-size: .66rem; color: #7d98aa; }

.sidebar {
    position: absolute; top: 62px; right: 14px; z-index: 1000;
    background: rgba(255,255,255,.96); backdrop-filter: blur(10px);
    border-radius: 1rem; width: 300px;
    box-shadow: 0 8px 32px rgba(0,0,0,.12);
    max-height: calc(100vh - 76px);
    display: flex; flex-direction: column; overflow: hidden;
}
.sidebar-tabs { display: flex; border-bottom: 1px solid #eef2f7; }
.sidebar-tabs button {
    flex: 1; padding: .8rem .5rem; border: none; background: transparent;
    font-size: .85rem; font-weight: 700; color: #7d98aa; cursor: pointer;
    transition: .2s; font-family: inherit;
}
.sidebar-tabs button.active { color: #66ccff; border-bottom: 2px solid #66ccff; }
.sidebar-tabs button:hover { background: rgba(102,204,255,.05); }

.sidebar-content { overflow-y: auto; padding: .8rem; flex: 1; -webkit-overflow-scrolling: touch; }
.sidebar-section-title {
    font-size: .72rem; text-transform: uppercase; letter-spacing: .06em;
    color: #7d98aa; font-weight: 700; margin: .6rem 0 .4rem;
}
.sidebar-item {
    position: relative;
    padding: .7rem 2.1rem .7rem .9rem; border-radius: .8rem; margin-bottom: .5rem;
    cursor: pointer; transition: .2s; border-left: 3px solid transparent;
    background: #fafcfe;
}
.sidebar-item:hover { background: rgba(102,204,255,.08); }
.sidebar-item.active { background: rgba(102,204,255,.15); border-left-color: #66ccff; }
.sidebar-item .name { font-weight: 700; font-size: .85rem; color: #1e3b4a; margin-bottom: .2rem; }
.sidebar-item .meta { font-size: .75rem; color: #7d98aa; }
.sidebar-item .qq { color: #66ccff; font-weight: 700; }

.popup-content { font-size: .85rem; line-height: 1.7; }
.popup-content .name { font-weight: 800; color: #1a2e3b; font-size: 1rem; margin-bottom: .4rem; }
.popup-content .qq { color: #66ccff; font-weight: 700; font-size: .95rem; }
.popup-content .meta { color: #7d98aa; font-size: .8rem; margin-top: .2rem; }
.popup-content .event-type {
    display: inline-block; padding: .15rem .5rem; border-radius: 20px;
    font-size: .7rem; font-weight: 700; color: #fff; margin-right: .3rem;
}

.backdrop {
    display: none;
    position: fixed; left: 0; right: 0; bottom: 0; top: 0;
    background: rgba(0,0,0,.35); z-index: 1080;
    transition: top .25s ease;
}
.backdrop.show { display: block; }

@media (max-width: 720px) {
    .header { padding: 0 1rem; height: 54px; }
    .header .logo { font-size: .95rem; }
    .header .version-badge { display: none; }
    .header .nav { display: none; }
    .header-toggle { display: block; }
    .header-menu { display: block; top: 54px; }
    #map { top: 54px; }
    .loading-bar { top: 54px; }
    .legend { display: none; }
    .attribution { display: none; }
    .tianyi-deco { height: 34vh; right: -7vw; bottom: -2vh; }
    .sidebar {
        top: auto; bottom: 0; left: 0; right: 0;
        width: 100%; max-height: 56vh;
        border-radius: 1.4rem 1.4rem 0 0;
        transform: translateY(100%);
        transition: transform .3s ease;
        z-index: 1400;
    }
    .sidebar.open { transform: translateY(0); }
    .sidebar-content { -webkit-overflow-scrolling: touch; }
    .sidebar-toggle { display: flex; }
}

#map{top:68px;background:#e9f6fb}
.loading-bar{top:68px;height:2px}.loading-bar .label{top:10px;padding:.35rem .75rem;background:rgba(255,255,255,.82);border:1px solid rgba(102,204,255,.22);box-shadow:0 10px 24px -16px rgba(25,83,113,.5);backdrop-filter:blur(12px)}
.header{height:68px;padding:0 max(1.4rem,calc((100vw - 1440px)/2));background:rgba(255,255,255,.76);border-bottom-color:rgba(126,177,207,.24);box-shadow:0 8px 28px rgba(47,106,138,.08);backdrop-filter:blur(22px) saturate(145%)}
.header .logo{font-size:1.05rem;letter-spacing:-.025em}.header .logo:before{content:"♪";display:grid;place-items:center;width:28px;height:28px;border-radius:10px;background:linear-gradient(135deg,#66ccff,#4aa3e8);box-shadow:0 7px 16px rgba(102,204,255,.35);color:#fff;font-size:1rem}.header .logo.has-logo-img:before{display:none}.header .version-badge{background:rgba(102,204,255,.14);color:#267ca8;border:1px solid rgba(102,204,255,.24);padding:.18rem .5rem}
.header .nav{gap:.15rem}.header .nav a{padding:.55rem .75rem;color:#547083;font-size:.84rem;border:1px solid transparent}.header .nav a:hover{background:rgba(255,255,255,.85);border-color:rgba(126,177,207,.24);box-shadow:0 5px 14px rgba(39,96,126,.07);color:#173447}.header-toggle{border:1px solid rgba(126,177,207,.24);background:rgba(255,255,255,.7);font-size:1.2rem}.header-menu{top:68px;background:rgba(249,253,255,.95);border-color:rgba(126,177,207,.24);box-shadow:0 16px 32px rgba(31,83,111,.12);backdrop-filter:blur(22px)}.header-menu .menu-item{padding:1rem 1.35rem;color:#48677a;border-bottom-color:rgba(126,177,207,.16)}
.sidebar{top:84px;right:max(1.4rem,calc((100vw - 1440px)/2));width:330px;max-height:calc(100vh - 100px);background:rgba(255,255,255,.78);border:1px solid rgba(255,255,255,.9);border-radius:1.35rem;box-shadow:0 20px 46px -28px rgba(20,78,108,.5),inset 0 1px 0 rgba(255,255,255,.8);backdrop-filter:blur(22px) saturate(135%)}
.sidebar-tabs{padding:.5rem;border-bottom:1px solid rgba(126,177,207,.14);gap:.35rem}.sidebar-tabs button{padding:.62rem .5rem;border-radius:.75rem;color:#668094}.sidebar-tabs button.active{color:#176c96;border-bottom:0;background:rgba(102,204,255,.14);box-shadow:inset 0 0 0 1px rgba(102,204,255,.2)}.sidebar-tabs button:hover{background:rgba(255,255,255,.68)}
.sidebar-content{padding:.7rem}.sidebar-section-title{margin:1rem .35rem .45rem;color:#7290a1;font-size:.68rem;letter-spacing:.12em}.sidebar-section-title:first-child{margin-top:.3rem}.sidebar-item{padding:.8rem .85rem;border:1px solid transparent;border-left:3px solid transparent;background:rgba(255,255,255,.47);box-shadow:0 7px 16px -18px rgba(20,78,108,.65)}.sidebar-item:hover{background:rgba(255,255,255,.88);border-color:rgba(126,177,207,.16);transform:translateY(-1px)}.sidebar-item.active{background:linear-gradient(135deg,rgba(102,204,255,.17),rgba(74,163,232,.12));border-left-color:#66ccff}.sidebar-item .name{color:#173447;line-height:1.35}.sidebar-item .meta{line-height:1.55}.sidebar-item .qq{color:#2785b2}
.legend{bottom:1.5rem;left:max(1.4rem,calc((100vw - 1440px)/2));padding:1rem 1.05rem;background:rgba(255,255,255,.78);border:1px solid rgba(255,255,255,.92);border-radius:1.15rem;box-shadow:0 16px 32px -24px rgba(20,78,108,.55);backdrop-filter:blur(18px)}.legend-title{color:#173447}.legend-item{color:#5e788a}.legend-swatch{border-radius:8px}
.attribution{bottom:1.5rem;left:max(216px,calc((100vw - 1440px)/2 + 202px));background:rgba(255,255,255,.78);border:1px solid rgba(255,255,255,.92);box-shadow:0 16px 32px -24px rgba(20,78,108,.55);backdrop-filter:blur(18px)}
.leaflet-control-zoom{border:1px solid rgba(255,255,255,.9)!important;border-radius:12px!important;overflow:hidden;box-shadow:0 14px 28px -18px rgba(20,78,108,.65)!important}.leaflet-control-zoom a{background:rgba(255,255,255,.88)!important;color:#38657c!important;border-bottom-color:rgba(126,177,207,.16)!important}.leaflet-control-zoom a:hover{background:#fff!important;color:#187fac!important}.leaflet-popup-content-wrapper,.leaflet-popup-tip{background:rgba(255,255,255,.94);box-shadow:0 18px 36px -22px rgba(20,78,108,.6)}.leaflet-popup-content{margin:15px 17px}.popup-content .name{color:#173447}.popup-content .qq{color:#2785b2}
@media (max-width:720px){#map{top:54px}.loading-bar{top:54px}.header{height:54px;padding:0 1rem}.header .logo:before{width:25px;height:25px;border-radius:8px;font-size:.9rem}.header-menu{top:54px}.sidebar-toggle{right:1rem;bottom:1rem;width:54px;height:54px;background:linear-gradient(135deg,#66ccff,#4aa3e8);box-shadow:0 12px 26px -10px rgba(79,159,210,.8)}.backdrop{top:54px}}

@media (min-width: 721px) {

    #map { top: 0 !important; }
    .loading-bar { top: 0 !important; }
    .topbar-fab { display: flex !important; top: 20px !important; right: min(368px, calc(100vw - 350px)) !important; }

    .leaflet-top.leaflet-left { top: 64px !important; }

    .header { display: none !important; }
    .header.open, body.topbar-open .header {
        display: flex !important;
        position: fixed; top: 84px; right: min(372px, calc(100vw - 354px)); left: auto;
        width: 340px; max-width: calc(100vw - 28px); height: auto;
        flex-direction: column; align-items: stretch; gap: .15rem;
        padding: .9rem 1rem;
        background: rgba(255,255,255,.97);
        backdrop-filter: blur(22px) saturate(145%);
        border: 1px solid rgba(126,177,207,.22);
        border-radius: 1.1rem;
        box-shadow: 0 18px 44px -18px rgba(20,78,108,.5);
        animation: topbarIn .16s ease;
        z-index: 1250;
    }
    .header.open .logo { margin-bottom: .5rem; }
    .header .nav { flex-direction: column; flex-wrap: nowrap; gap: .15rem; justify-content: flex-start; }
    .header .nav a { width: 100%; box-sizing: border-box; }
    .header .nav-divider { display: none; }
}
@keyframes topbarIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }
@keyframes topbarOut { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(-6px); } }

.header.open.closing { animation: topbarOut .15s ease forwards; pointer-events: none; }

@media (min-width: 721px) {
    .sidebar {
        top: 14px !important;
        right: 14px !important;
        max-height: calc(100vh - 28px) !important;
    }
    .legend { left: 14px !important; bottom: 14px !important; }

    .attribution { left: 156px !important; right: auto !important; bottom: 14px !important; }
}

.bound-mode-ctrl {
    position: fixed; top: 14px; left: 14px; z-index: 1100;
    display: flex; padding: 4px; overflow: hidden;
    background: rgba(255,255,255,.96);
    backdrop-filter: blur(14px) saturate(140%);
    border: 1px solid rgba(126,177,207,.25);
    border-radius: 999px;
    box-shadow: 0 10px 26px -14px rgba(20,78,108,.45);
}
.bound-mode-ctrl button {
    border: 0; background: transparent; color: #4a6b7d;
    font: inherit; font-size: .82rem; padding: .35rem .85rem;
    border-radius: 999px; cursor: pointer; white-space: nowrap;
    transition: background .15s, color .15s;
}
.bound-mode-ctrl button:hover { background: rgba(102,204,255,.16); color: #23739b; }
.bound-mode-ctrl button.active {
    background: #66ccff; color: #fff; font-weight: 700;
    box-shadow: 0 4px 12px -4px rgba(102,204,255,.7);
}
@media (max-width: 720px) {
    .bound-mode-ctrl { top: 10px; left: 10px; }
    .bound-mode-ctrl button { padding: .3rem .6rem; font-size: .75rem; }
}


:root{
--md-primary:#0f6d9e;
--md-on-primary:#ffffff;
--md-primary-container:#cbe7ff;
--md-on-primary-container:#001e30;
--md-secondary:#4c616e;
--md-secondary-container:#d0e5f2;
--md-on-secondary-container:#071e29;
--md-tertiary:#605a7d;
--md-tertiary-container:#e6deff;
--md-surface:#f6f9fc;
--md-surface-container-lowest:#ffffff;
--md-surface-container-low:#f0f4f8;
--md-surface-container:#eaeef3;
--md-surface-container-high:#e4e8ed;
--md-surface-container-highest:#dfe3e8;
--md-on-surface:#181c20;
--md-on-surface-variant:#40484d;
--md-outline:#70787e;
--md-outline-variant:#c0c8ce;
--md-error:#ba1a1a;
--md-error-container:#ffdad6;
--md-on-error-container:#410002;
--md-success-container:#c5f0d8;
--md-on-success-container:#0b3820;
}
body{
background:var(--md-surface) !important;
color:var(--md-on-surface) !important;
font-family:'Roboto','Noto Sans SC','HarmonyOS Sans SC','Microsoft YaHei',system-ui,-apple-system,'Segoe UI',sans-serif !important;
}
h1{font-size:28px !important;line-height:36px !important;font-weight:400 !important;letter-spacing:0 !important;color:var(--md-on-surface) !important}
h2{font-size:22px !important;line-height:28px !important;font-weight:400 !important;color:var(--md-on-surface) !important}
h3{font-size:16px !important;line-height:24px !important;font-weight:500 !important;color:var(--md-on-surface) !important}
a{color:var(--md-primary)}
.btn{
border-radius:999px !important;
min-height:40px;
display:inline-flex;
align-items:center;
justify-content:center;
padding:0 24px !important;
font-size:14px !important;
font-weight:500 !important;
letter-spacing:.1px;
border:none !important;
box-shadow:none !important;
text-decoration:none !important;
cursor:pointer;
transition:box-shadow .2s ease,background .2s ease,transform .1s ease;
}
.btn:hover{box-shadow:0 1px 3px 1px rgba(0,0,0,.15),0 1px 2px rgba(0,0,0,.3) !important}
.btn:active{transform:scale(.98)}
.btn-primary{background:var(--md-primary) !important;color:var(--md-on-primary) !important}
.btn-secondary{background:var(--md-secondary-container) !important;color:var(--md-on-secondary-container) !important}
.btn-danger{background:var(--md-error) !important;color:#ffffff !important}
.btn-outline{background:transparent !important;color:var(--md-primary) !important;border:1px solid var(--md-outline) !important}
.btn-sm{min-height:32px !important;padding:0 16px !important;font-size:13px !important}
.btn-block{width:100%}
.card{
background:var(--md-surface-container-low) !important;
border-radius:16px !important;
border:1px solid var(--md-outline-variant) !important;
box-shadow:none !important;
}
.field label{
display:block;
font-size:12px !important;
font-weight:500 !important;
letter-spacing:.4px;
color:var(--md-on-surface-variant) !important;
margin-bottom:4px !important;
}
.field input,
.field select,
.field textarea,
input[type=text],input[type=email],input[type=password],input[type=number],input[type=date],input[type=url],input[type=time],select,textarea{
background:var(--md-surface-container-highest) !important;
border:1px solid transparent !important;
border-bottom-color:var(--md-on-surface-variant) !important;
border-radius:8px 8px 0 0 !important;
color:var(--md-on-surface) !important;
transition:border-bottom-color .2s ease;
}
.field input:focus,
.field select:focus,
.field textarea:focus,
input:focus,select:focus,textarea:focus{
border-bottom:2px solid var(--md-primary) !important;
outline:none !important;
}
input::placeholder,textarea::placeholder{color:var(--md-outline) !important}
.message{
border-radius:12px !important;
border:none !important;
font-size:14px !important;
}
.message.success{background:var(--md-success-container) !important;color:var(--md-on-success-container) !important}
.message.error{background:var(--md-error-container) !important;color:var(--md-on-error-container) !important}
table{border-radius:12px;overflow:hidden}
th{background:var(--md-surface-container) !important;color:var(--md-on-surface-variant) !important;font-weight:500 !important}
td{border-color:var(--md-outline-variant) !important}
tr:hover td{background:var(--md-surface-container-low)}
.badge{border-radius:999px !important;font-weight:500 !important}

.header{
background:var(--md-surface) !important;
backdrop-filter:none !important;
box-shadow:none !important;
border-bottom:1px solid var(--md-outline-variant) !important;
}
.header .logo{color:var(--md-on-surface) !important}
.header .nav a{color:var(--md-on-surface-variant) !important;border-radius:999px !important}
.header .nav a:hover{background:var(--md-secondary-container) !important;color:var(--md-on-secondary-container) !important;box-shadow:none !important}
.header-menu{background:var(--md-surface-container) !important;border-color:var(--md-outline-variant) !important}

.legend{background:var(--md-surface-container) !important;border:1px solid var(--md-outline-variant) !important;backdrop-filter:none !important;box-shadow:none !important;border-radius:16px !important}
.legend-title{color:var(--md-on-surface) !important}
.legend-item{color:var(--md-on-surface-variant) !important}
.attribution{background:var(--md-surface-container) !important;border:1px solid var(--md-outline-variant) !important;box-shadow:none !important}
.leaflet-popup-content-wrapper,.leaflet-popup-tip{background:var(--md-surface-container-lowest) !important;box-shadow:0 2px 6px rgba(0,0,0,.18) !important;border-radius:16px !important}
.popup-content .name{color:var(--md-on-surface) !important}
.popup-content .qq{color:var(--md-primary) !important}
.sidebar{background:var(--md-surface-container-low) !important;border:1px solid var(--md-outline-variant) !important;backdrop-filter:none !important}
.sidebar-item{background:var(--md-surface-container-lowest) !important;border-radius:12px !important;border:1px solid transparent !important;border-left:none !important;box-shadow:none !important}
.sidebar-item:hover{background:var(--md-surface-container) !important;border-color:var(--md-outline-variant) !important;transform:none !important}
.sidebar-item.active{background:var(--md-secondary-container) !important;border:1px solid var(--md-primary) !important}
.sidebar-item .name{color:var(--md-on-surface) !important}
.sidebar-item .meta{color:var(--md-on-surface-variant) !important}
.sidebar-item .qq{color:var(--md-primary) !important}
.sidebar-tabs button{color:var(--md-on-surface-variant) !important}
.sidebar-tabs button.active{color:var(--md-primary) !important;background:var(--md-secondary-container) !important;box-shadow:none !important}
.bound-mode-ctrl{background:var(--md-secondary-container) !important;border:none !important;backdrop-filter:none !important}
.bound-mode-ctrl button{color:var(--md-on-secondary-container) !important}
.bound-mode-ctrl button.active{background:var(--md-primary) !important;color:var(--md-on-primary) !important;box-shadow:none !important}
.topbar-fab{background:var(--md-primary) !important;color:var(--md-on-primary) !important;box-shadow:0 3px 6px rgba(0,0,0,.25) !important;border-radius:28px !important}
.loading-bar .label{background:var(--md-surface-container) !important;border:1px solid var(--md-outline-variant) !important;color:var(--md-on-surface-variant) !important}
.leaflet-control-zoom{border-color:var(--md-outline-variant) !important;border-radius:12px !important}
.leaflet-control-zoom a{background:var(--md-surface-container-lowest) !important;color:var(--md-on-surface-variant) !important;border-bottom-color:var(--md-outline-variant) !important}
.leaflet-control-zoom a:hover{background:var(--md-surface-container) !important;color:var(--md-primary) !important}

</style>
</head>
<body>

<div id="map"></div>
<div class="tianyi-deco" aria-hidden="true"></div>

<div class="loading-bar" id="loadingBar">
    <div class="bar" id="loadingBarInner"></div>
    <div class="label" id="loadingLabel">加载地图数据...</div>
</div>

<div class="bound-mode-ctrl" id="boundModeCtrl" role="group" aria-label="边界查看模式">
    <button type="button" data-mode="default" class="active">默认边界</button>
    <button type="button" data-mode="province">只看省级边界</button>
    <button type="button" data-mode="city">只看市级边界</button>
</div>

    <button class="topbar-fab" id="topbarFab" title="展开/固定顶栏">☰</button>
<div class="header" id="siteHeader">
    <a href="index.php" class="logo<?php if (!empty(getConfig('logo', ''))) echo ' has-logo-img'; ?>">
        <?php if (!empty(getConfig('logo', ''))): ?><img src="<?= e(getConfig('logo', '')) ?>" alt="" style="width:28px;height:28px;border-radius:10px;object-fit:contain;background:#fff;box-shadow:0 7px 16px rgba(102,204,255,.35);padding:2px"><?php endif; ?>
        <?= e($siteName) ?>
        <span class="version-badge">v<?= SITE_VERSION ?></span>
    </a>
    <nav class="nav">
        <a href="index.php">地图</a>
        <a href="agreement.php">用户协议</a>
        <a href="api.php?action=groups">数据接口</a>
    </nav>
    <button class="header-toggle" id="headerToggle">☰</button>
</div>

<div class="header-menu" id="headerMenu">
    <a href="index.php" class="menu-item">地图</a>
    <a href="agreement.php" class="menu-item">用户协议</a>
    <a href="api.php?action=groups" class="menu-item">数据接口</a>
        <a href="feedback.php" class="menu-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="menu-gh-icon"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            <span>反馈</span>
        </a>
        <a href="https://github.com/Tianyi-Communtiy-Team/CVIMap" target="_blank" rel="noopener" class="menu-item menu-item-gh">
            <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="menu-gh-icon"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27s1.36.09 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8z"/></svg>
            <span>GitHub 开源仓库</span>
        </a>
    </div>

<button class="sidebar-toggle" id="sidebarToggle">☰</button>

<div class="backdrop" id="backdrop"></div>

<div class="legend">
    <div class="legend-title">群活跃度</div>
    <div class="legend-item"><span class="legend-swatch" style="background:rgba(231,76,60,.3);border-color:#e74c3c"></span> ★ 最活跃</div>
    <div class="legend-item"><span class="legend-swatch" style="background:rgba(230,126,34,.3);border-color:#e67e22"></span> ① 很活跃</div>
    <div class="legend-item"><span class="legend-swatch" style="background:rgba(241,196,15,.3);border-color:#f1c40f"></span> ② 较活跃</div>
    <div class="legend-item"><span class="legend-swatch" style="background:rgba(46,204,113,.3);border-color:#2ecc71"></span> ③ 一般</div>
    <div class="legend-item"><span class="legend-swatch" style="background:rgba(39,174,96,.3);border-color:#27ae60"></span> ④ 相对安静</div>
    <div class="legend-item"><span class="legend-swatch" style="background:rgba(200,208,212,.12);border-color:#a9b4ba"></span> 无群城市</div>
</div>

<div class="attribution">
    <img src="/tianditu-logo.svg" alt="天地图">
    <div class="txt">
        <div class="row1">GS（2026）4921号</div>
    <div class="row2">甲测资字 11110974</div>
</div>
</div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-tabs">
        <button class="active" data-tab="groups">粉丝群</button>
        <button data-tab="events">活动</button>
    </div>
    <div class="sidebar-content">
        <div id="tab-groups" style="display:block">
            <?php if (empty($groups) && empty($otherGroups)): ?>
                <div style="padding:1.5rem;text-align:center;color:#7d98aa;font-size:.85rem">暂无粉丝群</div>
            <?php else: ?>
                <?php if (!empty($groups)): ?>
                    <div class="sidebar-section-title">地区性群</div>
                    <?php foreach ($groups as $g): ?>
                        <div class="sidebar-item" id="sidebar-group-<?= (int)$g['id'] ?>" data-group-id="<?= (int)$g['id'] ?>">
                            <button type="button" class="item-more" data-ctx="group" data-id="<?= (int)$g['id'] ?>" aria-label="更多操作">⋮</button>
                            <div class="name"><?= e($g['name']) ?></div>
                            <div class="meta">QQ群：<span class="qq"><?= e($g['qq_group']) ?></span></div>
                            <div class="meta"><?= e($g['province']) ?><?= $g['city'] ? ' · ' . e($g['city']) : '' ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($otherGroups)): ?>
                    <div class="sidebar-section-title">非地区性群</div>
                    <?php foreach ($otherGroups as $g): ?>
                        <div class="sidebar-item" id="sidebar-group-<?= (int)$g['id'] ?>" data-group-id="<?= (int)$g['id'] ?>">
                            <button type="button" class="item-more" data-ctx="group" data-id="<?= (int)$g['id'] ?>" aria-label="更多操作">⋮</button>
                            <div class="name"><?= e($g['name']) ?></div>
                            <div class="meta">QQ群：<span class="qq"><?= e($g['qq_group']) ?></span></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div id="tab-events" style="display:none">
            <?php if (!empty($ongoingEvents)): ?>
                <div class="sidebar-section-title">进行中</div>
                <?php foreach ($ongoingEvents as $ev): ?>
                    <div class="sidebar-item" id="sidebar-event-<?= (int)$ev['id'] ?>" data-event-id="<?= (int)$ev['id'] ?>">
                        <button type="button" class="item-more" data-ctx="event" data-id="<?= (int)$ev['id'] ?>" aria-label="更多操作">⋮</button>
                        <div class="name"><?= e($ev['title']) ?></div>
                        <div class="meta"><?= e($ev['start_date']) ?> ~ <?= e($ev['end_date']) ?></div>
                        <div class="meta"><?= e($ev['province']) ?> · <?= e($ev['city']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (!empty($upcomingEvents)): ?>
                <div class="sidebar-section-title">计划中</div>
                <?php foreach ($upcomingEvents as $ev): ?>
                    <div class="sidebar-item" id="sidebar-event-<?= (int)$ev['id'] ?>" data-event-id="<?= (int)$ev['id'] ?>">
                        <button type="button" class="item-more" data-ctx="event" data-id="<?= (int)$ev['id'] ?>" aria-label="更多操作">⋮</button>
                        <div class="name"><?= e($ev['title']) ?></div>
                        <div class="meta"><?= e($ev['start_date']) ?> ~ <?= e($ev['end_date']) ?></div>
                        <div class="meta"><?= e($ev['province']) ?> · <?= e($ev['city']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (!empty($endedEvents)): ?>
                <div class="sidebar-section-title">已结束</div>
                <?php foreach (array_slice($endedEvents, 0, 20) as $ev): ?>
                    <div class="sidebar-item" style="opacity:.6" id="sidebar-event-<?= (int)$ev['id'] ?>" data-event-id="<?= (int)$ev['id'] ?>">
                        <button type="button" class="item-more" data-ctx="event" data-id="<?= (int)$ev['id'] ?>" aria-label="更多操作">⋮</button>
                        <div class="name"><?= e($ev['title']) ?></div>
                        <div class="meta"><?= e($ev['start_date']) ?> ~ <?= e($ev['end_date']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (empty($allEvents)): ?>
                <div style="padding:1.5rem;text-align:center;color:#7d98aa;font-size:.85rem">暂无活动</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var API_BASE = '<?= e(SITE_URL) ?>';
    var ITEM_PROPS = <?= json_encode($itemProps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var FEEDBACK_URL = 'https://github.com/Tianyi-Communtiy-Team/CVIMap/issues/new';
    var LOGGED_IN = <?= $isLogged ? 'true' : 'false' ?>;
    var TIANDITU_KEYS = <?= json_encode($TIANDITU_KEYS, JSON_UNESCAPED_UNICODE) ?>;
    var keyIndex = 0;
    var currentBaseLayer = null;
    var currentLabelLayer = null;
    var currentFailCount = 0;
    var switching = false;

    var today = new Date().toISOString().slice(0, 10);
    var usageKey = 'tdt_usage_' + today;
    var usage = {};
    try { usage = JSON.parse(localStorage.getItem(usageKey) || '{}'); } catch (e) { usage = {}; }

    function markUsed() {
        var idx = keyIndex % TIANDITU_KEYS.length;
        usage[idx] = (usage[idx] || 0) + 1;
        try { localStorage.setItem(usageKey, JSON.stringify(usage)); } catch (e) {}
    }

    function buildUrl(pathName, layerName, tk, sk) {
        var url = 'https://t{s}.tianditu.gov.cn/' + pathName + '/wmts'
            + '?SERVICE=WMTS'
            + '&REQUEST=GetTile'
            + '&VERSION=1.0.0'
            + '&LAYER=' + layerName
            + '&STYLE=default'
            + '&TILEMATRIXSET=w'
            + '&FORMAT=tiles'
            + '&TILEMATRIX={z}'
            + '&TILEROW={y}'
            + '&TILECOL={x}'
            + '&tk=' + encodeURIComponent(tk);
        if (sk) url += '&sk=' + encodeURIComponent(sk);
        return url;
    }

    function onTileError() {
        if (switching) return;
        currentFailCount++;
        if (currentFailCount >= 3) switchToNextKey();
    }

    function switchToNextKey() {
        if (switching) return;
        if (!TIANDITU_KEYS.length) return;
        switching = true;
        keyIndex++;
        currentFailCount = 0;
        var key = TIANDITU_KEYS[keyIndex % TIANDITU_KEYS.length];
        console.log('[天地图] 切换到密钥索引 ' + (keyIndex % TIANDITU_KEYS.length));
        if (currentBaseLayer) map.removeLayer(currentBaseLayer);
        if (currentLabelLayer) map.removeLayer(currentLabelLayer);
        mountTileLayers(key.tk, key.sk);
        setTimeout(function() { switching = false; }, 500);
    }

    function safeBringToFront(layer) {
        if (layer && typeof layer.bringToFront === 'function') {
            try { layer.bringToFront(); } catch (e) {}
        }
    }

    function mountTileLayers(tk, sk) {
        var blank = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

        currentBaseLayer = L.tileLayer(buildUrl('vec_w', 'vec', tk, sk), {
            subdomains: ['0','1','2','3','4','5','6','7'],
            maxZoom: 18,
            errorTileUrl: blank,
            crossOrigin: true
        });
        currentLabelLayer = L.tileLayer(buildUrl('cva_w', 'cva', tk, sk), {
            subdomains: ['0','1','2','3','4','5','6','7'],
            maxZoom: 18,
            errorTileUrl: blank,
            crossOrigin: true
        });

        currentBaseLayer.on('tileerror', onTileError);
        currentLabelLayer.on('tileerror', onTileError);
        currentBaseLayer.on('tileload', markUsed);

        currentBaseLayer.addTo(map);
        currentLabelLayer.addTo(map);
        if (typeof currentBaseLayer.bringToBack === 'function') currentBaseLayer.bringToBack();

        safeBringToFront(provinceGroups);
        safeBringToFront(cityGroups);
        safeBringToFront(eventLayer);
    }

    var map = L.map('map', {
        center: [35.0, 105.0],
        zoom: 5,
        minZoom: 4,
        maxZoom: 12,
        zoomControl: window.innerWidth > 720 ? true : false,
        attributionControl: false
    });

    if (window.innerWidth <= 720 && map.zoomControl) {
        map.removeControl(map.zoomControl);
    }

    if (TIANDITU_KEYS.length) mountTileLayers(TIANDITU_KEYS[0].tk, TIANDITU_KEYS[0].sk);

    window.addEventListener('load', function() { map.invalidateSize(); });
    window.addEventListener('resize', function() { map.invalidateSize(); });

    (function() {
        var fab = document.getElementById('topbarFab');
        var header = document.getElementById('siteHeader');
        if (!fab || !header) return;
        var pinned = false;
        var hideTimer = null;
        var closeTimer = null;

        function show() {
            if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
            if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
            header.classList.remove('closing');
            document.body.classList.add('topbar-open');
            header.classList.add('open');
            setTimeout(function() { map.invalidateSize(); }, 320);
        }
        function hide() {
            if (pinned) return;
            if (!header.classList.contains('open') || header.classList.contains('closing')) return;
            header.classList.add('closing');
            closeTimer = setTimeout(function() {
                closeTimer = null;
                document.body.classList.remove('topbar-open');
                header.classList.remove('open', 'closing');
                map.invalidateSize();
            }, 150);
        }

        fab.addEventListener('mouseenter', show);
        header.addEventListener('mouseenter', show);
        header.addEventListener('mouseleave', hide);
        fab.addEventListener('mouseleave', function() {

            hideTimer = setTimeout(function() {
                if (!pinned && !header.matches(':hover')) hide();
            }, 120);
        });

        fab.addEventListener('click', function() {
            pinned = !pinned;
            fab.classList.toggle('pinned', pinned);
            if (pinned) {
                show();
            } else {

                document.body.classList.add('topbar-open');
                header.classList.add('open');
            }
        });
    })();

    var groups = <?= json_encode($groups, JSON_UNESCAPED_UNICODE) ?>;
    var otherGroups = <?= json_encode($otherGroups, JSON_UNESCAPED_UNICODE) ?>;
    var allApprovedGroups = <?= json_encode($allApprovedGroups, JSON_UNESCAPED_UNICODE) ?>;
    var ongoingEvents = <?= json_encode($ongoingEvents, JSON_UNESCAPED_UNICODE) ?>;
    var upcomingEvents = <?= json_encode($upcomingEvents, JSON_UNESCAPED_UNICODE) ?>;
    var endedEvents = <?= json_encode($endedEvents, JSON_UNESCAPED_UNICODE) ?>;

    var GROUP_COLORS = {1:'#e74c3c', 2:'#e67e22', 3:'#f1c40f', 4:'#2ecc71', 5:'#27ae60'};
    var REGION_COLORS = {1:'#e74c3c', 2:'#e67e22', 3:'#f1c40f', 4:'#2ecc71', 5:'#27ae60'};
    var EVENT_COLORS = {offline:'#e74c3c', online:'#2ecc71', birthday:'#66ccff', other:'#95a5a6'};

    var provinceGroups = L.featureGroup();
    var cityGroups = L.featureGroup();
    var eventLayer = L.featureGroup();
    var groupMarkers = {};
    var eventMarkers = {};

    function buildGroupPopup(g) {
        return '<div class="popup-content">' +
            '<div class="name">' + escapeHtml(g.name) + '</div>' +
            '<div>QQ群：<span class="qq">' + escapeHtml(g.qq_group) + '</span></div>' +
            (g.backup_qq_group ? '<div>备用群：<span class="qq">' + escapeHtml(g.backup_qq_group) + '</span></div>' : '') +
            '<div class="meta">' + escapeHtml(g.province) + (g.city ? ' · ' + escapeHtml(g.city) : '') + '</div>' +
            (g.description ? '<div class="meta">' + escapeHtml(g.description) + '</div>' : '') +
            (g.public_note ? '<div class="meta" style="color:#a45c1f">' + escapeHtml(g.public_note) + '</div>' : '') +
            '</div>';
    }

    var allSidebarGroups = groups.concat(otherGroups);
    allSidebarGroups.forEach(function(g) {
        var lat = parseFloat(g.lat);
        var lng = parseFloat(g.lng);
        if (lat === 0 && lng === 0) return;

        var color = GROUP_COLORS[g.activity_level] || '#27ae60';
        var m = L.circleMarker([lat, lng], {
            radius: g.level === 'province' ? 13 : 9,
            fillColor: color, color: '#fff', weight: 2, opacity: 1, fillOpacity: 0.9
        });
        m.bindPopup(buildGroupPopup(g));
        m.on('popupopen', function() { highlightSidebarGroup(g.id); });
        m.on('popupclose', function() { unhighlightSidebarGroup(g.id); });

        if (g.level === 'province') m.addTo(provinceGroups);
        else m.addTo(cityGroups);

        groupMarkers[String(g.id)] = m;
    });

    var allMarkerEvents = ongoingEvents.concat(upcomingEvents);
    allMarkerEvents.forEach(function(ev) {
        var lat = parseFloat(ev.lat);
        var lng = parseFloat(ev.lng);
        if (lat === 0 && lng === 0) return;

        var color = EVENT_COLORS[ev.type] || '#95a5a6';
        var m = L.circleMarker([lat, lng], {
            radius: 11,
            fillColor: color, color: '#fff', weight: 2, opacity: 1, fillOpacity: 0.9
        });
        var typeLabels = {offline:'线下活动', online:'线上活动', birthday:'生日会', other:'其他'};
        var popup = '<div class="popup-content">' +
            '<div class="name">' + escapeHtml(ev.title) + '</div>' +
            '<span class="event-type" style="background:' + color + '">' + (typeLabels[ev.type] || '其他') + '</span>' +
            '<div class="meta">' + escapeHtml(ev.start_date) + ' ~ ' + escapeHtml(ev.end_date) + '</div>' +
            '<div class="meta">' + escapeHtml(ev.province) + (ev.city ? ' · ' + escapeHtml(ev.city) : '') + '</div>' +
            (ev.address ? '<div class="meta">' + escapeHtml(ev.address) + '</div>' : '') +
            (ev.qq_group ? '<div class="meta">QQ群：<span class="qq">' + escapeHtml(ev.qq_group) + '</span></div>' : '') +
            (ev.backup_qq_group ? '<div class="meta">备用群：<span class="qq">' + escapeHtml(ev.backup_qq_group) + '</span></div>' : '') +
            (ev.description ? '<div class="meta" style="margin-top:.4rem">' + escapeHtml(ev.description) + '</div>' : '') +
            (ev.public_note ? '<div class="meta" style="color:#a45c1f">' + escapeHtml(ev.public_note) + '</div>' : '') +
            '</div>';
        m.bindPopup(popup);
        m.on('popupopen', function() { highlightSidebarEvent(ev.id); });
        m.on('popupclose', function() { unhighlightSidebarEvent(ev.id); });
        m.addTo(eventLayer);
        eventMarkers[String(ev.id)] = m;
    });

    eventLayer.addTo(map);

    var zoomDebounce = null;
    map.on('zoomend', function() {
        clearTimeout(zoomDebounce);
        zoomDebounce = setTimeout(function() {
            updateLayers();
        }, 80);
    });

    var provinceLevelMap = {};
    var provinceGroupMap = {};
    var cityLevelMap = {};
    var cityGroupMap = {};

    function normalizeProvince(s) {
        if (!s) return '';
        s = String(s).trim();
        var m2 = {
            '北京':'北京市','天津':'天津市','上海':'上海市','重庆':'重庆市',
            '河北':'河北省','山西':'山西省','辽宁':'辽宁省','吉林':'吉林省',
            '黑龙江':'黑龙江省','江苏':'江苏省','浙江':'浙江省','安徽':'安徽省',
            '福建':'福建省','江西':'江西省','山东':'山东省','河南':'河南省',
            '湖北':'湖北省','湖南':'湖南省','广东':'广东省','海南':'海南省',
            '四川':'四川省','贵州':'贵州省','云南':'云南省','陕西':'陕西省',
            '甘肃':'甘肃省','青海':'青海省','台湾':'台湾省',
            '内蒙古':'内蒙古自治区','广西':'广西壮族自治区','西藏':'西藏自治区',
            '宁夏':'宁夏回族自治区','新疆':'新疆维吾尔自治区',
            '香港':'香港特别行政区','澳门':'澳门特别行政区'
        };
        if (m2[s]) return m2[s];
        return s;
    }

    function normalizeCity(s) {
        if (!s) return '';
        return String(s).trim();
    }

    function adcodeToProvince(gb) {
        if (!gb) return '';
        var s = String(gb).trim();
        if (s.indexOf('156') === 0 && s.length >= 8) {
            s = s.substring(3);
        }
        var prefix = s.substring(0, 2);
        var m2 = {
            '11':'北京市','12':'天津市','13':'河北省','14':'山西省','15':'内蒙古自治区',
            '21':'辽宁省','22':'吉林省','23':'黑龙江省',
            '31':'上海市','32':'江苏省','33':'浙江省','34':'安徽省','35':'福建省',
            '36':'江西省','37':'山东省',
            '41':'河南省','42':'湖北省','43':'湖南省','44':'广东省','45':'广西壮族自治区','46':'海南省',
            '50':'重庆市','51':'四川省','52':'贵州省','53':'云南省','54':'西藏自治区',
            '61':'陕西省','62':'甘肃省','63':'青海省','64':'宁夏回族自治区','65':'新疆维吾尔自治区',
            '71':'台湾省','81':'香港特别行政区','82':'澳门特别行政区'
        };
        return m2[prefix] || '';
    }

    allApprovedGroups.forEach(function(g) {
        var prov = normalizeProvince(g.province || '');
        var city = normalizeCity(g.city || '');
        var lvl = parseInt(g.activity_level, 10) || 5;

        if (prov) {
            if (provinceLevelMap[prov] === undefined || lvl < provinceLevelMap[prov]) {
                provinceLevelMap[prov] = lvl;
            }
            (provinceGroupMap[prov] = provinceGroupMap[prov] || []).push(g);
        }
        if (prov && city) {
            var k = prov + '|' + city;
            if (cityLevelMap[k] === undefined || lvl < cityLevelMap[k]) {
                cityLevelMap[k] = lvl;
            }
            (cityGroupMap[k] = cityGroupMap[k] || []).push(g);
        }
    });

    function getProvinceColor(prov) {
        var lvl = provinceLevelMap[prov];
        if (lvl === undefined) return { color: '#95a5a6' };
        return { color: REGION_COLORS[lvl] || '#27ae60' };
    }

    var EMPTY_CITY_COLOR = '#a9b4ba';
    var EMPTY_CITY_FILL = '#c8d0d4';
    var MUNICIPALITIES = ['北京市', '天津市', '上海市', '重庆市'];

    function isMunicipality(prov) {
        return MUNICIPALITIES.indexOf(prov) !== -1;
    }

    function isCityEmpty(prov, city) {
        if (isMunicipality(prov)) return provinceLevelMap[prov] === undefined;
        return cityLevelMap[prov + '|' + city] === undefined;
    }

    function getCityColor(prov, city) {
        if (isMunicipality(prov)) return getProvinceColor(prov);
        var lvl = cityLevelMap[prov + '|' + city];
        if (lvl === undefined) return { color: EMPTY_CITY_COLOR, empty: true };
        return { color: REGION_COLORS[lvl] || '#27ae60' };
    }

    function extractProvinceName(feature) {
        var p = feature.properties || {};
        if (p.name) return normalizeProvince(p.name);
        if (p.NAME) return normalizeProvince(p.NAME);
        if (p.NAME_1) return normalizeProvince(p.NAME_1);
        if (p['省']) return normalizeProvince(p['省']);
        if (p.province) return normalizeProvince(p.province);
        var code = p.gb || p.adcode || p.ADCODE || '';
        if (code) return adcodeToProvince(code);
        return '';
    }

    function extractCityName(feature) {
        var p = feature.properties || {};
        if (p.name) return normalizeCity(p.name);
        if (p.NAME_2) return normalizeCity(p.NAME_2);
        if (p['市']) return normalizeCity(p['市']);
        if (p.city) return normalizeCity(p.city);
        return '';
    }

    function extractCityProvince(feature) {
        var p = feature.properties || {};
        if (p.parent && p.parent.adcode) return adcodeToProvince(p.parent.adcode);
        if (p.NAME_1) return normalizeProvince(p.NAME_1);
        if (p['省']) return normalizeProvince(p['省']);
        if (p.province) return normalizeProvince(p.province);
        var code = p.gb || p.adcode || p.ADCODE || '';
        if (code) return adcodeToProvince(code);
        return '';
    }

    function buildRegionPopup(title, list, subtitle) {
        title = title || '未知地区';
        list = list || [];
        var html = '<div style="font-size:.85rem;line-height:1.7;max-width:280px">';
        html += '<div style="font-weight:800;color:#1a2e3b;margin-bottom:.4rem">' + escapeHtml(title) + '</div>';
        if (subtitle && subtitle !== title) {
            html += '<div style="font-size:.72rem;color:#7d98aa;margin-bottom:.5rem">' + escapeHtml(subtitle) + '</div>';
        }
        if (!list.length) {
            html += '<div style="color:#7d98aa">暂无群</div>';
            return html + '</div>';
        }
        list = list.slice().sort(function(a,b) { return a.activity_level - b.activity_level; });
        list.forEach(function(g) {
            var c = GROUP_COLORS[g.activity_level] || '#27ae60';
            var hasCoord = parseFloat(g.lat) != 0 || parseFloat(g.lng) != 0;
            html += '<div style="margin:.4rem 0;padding-bottom:.4rem;border-bottom:1px solid #eef2f7;' + (hasCoord ? 'cursor:pointer' : '') + '"'
                + (hasCoord ? ' data-region-group-id="' + g.id + '"' : '') + '>';
            html += '<div style="font-weight:700;display:flex;align-items:center;gap:.4rem">';
            html += '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' + c + '"></span>';
            html += escapeHtml(g.name) + '</div>';
            html += '<div style="color:#7d98aa;font-size:.78rem">QQ群：<span style="color:#66ccff;font-weight:700">' + escapeHtml(g.qq_group) + '</span></div>';
            html += '</div>';
        });
        html += '</div>';
        return html;
    }

    var vectorRenderer = L.canvas({ padding: 0.5, tolerance: 0.3 });

    var provinceBoundLayer = L.geoJSON(null, {
        renderer: vectorRenderer,
        smoothFactor: 0,
        style: function(feature) {
            var prov = extractProvinceName(feature);
            var c = getProvinceColor(prov);
            return {
                color: c.color, weight: 2, opacity: 0.85,
                fillColor: c.color, fillOpacity: 0.18, dashArray: '4 3'
            };
        },
        onEachFeature: function(feature, layer) {
            var prov = extractProvinceName(feature);
            var info = provinceGroupMap[prov] || [];
            layer.bindPopup(buildRegionPopup(prov, info));
            layer.on('mouseover', function() { layer.setStyle({ fillOpacity: 0.32, weight: 2.5 }); });
            layer.on('mouseout', function() { layer.setStyle({ fillOpacity: 0.18, weight: 2 }); });
        }
    });

    var cityBoundLayer = L.geoJSON(null, {
        renderer: vectorRenderer,
        smoothFactor: 0,
        style: function(feature) {
            var prov = extractCityProvince(feature);
            var city = extractCityName(feature);
            if (isCityEmpty(prov, city)) {
                return {
                    color: EMPTY_CITY_COLOR, weight: 1.2, opacity: 0.75,
                    fillColor: EMPTY_CITY_FILL, fillOpacity: 0.05, lineJoin: 'round'
                };
            }
            var c = getCityColor(prov, city);
            return {
                color: c.color, weight: 1.5, opacity: 0.85,
                fillColor: c.color, fillOpacity: 0.18, lineJoin: 'round'
            };
        },
        onEachFeature: function(feature, layer) {
            var prov = extractCityProvince(feature);
            var city = extractCityName(feature);
            var info;
            if (isMunicipality(prov)) {
                info = provinceGroupMap[prov] || [];
            } else {
                info = cityGroupMap[prov + '|' + city] || [];
            }
            var empty = isCityEmpty(prov, city);
            layer.bindPopup(buildRegionPopup(city || prov, info, prov));
            layer.on('mouseover', function() {
                layer.setStyle(empty ? { fillOpacity: 0.14, weight: 1.6 } : { fillOpacity: 0.32, weight: 2.5 });
            });
            layer.on('mouseout', function() {
                layer.setStyle(empty ? { fillOpacity: 0.05, weight: 1.2 } : { fillOpacity: 0.18, weight: 1.5 });
            });
        }
    });

    var provinceGeoData = null;
    var cityGeoData = null;

    function setLoadingProgress(percent, text) {
        var inner = document.getElementById('loadingBarInner');
        var label = document.getElementById('loadingLabel');
        if (inner) inner.style.width = percent + '%';
        if (label && text) label.textContent = text;
    }

    function hideLoadingBar() {
        var bar = document.getElementById('loadingBar');
        if (!bar) return;
        var inner = document.getElementById('loadingBarInner');
        if (inner) inner.style.width = '100%';

        bar.style.transition = 'opacity .5s ease';
        bar.style.opacity = '0';

        setTimeout(function() {
            if (bar && bar.parentNode) bar.parentNode.removeChild(bar);
        }, 700);
    }

    var boundMode = 'default';
    var geoFetches = { province: null, city: null };

    function fetchGeo(file) {
        return fetch(file + '?v=' + Date.now()).then(function(r) { return r.json(); });
    }

    function ensureGeo(which) {
        if (which === 'province') {
            if (provinceGeoData) return Promise.resolve();
            if (!geoFetches.province) {
                geoFetches.province = fetchGeo('china-provinces.geojson')
                    .then(function(d) { provinceGeoData = d; provinceBoundLayer.addData(d); return d; })
                    .catch(function(e) { console.error('省级 geojson 加载失败', e); geoFetches.province = null; return null; });
            }
            return geoFetches.province;
        }
        if (cityGeoData) return Promise.resolve();
        if (!geoFetches.city) {
            geoFetches.city = fetchGeo('china-cities.geojson')
                .then(function(d) { cityGeoData = d; cityBoundLayer.addData(d); return d; })
                .catch(function(e) { console.error('市级 geojson 加载失败', e); geoFetches.city = null; return null; });
        }
        return geoFetches.city;
    }

    setLoadingProgress(5, '加载边界...');

    Promise.all([ensureGeo('province'), ensureGeo('city')]).then(function() {
        setLoadingProgress(90, '渲染边界...');
        setTimeout(function() {
            try {
                updateRegionLayers(map.getZoom());
            } catch (e) {
                console.error('updateRegionLayers 出错:', e);
            } finally {
                hideLoadingBar();
            }
        }, 200);
    });

    function updateLayers() {
        var z = map.getZoom();

        if (!map.hasLayer(provinceGroups)) map.addLayer(provinceGroups);
        if (!map.hasLayer(cityGroups)) map.addLayer(cityGroups);

        var provinceRadius = z < 6 ? 8 : z < 8 ? 10 : 13;
        var cityRadius = z < 6 ? 4 : z < 8 ? 6 : z < 10 ? 8 : 9;

        provinceGroups.eachLayer(function(m) {
            if (m.setRadius) m.setRadius(provinceRadius);
        });
        cityGroups.eachLayer(function(m) {
            if (m.setRadius) m.setRadius(cityRadius);
        });

        updateRegionLayers(z);
    }

    function updateRegionLayers(z) {
        if (boundMode === 'province') {

            if (map.hasLayer(cityBoundLayer)) map.removeLayer(cityBoundLayer);
            if (provinceGeoData && !map.hasLayer(provinceBoundLayer)) provinceBoundLayer.addTo(map);
        } else if (boundMode === 'city') {

            if (map.hasLayer(provinceBoundLayer)) map.removeLayer(provinceBoundLayer);
            if (cityGeoData && !map.hasLayer(cityBoundLayer)) cityBoundLayer.addTo(map);
        } else if (z < 9) {
            if (map.hasLayer(cityBoundLayer)) map.removeLayer(cityBoundLayer);
            if (provinceGeoData && !map.hasLayer(provinceBoundLayer)) provinceBoundLayer.addTo(map);
        } else {
            if (map.hasLayer(provinceBoundLayer)) map.removeLayer(provinceBoundLayer);
            if (cityGeoData && !map.hasLayer(cityBoundLayer)) cityBoundLayer.addTo(map);
        }
        safeBringToFront(provinceGroups);
        safeBringToFront(cityGroups);
        safeBringToFront(eventLayer);
    }

    updateLayers();

    function applyBoundMode() {
        if (boundMode === 'province') {
            ensureGeo('province').then(function() {
                if (boundMode === 'province') updateRegionLayers(map.getZoom());
            });
        } else if (boundMode === 'city') {
            ensureGeo('city').then(function() {
                if (boundMode === 'city') updateRegionLayers(map.getZoom());
            });
        } else {
            updateRegionLayers(map.getZoom());
        }
    }

    (function() {
        var ctrl = document.getElementById('boundModeCtrl');
        if (!ctrl) return;
        var btns = ctrl.querySelectorAll('button[data-mode]');
        btns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var mode = btn.getAttribute('data-mode');
                if (mode === boundMode) return;
                boundMode = mode;
                btns.forEach(function(b) { b.classList.toggle('active', b === btn); });
                applyBoundMode();
            });
        });
    })();

    function findGroupData(id) {
        id = String(id);
        for (var i = 0; i < allSidebarGroups.length; i++) {
            var g = allSidebarGroups[i];
            if (String(g.id) === id) return g;
        }
        for (var j = 0; j < allApprovedGroups.length; j++) {
            var g2 = allApprovedGroups[j];
            if (String(g2.id) === id) return g2;
        }
        return null;
    }

    function findEventData(id) {
        id = String(id);
        var all = ongoingEvents.concat(upcomingEvents);
        for (var i = 0; i < all.length; i++) {
            if (String(all[i].id) === id) return all[i];
        }
        return null;
    }

    function focusGroup(id) {
        id = String(id);
        closeAll();

        var gd = findGroupData(id);
        if (!gd) {
            console.warn('focusGroup: 未找到群数据, id=' + id);
            return;
        }

        var lat = parseFloat(gd.lat);
        var lng = parseFloat(gd.lng);

        if (lat === 0 && lng === 0) {
            console.warn('focusGroup: 群坐标为 0, id=' + id + ' name=' + gd.name);
            return;
        }

        var currentZoom = map.getZoom();
        var targetZoom = Math.max(currentZoom, 10);
        if (targetZoom > 12) targetZoom = 12;

        console.log('[focusGroup] 飞向 id=' + id + ' lat=' + lat + ' lng=' + lng + ' zoom=' + targetZoom);

        try {
            map.flyTo([lat, lng], targetZoom, {
                duration: 1.6,
                easeLinearity: 0.25,
                noMoveStart: false
            });
        } catch (e) {
            console.error('flyTo 失败，回退 setView:', e);
            map.setView([lat, lng], targetZoom);
        }

        setTimeout(function() {
            try {
                var c = map.getCenter();
                var d = Math.abs(c.lat - lat) + Math.abs(c.lng - lng);
                if (d > 0.01 || map.getZoom() !== targetZoom) {
                    map.setView([lat, lng], targetZoom, { animate: false });
                }
            } catch (e) {}

            var m = groupMarkers[id];
            if (m && m.openPopup) {
                try { m.openPopup(); } catch (e) {}
            }
            highlightSidebarGroup(id);
        }, 1900);
    }

    function focusEvent(id) {
        id = String(id);
        closeAll();

        var ev = findEventData(id);
        if (!ev) {
            console.warn('focusEvent: 未找到活动数据, id=' + id);
            return;
        }

        var lat = parseFloat(ev.lat);
        var lng = parseFloat(ev.lng);

        if (lat === 0 && lng === 0) {
            console.warn('focusEvent: 活动坐标为 0, id=' + id);
            return;
        }

        var currentZoom = map.getZoom();
        var targetZoom = Math.max(currentZoom, 10);
        if (targetZoom > 12) targetZoom = 12;

        try {
            map.flyTo([lat, lng], targetZoom, {
                duration: 1.6,
                easeLinearity: 0.25,
                noMoveStart: false
            });
        } catch (e) {
            map.setView([lat, lng], targetZoom);
        }

        setTimeout(function() {
            try {
                var c = map.getCenter();
                var d = Math.abs(c.lat - lat) + Math.abs(c.lng - lng);
                if (d > 0.01 || map.getZoom() !== targetZoom) {
                    map.setView([lat, lng], targetZoom, { animate: false });
                }
            } catch (e) {}

            var m = eventMarkers[id];
            if (m && m.openPopup) {
                try { m.openPopup(); } catch (e) {}
            }
            highlightSidebarEvent(id);
        }, 1900);
    }

    function highlightSidebarGroup(id) {
        document.querySelectorAll('.sidebar-item').forEach(function(el) { el.classList.remove('active'); });
        var el = document.getElementById('sidebar-group-' + id);
        if (el) {
            el.classList.add('active');
            var parent = el.closest('.sidebar-content');
            if (parent) {
                var top = el.offsetTop - parent.clientHeight / 2 + el.clientHeight / 2;
                parent.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
            }
        }
    }

    function unhighlightSidebarGroup(id) {
        var el = document.getElementById('sidebar-group-' + id);
        if (el) el.classList.remove('active');
    }

    function highlightSidebarEvent(id) {
        document.querySelectorAll('.sidebar-item').forEach(function(el) { el.classList.remove('active'); });
        var el = document.getElementById('sidebar-event-' + id);
        if (el) {
            el.classList.add('active');
            var parent = el.closest('.sidebar-content');
            if (parent) {
                var top = el.offsetTop - parent.clientHeight / 2 + el.clientHeight / 2;
                parent.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
            }
        }
    }

    function unhighlightSidebarEvent(id) {
        var el = document.getElementById('sidebar-event-' + id);
        if (el) el.classList.remove('active');
    }

    function switchTab(tab, btn) {
        document.querySelectorAll('.sidebar-tabs button').forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');
        document.getElementById('tab-groups').style.display = tab === 'groups' ? 'block' : 'none';
        document.getElementById('tab-events').style.display = tab === 'events' ? 'block' : 'none';
    }

    function toggleHeaderMenu() {
        var m = document.getElementById('headerMenu');
        var b = document.getElementById('backdrop');
        if (!m) return;

        var willOpen = !m.classList.contains('open');
        m.classList.toggle('open', willOpen);

        if (b) {
            if (willOpen) {
                var s = document.getElementById('sidebar');
                if (s) s.classList.remove('open');

                var header = document.querySelector('.header');
                var headerH = header ? header.getBoundingClientRect().height : 54;
                b.style.top = headerH + 'px';
                b.classList.add('show');
            } else {
                b.classList.remove('show');
                b.style.top = '';
            }
        }
    }

    function closeHeaderMenu() {
        var m = document.getElementById('headerMenu');
        var b = document.getElementById('backdrop');
        if (m) m.classList.remove('open');
        if (b) {
            b.classList.remove('show');
            b.style.top = '';
        }
    }

    function toggleSidebar() {
        var s = document.getElementById('sidebar');
        var b = document.getElementById('backdrop');
        if (!s) return;

        var willOpen = !s.classList.contains('open');
        s.classList.toggle('open', willOpen);

        if (b) {
            if (willOpen) {
                var m = document.getElementById('headerMenu');
                if (m) m.classList.remove('open');

                if (window.innerWidth <= 720) {
                    b.style.top = '50vh';
                } else {
                    var rect = s.getBoundingClientRect();
                    b.style.top = rect.top + 'px';
                }
                b.classList.add('show');
            } else {
                b.classList.remove('show');
                b.style.top = '';
            }
        }
    }

    function closeSidebar() {
        var s = document.getElementById('sidebar');
        var b = document.getElementById('backdrop');
        if (s) s.classList.remove('open');
        if (b) {
            b.classList.remove('show');
            b.style.top = '';
        }
    }

    function closeAll() {
        var m = document.getElementById('headerMenu');
        var s = document.getElementById('sidebar');
        var b = document.getElementById('backdrop');
        if (m) m.classList.remove('open');
        if (s) s.classList.remove('open');
        if (b) {
            b.classList.remove('show');
            b.style.top = '';
        }
    }

    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, function(c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }

    var ctxMenu = null;

    function closeCtxMenu() {
        if (ctxMenu) {
            ctxMenu.remove();
            ctxMenu = null;
        }
    }

    function ctxButton(label, fn) {
        var b = document.createElement('button');
        b.type = 'button';
        b.textContent = label;
        b.addEventListener('click', function() {
            closeCtxMenu();
            fn();
        });
        return b;
    }

    function showItemProps(key) {
        var p = ITEM_PROPS[key];
        if (!p) return;
        var mask = document.createElement('div');
        mask.className = 'props-mask';
        var dlg = document.createElement('div');
        dlg.className = 'props-dialog';
        var html = '<h3>' + escapeHtml(p.title) + '</h3>';
        for (var i = 0; i < p.rows.length; i++) {
            html += '<div class="props-row"><div class="k">' + escapeHtml(p.rows[i][0]) +
                '</div><div class="v">' + escapeHtml(p.rows[i][1]) + '</div></div>';
        }
        html += '<div class="props-actions">' +
            (p.can_edit ? '<a class="btn btn-primary" href="' + escapeHtml(p.edit_url) + '">编辑</a>' : '') +
            '<button type="button" class="btn btn-secondary" data-props-close>关闭</button></div>';
        dlg.innerHTML = html;
        mask.appendChild(dlg);
        document.body.appendChild(mask);
        mask.addEventListener('click', function(e) {
            if (e.target === mask) mask.remove();
        });
        var closeBtn = dlg.querySelector('[data-props-close]');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() { mask.remove(); });
        }
    }

    function openCtxMenu(x, y, key) {
        closeCtxMenu();
        var p = ITEM_PROPS[key];
        if (!p) return;
        var m = document.createElement('div');
        m.className = 'ctx-menu';
        m.appendChild(ctxButton('属性', function() { showItemProps(key); }));
        m.appendChild(ctxButton('反馈', function() { window.open(FEEDBACK_URL, '_blank', 'noopener'); }));
        if (p.can_edit) {
            m.appendChild(ctxButton('编辑', function() { window.location.href = p.edit_url; }));
        }
        document.body.appendChild(m);
        var rect = m.getBoundingClientRect();
        var left = x;
        var top = y;
        if (left + rect.width > window.innerWidth - 8) left = window.innerWidth - rect.width - 8;
        if (top + rect.height > window.innerHeight - 8) top = window.innerHeight - rect.height - 8;
        m.style.left = Math.max(8, left) + 'px';
        m.style.top = Math.max(8, top) + 'px';
        ctxMenu = m;
    }

    document.addEventListener('click', function(e) {
        var more = e.target.closest('.item-more');
        if (more) {
            var r = more.getBoundingClientRect();
            openCtxMenu(r.left - 110, r.bottom + 4, more.getAttribute('data-ctx') + ':' + more.getAttribute('data-id'));
            return;
        }
        if (ctxMenu && !e.target.closest('.ctx-menu')) closeCtxMenu();
    }, true);

    document.addEventListener('contextmenu', function(e) {
        var item = e.target.closest('.sidebar-item');
        if (!item) return;
        var btn = item.querySelector('.item-more');
        if (!btn) return;
        e.preventDefault();
        openCtxMenu(e.clientX, e.clientY, btn.getAttribute('data-ctx') + ':' + btn.getAttribute('data-id'));
    });

    var pressTimer = null;

    function clearPress() {
        if (pressTimer) {
            clearTimeout(pressTimer);
            pressTimer = null;
        }
    }

    document.addEventListener('touchstart', function(e) {
        var item = e.target.closest('.sidebar-item');
        if (!item || e.target.closest('.item-more')) return;
        var btn = item.querySelector('.item-more');
        if (!btn || !e.touches.length) return;
        var touch = e.touches[0];
        var key = btn.getAttribute('data-ctx') + ':' + btn.getAttribute('data-id');
        clearPress();
        pressTimer = setTimeout(function() {
            pressTimer = null;
            openCtxMenu(touch.clientX, touch.clientY, key);
        }, 480);
    }, { passive: true });

    document.addEventListener('touchend', clearPress);
    document.addEventListener('touchmove', clearPress);
    document.addEventListener('touchcancel', clearPress);
    window.addEventListener('resize', closeCtxMenu);

    document.addEventListener('click', function(e) {
        var target = e.target;

        if (target.closest('.item-more')) {
            return;
        }

        var headerToggle = target.closest('#headerToggle');
        if (headerToggle) {
            toggleHeaderMenu();
            return;
        }

        var sidebarToggle = target.closest('#sidebarToggle');
        if (sidebarToggle) {
            toggleSidebar();
            return;
        }

        var tabBtn = target.closest('.sidebar-tabs button[data-tab]');
        if (tabBtn) {
            switchTab(tabBtn.dataset.tab, tabBtn);
            return;
        }

        var gItem = target.closest('[data-group-id]');
        if (gItem) {
            focusGroup(gItem.dataset.groupId);
            return;
        }

        var evItem = target.closest('[data-event-id]');
        if (evItem) {
            focusEvent(evItem.dataset.eventId);
            return;
        }

        var regionItem = target.closest('[data-region-group-id]');
        if (regionItem) {
            focusGroup(regionItem.dataset.regionGroupId);
            return;
        }

        var headerMenu = document.getElementById('headerMenu');
        if (headerMenu && headerMenu.classList.contains('open')) {
            if (!headerMenu.contains(target)) {
                closeHeaderMenu();
            }
        }

        var sidebar = document.getElementById('sidebar');
        if (sidebar && sidebar.classList.contains('open')) {
            if (!sidebar.contains(target)) {
                closeSidebar();
            }
        }
    });

    window.MapAPI = {
        getGroups: function() { return JSON.stringify(allApprovedGroups); },
        getEvents: function() { return JSON.stringify(ongoingEvents.concat(upcomingEvents)); },
        getProvinces: function() { return JSON.stringify(Object.keys(provinceGroupMap)); },
        getCities: function() { return JSON.stringify(Object.keys(cityGroupMap)); },
        focusGroupById: function(id) { focusGroup(id); },
        focusEventById: function(id) { focusEvent(id); },
        flyTo: function(lat, lng, zoom) { map.flyTo([lat, lng], zoom || 10, { duration: 1.6 }); },
        getCenter: function() {
            var c = map.getCenter();
            return JSON.stringify({ lat: c.lat, lng: c.lng, zoom: map.getZoom() });
        }
    };
})();
</script>
</body>
</html>
