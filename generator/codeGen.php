<?php

include_once '../config/database.php';

class CodeGenerator{
    private $text;
    private $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    
    public function generate_code($length = 6) {
        $code = '';
        $charactersLength = strlen($this->characters);
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $this->characters[rand(0, $charactersLength - 1)];
        }
        
        return $code;
    }
}

?>