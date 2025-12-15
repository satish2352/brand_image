<?php
namespace App\Http\Repository\Superadm;

use App\Models\Media;
use App\Models\MediaImage;

class MediaRepository
{
    public function list()
    {
        return Media::with(['state','district','city','images','type','radius'])
            ->where('is_deleted', 0)
            ->orderBy('id','desc')
            ->get();
    }

    public function save($data)
    {
        return Media::create($data);
    }

    public function edit($id)
    {
        return Media::with(['images'])->find($id);
    }

    public function update($data, $id)
    {
        return Media::where('id',$id)->update($data);
    }

    public function delete($id)
    {
        return Media::where('id',$id)->update(['is_deleted' => 1]);
    }

    public function addImage($data)
    {
        return MediaImage::create($data);
    }

    public function softDeleteImage($id)
    {
        return MediaImage::where('id',$id)->update(['is_deleted' => 1]);
    }
}
