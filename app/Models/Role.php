<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = ['code', 'libelle', 'systeme', 'actif'];

    protected function casts(): array
    {
        return ['systeme' => 'boolean', 'actif' => 'boolean'];
    }

    public function utilisateurs(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
