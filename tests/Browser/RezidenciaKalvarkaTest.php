<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use File;
use Carbon\Carbon;

class RezidenciaKalvarkaTest extends DuskTestCase
{

    private $config = [
        'url' => 'https://www.nefi.sk/rezidenciakalvarka-cennik/',
        'wait_time' => 2000,
        'name' => 'Rezidencia Kalvárka',
        'slug' => 'rezidenciakalvarka',
        'pricelist_head_element' => '#tableWrapper table thead',
        'pricelist_body_element' => '#tableWrapper table tbody',
        'pricelist_excluded_rows' => false,
        'pricelist_allowed_rows_class' => '',
        'assert_text' => false,
        'use_cell_html' => false,
        'yesterday' => '',
        'current_json' => '',
        'prev_json' => '',
        'prev_json_content' => ''
    ];
    
    
    /**
     * @test
     * @group rezidenciakalvarka
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
            
            $browser->pause($this->config['wait_time']);
            
            $browser->driver->executeScript('window.scrollTo(0, 3900);');
            
            $browser->driver->executeScript('document.querySelector(\'.mQxxMq.bqbmwD.Pg7AcP.dukD6Z\').click();');
            
            $browser->driver->executeScript('document.querySelector(\'.LWbAav.Kv1aVt\').click();');
            
            $browser->pause($this->config['wait_time']);
            
            
            $browser->screenshot('xxx');
            
            $ths = $browser->elements($this->config['pricelist_head_element'].'');
            
            //dd(count($ths));
            
            
            foreach($ths as $th) {
                $classes = explode(' ', $th->getAttribute('class'));
                $text = cleanText($th->getText());


                if(!empty($text) && canBeUsedByClassName($classes)) {
                    $header['column_names'][] = $text;
                }
            }
            
            
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
            $trs = $browser->elements($this->config['pricelist_body_element'] . 'tr');
            
            foreach($trs as $trKey => $tr) {
                
                if($this->config['pricelist_excluded_rows']) {
                    $classes = explode(' ', $tr->getAttribute('class'));
                    
                    if(in_array($this->config['pricelist_allowed_rows_class'], $classes)) {
                        $tds = $browser->elements($this->config['pricelist_body_element'].'> td:nth-child('.($trKey+1).')');
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
                        for($i = 0; $i < $header['column_count']; $i++) {
                            if(count($tds) >= ($i+1)) {
                                print_r($header['column_names'][$i] . ':' . $tds[$i]->getText(), true);
                                $flats[$trKey][$header['column_names'][$i]] = $cleanedTds[$i]->getText();
                            }
                        }
                    }
                } else {
                    $tds = $browser->elements($this->config['pricelist_body_element'].'> td:nth-child('.($trKey+1).')');
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
            }
            
            
            // NOTE: reset flat array keys
            $flats = array_values($flats);
            
            
            // NOTE: cleaning raw flats
            foreach($flats as $flat) {
                $cleanedFlat = [];
                
                foreach($flat as $col => $data) {
                    $columnName = parseColumns($col);
                    if ($columnName !== 'flat_area_brutto') {
                        $cleanedData = parseData($columnName, $data);
                        if ($columnName) {
                            $cleanedFlat[$columnName] = $cleanedData;
                        }
                    } else {
                        $cleanedFlat[$columnName] = $data;
                    }
                }
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
