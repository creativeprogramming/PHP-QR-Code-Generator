<?php

class QR {
    protected $code = array();
    protected $version;
    protected $size;
    
    protected $charSet;
    
    const CHARSET_NUMERIC      = 0;
    const CHARSET_ALPHANUMERIC = 1;
    const CHARSET_LATIN        = 2;
    const CHARSET_KANJI        = 3;
    
    // Error Correction Level
    const ECL_L = 1; // 7%
    const ECL_M = 0; // 15%
    const ECL_Q = 3; // 25%
    const ECL_H = 2; // 30%
    
    /**
     * The following is the list of Alphanumeric characters with their associated
     * character value.
     *  
     * @var array
     */
    protected $alphanumericChars = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', ' ', '$', '%', '*', '+', '-', '.', '/', ':');
    
    
    // Quiet zone is 4 pixels around the outside of 0's
    
    public function __construct($string, $version) {
        $this//->determineCharSet($string)
             ->createBlankCode($version)
             ->addFinderPatterns()
             ->addTimingPattern()
             ->addAlignmentPatterns()
             ->addVersionInformation()
             ->addFormatInformation();
    }
    
    public function determineCharSet($string) {
        if (preg_match('/^[0-9]+$/', $string)) {
            $this->charSet = QR::CHARSET_NUMERIC;
        } elseif (preg_match('|^[0-9A-Z $%*+./:-]+$|', $string)) {
            $this->charSet = QR::CHARSET_ALPHANUMERIC;
        } elseif (preg_match('|^[0-9A-Z $%*+./:-]+$|', $string)) {
            $this->charSet = QR::CHARSET_LATIN;
        } else {
            $this->charSet = QR::CHARSET_KANJI;
        }
        
        return $this;
    }
    
    public function addFormatInformation() {
        $formatInformation = array_fill(0, 15, 0);
        
        // TODO actually figure out format information
        
        $fragment = array_reverse(array_slice($formatInformation, 0, 8));
        $this->addPattern($fragment, 8, $this->size - 8);
        
        $fragment = array();
        for ($i = 8; $i < 15; $i++) {
            $fragment[] = array($formatInformation[$i]);
        }
        $this->addPattern($fragment, $this->size - 7, 8);
        
        $fragment = array();
        for ($i = 0; $i < 6; $i++) {
            $fragment[] = array($formatInformation[$i]);
        }
        $this->addPattern($fragment, 0, 8);
        
        $fragment = array();
        for ($i = 6; $i < 8; $i++) {
            $fragment[] = array($formatInformation[$i]);
        }
        $this->addPattern($fragment, 7, 8);
        
        $this->markCode(8, 7, $formatInformation[8]);
        
        $fragment = array_reverse(array_slice($formatInformation, 9, 6));
        $this->addPattern($fragment, 8, 0);
        
        $this->markCode(4 * $this->version + 9, 8, 1);
    }
    
    public function createBlankCode($version) {
        $this->version = $version;
        $this->size = 17 + ($version * 4);
        
        $this->code = array_fill(0, $this->size, array_fill(0, $this->size, 0));
        return $this;
    }
    
    public function addFinderPatterns() {
        $finderPattern = array(
            array(1,1,1,1,1,1,1),
            array(1,0,0,0,0,0,1),
            array(1,0,1,1,1,0,1),
            array(1,0,1,1,1,0,1),
            array(1,0,1,1,1,0,1),
            array(1,0,0,0,0,0,1),
            array(1,1,1,1,1,1,1),
        );
        
        $this->addPattern($finderPattern, 0, 0);
        $this->addPattern($finderPattern, $this->size - 7, 0);
        $this->addPattern($finderPattern, 0, $this->size - 7);
        return $this;
    }
    
    public function addTimingPattern() {
        for ($i = 8; $i < $this->size - 8; $i++) {
            $this->markCode(6, $i, ($i + 1) % 2);
            $this->markCode($i, 6, ($i + 1) % 2);
        }
        
        return $this;
    }
    
