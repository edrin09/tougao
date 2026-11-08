// 海报截图生成器
// 基于DOM元素截图生成海报

/**
 * 使用Canvas绘制海报
 * @param {Object} posterData - 海报数据
 * @param {Object} options - 配置选项
 * @returns {Promise<string>} 临时文件路径
 */
export async function drawPosterCanvas(posterData, options = {}) {
  const {
    width = 750,
    height = 1000,
    quality = 0.8,
    fileType = "jpg",
    canvasId = "posterCanvas",
  } = options;

  try {
    console.log("开始绘制海报Canvas...");

    // 等待DOM渲染完成
    await new Promise((resolve) => setTimeout(resolve, 200));

    return new Promise((resolve, reject) => {
      const query = uni.createSelectorQuery();

      query
        .select(`#${canvasId}`)
        .fields({ node: true, size: true })
        .exec(async (res) => {
          try {
            console.log("Canvas查询结果:", res);

            if (!res || !res[0] || !res[0].node) {
              reject(new Error("Canvas节点获取失败"));
              return;
            }

            const canvas = res[0].node;
            const ctx = canvas.getContext("2d");

            if (!ctx) {
              reject(new Error("Canvas上下文获取失败"));
              return;
            }

            // 计算内容需要的实际高度（完全根据内容撑开）
            const actualHeight = await calculateContentHeight(
              posterData,
              { width },
              canvas
            );
            const finalHeight =
              actualHeight + 40 < 400 ? 400 : actualHeight + 40; // 底部留40px边距

            // 设置Canvas尺寸
            const dpr = uni.getSystemInfoSync().pixelRatio || 2;
            canvas.width = width * dpr;
            canvas.height = finalHeight * dpr;
            ctx.scale(dpr, dpr);

            // 绘制海报内容
            await drawPosterContent(
              ctx,
              posterData,
              { width, height: finalHeight },
              canvas
            );

            // 转换为临时文件
            uni.canvasToTempFilePath({
              canvas: canvas,
              width: width,
              height: finalHeight,
              destWidth: width * dpr,
              destHeight: finalHeight * dpr,
              quality: quality,
              fileType: fileType,
              success: (result) => {
                console.log("Canvas转换成功:", result.tempFilePath);
                resolve(result.tempFilePath);
              },
              fail: (error) => {
                console.error("Canvas转换失败:", error);
                reject(
                  new Error("Canvas转换失败: " + (error.errMsg || "未知错误"))
                );
              },
            });
          } catch (error) {
            console.error("绘制过程出错:", error);
            reject(error);
          }
        });
    });
  } catch (error) {
    console.error("绘制Canvas出错:", error);
    throw error;
  }
}

/**
 * 计算内容需要的实际高度
 * @param {Object} data - 海报数据
 * @param {Object} size - 画布尺寸
 * @param {Object} canvas - Canvas节点
 * @returns {Promise<number>} 实际需要的高度
 */
