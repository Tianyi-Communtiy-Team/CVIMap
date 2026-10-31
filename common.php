<?php
require_once __DIR__ . '/config.php';

if (!function_exists('isLocalGroupName')) {
    function isLocalGroupName($name) {
        if (empty($name)) return false;

        $excludePatterns = [
            '/only\s*展/i',
            '/ONLY\s*展/i',
            '/漫展/',
            '/同人展/',
            '/展会/',
            '/路展/',
            '/演唱会/',
            '/巡演/',
            '/生日会/',
            '/同人音游/',
            '/同人游戏/',
            '/同人本/',
            '/同人周边/',
            '/cos服/',
            '/娃群/',
            '/专辑群/',
            '/蹲蹲群/',
            '/创作交流/',
            '/新人创作/',
        ];
        foreach ($excludePatterns as $p) {
            if (preg_match($p, $name)) return false;
        }

        $localKeywords = [
            '北京','天津','上海','重庆',
            '河北','山西','辽宁','吉林','黑龙江','江苏','浙江','安徽','福建','江西','山东',
            '河南','湖北','湖南','广东','广西','海南','四川','贵州','云南','西藏','陕西',
            '甘肃','青海','宁夏','新疆','内蒙古','香港','澳门','台湾',
            '石家庄','唐山','秦皇岛','邯郸','邢台','保定','张家口','承德','沧州','廊坊','衡水',
            '太原','大同','阳泉','长治','晋城','朔州','晋中','运城','忻州','临汾','吕梁',
            '呼和浩特','包头','乌海','赤峰','通辽','鄂尔多斯','呼伦贝尔','巴彦淖尔','乌兰察布',
            '沈阳','大连','鞍山','抚顺','本溪','丹东','锦州','营口','阜新','辽阳','盘锦','铁岭','朝阳','葫芦岛',
            '长春','吉林市','四平','辽源','通化','白山','松原','白城','延边',
            '哈尔滨','齐齐哈尔','鸡西','鹤岗','双鸭山','大庆','伊春','佳木斯','七台河','牡丹江','黑河','绥化','大兴安岭',
            '南京','无锡','徐州','常州','苏州','南通','连云港','淮安','盐城','扬州','镇江','泰州','宿迁',
            '杭州','宁波','温州','嘉兴','湖州','绍兴','金华','衢州','舟山','台州','丽水',
            '合肥','芜湖','蚌埠','淮南','马鞍山','淮北','铜陵','安庆','黄山','滁州','阜阳','宿州','六安','亳州','池州','宣城',
            '福州','厦门','莆田','三明','泉州','漳州','南平','龙岩','宁德',
            '南昌','景德镇','萍乡','九江','新余','鹰潭','赣州','吉安','宜春','抚州','上饶',
            '济南','青岛','淄博','枣庄','东营','烟台','潍坊','济宁','泰安','威海','日照','临沂','德州','聊城','滨州','菏泽',
            '郑州','开封','洛阳','平顶山','安阳','鹤壁','新乡','焦作','濮阳','许昌','漯河','三门峡','南阳','商丘','信阳','周口','驻马店','济源',
            '武汉','黄石','十堰','宜昌','襄阳','鄂州','荆门','孝感','荆州','黄冈','咸宁','随州','恩施',
            '长沙','株洲','湘潭','衡阳','邵阳','岳阳','常德','张家界','益阳','郴州','永州','怀化','娄底','湘西',
            '广州','韶关','深圳','珠海','汕头','佛山','江门','湛江','茂名','肇庆','惠州','梅州','汕尾','河源','阳江','清远','东莞','中山','潮州','揭阳','云浮','潮汕','粤北','粤西',
            '南宁','柳州','桂林','梧州','北海','防城港','钦州','贵港','玉林','百色','贺州','河池','来宾','崇左','北部湾',
            '海口','三亚','三沙','儋州',
            '成都','自贡','攀枝花','泸州','德阳','绵阳','广元','遂宁','内江','乐山','南充','眉山','宜宾','广安','达州','雅安','巴中','资阳','阿坝','甘孜','凉山',
            '贵阳','六盘水','遵义','安顺','毕节','铜仁','黔西南','黔东南','黔南',
            '昆明','曲靖','玉溪','保山','昭通','丽江','普洱','临沧','楚雄','红河','文山','西双版纳','大理','德宏','怒江','迪庆',
            '拉萨','日喀则','昌都','林芝','山南','那曲','阿里',
            '西安','铜川','宝鸡','咸阳','渭南','延安','汉中','榆林','安康','商洛',
            '兰州','嘉峪关','金昌','白银','天水','武威','张掖','平凉','酒泉','庆阳','定西','陇南','临夏','甘南',
            '西宁','海东','海北','黄南','海南州','果洛','玉树','海西',
            '银川','石嘴山','吴忠','固原','中卫',
            '乌鲁木齐','克拉玛依','吐鲁番','哈密','昌吉','博尔塔拉','巴音郭楞','阿克苏','克孜勒苏','喀什','和田','伊犁','塔城','阿勒泰',
            '湖北鄂西','鄂西',
        ];

        foreach ($localKeywords as $kw) {
            if (mb_strpos($name, $kw) !== false) return true;
        }

        return false;
    }
}

if (!function_exists('inferGroupIsLocal')) {
    function inferGroupIsLocal($group) {
        $prov = trim($group['province'] ?? '');
        $city = trim($group['city'] ?? '');
        if ($prov !== '' || $city !== '') return true;
        return isLocalGroupName($group['name'] ?? '');
    }
}

