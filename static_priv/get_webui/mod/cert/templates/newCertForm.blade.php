@extends('cabinet')

@section('head')
  <link rel='stylesheet' href='/css/mod_cert.css' media='all'>
  <script type='text/javascript' src='/js/mod_cert.js'></script>
@append

@section('modcontent')
<form method='POST' action='{{ $action }}'>
<table id='certForm' class='form'>
  <tr><td>Домен:</td><td><input type='text' name='domain'></td></tr>
  <tr><td>Корневая директория сайта:</td><td><input type='text' name='documentRoot' size='40'></td></tr>
  <tr>
    <td>SSH-аккаунт:</td>
    <td>
      <label><input type='radio' name='shared' value='0' id='shared0' onclick='switchShared()' checked>Указать отдельный</label><br>
      <label><input type='radio' name='shared' value='1' id='shared1' onclick='switchShared()' {{ empty($sshAccounts) ? 'disabled' : '' }}>Выбрать существующий</label>
    </td>
  </tr>
  <tr class='sshCustom'><td>SSH-сервер:</td><td><input type='text' name='host'></td></tr>
  <tr class='sshCustom'><td>Порт:</td><td><input type='text' name='port' size='6' value='22'></td></tr>
  <tr class='sshCustom'><td>Логин:</td><td><input type='text' name='login'></td></tr>
  <tr class='sshCustom'>
    <td>Тип авторизации:</td>
    <td>
      <label><input type='radio' name='authType' id='authType1' value='1' onchange='switchAuth()' checked> По паролю</label><br>
      <label><input type='radio' name='authType' id='authType2' value='2' onchange='switchAuth()'> По ключу</label>
    </td>
  </tr>
  <tr class='sshCustom' id='authPass'>
    <td>Пароль:</td>
    <td><input type='text' name='pass'></td>
  </tr>
  <tr class='sshCustom' id='authKey'>
    <td>Приватный ключ:</td>
    <td><textarea name='key' cols='67' rows='10'></textarea></td>
  </tr>
  <tr class='sshShared'>
    <td>Шаблон:</td>
    <td>
      <select name='idSsh'>
      @foreach($sshAccounts as $sshAccount)
        <option value='{{ $sshAccount['idSsh'] }}'>{{ $sshAccount['sharedName'] }}</option>
      @endforeach
      </select>
    </td>
  </tr>
{{--
  <tr>
    <td>Экспортировать файлы для:</td>
    <td>
      <select name='exporter' onchange='switchExporter()'>
      @foreach($exporters as $exporterName => $exporterTitle)
        <option value='{{ $exporterName }}'>{{ $exporterTitle }}</option>
      @endforeach
      </select>
    </td>
  </tr>
  @foreach($exporters as $exporterName => $exporterTitle)
  @include('exporters.' . $exporterName)
  @endforeach
  <tr><td>После экспорта выполнить команду:</td><td><input type='text' name='cmd' size='40'></td></tr>
--}}
  <tr>
    <td colspan='2'>
    <hr>
    <input type='submit' value='Зарегистрировать сертификат'>
    </td>
  </tr>
</table>
</form>
@stop