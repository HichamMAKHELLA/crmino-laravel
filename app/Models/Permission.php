<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';

    // Le Code EST la clé (port de app.Permission). Pas d'auto-incrément, pas de
    // timestamps : la table n'en porte pas.
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['code', 'libelle', 'module', 'actif'];

    protected function casts(): array
    {
        return ['actif' => 'boolean'];
    }
}
