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
        return response()->json(['result' => 0, 'message' => $this->message], $this->code);
    }
}