<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

use App\Models\Project;
use App\Models\FlatData;
use App\Models\FlatChanges;

class parseChanges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parse:flat-changes {--date=} {--project=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse flat changes';

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
        $date = $this->option('date') ? $this->option('date') : Carbon::now()->format('Y-m-d');
        $project = $this->option('project');

        if(!empty($project)) {
            $projects = Project::where('slug', $project)->where('parse_status', 1)->get();

        } else {
            $projects = Project::where('parse_status', 1)->get();
        }

        if($projects->count() == 0) {
            $this->error('No project/s found!');
            return;
        } else {
            $allChanges = [];

            foreach($projects as $project) {
                $currFlats = FlatData::where('project_id', $project->id)->where('date', $date)->get();
                $prevFlats = FlatData::where('project_id', $project->id)->where('date', Carbon::parse($date)->subDays(1)->format('Y-m-d'))->get();

                if($currFlats->count() > 0 && $prevFlats->count() > 0) {
                    foreach($currFlats as $flat) {
                        $prevFlat = $prevFlats->where('flat_name', $flat->flat_name)->first();

                        if(!empty($flat->flat_name) && !empty($prevFlat->flat_name)) {

                            $this->info('Comparing: ' . $flat->flat_name . ' <-> ' . $prevFlat->flat_name);

                            $changes = array_diff($flat->toArray(), $prevFlat->toArray());

                            unset($changes['id'], $changes['date']);

                            if(count($changes) > 0) {
                                $this->info('Changes in: ' . $flat->flat_name . ' <-> ' . $prevFlat->flat_name . ' = ' . count($changes));

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
            }

            FlatChanges::insert($allChanges);

            $this->info('Total changes: ' . count($allChanges));
        }
    }
}
