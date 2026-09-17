<?php

namespace Functional\Tickets\Models;

use Functional\Tickets\Database\Factories\TicketFactory;
use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Enums\TicketStatus;
use Functional\Tickets\Policies\TicketPolicy;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Lomkit\Access\Controls\HasControl;
use Technical\Framework\Concerns\HasChangeHistory;
use Technical\Framework\Concerns\HistorizesChanges;

/**
 * @property int $id
 * @property int $requester_id
 * @property int|null $assigned_technician_id
 * @property string $title
 * @property string $description
 * @property TicketStatus $status
 * @property TicketPriority $priority
 * @property Carbon|null $resolved_at
 * @property bool|null $sla_met
 * @property Carbon|null $escalated_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $requester
 * @property-read User|null $assignedTechnician
 * @property-read Collection<int, Comment> $comments
 * @property-read Collection<int, Attachment> $attachments
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
class Ticket extends Model implements HistorizesChanges
{
    use HasChangeHistory;
    use HasControl;

    /** @use HasFactory<TicketFactory> */
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'resolved_at' => 'datetime',
            'sla_met' => 'boolean',
            'escalated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    /** @return HasMany<Comment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** @return HasMany<Attachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /** @return Builder<Ticket> */
    public function prunable(): Builder
    {
        return static::where('deleted_at', '<=', now()->subDays(self::SOFT_DELETED_RETENTION_DAYS));
    }

    /** @return list<string> */
    public function historizedAttributes(): array
    {
        return ['status', 'priority', 'assigned_technician_id'];
    }
}
