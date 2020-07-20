<?php

return [
  'riakcs'  => [
    'key'       => env('RIAKCS_KEY'),
    'secret'    => env('RIAKCS_SECRET'),
    'bucket'    => env('RIAKCS_DEFAULT_BUCKET', 'test-bucket'),
    'scheme'    => env('RIAKCS_SCHEME', 'http'),
    'server'    => env('RIAKCS_SERVER'),                          // riak cs server. <host:port>
    'base_url'  => env('RIAKCS_BASE_URL', ''),                    // file public access base url
  ],

  'buckets' => [
    'ugc'   => env('RIAKCS_DEFAULT_BUCKET', 'test-bucket'),
    'works' => env('RIAKCS_WORKS_BUCKET', 'test-bucket'),
  ],

  'cdn_host'    => env('AWS_CDN_HOST'),
];