if (session_status() === PHP_SESSION_NONE && !defined('LTY_NO_SESSION')) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!function_exists('db')) {
    function db() {
        static $pdo = null;
        if ($pdo === null) {
            global $db_host, $db_port, $db_name, $db_user, $db_pass;
            $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        return $pdo;
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return false;
    }
}

if (!function_exists('currentUser')) {
    function currentUser() {
        return null;
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        $u = currentUser();
        return $u && in_array($u['role'] ?? 'user', ['admin', 'super_admin'], true);
    }
}

if (!function_exists('currentRole')) {
    function currentRole() {
        $u = currentUser();
        return $u ? ($u['role'] ?? 'user') : 'user';
    }
}

if (!function_exists('isSuperAdmin')) {
    function isSuperAdmin() {
        return in_array(currentRole(), ['admin', 'super_admin'], true);
    }
}

if (!function_exists('isVerified')) {
    function isVerified() {
        return in_array(currentRole(), ['verified', 'admin', 'super_admin'], true);
    }
}

if (!function_exists('roleRank')) {
    function roleRank($role = null) {
        $r = $role === null ? currentRole() : (string)$role;
        $map = ['user' => 0, 'verified' => 1, 'admin' => 2, 'super_admin' => 2];
        return $map[$r] ?? 0;
    }
}

if (!function_exists('roleLabel')) {
    function roleLabel($role = null) {
        $r = $role === null ? currentRole() : (string)$role;
        $map = [
            'user' => '普通用户',
            'verified' => '认证用户',
            'admin' => '超级管理员',
            'super_admin' => '超级管理员',
        ];
        return $map[$r] ?? '普通用户';
    }
}

if (!function_exists('roleBadgeSvg')) {
    function roleBadgeSvg($color, $size = 15) {
        $s = (int)$size;
        if ($s < 8) $s = 8;
        return '<svg viewBox="0 0 24 24" width="' . $s . '" height="' . $s . '" aria-hidden="true" focusable="false">'
            . '<circle cx="12" cy="12" r="9.2" fill="' . $color . '"/>'
            . '<circle cx="12" cy="12" r="9.6" fill="none" stroke="' . $color . '" stroke-width="3.2" stroke-dasharray="4.8 3.6"/>'
            . '<path d="M7.1 12.3l3.3 3.3 6.5-6.9" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>'
            . '</svg>';
    }
}

if (!function_exists('roleBadge')) {
    function roleBadge($role = null, $size = 15) {
        $r = $role === null ? currentRole() : (string)$role;
        if ($r === 'verified') {
            return '<span class="role-badge role-verified" title="认证用户">' . roleBadgeSvg('#1d9bf0', $size) . '</span>';
        }
        if ($r === 'admin' || $r === 'super_admin') {
            return '<span class="role-badge role-super" title="超级管理员">' . roleBadgeSvg('#e5484d', $size) . '</span>';
        }
        return '';
    }
}

if (!function_exists('managedGroupIds')) {
    function managedGroupIds($userId = null) {
        $uid = $userId === null ? (int)($_SESSION['user_id'] ?? 0) : (int)$userId;
        if ($uid <= 0) return [];
        $stmt = db()->prepare("SELECT group_id FROM group_managers WHERE user_id = ?");
        $stmt->execute([$uid]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}

if (!function_exists('canManageGroup')) {
    function canManageGroup($groupId) {
        if (!isLoggedIn()) return false;
        if (isSuperAdmin()) return true;
        if (currentRole() !== 'verified') return false;
        static $cache = null;
        if ($cache === null) $cache = array_flip(managedGroupIds());
        return isset($cache[(int)$groupId]);
    }
}

if (!function_exists('e')) {
    function e($s) {
        return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('csrfToken')) {
    function csrfToken() {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf'];
    }
}

if (!function_exists('csrfCheck')) {
    function csrfCheck($token) {
        return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$token);
    }
}

if (!function_exists('realImageExt')) {
    /**
     * 通过文件真实内容判断类型，返回标准扩展名；非法返回 null。
     * 不信任客户端提供的 MIME 与原始文件名扩展名。支持图片与 PDF。
     * 兼容未启用 fileinfo 扩展的环境（优先 finfo，回退 getimagesize / magic bytes）。
     */
    function realImageExt($tmpPath, $allowPdf = false) {
        if (!is_file($tmpPath)) return null;
        $mime = '';

        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string)$finfo->file($tmpPath);
        } else {
            $head5 = (string)@file_get_contents($tmpPath, false, null, 0, 5);
            if (substr($head5, 0, 4) === "\x00\x00\x01\x00") {
                $mime = 'image/x-icon';
            } else {
                $info = @getimagesize($tmpPath);
                if ($info && !empty($info['mime'])) {
                    $mime = (string)$info['mime'];
                } elseif ($allowPdf && strncmp($head5, '%PDF-', 5) === 0) {
                    $mime = 'application/pdf';
                }
            }
        }

        $map = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'image/x-icon' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
        ];
        if ($allowPdf) $map['application/pdf'] = 'pdf';
        return $map[$mime] ?? null;
    }
}

if (!function_exists('getConfig')) {
    function getConfig($key, $default = '') {
        static $configs = null;
        if ($configs === null) {
            $configs = [];
            try {
                $stmt = db()->query("SELECT k, v FROM settings");
                foreach ($stmt->fetchAll() as $row) {
                    $configs[$row['k']] = $row['v'];
                }
            } catch (PDOException $e) {}
        }
        return isset($configs[$key]) && $configs[$key] !== '' ? $configs[$key] : $default;
    }
}

if (!function_exists('setConfig')) {
    function setConfig($key, $value) {
        $stmt = db()->prepare("INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)");
        $stmt->execute([$key, $value]);
    }
}

