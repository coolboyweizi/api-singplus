@php
use SingPlus\Contracts\Musics\Constants\ArtistConstant;
@endphp
{
  "code": 0,
  "message": "",
  "data": {
    "hotArtists": [
@foreach ($data->hotArtists as $artist)
      {
        "id": "{!! $artist->id !!}",
        "artistId": "{!! $artist->artistId !!}",
        "avatar": "{!! $artist->avatar !!}",
        "name": "{!! $artist->name !!}"
      } @if ( ! $loop->last) , @endif
@endforeach
    ],
    "categories": [
      [
        {
          "title": "Male Singers",
          "category": {!! ArtistConstant::CATEGORY_CIVIL_MALE !!}
        },
        {
          "title": "Female Singers",
          "category": {!! ArtistConstant::CATEGORY_CIVIL_FEMALE !!}
        },
        {
          "title": "Bands",
          "category": {!! ArtistConstant::CATEGORY_CIVIL_BAND !!}
        }
      ],
      [
        {
          "title": "Foreign Male Singers",
          "category": {!! ArtistConstant::CATEGORY_ABROAD_MALE !!}
        },
        {
          "title": "Foreign Female Singers",
          "category": {!! ArtistConstant::CATEGORY_ABROAD_FEMALE !!}
        },
        {
          "title": "Foreign Bands",
          "category": {!! ArtistConstant::CATEGORY_ABROAD_BAND !!}
        }
      ]
    ]
  }
}
