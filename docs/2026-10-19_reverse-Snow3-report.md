# Snow3 1.2 行为还原与 2.0 修缮记录

日期：2026-10-19（Asia/Shanghai）。报告类型：普通逆向重建，flavor = null。

## 概述

Snow3 1.2 的 `js/three.js` 是 72,129 字节的旧版压缩 Three.js，引擎之外的粒子逻辑由 `snow3.php` 拼成内联脚本。线上三个核心文件的 SHA-256 与 GitHub `d201197` 完全一致，因此以该提交作为还原基线。2.0 以原生 Canvas 重建相同的粒子初速度、40 Hz 物理和 75° 透视投影，保留两种纹理与原设置键。差分测试和本地 WordPress 7.1.3 集成均通过；交付的运行时没有压缩引擎、jQuery、CDN 或构建工具依赖。生产环境本轮仅观察，没有部署。

## 观察与还原方法

先使用 REA `analyze_javascript_application` 静态分析实际 `js/three.js`，再从 PHP 生成的粒子代码追踪运动链路，并将新实现与旧引擎放入独立 Node VM 作数值对比。REA 共解析 1 个 JS 文件、28,588 个 AST 节点，没有解析失败。保留的父 Evidence ID 为 `ev_24a4f3557e41f4d2cc493c695d571147d0c779273d984d5cbb0a25acccedd204`。

REA 报告关系图为 partial、semantic 为 unknown，包含 6,313 个静态语义 unknown；这不证明全部旧引擎行为已覆盖。本任务没有声称恢复原始变量名、源代码工程或源映射，只重建并验证飘雪所用路径。原版代码可从 git 历史 `d201197` 取回；测试只保留由它记录的期望数值。

### 原运动模型

1. 粒子位置初始化于 `[-1000, 1000)` 的三维立方体。
2. 初始速度为 `(way, -speed, front)`，依次绕 X、Y 轴随机旋转；角度以度输入。
3. 每秒 40 次：`vy -= gravity`，位置叠加速度；旧版只在部分边界加减一次 2000。
4. 摄像机初始位置 `(0,0,1000)`，视角 75°，朝向原点；每步以 `0.005` 的比例追踪鼠标偏移。
5. Canvas 中投影精灵，深度越近，纹理显示越大。

新实现保留上述正常参数语义。改为 requestAnimationFrame 调度和固定 25ms 物理步长；延迟追赶最多 100ms，隐藏时不追赶。粒子任意方向越界完整回绕，极近且大于两倍视口的精灵跳过绘制。原版深度排序被省略；两种原始纹理均为白色透明精灵，正常视觉维持一致，未声明逐像素一致。

## Evidence

### E-001 — 实际旧引擎

- title: Snow3 1.2 编译 JS 静态分析。
- observed_at: 2026-10-19。
- source_type: file。
- source_ref: GitHub `d201197` 的 `js/three.js`；REA 父 Evidence 见上。
- content_hash: `ff78c7548afc8c91958799cb4459da51594ad6d702dc81fe2b67ec966cbaa7dd`。
- artifact_path: git 历史 `d201197:js/three.js`（快照已从源码中移除）。
- repro_command: `git show d201197:js/three.js | shasum -a 256`；`node --test tests/snow3.test.cjs`（期望值由该版本记录）。
- raw_excerpt: `THREE.PerspectiveCamera`、`THREE.Projector`、`THREE.CanvasRenderer`。
- linked_workitem: n/a。
- supersedes: none。

### E-002 — PHP 拼接逻辑

- title: 原版粒子模型、依赖与停用钩子。
- observed_at: 2026-10-19。
- source_type: file。
- source_ref: GitHub `d201197` 的 `snow3.php`。
- content_hash: `4faef22aa06876c6b45ef55fbb31d17a99439f1f29562db7763ddbbc6fba18cb`。
- artifact_path: git 历史 `d201197:snow3.php`（快照已从源码中移除）。
- repro_command: `git show d201197:snow3.php | shasum -a 256`；`node --test tests/snow3.test.cjs`（期望值由该版本记录）。
- raw_excerpt: `register_deactivation_hook(__FILE__, 'snow3_remove')`；`$(function(){...})`；`setInterval(loop,1000/40)`（原文件含空格差异）。
- linked_workitem: n/a。
- supersedes: none。

