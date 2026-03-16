<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Router extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function subscribers()
    {
        return $this->hasMany(Subscriber::class, 'router_id');
    }

    public function nas()
    {
        return $this->hasOne(Nas::class, 'nasname', 'wan_ip');
    }
}
