<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use File;
use Carbon\Carbon;
use Facebook\WebDriver\WebDriverBy;

class NovanitraTest extends DuskTestCase
{

    private $config = [
        'url' => 'https://www.novanitra.sk/cennik',
        'wait_time' => 10000,
        'name' => 'Nová Nitra',
        'slug' => 'novanitra',
        'pricelist_head_element' => '.pricelist thead',
        'pricelist_body_element' => '.pricelist tbody',
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
     * @group novanitra
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


            // NOTE: visit project url
            $browser->visit($this->config['url']);

            // NOTE: pause and wait for dynamic elements
            $browser->pause($this->config['wait_time']);
            
            $browser->driver->executeScript('window.scrollTo(0, 25000);');

            // NOTE: header scraping and check
            $ths = $browser->elements($this->config['pricelist_head_element'].' tr th');


            //$test = [];
            foreach($ths as $k => $th) {
                if($k == count($ths)) continue;
                $classes = explode(' ', $th->getAttribute('class'));
                $text = cleanText($th->getText());
                //dump($text);

                //dump($classes);
                //$test[$text] = join(";",$classes);
                if(!empty($text)) {
                    $header['column_names'][] = $text;
                }
            }
            
            //dump($test);

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
            $trs = $browser->elements($this->config['pricelist_body_element'] . ' > a');
            
            
            
            $k = 0;
            foreach($trs as $trKey => $tr) {
                if($trKey == 0){
                    $k = 1;
                } else{
                    $k +=2;
                }
                
                
                $tds = $browser->elements($this->config['pricelist_body_element'].' > a:nth-child('.($k).') > td');
                
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
                        array_push($cleanedTds, $td);
                    }
                } else {
                    $cleanedTds = $tds;
                }
                
                //dump($header['column_names'], count($tds), count($cleanedTds));
                
                
                // NOTE: parse data
                    for($i = 0; $i < $header['column_count']; $i++) {
                        if(count($tds) > ($i+1)) {
                            $flats[$trKey][$header['column_names'][$i]] = trim(urldecode(html_entity_decode(strip_tags($cleanedTds[$i]->getAttribute('innerHTML')))));
                        }
                    }
                    /*for($i=0; $i < $header['column_count']; $i++) {
                        if(isset($cleanedTds[$i])) {
                            $flats[$trKey][$header['column_names'][$i]] = $cleanedTds[$i]->getText();
                        }
                    }*/
                
            }
            
            /*dd(count($flats), count($trs));*/
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
