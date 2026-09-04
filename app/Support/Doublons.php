<?php

declare(strict_types=1);

namespace App\Support;

/**
 * La normalisation qui rend la détection de doublons possible (§41). Ces
 * fonctions produisent raison_sociale_normalisee, telephone_normalise et
 * domaine_web. Port de Domain/Doublons du .NET.
 *
 * AUCUN SOUNDEX. Calibré pour l'anglais, il rapprocherait au hasard des raisons
 * sociales marocaines. Un faux positif fusionne deux prospects réels — plus
 * coûteux qu'un faux négatif.
 */
final class Doublons
{
    /** Formes juridiques retirées : le suffixe change au gré de la saisie, jamais l'entreprise. */
    private const FORMES_JURIDIQUES = [
        'SARL', 'SARLAU', 'SARLU', 'SUARL', 'SA', 'SAS', 'SASU', 'SNC',
        'SCS', 'SCA', 'SPA', 'GIE', 'EURL', 'SPRL', 'SASAU', 'AU',
    ];

    private const DOMAINES_GRAND_PUBLIC = [
        'gmail.com', 'hotmail.com', 'hotmail.fr', 'outlook.com', 'outlook.fr',
        'yahoo.com', 'yahoo.fr', 'live.fr', 'live.com', 'menara.ma', 'icloud.com',
        'orange.fr', 'wanadoo.fr', 'free.fr', 'protonmail.com', 'gmx.com',
    ];

    /** Majuscules, accents retirés, ponctuation en espace, formes juridiques retirées. */
    public static function normaliserRaisonSociale(?string $raisonSociale): ?string
    {
        if ($raisonSociale === null || trim($raisonSociale) === '') {
            return null;
        }

        $sansAccent = mb_strtoupper(Texte::retirerAccents($raisonSociale));

        // La ponctuation devient de l'espace, pas du vide : sinon « X-Y » -> « XY »
        // rapprocherait des noms distincts.
        $lettres = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $sansAccent);
        $bruts = array_values(array_filter(explode(' ', trim($lettres)), fn ($m) => $m !== ''));

        $recolles = self::recollerSiglesPointes($bruts);
        $mots = array_values(array_filter($recolles, fn ($m) => ! in_array($m, self::FORMES_JURIDIQUES, true)));

        // Tout retirer laisserait une chaîne vide qui se rapproche de toutes les
        // autres : une raison réduite à sa seule forme juridique rend le nettoyé.
        if ($mots === []) {
            return implode(' ', $bruts);
        }

        return implode(' ', $mots);
    }

    /** Numéro marocain en forme nationale, chiffres seuls. Un étranger reste tel quel. */
    public static function normaliserTelephone(?string $telephone): ?string
    {
        if ($telephone === null || trim($telephone) === '') {
            return null;
        }

        $n = preg_replace('/\D+/', '', $telephone);
        if ($n === '') {
            return null;
        }

        if (str_starts_with($n, '00212') && strlen($n) === 14) {
            return '0'.substr($n, 5);
        }
        if (str_starts_with($n, '212') && strlen($n) === 12) {
            return '0'.substr($n, 3);
        }
        if (strlen($n) === 9 && $n[0] !== '0') {
            return '0'.$n;
        }

        return $n;
    }

    /** Domaine Internet extrait d'une URL ou d'un courriel. Les domaines grand public sont écartés. */
    public static function extraireDomaine(?string $urlOuCourriel): ?string
    {
        if ($urlOuCourriel === null || trim($urlOuCourriel) === '') {
            return null;
        }

        $v = mb_strtolower(trim($urlOuCourriel));

        $arobase = mb_strrpos($v, '@');
        if ($arobase !== false) {
            $v = mb_substr($v, $arobase + 1);
        }

        $v = str_replace(['https://', 'http://'], '', $v);

        $barre = mb_strpos($v, '/');
        if ($barre !== false) {
            $v = mb_substr($v, 0, $barre);
        }

        $deuxPoints = mb_strpos($v, ':');
        if ($deuxPoints !== false) {
            $v = mb_substr($v, 0, $deuxPoints);
        }

        if (str_starts_with($v, 'www.')) {
            $v = mb_substr($v, 4);
        }

        $v = trim($v);
        if ($v === '' || ! str_contains($v, '.')) {
            return null;
        }

        return in_array($v, self::DOMAINES_GRAND_PUBLIC, true) ? null : $v;
    }

    /**
     * Recolle les suites de lettres isolées : « S.A.R.L. » -> quatre jetons d'une
     * lettre -> « SARL » (reconnaissable comme forme juridique). « A.B.C. » -> « ABC ».
     *
     * @param  list<string>  $mots
     * @return list<string>
     */
    private static function recollerSiglesPointes(array $mots): array
    {
        $sortie = [];
        $sigle = '';

        foreach ($mots as $mot) {
            if (mb_strlen($mot) === 1) {
                $sigle .= $mot;
            } else {
                if ($sigle !== '') {
                    $sortie[] = $sigle;
                    $sigle = '';
                }
                $sortie[] = $mot;
            }
        }
        if ($sigle !== '') {
            $sortie[] = $sigle;
        }

        return $sortie;
    }
}
