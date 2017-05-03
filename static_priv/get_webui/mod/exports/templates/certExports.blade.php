@extends('cabinet')

@section('head')

@section('head')
  <link rel='stylesheet' href='/css/mod_exports.css' media='all'>
  <script type='text/javascript' src='/js/mod_exports.js'></script>
@append

@section('modcontent')
<form method='GET' action='/index.php' class='newExportForm'>
  <input type='hidden' name='get' value='webui'>
  <input type='hidden' name='mod' value='exports'>
  <input type='hidden' name='func' value='newExportForm'>
  <input type='hidden' name='idCert' value='{{ $idCert }}'>
  Создать выгрузку для:
  <select name='exporterName'>
    {!! listOptions($exporters) !!}
  </select>
  <input type='submit' value='&gt;&gt;'>
  <span class='certLink'>Сертификат: <a href='{{ $certLink }}'>{{ $cert['domain'] }}</a></span>
  <hr>
</form>

@if (empty($exports))
  <i>Конфигураций выгрузки нет</i>
@else
  <table class='grid'>
    <tr>
      <th data-hint='* Идентификатор конфигурации выгрузки в базе данных'>ID</th>
      <th data-hint='* Название программного обеспечения для которого делается выгрузка SSL-сертификата'>ПО</th>
      <th data-hint='* Выгружен-ли актуальный сертификат на сервер. Рассинхронизация может быть если сертификат перевыпущен, но ещё не выгружен на сервер.'>Синхронизация</th>
      <th data-hint='* Была-ли успешна последняя операция выгрузки сертификата'>Статус</th>
      <th data-hint='* Дата последней выгрузки сертификата'>Дата статуса</th>
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
  <div id='hint'></div>
@endif
@stop