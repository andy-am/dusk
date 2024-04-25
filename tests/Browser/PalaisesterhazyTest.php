<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use File;
use Carbon\Carbon;
use Facebook\WebDriver\WebDriverBy;

class PalaisesterhazyTest extends DuskTestCase
{

    private $config = [
        'url' => 'https://www.palais-esterhazy.com/index-sk.html#/liste',
        'wait_time' => 15000,
        'name' => 'Palais Esterhazy',
        'slug' => 'palaisesterhazy',
        'pricelist_head_element' => '.imex-Unitlist-table thead tr th',
        'pricelist_body_element' => '.imex-Unitlist-table tbody tr',
        'pricelist_excluded_rows' => false,
        'pricelist_allowed_rows_class' => 'normal-row',
        'assert_text' => false,
        'yesterday' => '',
        'current_json' => '',
        'prev_json' => '',
        'prev_json_content' => ''
    ];


    /**
     * @test
     * @group palaisesterhazy
     */
    public function scrapeData()
    {
        // NOTE: config vars
        $this->config['yesterday'] = Carbon::yesterday()->toDateString();
        $this->config['current_json'] = 'storage/scraped/'.$this->config['slug'].'.json';
        $this->config['prev_json'] = 'storage/scraped/'.$this->config['yesterday'].'/'.$this->config['slug'].'.json';
        $this->config['prev_json_content'] = json_decode(File::get($this->config['current_json']), true);

        // NOTE: yesterday directory check
        if(!File::exists('storage/scraped/'.$this->config['yesterday'])) {
            File::makeDirectory('storage/scraped/'.$this->config['yesterday']);
        }


        // NOTE: open browser
        $this->browse(function (Browser $browser) {
            $header = [
                'column_names' => [],
                'column_count' => []
            ];
            $errors = [
                'header' => [
                    'has_error' => false,
                    'new_items' => [],
                    'missing_items' => []
                ]
            ];
            $status = [
                'scraped' => 'no'
            ];
            $flats = [];
            $cleanedFlats = [];
            
            
            $browser->visit($this->config['url']);
            
            $browser->pause(5000);
            
            $browser->driver->executeScript("document.querySelector('#onetrust-accept-btn-handler').click();");
            
            $browser->driver->executeScript('window.scrollTo(0, 4700);');
            
            $browser->pause(10000);
            
            
            $browser->screenshot($this->config['slug'].time());
            

            // NOTE: header scraping and check
            $ths = $browser->elements($this->config['pricelist_head_element']);


            foreach($ths as $th) {
                $classes = explode(' ', $th->getAttribute('class'));
                $text = cleanText($th->getText());

                if(!empty($text) && canBeUsedByClassName($classes)) {
                    $header['column_names'][] = $text;
                }
            }

            $header['column_count'] = count($header['column_names']);


            if($header['column_count'] != $this->config['prev_json_content']['header']['column_count']) {
                info( $this->config['name'].': Column count does not match');

                $intersect = array_intersect($header['column_names'], $this->config['prev_json_content']['header']['column_names']);

                if(count($intersect) != $header['column_count']) {
                    $errors['header']['missing_items'] = array_diff($header['column_names'], $intersect);
                    $errors['header']['new_items'] = array_diff($this->config['prev_json_content']['header']['column_names'], $intersect);

                    info($this->config['name'].' missing items: '.print_r($errors['header']['missing_items'], true));
                    info($this->config['name'].' new items: '.print_r($errors['header']['new_items'], true));

                    $errors['header']['has_error'] = true;
                }
            } else {
                $intersect = array_intersect($header['column_names'], $this->config['prev_json_content']['header']['column_names']);

                if(count($intersect) != $header['column_count']) {
                    $errors['header']['missing_items'] = array_diff($header['column_names'], $intersect);
                    $errors['header']['new_items'] = array_diff($this->config['prev_json_content']['header']['column_names'], $intersect);

                    info($this->config['name'].' missing items: '.print_r($errors['header']['missing_items'], true));
                    info($this->config['name'].' new items: '.print_r($errors['header']['new_items'], true));

                    $errors['header']['has_error'] = true;
                }
            }

            // NOTE: raw row data scraping
            $trs = $browser->elements($this->config['pricelist_body_element'] . '');
            
            

            foreach($trs as $trKey => $tr) {

                
                    $tds = $browser->elements($this->config['pricelist_body_element'].':nth-child('.($trKey+1).')  td');
                    /*$d = [];
                    foreach($tds as $key => $td){
                        $d[$key] = $td->getText();
                        $flats[$trKey][$header['column_names'][$key]] = $d[$key]->getText();
                    }
                    dd($d, count($tds), $header['column_count']);*/
                    
                    $cleanedTds = [];

                    // NOTE: remove hidden elements
                    if(count($tds) > $header['column_count']) {
                        foreach($tds as $key => $td) {
                            $classes = explode(' ', $td->getAttribute('class'));
                            $text = $td->getText();
                            if(!empty($text) && canBeUsedByClassName($classes)) {
                                array_push($cleanedTds, $td);
                            }
                        }
                    } else {
                        $cleanedTds = $tds;
                    }
                    
                    

                    // NOTE: parse data
                for($i=0; $i < $header['column_count']; $i++) {
                    if(isset($cleanedTds[$i])) {
                        $flats[$trKey][$header['column_names'][$i]] = $cleanedTds[$i]->getText();
                    }
                }
                
            }
            
            // NOTE: reset flat array keys
            $flats = array_values($flats);


            // NOTE: cleaning raw flats
            foreach($flats as $flat) {
                $cleanedFlat = [];

                foreach($flat as $col => $data) {
                    $columnName = parseColumns($col);
                    $cleanedData = parseData($columnName, $data);

                    if($columnName) {
                        $cleanedFlat[$columnName] = $cleanedData;
                    }
                }

                $cleanedFlats[] = $cleanedFlat;
            }


            // NOTE: scraping status
            if(count($flats) > 0) {
                if($errors['header']['has_error']) {
                    $status['scraped'] = 'yes - with errors';
                } else {
                    $status['scraped'] = 'yes';
                }

                $status['scraped_flats'] = count($flats);
            }


            // NOTE: move yesterday json
            File::move('storage/scraped/'.$this->config['slug'].'.json', 'storage/scraped/'.Carbon::yesterday()->toDateString().'/'.$this->config['slug'].'.json');


            // NOTE: create and save json file
            File::put('storage/scraped/'.$this->config['slug'].'.json', json_encode([
                'header' => $header,
                'errors' => $errors,
                'status' => $status,
                'raw_flats' => $flats,
                'clean_flats' => $cleanedFlats
            ], JSON_UNESCAPED_UNICODE));


            // NOTE: assert for test to pass without error
            if($this->config['assert_text']) {
                $browser->assertSee($this->config['assert_text']);
            }
        });
    }
}
