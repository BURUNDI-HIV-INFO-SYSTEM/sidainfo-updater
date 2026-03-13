<?php

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('seeders/data/sites.csv');

        if (!file_exists($csvPath)) {
            $this->command->warn("CSV file not found: {$csvPath}");
            return;
        }

        $handle = fopen($csvPath, 'r');
        $header = null;
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            // Skip BOM header row
            if ($header === null) {
                // Strip BOM if present
                $row[0] = ltrim($row[0], "\xEF\xBB\xBF");
                $header = $row;
                continue;
            }

            if (count($row) < 4) {
                continue;
            }

            $siteid   = trim($row[0]);
            $siteName = trim($row[1]);
            $province = trim($row[2]) ?: null;
            $district = trim($row[3]) ?: null;
            // Column 4 (Actif) is blank for most rows — treat blank as active
            $active   = true;

            if (empty($siteid)) {
                continue;
            }

            Site::updateOrCreate(
                ['siteid' => $siteid],
                [
                    'site_name' => $siteName,
                    'province'  => $province,
                    'district'  => $district,
                    'active'    => $active,
                ]
            );
            $count++;
        }

        fclose($handle);
        $this->command->info("Seeded {$count} sites.");
    }
}