async function calculateContentHeight(data, size, canvas) {
  const { width } = size;
  const padding = 20;
  const cardPadding = 24;

  // 创建临时canvas上下文用于测量
  const tempCanvas = canvas;
  const ctx = tempCanvas.getContext("2d");

  let currentY = 60 + cardPadding; // cardY + cardPadding

  // 标签高度
  if (data.tag) {
    currentY += 40;
  }

  // 内容高度
  if (data.content) {
    ctx.font = "15px sans-serif";
    const contentPadding = 12;
    const cardWidth = width - padding * 2;
    const maxContentWidth = cardWidth - cardPadding * 2 - contentPadding * 2;
    const contentLines = wrapText(ctx, data.content, maxContentWidth);
    const contentHeight = contentLines.length * 22 + contentPadding * 2;
    currentY += contentHeight + 20;
  }

  // 墙主回复高度（放在最后）
  if (data.submission_reply) {
    ctx.font = "15px sans-serif"; // 与投稿内容相同的字体
    const contentPadding = 12; // 与投稿内容相同的内边距
    const avatarSize = 40;
    const replyGap = 12;
    const cardWidth = width - padding * 2;
    const replyWidth = cardWidth - cardPadding * 2;

    if (data.owner_avatar) {
      // 有头像的情况 - 右对齐，文本可以使用更多空间
      const maxReplyWidth =
        Math.min(replyWidth * 0.7, replyWidth - avatarSize - replyGap) -
        contentPadding * 2;
      const replyLines = wrapText(ctx, data.submission_reply, maxReplyWidth);
      const replyTextHeight = replyLines.length * 22; // 与投稿内容相同的行高
      const replyBubbleHeight = replyTextHeight + contentPadding * 2;
      const totalHeight = Math.max(avatarSize, replyBubbleHeight) + 60; // 顶部20px + 底部40px
      currentY += totalHeight;
    } else {
      // 无头像的情况 - 右对齐，文本使用70%宽度
      const maxReplyWidth = replyWidth * 0.7 - contentPadding * 2;
      const replyLines = wrapText(ctx, data.submission_reply, maxReplyWidth);
      const replyTextHeight = replyLines.length * 22;
      const replyBubbleHeight = replyTextHeight + contentPadding * 2;
      currentY += replyBubbleHeight + 60; // 顶部20px + 底部40px
    }
  }

  // 图片区域高度 - 垂直排列
  if (data.images && data.images.length > 0) {
    const cardWidth = width - padding * 2;
    const imageGap = 16; // 图片之间的间距
    const imagePadding = 12; // 图片容器内边距
    const imageWidth = cardWidth - imagePadding * 2;

    // 计算每张图片的高度
    for (let i = 0; i < Math.min(data.images.length, 9); i++) {
      try {
        const imageInfo = await loadCanvasImage(data.images[i].url, canvas);
        const aspectRatio = imageInfo.width / imageInfo.height;

        // 计算图片高度和容器高度
        const imageHeight = imageWidth / aspectRatio;
        const containerHeight = imageHeight + imagePadding * 2;

        currentY += containerHeight + imageGap;
      } catch (error) {
        // 图片加载失败，使用默认高度
        const defaultHeight = 200;
        const containerHeight = defaultHeight + imagePadding * 2;
        currentY += containerHeight + imageGap;
      }
    }
    currentY -= imageGap; // 减去最后一个gap
  } else if (data.imageCount && data.imageCount > 0) {
    // 占位符图片高度 - 垂直排列
    const imageGap = 16; // 图片之间的间距
    const imagePadding = 12; // 图片容器内边距
    const defaultHeight = 200; // 占位符默认高度
    const containerHeight = defaultHeight + imagePadding * 2;

    for (let i = 0; i < Math.min(data.imageCount, 9); i++) {
      currentY += containerHeight + imageGap;
    }
    currentY -= imageGap; // 减去最后一个gap
  }

  return currentY;
}

/**
 * 绘制海报内容
 * @param {CanvasRenderingContext2D} ctx - Canvas上下文
 * @param {Object} data - 海报数据
 * @param {Object} size - 画布尺寸
 * @param {Object} canvas - Canvas节点
 */