### E-003 — 线上基线，只读

- title: 线上站点的插件、WordPress、PHP 和选项。
- observed_at: 2026-10-19。
- source_type: command。
- source_ref: 在线上 WordPress 站点根目录执行的只读命令。
- content_hash: n/a。
- artifact_path: n/a。
- repro_command: 在站点根目录运行 `sha256sum wp-content/plugins/Snow3/snow3.php wp-content/plugins/Snow3/js/three.js wp-content/plugins/Snow3/css/Snow.css`；`wp core version --allow-root`；`wp plugin get Snow3 --fields=name,status,version --format=json --allow-root`；`wp option list --search='snow3_*' --fields=option_name,option_value --format=json --allow-root`。需要服务器访问权限，离线无法重查。
- raw_excerpt: WordPress 7.1.3；PHP CLI 8.4.26；Snow3 1.2 active；Cosy-5.2；style=2、num=500，其他值为原默认值。PHP/JS 哈希与 E-001/E-002 相同，CSS 为 `e26f18e7f065ad718287e9418e2d451ba1ba19daab839e4b837d20cefeca9f2a`。
- linked_workitem: n/a。
- supersedes: none。

### E-004 — 新旧行为与 PHP 合约测试

- title: 差分和插件回归测试。
- observed_at: 2026-10-19。
- source_type: command。
- source_ref: `tests/snow3.test.cjs`、`tests/plugin.php`。
- content_hash: n/a。
- artifact_path: n/a。
- repro_command: `node --test tests/snow3.test.cjs`；PHP 容器执行 `php tests/plugin.php manual`、`php tests/plugin.php auto`。
- raw_excerpt: Node 7 tests passed；manual 270 checks；auto 269 checks。PHP 8.2.34 与 8.4.26 的语法检查、manual 270 和 auto 269 项检查均通过。
- linked_workitem: n/a。
- supersedes: none。

### E-005 — 真实浏览器和 WordPress 集成

- title: 本地实际渲染与设置持久化。
- observed_at: 2026-10-19。
- source_type: screenshot / command。
- source_ref: IAB访问 localhost:18083；`tests/browser.html`、WordPress 设置页与前台 PHP。
- content_hash: n/a。
- artifact_path: n/a。
- repro_command: `docker compose -p snow3-local up -d` 后完成本地安装、启用插件，访问 `/wp-content/plugins/Snow3/tests/browser.html`。后台保存 style=1、speed=3、rotateX=-30、gravity=-0.02、num=240，再重新加载和读取选项；停用、启用后核对 num 仍为 240；测试后恢复本地 style=2、num=500 和其他默认参数。
- raw_excerpt: 9 browser checks passed；实际 Canvas 存在非透明像素；无 jQuery/THREE/$；首次减少动态效果不调度帧，切换可恢复；640×360 视口变为 390×844 后画布更新；重复扫描保持一个实例；destroy 移除画布。真实 Settings API POST、停用/启用保留设置、手动模式阻止自动输出、旧函数多次调用只输出一次，均 PASS。
- linked_workitem: n/a。
- supersedes: none。

## Findings

| ID | title / location | severity / category / status | evidence_ids | impact / confidence | remediation |
|---|---|---|---|---|---|
| F-001 | 旧运动和投影模型，原 `js/three.js` 与 PHP Particle3D | n/a_re / reverse_algo / validated | E-001, E-002, E-004 | 正常范围的速度、旋转和纹理投影已数值核对；high | `js/snow3.js` 可读重建 |
| F-002 | 全局 `$`、固定定时器、缺少 resize/清理，原 `add_snow_script()` | low / design / validated | E-002, E-004, E-005 | 无全局 `$` 的页面会失败；旧后台页面继续调度。未声称线上当前 `$` 一定缺失；high | 无依赖原生 Canvas、requestAnimationFrame、可见性/动态效果事件和 destroy |
| F-003 | 负 X 旋转/负重力拼成 `--30` / `--0.1`，原 `add_snow_script()` | low / other / validated | E-002, E-004, E-005 | 合法负参数导致整个动画语法错误；high | 转义 JSON 配置与普通数值运算 |
| F-004 | 停用即删除设置，原停用钩子 | medium / design / validated | E-002, E-004, E-005 | 升级前停用旧版可能丢失配置；high | 2.0 只在 `uninstall.php` 显式卸载时删除 |
| F-005 | 设置缺少范围与有限数验证，原 `register_snow3_settings()` | low / design / validated | E-002, E-004 | 异常参数可导致语法失败、过量粒子和越界异常；high | 读取/保存双重校验，粒子 0..2000，数值范围见 README |

