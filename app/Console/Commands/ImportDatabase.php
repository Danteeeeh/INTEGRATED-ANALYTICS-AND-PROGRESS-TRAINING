<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDatabase extends Command
{
    protected $signature = 'db:import {file : Path to SQL file to import}';
    protected $description = 'Import a SQL dump file into the database';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $this->info("Importing SQL file: {$file}");

        try {
            $sql = file_get_contents($file);
            DB::unprepared($sql);
            $this->info('✅ Database imported successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Import failed: ' . $e->getMessage());
            return 1;
        }
    }
}
