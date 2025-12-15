<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media_master';
    protected $fillable = [
        'state_id','district_id','city_id',
        'location_name','type_id','radius_id',
        'price','address','description','status',
        'is_active','is_deleted'
    ];

    public function state() { return $this->belongsTo(State::class); }
    public function district() { return $this->belongsTo(District::class); }
    public function city() { return $this->belongsTo(City::class); }
    public function type() { return $this->belongsTo(\DB::getTablePrefix() ? \App\Models\Type::class : \App\Models\Type::class); } // optional
    public function radius() { return $this->belongsTo(\App\Models\RadiusMaster::class, 'radius_id'); }
    public function images() { return $this->hasMany(MediaImage::class, 'media_id')->where('is_deleted',0); }
}