async function drawPosterContent(ctx, data, size, canvas) {
  const { width, height } = size;
  const padding = 20;
  const cardPadding = 24;

  console.log("开始绘制海报内容:", data);

  // 绘制背景（优先使用自定义背景图片）
  if (data.custom_bg_image) {
    await drawCustomBackground(
      ctx,
      data.custom_bg_image,
      width,
      height,
      canvas
    );
  } else {
    // 使用用户选择的背景色
    ctx.fillStyle = data.backgroundColor || "#1976d2";
    ctx.fillRect(0, 0, width, height);
  }

  // 计算卡片区域
  const cardX = padding;
  const cardY = 60;
  const cardWidth = width - padding * 2;

  // // 绘制白色圆角卡片背景
  // ctx.fillStyle = '#ffffff';
  // drawRoundRect(ctx, cardX, cardY, cardWidth, cardHeight, 16);
  // ctx.fill();

  // // 添加卡片阴影效果
  // ctx.shadowColor = 'rgba(0, 0, 0, 0.1)';
  // ctx.shadowBlur = 8;
  // ctx.shadowOffsetX = 0;
  // ctx.shadowOffsetY = 4;
  // ctx.fillStyle = '#ffffff';
  // drawRoundRect(ctx, cardX, cardY, cardWidth, cardHeight, 16);
  // ctx.fill();

  // // 重置阴影
  // ctx.shadowColor = 'transparent';
  // ctx.shadowBlur = 0;
  // ctx.shadowOffsetX = 0;
  // ctx.shadowOffsetY = 0;

  // 绘制顶部时间 - 在蓝色背景上显示
  if (data.time) {
    ctx.fillStyle = "#000000";
    ctx.font = "14px sans-serif";
    ctx.textAlign = "center";
    ctx.fillText(data.time, width / 2, 40);
  }

  let currentY = cardY + cardPadding;

  // 绘制标签
  if (data.tag) {
    // 计算标签文字宽度
    ctx.font = 'bold 24px sans-serif';  // 修改为24px并加粗
    const tagText = `#${data.tag}`;
    const textWidth = ctx.measureText(tagText).width;

    // 标签背景 - 圆角矩形
    const tagX = cardX + cardPadding;
    const tagY = currentY;
    const tagPadding = 12;
    const tagWidth = textWidth + tagPadding * 2;
    const tagHeight = 36;  // 增加高度以适应更大的字号
    const radius = 18;  // 增加圆角半径

    ctx.fillStyle = "#f8f9fa";
    drawRoundRect(ctx, tagX, tagY, tagWidth, tagHeight, radius);
    ctx.fill();

    // 标签文字（垂直居中）
    ctx.fillStyle = "#1976d2";
    ctx.font = "bold 24px sans-serif"; // 标签字体大小24px并加粗
    ctx.textAlign = "left";
    const tagTextY = tagY + tagHeight / 2 + 8; // 调整垂直居中位置
    ctx.fillText(tagText, tagX + tagPadding, tagTextY);

    currentY += 48;  // 增加间距
  }

  // // 绘制标题
  // if (data.title) {
  //   ctx.fillStyle = '#333333';
  //   ctx.font = 'bold 18px sans-serif';
  //   ctx.textAlign = 'left';

  //   const titleLines = wrapText(ctx, data.title, cardWidth - cardPadding * 2);
  //   titleLines.forEach((line, index) => {
  //     ctx.fillText(line, cardX + cardPadding, currentY + (index * 26));
  //   });

  //   currentY += titleLines.length * 26 + 16;
  // }

  // 绘制内容
  if (data.content) {
    ctx.font = "bold 24px sans-serif"; // 内容字体大小24px并加粗
    const contentPadding = 16;  // 增加内边距
    const maxContentWidth = cardWidth - cardPadding * 2 - contentPadding * 2;
    const contentLines = wrapText(ctx, data.content, maxContentWidth);

    // 计算每行的实际宽度，找出最宽的一行
    let maxLineWidth = 0;
    contentLines.forEach((line) => {
      const lineWidth = ctx.measureText(line).width;
      if (lineWidth > maxLineWidth) {
        maxLineWidth = lineWidth;
      }
    });

    // 根据内容计算背景区域的宽度和高度
    const contentHeight = contentLines.length * 32 + contentPadding * 2;  // 增加行高
    const contentWidth = maxLineWidth + contentPadding * 2;
    const contentX = cardX + cardPadding;
    const contentY = currentY;

    // 保存最大内容宽度，用于后续图片容器宽度
    const maxContentContainerWidth = contentWidth;

    // 绘制内容背景（圆角）
    ctx.fillStyle = "#f8f9fa";
    drawRoundRect(ctx, contentX, contentY, contentWidth, contentHeight, 12);
    ctx.fill();

    // 绘制内容文字（垂直居中）
    ctx.fillStyle = "#000000";
    const lineHeight = 32;  // 增加行高
    const totalTextHeight = contentLines.length * lineHeight;
    const textStartY = contentY + (contentHeight - totalTextHeight) / 2 + lineHeight * 0.8;

    contentLines.forEach((line, index) => {
      ctx.fillText(line, contentX + contentPadding, textStartY + index * lineHeight);
    });

    currentY += contentHeight + 20;

    // 绘制图片区域 - 垂直排列
    console.log("开始绘制图片区域", data.images);
    if (data.images && data.images.length > 0) {
      const imageGap = 16; // 图片之间的间距

      // 垂直排列绘制图片
      for (let i = 0; i < Math.min(data.images.length, 9); i++) {
        try {
          console.log("开始加载图片:", data.images[i].url);
          const imageInfo = await loadCanvasImage(data.images[i].url, canvas);

          // 计算图片绘制尺寸 - 使用与内容区域相同的起始位置和宽度
          const imagePadding = 16; // 增加图片内边距
          const imageContainerWidth = cardWidth - cardPadding * 2; // 与内容区域相同的宽度
          const imageWidth = imageContainerWidth - imagePadding * 2;
          const aspectRatio = imageInfo.width / imageInfo.height;
          const imageHeight = imageWidth / aspectRatio;

          // 计算容器总高度
          const containerHeight = imageHeight + imagePadding * 2;

          // 计算位置 - 与内容区域使用相同的起始位置
          const containerX = cardX + cardPadding; // 与内容区域相同的起始位置
          const containerY = currentY;
          const imageX = containerX + imagePadding;
          const imageY = containerY + imagePadding;

          // 绘制图片容器背景（圆角）
          ctx.fillStyle = "#f8f9fa";
          drawRoundRect(
            ctx,
            containerX,
            containerY,
            imageContainerWidth,
            containerHeight,
            12
          );
          ctx.fill();

          // 验证图片对象并绘制
          if (imageInfo.image) {
            console.log("准备绘制图片:", imageInfo.path);
            try {
              // 使用裁剪路径绘制圆角图片
              ctx.save();
              drawRoundRect(ctx, imageX, imageY, imageWidth, imageHeight, 8);
              ctx.clip();
              ctx.drawImage(
                imageInfo.image,
                imageX,
                imageY,
                imageWidth,
                imageHeight
              );
              ctx.restore();
              console.log("图片绘制成功:", imageInfo.path);
            } catch (drawError) {
              console.error("drawImage调用失败:", drawError);
              throw drawError;
            }
          } else {
            throw new Error("无效的图片对象");
          }

          // 绘制图片边框（圆角）
          ctx.strokeStyle = "#e0e0e0";
          ctx.lineWidth = 1;
          drawRoundRect(ctx, imageX, imageY, imageWidth, imageHeight, 8);
          ctx.stroke();

          // 更新Y坐标到下一张图片位置
          currentY += containerHeight + imageGap;
        } catch (error) {
          console.error("图片加载失败，使用占位符:", error);

          // 绘制占位符 - 使用与内容区域相同的宽度和位置
          const imagePadding = 16;
          const imageContainerWidth = cardWidth - cardPadding * 2;
          const imageWidth = imageContainerWidth - imagePadding * 2;
          const defaultHeight = 200;
          const containerHeight = defaultHeight + imagePadding * 2;

          const containerX = cardX + cardPadding;
          const containerY = currentY;
          const imageX = containerX + imagePadding;
          const imageY = containerY + imagePadding;

          // 绘制图片容器背景（圆角）
          ctx.fillStyle = "#f8f9fa";
          drawRoundRect(
            ctx,
            containerX,
            containerY,
            imageContainerWidth,
            containerHeight,
            12
          );
          ctx.fill();

          // 绘制图片占位框（圆角）
          ctx.fillStyle = "#f5f5f5";
          drawRoundRect(ctx, imageX, imageY, imageWidth, defaultHeight, 8);
          ctx.fill();

          // 绘制图片边框（圆角）
          ctx.strokeStyle = "#e0e0e0";
          ctx.lineWidth = 1;
          drawRoundRect(ctx, imageX, imageY, imageWidth, defaultHeight, 8);
          ctx.stroke();

          // 绘制图片占位文字
          ctx.fillStyle = "#cccccc";
          ctx.font = "14px sans-serif";
          ctx.textAlign = "center";
          ctx.fillText(
            "图片",
            imageX + imageWidth / 2,
            imageY + defaultHeight / 2 + 5
          );

          // 更新Y坐标
          currentY += containerHeight + imageGap;
        }
      }

      // 减去最后一个imageGap
      currentY -= imageGap;
    }
  } else if (data.imageCount && data.imageCount > 0) {
    // 如果只有图片数量信息，绘制占位符 - 垂直排列
    const imageGap = 16; // 图片之间的间距
    const imagePadding = 12; // 图片容器内边距
    const imageContainerWidth = cardWidth;
    const imageWidth = imageContainerWidth - imagePadding * 2;
    const defaultHeight = 200; // 占位符默认高度
    const containerHeight = defaultHeight + imagePadding * 2;

    for (let i = 0; i < Math.min(data.imageCount, 9); i++) {
      const containerX = cardX;
      const containerY = currentY;
      const imageX = containerX + imagePadding;
      const imageY = containerY + imagePadding;

      // 绘制图片容器背景（圆角）
      ctx.fillStyle = "#f8f9fa";
      drawRoundRect(
        ctx,
        containerX,
        containerY,
        imageContainerWidth,
        containerHeight,
        12
      );
      ctx.fill();

      // 绘制图片占位框（圆角）
      ctx.fillStyle = "#f5f5f5";
      drawRoundRect(ctx, imageX, imageY, imageWidth, defaultHeight, 8);
      ctx.fill();

      // 绘制图片边框（圆角）
      ctx.strokeStyle = "#e0e0e0";
      ctx.lineWidth = 1;
      drawRoundRect(ctx, imageX, imageY, imageWidth, defaultHeight, 8);
      ctx.stroke();

      // 绘制"图片"文字
      ctx.fillStyle = "#cccccc";
      ctx.font = "14px sans-serif";
      ctx.textAlign = "center";
      ctx.fillText(
        "图片",
        imageX + imageWidth / 2,
        imageY + defaultHeight / 2 + 5
      );

      // 更新Y坐标
      currentY += containerHeight + imageGap;
    }

    // 减去最后一个imageGap
    currentY -= imageGap;
  }

  // 绘制墙主回复（放在最下面）
  if (data.submission_reply && data.owner_avatar) {
    console.log("开始绘制墙主回复");
    currentY = await drawOwnerReply(
      ctx,
      data,
      cardX,
      cardWidth,
      cardPadding,
      currentY,
      canvas
    );
  }

  // 等待一段时间确保所有图片都已绘制完成
  await new Promise((resolve) => setTimeout(resolve, 1000));

  console.log("海报内容绘制完成");
}

