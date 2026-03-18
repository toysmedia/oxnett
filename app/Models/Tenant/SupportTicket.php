<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Support ticket submitted by a PPPoE customer to their ISP.
 *
 * @property int $id
 * @property int|null $customer_id
 * @property string $subject
 * @property string $message
 * @property string $status
 * @property string $priority
 */
class SupportTicket extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';

    protected $table = 'support_tickets';

    protected $fillable = [
        'customer_id',
        'subject',
        'message',
        'status',
        'priority',
    ];

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope to open tickets.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope to a specific priority.
     */
    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }
}
