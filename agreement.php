<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/agreement_text.php';

$type = (($_GET['type'] ?? '') === 'privacy') ? 'privacy' : 'terms';
$text = agreementDefaultText($type);
$paras = preg_split('/\n\s*\n/', trim($text));
$site = cfg('site_name', '中V资讯地图');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(agreementTitle($type)) ?> - <?= e($site) ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:system-ui,-apple-system,'Segoe UI','Microsoft YaHei',sans-serif}
body{background:linear-gradient(145deg,#f6fbff 0%,#eaf4fb 100%);min-height:100vh;color:#1e2e38;padding:2rem 1rem}
.wrap{max-width:760px;margin:0 auto}
.card{background:rgba(255,255,255,.92);border:1px solid rgba(126,177,207,.24);border-radius:16px;padding:1.6rem;box-shadow:0 16px 32px -24px rgba(20,78,108,.4)}
h1{font-size:1.25rem;margin-bottom:1rem;color:#173447}
.tabs{display:flex;gap:.5rem;margin-bottom:1rem}
.tabs a{flex:1;text-align:center;padding:.5rem .8rem;border-radius:.6rem;font-size:.85rem;font-weight:600;text-decoration:none;background:#eef6fb;color:#4a6fa5}
.tabs a.on{background:#4aa3e8;color:#fff}
p{margin:0 0 .85rem;line-height:1.95;font-size:.88rem;color:#3a5b6b;white-space:pre-wrap}
p:first-of-type{color:#7d98aa;font-size:.8rem}
.back{display:inline-block;margin-top:1rem;font-size:.85rem;color:#4a6fa5;text-decoration:none}
</style>
</head>
<body>
<div class="wrap"><div class="card">
<h1><?= e($site) ?></h1>
<div class="tabs">
<a href="agreement.php?type=terms" class="<?= $type === 'terms' ? 'on' : '' ?>">用户协议</a>
<a href="agreement.php?type=privacy" class="<?= $type === 'privacy' ? 'on' : '' ?>">隐私政策</a>
</div>
<?php foreach ($paras as $p): ?>
<p><?= nl2br(e(trim($p))) ?></p>
<?php endforeach; ?>
<a class="back" href="index.php">返回地图</a>
</div></div>
</body>
</html>
