<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\VerificationCodeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Overtrue\EasySms\EasySms;


class VerificationCodesController extends Controller
{
    public function store(VerificationCodeRequest $request, EasySms $easySms)
    {
        $phone = $request->phone;
        try {
            if (!app()->environment('production')) {
                $code = '1234';
            } else {
                // 生成4位随机数，左侧补0
                $code = str_pad(random_int(1, 9999), 4, 0, STR_PAD_LEFT);
                $easySms->send($phone, [
                    'content'  => '您的验证码为: {$code}',
                    'template' => 'SMS_190725409',
                    'data' => [
                        'code' => $code
                    ],
                ]);
            }
            $key = 'verificationCode_' . str_random(15);
            $expiredAt = now()->addMinutes(10);
            // 缓存验证码 10分钟过期。
            Cache::put($key, ['phone' => $phone, 'code' => $code], $expiredAt);
            return $this->response->array([
                'key' => $key,
                'expired_at' => $expiredAt->toDateTimeString(),
            ])->setStatusCode(201);
        } catch (\Overtrue\EasySms\Exceptions\NoGatewayAvailableException $exception) {
            $message = $exception->getException('aliyun')->getMessage();
            return $this->response->errorInternal($message ?: 'error');
        }
    }
}
