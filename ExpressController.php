<?php
/**
 * 快递查询
 * Created by Vscode
 * User: piscal
 * DateTime: 2019-10-23 15:41:28
 */

class ExpressController extends WebController
{

    //接口返回状态码
    private $status = [
        0 => '查询正常',
        201 => '快递单号错误',
        203 => '快递公司不存在',
        204 => '快递公司识别失败',
        205 => '查无快递信息',
        207 => '该单号被限制，一个单号对应多个快递公司，请求需要指定快递公司'
    ];

    //快递投递状态码
    private $delivery_status = [
        0 => '快递收件（揽件）',
        1 => '在途中',
        2 => '正在派件',
        3 => '已签收',
        4 => '派送失败（无法联系到收件人或客户要求择日派送，地址不详或手机号不清）',
        5 => '疑难件（收件人拒绝签收，地址有误或不能送达派送区域，收费等原因无法正常派送）',
        6 => '退件签收'
    ];

    /**
     * 快递单号查询
     * @author piscal
     *
     * @return string
     */
    public function actionSelect()
    {
        $no = Yii::app()->request->getParam('no', '');
        $type = Yii::app()->request->getParam('type', '');
        if (empty($no)) {
            echo '请输入快递单号';
            exit;
        }
        
        $expressModel = new Express();
        //优先查询数据库，防止接口被频繁刷新如果订单号存在的情况下
        $result = $expressModel->getDataByCondition($no, $type);
        if ($result) {
            print_r($result);
            exit;
        } else {
            //判断缓存是否存在，存在就读取出来，默认存储时常5min,不存在查询api
            $v = Yii::app()->redis->get($no);
            if ($v) {
                print_r($v);
                exit;
            }

            // $result = LibExpress::select($no);
            $result = json_decode('{"status":"0","msg":"ok","result":{"number":"SF1010013984972","type":"SFEXPRESS","list":[{"time":"2019-08-08 18:11:44","status":"已签收,感谢使用顺丰,期待再次为您服务"},{"time":"2019-08-08 17:50:02","status":"快件交给，正在派送途中（联系电话：）"},{"time":"2019-08-08 16:25:32","status":"快件到达 【上海浦东康桥营业点】"},{"time":"2019-08-08 15:57:43","status":"快件已发车"},{"time":"2019-08-08 15:51:43","status":"快件在【上海浦江集散中心】已装车,准备发往 【上海浦东康桥营业点】"},{"time":"2019-08-08 13:31:56","status":"快件到达 【上海浦江集散中心】"},{"time":"2019-08-08 02:27:08","status":"快件已发车"},{"time":"2019-08-08 01:48:44","status":"快件在【福州闽侯中转场】已装车,准备发往 【上海浦江集散中心】"},{"time":"2019-08-07 22:47:19","status":"快件到达 【福州闽侯中转场】"},{"time":"2019-08-07 21:55:56","status":"快件已发车"},{"time":"2019-08-07 20:50:38","status":"快件在【福州连江碧水龙庭营业点】已装车,准备发往下一站"},{"time":"2019-08-07 17:01:31","status":"顺丰速运 已收取快件"}],"deliverystatus":3,"issign":1,"expName":"顺丰快递","expSite":"www.sf-express.com ","expPhone":"95338","logo":"http:\/\/img3.fegine.com\/express\/sf.jpg","courier":"","courierPhone":"","updateTime":"2019-08-08 18:11:44","takeTime":"1天1小时10分"}}', true);
            Yii::app()->redis->setex($no, 5*60, json_encode($result));
        }

        if ($result['status'] == 0) {
            if (in_array($result['result']['deliverystatus'], [3,4,5,6])) {
                $data = [
                    'number' => $result['result']['number'],
                    'type' => $result['result']['type'],
                    'delivery_status' => $result['result']['deliverystatus'],
                    'exp_name' => $result['result']['expName'],
                    'data' => json_encode($result['result']),
                    'created_at' => time(),
                    'updated_at' => time()
                ];
                $addResult = $expressModel->addData($data);
                echo $addResult ? '写入成功' : '写入失败';
                exit;
            }
        } else {
            echo $this->status[$result['status']];
            exit;
        }


    }
}