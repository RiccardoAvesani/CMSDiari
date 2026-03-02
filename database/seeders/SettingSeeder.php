<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use JsonException;

class SettingSeeder extends Seeder
{
    /**
     * @throws JsonException
     */
    public function run(): void
    {
        $userId = User::query()->orderBy('sort')->value('id');

        $environment = Setting::ENV_PRODUCTION;

        $rows = [
            [
                'description' => 'ETB_SYNC_INTERVAL_MINUTES',
                'instructions' => 'Ogni quanti minuti eseguire la sincronizzazione ETB.',
                'value' => 60,
            ],
            [
                'description' => 'INVITATION_EXPIRY_DAYS',
                'instructions' => 'Scadenza Inviti Collaboratori (giorni).',
                'value' => 30,
            ],
            [
                'description' => 'COLLECTION_PERIOD_DAYS',
                'instructions' => 'Durata fase In Raccolta (giorni).',
                'value' => 30,
            ],
            [
                'description' => 'ANNOTATION_PERIOD_DAYS',
                'instructions' => 'Durata fase In Correzione (giorni).',
                'value' => 30,
            ],
            [
                'description' => 'COLLECTION_GRACE_DAYS',
                'instructions' => 'Giorni di tolleranza dopo la scadenza raccolta prima di abilitare “Estrai dati”.',
                'value' => 3,
            ],
            [
                'description' => 'MAX_CORRECTION_CYCLES',
                'instructions' => 'Numero massimo di cicli correttivi.',
                'value' => 3,
            ],
            [
                'description' => 'AUTOSAVE_SECONDS',
                'instructions' => 'Intervallo autosalvataggio nei form raccolta dati (secondi).',
                'value' => 15,
            ],
            [
                'description' => 'MAX_UPLOAD_MB',
                'instructions' => 'Limite upload PDF/immagini/loghi (MB).',
                'value' => 20,
            ],
            [
                'description' => 'IMAGE_MIN_DPI',
                'instructions' => 'Risoluzione minima immagini (DPI).',
                'value' => 300,
            ],
            [
                'description' => 'IMAGE_ALLOWED_FORMATS',
                'instructions' => 'Formati immagini consentiti.',
                'value' => ['jpg', 'jpeg', 'png', 'tif', 'tiff'],
            ],
        ];

        foreach ($rows as $row) {
            Setting::query()->updateOrCreate(
                [
                    'description' => $row['description'],
                    'environment' => $environment,
                ],
                [
                    'instructions' => $row['instructions'] ?? null,

                    // Importante: la colonna è JSON, quindi salvo SEMPRE JSON valido (anche per interi/bool/string).
                    'value' => $this->toJson($row['value'] ?? null),

                    'is_active' => true,
                    'permission' => Setting::PERMISSION_1,
                    'status' => Setting::STATUS_ACTIVE,

                    'user_id' => null,

                    // Nei seed Auth::id() è null: valorizzo esplicitamente.
                    'created_by' => $userId,
                    'modified_by' => $userId,
                ],
            );
        }
    }

    /**
     * Converte qualsiasi valore in JSON valido per MySQL JSON:
     * - 60 -> "60" (testo JSON che rappresenta il numero 60)
     * - ['a','b'] -> '["a","b"]'
     * - null -> 'null'
     *
     * @throws JsonException
     */
    private function toJson(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
