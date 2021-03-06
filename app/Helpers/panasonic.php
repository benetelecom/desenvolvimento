<?php

function panasonic_tda_tde_ns($file,$name){

    $cdrs = Storage::disk('local')->get($file);
    $cdrs = preg_split('/(\r|\n)/', $cdrs);
    
    foreach($cdrs as $cdr):
        $pbx='' ; $calldate=''; $extensions_id=''; $trunks_id=''; $did=''; $direction=''; $dialnumber=''; $callnumber='';
        $ring=''; $billsec=''; $accountcodes_id=''; $projectcodes_id=''; $disposition=''; $status_id=''; $lg=0; 
        
        if(!is_numeric(substr($cdr,0,2)) ):
            continue;
        endif;

        $pbx = $name;
        //date and time
        list($d,$m,$y)   = explode('/',trim(substr($cdr,0,8)));
        $hi              = trim(substr($cdr,9,7));
        $calldate        = "20$y-$m-$d $hi:00";
        $extensions_id   = trim(substr($cdr,17,5));
        $trunks_id       = trim(substr($cdr,23,4));
        $ring            = str_replace("'", ":", trim(substr($cdr,79,4)));
        $billsec         = str_replace("'", ":", trim(substr($cdr,84,8)));
        $accountcodes_id = trim(substr($cdr,105,10));
        $projectcodes_id = trim(substr($cdr,105,10));
        $disposition     = trim(substr($cdr,116,3));
        $ring            = strtotime($ring?'00:0'.$ring:'00:00:00') - strtotime('00:00:00');
        $billsec         = strtotime($billsec?$billsec:'00:00:00') - strtotime('00:00:00');
        $status_id       = 0;
        $continue        = false;
        switch(trim(substr($cdr,28,3))):

            case 'EXT':
                list($a, $dialnumber) = explode('EXT',trim(substr($cdr,28,50)));
                $direction = 'IN';
                break;
                
            case 'LOG':
                list($a, $dialnumber) = explode('LOG',trim(substr($cdr,28,50)));
                $lg=1;
                break;
                
            case '<D>':
                list($a, $did, $dialnumber) = preg_split('/(<D>|<I>)/', trim(substr($cdr,28,50)));
                $callnumber = dialIc($dialnumber, $trunks_id, $pbx);
                $direction = 'IC';
                break;
                
            case '<I>':
                list($a, $dialnumber) = preg_split('/(<D>|<I>)/', trim(substr($cdr,28,50)));
                $callnumber = dialIc($dialnumber, $trunks_id, $pbx);
                $direction = 'IC';
                break;
                        
            default :
                if(is_numeric(trim(substr($cdr,28,2)) ) ):
                    $dialnumber = trim(substr($cdr,28,50));
                    $callnumber = dialOc($dialnumber, $trunks_id, $pbx);
                    $direction = 'OC';
                else:
                    $continue = true;
                endif;
            break;
        endswitch;

            if($continue):
                continue;
                $continue = false;
            endif;
    
        if($lg==1):
            $log = App\Models\Agent::updateOrCreate(
                ['pbx' => $pbx, 
                'logdate' => $calldate, 
                'extensions_id' => $extensions_id, 
                'status_id' => $dialnumber, 
                ],
                
                ['pbx' => $pbx, 
                'logdate' => $calldate, 
                'extensions_id' => $extensions_id, 
                'status_id' => $dialnumber, 
                ]
            );
        else:
            $call = App\Models\Call::updateOrCreate(
                ['pbx' => $pbx, 
                'calldate' => $calldate, 
                'extensions_id' => $extensions_id, 
                'trunks_id' => $trunks_id, 
                'did' => $did, 
                'direction' => $direction, 
                'dialnumber' => $dialnumber==''?'NI':$dialnumber, 
                'ring' => $ring, 
                'billsec' => $billsec, 
                'accountcodes_id' => $accountcodes_id, 
                'projectcodes_id' => $projectcodes_id, 
                'disposition' => $disposition, 
                ],
                
                ['pbx' => $pbx, 
                'calldate' => $calldate, 
                'extensions_id' => $extensions_id, 
                'trunks_id' => $trunks_id, 
                'did' => $did, 
                'direction' => $direction, 
                'dialnumber' => $dialnumber==''?'NI':$dialnumber, 
                'callnumber' => $dialnumber==''?'NI':$callnumber, 
                'ring' => $ring, 
                'billsec' => $billsec, 
                'accountcodes_id' => $accountcodes_id, 
                'projectcodes_id' => $projectcodes_id, 
                'disposition' => $disposition, 
                'status_id' => $status_id, 
                ]
            );
        endif;
        $lg=0;
        //dd($call);
    endforeach;
    return true;
}

        