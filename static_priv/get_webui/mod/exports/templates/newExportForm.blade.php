@extends('cabinet')

@section('modcontent')
<form method='POST' action='{{ $action }}'>
  <table class='form'>
    <tr><td>Конфигурация выгрузки для:</td><td><strong>{{ $exporterTitle }}</strong></td></tr>
    <tr><td>Домен:</td><td><strong>{{ $cert['domain'] }}</strong></td></tr>
    @include($exporterName . '.new')
    <tr><td>После выгрузки выполнить команду:</td><td><input type='text' name='finalCommand' size='50' placeholder='{{ $exampleFinalCommand }}'></td></tr>
    <tr><td colspan='2'>
      <hr>
      <input type='hidden' name='exporterName' value='{{ $exporterName }}'>
      <input type='hidden' name='idCert' value='{{ $idCert }}'>
      <input type='submit' value='Добавть'>
    </td></tr>
  </table>
</form>
@stop