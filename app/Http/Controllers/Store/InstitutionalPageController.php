<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Support\InstitutionalPages;
use App\Support\WhatsApp;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionalPageController extends Controller
{
    public function service(): Response
    {
        return $this->render('service');
    }

    public function faq(): Response
    {
        return $this->render('faq');
    }

    public function returns(): Response
    {
        return $this->render('returns');
    }

    private function render(string $key): Response
    {
        $page = InstitutionalPages::all()[$key];
        $company = config('mglasto.company');
        $todo = InstitutionalPages::TO_DEFINE;
        $value = fn (string $field): string => is_array($company) && is_string($company[$field] ?? null) && $company[$field] !== ''
            ? $company[$field]
            : $todo;

        // Contact details come from config (.env); missing ones show [A DEFINIR].
        $placeholders = [
            '{whatsapp}' => WhatsApp::number() !== null ? '+'.WhatsApp::number() : "{$todo}: número do WhatsApp",
            '{phone}' => $value('phone'),
            '{email}' => $value('email'),
            '{address}' => $value('address'),
        ];

        return Inertia::render('store/institutional', [
            'title' => $page['title'],
            'intro' => $page['intro'],
            'sections' => array_map(fn (array $section): array => [
                'title' => $section['title'],
                'body' => strtr($section['body'], $placeholders),
            ], $page['sections']),
            'whatsappUrl' => $key === 'service' ? WhatsApp::link('Olá! Vim pelo site da MGLASTO.') : null,
        ]);
    }
}
