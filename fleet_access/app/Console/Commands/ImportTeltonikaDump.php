<?php

namespace App\Console\Commands;

use App\Services\TeltonikaDumpImporterService;
use Illuminate\Console\Command;

class ImportTeltonikaDump extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teltonika:import-dump {file? : Path to Teltonika JSONL dump file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports Teltonika GPS tracker JSONL dump file into database.';

    /**
     * Execute the console command.
     */
    public function handle(TeltonikaDumpImporterService $importerService): int
    {
        $filePath = $this->argument('file');
        if (!$filePath) {
            $candidates = [
                base_path('../teltonika_tcp/860848081275126.jsonl'),
                base_path('teltonika_tcp/860848081275126.jsonl'),
                '/var/www/html/teltonika_tcp/860848081275126.jsonl',
                '/var/www/html/fleet_tracker/teltonika_tcp/860848081275126.jsonl',
            ];
            foreach ($candidates as $cand) {
                if (file_exists($cand)) {
                    $filePath = $cand;
                    break;
                }
            }
        }

        if (!$filePath || !file_exists($filePath)) {
            $this->error("Dump file not found at: " . ($filePath ?? 'default search paths'));
            return Command::FAILURE;
        }

        $this->info("Importing Teltonika GPS dump file: {$filePath}...");

        try {
            $stats = $importerService
                ->fromFile($filePath)
                ->import();

            $this->info("Import complete!");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['GPS Records Imported', $stats['imported']],
                    ['Records Skipped', $stats['skipped']],
                    ['Vehicles Created/Updated', $stats['vehicles']],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Import failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
