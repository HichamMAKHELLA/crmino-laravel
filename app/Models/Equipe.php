<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipe extends Model
{
    protected $table = 'equipes';

    protected $fillable = ['code', 'libelle', 'responsable_id', 'actif'];

    protected function casts(): array
    {
        return ['actif' => 'boolean'];
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function membres(): HasMany
    {
        return $this->hasMany(User::class, 'equipe_id');
    }
}
