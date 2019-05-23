<?php

namespace Huaiyang\Functions;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

class Functions
{

    private $devEnv = true;

    private $httpErrorCode = [
        '10001' => '请求异常错误',
        '20001' => '响应异常错误',
        '20002' => '响应结果非json格式数据',
    ];

    private $httpClient = null;

    public function __construct()
    {

        if ('production' == config('app.env')) {
            $this->env = false;
        }

        $this->httpClient = new Client();
    }

    public function test()
    {

        echo 'hello functions';
    }


    /**
     *
     * POST 请求
     * @param $url
     * @param array $data
     * @return mixed
     */
    public function curlPost($url, $params = [], $headers = [])
    {


        $url = $this->getUrl($url);

        //开发环境异常返回的扩展参数
        $extension['url'] = $url;
        $extension['params'] = $params;

        try {
            $response = $this->httpClient->request('POST', $url, ['form_params' => $params, 'headers' => $headers]);

            return $this->curlResponse($response, $extension);

        } catch (RequestException $e) {

            $exception['error_code'] = 10001;
            $extension['exception_msg'] = $e -> getMessage();

            $this->httpException($exception, $extension);

        }

    }


    /**
     *
     * POST 请求
     * @param $url
     * @param array $data
     * @return mixed
     */
    public function curlGet($url, $params = [], $headers = [])
    {

        $url = $this->getUrl($url);

        //开发环境异常返回的扩展参数
        $extension['url'] = $url;
        $extension['params'] = $params;

        try {
            $response = $this->httpClient->request('GET', $url, ['query' => $params, 'headers' => $headers]);

            return $this->curlResponse($response, $extension);

        } catch (RequestException $e) {

            $exception['error_code'] = 10001;
            $extension['exception_msg'] = $e -> getMessage();
            $this->httpException($exception, $extension);

        }

    }

    /**
     *
     * POST 请求
     * @param $url
     * @param array $data
     * @return mixed
     */
    public function curlPut($url, $params = [], $headers = [])
    {

        $url = $this->getUrl($url);

        //开发环境异常返回的扩展参数
        $extension['url'] = $url;
        $extension['params'] = $params;

        try {
            $response = $this->httpClient->request('GET', $url, ['form_params' => $params, 'headers' => $headers]);

            return $this->curlResponse($response, $extension);

        } catch (RequestException $e) {

            $exception['error_code'] = 10001;
            $extension['exception_msg'] = $e -> getMessage();
            $this->httpException($exception, $extension);

        }

    }

    /**
     * 构建API成功后的返回JSON数据格式
     * @param array $data
     * @param string $msg
     * @param string $statusCode
     * @return mixed
     */
    public function buildSuccessResponse($data = [], $msg = 'success', $statusCode = '0')
    {


        $response['result'] = 1;
        $response['message'] = $msg;
        $response['data'] = $data;
        $response['success_code'] = $statusCode;

        return $this->buildResponse($response, 200);

    }

    /**
     * 构建API失败后的返回JSON数据格式
     * @param $msg
     * @param string $errorCode
     * @return mixed
     */
    public function buildErrorResponse($msg, $errorCode = '0')
    {

        $response['result'] = 0;
        $response['message'] = $msg;
        $response['error_code'] = $errorCode;

        return $this->buildResponse($response, 200);

    }


