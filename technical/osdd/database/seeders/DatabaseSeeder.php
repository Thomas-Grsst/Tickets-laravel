<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Xefi\LaravelOSDD\SeederRegistry;

/**
 * Renaming this class, or moving it out of the Database\Seeders namespace, makes
 * `migrate:fresh --seed` resolve nothing and skip every layer seeder silently.
 */
class DatabaseSeeder extends Seeder
{
    public function __construct(private readonly SeederRegistry $registry) {}

    public function run(): void
    {
        foreach ($this->registry->seeders() as $seeder) {
            $this->call($seeder);
        }
    }
}
