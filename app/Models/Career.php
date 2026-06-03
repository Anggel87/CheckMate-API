<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $short_name
 * @property string $code
 * @property bool $is_active
 * @property int $directors_id
 */
class Career extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'short_name', 'code', 'is_active', 'directors_id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function director(): BelongsTo
    {
        return $this->belongsTo(Director::class, 'directors_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class, 'career_id');
    }
}
