<?php


if(!function_exists('demo_helper_function')) {
    function demo_helper_function()
    {
        return 'demo';
    }
}


if(!function_exists('cleanText')) {
    function cleanText($text)
    {
        return str_replace(["\n", "\r", "-"], ' ', html_entity_decode($text));
    }
}

if(!function_exists('cleanAccents')) {
    function cleanAccents($text)
    {
        $accents = [
            'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
            'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
            'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
            'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
            'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
            'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
            'Ľ'=>'L', 'ľ'=>'l', 'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r', '['=>'', ']'=>'', '('=>'', ')'=>'', '*'=>'', 'm²'=>'m2',
            '’'=>'','ť'=>'t','Ť'=>'T',',–EUR'=>'','\\'=>'','²'=>'2','   '=>' '
        ];

        return strtr($text, $accents);
    }
}


if(!function_exists('parseColumns')) {
    function parseColumns($col) {
        $columnMap = [
            //'object_name' => [''],
            //'object_status' => [''],
            'special_id_domu' => ['id domu'],
            'special_pozemok' => ['pozemok'],
            'object_block' => ['dom','blok','vila','bytovy dom','objekt','budova','rezidencia'],
            'etape' => ['faza','etapa','etapa projektu'],
            'flat_type' => ['typ','typ priestoru','typ bytu'],
            'flat_name' => ['id','nazov','cislo bytu','cislo apartmanu','byt c.','cislo','vily koliba','cislo domu','oznacenie priestoru','byt','apartman','cislo jednotky','oznacenie','pozemok c.','pozemok','cislo objektu/ bytu','apartment number','typy','oznacenie bytu','parcela','por. c','cislo pozemku','byt cislo','nazov bytu'],
            'flat_floor' => ['podlazie','poschodie','np','floor','podlazie podlazie','posch','posch.', 'poschodia'],
            'flat_rooms' => ['pocet izieb','izby','kategoria','dispozicia','izieb','pocet izieb            1 izba            2 izby            3 izby            4 izby','izbovost','number of rooms','pocet izieb pocet izieb','typ domu','pocet izbieb', 'izba'],
            'flat_area_netto' => ['interier m2','interier','rozloha','rozloha m2','vymera interieru','obytna plocha','uzitkova plocha','byt v m2','vymera int. m2','plocha bytu m2','plocha interieru','byt m2','uzitkova plocha m2','vymera bytu','podlahova plocha bez balkona m2','vymera bytu m2','plocha','plocha m2','plocha bytu','plocha bytu /m2','uzitkova plocha domu','vymera v m2','interior','podlahova plocha v m2','up','byt m2','vymera domu','podlahova plocha','rozloha bytu', 'obytby priesor', 'plocha bytu (m2)'],
            'flat_area_brutto' => ['spolu m2','spolu','celkova vymera','plocha spolu','vymera spolu m2','spolu v m2','celkova plocha m2','celkova vymera m2','vymera spolu','celkova plocha','celk. plocha','plocha celkom m2','celkova plocha /m2','rozloha spolu','zastavana plocha domu', 'vymera', 'celk. plocha m2'],
            'flat_area_exterier' => ['exterier m2','exterier','vymera ext. m2','plocha exterieru','exterior','loggia, terasa, predzahradka'],
            'flat_terrace' => ['terasa', 'loggia, terasa','terasa m2','vymera terasy','terasa/ predzahradka','zahrada terasa','terasa / balkon','vymera terasy m2','zahrada s terasou /balkon','zahrada + terasa', 'stresna terasa', 'terasa 2m2'],
            'flat_balcony' => ['balkon','balkon m2', 'balkon v m2','balkon 1 m2','loggia/ balkon','balkon / terasa m2','balkon / logia m2', 'balkon / lodzia / terasa m2','vymera balkona m2','pris.','loggia / balkon m2','balkon terasa /m2','balkon / lodzia / terasa v m2','balkon / lodzia','balkon/terasa','loggia 1/ loggia 2','rozloha balkonu', 'balkon/terasa/zahrada', 'balkon', 'terasa 1m2'],
            'flat_loggia' => ['loggia','lodzia','balkon 2 m2','loggia/terasa m2','vymera loggie m2','loggia/ terasa m2'],
            'flat_garden' => ['predzahradka','zahrada','predzahradka m2','pred  zahradka m2','celkova plocha pozemku','predzah  radka m2', 'pozemok', 'zahradka','plocha predzahradky v m2','z toho vymera zahrady','pozemok m2','vymera pozemku'],
            'flat_cellar' => ['pivnica', 'pivnicna kobka','pivnicna kobka m2','pivnica m2','sklad','vymera pivnice m2','kobka m2', 'kobka'],
            'flat_sale_price' => ['zvyhodnena cena', 'zvyhodnena cena vratane bonusu','predpredajova cena','predpredajna cena s dph','cena s predkolaudacnou zlavou','cena prevedenia','akciova cena','uvadzacia cena', 'Predajna cena'],
            'flat_price' => ['cennikova cena','cena','cena s dph','suma','cena domu s dph','cena domu','cena bytu s dph','cena s dph €','cena po kolaudacii','spolu eur','cena vr. dph','cena s dph holobyt','cena standard','cena holobyt', 'celkova cena','cena domu v standarde','aktualne info','cena bytu bez gar. statia','cena bytu bez gar statia','cena bytu','predajna cena domu vr. pozemku','cena s domom','cena €'],
            'flat_rent_price' => ['cena prenajmu'],
            'flat_rent_fees' => ['energie a poplatky'],
            'flat_monthly_payment' => ['mesacna splatka','mesacna splatka od','mes. splatka'],
            'flat_status' => ['stav','dostupnost','stav bytu','aktualny stav','flat_status','availability','stav stav','status'],
            'unset_flat_entrance' => ['vchod'],
            'unset_balcony_1' => ['1. balkon m2'],
            'unset_balcony_2' => ['2. balkon m2'],
            'unset_balcony_3' => ['3. balkon m2'],
            'unset_garden_1' => ['1. predzahradka m2'],
            'unset_garden_2' => ['2. predzahradka m2'],
        ];

        $originalColumn = trim(strtolower(cleanAccents($col)));

        $result = false;

        foreach($columnMap as $colName => $col) {
            $result = array_search($originalColumn, $col);
            
            if($result !== false) {
                $result = $colName;
                break;
            }
        }

        return $result;
    }
}