/**
 * 绘制自定义背景图片
 * @param {CanvasRenderingContext2D} ctx - Canvas上下文
 * @param {string} bgImageUrl - 背景图片URL
 * @param {number} width - 画布宽度
 * @param {number} height - 画布高度
 * @param {Object} canvas - Canvas节点
 */
async function drawCustomBackground(ctx, bgImageUrl, width, height, canvas) {
  try {
    console.log("开始加载自定义背景图片:", bgImageUrl);
    const bgImageInfo = await loadCanvasImage(bgImageUrl, canvas);

    const bgWidth = bgImageInfo.width;
    const bgHeight = bgImageInfo.height;

    // 计算需要重复的次数（垂直重复）
    const repeatCount = Math.ceil(height / bgHeight);

    console.log(
      `背景图片尺寸: ${bgWidth}x${bgHeight}, 画布高度: ${height}, 重复次数: ${repeatCount}`
    );

    // 垂直重复绘制背景图片
    for (let i = 0; i < repeatCount; i++) {
      const y = i * bgHeight;
      const drawHeight = Math.min(bgHeight, height - y); // 最后一次可能需要裁剪

      if (drawHeight > 0) {
        // 如果背景图片宽度与画布宽度不同，需要缩放
        const scaleX = width / bgWidth;
        const scaledHeight = bgHeight * scaleX;
        const scaledY = i * scaledHeight;
        const scaledDrawHeight = Math.min(scaledHeight, height - scaledY);

        if (scaledDrawHeight > 0) {
          ctx.drawImage(
            bgImageInfo.image,
            0,
            0,
            bgWidth,
            bgHeight * (scaledDrawHeight / scaledHeight), // 源图片区域
            0,
            scaledY,
            width,
            scaledDrawHeight // 目标区域
          );
        }
      }
    }

    console.log("自定义背景图片绘制完成");
  } catch (error) {
    console.error("绘制自定义背景失败，使用默认背景色:", error);
    // 如果背景图片加载失败，使用默认背景色
    ctx.fillStyle = "#1976d2";
    ctx.fillRect(0, 0, width, height);
  }
}