if (!function_exists('ensureHiddenGroupTable')) {
    function ensureHiddenGroupTable() {
        try {
            if (getConfig('hidden_groups_ready', '') === '1') return true;
            db()->exec("CREATE TABLE IF NOT EXISTS `user_hidden_groups` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED NOT NULL,
                `group_id` INT UNSIGNED NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_user_group` (`user_id`, `group_id`),
                KEY `idx_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            setConfig('hidden_groups_ready', '1');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('hiddenGroupIds')) {
    function hiddenGroupIds($userId) {
        $userId = (int)$userId;
        if ($userId <= 0 || !ensureHiddenGroupTable()) return [];
        $stmt = db()->prepare("SELECT group_id FROM user_hidden_groups WHERE user_id = ?");
        $stmt->execute([$userId]);
        $ids = [];
        foreach ($stmt->fetchAll() as $row) {
            $ids[] = (int)$row['group_id'];
        }
        return $ids;
    }
}

if (!function_exists('hideGroupIds')) {
    function hideGroupIds($userId, array $ids) {
        $userId = (int)$userId;
        if ($userId <= 0 || !ensureHiddenGroupTable()) return 0;
        $stmt = db()->prepare("SELECT id FROM fans_groups WHERE status = 'approved'");
        $stmt->execute();
        $valid = [];
        foreach ($stmt->fetchAll() as $row) {
            $valid[(int)$row['id']] = true;
        }
        $ins = db()->prepare("INSERT IGNORE INTO user_hidden_groups (user_id, group_id) VALUES (?, ?)");
        $n = 0;
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id <= 0 || !isset($valid[$id])) continue;
            $ins->execute([$userId, $id]);
            $n++;
        }
        return $n;
    }
}

if (!function_exists('showGroupIds')) {
    function showGroupIds($userId, array $ids) {
        $userId = (int)$userId;
        if ($userId <= 0 || !ensureHiddenGroupTable()) return 0;
        $del = db()->prepare("DELETE FROM user_hidden_groups WHERE user_id = ? AND group_id = ?");
        $n = 0;
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id <= 0) continue;
            $del->execute([$userId, $id]);
            $n++;
        }
        return $n;
    }
}

