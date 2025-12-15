<?php

namespace App\Models\SuperAdm;

use Illuminate\Database\Eloquent\Model;

class UBoard extends Model
{
    protected $table='u_board';
    protected $primeryKey='id';
    public $timestamps=false;
    protected $fillable=[];
}
