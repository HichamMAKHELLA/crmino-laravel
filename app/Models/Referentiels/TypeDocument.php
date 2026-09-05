<?php

declare(strict_types=1);

namespace App\Models\Referentiels;

/**
 * Type de document (§44). DEVIS et PROPOSITION sont socle (§15) : le score les
 * nomme, donc RG-REF-001 en interdit le retrait.
 */
class TypeDocument extends Referentiel
{
    protected $table = 'types_document';
}
