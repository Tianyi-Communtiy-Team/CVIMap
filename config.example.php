<?php
/**
 * CVIMap 配置模板
 *
 * 部署只需两步：
 *   1. 复制本文件为 config.php
 *   2. 把 tianditu_keys 里的 tk / sk 换成你自己的天地图浏览器端密钥
 *
 * 数据库：默认使用本机的 vocaloidmap 库（建表语句见 schema.sql）。
 * 如需独立数据库，修改下面 $db_* 四个变量即可，其余配置无需改动。
 */

$db_host = 'localhost';
$db_port = 3306;
$db_name = 'vocaloidmap';
$db_user = 'vocaloidmap';
$db_pass = 'YOUR_DB_PASS';

define('SITE_NAME', '中V资讯地图');
define('SITE_SUBTITLE', 'core.map.ltycommunity.cn');
define('SITE_URL', 'https://core.map.ltycommunity.cn');
define('SITE_VERSION', '1.0.0');

define('THEME_PRIMARY', '#66ccff');
define('THEME_PRIMARY_DARK', '#3ba9e0');
define('THEME_ACCENT', '#4aa3e8');
define('THEME_BG_START', '#f6fbff');
define('THEME_BG_END', '#eaf4fb');

define('UPLOAD_DIR', __DIR__ . '/uploads');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);
define('SESSION_LIFETIME', 86400 * 30);
define('PASS_URL', 'https://user.ltycommunity.cn');

// 天地图浏览器端密钥，可填多个，页面会自动轮询并在失败时切换
// 申请：https://console.tianditu.gov.cn/api/key
$TIANDITU_KEYS_DEFAULT = [
    ['tk' => 'YOUR_TIANDITU_TK', 'sk' => 'YOUR_TIANDITU_SK'],
];

define('REPO_URL', 'https://github.com/Tianyi-Communtiy-Team/CVIMap');

require_once __DIR__ . '/centers.php';
require_once __DIR__ . '/coords.php';
