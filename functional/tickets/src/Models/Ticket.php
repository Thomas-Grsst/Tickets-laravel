<?php

namespace Functional\Tickets\Models;

use Carbon\CarbonInterface;
use Functional\Tickets\Database\Factories\TicketFactory;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Policies\TicketPolicy;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Lomkit\Access\Controls\HasControl;

/**
 * @property int             $id
 * @property int             $requester_id
 * @property ?int            $assigned_technician_id
 * @property string          $title
 * @property string          $description
 * @property TicketStatus    $status
 * @property TicketPriority  $priority
 * @property ?CarbonInterface $resolved_at
 * @property ?bool           $sla_met
 * @property CarbonInterface  $created_at
 * @property CarbonInterface  $updated_at
 * @property ?CarbonInterface $deleted_at
 */
#[Fillable([
    'requester_id',
    'assigned_technician_id',
    'title',
    'description',
    'status',
    'priority',
    'resolved_at',
])]
#[UseFactory(TicketFactory::class)]
#[UsePolicy(TicketPolicy::class)]
class Ticket extends Model
{
    use HasControl;
    use HasFactory;
    use Prunable;
    use SoftDeletes;

    private const SOFT_DELETED_RETENTION_DAYS = 90;

    /**
     * Every ticket's life starts at the first state of the lifecycle table, whichever
     * entry point creates it.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => TicketStatus::Open->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'resolved_at' => 'datetime',
            'sla_met' => 'boolean',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function prunable(): Builder
    {
        return static::where('deleted_at', '<=', now()->subDays(self::SOFT_DELETED_RETENTION_DAYS));
    }
}
