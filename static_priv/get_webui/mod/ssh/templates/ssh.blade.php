@extends('cabinet')

@section('head')
  <script type='text/javascript' src='/js/mod_ssh.js'></script>
@append

@section('modcontent')
@if (empty($accounts))
  <i>Нет аккаунтов</i>
@else
  <table class='grid'>
    <tr>
      <th>ID</th>
      <th>Название</th>
      <th>Сервер</th>
      <th>Действие</th>
    </tr>
  @foreach ($accounts as $account)
    <tr>
      <td>{{ $account['idSsh'] }}</td>
      <td>{{ $account['sharedName'] }}</td>
      <td>{{ $account['displayHost'] }}</td>
      <td>[ <a href='{{ $account['editLink'] }}'>Редактировать</a> |
            <a href='{{ $account['delLink'] }}' onclick='return confirmDelete()'>Удалить</a> ]
      </td>
    </tr>
  @endforeach
  </table>
@endif
@stop