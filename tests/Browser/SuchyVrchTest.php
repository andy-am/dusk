<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use File;
use Carbon\Carbon;
use Facebook\WebDriver\WebDriverBy;

class SuchyVrchTest extends DuskTestCase
{
    private $config = [
        'url' => 'https://www.suchyvrch.sk/cennik',
        'wait_time' => 2000,
        'name' => 'Suchý vrch',
        'slug' => 'suchyvrch',
        'pricelist_head_element' => 'table thead',
        'pricelist_body_element' => 'table tbody',
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
     * @group suchyvrch
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
            
            // SPECIAL – running scripts like clicking a button to load more flats, accept cookies to access website, scrolling, etc.
//            $browser->driver->executeScript('document.querySelector(\'.element-class\').click();');
//            $browser->driver->executeScript('document.querySelectorAll(\'.element-class\').forEach(element => element.style.display=\'initial\');');
//            $browser->driver->executeScript('window.scrollTo(0, 25000);');
            //> div > table > tbody > tr:nth-child(1)
            // NOTE: header scraping and check
            $ths = $browser->elements($this->config['pricelist_head_element'].' > tr th');
            
            foreach($ths as $th) {
                $classes = explode(' ', $th->getAttribute('class'));
                $text = cleanText($th->getText());
                
                
                if(!empty($text) && canBeUsedByClassName($classes)) {
                    $header['column_names'][] = $text;
                }
            }
            
            //dd($header);
            
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
            $trs = $browser->elements($this->config['pricelist_body_element'].' > tr');
            
            
            foreach($trs as $trKey => $tr) {
                
                if($this->config['pricelist_excluded_rows']) {
                    $classes = explode(' ', $tr->getAttribute('class'));
                    
                    if(in_array($this->config['pricelist_allowed_rows_class'], $classes)) {
                        $tds = $browser->elements($this->config['pricelist_body_element'].' > tr:nth-child('.($trKey).') > td');
                        
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
                    $tds = $browser->elements($this->config['pricelist_body_element'].'> tr:nth-child('.($trKey).') > td');
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
                            
                            if($this->config['use_cell_html']) {
                                $flats[$trKey][$header['column_names'][$i]] = trim(urldecode(html_entity_decode(strip_tags($cleanedTds[$i]->getAttribute('innerHTML')))));
                            } else {
                                $flats[$trKey][$header['column_names'][$i]] = $cleanedTds[$i]->getText();
                            }
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
                    $cleanedData = parseData($columnName, $data);
                    
                    if($columnName) {
                        $cleanedFlat[$columnName] = $cleanedData;
                    }
                }
                
                // SPECIAL - if you want a specific format of a column, you can do it here
//                $objectBlock = explode(' ', $cleanedFlat['object_block']);
//                $cleanedFlat['object_block'] = $objectBlock[0];
//                $cleanedFlat['flat_name'] = $cleanedFlat['object_block'].'-'.$cleanedFlat['flat_name'];
                
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
