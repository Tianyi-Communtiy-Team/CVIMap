<div align="center">

<a href="https://map.ltycommunity.cn">
    <img src="https://cvimap.cn-nb1.rains3.com/image/icon.png" width="200" height="200" alt="CVIMap">
</a>

# Chinese VOCALOID Information Map

<a href="https://www.php.net">
    <img src="https://img.shields.io/badge/Language-PHP-66ccff?logo=php&logoColor=white" width="111" height="20" alt="Language">
</a>

<a href="https://github.com/Tianyi-Communtiy-Team/CVIMap/blob/main/LICENSE">
    <img src="https://img.shields.io/badge/License-PolyForm%20Noncommercial%201.0.0-66ccff" width="234" height="20" alt="License">
</a>


欢迎使用中文 VOCALOID 资讯地图！这是一个开源项目，源码的发布遵循 **PolyForm Noncommercial 1.0.0**。详见[LICENSE](https://github.com/Tianyi-Communtiy-Team/CVIMap/blob/main/LICENSE)

[![Star History Chart](https://api.star-history.com/chart?repos=tianyi-communtiy-team/cvimap&type=date&legend=top-left)](https://www.star-history.com/?repos=tianyi-communtiy-team%2Fcvimap&type=date&legend=top-left)

</div>

## 项目简介

这个项目的主要功能是通过信息可视化来整合粉丝群聊，以图形化和打点的形式在地图上标出，包括粉丝群和活动。

比如，我想要让某个群以打点的形式在地图上标出，我只需要往数据库里加一条记录即可。

本仓库是完整站点的**精简内核版**：保留地图与列表的全部展示能力，去掉鸣谢、赞助、个人中心、上传群聊、上传活动、审核，并且**没有账号系统**（无需登录注册，也没有后台）。群聊与活动数据从您自己的 MySQL 读取。

## 实现功能

- 天地图矢量底图与注记图层，支持多密钥轮询与失败自动切换
- 粉丝群按活跃度分色打点，省级 / 市级两种点径
- 活动按类型分色打点，自动区分进行中 / 计划中 / 已结束
- 省 / 市级行政区活跃度着色，可切换渲染行政区
- 侧栏列表与地图联动：搜索、点击飞入、弹窗高亮；侧栏项支持三点菜单与长按菜单（属性 / 反馈）
- 只读 JSON 接口，方便二次开发或自用 App 接入
- 内嵌地图页 `embed.php`：无界面纯地图，供 App WebView 或 iframe 集成
- 用户协议 / 隐私政策页 `agreement.php`（文本内置，可在 `agreement_text.php` 修改）
- 无群城市以灰色轮廓显示，与有群区域的活跃度着色区分
- **无账号、无登录、无后台**：数据来自 MySQL，纯只读展示

## 快速开始

1. 克隆仓库

   克隆仓库至您的服务端。请注意，**本项目以 PHP 为语言编写，运行需要 MySQL**。以下是一个以 **Git** 为例子的仓库克隆命令。

   ```bash
   git clone https://github.com/Tianyi-Communtiy-Team/CVIMap.git
   ```

2. 建库建表

   新建一个数据库（或者使用您已有的），导入 `schema.sql`：

   ```bash
   mysql -u 用户名 -p 库名 < schema.sql
   ```

   会创建三张表：`fans_groups`（粉丝群）、`events`（活动）、`settings`（站点配置，`k` / `v` 两列）。

   页面与接口**只读取 `status = 'approved'`** 的记录，其他状态会被忽略。

3. 获取天地图密钥

   3.1 请先前往国家地理信息公共服务平台 天地图 <https://www.tianditu.gov.cn> 注册账号。如果您已有账号，请跳转至 3.2。

   <img src="https://cvimap.cn-nb1.rains3.com/image/step1.png" width="2560" height="1271" alt="step1">
   <img src="https://cvimap.cn-nb1.rains3.com/image/step2.png" width="2560" height="1271" alt="step2">

   3.2 登录您的账号。

   <img src="https://cvimap.cn-nb1.rains3.com/image/step3.png" width="2560" height="1271" alt="step3">

   3.3 首先进行开发者认证，以便后续操作。

   <img src="https://cvimap.cn-nb1.rains3.com/image/step4.png" width="2560" height="1271" alt="step4">

   3.4 前往“我的应用”页，进行添加应用操作。

   <img src="https://cvimap.cn-nb1.rains3.com/image/step5.png" width="2560" height="1271" alt="step5">

   参数配置信息部分请按图填写。

   <img src="https://cvimap.cn-nb1.rains3.com/image/step6.png" width="2560" height="1271" alt="step6">

   3.5 复制您申请的 `tk` 和 `sk（如有）`。这里复制两个方便演示。

   <img src="https://cvimap.cn-nb1.rains3.com/image/step7.png" width="2560" height="1271" alt="step7">

   至此，天地图配置部分已全部完成。

4. 复制配置文件并填入数据库与天地图密钥

   ```bash
   cp config.example.php config.php
   ```

   编辑 `config.php`，需要改动的地方只有两组：

   | 项 | 说明 |
   |---|---|
   | `$db_host` / `$db_port` / `$db_name` / `$db_user` / `$db_pass` | 您的数据库连接信息 |
   | `$TIANDITU_KEYS_DEFAULT` | 天地图**浏览器端**密钥，把 `tk` / `sk` 换成您申请的（可填多组，会自动轮询并在失败时切换） |

   站点名、主题色、仓库地址等其余配置按需调整，不改也能正常跑。

   下图使用 GitHub 作为演示，请按照实际文件版本调整。

   <img src="https://cvimap.cn-nb1.rains3.com/image/step8.png" width="2560" height="1271" alt="step8">

5. 用任意 **PHP** 环境跑起来

   ```bash
   php -S 127.0.0.1:8080
   ```

   浏览器打开 <http://localhost:8080/index.php> 即可。生产环境把站点根目录指向本目录即可，
   **不需要任何写入权限**（内核版不会上传文件）。

## 数据说明

群聊与活动直接写进数据库。`lat` / `lng` 留 `0` 时，会自动按 `city` → `province`
从内置的中心点表（`centers.php`）兜底定位。

### `fans_groups`（粉丝群）

| 字段 | 说明 |
|---|---|
| `name` | 群名 |
| `qq_group` / `backup_qq_group` | 主群号 / 备用群号，群号唯一 |
| `province` / `city` | 省份 / 城市 |
| `level` | `province` 画大点，`city` 画小点 |
| `member_count` | 群人数，展示用 |
| `activity_level` | 1–5，分别对应 ★ / ① / ② / ③ / ④（最活跃 → 相对安静） |
| `description` | 简介 |
| `public_note` | 橙色强调的备注，留空则不显示 |
| `lat` / `lng` | 可留 `0`，会自动取中心点 |
| `status` | 必须是 `approved` 才会展示 |

### `events`（活动）

| 字段 | 说明 |
|---|---|
| `title` | 活动标题 |
| `type` | `offline`（线下活动）/ `online`（线上活动）/ `birthday`（生日会）/ `other`（其他） |
| `start_date` / `end_date` | 起止日期，`end_date` 留空则等于 `start_date` |
| `province` / `city` / `address` | 位置信息 |
| `qq_group` / `backup_qq_group` | 关联群号 |
| `description` / `public_note` | 简介 / 橙色备注 |
| `cover_file` | 封面图路径（可选） |
| `lat` / `lng` | 可留 `0`，会自动取中心点 |
| `status` | 必须是 `approved` 才会展示 |

已结束的活动不再打点，只在侧栏「已结束」分组中列出。

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

以下配置写在 `settings` 表（`k` / `v` 两列）里，缺失时回落到 `config.php` 的默认值：

| 键 | 说明 |
|---|---|
| `site_name` | 站点名称 |
| `site_url` | 留空自动推断 |
| `tianditu_keys` | 天地图浏览器端密钥数组，可多组（**优先级高于 `config.php`**） |
| `map_center` / `map_zoom` / `map_min_zoom` / `map_max_zoom` | 初始视图与缩放范围 |
| `city_zoom` | 达到该层级自动从省级边界切到市级边界 |
| `map_number` / `survey_license` | 审图号与测绘资质号，**使用天地图底图必须按您的资质如实填写** |
| `boundary_enabled` | 是否加载行政区边界数据 |

## 行政区边界数据

省 / 市边界着色需要站点根目录下存在：

- `china-provinces.geojson`
- `china-cities.geojson`

为规避版权问题，**本仓库源码、发行版不包含行政区边界文件**。缺失时地图与列表仍可正常使用，只是没有区域着色。

需要自备时请注意：

- 坐标基准必须是 **EPSG:4490（CGCS2000）**，与天地图一致。
- `properties` 至少包含 `name`（省级的 `province` / 市级的 `province` 字段用于配对，缺失时市级会退化为省级配色）
- 原站使用来源：天地图资源中心
  <https://cloudcenter.tianditu.gov.cn/administrativeDivision>
- 只需要中国范围内的省、市两级，不需要县级
- 国界与南海诸岛的画法以国家标准地图为准

## 与完整站点的区别

完整版（含账号、投稿、审核、后台、App 接口）不在本仓库。内核版刻意去掉了：

- 鸣谢页、赞助页与赞助者展示
- 个人中心 / 登录 / 注册
- 上传群聊、上传活动
- 审核与后台管理
- 隐藏群聊、App 令牌、OOBE 等需要账号的功能

如果您已经有跑着 MySQL 的完整站点，只需把本项目的 `config.php` 指向同一个库即可，
内核版会只读其中 `status = 'approved'` 的数据。


Required Notice: Copyright 2026 Tianyi Community Team。详见[NOTICE](https://github.com/Tianyi-Communtiy-Team/CVIMap/blob/main/NOTICE)。
