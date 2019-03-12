<?php
namespace Huaiyang\Functions;

use Illuminate\Http\Response;

class Functions{
    public function hello(){
        echo 'hello world';
    }


    private static function throw_exception($msg){
        throw new FunctionException($msg);
    }
    public static function buildSuccessResponse($data=[],$msg='success',$status_code=0){


        $response['result'] = 1;
        $response['message'] = $msg;
        $response['data'] = $data;
        $response['status_code'] = $status_code;

        return self::buildResponse($response,200);

    }


    public static function buildErrorResponse($msg,$status_code=0){

        $response['result'] = 0;
        $response['message'] = $msg;
        $response['status_code'] = $status_code;

        return self::buildResponse($response,200);

    }

    public static function curlPost($url, $data = null){

        $response = self::curl($url,$data);

        return $response;

    }

    public static function curlPostJsonArr($url,$data=[]){

        $response = self::curlPost($url,$data);

        return json_decode($response,true);

    }

    public static function curlGet($url, $data = null){

        if (!empty($data)){

            foreach ($data as $k => $v){
                $data[$k] = $k.'='.$v;
            }

            $params = implode('&',$data);

            $url .= '?'.$params;
        }

        $response = self::curl($url);

        return $response;

    }

    public static function curlGetJsonArr($url,$data = null){

        $response = self::curlPost($url,$data);

        return json_decode($response,true);

    }

    public static function paramsVerify($request,$fileds){

        //检测空
        foreach ($fileds as $k => $v){
            $regular = explode('|',$v);

            //字段说明文字
            $name = $regular[0];

            if(1 == count($regular)){
                if('' === $request -> input($k) || 'no' === $request -> input($k,'no') || null === $request -> input($k)){
                    self::throw_exception($name.'参数不能为空');
                }
            }else{
                //字段类型
                $type = $regular[1];
                if('array' == $type){
                    $array = $request -> input($k);
                    if('array' != gettype($array)){
                        self::throw_exception($name.'数据类型应为数组');
                    }
                    if(0 == count($array)){
                        self::throw_exception($name.'参数不能为空');
                    }

                }else{
                    if('' === $request -> input($k) || 'no' === $request -> input($k,'no') || null === $request -> input($k)){
                        self::throw_exception($name.'参数不能为空');
                    }
                }
            }

        }

        //规则检测
        foreach($fileds as $k => $v) {

            $regular = explode('|', $v);
            //字段说明文字
            $name = $regular[0];

            if (1 == count($regular)) {
                //如果只检测了空
                continue;
            }

            //字段类型
            $type = $regular[1];
            //要检测的数据
            $value = $request->input($k);

            //是否自定义了类型值的  限定范围
            $self_rule = count($regular) > 2 ? true: false;

            if('str' == $type) {

                if ($self_rule) {
                    //自定了长度限定
                    $range = explode(':', $regular[2]);

                    if (1 == count($range)) {
                        self::throw_exception($name.'参数的长度范围配置出错');

                    } else {
                        $range[0] = (int)$range[0];
                        $range[1] = (int)$range[1];

                        if (mb_strlen($value) < $range[0]) {
                            self::throw_exception($name . '长度不可以小于' . $range[0]);
                        }

                        if (mb_strlen($value) > $range[1]) {
                            self::throw_exception($name . '长度不可以大于' . $range[1]);

                        }
                    }
                } else {
                    //没有限定长度则按照默认长度计算
                    if (mb_strlen($value) > 85) {
                        self::throw_exception($name.'长度不可以大于85个字节');
                    }
                }

            }else if('maxStr' == $type){

                if($self_rule){
                    $length = (int)$regular[2];

                    if (mb_strlen($value) > $length) {
                        self::throw_exception($name . '长度不可以大于'.$length.'个字节');
                    }
                }else{
                    if (mb_strlen($value) > 85) {
                        self::throw_exception($name.'长度不可以大于85个字节');
                    }
                }


            }else if('text' == $type){
                if(strlen($value) > 65535 - 10){
                    self::throw_exception($name.'长度超过了限制');
                }
            }else if('int' == $type){

                //数据类型检车
                $check_type = (int)$value == $value ? true: false;

                if(!$check_type){
                    self::throw_exception($name.'数据类型应为正整数');
                }

                if ($self_rule) {
                    //自定了长度限定
                    $range = explode(':', $regular[2]);

                    if (1 == count($range)) {
                        self::throw_exception($name.'参数的长度配置范围出错');
                    } else {
                        $range[0] = (int)$range[0];
                        $range[1] = (int)$range[1];

                        if ($value < $range[0]) {
                            self::throw_exception($name . '大小不可以小于' . $range[0]);
                        }

                        if ($value > $range[1]) {
                            self::throw_exception($name . '大小不可以大于' . $range[1]);
                        }
                    }
                } else {
                    //没有限定长度则按照默认长度计算
                    if (strlen($value) > 10) {
                        self::throw_exception($name.'不能大于10位');
                    }
                }
            }else if('maxInt' == $type){

                //数据类型检车
                $check_type = (int)$value == $value ? true: false;

                if(!$check_type){
                    self::throw_exception($name.'数据类型应为正整数');
                }

                if($value > $regular[2]){
                    self::throw_exception($name . '大小超过了'.$regular[2]);
                }
            }else if('array' == $type){

                if($self_rule){
                    //自定了长度限定
                    $range = explode(':', $regular[2]);

                    if (1 == count($range)) {
                        self::throw_exception($name.'参数的长度范围配置出错');
                    } else {
                        $range[0] = (int)$range[0];
                        $range[1] = (int)$range[1];

                        if (count($value) < $range[0]) {
                            self::throw_exception($name . '的长度不可以小于' . $range[0]);
                        }

                        if (count($value) > $range[1]) {
                            self::throw_exception($name . '的长度不可以大于' . $range[1]);
                        }
                    }
                }


            }else if('maxArray' == $type){
                if(count($value) > $regular[2]){
                    self::throw_exception($name . '的长度不可以大于' . $regular[2]);
                }
            }else if('double' == $type){
                //数据类型检车
                $check_type = (double)$value == $value ? true: false;

                if(!$check_type){
                    self::throw_exception($name . '数据类型应为浮点数');
                }

                if ($self_rule) {
                    //自定了长度限定
                    $range = explode(':', $regular[2]);

                    if (1 == count($range)) {
                        self::throw_exception($name . '参数的长度范围配置出错');
                    } else {
                        $range[0] = (double)$range[0];
                        $range[1] = (double)$range[1];

                        if ($value < $range[0]) {
                            self::throw_exception($name . '大小不可以小于' . $range[0]);
                        }

                        if ($value > $range[1]) {
                            self::throw_exception($name . '大小不可以大于' . $range[1]);
                        }
                    }
                } else {
                    //没有限定长度则按照默认长度计算
                    if (strlen($value) > 10) {
                        self::throw_exception($name.'不能大于10位');
                    }
                }
            }else if('maxDouble' == $type){

                //数据类型检车
                $check_type = (double)$value == $value ? true: false;

                if(!$check_type){
                    self::throw_exception($name.'数据类型应为浮点数');
                }

                if($value > $regular[2]){
                    self::throw_exception($name . '大小超过了'.$regular[2]);
                }

            } else if ('mobile' == $type) {

                if (!self::mobile_regular($value)) {
                    self::throw_exception('手机号格式错误');
                }
            } else if ('email' == $type) {
                if (!self::email_regular($value)) {
                    self::throw_exception('邮箱格式错误');
                }
            } else if ('enum' == $type) {
                $range = explode(':', $regular[2]);

                if(0 == count($range)){
                    self::throw_exception('枚举参数配置错误');
                }
                if (!in_array($value, $range)) {
                    self::throw_exception('枚举参数不在限定范围');
                }

            } else if('positiveDouble' == $type){
                //数据类型检车
                $check_type = (double)$value == $value ? true: false;

                if(!$check_type){
                    self::throw_exception($name.'数据类型应为浮点数');
                }

                if( 0 >= (double)$value){
                    self::throw_exception($name.'不能为小于零的浮点数');
                }
            } else if('positiveInt' == $type){

                //数据类型检车
                $check_type = (int)$value == $value ? true: false;

                if(!$check_type){
                    self::throw_exception($name.'数据类型应为浮点数');
                }

                if( 0 >= (int)$value){
                    self::throw_exception($name.'不能为小于零的整数');
                }

            }else{
                self::throw_exception('参数类型配置错误');

            }

        }
    }

