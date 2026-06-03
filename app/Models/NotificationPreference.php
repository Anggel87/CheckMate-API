<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tutor_id
 * @property bool $absences
 * @property bool $lates
 * @property bool $incidents
 * @property bool $justifications
 * @property bool $claims
 * @property bool $announcements
 */
class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'tutor_id',
        'absences',
        'lates',
        'incidents',
        'justifications',
        'claims',
        'announcements',
    ];

    protected function casts(): array
    {
        return [
            'absences' => 'boolean',
            'lates' => 'boolean',
            'incidents' => 'boolean',
            'justifications' => 'boolean',
            'claims' => 'boolean',
            'announcements' => 'boolean',
        ];
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class, 'tutor_id');
    }
}