复现 F-001/F-003 用 Node 差分测试；F-002/F-004/F-005 用 PHP 测试和浏览器页面。F-004 的旧版停用删除结论来自已确认的 PHP 钩子和直接代码，并未在生产执行破坏性复现。

## Path P-001 — 配置到画布的调用链

- path_type: callflow。
- start: WordPress 中保留的 `snow3_*` 选项。
- goal: 单个无依赖、可释放的 3D 雪层。
- steps:
  1. Settings API 保存并校验配置；读取时再次校验 — evidence: E-002, E-004, E-005 — finding: F-005。
  2. 自动模式经 enqueue/footer，手动模式经旧主题函数生成转义 JSON 容器 — evidence: E-004, E-005 — finding: F-002, F-003。
  3. `Snow3.scan()` / `create()` 生成粒子和投影，固定 40 Hz 更新 — evidence: E-001, E-002, E-004 — finding: F-001。
  4. resize 更新画布，可见性/动态偏好暂停，destroy 释放事件与画布 — evidence: E-004, E-005 — finding: F-002。
- residual_risks: 极端参数行为和近裁剪面有意修正；没有全版本浏览器矩阵，也未做线上 Cosy 主题回归。

## 交付和操作边界

`Snow3-2.0.0.zip` 仅含 PHP、CSS、可读 JS、两张原纹理、README 和许可证。演示、测试、Compose 和本报告只保留在 git 仓库，不打包。`SHA256SUMS` 可核对安装包。

从 1.2 升级请先备份插件目录与选项，使用原地替换升级，避免先触发旧版停用钩子。新版本激活后，刷新页面并检查请求变为 `js/snow3.js?ver=2.0.0`；清理站点缓存。升级/回滚具体生产操作本轮未执行。

当前独立测试服务为容器 `snow3-test-wp`、`snow3-test-db`，仅 Web 绑定 `127.0.0.1:18083`。本地测试账号在安装本地站点时自行创建，不随仓库提供。可通过 `docker stop snow3-test-wp snow3-test-db` 停止服务；容器保留测试数据库供恢复。源码 Compose 使用另一项目名称，勿与当前服务同时占用 18083。

## 时间线与验证边界

2026-10-19：读取仓库与线上基线 → REA 解析压缩 JS → 还原运动/投影与独立测试站 → 实现 PHP 和 Canvas → 复现负参数错误并回归 → WordPress 后台保存与生命周期验证 → 浏览器 9 项检查 → 打包交付。

实际环境为 WordPress 7.1.3、PHP 8.2.34、本地 MariaDB 11.4、Node v26 和 IAB。另使用官方 `php:8.4-cli-alpine`（实际 8.4.26，与线上版本相同）在断网、只读容器运行语法与 PHP 合约测试，全部通过；完整 WordPress HTTP 集成仍使用 PHP 8.2.34。插件声明最低 WP 5.8 / PHP 7.4，未覆盖最低版本矩阵；未修改线上文件或数据，未提交/推送仓库。

## 当前 API 依据

Context7 查询官方 WordPress Functions 文档，使用 `register_setting` 的 sanitize_callback、独立 admin_init、`add_menu_page` 的 manage_options（顶级菜单，避免设置页“已保存”提示跳动）、`uninstall.php` 和 `esc_attr(wp_json_encode())` 属性编码。

- https://developer.wordpress.org/reference/functions/register_setting/
- https://developer.wordpress.org/reference/functions/add_menu_page/
- https://developer.wordpress.org/reference/functions/register_uninstall_hook/
- https://developer.wordpress.org/reference/functions/esc_js/

完成自检：范围已记录；样本哈希已核对；所有 Finding 均引用 Evidence；调用链带证据；差分、PHP 与浏览器测试通过；报告未套用恶意软件模板；生产保持只读。