if(!function_exists('parseData')) {
    function parseData($column, $data) {
        $cleanedData = false;

        if($column == 'object_name') {
            $cleanedData = parseObjectName($data);
        }

        if($column == 'object_status') {
            $cleanedData = parseObjectStatus($data);
        }

        if($column == 'object_block') {
            $cleanedData = parseObjectBlock($data);
        }

        if($column == 'flat_status') {
            $cleanedData = parseFlatStatus($data);
        }

        if($column == 'etape') {
            $cleanedData = parseEtape($data);
        }

        if($column == 'flat_type') {
            $cleanedData = parseFlatType($data);
        }

        if($column == 'flat_name') {
            $cleanedData = parseFlatName($data);
        }

        if($column == 'flat_floor') {
            $cleanedData = parseFlatFloor($data);
        }

        if($column == 'flat_rooms') {
            $cleanedData = parseFlatRooms($data);
        }

        if(in_array($column, ['flat_area_netto', 'flat_area_brutto', 'flat_area_exterier', 'flat_terrace', 'flat_balcony', 'flat_loggia', 'flat_garden', 'flat_cellar','unset_balcony_1','unset_balcony_2','unset_balcony_3','unset_garden_1','unset_garden_2'])) {
            $cleanedData = parseFlatArea($data);
        }

        if(in_array($column, ['flat_sale_price', 'flat_price', 'flat_monthly_payment','flat_rent_price','flat_rent_fees'])) {
            $cleanedData = parseFlatPrice($data);
        }

        if($column == 'unset_flat_entrance') {
            $cleanedData = $data;
        }


        return $cleanedData;
    }
}

if(!function_exists('parseObjectName')) {
    function parseObjectName($data) {
        return strtoupper(cleanAccents(strip_tags($data)));
    }
}

if(!function_exists('parseObjectStatus')) {
    function parseObjectStatus($data) {
        return strtolower(cleanAccents(strip_tags($data)));
    }
}

if(!function_exists('parseObjectBlock')) {
    function parseObjectBlock($data) {
        return strtoupper(cleanAccents(strip_tags($data)));
    }
}

