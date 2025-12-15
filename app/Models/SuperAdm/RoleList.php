<?php
namespace App\Models\SuperAdm;
use Illuminate\Database\Eloquent\Model;

class RoleList extends Model
{
    protected $table='u_rolefacility';
    protected $primeryKey='id';
    public $timestamps=false;
    protected $fillable=[];
}
