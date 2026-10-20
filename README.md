# Snow3

WordPress 3D 飘雪插件。2.0 以 1.2 的实际运行效果为准，还原了粒子运动和透视投影，并用原生 Canvas 重写为可读源码，不依赖 jQuery、Three.js、CDN，也不需要构建步骤。两种原始雪花纹理、鼠标视差和原有的 `snow3_*` 配置均保持不变。

## 安装或升级

运行环境：WordPress 5.8+、PHP 7.4+，以及支持 Canvas、requestAnimationFrame 和 matchMedia 事件的现代浏览器。集成测试在 WordPress 7.1.3 / PHP 8.2.34 上完成，并在 PHP 8.4.26 上通过了语法和合约测试；最低版本要求尚未经过完整的版本矩阵验证。

1. 备份 `wp-content/plugins/Snow3/` 目录和 `snow3_*` 选项。
2. 在 WordPress「插件 → 安装插件 → 上传插件」中上传 `Snow3-2.0.0.zip`，确认替换旧版，目录名保持为 `Snow3`。
3. 在后台侧边栏的「Snow3」菜单中调整参数（与 1.2 位置相同）。自动模式会在前台页面添加一个飘雪层。
4. 清理站点的页面缓存和 CDN 缓存，确认页面加载的是 `js/snow3.js?ver=2.0.0`。

**从 1.2 升级时，不要先停用或删除旧插件：旧版的停用钩子会删除全部配置。** 请使用 WordPress 的插件替换升级流程，或在备份后直接替换目录中的文件。2.0 停用时保留配置，只有在卸载时才会删除；在多站点网络中，卸载会逐个站点清理。

选项名称与旧版相同。旧的 `snow3_jquery` 选项会保留，但不再影响资源加载。雪花数量为 0 时，插件不加载 CSS/JS，也不输出飘雪层；访客开启“减少动态效果”或页面不可见时，动画会暂停。

设置页文本使用文本域 `snow3`，可通过 `languages/` 目录或翻译插件进行本地化。

## 性能优化

页面加载：飘雪层的 CSS 以内联样式输出，不产生阻塞渲染的样式表请求；脚本在页脚以 `defer` 方式加载（WordPress 6.3 起生效）；动画在页面 `load` 事件之后、主线程空闲时才创建粒子并请求纹理，不与正文争抢网络和主线程。

运行时：物理更新固定为每秒 40 次。只有粒子、摄像机或画布尺寸发生变化时才重绘，避免在高刷新率屏幕上反复绘制同一帧。粒子位置在常规情况下不做取模运算；鼠标移动时使用缓存的飘雪层边界；动画期间连续触发的 resize 事件会合并到下一帧处理，尺寸不变时不重新分配画布。粒子数量、Retina 分辨率和运动速度均未降低。

`tests/performance.html` 用于测量当前实现：固定随机种子和纹理，分别测试 500 和 2000 片雪花，在 60 Hz 模拟时间线上运行 10 秒，给出主线程耗时和含 GPU 光栅的耗时（每次绘制后读回 1 像素），各取五轮中位数。本地测试站中的地址为 `http://127.0.0.1:18083/wp-content/plugins/Snow3/tests/performance.html`。

完整结果见 [性能记录](docs/2026-10-19_performance-Snow3.md)。

## 参数

速度和重力的单位与原版一致，以每秒 40 次物理更新计算，不随显示器刷新率变化。

| 参数 | 默认值 | 支持范围 |
|---|---:|---|
| 样式 `snow3_style` | 1 | 1 圆形 / 2 不规则 |
| 加载 `snow3_load` | 0 | 0 自动 / 1 手动 |
| 水平速度 `snow3_way` | 0 | -1000 至 1000 |
| 下降速度 `snow3_speed` | 0.5 | 0 至 1000，数值越大越快 |
| 纵深速度 `snow3_front` | 0 | -1000 至 1000 |
| X 轴随机旋转 `snow3_rotateX` | 45 | -360 至 360 度 |
| Y 轴随机旋转 `snow3_rotateY` | 360 | -360 至 360 度 |
| 重力 `snow3_gravity` | 0 | -1 至 1，建议绝对值小于 0.5 |
| 数量 `snow3_num` | 500 | 0 至 2000，0 表示关闭 |

