<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Lead;
use App\Support\ReglesLead;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Lead::class) ?? false;
    }

    public function rules(): array
    {
        return [
            // L'origine est franchement obligatoire (§38, §12) : c'est la seule
            // chose qu'on ne pourra pas reconstituer après coup.
            'source_id' => ['required', 'integer', 'exists:sources,id'],
            'raison_sociale' => ['nullable', 'string', 'max:200'],
            'ice' => ['nullable', 'string', 'max:15'],
            'site_web' => ['nullable', 'string', 'max:256'],
            'commentaire' => ['nullable', 'string', 'max:2000'],

            'contact' => ['nullable', 'array'],
            'contact.nom' => ['nullable', 'string', 'max:100'],
            'contact.prenom' => ['nullable', 'string', 'max:100'],
            'contact.telephone' => ['nullable', 'string', 'max:32'],
            'contact.gsm' => ['nullable', 'string', 'max:32'],
            'contact.email' => ['nullable', 'email', 'max:256'],

            'forcer' => ['nullable', 'boolean'],
        ];
    }

    /**
     * RG-LEA-001 : identité ET moyen de joindre. La règle est composée, pas
     * exprimable proprement en règles de champ.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $c = (array) $this->input('contact', []);
                $erreur = ReglesLead::controlerDonneesMinimales(
                    $this->input('raison_sociale'),
                    $c['nom'] ?? null,
                    $c['telephone'] ?? null,
                    $c['gsm'] ?? null,
                    $c['email'] ?? null,
                );

                if ($erreur !== null) {
                    $validator->errors()->add('rg_lea_001', ReglesLead::REGLE.' : '.$erreur['message']);
                }
            },
        ];
    }
}