    /**
     * 用户输入的参数校验
     * @param $request  laravel的Request对象实例
     * @param $fields   需要校验的参数数组 ['field' => 'description|type|rule',...]
     */
    public function paramsVerify(Request $request, $fields)
    {

        //检测空
        foreach ($fields as $k => $v) {
            $regular = explode('|', $v);

            //字段说明文字
            $name = $regular[0];

            if (1 == count($regular)) {
                //只有说明文字，则按照字符串不为空检测
                if ('' === $request->input($k) || null === $request->input($k)) {
                    $this->verifyParamException($name . '不能为空');
                }

                //清除只检测空的字段
                unset($fields[$k]);
            } else {
                //包含字段类型
                $type = $regular[1];
                if ('array' == $type) {
                    //字段类型是数组，检测数据类型和数组长度
                    $array = $request->input($k);
                    if ('array' != gettype($array)) {
                        $this->verifyParamException($name . '数据类型应为数组');
                    }
                    if (0 == count($array)) {
                        $this->verifyParamException($name . '不能为空');
                    }

                } else {
                    //字段类型是非数组，按照不能空值处理
                    if ('' === $request->input($k) || null === $request->input($k)) {
                        $this->verifyParamException($name . '不能为空');
                    }
                }
            }

        }

        //规则检测
        foreach ($fields as $k => $v) {

            //字符串格式化成数组
            $regular = explode('|', $v);

            //字段说明文字
            $name = $regular[0];

            //字段类型
            $type = $regular[1];

            //要检测的数据
            $value = $request->input($k);

            //是否自定义了类型值的  限定范围
            $self_rule = count($regular) > 2 ? true : false;

            if ('str' == $type) {

                if ($self_rule) {
                    //自定了长度限定
                    $range = explode(':', $regular[2]);

                    if (1 == count($range)) {
                        $this->verifyParamException($name . '的长度范围配置出错');

                    } else {
                        $range[0] = (int)$range[0];
                        $range[1] = (int)$range[1];

                        if (mb_strlen($value) < $range[0]) {
                            $this->verifyParamException($name . '的长度不可以小于' . $range[0]);
                        }

                        if (mb_strlen($value) > $range[1]) {
                            $this->verifyParamException($name . '的长度不可以大于' . $range[1]);

                        }
                    }
                } else {
                    //没有限定长度则按照默认长度计算
                    if (mb_strlen($value) > 85) {
                        $this->verifyParamException($name . '的长度不可以大于85个字节');
                    }
                }

            } else if ('maxStr' == $type) {

                if ($self_rule) {
                    $length = (int)$regular[2];

                    if (mb_strlen($value) > $length) {
                        $this->verifyParamException($name . '的长度不可以大于' . $length . '个字节');
                    }
                } else {
                    if (mb_strlen($value) > 85) {
                        $this->verifyParamException($name . '的长度不可以大于85个字节');
                    }
                }


            } else if ('text' == $type) {
                if (mb_strlen($value) > 21840) {
                    $this->verifyParamException($name . '的长度超过了限制');
                }
            } else if ('int' == $type) {

                //数据类型检车
                $check_type = (int)$value == $value ? true : false;

                if (!$check_type) {
                    $this->verifyParamException($name . '的数据类型应为正整数');
                }

                if ($self_rule) {
                    //自定了长度限定
                    $range = explode(':', $regular[2]);

                    if (1 == count($range)) {
                        $this->verifyParamException($name . '的参数的长度配置范围出错');
                    } else {
                        $range[0] = (int)$range[0];
                        $range[1] = (int)$range[1];

                        if ($value < $range[0]) {
                            $this->verifyParamException($name . '大小不可以小于' . $range[0]);
                        }

                        if ($value > $range[1]) {
                            $this->verifyParamException($name . '大小不可以大于' . $range[1]);
                        }
                    }
                } else {
                    //没有限定长度则按照默认长度计算
                    if (strlen($value) > 10) {
                        $this->verifyParamException($name . '不能大于10位');
                    }
                }
            } else if ('maxInt' == $type) {

                //数据类型检车
                $check_type = (int)$value == $value ? true : false;

                if (!$check_type) {
                    $this->verifyParamException($name . '的数据类型应为正整数');
                }

                if ($value > $regular[2]) {
                    $this->verifyParamException($name . '大小超过了' . $regular[2]);
                }
            } else if ('array' == $type) {

                if ($self_rule) {
                    //自定了长度限定
                    $range = explode(':', $regular[2]);

                    if (1 == count($range)) {
                        $this->verifyParamException($name . '的长度范围配置出错');
                    } else {
                        $range[0] = (int)$range[0];
                        $range[1] = (int)$range[1];

                        if (count($value) < $range[0]) {
                            $this->verifyParamException($name . '的长度不可以小于' . $range[0]);
                        }

                        if (count($value) > $range[1]) {
                            $this->verifyParamException($name . '的长度不可以大于' . $range[1]);
                        }
                    }
                }


            } else if ('maxArray' == $type) {
                if (count($value) > $regular[2]) {
                    $this->verifyParamException($name . '的长度不可以大于' . $regular[2]);
                }
            } else if ('double' == $type) {
                //数据类型检车
                $check_type = (double)$value == $value ? true : false;

                if (!$check_type) {
                    $this->verifyParamException($name . '数据类型应为浮点数');
                }

                if ($self_rule) {
                    //自定了长度限定
                    $range = explode(':', $regular[2]);

                    if (1 == count($range)) {
                        $this->verifyParamException($name . '的长度范围配置出错');
                    } else {
                        $range[0] = (double)$range[0];
                        $range[1] = (double)$range[1];

                        if ($value < $range[0]) {
                            $this->verifyParamException($name . '大小不可以小于' . $range[0]);
                        }

                        if ($value > $range[1]) {
                            $this->verifyParamException($name . '大小不可以大于' . $range[1]);
                        }
                    }
                } else {
                    //没有限定长度则按照默认长度计算
                    if (strlen($value) > 10) {
                        $this->verifyParamException($name . '不能大于10位');
                    }
                }
            } else if ('maxDouble' == $type) {

                //数据类型检车
                $check_type = (double)$value == $value ? true : false;

                if (!$check_type) {
                    $this->verifyParamException($name . '数据类型应为浮点数');
                }

                if ($value > $regular[2]) {
                    $this->verifyParamException($name . '大小超过了' . $regular[2]);
                }

            } else if ('mobile' == $type) {

                if (!$this->mobileRegular($value)) {
                    $this->verifyParamException($name . '手机号格式错误');
                }
            } else if ('email' == $type) {
                if (!$this->emailRegular($value)) {
                    $this->verifyParamException($name . '邮箱格式错误');
                }
            } else if ('enum' == $type) {
                $range = explode(':', $regular[2]);

                if (0 == count($range)) {
                    $this->verifyParamException('枚举参数配置错误');
                }
                if (!in_array($value, $range)) {
                    $this->verifyParamException($name . '不在限定范围');
                }

            } else if ('positiveDouble' == $type) {
                //数据类型检车
                $check_type = (double)$value == $value ? true : false;

                if (!$check_type) {
                    $this->verifyParamException($name . '数据类型应为浮点数');
                }

                if (0 >= (double)$value) {
                    $this->verifyParamException($name . '不能为小于零的浮点数');
                }
            } else if ('positiveInt' == $type) {

                //数据类型检车
                $check_type = (int)$value == $value ? true : false;

                if (!$check_type) {
                    $this->verifyParamException($name . '数据类型应为整数');
                }

                if (0 >= (int)$value) {
                    $this->throwException($name . '不能为小于零的整数');
                }

            } else if ('money' == $type) {
                if (!$this->moneyRegular($value)) {
                    $this->verifyParamException($name . '格式错误');
                }
            } else if ('fakeBoolean' == $type) {

                $Boolean = [0, 1];

                if (!in_array($value, $Boolean)) {
                    $this->verifyParamException($name . '参数范围限定为0和1');
                }
            } else {
                $this->verifyParamException('参数类型配置错误');

            }

        }
    }

