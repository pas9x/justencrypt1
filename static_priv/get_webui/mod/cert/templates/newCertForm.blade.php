@extends('cabinet')

@section('head')
  <link rel='stylesheet' href='/css/mod_cert.css' media='all'>
  <script type='text/javascript' src='/js/mod_cert.js'></script>
@append

@section('modcontent')
<h3 class='cool'>Параметры регистрации сертификата</h3>
<form method='POST' action='{{ $action }}' class='certForm'>
<table class='form'>
  <tr><td>Домен:</td><td><input type='text' name='domain' placeholder='mysite.ru' data-hint='* Домен на который будет зарегистрирован сертификат'></td></tr>
  <tr><td>Корневая директория сайта:</td><td><input type='text' name='documentRoot' size='40' placeholder='/home/mysite/public_html' data-hint='* В эту директорию будет загружен файл верификации. Пример: при загрузке в эту директорию файла check.txt он должен быть доступен по адресу http://mysite.ru/check.txt'></td></tr>
  <tr>
    <td>SSH-аккаунт:</td>
    <td>
      <label><input type='radio' name='shared' value='0' id='shared0' onclick='switchShared()' checked>Указать отдельный</label><br>
      <label><input type='radio' name='shared' value='1' id='shared1' onclick='switchShared()' {{ empty($sshAccounts) ? 'disabled' : '' }}>Выбрать существующий</label>
    </td>
  </tr>
  <tr class='sshCustom'><td>SSH-сервер:</td><td><input type='text' name='host' placeholder='123.45.67.89' data-hint='* Имя хоста или IP-адрес SSH-сервера к которому следует подключиться для загрузки нового сертификата.'></td></tr>
  <tr class='sshCustom'><td>Порт:</td><td><input type='text' name='port' size='6' value='22'></td></tr>
  <tr class='sshCustom'><td>Логин:</td><td><input type='text' name='login' placeholder='root'></td></tr>
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
  <tr>
    <td colspan='2'>
    <hr>
    <input type='submit' value='Зарегистрировать сертификат'>
    </td>
  </tr>
</table>
<div id='hint'></div>
<div class='note hidden'>Регистрация сертификата может занять 1-2 минуты. Не закрывайте страницу, дождитесь завершения обработки вашего запроса.</div>
</form>
@stop