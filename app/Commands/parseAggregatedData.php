<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;

use App\Models\Project;
use App\Models\FlatData;
use App\Models\AggregatedFlatData;

class parseAggregatedData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:aggregated-data-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse aggregated flat data - history';

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
        $dateRange  = CarbonPeriod::create('2019-01-01', '2019-11-26');
        $projects = Project::get();

        foreach($dateRange as $date) {
            $this->info('Starting process for date: ' . $date);
            $date = $date->format('Y-m-d');

            foreach ($projects as $project) {
                $flats = FlatData::where('project_id', $project->id)->where('date', $date)->get();



                AggregatedFlatData::create([
                    'project_id' => $project->id,
                    'date' => $date,
                    'free_flats' => count($flats->whereStrict('flat_status', 1)),
                    'reserved_flats' => count($flats->whereStrict('flat_status', 2)),
                    'sold_flats' => count($flats->whereStrict('flat_status', 3)),
                    'rented_flats' => count($flats->whereStrict('flat_status', 10)),
                    'total_flats' => count($flats),
                ]);
            }

        }
    }
}
