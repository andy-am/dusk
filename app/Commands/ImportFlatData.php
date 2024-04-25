<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\FlatData;
use App\Models\ImportLog;
use App\Models\AggregatedFlatData;
use GuzzleHttp;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;

class ImportFlatData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:flats {from_date} {to_date=false?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import flat data';

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
        $fromDate = $this->argument('from_date');
        $toDate = $this->argument('to_date');
        $dateRange = [];
        $projects = Project::where('parse_status', 1)->get();

        if($toDate) {
            $dateRange = CarbonPeriod::create($fromDate, $toDate);
        } else {
            $dateRange = [$fromDate];
        }

        foreach($dateRange as $date) {
            $this->info('Starting process for date: ' . $date);

            foreach($projects as $project) {
                $this->info('Starting process for project: ' . $project->name);

                $client = new GuzzleHttp\Client(['http_errors' => false]);
                $response = $client->get(env('WEBDRIVER_IP') . 'data/' . $project->slug . '/' . $date);

                if($response->getStatusCode() !== 200) {
                    ImportLog::create([
                        'project_id' => $project->id,
                        'date' => $date,
                        'status' => 'webdriver data not accessible'
                    ]);
                } else {
                    $webDriverData = json_decode($response->getBody(), true);

                    if(isset($webDriverData['status']['scraped_flats']) && $webDriverData['status']['scraped_flats'] > 0) {
                        // check if is imported
                        $aggregatedFlatData = AggregatedFlatData::where('project_id', $project->id)->where('date', $date)->first();

                        if(!$aggregatedFlatData) {

                            // insert import data
                            ImportLog::create([
                                'project_id' => $project->id,
                                'date' => $date,
                                'status' => $webDriverData['status']['scraped'],
                                'column_count' => $webDriverData['header']['column_count'],
                                'column_data' => json_encode($webDriverData['header']['column_names']),
                                'has_error' => boolval($webDriverData['errors']['header']['has_error']),
                                'errors' => json_encode($webDriverData['errors']),
                                'scraped_flats' => $webDriverData['status']['scraped_flats']
                            ]);


                            // insert aggregated data
                            $flats = collect($webDriverData['clean_flats']);

                            AggregatedFlatData::create([
                                'project_id' => $project->id,
                                'date' => $date,
                                'free_flats' => count($flats->whereStrict('flat_status', 1)),
                                'reserved_flats' => count($flats->whereStrict('flat_status', 2)),
                                'sold_flats' => count($flats->whereStrict('flat_status', 3)),
                                'rented_flats' => count($flats->whereStrict('flat_status', 10)),
                                'total_flats' => intval($webDriverData['status']['scraped_flats']),
                            ]);


                            // Insert Flats
                            foreach ($webDriverData['clean_flats'] as $key => $flat) {
                                $flat['project_id'] = $project->id;
                                $flat['date'] = $date;

                                if(empty($flat['flat_type'])) {
                                    $flat['flat_type'] = 1;
                                }

                                // unset special data
                                unset($flat['unset_flat_entrance'], $flat['unset_flat_type'], $flat['unset_balcony_1'], $flat['unset_balcony_2'], $flat['unset_balcony_3'], $flat['unset_garden_1'], $flat['unset_garden_2']);

                                FlatData::create($flat);
                            }
                        } else {
                            ImportLog::create([
                                'project_id' => $project->id,
                                'date' => $date,
                                'status' => 'duplicate import attempt'
                            ]);
                        }
                    } else {
                        ImportLog::create([
                            'project_id' => $project->id,
                            'date' => $date,
                            'status' => 'empty json file'
                        ]);
                    }
                }
            }
        }
    }
}
