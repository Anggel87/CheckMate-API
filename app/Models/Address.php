<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $street
 * @property string $number
 * @property string $neighborhood
 * @property string $postal_code
 * @property string $city
 * @property string $state
 * @property string $country
 * @property int $users_id
 * @property int|null $tutors_id
 */
class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'street',
        'number',
        'neighborhood',
        'postal_code',
        'city',
        'state',
        'country',
        'users_id',
        'tutors_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class, 'tutors_id');
    }
}
