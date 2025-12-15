<?php
namespace App\Models\SuperAdm;
use Illuminate\Database\Eloquent\Model;

class MSchool extends Model
{
    protected $table='u_schools';
    protected $primeryKey='id';
    public $timestamps=false;
    protected $fillable=[];
}
