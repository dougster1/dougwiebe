<?php

class Encoder {

    public static function encode_string($input) {
        $revs = strrev($input);
        $bs64_1 = base64_encode($revs);
        $revs2 = strrev($bs64_1);
        $bs64_2 = base64_encode($revs2);
        return $bs64_2;
    }

    public static function decode_string($input) {
        $bs64d_1 = base64_decode($input);
        $revs1 = strrev($bs64d_1);
        $bs64d_2 = base64_decode($revs1);
        $revs2 = strrev($bs64d_2);
        return $revs2;
    }

}
