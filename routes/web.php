<?php

use Illuminate\Support\Facades\Route;
use Symfony\Component\Panther\PantherTestCase;

Route::get('/', function () {
    //*/return view('welcome');
    
    /*PantherTestCase::startWebServer();
            
            $client = PantherTestCase::createPantherClient();
            
            $crawler = $client->request('GET', 'https://datacvr.virk.dk/soegeresultater?sideIndex=0&enhedstype=virksomhed&antalAnsatte=ANTAL_20_49&virksomhedsstatus=aktiv%252Cnormal&size=10');
            
            // Wait for some JavaScript content to load if needed
            $client->waitFor('.some-element-class');
            
            return $crawler->html();*/
    
});
