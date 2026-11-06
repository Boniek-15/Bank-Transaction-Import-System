<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Iban implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $iban = strtoupper(str_replace(' ', '', $value));
        if (!preg_match('/^PL\d{2}[A-Z0-9]{24}$/', $iban))
        {
            $fail('The IBAN is invalid.');
            return;
        }
        $mod = '';
        $fourChars = substr($iban, 0, 4);
        $iban = substr($iban, 4);
        $iban .= $fourChars;
        foreach (str_split($iban) as $char) {
            if(ctype_alpha($char)){
                $mod .= ord($char)-55;
            }
            else{
                $mod .= $char;
            }
        }
        $remainder = 0;
        $mod = str_split($mod);
        foreach ($mod as $char) {
            $remainder = ($remainder * 10 + $char) % 97;
        }
        if($remainder !== 1){
            $fail('The IBAN is invalid.');
            return;
        }
    }
}
