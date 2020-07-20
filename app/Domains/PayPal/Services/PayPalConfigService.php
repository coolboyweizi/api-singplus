<?php
/**
 * Created by PhpStorm.
 * User: zhangyujie
 * Date: 2018/9/4
 * Time: 下午5:30
 */

namespace SingPlus\Domains\PayPal\Services;


use SingPlus\Contracts\PayPal\Services\PayPalConfigService as PayPalConfigServiceContract;
use SingPlus\Domains\PayPal\Repositories\PayPalConfigRepository;

class PayPalConfigService implements PayPalConfigServiceContract
{
    private $payPalConfigRepository;
    public function __construct(PayPalConfigRepository $payPalConfigRepository)
    {
        $this->payPalConfigRepository=$payPalConfigRepository;
    }
    public function getPayPalStatus(string $appName) : \stdClass
    {
        $payPalInfo=$this->payPalConfigRepository->findOneAppName($appName);
        if(!$payPalInfo){
            return (object) [
                'isOpen'         => false,
                'url'            => null,
            ];
        }
        return (object) [
            'isOpen'         => $payPalInfo->is_open,
            'url'            => $payPalInfo->url,
        ];
    }

}