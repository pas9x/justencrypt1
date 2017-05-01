@extends('cabinet')

@section('head')
  <link rel='stylesheet' href='/css/mod_cert.css' media='all'>
  <script type='text/javascript' src='/js/mod_cert.js'></script>
@append

@section('modcontent')
@if (empty($certificates))
  <i>Сертификатов нет</i>
@else
  <table class='grid'>
    <tr>
      <th>ID</th>
      <th>Домен</th>
      <th>Годен до</th>
      <th>Действие</th>
    </tr>
    @foreach($certificates as $cert)
    <tr>
      <td>{{ $cert['idCert'] }}</td>
      <td>{{ $cert['domain'] }}</td>
      <td class='{{ $cert['color'] }}'>{{ date('d.m.Y', $cert['expireTimestamp']) }}</td>
      <td>
        [ <a href='{{ $cert['delLink'] }}' onclick='return confirmDelete()'>Удалить</a>
        | <a href='{{ $cert['showLink'] }}'>Показать детали</a>
        ]
      </td>
    </tr>
    @endforeach
  </table>
@endif
@stop