/**
 * 绘制墙主回复
 * @param {CanvasRenderingContext2D} ctx - Canvas上下文
 * @param {Object} data - 海报数据
 * @param {number} cardX - 卡片X坐标
 * @param {number} cardWidth - 卡片宽度
 * @param {number} cardPadding - 卡片内边距
 * @param {number} currentY - 当前Y坐标
 * @param {Object} canvas - Canvas节点
 * @returns {Promise<number>} 更新后的Y坐标
 */
async function drawOwnerReply(
  ctx,
  data,
  cardX,
  cardWidth,
  cardPadding,
  currentY,
  canvas
) {
  const contentPadding = 12; // 与投稿内容相同的内边距
  const avatarSize = 40;
  const replyGap = 12;

  // 计算回复区域
  const replyX = cardX + cardPadding;
  const replyY = currentY + 20; // 顶部留20px间距
  const replyWidth = cardWidth - cardPadding * 2;

  try {
    // 加载墙主头像
    const avatarInfo = await loadCanvasImage(data.owner_avatar, canvas);

    // 计算回复文字区域（右对齐，使用合适的宽度）
    ctx.font = "15px sans-serif"; // 与投稿内容相同的字体
    const maxReplyWidth =
      Math.min(replyWidth * 0.7, replyWidth - avatarSize - replyGap) -
      contentPadding * 2;
    const replyLines = wrapText(ctx, data.submission_reply, maxReplyWidth);

    // 计算每行的实际宽度，找出最宽的一行
    let maxLineWidth = 0;
    replyLines.forEach((line) => {
      const lineWidth = ctx.measureText(line).width;
      if (lineWidth > maxLineWidth) {
        maxLineWidth = lineWidth;
      }
    });

    // 根据内容计算背景区域的宽度和高度
    const replyTextHeight = replyLines.length * 22; // 与投稿内容相同的行高
    const replyBubbleHeight = replyTextHeight + contentPadding * 2;
    const replyBubbleWidth = maxLineWidth + contentPadding * 2;

    // 计算总高度（取头像高度和回复气泡高度的最大值）
    const totalHeight = Math.max(avatarSize, replyBubbleHeight);

    // 计算右对齐位置
    const totalReplyWidth = replyBubbleWidth + replyGap + avatarSize;
    const rightAlignX = replyX + replyWidth - totalReplyWidth;

    // 绘制回复文字背景（右对齐）
    const bubbleX = rightAlignX;
    const bubbleY = replyY + (totalHeight - replyBubbleHeight) / 2; // 垂直居中

    // 绘制内容背景（圆角，与投稿内容相同）
    ctx.fillStyle = "#f8f9fa";
    drawRoundRect(
      ctx,
      bubbleX,
      bubbleY,
      replyBubbleWidth,
      replyBubbleHeight,
      8
    );
    ctx.fill();

    // 绘制回复文字（与投稿内容相同的样式）
    ctx.fillStyle = "#000000";
    ctx.font = "18px sans-serif"; // 回复文字字体大小
    ctx.textAlign = "left";

    const lineHeight = 22;
    const totalTextHeight = replyLines.length * lineHeight;
    const textStartY =
      bubbleY + (replyBubbleHeight - totalTextHeight) / 2 + lineHeight * 0.8; // 0.8是文字基线调整

    replyLines.forEach((line, index) => {
      ctx.fillText(
        line,
        bubbleX + contentPadding,
        textStartY + index * lineHeight
      );
    });

    // 绘制头像（圆形，在最右边）
    const avatarX = bubbleX + replyBubbleWidth + replyGap;
    const avatarY = replyY + (totalHeight - avatarSize) / 2; // 垂直居中

    ctx.save();
    ctx.beginPath();
    ctx.arc(
      avatarX + avatarSize / 2,
      avatarY + avatarSize / 2,
      avatarSize / 2,
      0,
      Math.PI * 2
    );
    ctx.clip();
    ctx.drawImage(avatarInfo.image, avatarX, avatarY, avatarSize, avatarSize);
    ctx.restore();

    // 绘制头像边框
    ctx.strokeStyle = "#e0e0e0";
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.arc(
      avatarX + avatarSize / 2,
      avatarY + avatarSize / 2,
      avatarSize / 2,
      0,
      Math.PI * 2
    );
    ctx.stroke();

    return currentY + totalHeight + 40; // 底部留40px间距
  } catch (error) {
    console.error("绘制墙主回复失败:", error);
    // 如果头像加载失败，只绘制文字回复

    ctx.font = "18px sans-serif"; // 回复文字字体大小（头像加载失败时）
    const maxReplyWidth = replyWidth * 0.7 - contentPadding * 2; // 右对齐，使用70%宽度
    const replyLines = wrapText(ctx, data.submission_reply, maxReplyWidth);

    // 计算每行的实际宽度，找出最宽的一行
    let maxLineWidth = 0;
    replyLines.forEach((line) => {
      const lineWidth = ctx.measureText(line).width;
      if (lineWidth > maxLineWidth) {
        maxLineWidth = lineWidth;
      }
    });

    // 根据内容计算背景区域的宽度和高度
    const replyTextHeight = replyLines.length * 22;
    const replyBubbleHeight = replyTextHeight + contentPadding * 2;
    const replyBubbleWidth = maxLineWidth + contentPadding * 2;

    // 绘制回复文字背景（右对齐）
    const bubbleX = replyX + replyWidth - replyBubbleWidth;
    const bubbleY = replyY;

    // 绘制内容背景（圆角）
    ctx.fillStyle = "#f8f9fa";
    drawRoundRect(
      ctx,
      bubbleX,
      bubbleY,
      replyBubbleWidth,
      replyBubbleHeight,
      8
    );
    ctx.fill();

    // 绘制回复文字
    ctx.fillStyle = "#000000";
    ctx.font = "18px sans-serif"; // 回复文字字体大小
    ctx.textAlign = "left";

    const lineHeight = 22;
    const totalTextHeight = replyLines.length * lineHeight;
    const textStartY =
      bubbleY + (replyBubbleHeight - totalTextHeight) / 2 + lineHeight * 0.8;

    replyLines.forEach((line, index) => {
      ctx.fillText(
        line,
        bubbleX + contentPadding,
        textStartY + index * lineHeight
      );
    });

    return currentY + replyBubbleHeight + 40; // 底部留40px间距
  }
}

