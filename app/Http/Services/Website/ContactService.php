<?php

namespace App\Http\Services\Website;

use App\Http\Repository\Website\ContactRepository;
use Illuminate\Support\Facades\DB;

class ContactService
{
    protected $contactRepo;

    public function __construct(ContactRepository $contactRepo)
    {
        $this->contactRepo = $contactRepo;
    }

    public function save(array $data)
    {
        DB::beginTransaction();
        try {
            $this->contactRepo->store($data);
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
