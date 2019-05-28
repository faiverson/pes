<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedGames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'games:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Shortcut to seed the database with the results from the google sheet';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->call('db:seed', ['--class' => 'SeedTuesdays']);
        $this->call('db:seed', ['--class' => 'FetchSpreadSheet']);
    }
}
