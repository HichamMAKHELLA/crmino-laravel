<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Opportunite;
use Illuminate\Foundation\Http\FormRequest;

class StoreOpportuniteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Opportunite::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'intitule' => ['required', 'string', 'max:200'],
            'societe_id' => ['required', 'integer', 'exists:societes,id'],
            // Seules les étapes OUVERTES : créer directement à « Perdu » ferait
            // naître une affaire close sans motif (RG-OPP-002).
            'etape_id' => ['required', 'integer', 'exists:etapes_pipeline,id'],
            'montant_ht' => ['nullable', 'numeric', 'min:0'],
            // RG-OPP-004 : la probabilité peut surcharger celle de l'étape.
            'probabilite' => ['nullable', 'integer', 'min:0', 'max:100'],
            'date_cloture_estimee' => ['nullable', 'date'],
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