if (!function_exists('approvedGroupRows')) {
    function approvedGroupRows() {
        $stmt = db()->prepare("SELECT id, name, qq_group, province, city, activity_level FROM fans_groups WHERE status = 'approved' ORDER BY activity_level ASC, name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

if (!function_exists('hideGroupsByFilter')) {
    function hideGroupsByFilter($userId, array $levels, array $provinces, array $cities) {
        $userId = (int)$userId;
        if ($userId <= 0) return 0;
        $levels = array_map('intval', $levels);
        $provinces = array_map('strval', $provinces);
        $cities = array_map('strval', $cities);
        if (!$levels && !$provinces && !$cities) return 0;

        $matched = [];
        foreach (approvedGroupRows() as $g) {
            $hit = false;
            if ($levels && in_array((int)$g['activity_level'], $levels, true)) $hit = true;
            if (!$hit && $provinces && in_array((string)$g['province'], $provinces, true)) $hit = true;
            if (!$hit && $cities && in_array((string)$g['city'], $cities, true)) $hit = true;
            if ($hit) $matched[] = (int)$g['id'];
        }
        return hideGroupIds($userId, $matched);
    }
}

if (!function_exists('ltyKeysNormalize')) {
    function ltyKeysNormalize($arr) {
        $clean = [];
        foreach ((array)$arr as $item) {
            if (!is_array($item)) continue;
            $tk = trim((string)($item['tk'] ?? ''));
            if ($tk === '') continue;
            $clean[] = ['tk' => $tk, 'sk' => trim((string)($item['sk'] ?? ''))];
        }
        return $clean;
    }
}

if (!function_exists('getTiandituKeys')) {
    function getTiandituKeys() {
        $raw = getConfig('tianditu_keys', '');
        if (!empty($raw)) {
            $arr = json_decode($raw, true);
            if (is_array($arr)) {
                $clean = ltyKeysNormalize($arr);
                if (!empty($clean)) {
                    return $clean;
                }
            }
        }
        global $TIANDITU_KEYS_DEFAULT;
        if (!is_array($TIANDITU_KEYS_DEFAULT)) return [];
        return ltyKeysNormalize($TIANDITU_KEYS_DEFAULT);
    }
}

if (!function_exists('getTiandituServerKeys')) {
    function getTiandituServerKeys() {
        $raw = getConfig('tianditu_server_keys', '');
        if (!empty($raw)) {
            $arr = json_decode($raw, true);
            if (is_array($arr)) {
                $clean = ltyKeysNormalize($arr);
                if (!empty($clean)) {
                    return $clean;
                }
            }
        }
        global $TIANDITU_SERVER_KEYS_DEFAULT;
        if (!is_array($TIANDITU_SERVER_KEYS_DEFAULT)) return [];
        return array_values($TIANDITU_SERVER_KEYS_DEFAULT);
    }
}

if (!function_exists('formatNumber')) {
    function formatNumber($n) {
        if ($n >= 10000) return round($n / 10000, 1) . '万';
        return (string)$n;
    }
}

if (!function_exists('activityLevel')) {
    function activityLevel($count) {
        $count = (int)$count;
        if ($count > 1000) return 1;
        if ($count > 700) return 2;
        if ($count > 400) return 3;
        if ($count > 100) return 4;
        return 5;
    }
}

if (!function_exists('activityColor')) {
    function activityColor($level) {
        $colors = [1=>'#e74c3c',2=>'#e67e22',3=>'#f1c40f',4=>'#2ecc71',5=>'#27ae60'];
        return $colors[$level] ?? '#27ae60';
    }
}

if (!function_exists('activityLabel')) {
    function activityLabel($level) {
        $labels = [1=>'★ 最活跃',2=>'① 很活跃',3=>'② 较活跃',4=>'③ 一般',5=>'④ 相对安静'];
        return $labels[$level] ?? '未分级';
    }
}

if (!function_exists('eventTypeColor')) {
    function eventTypeColor($type) {
        $colors = ['offline'=>'#e74c3c','online'=>'#2ecc71','birthday'=>'#66ccff','other'=>'#95a5a6'];
        return $colors[$type] ?? '#95a5a6';
    }
}

if (!function_exists('eventTypeLabel')) {
    function eventTypeLabel($type) {
        $labels = ['offline'=>'线下活动','online'=>'线上活动','birthday'=>'生日会','other'=>'其他'];
        return $labels[$type] ?? '其他';
    }
}

if (!function_exists('eventStatus')) {
    function eventStatus($startDate, $endDate) {
        $today = date('Y-m-d');
        if ($startDate > $today) return 'upcoming';
        if ($endDate < $today) return 'ended';
        return 'ongoing';
    }
}

if (!function_exists('eventStatusLabel')) {
    function eventStatusLabel($status) {
        $labels = ['upcoming'=>'计划中','ongoing'=>'进行中','ended'=>'已结束'];
        return $labels[$status] ?? '未知';
    }
}

if (!function_exists('avatarUrl')) {
    function avatarUrl($avatar, $username = '', $size = 80) {
        if (!empty($avatar)) {
            if (preg_match('#^https?://#i', $avatar)) return $avatar;
            if (strpos($avatar, '/') === 0) return $avatar;
            // Pass 站存储的头像（avatars/、favicons/ 前缀）指向 Pass 域名
            if (strpos($avatar, 'avatars/') === 0 || strpos($avatar, 'favicons/') === 0) {
                return rtrim(PASS_URL, '/') . '/' . ltrim($avatar, '/');
            }
            return SITE_URL . '/' . ltrim($avatar, '/');
        }
        $first = $username ? mb_substr($username, 0, 1) : '?';
        $colors = ['#66ccff', '#ff9ec4', '#a8e6ff', '#ffcc66', '#c4a8ff'];
        $bg = $colors[abs(crc32($username ?: $first)) % count($colors)];
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '"><rect width="100%" height="100%" fill="' . $bg . '" rx="' . ($size / 2) . '"/><text x="50%" y="50%" font-family="system-ui,sans-serif" font-size="' . ($size * 0.5) . '" font-weight="700" fill="#fff" text-anchor="middle" dominant-baseline="central">' . htmlspecialchars($first) . '</text></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}

if (!function_exists('avatarImg')) {
    function avatarImg($avatar, $username = '', $size = 40, $extraClass = '') {
        return '<img src="' . e(avatarUrl($avatar, $username, $size)) . '" alt="" class="avatar-img ' . e($extraClass) . '" style="width:' . (int)$size . 'px;height:' . (int)$size . 'px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.1)">';
    }
}

if (!function_exists('sendMail')) {
    function sendMail($to, $subject, $html) {
        $host = SMTP_HOST;
        $port = SMTP_PORT;
        $user = SMTP_USER;
        $pass = SMTP_PASS;
        $fromName = SMTP_FROM_NAME;
        $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : $user;
        $helo = defined('SMTP_HELO') ? SMTP_HELO : ($_SERVER['SERVER_NAME'] ?? 'localhost');
        $secure = SMTP_SECURE;

        $transport = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host;
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);

        $fp = @stream_socket_client($transport . ':' . $port, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) return false;
        stream_set_timeout($fp, 20);

        $read = function() use ($fp) {
            $data = '';
            while (($line = fgets($fp, 1024)) !== false) {
                $data .= $line;
                if (strlen($line) >= 4 && $line[3] === ' ') break;
            }
            return $data;
        };
        $write = function($cmd) use ($fp) { fwrite($fp, $cmd . "\r\n"); };

        $read();
        $write('EHLO ' . $helo);
        $ehloResp = $read();
        if (strpos($ehloResp, '250') === false) { fclose($fp); return false; }

        $write('AUTH LOGIN'); $read();
        $write(base64_encode($user)); $read();
        $write(base64_encode($pass));
        if (strpos($read(), '235') === false) { fclose($fp); return false; }

        $write('MAIL FROM: <' . $fromEmail . '>'); $read();
        $write('RCPT TO: <' . $to . '>'); $read();
        $write('DATA');
        if (strpos($read(), '354') === false) { fclose($fp); return false; }

        $headers = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
        $headers .= "To: <{$to}>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";
        $headers .= "Date: " . date('r') . "\r\n";

        $body = chunk_split(base64_encode($html));
        $write($headers . "\r\n" . $body . '.');
        $resp = $read();
        $write('QUIT');
        fclose($fp);
        return strpos($resp, '250') !== false;
    }
}

if (!function_exists('notifyAdmin')) {
    function notifyAdmin($subject, $content) {
        $to = getConfig('notify_email', '');
        if (empty($to)) return false;
        $siteName = getConfig('site_name', SITE_NAME);
        $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body style='font-family:sans-serif;background:#f6fbff;padding:30px;'><div style='max-width:600px;margin:0 auto;background:#fff;border-radius:12px;padding:30px;'><h2 style='color:#1e3b4a;'>" . e($siteName) . "</h2><div style='color:#555;line-height:1.7;'>" . $content . "</div><p style='color:#999;font-size:13px;margin-top:20px;'>此邮件由系统自动发送，请勿回复。</p></div></body></html>";
        return sendMail($to, $subject, $html);
    }
}

if (!function_exists('themeRgbPalette')) {
    /** 由主色/强调色生成粒子调色板（rgb 数组列表） */
    function themeRgbPalette($primaryHex, $accentHex) {
        $parse = function($hex) {
            $hex = ltrim((string)$hex, '#');
            if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) return [102, 204, 255];
            return [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
        };
        $lighten = function($rgb, $amt) {
            return [
                (int)round($rgb[0] + (255 - $rgb[0]) * $amt),
                (int)round($rgb[1] + (255 - $rgb[1]) * $amt),
                (int)round($rgb[2] + (255 - $rgb[2]) * $amt),
            ];
        };
        $p = $parse($primaryHex);
        $a = $parse($accentHex);
        return [$p, $a, $lighten($p, 0.25), $lighten($a, 0.3), $lighten($p, 0.45)];
    }
}

if (!function_exists('mapParticleScript')) {
    function mapParticleScript($primaryHex = '#66ccff', $accentHex = '#4aa3e8') {
        $palette = themeRgbPalette($primaryHex, $accentHex);
        $accentRgba = sprintf('rgba(%d,%d,%d,.5)', $palette[1][0], $palette[1][1], $palette[1][2]);
        echo "<script>\n(function(){\n";
        echo '  var palette = ' . json_encode($palette) . ";\n";
        echo '  var ACCENT_GLOW = ' . json_encode($accentRgba) . ";\n";
        echo <<<'HTML'
  var MAX = 180;
  var chars = ['♪','♫','♬','♩','●','◆','✦'];
  var layer = null;
  function ensureLayer(){
    if (layer && layer.parentNode) return layer;
    layer = document.createElement('div');
    layer.style.cssText = 'position:fixed;inset:0;pointer-events:none;z-index:9999;overflow:hidden;';
    document.body.appendChild(layer);
    return layer;
  }
  var pool = [];
  var running = false;

  function spawn(x, y, count){
    ensureLayer();
    var n = count || (8 + Math.floor(Math.random()*7));
    for (var i=0;i<n;i++){
      if (pool.length >= MAX) break;
      var el = document.createElement('div');
      var c = palette[Math.floor(Math.random()*palette.length)];
      var color = 'rgb(' + c[0] + ',' + c[1] + ',' + c[2] + ')';
      var size = 12 + Math.random()*14;
      var ch = chars[Math.floor(Math.random()*chars.length)];
      el.textContent = ch;
      el.style.cssText =
        'position:absolute;left:0;top:0;font-size:' + size + 'px;color:' + color + ';' +
        'text-shadow:0 0 10px ' + color + ',0 0 20px ' + ACCENT_GLOW + ';font-weight:700;' +
        'will-change:transform;user-select:none;' +
        'transform:translate3d(' + x + 'px,' + y + 'px,0) rotate(' + (Math.random()*360) + 'deg);';
      layer.appendChild(el);
      var angle = Math.random() * Math.PI * 2;
      var speed = 1.6 + Math.random() * 3.2;
      pool.push({
        el: el, x: x, y: y,
        vx: Math.cos(angle) * speed,
        vy: Math.sin(angle) * speed - (1.5 + Math.random()*2.5),
        rot: Math.random()*360,
        vr: (Math.random()-0.5)*6,
        life: 0, ttl: 90 + Math.floor(Math.random()*60), size: size
      });
    }
    if (!running) { running = true; requestAnimationFrame(tick); }
  }

  var G = 0.55, AIR = 0.999, FLOOR_STOP = 0.42;
  function tick(){
    var w = window.innerWidth, h = window.innerHeight;
    for (var i = pool.length - 1; i >= 0; i--) {
      var p = pool[i];
      p.life++;
      p.vy += G;
      p.vx *= AIR;
      p.x += p.vx;
      p.y += p.vy;
      p.rot += p.vr;
      if (p.y > h - p.size*0.2) {
        p.y = h - p.size*0.2;
        p.vy *= -FLOOR_STOP;
        p.vx *= 0.75;
        p.vr *= 0.5;
        if (Math.abs(p.vy) < 0.4) p.vy = 0;
      }
      if (p.x < 0) { p.x = 0; p.vx = Math.abs(p.vx) * 0.6; }
      if (p.x > w) { p.x = w; p.vx = -Math.abs(p.vx) * 0.6; }
      p.el.style.transform = 'translate3d(' + p.x + 'px,' + p.y + 'px,0) rotate(' + p.rot + 'deg)';
      var alpha = p.life > p.ttl * 0.7 ? Math.max(0, 1 - (p.life - p.ttl*0.7) / (p.ttl*0.3)) : 1;
      p.el.style.opacity = alpha.toFixed(2);
      if (p.life > p.ttl || alpha <= 0) {
        if (p.el.parentNode) p.el.parentNode.removeChild(p.el);
        pool.splice(i, 1);
      }
    }
    if (pool.length > 0) requestAnimationFrame(tick);
    else running = false;
  }

  document.addEventListener('click', function(ev){
    var t = ev.target;
    if (t && t.closest && t.closest('input,textarea,select,option,label')) {
      spawn(ev.clientX, ev.clientY, 10);
      return;
    }
    spawn(ev.clientX, ev.clientY, 12 + Math.floor(Math.random()*6));
  }, { passive: true });
})();
</script>
HTML;
    }
}

if (!function_exists('pageStart')) {
    function pageStart($title) {
        $user = currentUser();
        $siteName = getConfig('site_name', SITE_NAME);
        $primary = getConfig('theme_primary', THEME_PRIMARY);
        $accent = getConfig('theme_accent', THEME_ACCENT);
        $bgStart = getConfig('theme_bg_start', THEME_BG_START);
        $bgEnd = getConfig('theme_bg_end', THEME_BG_END);
        $logo = getConfig('logo', '');
        if ($logo === '' || !is_file(__DIR__ . '/' . ltrim($logo, '/'))) {
            $logo = is_file(__DIR__ . '/core_assets/core_logo.png') ? 'core_assets/core_logo.png' : '';
        }
        $accentRgb = implode(',', themeRgbPalette($primary, $accent)[1]);
        $isMapPage = (basename($_SERVER['PHP_SELF']) === 'index.php');

        $navItems = [];
        $navItems[] = ['href' => 'index.php', 'label' => '地图'];
        $navItems[] = ['href' => 'api.php?action=groups', 'label' => '数据接口'];
        $navItems[] = ['href' => 'agreement.php', 'label' => '用户协议'];

        ?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= e($siteName) ?>">
<meta name="theme-color" content="#66ccff">
<meta name="format-detection" content="telephone=no,email=no,address=no">
<?php $favIcon = is_file(__DIR__ . '/core_assets/core_favicon.png') ? 'core_assets/core_favicon.png' : getConfig('favicon', ''); if ($favIcon !== ''): ?><?php if (preg_match('#^https?://#i', $favIcon)): ?><link rel="icon" href="<?= e($favIcon) ?>"><?php else: ?><?php $favPath = __DIR__ . '/' . ltrim($favIcon, '/'); ?><link rel="icon" href="favicon.php<?= is_file($favPath) ? '?v=' . filemtime($favPath) : '' ?>"><?php endif; ?><?php endif; ?>
<title><?= e($title) ?> - <?= e($siteName) ?></title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif}
html,body{overflow-x:hidden;-webkit-tap-highlight-color:transparent}
input,select,textarea{font-size:16px}
body{background:linear-gradient(145deg,<?= e($bgStart) ?> 0%,<?= e($bgEnd) ?> 100%);min-height:100vh;color:#1e2e38;padding-bottom:3rem;position:relative}

.header {
    background: rgba(255,255,255,.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(102,204,255,.25);
    padding: 1rem 2rem;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
    position: sticky; top: 0; z-index: 1000;
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
.header-menu .menu-item {
    display: flex; align-items: center; gap: .6rem;
    padding: .9rem 1.4rem; color: #3a5b6b; text-decoration: none;
    font-size: .92rem; border-bottom: 1px solid rgba(102,204,255,.1);
}
.header-menu .menu-item:last-child { border-bottom: none; }
.header-menu .menu-item:hover { background: rgba(102,204,255,.08); color: #1a2e3b; }
.header-menu .menu-item-gh { font-weight: 600; }
.header-menu .menu-gh-icon { width: 16px; height: 16px; flex: none; }

.container{max-width:1100px;margin:2rem auto;padding:0 1.5rem;position:relative;z-index:1}
.container-narrow{max-width:560px;margin:3rem auto;padding:0 1.5rem}
.card{background:rgba(255,255,255,.9);backdrop-filter:blur(10px);border-radius:1.6rem;padding:2rem;box-shadow:0 12px 40px -16px rgba(102,204,255,.3);margin-bottom:1.6rem;border:1px solid rgba(255,255,255,.7)}
.card h1{font-size:1.5rem;font-weight:800;color:#1a2e3b;margin-bottom:.4rem;letter-spacing:-.02em}
.card h2{font-size:1.1rem;font-weight:700;color:#1e3b4a;margin-bottom:1rem}
.card .subtitle{color:#7d98aa;font-size:.88rem;margin-bottom:1.5rem}
.field{margin-bottom:1.1rem}
.field label{display:block;font-size:.8rem;font-weight:700;color:#3a5b6b;margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.04em}
.field input,.field select,.field textarea{width:100%;padding:.8rem 1rem;border-radius:.9rem;border:1.5px solid #d8e4ee;background:#fafcfe;font-size:.95rem;color:#1e2e38;outline:none;transition:.25s;font-family:inherit}
.field textarea{min-height:100px;resize:vertical;line-height:1.6}
.field input:focus,.field select:focus,.field textarea:focus{border-color:<?= e($primary) ?>;background:#fff;box-shadow:0 0 0 4px <?= e($primary) ?>26}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;padding:.75rem 1.4rem;border:none;border-radius:.9rem;font-weight:700;font-size:.9rem;cursor:pointer;transition:.25s;text-decoration:none;letter-spacing:.01em}
.btn-primary{background:<?= e($primary) ?>;color:#fff;box-shadow:0 4px 16px -4px <?= e($primary) ?>66}
.btn-primary:hover{background:<?= e(THEME_PRIMARY_DARK) ?>;transform:translateY(-2px)}
.btn-secondary{background:#e3edf4;color:#1e3b4a}
.btn-secondary:hover{background:#d4e2ec;transform:translateY(-1px)}
.btn-danger{background:#fbe9e9;color:#a13e3e}
.btn-danger:hover{background:#f5d7d7;transform:translateY(-1px)}
.btn-outline{background:transparent;border:1.5px solid #d8e4ee;color:#3a5b6b}
.btn-outline:hover{background:#f0f6fb;border-color:<?= e($primary) ?>}
.btn-sm{padding:.4rem .8rem;font-size:.78rem;border-radius:.6rem}
.btn-block{width:100%;margin-top:.5rem}
.message{padding:.9rem 1.2rem;border-radius:1rem;font-size:.88rem;margin-bottom:1.2rem;background:rgba(102,204,255,.1);color:#1d4b63;border-left:4px solid <?= e($primary) ?>}
.message.error{background:#fef1f0;border-left-color:#d97a7a;color:#a13e3e}
.message.success{background:#eaf6ef;border-left-color:#6fae8a;color:#1f6a44}
.message.warning{background:#fff8e6;border-left-color:#f1c40f;color:#7a5c00}
.footer{text-align:center;padding:2rem 1rem;color:#7d98aa;font-size:.8rem;position:relative;z-index:1}
table{width:100%;border-collapse:collapse;font-size:.88rem}
th,td{padding:.8rem .7rem;text-align:left;border-bottom:1px solid #eef2f7}
th{color:#3a5b6b;font-weight:700;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em}
tr:hover{background:rgba(102,204,255,.05)}
.badge{display:inline-block;padding:.2rem .6rem;border-radius:20px;font-size:.7rem;font-weight:700}
.badge-pending{background:#fff8e6;color:#7a5c00}
.badge-approved{background:#eaf6ef;color:#1f6a44}
.badge-rejected{background:#fef1f0;color:#a13e3e}
.role-badge{display:inline-flex;align-items:center;vertical-align:-2px;margin-left:.25rem;line-height:0}
.role-badge svg{display:block}
.role-chip{display:inline-flex;align-items:center;gap:.25rem;padding:.12rem .5rem .12rem .3rem;border-radius:999px;font-size:.68rem;font-weight:700;line-height:1.5}
.role-chip-verified{background:rgba(29,155,240,.12);color:#0b6fb0}
.role-chip-super{background:rgba(229,72,77,.12);color:#b3242a}
.item-more{position:absolute;top:.35rem;right:.3rem;width:26px;height:26px;display:flex;align-items:center;justify-content:center;border:0;background:transparent;color:#7d98aa;border-radius:50%;cursor:pointer;font-size:1rem;line-height:1;opacity:.5}
.item-more:hover{background:rgba(102,204,255,.16);color:#1a2e3b;opacity:1}
.ctx-menu{position:fixed;z-index:1200;min-width:136px;background:#fff;border:1px solid rgba(126,177,207,.22);border-radius:.7rem;box-shadow:0 14px 34px -18px rgba(20,78,108,.55);padding:.28rem}
.ctx-menu button{display:block;width:100%;text-align:left;border:0;background:transparent;padding:.5rem .7rem;border-radius:.5rem;font-size:.82rem;color:#1e3b4a;cursor:pointer}
.ctx-menu button:hover{background:rgba(102,204,255,.13)}
.props-mask{position:fixed;inset:0;z-index:1300;background:rgba(15,35,48,.42);display:flex;align-items:center;justify-content:center;padding:1.2rem}
.props-dialog{background:#fff;border-radius:1rem;max-width:420px;width:100%;max-height:80vh;overflow:auto;box-shadow:0 24px 60px -24px rgba(20,78,108,.6);padding:1.3rem}
.props-dialog h3{margin:0 0 .8rem;font-size:1rem;color:#1a2e3b}
.props-row{display:flex;gap:.6rem;padding:.42rem 0;border-bottom:1px solid #eef2f7;font-size:.83rem;line-height:1.6}
.props-row:last-of-type{border-bottom:0}
.props-row .k{width:5.4rem;flex:none;color:#7d98aa}
.props-row .v{color:#1e3b4a;word-break:break-all;white-space:pre-wrap}
.props-actions{display:flex;justify-content:flex-end;gap:.6rem;margin-top:1rem}
.empty-tip{text-align:center;color:#7d98aa;font-size:.9rem;padding:2rem 0}

:root{--ui-ink:#173447;--ui-muted:#668094;--ui-line:rgba(126,177,207,.24);--ui-surface:rgba(255,255,255,.78);--ui-surface-strong:rgba(255,255,255,.94);--ui-primary:<?= e($primary) ?>;--ui-accent:<?= e($accent) ?>}
html{scroll-behavior:smooth}
body{background:radial-gradient(circle at 8% 5%,rgba(255,255,255,.92) 0,rgba(255,255,255,0) 28rem),radial-gradient(circle at 94% 16%,rgba(<?= $accentRgb ?>,.18) 0,rgba(<?= $accentRgb ?>,0) 25rem),linear-gradient(135deg,<?= e($bgStart) ?> 0%,<?= e($bgEnd) ?> 48%,#e8f7fb 100%);letter-spacing:.01em}
body:before,body:after{content:"";position:fixed;z-index:0;pointer-events:none;user-select:none}
body:before{right:-6vw;bottom:-8vw;font-size:38vw;line-height:.8;font-weight:800;color:rgba(102,204,255,.1);filter:blur(2px);content:"∞"}
.tianyi-deco{position:fixed;left:-1.5vw;bottom:-1vh;z-index:0;height:min(82vh,720px);aspect-ratio:440/1004;pointer-events:none;user-select:none;background:rgba(102,204,255,.1);filter:blur(2px);-webkit-mask:url('core_assets/core_tianyi.png') no-repeat center bottom/contain;mask:url('core_assets/core_tianyi.png') no-repeat center bottom/contain}
@media (max-width:720px){.tianyi-deco{height:42vh;left:-3vw;bottom:0}}
body:after{display:none}
.header{min-height:68px;padding:.7rem max(1.4rem,calc((100vw - 1320px)/2));background:rgba(255,255,255,.72);border-bottom-color:var(--ui-line);box-shadow:0 8px 28px rgba(47,106,138,.06);backdrop-filter:blur(22px) saturate(145%)}
.header .logo{font-size:1.05rem;letter-spacing:-.025em;color:var(--ui-ink)}
.header .logo:before{content:"♪";display:grid;place-items:center;width:28px;height:28px;border-radius:10px;background:linear-gradient(135deg,var(--ui-primary),var(--ui-accent));box-shadow:0 7px 16px rgba(102,204,255,.35);color:#fff;font-size:1rem}
.header .logo.has-logo-img:before{display:none}
.header .version-badge{background:rgba(102,204,255,.14);color:#267ca8;border:1px solid rgba(102,204,255,.24);padding:.18rem .5rem}
.header .nav{gap:.15rem}
.header .nav a{position:relative;padding:.55rem .75rem;color:#547083;font-size:.84rem;border:1px solid transparent}
.header .nav a:hover{background:rgba(255,255,255,.8);border-color:var(--ui-line);box-shadow:0 5px 14px rgba(39,96,126,.07);color:var(--ui-ink)}
.header-toggle{border:1px solid var(--ui-line);background:rgba(255,255,255,.7);font-size:1.2rem}
.header-menu{background:rgba(249,253,255,.95);border-color:var(--ui-line);box-shadow:0 16px 32px rgba(31,83,111,.12);backdrop-filter:blur(22px)}
.header-menu .menu-item{padding:1rem 1.35rem;color:#48677a;border-bottom-color:rgba(126,177,207,.16)}
.container,.container-narrow{position:relative;z-index:1}.container{max-width:1180px;margin:2.75rem auto}.container-narrow{max-width:540px;margin:4.5rem auto}
.card{position:relative;overflow:hidden;background:var(--ui-surface);border:1px solid rgba(255,255,255,.92);box-shadow:0 18px 48px -28px rgba(20,78,108,.38),inset 0 1px 0 rgba(255,255,255,.8);backdrop-filter:blur(22px);border-radius:1.5rem;padding:2rem}
.card:before{content:"";position:absolute;inset:0 0 auto;height:1px;background:linear-gradient(90deg,transparent,rgba(102,204,255,.5),rgba(<?= $accentRgb ?>,.35),transparent)}
.card h1{font-size:clamp(1.45rem,2.8vw,2rem);line-height:1.2;color:var(--ui-ink)}.card h2{color:var(--ui-ink);font-size:1.08rem}.card .subtitle{color:var(--ui-muted);line-height:1.7}
.field label{color:#527083;font-size:.72rem;letter-spacing:.09em}.field input,.field select,.field textarea{border-color:rgba(110,160,190,.26);background:rgba(255,255,255,.66);border-radius:.85rem;box-shadow:inset 0 1px 2px rgba(30,80,110,.025)}
.field input:hover,.field select:hover,.field textarea:hover{border-color:rgba(102,204,255,.5)}
.field input:focus,.field select:focus,.field textarea:focus{box-shadow:0 0 0 4px <?= e($primary) ?>1f,0 8px 16px -14px rgba(39,112,151,.55)}
.btn{min-height:42px;border-radius:.82rem;padding:.72rem 1.2rem;box-shadow:0 8px 18px -14px rgba(20,79,110,.62)}
.btn-primary{background:linear-gradient(135deg,<?= e($primary) ?>,<?= e($accent) ?>);box-shadow:0 10px 20px -12px rgba(79,159,210,.85)}
.btn-primary:hover{background:linear-gradient(135deg,<?= e(THEME_PRIMARY_DARK) ?>,<?= e($accent) ?>);box-shadow:0 13px 24px -13px rgba(79,159,210,.95)}
.btn-secondary{background:rgba(231,243,249,.85);color:#315d74}.btn-outline{background:rgba(255,255,255,.5);border-color:var(--ui-line)}
.message{border-left-width:3px;border-radius:.85rem;box-shadow:0 7px 16px -16px rgba(20,78,108,.45)}
table{border-collapse:separate;border-spacing:0;border:1px solid rgba(126,177,207,.17);border-radius:1rem;overflow:hidden;background:rgba(255,255,255,.38)}
th{background:rgba(237,248,252,.75);color:#527083}th,td{border-bottom-color:rgba(126,177,207,.14)}tr:last-child td{border-bottom:0}tr:hover{background:rgba(255,255,255,.62)}
.badge{padding:.25rem .62rem;letter-spacing:.02em}.footer{color:#6c899b}

@media (max-width: 720px) {
    body { padding-bottom: 2rem; }
    .header { padding: .8rem 1rem; }
    .header .logo { font-size: .95rem; }
    .header .version-badge { display: none; }
    .header .nav { display: none; }
    .header-toggle { display: block; }
    .header-menu { display: block; top: 54px; }
    .container { padding: 0 1rem; margin: 1.5rem auto; }
    .container-narrow { padding: 0 1rem; margin: 1.5rem auto; }
    .card { padding: 1.5rem 1.2rem; border-radius: 1.2rem; }
    .card h1 { font-size: 1.3rem; }
    .field input, .field select, .field textarea { font-size: 16px; }
    table { font-size: .82rem; }
    th, td { padding: .6rem .4rem; }
    .btn { padding: .7rem 1rem; font-size: .85rem; }
    .message { font-size: .85rem; padding: .8rem 1rem; }
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

.footer{color:var(--md-on-surface-variant) !important}
.badge-approved{background:var(--md-success-container) !important;color:var(--md-on-success-container) !important}
.badge-pending{background:var(--md-tertiary-container) !important;color:var(--md-tertiary) !important}
.badge-rejected{background:var(--md-error-container) !important;color:var(--md-on-error-container) !important}

</style>
</head>
<body>
<div class="tianyi-deco" aria-hidden="true"></div>
<header class="header">
<a href="index.php" class="logo<?php if (!empty($logo)) echo ' has-logo-img'; ?>">
    <?php if (!empty($logo)): ?><img src="<?= e($logo) ?>" alt="" style="width:28px;height:28px;border-radius:10px;object-fit:contain;background:#fff;box-shadow:0 7px 16px rgba(102,204,255,.35);padding:2px"><?php endif; ?>
    <?= e($siteName) ?>
    <span class="version-badge">v<?= SITE_VERSION ?></span>
</a>
<nav class="nav">
<?php foreach ($navItems as $item): ?>
    <a href="<?= e($item['href']) ?>">
        <?php if (!empty($item['avatar']) || !empty($item['username'])): ?>
            <?= avatarImg($item['avatar'] ?? '', $item['username'] ?? '', 22) ?>
        <?php endif; ?>
        <span><?= e($item['label']) ?></span>
    </a>
<?php endforeach; ?>
</nav>
<button class="header-toggle" id="headerToggle">☰</button>
</header>

<div class="header-menu" id="headerMenu">
<?php foreach ($navItems as $item): ?>
    <a href="<?= e($item['href']) ?>" class="menu-item">
        <?php if (!empty($item['avatar']) || !empty($item['username'])): ?>
            <?= avatarImg($item['avatar'] ?? '', $item['username'] ?? '', 22) ?>
        <?php endif; ?>
        <span><?= e($item['label']) ?></span>
    </a>
<?php endforeach; ?>
    <a href="feedback.php" class="menu-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="menu-gh-icon"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
        <span>反馈</span>
    </a>
    <a href="https://github.com/Tianyi-Communtiy-Team/CVIMap" target="_blank" rel="noopener" class="menu-item menu-item-gh">
        <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="menu-gh-icon"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27s1.36.09 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8z"/></svg>
        <span>GitHub 开源仓库</span>
    </a>
</div>

<script>
(function(){
    var toggle = document.getElementById('headerToggle');
    var menu = document.getElementById('headerMenu');
    if (!toggle || !menu) return;
    toggle.addEventListener('click', function(e){
        e.stopPropagation();
        menu.classList.toggle('open');
    });
    document.addEventListener('click', function(e){
        if (!menu.classList.contains('open')) return;
        if (menu.contains(e.target) || toggle.contains(e.target)) return;
        menu.classList.remove('open');
    });
})();
</script>
<?php
        if (!$isMapPage) mapParticleScript($primary, $accent);
    }
}

if (!function_exists('pageEnd')) {
    function pageEnd() {
        ?><div class="footer">© <?= date('Y') ?> <?= e(getConfig('site_name', SITE_NAME)) ?> · <?= e(SITE_URL) ?></div>
</body></html><?php
    }
}
