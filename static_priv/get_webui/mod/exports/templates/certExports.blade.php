@extends('cabinet')

@section('head')
  {{--<link rel='stylesheet' href='/css/mod_cert.css' media='all'>--}}
  <script type='text/javascript' src='/js/mod_exports.js'></script>
@append

@section('modcontent')
<form method='GET' action='/index.php'>
  <input type='hidden' name='get' value='webui'>
  <input type='hidden' name='mod' value='exports'>
  <input type='hidden' name='func' value='newExportForm'>
  <input type='hidden' name='idCert' value='{{ $idCert }}'>
  Создать выгрузку для:
  <select name='exporterName'>
    {!! listOptions($exporters) !!}
  </select>
  <input type='submit' value='&gt;&gt;'>
  <strong style='float:right; margin:3px 10px 3px 0'>Сертификат: {{ $cert['domain'] }}</strong>
  <hr>
</form>

@if (empty($exports))
  <i>Конфигураций выгрузки нет</i>
@else
  <table class='grid'>
    <tr>
      <th>ID</th>
      <th>ПО</th>
      <th>Синхронизация</th>
      <th>Статус</th>
      <th>Дата статуса</th>
      <th>Действие</th>
    </tr>
    @foreach($exports as $export)
    <tr>
      <td>{{ $export['idExport'] }}</td>
      <td>{{ $export['exporterTitle'] }}</td>
      <td>{{ $export['sync'] ? 'Да' : 'Нет' }}</td>
      <td>{{ $export['status'] }}</td>
      <td>{{ ($export['lastDate'] > 0) ? date('d.m.Y', $export['lastDate']) : 'Никогда' }}</td>
      <td>
        [ <a href='{{ $export['deleteLink'] }}' onclick='return confirmDelete()'>Удалить</a>
        | <a href='{{ $export['editLink'] }}'>Настроить</a>
        | <a href='{{ $export['startLink'] }}'>Выгрузить</a>
        ]
      </td>
    </tr>
    @endforeach
  </table>
@endif
@stop