<?php
namespace app\controller;


class BaseApi {
    protected function response($code=200, $msg='success', $data=[]) {
        $res = [
            'code'  =>  $code,
            'msg'   =>  $msg,
            'data'  =>  $data
        ];

        json($res)->send();
    }

    protected function success($data=[], $code=200, $msg='success') {
        $this->response( $code, $msg, $data);
    }

    protected function fail($msg, $code=400, $data=[]) {
        $this->response($code, $msg, $data);
    }

}

?>