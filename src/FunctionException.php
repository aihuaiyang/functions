<?php

namespace Huaiyang\Functions;

use Exception;
use Illuminate\Http\Response;

class FunctionException extends Exception
{
    public function __construct(string $message = "", int $code = 200)
    {
        parent::__construct($message, $code);
    }

    public function render()
    {

        //构建异常返回数组结构
        $exception['result'] = 0;

        //处理message
        $response = json_decode($this->message,true);

        if (null === $response) {
            $exception['message'] = $response;
        }else{

            if(array_key_exists('message',$response)){
                $exception['message'] = $response['message'];
            }

            if(array_key_exists('message',$response)){
                $exception['extension'] = $response['extension'];
            }

            if(array_key_exists('error_code',$response)){
                $exception['error_code'] = $response['error_code'];
            }

        }

        return response() -> json($exception,$this -> code);

    }
}