if(!function_exists('parseEtape')) {
    function parseEtape($data) {
        return strtoupper(cleanAccents(strip_tags($data)));
    }
}

if(!function_exists('parseFlatType')) {
    function parseFlatType($data) {
        $data = strtolower(cleanAccents(strip_tags($data)));

        $statusMap = [
            1 => ['byt','byt/apt.','byt apt.'],
            2 => ['apartman', 'apt.', 'apt', 'appart.'],
            3 => ['mezonet'],
            10 => ['obchodny priestor', 'komercia', 'komercny priestor','obchod','obchod. priestor'],
            11 => ['work & live'],
            12 => ['garaz','garaz + kobka'],
            13 => ['sklad','kobka']
        ];

        $result = false;

        foreach($statusMap as $status => $values) {
            $result = array_search($data, $values);

            if($result !== false) {
                $result = $status;
                break;
            }
        }

        return $result;
    }
}

if(!function_exists('parseFlatName')) {
    function parseFlatName($data, $upper = true, $accents = true) {
        $data = trim(strtoupper(cleanAccents(strip_tags($data))));
        $data = str_replace(['.', ','], '', $data);

        return $data;
    }
}

if(!function_exists('parseFlatFloor')) {
    function parseFlatFloor($data) {
        if(strpos($data, 'Prízemie') !== false) {
            $data = str_replace('Prízemie', '0', $data);
        }

        $data = preg_replace('/[^0-9,]/', '', strtoupper(cleanAccents(strip_tags($data))));

        return $data;
    }
}

if(!function_exists('parseFlatRooms')) {
    function parseFlatRooms($data) {
        (string) $data = cleanAccents(strip_tags($data));

        if(strpos($data, ',') === FALSE && strpos($data, '.' === FALSE)) {
            preg_match('/(\d{1,2})/', $data, $matches);
            if(!empty($matches[0])) {
                $data = $matches[0];
            } else {
                $data = 0;
            }
            $data = trim(str_replace(['.','+','kk',' ','izb,','-izbovy','izb, A','izb, B','KK','a viac','-IZBOVY','izby','izba','izieb','-IZOVY','Izbovy','izbovy'], [',','','','','','','','','','','','','','','',''], $data));
        } else {
            $data = trim(str_replace(['.','+','kk',' ','izb,','-izbovy','izb, A','izb, B','KK','a viac','-IZBOVY','izby','izba','izieb','-IZOVY','Izbovy','izbovy'], [',','','','','','','','','','','','','','','',''], $data));
        }

        return $data;
    }
}

if(!function_exists('parseFlatArea')) {
    function parseFlatArea($data) {
        return floatval(str_replace(',', '.', (cleanAccents(strip_tags($data)))));
    }
}

if(!function_exists('parseFlatPrice')) {
    function parseFlatPrice($data) {
        return intval(str_replace(['.',',', '€', 'eur', ' '], ['','.', '', '', ''], (cleanAccents(strip_tags(html_entity_decode($data))))));
    }
}

if(!function_exists('parseFlatStatus')) {
    function parseFlatStatus($data) {
        $data = trim(strtolower(cleanAccents(strip_tags($data))));

        $statusMap = [
            1 => ['1','1volny','je v predaji','dostupny','volny','v','volna','nepredava sa','nedostupny','n','vzorovy byt','predpredaj','','2. faza','free','volne','na predaj','volny v','v predaji'],
            2 => ['2','2rezervovany','r','pr','rezervovany','predrezervovany','rezervovana','predbezne rezervovany','predrezervovana','rezerv.','rezervacia','prereserved','reserved','rezervovane','rezervovany r','rez.','predrezervovane'],
            3 => ['3','3predany','p','predany','predana','predane','o','obsadeny','vzorovy byt','sold','predany p'],
            10 => ['prenajaty']
        ];

        $result = false;

        foreach($statusMap as $status => $values) {
            $result = array_search($data, $values);

            if($result !== false) {
                $result = $status;
                break;
            }
        }

        return $result;
    }
}

if(!function_exists('canBeUsedByClassName')) {
    function canBeUsedByClassName($classes) {
        $excludedClasses = ['hidden-xl','visible-xs','hidden-lg','visible-sm','visible-md','price-list__column--star'];

        $result = array_intersect($classes, $excludedClasses);

        if(count($result) > 0) {
            return false;
        } else {
            return true;
        }
    }
}
