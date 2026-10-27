<?php

namespace App\Models;

use App\Concerns\BelongsToUser;
use Database\Factories\DeadlineNotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log degli invii push di una scadenza: al massimo uno per giorno (RN5).
 */
class DeadlineNotification extends Model
{
    /** @use HasFactory<DeadlineNotificationFactory> */
    use BelongsToUser, HasFactory;

    protected $fillable = [
        'user_id',
        'deadline_id',
        'days_until_due',
        'sent_on',
    ];

    protected function casts(): array
    {
        return [
            'days_until_due' => 'integer',
            'sent_on' => 'date',
        ];
    }

    public function deadline(): BelongsTo
    {
        return $this->belongsTo(Deadline::class);
    }
}
