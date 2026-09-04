<?php

declare(strict_types=1);

use App\Support\Doublons;
use App\Support\Texte;

it('retire les accents', function () {
    expect(Texte::retirerAccents('Société'))->toBe('Societe');
    expect(Texte::retirerAccents('Fès'))->toBe('Fes');
});

it('réduit aux alphanumériques en majuscules', function () {
    expect(Texte::reduireAuxAlphanumeriques('I.C.E.'))->toBe('ICE');
    expect(Texte::reduireAuxAlphanumeriques('  a b '))->toBe('AB');
});

it('normalise la raison sociale en retirant la forme juridique (§41)', function () {
    expect(Doublons::normaliserRaisonSociale('Société Exemple S.A.R.L.'))->toBe('SOCIETE EXEMPLE');
    expect(Doublons::normaliserRaisonSociale('societe exemple sarl'))->toBe('SOCIETE EXEMPLE');
});

it('recolle les sigles pointés', function () {
    expect(Doublons::normaliserRaisonSociale('A.B.C. Trading'))->toBe('ABC TRADING');
});

it('rend le nettoyé quand tout est forme juridique', function () {
    expect(Doublons::normaliserRaisonSociale('SARL'))->toBe('SARL');
});

it('rend null sur une raison sociale vide', function () {
    expect(Doublons::normaliserRaisonSociale('   '))->toBeNull();
    expect(Doublons::normaliserRaisonSociale(null))->toBeNull();
});

it('normalise un numéro marocain sous ses formes', function () {
    expect(Doublons::normaliserTelephone('+212 6 12 34 56 78'))->toBe('0612345678');
    expect(Doublons::normaliserTelephone('0612345678'))->toBe('0612345678');
    expect(Doublons::normaliserTelephone('00212612345678'))->toBe('0612345678');
    expect(Doublons::normaliserTelephone('612345678'))->toBe('0612345678');
});

it('laisse un numéro étranger tel quel', function () {
    expect(Doublons::normaliserTelephone('+33 1 23 45 67 89'))->toBe('33123456789');
});

it('extrait le domaine d une URL ou d un courriel', function () {
    expect(Doublons::extraireDomaine('https://www.exemple.ma/contact'))->toBe('exemple.ma');
    expect(Doublons::extraireDomaine('jean@exemple.ma'))->toBe('exemple.ma');
    expect(Doublons::extraireDomaine('EXEMPLE.MA'))->toBe('exemple.ma');
});

it('écarte les domaines grand public et les valeurs sans point', function () {
    expect(Doublons::extraireDomaine('jean@gmail.com'))->toBeNull();
    expect(Doublons::extraireDomaine('bonjour'))->toBeNull();
});
