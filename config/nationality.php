<?php

return [
  'operation_country_abbr'  => env('OPERATION_COUNTRY_ABBR')
                                ? explode(',', env('OPERATION_COUNTRY_ABBR'))
                                : [],
];
