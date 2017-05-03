@extends('cabinet')

@section('head')
  <link rel='stylesheet' href='/css/mod_cert.css' media='all'>
  <script type='text/javascript' src='/js/mod_cert.js'></script>
@append

@section('modcontent')
@if (empty($certificates))
  <i>Сертификатов нет</i>
@else
  <h3 class='cool'>Ваши сертификаты</h3>
  <table class='grid certList'>
    <tr>
      <th data-hint='* Идентификатор сертификата в базе данных'>ID</th>
      <th>Домен</th>
      <th data-hint='* Дата ДО которой сертификат считается действительным. В день этой даты сайт/сервис использующий данный сертификат перестаёт работать по SSL.'>Годен до</th>
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
  <div id='hint'></div>
@endif
@stop