/**
 * 加载Canvas图片 - 适用于微信小程序
 * @param {string} src - 图片URL
 * @param {Object} canvas - Canvas节点
 * @returns {Promise<Object>} 图片信息对象
 */
function loadCanvasImage(src, canvas) {
  return new Promise((resolve, reject) => {
    console.log("开始加载图片:", src);

    // 设置超时处理
    const timeoutId = setTimeout(() => {
      reject(new Error("图片加载超时: " + src));
    }, 15000); // 15秒超时

    // 使用uni.getImageInfo获取图片信息
    uni.getImageInfo({
      src: src,
      success: (res) => {
        clearTimeout(timeoutId);
        console.log("图片信息获取成功:", res);

        try {
          // 创建图片对象
          const img = canvas.createImage();

          img.onload = () => {
            console.log("Canvas图片对象加载完成");
            const imageInfo = {
              width: res.width,
              height: res.height,
              image: img, // 使用Canvas创建的图片对象
              path: res.path || res.src || src,
              originalSrc: src,
            };

            console.log("图片信息处理完成:", imageInfo);
            resolve(imageInfo);
          };

          img.onerror = (error) => {
            console.error("Canvas图片对象加载失败:", error);
            reject(new Error(`Canvas图片对象创建失败: ${src}`));
          };

          // 设置图片源
          img.src = res.path || res.src || src;
        } catch (error) {
          console.error("创建Canvas图片对象失败:", error);
          reject(
            new Error(`创建Canvas图片对象失败: ${src} - ${error.message}`)
          );
        }
      },
      fail: (error) => {
        clearTimeout(timeoutId);
        console.error("图片信息获取失败:", src, error);

        // 提供更详细的错误信息
        const errorMsg = error.errMsg || error.message || "未知错误";
        reject(new Error(`图片加载失败: ${src} - ${errorMsg}`));
      },
    });
  });
}

