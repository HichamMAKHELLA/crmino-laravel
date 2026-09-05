<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stockage des documents (§44, §58). Le nom d'origine ne touche JAMAIS le
 * disque : le nom de stockage est ENGENDRÉ, l'extension passe une liste BLANCHE
 * (jamais une liste noire — elle se contourne avec une extension imprévue), et
 * le fichier vit sur un disque PRIVÉ, hors de la racine servie.
 */
final class StockageDocuments
{
    public const TAILLE_MAX = 10 * 1024 * 1024; // 10 Mo

    /** @var list<string> */
    public const EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'png', 'jpg', 'jpeg', 'csv', 'txt', 'zip'];

    private const DISQUE = 'local';
    private const DOSSIER = 'documents';

    public static function extensionAdmise(string $nomOrigine): bool
    {
        $ext = strtolower(pathinfo($nomOrigine, PATHINFO_EXTENSION));

        return in_array($ext, self::EXTENSIONS, true);
    }

    /**
     * Écrit le fichier sous un nom engendré et rend [nomStockage, cheminStockage].
     *
     * @return array{0:string, 1:string}
     */
    public static function ecrire(UploadedFile $fichier): array
    {
        $ext = strtolower($fichier->getClientOriginalExtension());
        $nomStockage = Str::uuid()->toString().($ext !== '' ? '.'.$ext : '');
        $chemin = $fichier->storeAs(self::DOSSIER, $nomStockage, self::DISQUE);

        return [$nomStockage, $chemin];
    }

    public static function disque(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(self::DISQUE);
    }
}
