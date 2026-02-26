<?php

namespace App\Http\Services\Superadm;

use App\Http\Repository\Superadm\ContactUsRepository;
use App\Models\ContactUs;
use App\Models\MediaImage;


class ContactUsService
{
    protected $repo;

    public function __construct(ContactUsRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list()
    {
        return $this->repo->list();
    }

    public function delete($id)
    {
        return $this->repo->delete($id);
    }

    public function getById($id)
    {
        $contact = $this->repo->findById($id);

        if (!$contact) {
            return null;
        }

        // MEDIA IMAGES FETCH
        if (!empty($contact->media_id)) {
            $contact->images = MediaImage::where('media_id', $contact->media_id)
                ->where('is_deleted', 0)
                ->pluck('images')
                ->toArray();
        } else {
            $contact->images = [];
        }

        return $contact;
    }
}
