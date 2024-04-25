<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use File;
use Carbon\Carbon;

class PupavyHviezdoslavTest extends DuskTestCase
{
    private $dataFlats = [];
    private $config = [
        'url' => 'https://www.pupavyhviezdoslav.sk/byty',
        'wait_time' => 2000,
        'name' => 'Púpavy Hviezdoslav',
        'slug' => 'pupavyhviezdoslav',
        'pricelist_head_element' => 'table thead',
        'pricelist_body_element' => 'table tbody',
        'pricelist_excluded_rows' => '',
        'pricelist_allowed_rows_class' => '',
        'assert_text' => false,
        'use_cell_html' => false,
        'yesterday' => '',
        'current_json' => '',
        'prev_json' => '',
        'prev_json_content' => '',
        'pages' => 5
    ];
    
    private $header = [];
    private $errors = [];
    private $status = [];
    
    /**
     * @test
     * @group pupavyhviezdoslav
     */
    public function scrapeData()
    {
        
        // NOTE: config vars
        $this->config['yesterday'] = Carbon::yesterday()->toDateString();
        $this->config['current_json'] = 'storage/scraped/' . $this->config['slug'] . '.json';
        $this->config['prev_json'] = 'storage/scraped/' . $this->config['yesterday'] . '/' . $this->config['slug'] . '.json';
        $this->config['prev_json_content'] = json_decode(File::get($this->config['current_json']), true);
        
        // NOTE: yesterday directory check
        if (!File::exists('storage/scraped/' . $this->config['yesterday'])) {
            File::makeDirectory('storage/scraped/' . $this->config['yesterday']);
        }
        $this->header['column_names'] = [
            'Byt',
            'Počet izieb',
            'Podlažie',
            'Interiér',
            'Exteriér',
            'Spolu',
            'Cena',
            'Stav',
        ];
        $this->header['column_count'] = count($this->header['column_names']);
        $this->errors = [
            'header' => [
                'has_error' => false,
                'new_items' => [],
                'missing_items' => []
            ]
        ];
        $this->status = [
            'scraped' => 'no'
        ];
        
        
        foreach (range(1, $this->config['pages']) as $keyPage => $page) {
            
            
            // NOTE: open browser
            $this->browse(function (Browser $browser) use ($keyPage ) {
                $flats = [];
                $browser->visit($this->config['url'] . '?page=' . ($keyPage + 1));
                $browser->pause($this->config['wait_time']);
                //$browser->driver->executeScript('window.scrollTo(0, 25000);');
                
                
                if ($this->header['column_count'] != $this->config['prev_json_content']['header']['column_count']) {
                    info($this->config['name'] . ': Column count does not match');
                    
                    $intersect = array_intersect($this->header['column_names'], $this->config['prev_json_content']['header']['column_names']);
                    
                    if (count($intersect) != $this->header['column_count']) {
                        $this->errors['header']['missing_items'] = array_diff($this->header['column_names'], $intersect);
                        $this->errors['header']['new_items'] = array_diff($this->config['prev_json_content']['header']['column_names'], $intersect);
                        
                        info($this->config['name'] . ' missing items: ' . print_r($this->errors['header']['missing_items'], true));
                        info($this->config['name'] . ' new items: ' . print_r($this->errors['header']['new_items'], true));
                        
                        $this->errors['header']['has_error'] = true;
                    }
                } else {
                    $intersect = array_intersect($this->header['column_names'], $this->config['prev_json_content']['header']['column_names']);
                    if (count($intersect) != $this->header['column_count']) {
                        $this->errors['header']['missing_items'] = array_diff($this->header['column_names'], $intersect);
                        $this->errors['header']['new_items'] = array_diff($this->config['prev_json_content']['header']['column_names'], $intersect);
                        
                        info($this->config['name'] . ' missing items: ' . print_r($this->errors['header']['missing_items'], true));
                        info($this->config['name'] . ' new items: ' . print_r($this->errors['header']['new_items'], true));
                        
                        $this->errors['header']['has_error'] = true;
                    }
                }
                
                $trs = $browser->elements($this->config['pricelist_body_element'] . ' tr');
                foreach ($trs as $trKey => $tr) {
                    $tds = $browser->elements($this->config['pricelist_body_element'] . ' tr:nth-child(' . ($trKey + 1) . ') td');
                    $cleanedTds = [];
                    
                    
                    if (count($tds) > $this->header['column_count']) {
                        foreach ($tds as $key => $td) {
                            $classes = explode(' ', $td->getAttribute('class'));
                            $text = $td->getText();
                            if (!empty($text) && canBeUsedByClassName($classes)) {
                                array_push($cleanedTds, $td);
                            }
                        }
                    } else {
                        $cleanedTds = $tds;
                    }
                    
                    // NOTE: parse data
                    for ($i = 0; $i < $this->header['column_count']; $i++) {
                        if (isset($cleanedTds[$i])) {
                            if($i < 6){
                                $flats[$trKey][$this->header['column_names'][$i]] = $cleanedTds[$i]->getText();
                            }else{
                                if($i == 6){
                                    $hasStatus = in_array($cleanedTds[$i]->getText(), ['Rezervovaný', 'Predaný']);
                                    $flats[$trKey][$this->header['column_names'][$i]] = !$hasStatus ? $cleanedTds[$i]->getText() : 0;
                                }else{
                                    $hasStatus = in_array($cleanedTds[$i-1]->getText(), ['Rezervovaný', 'Predaný']);
                                    $flats[$trKey][$this->header['column_names'][$i]] = $hasStatus ? $cleanedTds[$i-1]->getText() : 'Voľný';
                                }
                            }
                        }
                    }
                }
                
                
                $this->dataFlats[] = array_values($flats);
            });
            
        };
        
        $flats = [];
        
        foreach ($this->dataFlats as $dataFlat) {
            foreach ($dataFlat as $attributes) {
                array_push($flats, $attributes);
            }
        }
        
        $cleanedFlats = [];
        
        foreach ($flats as $flat) {
            $cleanedFlat = [];
            
            foreach ($flat as $col => $data) {
                $columnName = parseColumns($col);
                $cleanedData = parseData($columnName, $data);
                
                if ($columnName) {
                    $cleanedFlat[$columnName] = $cleanedData;
                }
            }
            
            $cleanedFlats[] = $cleanedFlat;
        }
        
        
        // NOTE: scraping status
        if (count($flats) > 0) {
            if ($this->errors['header']['has_error']) {
                $this->status['scraped'] = 'yes - with errors';
            } else {
                $this->status['scraped'] = 'yes';
            }
            
            $this->status['scraped_flats'] = count($flats);
        }
        
        
        // NOTE: move yesterday json
        File::move('storage/scraped/' . $this->config['slug'] . '.json', 'storage/scraped/' . Carbon::yesterday()->toDateString() . '/' . $this->config['slug'] . '.json');
        
        
        // NOTE: create and save json file
        File::put('storage/scraped/' . $this->config['slug'] . '.json', json_encode([
            'header' => $this->header,
            'errors' => $this->errors,
            'status' => $this->status,
            'raw_flats' => $flats,
            'clean_flats' => $cleanedFlats
        ], JSON_UNESCAPED_UNICODE));
        
        
    }
}