/**
 * 绘制圆角矩形
 * @param {CanvasRenderingContext2D} ctx - Canvas上下文
 * @param {number} x - X坐标
 * @param {number} y - Y坐标
 * @param {number} width - 宽度
 * @param {number} height - 高度
 * @param {number} radius - 圆角半径
 */
function drawRoundRect(ctx, x, y, width, height, radius) {
  ctx.beginPath();
  ctx.moveTo(x + radius, y);
  ctx.lineTo(x + width - radius, y);
  ctx.arcTo(x + width, y, x + width, y + radius, radius);
  ctx.lineTo(x + width, y + height - radius);
  ctx.arcTo(x + width, y + height, x + width - radius, y + height, radius);
  ctx.lineTo(x + radius, y + height);
  ctx.arcTo(x, y + height, x, y + height - radius, radius);
  ctx.lineTo(x, y + radius);
  ctx.arcTo(x, y, x + radius, y, radius);
  ctx.closePath();
}

/**
 * 文本换行处理
 * @param {CanvasRenderingContext2D} ctx - Canvas上下文
 * @param {string} text - 文本内容
 * @param {number} maxWidth - 最大宽度
 * @returns {Array<string>} 换行后的文本数组
 */
function wrapText(ctx, text, maxWidth) {
  const words = Array.from(text);
  const lines = [];
  let currentLine = "";

  for (let i = 0; i < words.length; i++) {
    const testLine = currentLine + words[i];
    const metrics = ctx.measureText(testLine);

    if (metrics.width > maxWidth && currentLine !== "") {
      lines.push(currentLine);
      currentLine = words[i];
    } else {
      currentLine = testLine;
    }
  }

  if (currentLine) {
    lines.push(currentLine);
  }

  return lines;
}

