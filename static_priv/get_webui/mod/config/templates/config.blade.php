@extends('cabinet')

@section('modcontent')
<form method='POST' action='{{ $action }}'>
<table class='form'>
  <tr><th colspan='2' class='header'>Общие настройки</th></tr>
  <tr><td>Время жизни сессии:</td><td><input type='text' name='sessionLifetime' value='{{ $sessionLifetime }}' size='6' data-hint='* Срок жизни сессии с момента последнего посещения панели. Если не заходить в панель долгое время - сессия завершается автоматически.'> секунд</td></tr>
  <tr><td>Интервал между попытками входа:</td><td><input type='text' name='authInterval' value='{{ $authInterval }}' size='6' data-hint='* Минимально допустимый интервал между попытками входа в панель. Это нужно для защиты от перебора пароля.'> секунд</td></tr>
  <tr><td>Продлевать сертификаты за:</td><td><input type='text' name='prolongDaysEarly' value='{{ $prolongDaysEarly }}' size='6' data-hint='* Продлевать SSL-сертификаты нужно заблаговременно. За сколько дней до окончания срока действия сертификата следует его перевыпустить. Этот параметр используется скриптом cron.php.'> дней</td></tr>
  <tr><td>Новый пароль администратора:</td><td><input type='text' name='newPass' data-hint='* Новый пароль для входа в панель. Оставьте это поле пустым если вы не хотите менять ваш пароль.'></td></tr>

  <tr><th colspan='2' class='header'>Аккаунт Let`s Encrypt</th></tr>
  <tr><td>ID аккаунта:</td><td><input type='text' name='leAccountID' value='{{ $leAccountID }}' data-hint='* ID аккаунта на стороне удостоверяющего центра Let`s Encrypt'></td></tr>
  <tr><td>E-mail:</td><td><input type='text' name='adminEmail' value='{{ $adminEmail }}' data-hint='* Адрес e-mail на который зарегистрирован ваш аккаунт Let`s Encrypt'></td></tr>
  <tr><td colspan='2'>
    Приватный ключ аккаунта:<br>
    <textarea name='leAccountKey' cols='67' rows='10'>{{ $leAccountKey }}</textarea><br>
    [ <a href='{{ $leRegisterLink }}'>Зарегистрировать новый LE-аккаунт</a> ]
  </td></tr>

  <tr><th colspan='2' class='header'>Шаблон CSR</th></tr>
  <tr><td>Код страны:</td><td><input type='text' name='countryName' value='{{ $defaultCsrTemplate['countryName'] }}' data-hint='* Двухбуквенный код страны владельца SSL-сертификата'></td></tr>
  <tr><td>Область/Штат:</td><td><input type='text' name='stateOrProvinceName' value='{{ $defaultCsrTemplate['stateOrProvinceName'] }}' data-hint='* Область/регион/штат в которой находится владелец сертификата'></td></tr>
  <tr><td>Город:</td><td><input type='text' name='localityName' value='{{ $defaultCsrTemplate['localityName'] }}' data-hint='* Название города в котором находится владелец сертификата'></td></tr>
  <tr><td>Название организации:</td><td><input type='text' name='organizationName' value='{{ $defaultCsrTemplate['organizationName'] }}' data-hint='* Имя организации на которую регистрируется сертификат. Физическим лицам следует указывать значение &quot;Private Person&quot;'></td></tr>
  <tr><td>Отдел:</td><td><input type='text' name='organizationalUnitName' value='{{ $defaultCsrTemplate['organizationalUnitName'] }}' data-hint='* Название отдела организации который отвечает за регистрацию SSL-сертификата. Физическим лицам можно указывать любое значение.'></td></tr>

  <tr><td colspan='2'>
    <hr>
    <input type='submit' value='Сохранить настройки'>
  </td></tr>
</table>
<div id='hint'></div>
</form>
@stop