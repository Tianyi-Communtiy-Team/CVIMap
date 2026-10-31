<?php
require_once __DIR__ . '/common.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$stmt = db()->prepare("SELECT id, name, province, city, activity_level, qq_group FROM fans_groups WHERE status = 'approved' ORDER BY activity_level ASC, name ASC");
$stmt->execute();
$allApprovedGroups = [];
foreach ($stmt->fetchAll() as $g) {
    $allApprovedGroups[] = [
        'id' => (int)$g['id'],
        'name' => (string)$g['name'],
        'province' => (string)($g['province'] ?? ''),
        'city' => (string)($g['city'] ?? ''),
        'activity_level' => (int)($g['activity_level'] ?? 5),
        'qq_group' => (string)($g['qq_group'] ?? ''),
    ];
}

$keys = function_exists('getTiandituKeys') ? getTiandituKeys() : [];
if (empty($keys) && isset($GLOBALS['TIANDITU_KEYS_DEFAULT'])) {
    $keys = $GLOBALS['TIANDITU_KEYS_DEFAULT'];
}
$mode = strtolower((string)($_GET['mode'] ?? 'auto'));
if (!in_array($mode, ['auto', 'province', 'city'], true)) {
    $mode = 'auto';
}
$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : 35.0;
$lng = isset($_GET['lng']) ? (float)$_GET['lng'] : 105.0;
if ($lat < -90 || $lat > 90) {
    $lat = 35.0;
}
if ($lng < -180 || $lng > 180) {
    $lng = 105.0;
}
$zoom = isset($_GET['zoom']) ? (int)$_GET['zoom'] : 4;
if ($zoom < 3) {
    $zoom = 3;
}
if ($zoom > 14) {
    $zoom = 14;
}
$labels = (($_GET['labels'] ?? '1') === '0') ? 0 : 1;
$zoomBtn = (($_GET['zoombtn'] ?? '0') === '1') ? 1 : 0;
$usePopup = (($_GET['popup'] ?? '1') === '0') ? 0 : 1;
$fitOnLoad = (($_GET['fit'] ?? '0') === '1') ? 1 : 0;
$useBridge = (($_GET['bridge'] ?? '1') === '0') ? 0 : 1;
$brand = (($_GET['brand'] ?? '1') === '0') ? 0 : 1;
$bg = (string)($_GET['bg'] ?? '');
if ($bg === '' || !preg_match('/^#[0-9a-fA-F]{3,8}$/', $bg)) {
    $bg = '#eaf4fb';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>地图视图</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
html, body { margin: 0; padding: 0; height: 100%; width: 100%; overflow: hidden; background: <?= e($bg) ?>; }
#map { position: absolute; inset: 0; background: <?= e($bg) ?>; }
.leaflet-container { background: <?= e($bg) ?>; font-family: system-ui, -apple-system, "PingFang SC", "Microsoft YaHei", sans-serif; }
.loading-bar {
    position: fixed; bottom: 0; left: 0; right: 0; height: 5px;
    background: rgba(0,0,0,.06); z-index: 1200; pointer-events: none;
    opacity: 1; transition: opacity .5s ease;
}
.loading-bar.done { opacity: 0; }
.loading-bar .bar {
    height: 100%; width: 0%;
    background: linear-gradient(90deg, #66ccff, #4aa3e8);
    transition: width .3s ease;
    box-shadow: 0 0 8px rgba(102,204,255,.8);
}
.loading-bar .label {
    position: fixed; top: .9rem; left: 50%; transform: translateX(-50%);
    background: rgba(255,255,255,.94); padding: .25rem .85rem; border-radius: 20px;
    font-size: .72rem; color: #3a5b6b; white-space: nowrap;
    box-shadow: 0 2px 10px rgba(0,0,0,.1);
    transition: opacity .5s ease;
}
.loading-bar.done .label { opacity: 0; }

.brand-bar {
    position: absolute; left: .7rem; bottom: .5rem; z-index: 1000;
    display: flex; align-items: flex-end; gap: .45rem;
    pointer-events: none;
}

.legend {
    background: rgba(255,255,255,.9); backdrop-filter: blur(10px);
    border-radius: .85rem; padding: .55rem .7rem;
    box-shadow: 0 4px 18px rgba(0,0,0,.12);
    font-size: .68rem; line-height: 1.35;
}
.legend-title { font-weight: 800; color: #1a2e3b; margin-bottom: .3rem; font-size: .72rem; }
.legend-item { display: flex; align-items: center; gap: .35rem; margin-bottom: .15rem; color: #4a6fa5; }
.legend-swatch { width: 14px; height: 9px; border-radius: 3px; flex-shrink: 0; border: 1.5px solid; }

.attribution {
    background: rgba(255,255,255,.9); backdrop-filter: blur(10px);
    border-radius: .85rem; padding: .35rem .6rem;
    box-shadow: 0 4px 18px rgba(0,0,0,.12);
    display: flex; align-items: center; gap: .45rem;
    font-size: .6rem; color: #3a5b6b; line-height: 1.3;
}
.attribution img { height: 22px; width: auto; display: block; }
.attribution .txt { display: flex; flex-direction: column; gap: .05rem; }
.attribution .txt .row1 { font-weight: 600; color: #1a2e3b; font-size: .62rem; }
.attribution .txt .row2 { font-size: .58rem; color: #7d98aa; }

@media (max-width: 420px) {
    .brand-bar { flex-direction: column; align-items: flex-start; }
}

.leaflet-control-zoom a { color: #176c96 !important; }
</style>
</head>
<body>
<div id="map"></div>
<div class="loading-bar" id="loadingBar">
    <div class="bar" id="loadingBarInner"></div>
    <div class="label" id="loadingLabel">加载边界…</div>
</div>
<?php if ($brand): ?>
<div class="brand-bar">
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
        <img src="core_assets/core_tianditu_logo.svg" alt="天地图">
        <div class="txt">
            <div class="row1"><?= e(getConfig('map_number', '')) ?></div>
            <div class="row2"><?= e(getConfig('survey_license', '')) ?></div>
        </div>
    </div>
</div>
<?php endif; ?>
<script>
(function () {
    'use strict';

    var TIANDITU_KEYS = <?= json_encode($keys, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var ALL_GROUPS = <?= json_encode($allApprovedGroups, JSON_UNESCAPED_UNICODE) ?>;
    var CFG = {
        mode: <?= json_encode($mode) ?>,
        lat: <?= json_encode($lat) ?>,
        lng: <?= json_encode($lng) ?>,
        zoom: <?= json_encode($zoom) ?>,
        labels: <?= $labels ? 1 : 0 ?>,
        popup: <?= $usePopup ? 1 : 0 ?>,
        fit: <?= $fitOnLoad ? 1 : 0 ?>,
        bridge: <?= $useBridge ? 1 : 0 ?>
    };

    var GROUP_COLORS = {1: '#e74c3c', 2: '#e67e22', 3: '#f1c40f', 4: '#2ecc71', 5: '#27ae60'};
    var REGION_COLORS = {1: '#e74c3c', 2: '#e67e22', 3: '#f1c40f', 4: '#2ecc71', 5: '#27ae60'};

    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, function (c) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
        });
    }

    function normalizeProvince(s) {
        if (!s) return '';
        s = String(s).trim();
        var m = {
            '北京': '北京市', '天津': '天津市', '上海': '上海市', '重庆': '重庆市',
            '河北': '河北省', '山西': '山西省', '辽宁': '辽宁省', '吉林': '吉林省',
            '黑龙江': '黑龙江省', '江苏': '江苏省', '浙江': '浙江省', '安徽': '安徽省',
            '福建': '福建省', '江西': '江西省', '山东': '山东省', '河南': '河南省',
            '湖北': '湖北省', '湖南': '湖南省', '广东': '广东省', '海南': '海南省',
            '四川': '四川省', '贵州': '贵州省', '云南': '云南省', '陕西': '陕西省',
            '甘肃': '甘肃省', '青海': '青海省', '台湾': '台湾省',
            '内蒙古': '内蒙古自治区', '广西': '广西壮族自治区', '西藏': '西藏自治区',
            '宁夏': '宁夏回族自治区', '新疆': '新疆维吾尔自治区',
            '香港': '香港特别行政区', '澳门': '澳门特别行政区'
        };
        return m[s] || s;
    }

    function normalizeCity(s) {
        return s ? String(s).trim() : '';
    }

    function adcodeToProvince(gb) {
        if (!gb) return '';
        var s = String(gb).trim();
        if (s.indexOf('156') === 0 && s.length >= 8) {
            s = s.substring(3);
        }
        var prefix = s.substring(0, 2);
        var m = {
            '11': '北京市', '12': '天津市', '13': '河北省', '14': '山西省', '15': '内蒙古自治区',
            '21': '辽宁省', '22': '吉林省', '23': '黑龙江省',
            '31': '上海市', '32': '江苏省', '33': '浙江省', '34': '安徽省', '35': '福建省',
            '36': '江西省', '37': '山东省',
            '41': '河南省', '42': '湖北省', '43': '湖南省', '44': '广东省', '45': '广西壮族自治区', '46': '海南省',
            '50': '重庆市', '51': '四川省', '52': '贵州省', '53': '云南省', '54': '西藏自治区',
            '61': '陕西省', '62': '甘肃省', '63': '青海省', '64': '宁夏回族自治区', '65': '新疆维吾尔自治区',
            '71': '台湾省', '81': '香港特别行政区', '82': '澳门特别行政区'
        };
        return m[prefix] || '';
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

    var provinceLevelMap = {};
    var provinceGroupMap = {};
    var cityLevelMap = {};
    var cityGroupMap = {};

    ALL_GROUPS.forEach(function (g) {
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
        if (lvl === undefined) return '#95a5a6';
        return REGION_COLORS[lvl] || '#27ae60';
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

    var map = L.map('map', {
        center: [CFG.lat, CFG.lng],
        zoom: CFG.zoom,
        minZoom: 3,
        maxZoom: 14,
        zoomControl: <?= $zoomBtn ? 'true' : 'false' ?>,
        attributionControl: false,
        zoomSnap: 0.5,
        wheelPxPerZoomLevel: 120
    });

    var keyIndex = 0;
    var currentBaseLayer = null;
    var currentLabelLayer = null;
    var currentFailCount = 0;
    var switching = false;

    function buildUrl(pathName, layerName, tk, sk) {
        var url = 'https://t{s}.tianditu.gov.cn/' + pathName + '/wmts'
            + '?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER=' + layerName
            + '&STYLE=default&TILEMATRIXSET=w&FORMAT=tiles'
            + '&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}'
            + '&tk=' + encodeURIComponent(tk);
        if (sk) {
            url += '&sk=' + encodeURIComponent(sk);
        }
        return url;
    }

    function onTileError() {
        if (switching) return;
        currentFailCount++;
        if (currentFailCount >= 3) switchToNextKey();
    }

    function switchToNextKey() {
        if (switching || !TIANDITU_KEYS.length) return;
        switching = true;
        keyIndex++;
        currentFailCount = 0;
        var key = TIANDITU_KEYS[keyIndex % TIANDITU_KEYS.length];
        if (currentBaseLayer) map.removeLayer(currentBaseLayer);
        if (currentLabelLayer) map.removeLayer(currentLabelLayer);
        mountTileLayers(key.tk, key.sk);
        setTimeout(function () { switching = false; }, 500);
    }

    function mountTileLayers(tk, sk) {
        var blank = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
        currentBaseLayer = L.tileLayer(buildUrl('vec_w', 'vec', tk, sk), {
            subdomains: ['0', '1', '2', '3', '4', '5', '6', '7'],
            maxZoom: 18,
            errorTileUrl: blank,
            crossOrigin: true
        });
        currentBaseLayer.on('tileerror', onTileError);
        currentBaseLayer.addTo(map);
        currentBaseLayer.bringToBack();

        if (CFG.labels) {
            currentLabelLayer = L.tileLayer(buildUrl('cva_w', 'cva', tk, sk), {
                subdomains: ['0', '1', '2', '3', '4', '5', '6', '7'],
                maxZoom: 18,
                errorTileUrl: blank,
                crossOrigin: true
            });
            currentLabelLayer.on('tileerror', onTileError);
            currentLabelLayer.addTo(map);
        }
        bringBoundariesToFront();
    }

    var vectorRenderer = L.canvas({padding: 0.5, tolerance: 0.3});

    function onRegionClick(prov, city, list, layer) {
        if (layer && layer.setStyle) {
            layer.setStyle({fillOpacity: 0.42, weight: 3});
        }
        send({
            type: 'region',
            province: prov || '',
            city: city || '',
            name: city || prov || '',
            level: provinceLevelMap[prov],
            groups: (list || []).map(function (g) {
                return {id: g.id, name: g.name, qq_group: g.qq_group, activity_level: g.activity_level};
            })
        });
    }

    var provinceBoundLayer = L.geoJSON(null, {
        renderer: vectorRenderer,
        smoothFactor: 0,
        style: function (feature) {
            var prov = extractProvinceName(feature);
            var c = getProvinceColor(prov);
            return {color: c, weight: 2, opacity: 0.85, fillColor: c, fillOpacity: 0.18, dashArray: '4 3'};
        },
        onEachFeature: function (feature, layer) {
            var prov = extractProvinceName(feature);
            var info = provinceGroupMap[prov] || [];
            layer.on('mouseover', function () { layer.setStyle({fillOpacity: 0.32, weight: 2.5}); });
            layer.on('mouseout', function () { layer.setStyle({fillOpacity: 0.18, weight: 2}); });
            layer.on('click', function () { onRegionClick(prov, '', info, layer); });
        }
    });

    var cityBoundLayer = L.geoJSON(null, {
        renderer: vectorRenderer,
        smoothFactor: 0,
        style: function (feature) {
            var prov = extractCityProvince(feature);
            var city = extractCityName(feature);
            if (isCityEmpty(prov, city)) {
                return {color: EMPTY_CITY_COLOR, weight: 1.2, opacity: 0.75, fillColor: EMPTY_CITY_FILL, fillOpacity: 0.05, lineJoin: 'round'};
            }
            var c = getCityColor(prov, city);
            return {color: c.color, weight: 1.5, opacity: 0.85, fillColor: c.color, fillOpacity: 0.18, lineJoin: 'round'};
        },
        onEachFeature: function (feature, layer) {
            var prov = extractCityProvince(feature);
            var city = extractCityName(feature);
            var info;
            if (isMunicipality(prov)) {
                info = provinceGroupMap[prov] || [];
            } else {
                info = cityGroupMap[prov + '|' + city] || [];
            }
            var empty = isCityEmpty(prov, city);
            layer.on('mouseover', function () {
                layer.setStyle(empty ? {fillOpacity: 0.14, weight: 1.6} : {fillOpacity: 0.32, weight: 2.5});
            });
            layer.on('mouseout', function () {
                layer.setStyle(empty ? {fillOpacity: 0.05, weight: 1.2} : {fillOpacity: 0.18, weight: 1.5});
            });
            layer.on('click', function () { onRegionClick(prov, city, info, layer); });
        }
    });

    function bringBoundariesToFront() {
        try { if (map.hasLayer(provinceBoundLayer)) provinceBoundLayer.bringToFront(); } catch (e) {}
        try { if (map.hasLayer(cityBoundLayer)) cityBoundLayer.bringToFront(); } catch (e) {}
    }

    var provinceGeoData = null;
    var cityGeoData = null;
    var boundMode = CFG.mode;
    var geoReady = false;
    var CITY_ZOOM = 7;
    var GEO_FILES = {province: 'china-provinces.geojson', city: 'china-cities.geojson'};
    var GEO_EXPECT = {province: 1782579, city: 4299162};
    var geoProgress = {province: 0, city: 0};
    var geoLoaded = {province: false, city: false};

    function setProgress(pct, text) {
        var inner = document.getElementById('loadingBarInner');
        var label = document.getElementById('loadingLabel');
        if (inner) inner.style.width = Math.max(0, Math.min(100, pct)) + '%';
        if (label && text) label.textContent = text;
    }

    function hideProgress() {
        var bar = document.getElementById('loadingBar');
        if (bar) bar.classList.add('done');
    }

    function refreshProgress() {
        var total = GEO_EXPECT.province + GEO_EXPECT.city;
        var done = 0;
        ['province', 'city'].forEach(function (k) {
            var f = geoLoaded[k] ? 1 : Math.min(0.98, geoProgress[k]);
            done += f * (GEO_EXPECT[k] / total);
        });
        setProgress(done * 100, (geoLoaded.province && geoLoaded.city) ? '渲染边界…' : '加载边界…');
    }

    function fetchGeoProgressed(which) {
        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', GEO_FILES[which] + '?v=' + Date.now(), true);
            xhr.onprogress = function (e) {
                if (e.lengthComputable && e.total > 0) {
                    geoProgress[which] = e.loaded / e.total;
                } else {
                    geoProgress[which] = (e.loaded || 0) / GEO_EXPECT[which];
                }
                refreshProgress();
            };
            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    var d = null;
                    try {
                        d = JSON.parse(xhr.responseText);
                    } catch (err) {
                        reject(err);
                        return;
                    }
                    geoProgress[which] = 1;
                    geoLoaded[which] = true;
                    refreshProgress();
                    resolve(d);
                } else {
                    reject(new Error('HTTP ' + xhr.status));
                }
            };
            xhr.onerror = function () { reject(new Error('network error')); };
            xhr.send();
        });
    }

    function loadAllGeo() {
        setProgress(2, '加载边界…');
        return Promise.all([
            fetchGeoProgressed('province')
                .then(function (d) { provinceGeoData = d; return d; })
                .catch(function (e) {
                    send({type: 'error', scope: 'geo-province', message: String(e)});
                    return null;
                }),
            fetchGeoProgressed('city')
                .then(function (d) { cityGeoData = d; return d; })
                .catch(function (e) {
                    send({type: 'error', scope: 'geo-city', message: String(e)});
                    return null;
                })
        ]).then(function (res) {
            setProgress(96, '渲染边界…');
            try {
                if (res[0]) provinceBoundLayer.addData(res[0]);
                if (res[1]) cityBoundLayer.addData(res[1]);
            } catch (e) {
                send({type: 'error', scope: 'geo-render', message: String(e)});
            }
            setProgress(100, '完成');
            geoReady = true;
            updateRegionLayers();
            notifyReady();
            setTimeout(hideProgress, 320);
            if (CFG.fit) {
                setTimeout(fitBounds, 80);
            }
        });
    }

    function updateRegionLayers() {
        var z = map.getZoom();
        var useCity = boundMode === 'city' || (boundMode === 'auto' && z >= CITY_ZOOM);

        if (useCity && cityGeoData) {
            if (map.hasLayer(provinceBoundLayer)) map.removeLayer(provinceBoundLayer);
            if (!map.hasLayer(cityBoundLayer)) cityBoundLayer.addTo(map);
        } else if (provinceGeoData) {
            if (map.hasLayer(cityBoundLayer)) map.removeLayer(cityBoundLayer);
            if (!map.hasLayer(provinceBoundLayer)) provinceBoundLayer.addTo(map);
        }
        bringBoundariesToFront();
        send({type: 'view', lat: map.getCenter().lat, lng: map.getCenter().lng, zoom: map.getZoom()});
    }

    var readySent = false;
    function notifyReady() {
        if (readySent) return;
        readySent = true;
        hideProgress();
        send({type: 'ready'});
    }

    function send(msg) {
        if (!CFG.bridge) return;
        var s;
        try { s = JSON.stringify(msg); } catch (e) { return; }
        try {
            if (window.LTYBridge && typeof window.LTYBridge.postMessage === 'function') {
                window.LTYBridge.postMessage(s);
            }
        } catch (e) {}
        try {
            if (window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.LTYBridge) {
                window.webkit.messageHandlers.LTYBridge.postMessage(msg);
            }
        } catch (e) {}
        try {
            if (window.parent && window.parent !== window) {
                window.parent.postMessage(msg, '*');
            }
        } catch (e) {}
    }

    function setMode(mode) {
        if (['auto', 'province', 'city'].indexOf(mode) === -1) return;
        boundMode = mode;
        updateRegionLayers();
    }

    function setView(lat, lng, zoom) {
        if (typeof lat !== 'number' || typeof lng !== 'number') return;
        var z = typeof zoom === 'number' ? zoom : map.getZoom();
        map.setView([lat, lng], z);
    }

    function flyToProvince(name) {
        var target = normalizeProvince(name);
        if (!target) return;
        if (!provinceGeoData) {
            send({type: 'error', scope: 'focus', message: 'geo not ready'});
            return;
        }
        var found = null;
        provinceBoundLayer.eachLayer(function (layer) {
            if (found) return;
            if (extractProvinceName(layer.feature) === target) found = layer;
        });
        if (found) {
            if (map.hasLayer(cityBoundLayer)) map.removeLayer(cityBoundLayer);
            if (!map.hasLayer(provinceBoundLayer)) provinceBoundLayer.addTo(map);
            bringBoundariesToFront();
            found.setStyle({fillOpacity: 0.4, weight: 3});
            map.fitBounds(found.getBounds(), {padding: [24, 24]});
            send({type: 'focus', province: target});
        } else {
            send({type: 'error', scope: 'focus', message: 'not found: ' + target});
        }
    }

    function fitBounds() {
        var layer = map.hasLayer(cityBoundLayer) ? cityBoundLayer : provinceBoundLayer;
        var b = null;
        try { b = layer.getBounds(); } catch (e) {}
        if (b && b.isValid()) {
            map.fitBounds(b, {padding: [16, 16]});
        }
    }

    function handleCmd(d) {
        if (d.type === 'setView') setView(d.lat, d.lng, d.zoom);
        else if (d.type === 'setMode') setMode(d.mode);
        else if (d.type === 'fitBounds') fitBounds();
        else if (d.type === 'flyToProvince') flyToProvince(d.name);
        else if (d.type === 'getView') {
            send({type: 'view', lat: map.getCenter().lat, lng: map.getCenter().lng, zoom: map.getZoom()});
        }
    }

    window.addEventListener('message', function (ev) {
        var d = ev.data;
        if (typeof d === 'string') {
            try { d = JSON.parse(d); } catch (e) { return; }
        }
        if (!d || typeof d !== 'object' || !d.type) return;
        handleCmd(d);
    });

    window.LTYMap = {
        setView: setView,
        setMode: setMode,
        fitBounds: fitBounds,
        flyToProvince: flyToProvince,
        getView: function () {
            return {lat: map.getCenter().lat, lng: map.getCenter().lng, zoom: map.getZoom()};
        }
    };

    if (TIANDITU_KEYS.length) {
        mountTileLayers(TIANDITU_KEYS[0].tk, TIANDITU_KEYS[0].sk);
    } else {
        send({type: 'error', scope: 'keys', message: 'no tianditu key'});
    }

    var zoomDebounce = null;
    map.on('zoomend', function () {
        clearTimeout(zoomDebounce);
        zoomDebounce = setTimeout(updateRegionLayers, 80);
    });
    map.on('moveend', function () {
        send({type: 'view', lat: map.getCenter().lat, lng: map.getCenter().lng, zoom: map.getZoom()});
    });

    updateRegionLayers();
    loadAllGeo();

    setTimeout(notifyReady, 15000);

    window.addEventListener('load', function () { map.invalidateSize(); });
    window.addEventListener('resize', function () { map.invalidateSize(); });
    setTimeout(function () { map.invalidateSize(); }, 300);
})();
</script>
</body>
</html>
