<?php

namespace App\Models\SuperAdm;

use Illuminate\Database\Eloquent\Model;

class SuperLogin extends Model
{
    protected $table='u_superadm';
    protected $primeryKey='id';
    public $timestamps=false;
    protected $fillable=[];
}
