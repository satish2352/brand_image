<?php
namespace App\Http\Repository\Superadm;

use App\Models\State;
use Exception;
use Log;

class StateRepository
{
    public function list()
    {
        return State::where('is_deleted', 0)->orderBy('id', 'desc')->get();
    }

    public function save($data)
    {
        return State::create($data);
    }

    public function edit($id)
    {
        return State::find($id);
    }

    public function update($data, $id)
    {
        return State::where('id', $id)->update($data);
    }

    public function delete($data, $id)
    {
        return State::where('id', $id)->update($data);
    }
}
