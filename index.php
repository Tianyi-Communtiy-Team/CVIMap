<?php
require_once __DIR__ . '/common.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

list($localGroups, $otherGroups) = cviSplitGroups();
$allGroups = array_values(cviGroups());
$grouped = cviGroupedEvents();
$ongoingEvents = $grouped['ongoing'];
$upcomingEvents = $grouped['upcoming'];
$endedEvents = $grouped['ended'];

$siteName = cfg('site_name', '中V资讯地图');
$repoUrl = cfg('repo_url', '');
$mapNumber = trim((string)cfg('map_number', ''));
$surveyLicense = trim((string)cfg('survey_license', ''));
$boundaryEnabled = (bool)cfg('boundary_enabled', true);
$keys = cviTiandituKeys();

$jsGroups = json_encode($localGroups, JSON_UNESCAPED_UNICODE);
$jsOther = json_encode($otherGroups, JSON_UNESCAPED_UNICODE);
$jsAll = json_encode($allGroups, JSON_UNESCAPED_UNICODE);
$jsOngoing = json_encode($ongoingEvents, JSON_UNESCAPED_UNICODE);
$jsUpcoming = json_encode($upcomingEvents, JSON_UNESCAPED_UNICODE);
$jsEnded = json_encode($endedEvents, JSON_UNESCAPED_UNICODE);
$jsKeys = json_encode($keys, JSON_UNESCAPED_UNICODE);
$jsCenter = json_encode(cfg('map_center', [35.0, 105.0]));
$jsZoom = (int)cfg('map_zoom', 5);
$jsMinZoom = (int)cfg('map_min_zoom', 4);
$jsMaxZoom = (int)cfg('map_max_zoom', 12);
$jsCityZoom = (int)cfg('city_zoom', 9);
$jsBoundary = json_encode([
    'enabled' => $boundaryEnabled,
    'province' => $boundaryEnabled && cviHasBoundary('province'),
    'city' => $boundaryEnabled && cviHasBoundary('city'),
]);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#66ccff">
<meta name="format-detection" content="telephone=no,email=no,address=no">
<title><?= e($siteName) ?></title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
html, body { height: 100%; overflow: hidden; -webkit-tap-highlight-color: transparent; }
body { background: #eaf4fb; color: #1a2e3b; }

#map { position: absolute; top: 58px; left: 0; right: 0; bottom: 0; z-index: 1; background: #eaf4fb; }

.loading-bar { position: fixed; top: 58px; left: 0; right: 0; height: 3px; background: rgba(255,255,255,.6); z-index: 1400; }
.loading-bar .bar { height: 100%; width: 0; background: linear-gradient(90deg,#66ccff,#4aa3e8); transition: width .3s ease; }
.loading-bar .label { position: absolute; top: 8px; left: 50%; transform: translateX(-50%); font-size: .72rem; color: #48677a; background: rgba(255,255,255,.9); padding: .2rem .7rem; border-radius: 1rem; box-shadow: 0 4px 14px rgba(31,83,111,.12); }

.header {
    position: absolute; top: 0; left: 0; right: 0; height: 58px; z-index: 1200;
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 0 1.4rem; background: rgba(255,255,255,.92); backdrop-filter: blur(14px);
    box-shadow: 0 2px 14px rgba(31,83,111,.08);
}
.header .logo { display: flex; align-items: center; gap: .5rem; font-weight: 800; font-size: 1.05rem; color: #1a2e3b; text-decoration: none; }
.header .logo .version-badge { font-size: .62rem; font-weight: 700; color: #4aa3e8; background: rgba(102,204,255,.14); padding: .12rem .45rem; border-radius: .6rem; }
.header .nav { display: flex; align-items: center; gap: .2rem; }
.header .nav a { padding: .5rem .8rem; border-radius: .7rem; font-size: .86rem; color: #547083; text-decoration: none; }
.header .nav a:hover { background: rgba(102,204,255,.1); color: #173447; }
.header .nav a.active { color: #66ccff; font-weight: 700; }
.header-toggle { display: none; border: 1px solid rgba(126,177,207,.24); background: rgba(255,255,255,.75); color: #3a5b6b; width: 38px; height: 34px; border-radius: .7rem; font-size: 1.1rem; cursor: pointer; }

.header-menu {
    display: none; position: absolute; top: 58px; right: 1rem; z-index: 1300;
    min-width: 210px; max-height: 0; overflow: hidden;
    background: rgba(255,255,255,.97); backdrop-filter: blur(18px);
    border: 1px solid rgba(126,177,207,.18); border-radius: 1rem;
    box-shadow: 0 16px 32px rgba(31,83,111,.14); transition: max-height .3s ease;
}
.header-menu.open { max-height: 60vh; overflow-y: auto; }
.header-menu .menu-item { display: flex; align-items: center; gap: .6rem; padding: .9rem 1.4rem; color: #3a5b6b; text-decoration: none; font-size: .92rem; border-bottom: 1px solid rgba(102,204,255,.1); }
.header-menu .menu-item:last-child { border-bottom: none; }
.header-menu .menu-item:hover { background: rgba(102,204,255,.08); color: #1a2e3b; }
.header-menu .menu-gh-icon { width: 16px; height: 16px; flex: none; }

.bound-mode-ctrl {
    position: absolute; top: 70px; left: 1rem; z-index: 1100;
    display: flex; gap: .3rem; padding: .3rem; border-radius: .9rem;
    background: rgba(255,255,255,.92); box-shadow: 0 6px 20px rgba(31,83,111,.12);
}
.bound-mode-ctrl button { border: none; background: transparent; color: #547083; font-size: .74rem; padding: .35rem .7rem; border-radius: .6rem; cursor: pointer; }
.bound-mode-ctrl button.active { background: linear-gradient(135deg,#66ccff,#4aa3e8); color: #fff; font-weight: 700; }

.legend {
    position: absolute; left: 1rem; bottom: 1rem; z-index: 1000;
    background: rgba(255,255,255,.94); backdrop-filter: blur(10px);
    border-radius: .9rem; padding: .7rem .85rem; box-shadow: 0 6px 20px rgba(31,83,111,.1);
    font-size: .72rem; color: #3a5b6b;
}
.legend-title { font-weight: 800; margin-bottom: .4rem; font-size: .75rem; }
.legend-item { display: flex; align-items: center; gap: .4rem; margin-top: .22rem; }
.legend-swatch { width: 12px; height: 12px; border-radius: 50%; border: 2px solid; }

.attribution {
    position: absolute; right: 1rem; bottom: 1rem; z-index: 1000;
    display: flex; align-items: center; gap: .5rem;
    background: rgba(255,255,255,.94); border-radius: .8rem; padding: .4rem .7rem;
    box-shadow: 0 6px 20px rgba(31,83,111,.1);
}
.attribution img { height: 32px; width: auto; display: block; }
.attribution .txt { display: flex; flex-direction: column; gap: .1rem; }
.attribution .txt .row1 { font-weight: 600; color: #1a2e3b; font-size: .72rem; }
.attribution .txt .row2 { font-size: .66rem; color: #7d98aa; }

.sidebar-toggle {
    position: absolute; right: 1rem; bottom: 5.6rem; z-index: 1150;
    width: 52px; height: 52px; border-radius: 50%; border: none; cursor: pointer;
    background: linear-gradient(135deg,#66ccff,#4aa3e8); color: #fff; font-size: 1.3rem;
    box-shadow: 0 10px 24px -8px rgba(79,159,210,.85);
}

.backdrop { position: fixed; left: 0; right: 0; bottom: 0; top: 58px; background: rgba(20,40,55,.32); opacity: 0; pointer-events: none; transition: opacity .25s ease; z-index: 1050; }
.backdrop.show { opacity: 1; pointer-events: auto; }

.sidebar {
    position: absolute; top: 70px; right: 14px; z-index: 1060;
    background: rgba(255,255,255,.97); backdrop-filter: blur(14px);
    border-radius: 1rem; width: 320px; box-shadow: 0 8px 32px rgba(0,0,0,.12);
    max-height: calc(100vh - 90px); display: flex; flex-direction: column; overflow: hidden;
    transform: translateX(calc(100% + 20px)); transition: transform .3s ease;
}
.sidebar.open { transform: translateX(0); }
.sidebar-tabs { display: flex; border-bottom: 1px solid rgba(102,204,255,.16); flex: none; }
.sidebar-tabs button { flex: 1; border: none; background: transparent; padding: .85rem 0; font-size: .88rem; color: #7d98aa; cursor: pointer; }
.sidebar-tabs button.active { color: #66ccff; font-weight: 800; box-shadow: inset 0 -2px 0 #66ccff; }
.sidebar-search { flex: none; padding: .6rem .8rem; border-bottom: 1px solid rgba(102,204,255,.12); }
.sidebar-search input { width: 100%; border: 1px solid rgba(126,177,207,.24); border-radius: .7rem; padding: .5rem .7rem; font-size: .82rem; outline: none; color: #1a2e3b; }
.sidebar-search input:focus { border-color: #66ccff; }
.sidebar-content { overflow-y: auto; padding: .5rem; }
.sidebar-section-title { font-size: .72rem; font-weight: 800; color: #9db4c2; padding: .6rem .4rem .3rem; }
.sidebar-item { padding: .7rem .8rem; border-radius: .8rem; cursor: pointer; display: flex; align-items: center; gap: .6rem; }
.sidebar-item:hover { background: rgba(102,204,255,.08); }
.sidebar-item.active { background: rgba(102,204,255,.16); }
.sidebar-item .dot { width: 10px; height: 10px; border-radius: 50%; flex: none; border: 2px solid #fff; box-shadow: 0 2px 6px rgba(31,83,111,.2); }
.sidebar-item .body { min-width: 0; flex: 1; }
.sidebar-item .name { font-size: .88rem; font-weight: 700; color: #1a2e3b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sidebar-item .meta { font-size: .72rem; color: #7d98aa; margin-top: .1rem; }
.sidebar-item .badge { font-size: .66rem; padding: .1rem .4rem; border-radius: .5rem; background: rgba(102,204,255,.14); color: #4aa3e8; font-weight: 700; flex: none; }
.sidebar-empty { padding: 1.6rem; text-align: center; color: #9db4c2; font-size: .8rem; }

.popup-content { font-size: .8rem; line-height: 1.7; color: #3a5b6b; max-width: 260px; }
.popup-content .name { font-weight: 800; color: #1a2e3b; font-size: .92rem; margin-bottom: .2rem; }
.popup-content .qq { color: #66ccff; font-weight: 700; }
.popup-content .meta { color: #7d98aa; font-size: .76rem; }
.popup-content .event-type { display: inline-block; font-size: .66rem; color: #fff; padding: .1rem .45rem; border-radius: .5rem; margin: .2rem 0; }

.leaflet-container { background: #eaf4fb; }
.leaflet-control-zoom { border-radius: 12px !important; overflow: hidden; }
.leaflet-popup-content-wrapper { border-radius: .9rem; }

@media (max-width: 720px) {
    .header { height: 54px; padding: 0 1rem; }
    .header .nav { display: none; }
    .header-toggle { display: block; }
    .header-menu { display: block; top: 54px; }
    #map { top: 54px; }
    .loading-bar { top: 54px; }
    .legend { display: none; }
    .attribution { display: none; }
    .sidebar { top: auto; bottom: 0; left: 0; right: 0; width: 100%; max-height: 56vh; border-radius: 1.4rem 1.4rem 0 0; transform: translateY(100%); }
    .sidebar.open { transform: translateY(0); }
    .sidebar-toggle { right: 1rem; bottom: 1rem; }
    .bound-mode-ctrl { top: 62px; left: .6rem; }
}
</style>
</head>
<body>

<div id="map"></div>

<div class="loading-bar" id="loadingBar">
    <div class="bar" id="loadingBarInner"></div>
    <div class="label" id="loadingLabel">加载中...</div>
</div>

<div class="bound-mode-ctrl" id="boundModeCtrl" role="group" aria-label="边界查看模式">
    <button type="button" data-mode="default" class="active">默认边界</button>
    <button type="button" data-mode="province">只看省级</button>
    <button type="button" data-mode="city">只看市级</button>
</div>

<header class="header" id="siteHeader">
    <a href="index.php" class="logo">
        <?= e($siteName) ?>
        <span class="version-badge">v<?= e(CVI_VERSION) ?></span>
    </a>
    <nav class="nav">
        <a href="#" data-scroll="groups">粉丝群</a>
        <a href="#" data-scroll="events">活动</a>
    </nav>
    <button class="header-toggle" id="headerToggle" aria-label="菜单">☰</button>
</header>

<div class="header-menu" id="headerMenu">
    <a href="index.php" class="menu-item">地图首页</a>
    <a href="api.php?action=groups" class="menu-item">群列表接口</a>
    <a href="api.php?action=events" class="menu-item">活动接口</a>
    <?php if ($repoUrl !== ''): ?>
    <a href="<?= e($repoUrl) ?>/issues/new" target="_blank" rel="noopener" class="menu-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="menu-gh-icon"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
        <span>反馈</span>
    </a>
    <a href="<?= e($repoUrl) ?>" target="_blank" rel="noopener" class="menu-item menu-item-gh">
        <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="menu-gh-icon"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27s1.36.09 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8z"/></svg>
        <span>GitHub 开源仓库</span>
    </a>
    <?php endif; ?>
</div>

<button class="sidebar-toggle" id="sidebarToggle" aria-label="群列表">☰</button>
<div class="backdrop" id="backdrop"></div>

<div class="legend">
    <div class="legend-title">群活跃度</div>
    <div class="legend-item"><span class="legend-swatch" style="background:rgba(231,76,60,.3);border-color:#e74c3c"></span> ★ 最活跃</div>
    <div class="legend-item"><span class="legend-swatch" style="background:rgba(230,126,34,.3);border-color:#e67e22"></span> ① 很活跃</div>
    <div class="legend-item"><span class="legend-swatch" style="background:rgba(241,196,15,.3);border-color:#f1c40f"></span> ② 较活跃</div>
    <div class="legend-item"><span class="legend-swatch" style="background:rgba(46,204,113,.3);border-color:#2ecc71"></span> ③ 一般</div>
    <div class="legend-item"><span class="legend-swatch" style="background:rgba(39,174,96,.3);border-color:#27ae60"></span> ④ 相对安静</div>
    <div class="legend-item"><span class="legend-swatch" style="background:rgba(200,208,212,.12);border-color:#a9b4ba"></span> 无群城市</div>
    <div class="legend-title" style="margin-top:.6rem">活动类型</div>
    <div class="legend-item"><span class="legend-swatch" style="background:rgba(231,76,60,.3);border-color:#e74c3c"></span> 线下活动</div>
    <div class="legend-item"><span class="legend-swatch" style="background:rgba(46,204,113,.3);border-color:#2ecc71"></span> 线上活动</div>
    <div class="legend-item"><span class="legend-swatch" style="background:rgba(102,204,255,.3);border-color:#66ccff"></span> 生日会</div>
    <div class="legend-item"><span class="legend-swatch" style="background:rgba(149,165,166,.3);border-color:#95a5a6"></span> 其他</div>
</div>

<div class="attribution">
    <img src="tianditu-logo.svg" alt="天地图">
    <div class="txt">
        <div class="row1"><?= $mapNumber !== '' ? e($mapNumber) : '审图号未配置' ?></div>
        <div class="row2"><?= $surveyLicense !== '' ? e($surveyLicense) : '底图来自天地图' ?></div>
    </div>
</div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-tabs">
        <button class="active" data-tab="groups">粉丝群</button>
        <button data-tab="events">活动</button>
    </div>
    <div class="sidebar-search">
        <input type="search" id="sidebarSearch" placeholder="搜索群名 / 地区 / 活动">
    </div>
    <div class="sidebar-content">
        <div id="tab-groups" style="display:block">
            <?php if (empty($localGroups) && empty($otherGroups)): ?>
                <div class="sidebar-empty">暂无粉丝群</div>
            <?php else: ?>
                <?php if (!empty($localGroups)): ?>
                    <div class="sidebar-section-title">地区性群</div>
                    <?php foreach ($localGroups as $g): ?>
                        <?php $color = ['#e74c3c','#e67e22','#f1c40f','#2ecc71','#27ae60'][$g['activity_level'] - 1] ?? '#27ae60'; ?>
                        <div class="sidebar-item" id="sidebar-group-<?= (int)$g['id'] ?>" data-group-id="<?= (int)$g['id'] ?>" data-text="<?= e(strtolower($g['name'] . ' ' . $g['province'] . ' ' . $g['city'] . ' ' . $g['qq_group'])) ?>">
                            <span class="dot" style="background:<?= e($color) ?>"></span>
                            <span class="body">
                                <span class="name"><?= e($g['name']) ?></span>
                                <span class="meta"><?= e(trim($g['province'] . ' ' . $g['city'])) ?></span>
                            </span>
                            <span class="badge"><?= e(cviActivityMark((int)$g['activity_level'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($otherGroups)): ?>
                    <div class="sidebar-section-title">其他群</div>
                    <?php foreach ($otherGroups as $g): ?>
                        <?php $color = ['#e74c3c','#e67e22','#f1c40f','#2ecc71','#27ae60'][$g['activity_level'] - 1] ?? '#27ae60'; ?>
                        <div class="sidebar-item" id="sidebar-group-<?= (int)$g['id'] ?>" data-group-id="<?= (int)$g['id'] ?>" data-text="<?= e(strtolower($g['name'] . ' ' . $g['province'] . ' ' . $g['city'] . ' ' . $g['qq_group'])) ?>">
                            <span class="dot" style="background:<?= e($color) ?>"></span>
                            <span class="body">
                                <span class="name"><?= e($g['name']) ?></span>
                                <span class="meta"><?= e($g['description'] !== '' ? $g['description'] : '综合群') ?></span>
                            </span>
                            <span class="badge"><?= e(cviActivityMark((int)$g['activity_level'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div id="tab-events" style="display:none">
            <?php $allEventSections = ['进行中' => $ongoingEvents, '计划中' => $upcomingEvents, '已结束' => $endedEvents]; ?>
            <?php $hasEvent = false; foreach ($allEventSections as $label => $rows) { if (!empty($rows)) { $hasEvent = true; break; } } ?>
            <?php if (!$hasEvent): ?>
                <div class="sidebar-empty">暂无活动</div>
            <?php else: ?>
                <?php foreach ($allEventSections as $label => $rows): ?>
                    <?php if (empty($rows)) continue; ?>
                    <div class="sidebar-section-title"><?= e($label) ?></div>
                    <?php foreach ($rows as $ev): ?>
                        <?php $ec = ['offline' => '#e74c3c', 'online' => '#2ecc71', 'birthday' => '#66ccff', 'other' => '#95a5a6'][$ev['type']] ?? '#95a5a6'; ?>
                        <div class="sidebar-item" id="sidebar-event-<?= (int)$ev['id'] ?>" data-event-id="<?= (int)$ev['id'] ?>" data-text="<?= e(strtolower($ev['title'] . ' ' . $ev['province'] . ' ' . $ev['city'] . ' ' . $ev['address'])) ?>">
                            <span class="dot" style="background:<?= e($ec) ?>"></span>
                            <span class="body">
                                <span class="name"><?= e($ev['title']) ?></span>
                                <span class="meta"><?= e($ev['start_date']) ?> ~ <?= e($ev['end_date']) ?>　<?= e(trim($ev['province'] . ' ' . $ev['city'])) ?></span>
                            </span>
                            <span class="badge"><?= e(cviEventTypeLabel($ev['type'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</aside>

<script>
(function () {
    'use strict';

    var TIANDITU_KEYS = <?= $jsKeys ?>;
    var BOUNDARY = <?= $jsBoundary ?>;
    var CITY_ZOOM = <?= $jsCityZoom ?>;

    var groups = <?= $jsGroups ?>;
    var otherGroups = <?= $jsOther ?>;
    var allGroups = <?= $jsAll ?>;
    var ongoingEvents = <?= $jsOngoing ?>;
    var upcomingEvents = <?= $jsUpcoming ?>;
    var endedEvents = <?= $jsEnded ?>;

    var GROUP_COLORS = {1:'#e74c3c',2:'#e67e22',3:'#f1c40f',4:'#2ecc71',5:'#27ae60'};
    var EVENT_COLORS = {offline:'#e74c3c',online:'#2ecc71',birthday:'#66ccff',other:'#95a5a6'};
    var EVENT_LABELS = {offline:'线下活动',online:'线上活动',birthday:'生日会',other:'其他'};

    var keyIndex = 0;
    var failCount = 0;
    var switching = false;
    var baseLayer = null;
    var labelLayer = null;

    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, function (c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }

    function setLoading(percent, text) {
        var inner = document.getElementById('loadingBarInner');
        var label = document.getElementById('loadingLabel');
        if (inner) inner.style.width = percent + '%';
        if (label && text) label.textContent = text;
    }

    function hideLoading() {
        var bar = document.getElementById('loadingBar');
        if (!bar) return;
        var inner = document.getElementById('loadingBarInner');
        if (inner) inner.style.width = '100%';
        bar.style.transition = 'opacity .5s ease';
        bar.style.opacity = '0';
        setTimeout(function () { if (bar.parentNode) bar.parentNode.removeChild(bar); }, 700);
    }

    function buildUrl(pathName, layerName, tk, sk) {
        var url = 'https://t{s}.tianditu.gov.cn/' + pathName + '/wmts'
            + '?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0'
            + '&LAYER=' + layerName
            + '&STYLE=default&TILEMATRIXSET=w&FORMAT=tiles'
            + '&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}'
            + '&tk=' + encodeURIComponent(tk);
        if (sk) url += '&sk=' + encodeURIComponent(sk);
        return url;
    }

    var map = L.map('map', {
        center: <?= $jsCenter ?>,
        zoom: <?= $jsZoom ?>,
        minZoom: <?= $jsMinZoom ?>,
        maxZoom: <?= $jsMaxZoom ?>,
        zoomControl: window.innerWidth > 720,
        attributionControl: false
    });

    var provinceGroups = L.featureGroup();
    var cityGroups = L.featureGroup();
    var eventLayer = L.featureGroup().addTo(map);
    var groupMarkers = {};
    var eventMarkers = {};

    function bringMarkersFront() {
        [provinceGroups, cityGroups, eventLayer].forEach(function (layer) {
            if (layer && typeof layer.bringToFront === 'function') {
                try { layer.bringToFront(); } catch (e) {}
            }
        });
    }

    function onTileError() {
        if (switching || TIANDITU_KEYS.length < 2) return;
        failCount++;
        if (failCount >= 3) switchKey();
    }

    function switchKey() {
        if (switching) return;
        switching = true;
        failCount = 0;
        keyIndex++;
        var key = TIANDITU_KEYS[keyIndex % TIANDITU_KEYS.length];
        if (baseLayer) map.removeLayer(baseLayer);
        if (labelLayer) map.removeLayer(labelLayer);
        mountTiles(key.tk, key.sk);
        setTimeout(function () { switching = false; }, 500);
    }

    function mountTiles(tk, sk) {
        var blank = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
        baseLayer = L.tileLayer(buildUrl('vec_w', 'vec', tk, sk), {
            subdomains: ['0','1','2','3','4','5','6','7'],
            maxZoom: 18, errorTileUrl: blank, crossOrigin: true
        });
        labelLayer = L.tileLayer(buildUrl('cva_w', 'cva', tk, sk), {
            subdomains: ['0','1','2','3','4','5','6','7'],
            maxZoom: 18, errorTileUrl: blank, crossOrigin: true
        });
        baseLayer.on('tileerror', onTileError);
        labelLayer.on('tileerror', onTileError);
        baseLayer.addTo(map);
        labelLayer.addTo(map);
        if (baseLayer.bringToBack) baseLayer.bringToBack();
        bringMarkersFront();
    }

    if (TIANDITU_KEYS.length) {
        mountTiles(TIANDITU_KEYS[0].tk, TIANDITU_KEYS[0].sk);
    } else {
        setLoading(100, '未配置天地图密钥');
        setTimeout(hideLoading, 1200);
    }

    function groupPopup(g) {
        var html = '<div class="popup-content"><div class="name">' + escapeHtml(g.name) + '</div>';
        if (g.qq_group) html += '<div>QQ群：<span class="qq">' + escapeHtml(g.qq_group) + '</span></div>';
        if (g.backup_qq_group) html += '<div>备用群：<span class="qq">' + escapeHtml(g.backup_qq_group) + '</span></div>';
        var place = [g.province, g.city].filter(Boolean).join(' ');
        if (place) html += '<div class="meta">' + escapeHtml(place) + '</div>';
        if (g.member_count) html += '<div class="meta">成员约 ' + escapeHtml(g.member_count) + ' 人</div>';
        if (g.description) html += '<div class="meta">' + escapeHtml(g.description) + '</div>';
        if (g.public_note) html += '<div class="meta" style="color:#a45c1f">' + escapeHtml(g.public_note) + '</div>';
        return html + '</div>';
    }

    function eventPopup(ev) {
        var color = EVENT_COLORS[ev.type] || '#95a5a6';
        var html = '<div class="popup-content"><div class="name">' + escapeHtml(ev.title) + '</div>';
        html += '<span class="event-type" style="background:' + color + '">' + (EVENT_LABELS[ev.type] || '其他') + '</span>';
        html += '<div class="meta">' + escapeHtml(ev.start_date) + ' ~ ' + escapeHtml(ev.end_date) + '</div>';
        var place = [ev.province, ev.city].filter(Boolean).join(' ');
        if (place) html += '<div class="meta">' + escapeHtml(place) + '</div>';
        if (ev.address) html += '<div class="meta">' + escapeHtml(ev.address) + '</div>';
        if (ev.qq_group) html += '<div class="meta">QQ群：<span class="qq">' + escapeHtml(ev.qq_group) + '</span></div>';
        if (ev.description) html += '<div class="meta">' + escapeHtml(ev.description) + '</div>';
        return html + '</div>';
    }

    groups.concat(otherGroups).forEach(function (g) {
        var lat = parseFloat(g.lat);
        var lng = parseFloat(g.lng);
        if (!lat && !lng) return;
        var m = L.circleMarker([lat, lng], {
            radius: g.level === 'province' ? 13 : 9,
            fillColor: GROUP_COLORS[g.activity_level] || '#27ae60',
            color: '#fff', weight: 2, opacity: 1, fillOpacity: 0.9
        });
        m.bindPopup(groupPopup(g));
        m.on('popupopen', function () { highlight('sidebar-group-' + g.id); });
        m.on('popupclose', function () { unhighlight('sidebar-group-' + g.id); });
        m.addTo(g.level === 'province' ? provinceGroups : cityGroups);
        groupMarkers[String(g.id)] = m;
    });

    ongoingEvents.concat(upcomingEvents).forEach(function (ev) {
        var lat = parseFloat(ev.lat);
        var lng = parseFloat(ev.lng);
        if (!lat && !lng) return;
        var m = L.circleMarker([lat, lng], {
            radius: 11,
            fillColor: EVENT_COLORS[ev.type] || '#95a5a6',
            color: '#fff', weight: 2, opacity: 1, fillOpacity: 0.9
        });
        m.bindPopup(eventPopup(ev));
        m.on('popupopen', function () { highlight('sidebar-event-' + ev.id); });
        m.on('popupclose', function () { unhighlight('sidebar-event-' + ev.id); });
        m.addTo(eventLayer);
        eventMarkers[String(ev.id)] = m;
    });

    map.addLayer(provinceGroups);
    map.addLayer(cityGroups);

    function highlight(id) {
        document.querySelectorAll('.sidebar-item').forEach(function (el) { el.classList.remove('active'); });
        var el = document.getElementById(id);
        if (!el) return;
        el.classList.add('active');
        var parent = el.closest('.sidebar-content');
        if (parent) parent.scrollTo({ top: Math.max(0, el.offsetTop - parent.clientHeight / 2), behavior: 'smooth' });
    }

    function unhighlight(id) {
        var el = document.getElementById(id);
        if (el) el.classList.remove('active');
    }

    function findGroup(id) {
        id = String(id);
        for (var i = 0; i < allGroups.length; i++) if (String(allGroups[i].id) === id) return allGroups[i];
        return null;
    }

    function findEvent(id) {
        id = String(id);
        var all = ongoingEvents.concat(upcomingEvents, endedEvents);
        for (var i = 0; i < all.length; i++) if (String(all[i].id) === id) return all[i];
        return null;
    }

    function flyTo(lat, lng, then) {
        var z = Math.min(Math.max(map.getZoom(), 10), <?= $jsMaxZoom ?>);
        try {
            map.flyTo([lat, lng], z, { duration: 1.4, easeLinearity: 0.25 });
        } catch (e) {
            map.setView([lat, lng], z);
        }
        setTimeout(function () {
            if (map.getZoom() !== z) map.setView([lat, lng], z, { animate: false });
            then();
        }, 1500);
    }

    function focusGroup(id) {
        closeAll();
        var g = findGroup(id);
        if (!g) return;
        var lat = parseFloat(g.lat), lng = parseFloat(g.lng);
        if (!lat && !lng) return;
        flyTo(lat, lng, function () {
            var m = groupMarkers[String(id)];
            if (m) { try { m.openPopup(); } catch (e) {} }
            highlight('sidebar-group-' + id);
        });
    }

    function focusEvent(id) {
        closeAll();
        var ev = findEvent(id);
        if (!ev) return;
        var lat = parseFloat(ev.lat), lng = parseFloat(ev.lng);
        if (!lat && !lng) return;
        flyTo(lat, lng, function () {
            var m = eventMarkers[String(id)];
            if (m) { try { m.openPopup(); } catch (e) {} }
            highlight('sidebar-event-' + id);
        });
    }

    var regionLevels = {};
    var regionMembers = {};
    allGroups.forEach(function (g) {
        var pairs = [];
        if (g.province) pairs.push(['p', g.province]);
        if (g.province && g.city) pairs.push(['c', g.province + '|' + g.city]);
        pairs.forEach(function (p) {
            var key = p[0] + ':' + p[1];
            var lvl = parseInt(g.activity_level, 10) || 5;
            if (regionLevels[key] === undefined || lvl < regionLevels[key]) regionLevels[key] = lvl;
            (regionMembers[key] = regionMembers[key] || []).push(g);
        });
    });

    function regionColor(key, fallback) {
        var lvl = regionLevels[key];
        if (lvl === undefined) return fallback;
        return GROUP_COLORS[lvl] || '#27ae60';
    }

    function regionPopup(title, key, subtitle) {
        var list = regionMembers[key] || [];
        var html = '<div class="popup-content"><div class="name">' + escapeHtml(title) + '</div>';
        if (subtitle && subtitle !== title) html += '<div class="meta">' + escapeHtml(subtitle) + '</div>';
        if (!list.length) return html + '<div class="meta">暂无群</div></div>';
        list.slice().sort(function (a, b) { return a.activity_level - b.activity_level; }).forEach(function (g) {
            var c = GROUP_COLORS[g.activity_level] || '#27ae60';
            var hasCoord = parseFloat(g.lat) || parseFloat(g.lng);
            html += '<div style="margin:.35rem 0;padding-bottom:.35rem;border-bottom:1px solid #eef2f7;' + (hasCoord ? 'cursor:pointer' : '') + '"'
                + (hasCoord ? ' data-region-group-id="' + g.id + '"' : '') + '>';
            html += '<div style="font-weight:700;display:flex;align-items:center;gap:.4rem">'
                + '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' + c + '"></span>'
                + escapeHtml(g.name) + '</div>';
            html += '<div class="meta">QQ群：<span class="qq">' + escapeHtml(g.qq_group) + '</span></div></div>';
        });
        return html + '</div>';
    }

    var provinceBound = null;
    var cityBound = null;
    var boundMode = 'default';
    var geoCache = { province: null, city: null };
    var geoPending = { province: null, city: null };

    function buildBoundLayer(which) {
        var isCity = which === 'city';
        return L.geoJSON(null, {
            renderer: L.canvas({ padding: 0.5 }),
            smoothFactor: 0,
            style: function (feature) {
                var p = feature.properties || {};
                var name = String(p.name || p.NAME || '');
                if (isCity) {
                    var prov = String(p.province || p.NAME_1 || '');
                    var muni = ['北京市', '天津市', '上海市', '重庆市'].indexOf(prov) !== -1;
                    var key = muni ? ('p:' + prov) : ('c:' + prov + '|' + name);
                    if (regionLevels[key] === undefined) {
                        return { color: '#a9b4ba', weight: 1.2, opacity: .75, fillColor: '#c8d0d4', fillOpacity: .05, lineJoin: 'round' };
                    }
                    var col2 = regionColor(key, '#c8d0d4');
                    return { color: col2, weight: 1.5, opacity: .85, fillColor: col2, fillOpacity: .18, lineJoin: 'round' };
                }
                var col = regionColor('p:' + name, '#95a5a6');
                return { color: col, weight: 2, opacity: .85, fillColor: col, fillOpacity: .18, dashArray: '4 3' };
            },
            onEachFeature: function (feature, layer) {
                var p = feature.properties || {};
                var name = String(p.name || p.NAME || '');
                if (isCity) {
                    var prov = String(p.province || p.NAME_1 || '');
                    var muni = ['北京市', '天津市', '上海市', '重庆市'].indexOf(prov) !== -1;
                    var rkey = muni ? ('p:' + prov) : ('c:' + prov + '|' + name);
                    var empty = regionLevels[rkey] === undefined;
                    layer.bindPopup(regionPopup(name, rkey, prov));
                    layer.on('mouseover', function () { layer.setStyle({ fillOpacity: empty ? .14 : .32 }); });
                    layer.on('mouseout', function () { layer.setStyle({ fillOpacity: empty ? .05 : .18 }); });
                } else {
                    layer.bindPopup(regionPopup(name, 'p:' + name, ''));
                    layer.on('mouseover', function () { layer.setStyle({ fillOpacity: .32 }); });
                    layer.on('mouseout', function () { layer.setStyle({ fillOpacity: .18 }); });
                }
            }
        });
    }

    function ensureGeo(which) {
        if (!BOUNDARY.enabled || !BOUNDARY[which]) return Promise.resolve(null);
        if (geoCache[which]) return Promise.resolve(geoCache[which]);
        if (!geoPending[which]) {
            var file = which === 'city' ? 'china-cities.geojson' : 'china-provinces.geojson';
            geoPending[which] = fetch(file + '?v=' + Date.now())
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    geoCache[which] = d;
                    var layer = buildBoundLayer(which);
                    layer.addData(d);
                    if (which === 'city') cityBound = layer; else provinceBound = layer;
                    return d;
                })
                .catch(function (e) { console.error(file + ' 加载失败', e); geoPending[which] = null; return null; });
        }
        return geoPending[which];
    }

    function updateRegionLayers() {
        var z = map.getZoom();
        var showProvince = boundMode === 'province' || (boundMode === 'default' && z < CITY_ZOOM);
        var showCity = boundMode === 'city' || (boundMode === 'default' && z >= CITY_ZOOM);
        [[provinceBound, showProvince], [cityBound, showCity]].forEach(function (pair) {
            var layer = pair[0], show = pair[1];
            if (!layer) return;
            if (show && !map.hasLayer(layer)) layer.addTo(map);
            if (!show && map.hasLayer(layer)) map.removeLayer(layer);
        });
        bringMarkersFront();
    }

    function updateLayers() {
        var z = map.getZoom();
        var pr = z < 6 ? 8 : z < 8 ? 10 : 13;
        var cr = z < 6 ? 4 : z < 8 ? 6 : z < 10 ? 8 : 9;
        provinceGroups.eachLayer(function (m) { if (m.setRadius) m.setRadius(pr); });
        cityGroups.eachLayer(function (m) { if (m.setRadius) m.setRadius(cr); });
        updateRegionLayers();
    }

    var zoomTimer = null;
    map.on('zoomend', function () {
        clearTimeout(zoomTimer);
        zoomTimer = setTimeout(updateLayers, 80);
    });
    updateLayers();

    setLoading(30, '加载边界...');
    Promise.all([ensureGeo('province'), ensureGeo('city')]).then(function () {
        setLoading(90, '渲染边界...');
        setTimeout(function () { updateLayers(); hideLoading(); }, 150);
    });

    (function () {
        var ctrl = document.getElementById('boundModeCtrl');
        if (!ctrl) return;
        var btns = ctrl.querySelectorAll('button[data-mode]');
        btns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var mode = btn.getAttribute('data-mode');
                if (mode === boundMode) return;
                boundMode = mode;
                btns.forEach(function (b) { b.classList.toggle('active', b === btn); });
                ensureGeo(mode === 'default' ? (map.getZoom() >= CITY_ZOOM ? 'city' : 'province') : mode)
                    .then(updateLayers);
            });
        });
    })();

    function switchTab(tab, btn) {
        document.querySelectorAll('.sidebar-tabs button').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        document.getElementById('tab-groups').style.display = tab === 'groups' ? 'block' : 'none';
        document.getElementById('tab-events').style.display = tab === 'events' ? 'block' : 'none';
    }

    function openSidebar() {
        var s = document.getElementById('sidebar');
        var b = document.getElementById('backdrop');
        if (!s) return;
        var willOpen = !s.classList.contains('open');
        s.classList.toggle('open', willOpen);
        if (b) {
            if (willOpen) {
                document.getElementById('headerMenu').classList.remove('open');
                b.style.top = window.innerWidth <= 720 ? '44vh' : '70px';
                b.classList.add('show');
            } else {
                b.classList.remove('show');
                b.style.top = '';
            }
        }
    }

    function toggleMenu() {
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
                b.style.top = (header ? header.getBoundingClientRect().height : 58) + 'px';
                b.classList.add('show');
            } else {
                b.classList.remove('show');
                b.style.top = '';
            }
        }
    }

    function closeAll() {
        var m = document.getElementById('headerMenu');
        var s = document.getElementById('sidebar');
        var b = document.getElementById('backdrop');
        if (m) m.classList.remove('open');
        if (s) s.classList.remove('open');
        if (b) { b.classList.remove('show'); b.style.top = ''; }
    }

    function applySearch() {
        var input = document.getElementById('sidebarSearch');
        if (!input) return;
        var q = input.value.trim().toLowerCase();
        document.querySelectorAll('.sidebar-item').forEach(function (el) {
            var text = (el.getAttribute('data-text') || '') + ' ' + el.textContent.toLowerCase();
            el.style.display = !q || text.indexOf(q) !== -1 ? '' : 'none';
        });
        document.querySelectorAll('.sidebar-section-title').forEach(function (el) {
            var node = el.nextElementSibling;
            var visible = false;
            while (node && !node.classList.contains('sidebar-section-title')) {
                if (node.classList.contains('sidebar-item') && node.style.display !== 'none') { visible = true; break; }
                node = node.nextElementSibling;
            }
            el.style.display = visible || !q ? '' : 'none';
        });
    }

    document.addEventListener('click', function (e) {
        var t = e.target;

        if (t.closest('#headerToggle')) { toggleMenu(); return; }
        if (t.closest('#sidebarToggle')) { openSidebar(); return; }

        var tabBtn = t.closest('.sidebar-tabs button[data-tab]');
        if (tabBtn) { switchTab(tabBtn.dataset.tab, tabBtn); return; }

        var gItem = t.closest('[data-group-id]');
        if (gItem) { focusGroup(gItem.dataset.groupId); return; }

        var evItem = t.closest('[data-event-id]');
        if (evItem) { focusEvent(evItem.dataset.eventId); return; }

        var regionItem = t.closest('[data-region-group-id]');
        if (regionItem) { focusGroup(regionItem.dataset.regionGroupId); return; }

        var scrollLink = t.closest('[data-scroll]');
        if (scrollLink) {
            e.preventDefault();
            var wanted = scrollLink.dataset.scroll;
            var tabBtnEl = document.querySelector('.sidebar-tabs button[data-tab="' + wanted + '"]');
            if (tabBtnEl) switchTab(wanted, tabBtnEl);
            var s = document.getElementById('sidebar');
            if (s && !s.classList.contains('open')) openSidebar();
            return;
        }

        var menu = document.getElementById('headerMenu');
        if (menu && menu.classList.contains('open') && !menu.contains(t)) menu.classList.remove('open');

        var sidebar = document.getElementById('sidebar');
        if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(t)) closeAll();

        var backdrop = document.getElementById('backdrop');
        if (backdrop && t === backdrop) closeAll();
    });

    var searchInput = document.getElementById('sidebarSearch');
    if (searchInput) searchInput.addEventListener('input', applySearch);

    window.addEventListener('load', function () { map.invalidateSize(); });
    window.addEventListener('resize', function () { map.invalidateSize(); });

    window.CVIMap = {
        getGroups: function () { return JSON.stringify(allGroups); },
        getEvents: function () { return JSON.stringify(ongoingEvents.concat(upcomingEvents, endedEvents)); },
        focusGroupById: function (id) { focusGroup(id); },
        focusEventById: function (id) { focusEvent(id); },
        flyTo: function (lat, lng, zoom) { map.flyTo([lat, lng], zoom || 10, { duration: 1.4 }); },
        getCenter: function () {
            var c = map.getCenter();
            return JSON.stringify({ lat: c.lat, lng: c.lng, zoom: map.getZoom() });
        }
    };
})();
</script>
</body>
</html>
