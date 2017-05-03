@extends('cabinet')

@section('head')
  <link rel='stylesheet' href='/css/mod_ssh.css' media='all'>
  <script type='text/javascript' src='/js/mod_ssh.js'></script>
@append

@section('modcontent')
@if (empty($accounts))
  <i>Нет аккаунтов</i>
@else
  <h3 class='cool'>SSH-аккаунты</h3>
  <table class='grid sshList'>
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
      <td>[ <a href='{{ $account['editLink'] }}'>Редактировать</a>
          | <a href='{{ $account['delLink'] }}' onclick='return confirmDelete()'>Удалить</a>
          ]
      </td>
    </tr>
  @endforeach
  </table>
@endif
@stop