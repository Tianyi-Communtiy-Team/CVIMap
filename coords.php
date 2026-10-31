<?php
// 无坐标群聊按省 / 市中心点补坐标

    function fillGroupCoords(&$groups, $cityMap, $provinceMap) {
        foreach ($groups as &$g) {
            $lat = (float)($g['lat'] ?? 0);
            $lng = (float)($g['lng'] ?? 0);
            if ($lat == 0 && $lng == 0) {
                $city = $g['city'] ?? '';
                $prov = $g['province'] ?? '';
                if ($city !== '' && isset($cityMap[$city])) {
                    $g['lat'] = $cityMap[$city][0];
                    $g['lng'] = $cityMap[$city][1];
                    $g['_coord_source'] = 'city';
                } elseif ($prov !== '' && isset($provinceMap[$prov])) {
                    $g['lat'] = $provinceMap[$prov][0];
                    $g['lng'] = $provinceMap[$prov][1];
                    $g['_coord_source'] = 'province';
                }
            }
        }
        unset($g);
    }
