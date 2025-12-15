<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaImage extends Model
{
    protected $table = 'media_images';
    protected $fillable = ['media_id','filename','path','is_deleted'];

    public function media() { return $this->belongsTo(Media::class, 'media_id'); }
}
