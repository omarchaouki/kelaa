<?php

namespace App\Services\Pharmacies;

use App\Models\Business;
use App\Models\PharmacyDuty;
use App\Support\Hours\TimeOfDay;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Imports pharmacies de garde from CSV, all or nothing.
 *
 * Expected columns (header row required, comma or semicolon separated):
 *   pharmacie (business slug), date (YYYY-MM-DD), debut (HH:MM), fin (HH:MM)
 * A "fin" earlier than or equal to "debut" ends the next day (night duty).
 */
class PharmacyDutyImporter
{
    public const MAX_ROWS = 2000;

    public const MAX_BYTES = 512 * 1024;

    public const PHARMACY_CATEGORY = 'pharmacie-de-garde';

    /**
     * @return array{imported: int, errors: list<string>}
     */
    public function import(string $path, string $timezone): array
    {
        if (! is_file($path) || filesize($path) > self::MAX_BYTES) {
            return ['imported' => 0, 'errors' => [__('Fichier absent ou trop volumineux (512 Ko maximum).')]];
        }

        $rows = $this->readRows($path);

        if ($rows === null) {
            return ['imported' => 0, 'errors' => [__('En-têtes attendus : pharmacie, date, debut, fin.')]];
        }

        if (count($rows) > self::MAX_ROWS) {
            return ['imported' => 0, 'errors' => [__('Trop de lignes (:max maximum).', ['max' => self::MAX_ROWS])]];
        }

        $pharmacies = Business::query()->published()
            ->whereHas('categories', fn ($q) => $q->where('slug', self::PHARMACY_CATEGORY))
            ->whereIn('slug', array_unique(array_column($rows, 'pharmacie')))
            ->pluck('id', 'slug');

        $duties = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2; // header is line 1

            try {
                $businessId = $pharmacies[$row['pharmacie']] ?? throw new InvalidArgumentException(__('pharmacie inconnue ou non publiée « :slug »', ['slug' => mb_substr($row['pharmacie'], 0, 60)]));
                $date = CarbonImmutable::createFromFormat('!Y-m-d', $row['date'], $timezone) ?: throw new InvalidArgumentException(__('date invalide'));

                if ($date->format('Y-m-d') !== $row['date']) {
                    throw new InvalidArgumentException(__('date invalide'));
                }

                $starts = $date->setTimeFromTimeString(TimeOfDay::normalize($row['debut']) ?? throw new InvalidArgumentException(__('heure de début manquante')));
                $ends = $date->setTimeFromTimeString(TimeOfDay::normalize($row['fin']) ?? throw new InvalidArgumentException(__('heure de fin manquante')));

                if ($ends->lessThanOrEqualTo($starts)) {
                    $ends = $ends->addDay();
                }

                $duties[] = ['business_id' => $businessId, 'date' => $date->toDateString(), 'starts_at' => $starts, 'ends_at' => $ends];
            } catch (InvalidArgumentException $e) {
                $errors[] = __('Ligne :line : :error', ['line' => $line, 'error' => $e->getMessage()]);
            }

            if (count($errors) >= 20) {
                $errors[] = __('Import arrêté après 20 erreurs.');

                break;
            }
        }

        if ($errors !== []) {
            return ['imported' => 0, 'errors' => $errors];
        }

        DB::transaction(function () use ($duties): void {
            foreach ($duties as $duty) {
                PharmacyDuty::query()->updateOrCreate(
                    ['business_id' => $duty['business_id'], 'date' => $duty['date']],
                    ['starts_at' => $duty['starts_at'], 'ends_at' => $duty['ends_at']],
                );
            }
        });

        return ['imported' => count($duties), 'errors' => []];
    }

    /**
     * @return list<array{pharmacie: string, date: string, debut: string, fin: string}>|null
     */
    private function readRows(string $path): ?array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return null;
        }

        $firstLine = (string) fgets($handle);
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);

        $header = fgetcsv($handle, 1000, $delimiter, '"', '');

        if (! is_array($header)) {
            fclose($handle);

            return null;
        }

        // Strip a UTF-8 BOM (Excel) and normalize header names.
        $header = array_map(fn ($name) => mb_strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $name) ?? '')), $header);
        $columns = ['pharmacie', 'date', 'debut', 'fin'];

        if (array_diff($columns, $header) !== []) {
            fclose($handle);

            return null;
        }

        $positions = array_map(fn (string $column) => array_search($column, $header, true), $columns);
        $rows = [];

        while (($record = fgetcsv($handle, 1000, $delimiter, '"', '')) !== false) {
            if ($record === [null] || count(array_filter($record, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = [];

            foreach ($columns as $i => $column) {
                $row[$column] = trim((string) ($record[$positions[$i]] ?? ''));
            }

            $rows[] = $row;

            if (count($rows) > self::MAX_ROWS) {
                break;
            }
        }

        fclose($handle);

        return $rows;
    }
}
