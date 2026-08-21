<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $student_id
 * @property int|null $tutor_id
 * @property int|null $user_id
 * @property string $recipient_type TUTOR (WhatsApp al tutor familiar) | STUDENT | TEACHER (ambas en la app, via user_id)
 * @property string|null $batch_id Agrupa las filas creadas por un mismo envio (un aviso a N destinatarios) para que el log muestre una sola entrada
 * @property int|null $sent_by_user_id
 * @property string $title
 * @property string $message
 * @property string $type
 * @property bool $is_read
 * @property Carbon|null $sent_at
 */
class AppNotification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'student_id',
        'tutor_id',
        'user_id',
        'recipient_type',
        'batch_id',
        'sent_by_user_id',
        'title',
        'message',
        'type',
        'is_read',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class, 'tutor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
