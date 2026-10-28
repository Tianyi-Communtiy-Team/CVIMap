# CVIMap

中文 VOCALOID 资讯地图的**精简内核**：一张天地图底图 + 粉丝群 / 活动数据，仅此而已。

完整站点（账号体系、投稿审核、后台管理、App 接口等）不在此仓库，这里只保留「打开就能看地图」所需的最小实现。

## 特性

- 天地图矢量底图与注记图层，支持多密钥轮询与失败自动切换
- 粉丝群按活跃度分色打点，省级 / 市级两种点径
- 活动按类型分色打点，自动区分进行中 / 计划中 / 已结束
- 省 / 市级行政区着色（按该区域最活跃群的活跃度），可切换只看省级 / 只看市级
- 侧栏列表与地图联动：搜索、点击飞入、弹窗高亮
- 只读 JSON 接口，方便二次开发或自用 App 接入
- **无数据库、无账号、无后台**：数据就是两个 JSON 文件

## 快速开始

1. 复制配置文件并填入天地图密钥

   ```bash
   cp config.example.php config.php
   ```

   编辑 `config.php`，把 `tianditu_keys` 里的 `tk` / `sk` 换成你在
   [天地图控制台](https://console.tianditu.gov.cn/api/key) 申请的**浏览器端**密钥。

2. 放上数据

   编辑 `data/groups.json` 与 `data/events.json`（格式见下）。
   仓库自带几条示例数据，可直接打开看效果。

3. 用任意 PHP 环境跑起来

   ```bash
   php -S 127.0.0.1:8080
   ```

   浏览器打开 <http://127.0.0.1:8080/index.php> 即可。生产环境把站点根目录指向本目录，
   保证 `data/` 可读即可，无需写入权限。

> 换句话说：**提供群列表（含活动）+ 天地图 tk，就能访问。**

## 数据格式

### `data/groups.json`

```json
[
  {
    "id": 1,
    "name": "河南洛天依粉丝群",
    "qq_group": "100000001",
    "backup_qq_group": "",
    "province": "河南省",
    "city": "郑州市",
    "level": "city",
    "activity_level": 1,
    "member_count": 320,
    "description": "河南本地粉丝交流",
    "public_note": "",
    "lat": 34.7466,
    "lng": 113.6254
  }
]
```

| 字段 | 说明 |
|---|---|
| `activity_level` | 1–5，分别对应 ★ / ① / ② / ③ / ④（最活跃 → 相对安静） |
| `level` | `province` 画大点，`city` 画小点 |
| `lat` / `lng` | 可留 `0`，会自动按 `city` → `province` 从 `data/centers.json` 取中心点 |
| `public_note` | 橙色强调的备注，留空则不显示 |

### `data/events.json`

```json
[
  {
    "id": 1,
    "title": "洛天依生日会 · 郑州站",
    "type": "birthday",
    "start_date": "2026-07-12",
    "end_date": "2026-07-12",
    "province": "河南省",
    "city": "郑州市",
    "address": "郑州市金水区某展演中心",
    "qq_group": "100000001",
    "description": "现场合唱与周边交换",
    "public_note": "",
    "lat": 34.7466,
    "lng": 113.6254
  }
]
```

`type` 取值：`offline`（线下活动）/ `online`（线上活动）/ `birthday`（生日会）/ `other`（其他）。
`end_date` 留空则等于 `start_date`。已结束的活动不再打点，只在侧栏「已结束」分组中列出。

### `data/centers.json`

省 / 市中心点表，仅在 `lat` / `lng` 缺失时用于兜底定位。一般不需要改动。

## 接口

`api.php` 是只读接口，返回统一结构 `{ ok, action, data }`：

| 地址 | 说明 |
|---|---|
| `api.php?action=groups` | 群列表 |
| `api.php?action=events` | 活动列表（含 `status` / `status_label` / `type_label`） |
| `api.php?action=meta` | 站点名、版本、群与活动数量、更新时间 |
| `api.php?action=all` | 以上全部 |

页面里还挂了 `window.CVIMap`（`getGroups` / `getEvents` / `focusGroupById` / `focusEventById` / `flyTo` / `getCenter`），供内嵌 WebView 调用。

## 配置

| 键 | 说明 |
|---|---|
| `site_name` | 站点名称 |
| `site_url` | 留空自动推断 |
| `tianditu_keys` | 天地图浏览器端密钥数组，可多组 |
| `map_center` / `map_zoom` / `map_min_zoom` / `map_max_zoom` | 初始视图与缩放范围 |
| `city_zoom` | 达到该层级自动从省级边界切到市级边界 |
| `map_number` / `survey_license` | 审图号与测绘资质号，**使用天地图底图必须按你的资质如实填写** |
| `repo_url` | 菜单里的源码仓库地址 |
| `boundary_enabled` | 是否加载行政区边界数据 |

## 行政区边界数据

省 / 市边界着色需要站点根目录下存在：

- `china-provinces.geojson`
- `china-cities.geojson`

**本仓库不包含这两个文件**，缺失时地图、群列表、活动、搜索全部照常工作，只是没有区域着色。

需要自备时请注意：

- 坐标基准必须是 **EPSG:4490（CGCS2000）**，与天地图一致。阿里云 DataV.GeoAtlas 下发的是 GCJ-02，直接叠加会偏移数百米
- `properties` 至少包含 `name`（省级的 `province` / 市级的 `province` 字段用于配对，缺失时市级会退化为省级配色）
- 推荐来源：天地图服务中心「数据资源 → 行政区划可视化」
  <https://cloudcenter.tianditu.gov.cn/administrativeDivision>
  （登录 → 地区选中国 → 下载区域选省 / 市 → 格式 GeoJSON）
- 只需要中国范围内的省、市两级，不需要县级
- 国界与南海诸岛的画法以国家标准地图为准

## 从现有站点迁移

如果你已经有跑着 MySQL 的完整站点，用导出脚本一键生成 JSON：

```bash
CVI_DB_HOST=localhost CVI_DB_NAME=你的库名 CVI_DB_USER=用户名 CVI_DB_PASS=密码 \
  php tools/export_from_mysql.php
```

脚本只读取 `status = 'approved'` 的 `fans_groups` 与 `events`，导出到 `data/` 下。

## 许可

[PolyForm Noncommercial 1.0.0](LICENSE) — 允许非商业性使用、修改与分发，禁止用于商业目的。
Required Notice: Copyright 2026 Tianyi Community Team。详见 [NOTICE](NOTICE)。