    /**
     * 手机号正则校验
     * @param $mobile
     * @return bool
     */
    public function mobileRegular($mobile)
    {

        if (preg_match("/^1[345678]\d{9}$/", $mobile)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 邮箱正则校验
     * @param $email
     * @return bool
     */
    public function emailRegular($email)
    {

        if (preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/", $email)) {
            return true;
        } else {
            return false;
        }
    }


    public function moneyRegular($money)
    {

        if (preg_match("/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/", $money)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取未来几个月
     * @param string $date 日期，2018-12-05 16:04:24  或者 time()的时间戳格式
     * @param int $months 未来的月数  默认1个月
     * @param int $time 返回值是否带时分秒
     * @return false|int
     */
    public function natureNextMonths($date = '', $months = 1, $time = 0)
    {

        if ('' == $date) {
            //如果日期为空，则取当前时间戳
            if (1 == $time) {
                $timestamp = time();
            } else {
                $timestamp = strtotime(date('Y-m-d', time()));
            }

        } else {

            if (is_numeric($date)) {
                if (1 == $time) {
                    $timestamp = $date;
                } else {
                    $timestamp = strtotime(date('Y-m-d', $date));
                }

            } else {
                //如果传入的时间格式为日期的字符串格式
                $timestamp = strtotime($date);
                if (1 != $time) {
                    $timestamp = strtotime(date('Y-m-d', $timestamp));
                }
            }
        }

        $date_arr = explode('-', date("n-j", $timestamp));

        //获取明天的月日
        $next_arr = explode('-', date("n-j", strtotime("+1 day", $timestamp)));

        //判断当前是不是当前月的最后一天
        if ($next_arr[0] != $date_arr[0]) {
            //如果月变了，说明是最后一天
            return strtotime("last day of +$months month", $timestamp);
        } else {
            //否则是非第一天和最后一天的日期，不做任何修正
            return strtotime("+$months month", $timestamp);
        }

    }


    /**
     * 两个日期之间相差几个自然月
     * @param $start_time
     * @param $end_time
     * @param int $limit
     * @return int
     */
    public function natureDifferMonths($start_time, $end_time, $limit = 18)
    {

        $months = 0;
        for ($i = 1; $i <= $limit + 1; $i++) {

            $future_months = $this->natureNextMonths($start_time, $i);

            if ($future_months > $end_time || $i == $limit + 1) {

                $months = $i - 1;
                break;
            }
        }

        return $months;
    }

    /**
     * 将时间戳格式化成模糊时间
     * @param $sTime
     * @param string $type
     * @param string $alt
     * @return false|string
     */
    public function friendlyDate($sTime, $type = 'normal', $alt = 'false')
    {
        if (!$sTime)
            return '';

        //sTime=源时间，cTime=当前时间，dTime=时间差
        $cTime = time();
        $dTime = $cTime - $sTime;
        $dDay = intval(date("z", $cTime)) - intval(date("z", $sTime));
        //$dDay     =   intval($dTime/3600/24);
        $dYear = intval(date("Y", $cTime)) - intval(date("Y", $sTime));
        //normal：n秒前，n分钟前，n小时前，日期
        if ($type == 'normal') {
            if ($dTime < 60) {
                if ($dTime < 10) {
                    return '刚刚';    //by yangjs
                } else {
                    return intval(floor($dTime / 10) * 10) . "秒前";
                }
            } elseif ($dTime < 3600) {
                return intval($dTime / 60) . "分钟前";
                //今天的数据.年份相同.日期相同.
            } elseif ($dYear == 0 && $dDay == 0) {
                //return intval($dTime/3600)."小时前";
                return date('H:i', $sTime);
            } elseif ($dYear == 0) {
                return date("m月d日", $sTime);
            } else {
                return date("Y-m-d", $sTime);
            }
        } elseif ($type == 'mohu') {
            if ($dTime < 60) {
                return $dTime . "秒前";
            } elseif ($dTime < 3600) {
                return intval($dTime / 60) . "分钟前";
            } elseif ($dTime >= 3600 && $dDay == 0) {
                return intval($dTime / 3600) . "小时前";
            } elseif ($dDay > 0 && $dDay <= 7) {
                return intval($dDay) . "天前";
            } elseif ($dDay > 7 && $dDay <= 30) {
                return intval($dDay / 7) . '周前';
            } elseif ($dDay > 30) {
                return intval($dDay / 30) . '个月前';
            }
            //full: Y-m-d , H:i:s
        } elseif ($type == 'full') {
            return date("Y-m-d , H:i:s", $sTime);
        } elseif ($type == 'ymd') {
            return date("Y-m-d", $sTime);
        } else if ($type == 'oneday') {

            if ($dDay == 0) {
                return date('H:i', $sTime);
            } else {
                return date('m-d H:i', $sTime);
            }
        } else {
            if ($dTime < 60) {
                return $dTime . "秒前";
            } elseif ($dTime < 3600) {
                return intval($dTime / 60) . "分钟前";
            } elseif ($dTime >= 3600 && $dDay == 0) {
                return intval($dTime / 3600) . "小时前";
            } elseif ($dYear == 0) {
                return date("Y-m-d H:i:s", $sTime);
            } else {
                return date("Y-m-d H:i:s", $sTime);
            }
        }
    }


    /**
     * 构建JSON格式的API返回数据
     * @param $response
     * @param $status_code
     * @return mixed
     */
    private function buildResponse($response, $status_code)
    {

        return response()->json($response, $status_code);
    }

    /**
     * 抛出异常
     * @param $msg
     */
    private function throwException($msg, $code = 200)
    {

        throw new FunctionException($msg, $code);
    }

    //获取http请求的URL
    private function getUrl($url)
    {

        $urls = config('functions.urls');

        if (array_key_exists($url, $urls)) {
            return $urls[$url];
        } else {
            return $url;
        }
    }


    private function curlResponse($response, $extension)
    {

        $body = $response->getBody();
        $response_content = $body->getContents();

        $extension['response_code'] = $response->getStatusCode();
        $extension['response_content'] = $response_content;

        if ('200' != $response->getStatusCode()) {
            //状态码错误
            $exception['error_code'] = 20001;

            $this -> httpException($exception,$extension);
        }

        $arr = json_decode($response_content, true);

        if(null === $arr){
            //响应的数据结构非JSON格式

            $exception['error_code'] = 20002;

            $this -> httpException($exception,$extension);

        }else{
            return $arr;
        }
    }

    /**
     * http 请求异常
     * @param $exception
     * @param array $extension
     */
    private function httpException($exception, $extension = [])
    {

        $exception['message'] = $this->httpErrorCode[$exception['error_code']];

        if ($this->devEnv) {

            $exception['extension'] = $extension;
        }

        $status_code = config('functions.http_error_status_code');

        $this -> throwException(json_encode($exception), $status_code);
    }

    /**
     *
     * 校验参数时异常处理
     * @param $message
     */
    private function verifyParamException($message){

        $status_code = config('functions.verify_param_status_code');

        $this -> throwException($message,$status_code);

    }
}