<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $fillable = ['district', 'state_id', 'is_active', 'is_deleted'];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function cities()
    {
        return $this->hasMany(City::class);
    }
}
