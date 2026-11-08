/**
 * 佣金API
 */

import {
	get,
	post
} from '../utils/request';

/**
 * 墙主佣金预览
 */
export const preview = () => {
	return get('/api/wallowner/preview');
};

/**
 * @function saveUnify 统一下单
 * @param {Object} params 请求参数
 * @param {Number} amount 金额
 * @param {String} desc 描述
 * @param {String}	out_trade_no  订单号随机生成即可
 * 响应参数
 * {
    "code": 200,
    "msg": "支付成功",
    "data": {
        "res": {
            "return_code": "SUCCESS",
            "return_msg": "OK",
            "result_code": "SUCCESS",
            "mch_id": "1900000000",
            "appid": "wx0000000000000000",
            "nonce_str": "MOCK_NONCE_STR",
            "sign": "00000000000000000000000000000000",
            "prepay_id": "wx0000000000000000000000000000000000",
            "trade_type": "JSAPI"
        }
    },
    "url": null,
    "wait": 3
}
 */
export const saveUnify = (params={}) => {
	return post('/api/payment/saveUnify',params);
};
/**
 * @function jssdkConfig 获取支付的jssdk
 * @param {Object} params 请求参数
 * @param {String} params.prepay_id
 * 响应示例
 * {
    "code": 200,
    "msg": "获取成功",
    "data": {
        "jssdk": {
            "appId": "wx0000000000000000",
            "timeStamp": "1758894316",
            "nonceStr": "MOCK_NONCE_STR",
            "package": "prepay_id=21312",
            "signType": "MD5",
            "paySign": "00000000000000000000000000000000"
        }
    },
    "url": null,
    "wait": 3
}
 */
export const jssdkConfig = (params={}) => {
	return post('/api/payment/jssdkConfig',params);
};

/**
 * @function history 墙主提现历史
 * @param {Object}  params 请求参数
 * @param {String}  params.page	页码
 * @param {String}  params.limit	分页大小
 * @param {String}  params.start_date 开始时间
 * @param {String}  params.end_date	结束时间
 * @returns {Promise}
 * 响应示例
 * {
    "code": 200,
    "msg": "获取成功",
    "data": {
        "list": [
            {
                "withdrawal_id": 2,
                "cash_amount": 8,
                "cash_time": null,
                "create_time": "2025-09-20 11:56:00",
                "reject_remarks": "",
                "withdraw_status": 0,
                "withdrawal_evidence": "",
                "withdrawal_method": "wechat",
                "bank_card": "",
                "bank_name": "",
                "payee_name": ""
            },
            {
                "withdrawal_id": 1,
                "cash_amount": 5.2,
                "cash_time": "2025-09-22 15:17:35",
                "create_time": "2025-09-16 11:03:00",
                "reject_remarks": "",
                "withdraw_status": 0,
                "withdrawal_evidence": "https://tougao.njlinks.cn/uploads/withdrawal/20250922/1142f8c1fe6dac344a1b0b79327e6d87.png",
                "withdrawal_method": "offline",
                "bank_card": "123456",
                "bank_name": "中国银行",
                "payee_name": "岳鹏"
            }
        ],
        "total": 2,
        "page": 1,
        "limit": 20,
        "hasMore": false
    },
    "url": null,
    "wait": 3
}
 */
export const history = (params={}) => {
	return get('/api/wallowner/history',params);
};
/**
 * @function withdrawal_channels 获取提现渠道
 * @returns {Promise} 
 * 响应示例
 * {
    "code": 200,
    "msg": "生成成功",
    "data": {
        "info": "[\"wechat\",\"offline\"]"
    },
    "url": null,
    "wait": 3
}
 */
export const withdrawal_channels = () => {
	return get('/api/wallowner/withdrawal_channels');
};
/**
 * @function application 墙主申请提现
 * @param {Object} params 请求参数
 * @param {String} params.withdrawal_method 提现方式（微信零钱：wechat、银行账户：offline） 必填
 * @param {String} params.cash_amount	提现金额	必填
 * @param {String} params.bank_card		提现账户 （提现方式为银行账户时填写）
 * @param {String} params.bank_name		账户名称 （提现方式为银行账户时填写）
 * @param {String} params.payee_name	提现人	（提现方式为银行账户时填写）
 * 
*/
export const application = (params={}) => {
	return post('/api/wallowner/application',params);
};
const withdrawApi = {
	preview,
	saveUnify,
	jssdkConfig,
	history,
	withdrawal_channels,
	application
}
export default withdrawApi;