    // TODO make this function dynamic
    public function addAlignmentPatterns() {
        $alignmentPattern = array(
            array(1,1,1,1,1),
            array(1,0,0,0,1),
            array(1,0,1,0,1),
            array(1,0,0,0,1),
            array(1,1,1,1,1),
        );
        
        $coordinates = array(6);
        
        if ($this->version > 34) {
            $coordinates[] = ($this->version % 3) * 4 + floor($this->version / 3) * 2;
        }
        
        if ($this->version > 27) {
            $temp = array(26,30,26,30,34,30,34,54,50,54,58,54,58);
            $coordinates[] = $temp[$this->version - 28];
        }
        
        if ($this->version > 20) {
            $temp = array(28,26,30,28,32,30,34,50,54,52,56,60,58,62,78,76,80,84,82,86);
            $coordinates[] = $temp[$this->version - 21];
        }
        
        if ($this->version > 13) {
            $temp = array(26,26,26,30,30,30,34,50,50,54,54,58,58,62,74,78,78,82,86,86,90,102,102,106,110,110,114);
            $coordinates[] = $temp[$this->version - 14];
        }
        
        if ($this->version > 6) {
            $temp = array(22,24,26,28,30,32,34,46,48,50,54,56,58,62,72,74,78,80,84,86,90,98,102,104,108,112,114,118,126,128,132,136,138,142);
            $coordinates[] = $temp[$this->version - 7];
        }
                
        if ($this->version > 1) {
            $coordinates[] = $this->version * 4 + 10;
        }
        
        foreach ($coordinates as $a) {
            foreach ($coordinates as $b) {
                if (($a == 6 && $b == 6)
                        || ($a == 6 && $b == $this->size - 7)
                        || ($a == $this->size - 7 && $b == 6)) {
                    continue;
                }
                
                $this->addPattern($alignmentPattern, $a, $b, true);
            }
        }
        
        return $this;
    }
    
    
    
    public function addVersionInformation() {
        if ($this->version < 7) {
            return;
        }
        
        $versionInformation = array_fill(0, 18, 0);
        
        $versionBin = decbin($this->version);
        
        for ($i = 0, $max = strlen($versionBin); $i < $max; $i++) {
            $versionInformation[$i + 6 - $max] = $versionBin[$i];   
        }
        
        // TODO add BCH to versionInformation
        
        $horizontalPattern = array_fill(0, 3, array_fill(0, 6, 0));
        $verticalPattern   = array_fill(0, 6, array_fill(0, 3, 0));
        
        for ($i = 0; $i < 6; $i++) {
            for ($j = 0; $j < 3; $j++) {
                $verticalPattern[$i][$j]   = $versionInformation[($i * 3) + $j];
                $horizontalPattern[$j][$i] = $versionInformation[($i * 3) + $j];
            }
        }
        
        $this->addPattern($horizontalPattern, $this->size - 11, 0);
        $this->addPattern($verticalPattern, 0, $this->size - 11);
        
        return $this;
    }
    
    public function addPattern($pattern, $i, $j, $centered = false) {
        if ($centered) {
            $i -= floor(count($pattern) / 2);
            $j -= floor(count($pattern[0]) / 2);
        }
        
        foreach ($pattern as $i1 => $a) {
            if (!is_array($a)) {
                $this->markCode($i, $i1 + $j, $a);
            } else {
                foreach ($a as $j1 => $b) {
                    $this->markCode($i1 + $i, $j1 + $j, $b);
                }
            }
        }
        return $this;
    }
    
    public function markCode($i, $j, $value) {
        $this->code[$i][$j] = $value;
    }
    
    public function printCode() {
        echo '<pre>';
        foreach ($this->code as $a) {
            foreach ($a as $b) {
                echo $b;
            }
            echo '<br />';
        }
            
        echo '</pre>';
    }    
}

$qr = new QR(isset($_GET['s']) ? $_GET['s'] : 'test', isset($_GET['v']) ? $_GET['v'] : 1);
$qr->printCode();