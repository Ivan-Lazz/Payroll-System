<?php

class Base64 {
    private $text;
    private $alphabet;

    public function __construct($text) {
        $this->text = $text;
        $this->alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    }

    public function encrypt() {
        $eightBit = '';
        $encryptedText = '';

        // Convert each character to 8-bit binary
        for ($i = 0; $i < strlen($this->text); $i++) {
            $eightBit .= str_pad(decbin(ord($this->text[$i])), 8, '0', STR_PAD_LEFT);
        }

        // Pad binary string to be divisible by 6
        while (strlen($eightBit) % 6 != 0) {
            $eightBit .= '0';
        }

        // Convert every 6 bits to Base64 characters
        for ($i = 0; $i < strlen($eightBit); $i += 6) {
            $chunk = substr($eightBit, $i, 6);
            $encryptedText .= $this->alphabet[bindec($chunk)];
        }

        // Add padding '=' to make length divisible by 4
        while (strlen($encryptedText) % 4 != 0) {
            $encryptedText .= '=';
        }

        return $encryptedText;
    }
}

class Base64d {
    // private $pas;
    private $alphabet;

    public function __construct() {
        // $this->text = $pas;
        $this->alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    }

    public function decrypt($encryptedText) {
        $decryptedText = '';
        $sixBit = '';

        // Convert Base64 characters to 6-bit binary
        for ($i = 0; $i < strlen($encryptedText); $i++) {
            if ($encryptedText[$i] !== '=') {//removes padding in the conversion
                $sixBit .= str_pad(decbin(strpos($this->alphabet, $encryptedText[$i])), 6, '0', STR_PAD_LEFT);
            }
        }

        // Process 8-bit chunks and convert to ASCII characters
        for ($ch = 0; $ch < strlen($sixBit); $ch += 8) {
            $chunk = substr($sixBit, $ch, 8);//removes 8-bit chunk from the binary and stores it to the variable
            if (strlen($chunk) == 8) { // Ensure valid 8-bit chunk
                $decryptedText .= chr(bindec($chunk));//coverts 8-bit chunk into ASCII
            }
        }

        return $decryptedText;
    }
}

function safety($pass){
    $cipher = new Base64($pass);
    $encryptedText = $cipher->encrypt();

    return $encryptedText;
}

function decrypt($pas){

    $decryptor = new Base64d();
    $decryptedText = $decryptor->decrypt($pas);

    return $decryptedText;
}

?>