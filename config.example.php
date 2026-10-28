<?php
/**
 * CVIMap 配置模板
 *
 * 部署只需两步：
 *   1. 复制本文件为 config.php
 *   2. 把 tianditu_keys 里的 tk / sk 换成你自己的天地图浏览器端密钥
 *
 * 数据来自 data/groups.json 与 data/events.json，不需要数据库。
 */

return [
    // 站点名称，显示在标题栏与页头
    'site_name' => '中V资讯地图',

    // 站点地址，留空则自动推断
    'site_url' => '',

    // 天地图密钥（浏览器端类型，可填多个，会自动轮询并在失败时切换）
    // 申请：https://console.tianditu.gov.cn/api/key
    // sk 为可选的服务端签名，申请时若未生成则留空即可
    'tianditu_keys' => [
        ['tk' => 'YOUR_TIANDITU_TK', 'sk' => 'YOUR_TIANDITU_SK'],
    ],

    // 地图初始视图
    'map_center' => [35.0, 105.0],
    'map_zoom' => 5,
    'map_min_zoom' => 4,
    'map_max_zoom' => 12,

    // 切换到市级边界的层级阈值
    'city_zoom' => 9,

    // 审图号与测绘资质（使用天地图底图必须按你的资质如实填写）
    'map_number' => '',
    'survey_license' => '',

    // 底部与菜单里展示的源码仓库地址
    'repo_url' => 'https://github.com/Tianyi-Communtiy-Team/CVIMap',

    // 是否加载根目录的 china-provinces.geojson / china-cities.geojson 做行政区着色
    // 这两个数据文件不包含在本仓库，缺失时地图与群列表仍可正常使用
    'boundary_enabled' => true,
];
