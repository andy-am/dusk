<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


use App\Models\Project;
use App\Models\FlatData;
use App\Models\CompiledFlatData;
use App\Models\AggregatedFlatData;


class compileFlatData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compile:flat-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile flat data';

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
        $projects = Project::get();

        foreach($projects as $project) {
            $latestDate = AggregatedFlatData::select('date')->where('project_id', $project->id)->orderBy('date', 'desc')->first();


            // import data and check for new flats
            if($latestDate) {
                $latestDate = $latestDate->date;
                $latestFlats = FlatData::where('project_id', $project->id)->where('date', $latestDate)->get();
                $compiledFlats = CompiledFlatData::where('project_id', $project->id)->get();

                if($compiledFlats->count() > 0) {
                    $compiledFlatNames = $compiledFlats->pluck('flat_name')->toArray();

                    foreach($latestFlats as $latestFlat) {
                        if(!in_array($latestFlat->flat_name, $compiledFlatNames)) {
                            $this->createCompiledFlat($latestFlat);
                        }
                    }
                } else {
                    foreach($latestFlats as $flat) {
                        $this->createCompiledFlat($flat);
                    }
                }
            }

            // get latest available prices
            $compiledFlats = CompiledFlatData::where('project_id', $project->id)->whereIn('flat_price', [null, 0])->get();
            if($compiledFlats->count() > 0) {
                foreach($compiledFlats as $flat) {
                    $flatWithPrice = FlatData::where('project_id', $project->id)->where('flat_price', '>', 0)->where('flat_name', $flat->flat_name)->first();

                    if($flatWithPrice) {
                        $this->updateCompiledFlat($flat, $flatWithPrice);
                        $this->info('Updated data for flat: '.$flat->flat_name.' in project: '.$project->name);
                    } else {
                        $this->info('Doesn\'t have price for flat: '.$flat->flat_name.' in project: '.$project->name);
                    }
                }
            }
        }


    }

    protected function createCompiledFlat($flat)
    {
        $compiledFlat = new CompiledFlatData();

        $compiledFlat->updated_at = Carbon::now();
        $compiledFlat->project_id = $flat->project_id;
        $compiledFlat->object_name = $flat->object_name;
        $compiledFlat->object_block = $flat->object_block;
        $compiledFlat->etape = $flat->etape;
        $compiledFlat->flat_type = $flat->flat_type;
        $compiledFlat->flat_name = $flat->flat_name;
        $compiledFlat->flat_floor = $flat->flat_floor;
        $compiledFlat->flat_rooms = $flat->flat_rooms;
        $compiledFlat->flat_area_netto = $flat->flat_area_netto;
        $compiledFlat->flat_area_brutto = $flat->flat_area_brutto;
        $compiledFlat->flat_area_exterier = $flat->flat_area_exterier;
        $compiledFlat->flat_terrace = $flat->flat_terrace;
        $compiledFlat->flat_balcony = $flat->flat_balcony;
        $compiledFlat->flat_loggia = $flat->flat_loggia;
        $compiledFlat->flat_garden = $flat->flat_garden;
        $compiledFlat->flat_cellar = $flat->flat_cellar;
        $compiledFlat->flat_sale_price = $flat->flat_sale_price;
        $compiledFlat->flat_price = $flat->flat_price;
        $compiledFlat->flat_rent_price = $flat->flat_rent_price;
        $compiledFlat->flat_monthly_payment = $flat->flat_monthly_payment;
        $compiledFlat->flat_status = $flat->flat_status;
        $compiledFlat->object_status = $flat->object_status;

        $compiledFlat->save();

        $this->info('Found new flat: '.$compiledFlat->flat_name);
    }

    private function updateCompiledFlat($compiledFlat, $dataFlat) {
        $compiledFlat->date = $dataFlat->date;
        $compiledFlat->flat_price = $dataFlat->flat_price;
        $compiledFlat->flat_sale_price = $dataFlat->flat_sale_price;
        $compiledFlat->flat_area_netto = $dataFlat->flat_area_netto;
        $compiledFlat->flat_area_brutto = $dataFlat->flat_area_brutto;

        $compiledFlat->save();
    }
}
