<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscriber extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = ['id'];
    protected $hidden = ['password_hash', 'radius_password'];
    protected $casts = ['expires_at' => 'datetime'];

    public function package()
    {
        return $this->belongsTo(IspPackage::class, 'isp_package_id');
    }

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function payments()
    {
        return $this->hasMany(MpesaPayment::class, 'subscriber_id');
    }

    public function sessions()
    {
        return $this->hasMany(Radacct::class, 'username', 'username');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
