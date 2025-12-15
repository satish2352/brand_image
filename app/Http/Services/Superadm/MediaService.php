<?php
namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\MediaRepository;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class MediaService
{
    protected $repo;
    public function __construct()
    {
        $this->repo = new MediaRepository();
    }

    public function list()
    {
        return $this->repo->list();
    }

    public function save($req)
    {
        // Save master record
        $data = [
            'state_id' => $req->state_id,
            'district_id' => $req->district_id,
            'city_id' => $req->city_id,
            'location_name' => $req->location_name,
            'type_id' => $req->type_id,
            'radius_id' => $req->radius_id ?: null,
            'price' => $req->price ?: null,
            'address' => $req->address ?: null,
            'description' => $req->description ?: null,
            'status' => $req->status ?? 'Available',
        ];

        $media = $this->repo->save($data);

        // handle images (should be validated before)
        if ($req->hasFile('images')) {
            foreach ($req->file('images') as $file) {
                // store under public/uploads/media/YYYYMM/
                $folder = 'uploads/media/'.date('Ym');
                $filename = time().'_'.Str::random(6).'_'.$file->getClientOriginalName();
                $path = $file->storeAs($folder, $filename, 'public');
                $this->repo->addImage([
                    'media_id' => $media->id,
                    'filename' => $filename,
                    'path' => $path,
                ]);
            }
        }

        return $media;
    }

    public function edit($id)
    {
        return $this->repo->edit($id);
    }

    public function update($req)
    {
        $data = [
            'state_id' => $req->state_id,
            'district_id' => $req->district_id,
            'city_id' => $req->city_id,
            'location_name' => $req->location_name,
            'type_id' => $req->type_id,
            'radius_id' => $req->radius_id ?: null,
            'price' => $req->price ?: null,
            'address' => $req->address ?: null,
            'description' => $req->description ?: null,
            'status' => $req->status ?? 'Available',
        ];

        $this->repo->update($data, $req->id);

        // new images (optional)
        if ($req->hasFile('images')) {
            foreach ($req->file('images') as $file) {
                $folder = 'uploads/media/'.date('Ym');
                $filename = time().'_'.Str::random(6).'_'.$file->getClientOriginalName();
                $path = $file->storeAs($folder, $filename, 'public');
                $this->repo->addImage([
                    'media_id' => $req->id,
                    'filename' => $filename,
                    'path' => $path,
                ]);
            }
        }

        return true;
    }

    public function delete($req)
    {
        $id = base64_decode($req->id);
        return $this->repo->delete($id);
    }

    public function find($id)
    {
        return $this->repo->edit($id);
    }

    // soft delete image
    public function softDeleteImage($imageId)
    {
        return $this->repo->softDeleteImage($imageId);
    }
}