/**
 * 生成校园墙海报
 * @param {Object} posterData - 海报数据
 * @param {Object} options - 配置选项
 * @returns {Promise<string>} 临时文件路径
 */
export async function generateCampusPoster(posterData, options = {}) {
  try {
    console.log("开始生成校园墙海报...", posterData);

    const tempFilePath = await drawPosterCanvas(posterData, {
      width: 750,
      quality: 0.9,
      fileType: "jpg",
      canvasId: "posterCanvas",
      ...options,
    });

    console.log("校园墙海报生成成功:", tempFilePath);
    return tempFilePath;
  } catch (error) {
    console.error("生成校园墙海报失败:", error);
    throw error;
  }
}

/**
 * 保存图片到相册
 * @param {string} filePath - 图片文件路径
 * @returns {Promise<void>}
 */
export async function saveImageToAlbum(filePath) {
  try {
    console.log("开始保存图片到相册:", filePath);

    await uni.saveImageToPhotosAlbum({
      filePath: filePath,
    });

    console.log("图片保存成功");

    uni.showToast({
      title: "保存成功",
      icon: "success",
    });
  } catch (error) {
    console.error("保存图片失败:", error);

    if (error.errMsg && error.errMsg.includes("auth")) {
      uni.showModal({
        title: "提示",
        content: "需要授权访问相册才能保存图片",
        confirmText: "去设置",
        success: (res) => {
          if (res.confirm) {
            uni.openSetting();
          }
        },
      });
    } else {
      uni.showToast({
        title: "保存失败",
        icon: "none",
      });
    }

    throw error;
  }
}

/**
 * 预览图片
 * @param {string} filePath - 图片文件路径
 */
export function previewPoster(filePath) {
  try {
    console.log("预览海报:", filePath);

    uni.previewImage({
      urls: [filePath],
      current: filePath,
      success: () => {
        console.log("预览成功");
      },
      fail: (error) => {
        console.error("预览失败:", error);
        uni.showToast({
          title: "预览失败",
          icon: "none",
        });
      },
    });
  } catch (error) {
    console.error("预览图片出错:", error);
  }
}

/**
 * 分享海报
 * @param {string} filePath - 图片文件路径
 * @param {Object} shareInfo - 分享信息
 */
export function sharePoster(filePath, shareInfo = {}) {
  try {
    console.log("分享海报:", filePath);

    // 分享信息配置
    console.log("分享信息:", shareInfo);

    // 微信小程序分享
    uni.showShareMenu({
      withShareTicket: true,
      menus: ["shareAppMessage", "shareTimeline"],
    });

    // 可以在这里添加更多分享逻辑
    console.log("分享配置完成");
  } catch (error) {
    console.error("分享配置失败:", error);
  }
}
