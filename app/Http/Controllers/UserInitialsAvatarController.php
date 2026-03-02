<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Response;

class UserInitialsAvatarController
{
    public function __invoke(User $user): Response
    {
        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        if ($name === '') {
            $name = $user->email ?? 'User';
        }

        $initials = collect(preg_split('/\s+/', $name))
            ->filter()
            ->take(2)
            ->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        if ($initials === '') {
            $initials = 'U';
        }

        // Colore deterministico (semplice) basato sull'id
        $palette = ['#2563eb', '#7c3aed', '#059669', '#d97706', '#dc2626', '#0ea5e9'];
        $bg = $palette[$user->id % count($palette)];
        $fg = '#ffffff';

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">
  <rect width="128" height="128" rx="64" fill="{$bg}"/>
  <text x="64" y="70" text-anchor="middle" font-family="Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial" font-size="48" fill="{$fg}">{$initials}</text>
</svg>
SVG;

        return response($svg, 200)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
