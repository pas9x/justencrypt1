@extends('cabinet')

@section('modcontent')
<form method='POST' action='{{ $action }}'>
<table class='form'>
  <tr><th colspan='2' class='header'>Общие настройки</th></tr>
  <tr><td>Время жизни сессии:</td><td><input type='text' name='sessionLifetime' value='{{ $sessionLifetime }}' size='6'> секунд</td></tr>
  <tr><td>Интервал между попытками входа:</td><td><input type='text' name='authInterval' value='{{ $authInterval }}' size='6'> секунд</td></tr>
  <tr><td>Продлевать сертификаты за:</td><td><input type='text' name='prolongDaysEarly' value='{{ $prolongDaysEarly }}' size='6'> дней</td></tr>
  <tr><td>Новый пароль администратора:</td><td><input type='text' name='newPass'></td></tr>

  <tr><th colspan='2' class='header'>Аккаунт Let`s Encrypt</th></tr>
  <tr><td>ID аккаунта:</td><td><input type='text' name='leAccountID' value='{{ $leAccountID }}'></td></tr>
  <tr><td>E-mail:</td><td><input type='text' name='adminEmail' value='{{ $adminEmail }}'></td></tr>
  <tr><td colspan='2'>
    Приватный ключ аккаунта:<br>
    <textarea name='leAccountKey' cols='67' rows='10'>{{ $leAccountKey }}</textarea><br>
    [ <a href='{{ $leRegisterLink }}'>Зарегистрировать новый LE-аккаунт</a> ]
  </td></tr>

  <tr><th colspan='2' class='header'>Шаблон CSR</th></tr>
  <tr><td>Код страны:</td><td><input type='text' name='countryName' value='{{ $defaultCsrTemplate['countryName'] }}'></td></tr>
  <tr><td>Область/Штат:</td><td><input type='text' name='stateOrProvinceName' value='{{ $defaultCsrTemplate['stateOrProvinceName'] }}'></td></tr>
  <tr><td>Город:</td><td><input type='text' name='localityName' value='{{ $defaultCsrTemplate['localityName'] }}'></td></tr>
  <tr><td>Название организации:</td><td><input type='text' name='organizationName' value='{{ $defaultCsrTemplate['organizationName'] }}'></td></tr>
  <tr><td>Отдел:</td><td><input type='text' name='organizationalUnitName' value='{{ $defaultCsrTemplate['organizationalUnitName'] }}'></td></tr>

  <tr><td colspan='2'>
    <hr>
    <input type='submit' value='Сохранить настройки'>
  </td></tr>
</table>
</form>
@stop