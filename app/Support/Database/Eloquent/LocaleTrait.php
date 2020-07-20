<?php

namespace SingPlus\Support\Database\Eloquent;

use App;


Trait LocaleTrait
{
    /**
     * Trans database field
     *
     * @params mixed            messages will be translated
     * @param ?string           locale
     *
     * @return ?string          translated message
     */
    protected function translateField($message, ?string $locale = null) : ?string
    {
        if (is_string($message) || is_null($message)) {
            return $message;
        }
        if ( ! is_array($message)) {
            return null;
        }

        $locale = $locale ?: App::getLocale();

        return array_get($message, $locale);
    }
}
