<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                // §41 : doublons potentiels remontés après un contrôle de création.
                'doublons' => $request->session()->get('doublons'),
            ],
            // §36 : le centre de notifications se rafraîchit à chaque navigation,
            // pas par sondage périodique.
            'notifications' => $request->user()
                ? \App\Models\Notification::query()
                    ->where('utilisateur_id', $request->user()->id)
                    ->orderByDesc('cree_le')->limit(10)->get()
                    ->map(fn ($n) => [
                        'id' => $n->id,
                        'titre' => $n->titre,
                        'texte' => $n->texte,
                        'lue' => $n->lue_le !== null,
                        'cree_le' => $n->cree_le?->toIso8601String(),
                    ])
                : [],
            'notificationsNonLues' => $request->user()
                ? \App\Models\Notification::query()
                    ->where('utilisateur_id', $request->user()->id)->whereNull('lue_le')->count()
                : 0,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
