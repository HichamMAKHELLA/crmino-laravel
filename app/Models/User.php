<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Portee;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'nom', 'prenom', 'fonction', 'role_id', 'equipe_id', 'responsable_id', 'actif', 'derniere_connexion_le'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'actif' => 'boolean',
            'derniere_connexion_le' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function equipe(): BelongsTo
    {
        return $this->belongsTo(Equipe::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * La portée d'une permission pour cet utilisateur (port de Habilitations).
     *
     * Le défaut est le REFUS (Portee::Aucune) : compte inactif, sans rôle, rôle
     * éteint, permission absente ou permission éteinte (§68) — tout ce qui n'est
     * pas un droit explicitement accordé ET actif ne donne rien.
     */
    public function porteePour(string $permission): Portee
    {
        if (! $this->actif || $this->role_id === null) {
            return Portee::Aucune;
        }

        $valeur = DB::table('role_permissions as rp')
            ->join('roles as r', 'r.id', '=', 'rp.role_id')
            ->join('permissions as p', 'p.code', '=', 'rp.permission_code')
            ->where('rp.role_id', $this->role_id)
            ->where('rp.permission_code', $permission)
            ->where('r.actif', true)
            ->where('p.actif', true)
            ->value('rp.portee');

        return $valeur !== null ? Portee::from($valeur) : Portee::Aucune;
    }

    /** L'utilisateur a-t-il un droit (quelconque portée sauf Aucune) ? */
    public function peut(string $permission): bool
    {
        return $this->porteePour($permission) !== Portee::Aucune;
    }
}
