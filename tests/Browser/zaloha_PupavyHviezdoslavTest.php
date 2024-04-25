<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use File;
use Carbon\Carbon;

class zaloha_PupavyHviezdoslavTest extends DuskTestCase
{
    private $config = [
        'url' => 'https://www.pupavyhviezdoslav.sk/byty/',
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
        'prev_json_content' => ''
    ];
    
    /**
     * @test
     * @group zaloha_pupavyhviezdoslav
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
            $ths = $browser->elements($this->config['pricelist_head_element'] . ' tr th');
            
            
            foreach ($ths as $key => $th) {
                $classes = explode(' ', $th->getAttribute('class'));
                $text = cleanText($th->getText());
                if (!empty($text) && canBeUsedByClassName($classes)) {
                    $header['column_names'][] = $text;
                }
            }
            
            $header['column_count'] = count($header['column_names']);
            
            
            if ($header['column_count'] != $this->config['prev_json_content']['header']['column_count']) {
                info($this->config['name'] . ': Column count does not match');
                
                $intersect = array_intersect($header['column_names'], $this->config['prev_json_content']['header']['column_names']);
                
                if (count($intersect) != $header['column_count']) {
                    $errors['header']['missing_items'] = array_diff($header['column_names'], $intersect);
                    $errors['header']['new_items'] = array_diff($this->config['prev_json_content']['header']['column_names'], $intersect);
                    
                    info($this->config['name'] . ' missing items: ' . print_r($errors['header']['missing_items'], true));
                    info($this->config['name'] . ' new items: ' . print_r($errors['header']['new_items'], true));
                    
                    $errors['header']['has_error'] = true;
                }
            } else {
                $intersect = array_intersect($header['column_names'], $this->config['prev_json_content']['header']['column_names']);
                
                
                if (count($intersect) != $header['column_count']) {
                    $errors['header']['missing_items'] = array_diff($header['column_names'], $intersect);
                    $errors['header']['new_items'] = array_diff($this->config['prev_json_content']['header']['column_names'], $intersect);
                    
                    info($this->config['name'] . ' missing items: ' . print_r($errors['header']['missing_items'], true));
                    info($this->config['name'] . ' new items: ' . print_r($errors['header']['new_items'], true));
                    
                    $errors['header']['has_error'] = true;
                }
            }
            $pageCount = 5;
            $trs = $browser->elements($this->config['pricelist_body_element'] . ' tr');
            $pages[] = $trs;
            
            /*foreach (range(2, $pageCount) as $page) {
                $browser->visit($this->config['url']);
                $browser->pause($this->config['wait_time']);
                $browser->driver->executeScript('window.scrollTo(0, 25000);');
                $trs = $browser->elements($this->config['pricelist_body_element'] . ' tr');
                $pages[] = $trs;
            }*/
            
            
            //$trs = array_merge($trs, $data);
            $cleanedTds = [];
            foreach ($pages as $trs) {
                foreach ($trs as $keyTr => $tr) {
                    dd($tr->elements());
                    $tds = $tr->child();
                    foreach($tds as $keyTd => $td){
                        dump($td->getText());
                        $cleanedTds;
                    }
                    //$tds = $tr->elements('td');
                    
                    // Spracovanie elementov TD
                    /*foreach ($tds as $td) {
                        echo $td->getText() . PHP_EOL;
                    }*/
                }
                
            }
            
            exit();
            
            
            // NOTE: reset flat array keys
            $flats = array_values($flats);
            
            
            // NOTE: cleaning raw flats
            foreach ($flats as $flat) {
                $cleanedFlat = [];
                
                foreach ($flat as $col => $data) {
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
            if (count($flats) > 0) {
                if ($errors['header']['has_error']) {
                    $status['scraped'] = 'yes - with errors';
                } else {
                    $status['scraped'] = 'yes';
                }
                
                $status['scraped_flats'] = count($flats);
            }
            
            
            // NOTE: move yesterday json
            File::move('storage/scraped/' . $this->config['slug'] . '.json', 'storage/scraped/' . Carbon::yesterday()->toDateString() . '/' . $this->config['slug'] . '.json');
            
            
            // NOTE: create and save json file
            File::put('storage/scraped/' . $this->config['slug'] . '.json', json_encode([
                'header' => $header,
                'errors' => $errors,
                'status' => $status,
                'raw_flats' => $flats,
                'clean_flats' => $cleanedFlats
            ], JSON_UNESCAPED_UNICODE));
            
            
            // NOTE: assert for test to pass without error
            if ($this->config['assert_text']) {
                $browser->assertSee($this->config['assert_text']);
            }
        });
    }
    
    private function slugify($text, $divider = '-')
    {
        // replace non letter or digits by divider
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);
        
        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        
        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        
        // trim
        $text = trim($text, $divider);
        
        // remove duplicate divider
        $text = preg_replace('~-+~', $divider, $text);
        
        // lowercase
        $text = strtolower($text);
        
        if (empty($text)) {
            return 'n-a';
        }
        
        return $text;
    }
}