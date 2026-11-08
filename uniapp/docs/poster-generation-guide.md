# 海报生成功能使用指南

## 📋 概述

全新的海报生成功能已经完全重写，采用简洁可靠的Canvas绘制方案，确保在微信小程序中稳定运行。

## 🚀 功能特点

- ✅ **简洁可靠**: 使用原生Canvas API，避免复杂封装
- ✅ **样式还原**: 完美还原预览卡片的视觉效果
- ✅ **错误处理**: 完善的错误处理和用户提示
- ✅ **易于维护**: 代码结构清晰，便于后续维护

## 📁 文件结构

```
utils/
└── poster-capture.js          # 海报生成核心文件

pages/preview/
└── preview.vue               # 预览页面（已集成海报功能）
```

## 🔧 核心API

### `generateCampusPoster(posterData, options)`

生成校园墙海报的主要函数。

**参数:**
- `posterData` (Object): 海报数据
  - `time` (string): 发布时间
  - `tag` (string): 标签
  - `title` (string): 标题
  - `content` (string): 内容
  - `backgroundColor` (string): 背景色
  - `imageCount` (number): 图片数量
- `options` (Object): 配置选项
  - `width` (number): 画布宽度，默认750
  - `height` (number): 画布高度，默认1000
  - `quality` (number): 图片质量，默认0.9
  - `fileType` (string): 文件类型，默认'jpg'

**返回值:**
- `Promise<string>`: 临时文件路径

### `saveImageToAlbum(filePath)`

保存图片到相册。

**参数:**
- `filePath` (string): 图片文件路径

### `previewPoster(filePath)`

预览海报图片。

**参数:**
- `filePath` (string): 图片文件路径

## 💡 使用示例

### 在预览页面中使用

```javascript
// 导入海报生成功能
import { generateCampusPoster, saveImageToAlbum, previewPoster } from "../../utils/poster-capture";

// 生成海报
async generatePoster() {
  try {
    uni.showLoading({ title: '生成海报中...' });

    // 构建海报数据
    const posterData = {
      time: this.currentTime,
      tag: this.postData.tag,
      title: this.postData.title,
      content: this.postData.content,
      backgroundColor: this.selectedBgColor,
      imageCount: this.postData.images ? this.postData.images.length : 0
    };

    // 生成海报
    const tempFilePath = await generateCampusPoster(posterData);

    // 预览海报
    previewPoster(tempFilePath);

  } catch (error) {
    uni.showToast({
      title: error.message || '生成海报失败',
      icon: 'none'
    });
  } finally {
    uni.hideLoading();
  }
}
```

### 在其他页面中使用

```javascript
import { generateCampusPoster } from "@/utils/poster-capture";

// 自定义海报数据
const posterData = {
  time: '2025-05-24 17:22:04',
  tag: '求助',
  title: '问问',
  content: '水电费方式',
  backgroundColor: '#e3f1fc',
  imageCount: 1
};

// 生成海报
const posterPath = await generateCampusPoster(posterData);
```

## 🎨 海报样式

海报包含以下元素：

1. **背景色**: 可自定义的纯色背景
2. **时间**: 右上角显示发布时间
3. **标签**: 圆角矩形背景的标签（如果有）
4. **标题**: 粗体显示的标题文字
5. **内容**: 正文内容，支持自动换行
6. **图片占位符**: 显示图片数量的占位框

## 🔧 Canvas配置

海报使用隐藏的Canvas元素进行绘制：

```html
<!-- 在页面模板中添加 -->
<canvas
  type="2d"
  id="posterCanvas"
  class="poster-canvas"
></canvas>
```

```css
/* 对应的CSS样式 */
.poster-canvas {
  position: fixed !important;
  left: -9999rpx !important;
  top: 0 !important;
  z-index: -1 !important;
  width: 750rpx !important;
  height: 1000rpx !important;
  background: transparent !important;
  visibility: hidden !important;
}
```

## ⚠️ 注意事项

1. **Canvas ID**: 确保页面中有id为`posterCanvas`的Canvas元素
2. **权限**: 保存到相册需要用户授权
3. **性能**: 海报生成是异步操作，需要适当的加载提示
4. **兼容性**: 仅支持微信小程序环境

## 🐛 常见问题

### Q: Canvas节点获取失败？
A: 确保页面中存在正确的Canvas元素，并且在DOM渲染完成后调用生成函数。

### Q: 生成的海报是空白的？
A: 检查传入的posterData是否包含有效数据，特别是title和content字段。

### Q: 保存到相册失败？
A: 检查是否已获得相册访问权限，可以引导用户到设置页面开启权限。

## 🔄 更新日志

### v2.0.1 (2025-05-24)
- 修复Canvas图片加载问题
- 时间显示改为居中对齐
- 图片区域支持100%宽度和居中对齐
- 暂时使用占位符显示图片，确保基本功能稳定

### v2.0.0 (2025-05-24)
- 完全重写海报生成功能
- 移除复杂的封装，使用原生Canvas API
- 优化错误处理和用户体验
- 简化代码结构，提高可维护性

## 🚧 已知问题

1. **图片显示**: 当前版本暂时使用占位符显示图片，实际图片加载功能正在优化中
2. **Canvas兼容性**: 不同版本的微信小程序对Canvas 2D API支持略有差异

## 📞 技术支持

如有问题，请：
1. 查看浏览器控制台日志
2. 检查微信开发者工具的调试信息
3. 联系开发团队获取技术支持

---

**注意**: 当前版本专注于稳定性，图片加载功能将在后续版本中完善。
