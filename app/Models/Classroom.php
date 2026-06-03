<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $building
 */
class Classroom extends Model
{
    use HasFactory;

    protected $table = 'classroom';

    protected $fillable = ['name', 'building'];

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'classroom_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'classroom_id');
    }
}
