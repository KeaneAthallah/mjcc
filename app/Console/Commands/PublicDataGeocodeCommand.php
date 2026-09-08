<?php

namespace App\Console\Commands;

use App\Models\ExternalData;
use App\Services\PublicData\LocationResolver;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('public-data:geocode {--all}')]
#[Description('Melengkapi koordinat latitude/longitude pada data Publik dari lokasi yang sudah direkam')]
class PublicDataGeocodeCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(LocationResolver $resolver): int
    {
        $query = ExternalData::query();

        if (! $this->option('all')) {
            $query->whereNull('latitude')->orWhereNull('longitude');
        }

        $updated = 0;
        $skipped = 0;

        $query->select(['id', 'location', 'latitude', 'longitude'])
            ->cursor()
            ->each(function (ExternalData $row) use ($resolver, &$updated, &$skipped) {
                $coordinates = $resolver->resolve($row->location);

                if ($coordinates === null) {
                    $skipped++;

                    return;
                }

                $row->update([
                    'latitude' => $coordinates['latitude'],
                    'longitude' => $coordinates['longitude'],
                ]);

                $updated++;
            });

        $this->line(sprintf(
            '  koordinat diperbarui: %d, lokasi tidak dikenal: %d',
            $updated,
            $skipped,
        ));

        return self::SUCCESS;
    }
}