超出范围的数值在保存和读取时都会被限制到支持范围内，无法识别的数据使用默认值。在原版中会导致脚本出错的负 X 轴旋转和负重力，现在可以正常使用；一次越过多个边界周期的粒子也能正确回绕。

## 主题手动加载

先在后台选择“手动加载”，然后在主题中于 `get_header()` 之前加载样式，于 `wp_footer()` 之前输出飘雪层：

```php
<?php
if (function_exists('add_snow_style')) {
    add_snow_style();
}
get_header();
// 主题内容……
if (function_exists('snow3')) {
    snow3();
}
wp_footer();
```

旧接口 `add_snow_script()` 仍然可用。同一页面多次调用这些接口，也只会创建一个飘雪层。主题必须调用 `wp_head()` 和 `wp_footer()`；`add_snow_style()` 应在 `wp_head()` 之前调用，以免样式加载过晚。

## 本地测试

git 仓库自带独立的 Docker Compose 环境（数据库不映射主机端口，Web 只绑定 localhost）：

```sh
docker compose -p snow3-local up -d
```

打开 `http://127.0.0.1:18083/`，完成本地 WordPress 安装并启用 Snow3。如果端口被占用，请先停止已有的 Snow3 测试服务，或修改 Compose 中的端口映射。

- 交互预览：`http://127.0.0.1:18083/wp-content/plugins/Snow3/demo/`
- 浏览器回归测试：`http://127.0.0.1:18083/wp-content/plugins/Snow3/tests/browser.html`

运行回归测试并打包：

```sh
node --test tests/snow3.test.cjs
docker compose -p snow3-local exec wordpress php /var/www/html/wp-content/plugins/Snow3/tests/plugin.php manual
docker compose -p snow3-local exec wordpress php /var/www/html/wp-content/plugins/Snow3/tests/plugin.php auto
python3 scripts/package.py
```

`scripts/package.py` 只打包运行所需的 9 个文件。测试、演示、文档、脚本和 Compose 配置只保留在 git 仓库中，不会进入安装包，因此服务器上不需要额外配置访问限制。`tests/plugin.php` 只能从命令行运行，通过 HTTP 访问会返回 404。

运动与投影测试的期望值记录自 1.2 原版（提交 `d201197`），仓库中不再保存旧代码。运行时源码就是 `js/snow3.js`，可以直接编辑，不需要打包工具。

预览页也可以用任意静态 HTTP 服务打开；PHP 只负责 WordPress 集成。

## JavaScript 接口

WordPress 会把转义后的 JSON 配置写入 `.Snow[data-snow3]`。脚本在页面加载完成、主线程空闲时扫描这些容器并创建实例。自定义页面在加载 CSS 和 JS 后，也可以直接调用：

```js
const root = document.querySelector('.Snow');
const snow = Snow3.create(root, {
    num: 500, style: 2, speed: 0.5,
    texture: '/wp-content/plugins/Snow3/img/Snow2.png'
});
// 在动态移除页面内容之前，释放动画帧、事件监听和画布：
snow.destroy();
```

对同一个容器调用 `Snow3.create()` 会返回已有实例；如需修改参数，先调用 `destroy()` 再重新创建。`Snow3.scan()` 用于初始化之后才加入页面的声明式容器。纹理必须是 HTTP(S) 地址，允许跨域（例如 CDN）；图片加载失败时，会改用本地生成的圆形柔光雪花。

## 修复范围和验证边界

已修复的问题：依赖全局 `$` 和旧版 Three.js 引擎、负数参数拼接导致的语法错误、缺少参数校验、重复初始化、固定定时器、窗口缩放与高 DPI 适配、后台页面持续耗电、未遵循“减少动态效果”偏好，以及停用插件时误删设置。详见 `docs/2026-10-19_reverse-Snow3-report.md`。

不兼容 IE，也不保证与原版逐像素一致或随机序列完全相同。新渲染器会跳过贴近摄像机、投影过大的雪花，粒子越过上边界时也会回绕；正常参数范围内的运动和投影均有数值对比测试。

## 版权

原插件作者为 Jacky（Copyright 2017），原 Typecho 版 Snow 作者为清馨雅致。原始雪花图片和项目的 GPL 授权保持不变。2.0 是依据原版运行效果重新实现的版本，继续采用 GPL-2.0-or-later 授权。旧版 Three.js 已从仓库移除，也不随安装包分发。参见 [LICENSE.md](LICENSE.md)。
