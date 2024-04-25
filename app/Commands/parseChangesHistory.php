<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;

use App\Models\Project;
use App\Models\FlatData;
use App\Models\AggregatedFlatData;
use App\Models\FlatChanges;

class parseChangesHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:flat-changes-history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse flat changes - history';

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
        $dateRange  = CarbonPeriod::create('2020-07-31', '2020-11-10');
        $projects = Project::get();

        foreach($dateRange as $date) {
            $this->info('Starting process for date: ' . $date);

            $date = $date->format('Y-m-d');
            $allChanges = [];

            foreach ($projects as $project) {
                $this->info('Starting project ' . $project->id . ' and date:  ' . $date);

                $currFlats = FlatData::where('project_id', $project->id)->where('date', $date)->get();

                if(count($currFlats) > 0) {

                    $latestAvailImport = AggregatedFlatData::where('project_id', $project->id)->where('date', '<', $date)->orderBy('date', 'desc')->limit(1)->get();

                    if (count($latestAvailImport) > 0) {
                        $latestDate = $latestAvailImport[0]['date'];
                        $this->info('Using prev date ' . $latestDate);

                        $prevFlats = FlatData::where('project_id', $project->id)->where('date', $latestDate)->get();
                    }

                    if ($currFlats->count() > 0 && $prevFlats->count() > 0) {
                        foreach ($currFlats as $flat) {
                            $prevFlat = $prevFlats->where('flat_name', $flat->flat_name)->first();

                            if (!empty($flat->flat_name) && !empty($prevFlat->flat_name)) {

                                //$this->info('Comparing: ' . $flat->flat_name . ' <-> ' . $prevFlat->flat_name);

                                $changes = array_diff($flat->toArray(), $prevFlat->toArray());

                                unset($changes['id'], $changes['date']);

                                if (count($changes) > 0) {
                                    //$this->info('Changes in: ' . $flat->flat_name . ' <-> ' . $prevFlat->flat_name . ' = ' . count($changes));

                                    $changeKeys = array_keys($changes);

                                    $flatChanges = [
                                        'project_id' => $project->id,
                                        'date' => $date,
                                        'flat_name' => $flat->flat_name,
                                        'changes' => []
                                    ];

                                    foreach ($changeKeys as $change) {
                                        $flatChanges['changes'][$change]['from'] = $prevFlat->{$change};
                                        $flatChanges['changes'][$change]['to'] = $flat->{$change};
                                    }

                                    $flatChanges['changes'] = json_encode($flatChanges['changes']);

                                    $allChanges[] = $flatChanges;
                                }
                            }
                        }
                    }
                } else {
                    $this->info('No new flats in project ' . $project->id);
                }
            }

            FlatChanges::insert($allChanges);

            $this->info('Total changes: ' . count($allChanges));
        }
    }
}