    //手机号正则校验
    public static function mobile_regular($mobile){

        if(preg_match("/^1[345678]\d{9}$/",$mobile)){
            return true;
        }else{
            return false;
        }
    }

    //邮箱正则校验
    public static function email_regular($email){

        if(preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/",$email)){
            return true;
        }else{
            return false;
        }
    }

    //获取未来几个月
    /*
     *  $date 日期，2018-12-05 16:04:24  或者 time()的时间戳格式
     *  $months 未来的月数  默认1个月
     *  $time  返回值是否带时分秒
     */

    public static function nature_next_months($date='',$months=1,$time=0){

        if('' == $date){
            //如果日期为空，则取当前时间戳
            if(1 == $time){
                $timestamp = time();
            }else{
                $timestamp = strtotime(date('Y-m-d',time()));
            }

        }else{

            if(is_numeric($date)){
                if(1 == $time){
                    $timestamp = $date;
                }else{
                    $timestamp = strtotime(date('Y-m-d',$date));
                }

            }else{
                //如果传入的时间格式为日期的字符串格式
                $timestamp = strtotime($date);
                if(1 != $time){
                    $timestamp = strtotime(date('Y-m-d',$timestamp));
                }
            }
        }

        $date_arr = explode('-',date("n-j",$timestamp));

        //获取明天的月日
        $next_arr = explode('-',date("n-j",strtotime("+1 day",$timestamp)));

        //判断当前是不是当前月的最后一天
        if($next_arr[0] != $date_arr[0]){
            //如果月变了，说明是最后一天
            return strtotime("last day of +$months month",$timestamp);
        }else{
            //否则是非第一天和最后一天的日期，不做任何修正
            return strtotime("+$months month",$timestamp);
        }

    }

    //两个日期之间相差几个自然月
    public static function nature_differ_months($start_time,$end_time,$limit=18){

        $months = 0;
        for($i=1;$i<=$limit + 1;$i++){

            $feture_months = nature_next_months($start_time,$i);

            if($feture_months > $end_time  || $i == $limit + 1){

                $months = $i-1;
                break;
            }
        }

        return $months;
    }


    private static function curl($url,$data=null){

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        $httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        curl_close($curl);
        if('200' == $httpCode){
            return $output;
        }else{
            return false;
        }

    }

    private static function buildResponse($response,$status_code){

        return response() -> json($response,$status_code);
    }


}