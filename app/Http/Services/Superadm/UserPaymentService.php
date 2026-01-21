<?php

namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\UserPaymentRepository;

class UserPaymentService
{
    protected $repo;

    public function __construct(UserPaymentRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list()
    {
        $data_output =  $this->repo->list();

        return $data_output;
    }
    public function getOrderDetails($orderId)
    {
        return $this->repo->getOrderDetails($orderId);
    }
}
