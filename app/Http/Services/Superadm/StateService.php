<?php
namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\StateRepository;
use Exception;

class StateService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new StateRepository();
    }

    public function list()
    {
        return $this->repo->list();
    }

    public function save($req)
    {
        $data = [
            'state' => $req->state,
        ];
        return $this->repo->save($data);
    }

    public function edit($id)
    {
        return $this->repo->edit($id);
    }

    public function update($req)
    {
        $data = [
            'state' => $req->state,
        ];
        return $this->repo->update($data, $req->id);
    }

    public function delete($req)
    {
        $id = base64_decode($req->id);

        // check where used (Example: cities table)
        $count = \DB::table('cities')->where('state_id', $id)->count();

        if ($count > 0) {
            throw new Exception("This state is assigned to cities. Cannot delete.");
        }

        return $this->repo->delete(['is_deleted' => 1], $id);
    }

    public function find($id)
    {
        try {
            return $this->repo->edit($id);
        } catch (Exception $e) {
            \Log::error("StateService find error: " . $e->getMessage());
            return null;
        }
    }

}
