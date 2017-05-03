@extends('cabinet')

@section('head')
  <link rel='stylesheet' href='/css/mod_exports.css' media='all'>
  <script type='text/javascript' src='/js/mod_exports.js'></script>
@append

@section('modcontent')
<h3 class='cool'>Новая конфигурация выгрузки</h3>
<form method='POST' action='{{ $action }}' class='exportForm'>
  <table class='form'>
    <tr><td>Конфигурация выгрузки для:</td><td><strong>{{ $exporterTitle }}</strong></td></tr>
    <tr><td>Домен:</td><td><strong>{{ $cert['domain'] }}</strong></td></tr>
    @include($exporterName . '.new')
    <tr><td>После выгрузки выполнить команду:</td><td><input type='text' name='finalCommand' size='50' placeholder='{{ $exampleFinalCommand }}' data-hint='* Команда которая будет выполнена на сервере после успешной выгрузки файлов сертификата. Например, после обновления сертификата может понадобиться перезагрузка веб-сервера.'></td></tr>
    <tr><td colspan='2'>
      <hr>
      <input type='hidden' name='exporterName' value='{{ $exporterName }}'>
      <input type='hidden' name='idCert' value='{{ $idCert }}'>
      <input type='submit' value='Добавть'>
      <a href='{{ $exportsLink }}' class='exportsLink'>Вернуться к списку</a>
    </td></tr>
  </table>
</form>
<div id='hint'></div>
@stop