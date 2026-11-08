<template>
  <view class="poster-generator">
    <!-- 内容区域 -->
    <view class="content-wrapper" id="posterContent" ref="posterContent">
      <slot></slot>
    </view>

    <!-- 操作按钮 -->
    <view class="action-buttons">
      <button class="action-btn primary" @click="generatePoster">生成海报</button>
      <button class="action-btn" @click="savePoster">保存到相册</button>
    </view>

    <!-- 隐藏的画布 -->
    <canvas type="2d" id="posterCanvas" class="poster-canvas"></canvas>
  </view>
</template>

<script>
export default {
  name: 'PosterGenerator',
  props: {
    // 配置项
    options: {
      type: Object,
      default: () => ({
        width: 750, // 画布宽度
        height: 1334, // 画布高度
        scale: 3, // 缩放比例，越大越清晰
        backgroundColor: '#ffffff', // 背景颜色
        usePadding: true, // 是否使用内边距
        padding: 20, // 内边距大小
      })
    }
  },

  data() {
    return {
      ctx: null,
      posterUrl: '',
      contentSize: {
        width: 0,
        height: 0
      }
    }
  },

  mounted() {
    this.initCanvas()
  },

  methods: {
    // 初始化画布
    initCanvas() {
      const query = uni.createSelectorQuery().in(this)
      query.select('#posterCanvas')
        .fields({ node: true, size: true })
        .exec(res => {
          console.log('Canvas查询结果:', res);
          if (res && res[0] && res[0].node) {
            const canvas = res[0].node;
            this.ctx = canvas.getContext('2d');
            console.log('Canvas 2D 上下文创建成功');
          } else {
            console.error('Canvas节点获取失败:', res);
          }
        })
    },

    // 获取元素位置和大小信息
    async getNodeInfo(selector) {
      return new Promise((resolve) => {
        const query = uni.createSelectorQuery().in(this)
        query.select(selector)
          .fields({
            rect: true,
            size: true,
            scrollOffset: true,
            properties: ['src', 'style'],
            computedStyle: ['fontSize', 'color', 'backgroundColor', 'padding', 'margin', 'lineHeight', 'fontWeight']
          })
          .exec((res) => {
            resolve(res[0])
          })
      })
    },

    // 递归获取所有子节点
    async getAllNodes(parentSelector) {
      return new Promise((resolve) => {
        const query = uni.createSelectorQuery().in(this)
        query.selectAll(`${parentSelector}, ${parentSelector} >>> *`)
          .fields({
            rect: true,
            size: true,
            scrollOffset: true,
            node: true,
            properties: ['src', 'style'],
            computedStyle: ['fontSize', 'color', 'backgroundColor', 'padding', 'margin', 'lineHeight', 'fontWeight']
          })
          .exec((res) => {
            resolve(res[0] || [])
          })
      })
    },

    // 绘制节点
    async drawNode(node, baseX = 0, baseY = 0) {
      if (!node) return

      const { left, top, width, height } = node
      const x = baseX + left
      const y = baseY + top

      // 绘制背景色
      if (node.backgroundColor && node.backgroundColor !== 'transparent') {
        this.ctx.fillStyle = node.backgroundColor
        this.ctx.fillRect(x, y, width, height)
      }

      // 如果是图片节点
      if (node.src) {
        try {
          const imageInfo = await this.getImageInfo(node.src)
          this.ctx.drawImage(imageInfo.path, x, y, width, height)
        } catch (error) {
          console.error('绘制图片失败:', error)
        }
      }

      // 如果是文本节点
      if (node.textContent) {
        this.ctx.font = `${node.fontWeight || 'normal'} ${node.fontSize || '28rpx'} sans-serif`
        this.ctx.fillStyle = node.color || '#000000'
        this.ctx.textBaseline = 'top'

        // 处理文本换行
        const maxWidth = width
        const text = node.textContent.trim()
        const lineHeight = parseInt(node.lineHeight) || 1.5
        const fontSize = parseInt(node.fontSize) || 28

        let line = ''
        let lineY = y

        for (const char of text) {
          const testLine = line + char
          const metrics = this.ctx.measureText(testLine)

          if (metrics.width > maxWidth) {
            this.ctx.fillText(line, x, lineY)
            line = char
            lineY += fontSize * lineHeight
          } else {
            line = testLine
          }
        }

        if (line) {
          this.ctx.fillText(line, x, lineY)
        }
      }
    },

    // 生成海报
    async generatePoster() {
      uni.showLoading({ title: '生成中...' })

      try {
        // 获取内容区域的大小
        const contentInfo = await this.getNodeInfo('#posterContent')
        const { width, height } = contentInfo

        // 设置画布大小
        const canvas = this.ctx.canvas
        canvas.width = width * this.options.scale
        canvas.height = height * this.options.scale

        // 缩放以提高清晰度
        this.ctx.scale(this.options.scale, this.options.scale)

        // 绘制背景
        this.ctx.fillStyle = this.options.backgroundColor
        this.ctx.fillRect(0, 0, width, height)

        // 获取所有节点
        const nodes = await this.getAllNodes('#posterContent')

        // 按照层级顺序绘制节点
        for (const node of nodes) {
          await this.drawNode(node)
        }

        // 将画布内容转换为图片
        const res = await new Promise((resolve, reject) => {
          uni.canvasToTempFilePath({
            canvas: canvas,
            width: width,
            height: height,
            destWidth: width * this.options.scale,
            destHeight: height * this.options.scale,
            fileType: 'png',
            quality: 0.8,
            success: resolve,
            fail: reject
          })
        })

        this.posterUrl = res.tempFilePath
        this.$emit('success', this.posterUrl)

        // 预览生成的海报
        uni.previewImage({
          urls: [this.posterUrl]
        })
      } catch (error) {
        console.error('生成海报失败:', error)
        uni.showToast({
          title: '生成海报失败',
          icon: 'none'
        })
      } finally {
        uni.hideLoading()
      }
    },

    // 保存海报到相册
    async savePoster() {
      if (!this.posterUrl) {
        uni.showToast({
          title: '请先生成海报',
          icon: 'none'
        })
        return
      }

      try {
        await uni.saveImageToPhotosAlbum({
          filePath: this.posterUrl
        })

        uni.showToast({
          title: '保存成功',
          icon: 'success'
        })

        this.$emit('save', this.posterUrl)
      } catch (error) {
        console.error('保存失败:', error)
        uni.showToast({
          title: '保存失败',
          icon: 'none'
        })
      }
    },

    // 获取图片信息
    getImageInfo(src) {
      return new Promise((resolve, reject) => {
        if (src.startsWith('http')) {
          // 网络图片需要先下载
          uni.downloadFile({
            url: src,
            success: res => {
              if (res.statusCode === 200) {
                uni.getImageInfo({
                  src: res.tempFilePath,
                  success: resolve,
                  fail: reject
                })
              } else {
                reject(new Error('下载图片失败'))
              }
            },
            fail: reject
          })
        } else {
          // 本地图片直接获取信息
          uni.getImageInfo({
            src,
            success: resolve,
            fail: reject
          })
        }
      })
    }
  }
}
</script>

<style>
.poster-generator {
  width: 100%;
}

.content-wrapper {
  width: 100%;
  background-color: #ffffff;
  overflow: hidden;
}

.poster-canvas {
  position: fixed;
  left: -9999px;
  visibility: hidden;
}

.action-buttons {
  width: 100%;
  display: flex;
  justify-content: center;
  gap: 20rpx;
  padding: 30rpx;
}

.action-btn {
  flex: 1;
  height: 80rpx;
  line-height: 80rpx;
  text-align: center;
  border-radius: 40rpx;
  font-size: 28rpx;
  background-color: #f5f5f5;
  color: #333333;
}

.action-btn.primary {
  background-color: #2979ff;
  color: #ffffff;
}
</style>