# Snow3

WordPress 3D 飘雪插件。原生 Canvas 绘制，不依赖 jQuery 或其他库，也不需要构建步骤。

在线演示：https://jackyu.cn/projects/snow3-2/

## 安装

需要 WordPress 5.8+ 和 PHP 7.4+。

1. 从 [Releases](https://github.com/0xJacky/Snow3/releases/latest) 下载 `Snow3-x.y.z.zip`。
2. 在后台「插件 → 安装插件 → 上传插件」上传并启用。
3. 在侧边栏「Snow3」菜单中调整参数。

停用插件会保留设置；删除插件时清除全部设置（多站点网络会逐个站点清理）。

## 设置

| 参数 | 选项名 | 默认值 | 范围 |
|---|---|---:|---|
| 加载方式 | `snow3_load` | 0 | 0 自动 / 1 手动 |
| 雪花样式 | `snow3_style` | 1 | 1 圆形 / 2 不规则 |
| 水平速度 | `snow3_way` | 0 | -1000 至 1000，正值向右 |
| 下降速度 | `snow3_speed` | 0.5 | 0 至 1000 |
| 纵深速度 | `snow3_front` | 0 | -1000 至 1000，正值朝向屏幕 |
| X 轴随机旋转 | `snow3_rotateX` | 45 | -360 至 360 度 |
| Y 轴随机旋转 | `snow3_rotateY` | 360 | -360 至 360 度 |
| 重力 | `snow3_gravity` | 0 | -1 至 1，正值向下 |
| 雪花数量 | `snow3_num` | 500 | 0 至 2000，0 为关闭 |

速度和重力按每秒 40 次物理更新计算，与屏幕刷新率无关。超出范围的值会被限制到范围内，无效值使用默认值。

访客开启“减少动态效果”或页面处于后台时，动画自动暂停。

## 主题手动加载

在设置中选择“手动加载”后，由主题决定在哪些页面显示：

```php
<?php
if (function_exists('add_snow_style')) {
    add_snow_style();   // 在 get_header() 之前
}
get_header();
// 页面内容……
if (function_exists('snow3')) {
    snow3();            // 在 wp_footer() 之前
}
wp_footer();
```

主题必须调用 `wp_head()` 和 `wp_footer()`。同一页面重复调用只会创建一个飘雪层。

## JavaScript 接口

插件把配置以 JSON 写入 `.Snow[data-snow3]` 容器，页面加载完成、主线程空闲后自动创建动画。也可以手动控制：

```js
const root = document.querySelector('.Snow');
const snow = Snow3.create(root, {
    num: 500, style: 2, speed: 0.5,
    texture: '/wp-content/plugins/Snow3/img/Snow2.png'
});

snow.destroy();   // 移除画布、动画帧和事件监听
```

- 对同一个容器重复调用 `Snow3.create()` 返回已有实例；修改参数需先 `destroy()` 再创建。
- `Snow3.scan()` 初始化之后动态加入页面的 `.Snow[data-snow3]` 容器。
- `texture` 可以是任意 HTTP(S) 地址（包括 CDN）；加载失败时使用内置的圆形雪花。

## 性能

- CSS 内联输出，脚本以 `defer` 加载，动画在页面加载完成后才启动。
- 雪花数量为 0 时不加载任何资源。
- 只在物理状态变化时重绘（每秒 40 次），高刷新率屏幕不会重复绘制。
- 窗口尺寸变化按帧合并处理；页面不可见时完全停止。

## 开发

```sh
docker compose -p snow3-local up -d                # 本地 WordPress：http://127.0.0.1:18083/
node --test tests/snow3.test.cjs                     # JavaScript 测试
docker compose -p snow3-local exec wordpress php /var/www/html/wp-content/plugins/Snow3/tests/plugin.php manual
docker compose -p snow3-local exec wordpress php /var/www/html/wp-content/plugins/Snow3/tests/plugin.php auto
python3 scripts/package.py                           # 生成安装包
```

本地站点启用插件后，可以打开：

- 参数预览：`/wp-content/plugins/Snow3/demo/`
- 浏览器测试：`/wp-content/plugins/Snow3/tests/browser.html`
- 性能基准：`/wp-content/plugins/Snow3/tests/performance.html`

安装包只包含运行所需的文件，测试、演示和开发脚本不会被打包。

### 发布

同步修改 `snow3.php` 头部的 `Version`、`SNOW3_VERSION` 和 `js/snow3.js` 中的 `version`，提交后推送同名 tag：

```sh
git tag -a 2.0.1 -m "Snow3 2.0.1" && git push origin 2.0.1
```

CI 会运行测试、校验版本号，然后发布带安装包的 GitHub Release。

## 许可

GPL-2.0-or-later，见 [LICENSE.md](LICENSE.md)。飘雪效果最初移植自 Typecho 插件 Snow（作者：清馨雅致）。
