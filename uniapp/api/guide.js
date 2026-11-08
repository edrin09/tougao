import { get } from "../utils/request";

const guide = {
  // 获取使用教程富文本
  getUserGuide() {
    // 后端接口示例，可根据实际情况修改
    return get("api/tutorial/get");
  },
};

export default guide; 