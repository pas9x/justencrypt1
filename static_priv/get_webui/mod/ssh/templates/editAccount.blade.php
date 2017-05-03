@extends('cabinet')

@section('head')
  <link rel='stylesheet' href='/css/mod_ssh.css' media='all'>
  <script type='text/javascript' src='/js/mod_ssh.js'></script>
@append

@section('modcontent')
<h3 class='cool'>Настройки SSH-аккаунта</h3>
<form method='POST' action='{{ $action }}' class='sshForm'>
<table class='form'>
  <tr><td>Название аккаунта:</td><td><input type='text' name='sharedName' value='{{ $sharedName }}'></td></tr>
  <tr><td>SSH-сервер:</td><td><input type='text' name='host' value='{{ $host }}'></td></tr>
  <tr><td>Порт:</td><td><input type='text' name='port' size='6' value='{{ $port }}'></td></tr>
  <tr><td>Логин:</td><td><input type='text' name='login' value='{{ $login }}'></td></tr>
  <tr>
    <td>Тип авторизации:</td>
    <td>
      <label><input type='radio' name='authType' value='{{ \app\SSH::ACCESS_TYPE_PASSWORD }}' {{ $authType === \app\SSH::ACCESS_TYPE_PASSWORD ? 'checked' : '' }}> По паролю</label><br>
      <label><input type='radio' name='authType' value='{{ \app\SSH::ACCESS_TYPE_KEY }}' {{ $authType === \app\SSH::ACCESS_TYPE_KEY ? 'checked' : '' }}> По ключу</label>
    </td>
  </tr>
  <tr id='authPass'>
    <td>Новый пароль:</td>
    <td><input type='text' name='pass'></td>
  </tr>
  <tr id='authKey' class='hidden'>
    <td>Новый приватный ключ:</td>
    <td><textarea name='key' cols='67' rows='10'></textarea></td>
  </tr>
  <tr><td colspan='2'>
    <hr>
    <input type='hidden' name='idSsh' value='{{ $idSsh }}'>
    <input type='submit' value='Сохранить'>
  </td></tr>
</table>
</form>
@stop