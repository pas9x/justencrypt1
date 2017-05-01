@extends('cabinet')

@section('head')
  <link rel='stylesheet' href='/css/mod_cert.css' media='all'>
  <script type='text/javascript' src='/js/mod_cert.js'></script>
@append

@section('modcontent')
<form method='POST' action='{{ $action }}'>
<table id='certForm' class='form'>
  <tr><td>Домен:</td><td><input type='text' name='domain' value='{{ $cert['domain'] }}' disabled></td></tr>
  <tr><td>Корневая директория сайта:</td><td><input type='text' name='documentRoot' size='40' value='{{ $cert['documentRoot'] }}'></td></tr>
  <tr>
    <td>SSH-аккаунт:</td>
    <td>
      <label><input type='radio' name='shared' value='0' id='shared0' onclick='switchShared()' {{ $ssh['shared'] ? '' : 'checked' }}>Указать отдельный</label><br>
      <label><input type='radio' name='shared' value='1' id='shared1' onclick='switchShared()' {{ $ssh['shared'] ? 'checked' : '' }} {{ empty($sshAccounts) ? 'disabled' : '' }}>Выбрать существующий</label>
    </td>
  </tr>
  <tr class='sshCustom'><td>SSH-сервер:</td><td><input type='text' name='host' value='{{ $ssh['host'] }}'></td></tr>
  <tr class='sshCustom'><td>Порт:</td><td><input type='text' name='port' size='6' value='22' value='{{ $ssh['port'] }}'></td></tr>
  <tr class='sshCustom'><td>Логин:</td><td><input type='text' name='login' value='{{ $ssh['login'] }}'></td></tr>
  <tr class='sshCustom'>
    <td>Тип авторизации:</td>
    <td>
      <label><input type='radio' name='authType' id='authType1' value='1' onchange='switchAuth()' {{ $ssh['authType']===1 ? 'checked' : '' }}> По паролю</label><br>
      <label><input type='radio' name='authType' id='authType2' value='2' onchange='switchAuth()' {{ $ssh['authType']===2 ? 'checked' : '' }}> По ключу</label>
    </td>
  </tr>
  <tr class='sshCustom' id='authPass'>
    <td>Новый пароль:</td>
    <td><input type='text' name='pass'></td>
  </tr>
  <tr class='sshCustom' id='authKey'>
    <td>Новый приватный ключ:</td>
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
  <tr>
    <td colspan='2'>
    <hr>
    <input type='hidden' name='idCert' value='{{ $cert['idCert'] }}'>
    <input type='submit' value='Сохранить настройки'>
    </td>
  </tr>
</table>
</form>
@stop