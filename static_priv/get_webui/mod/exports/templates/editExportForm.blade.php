@extends('cabinet')

@section('modcontent')
<form method='POST' action='{{ $action }}'>
  <table class='form'>
    <tr><td>Конфигурация выгрузки для:</td><td><strong>{{ $exporterTitle }}</strong></td></tr>
    <tr><td>Домен:</td><td><strong>{{ $cert['domain'] }}</strong></td></tr>
    @include($exporterName . '.edit')
    <tr><td>После выгрузки выполнить команду:</td><td><input type='text' name='finalCommand' size='50' value='{{ $finalCommand }}'></td></tr>
    <tr><td colspan='2'>
      <hr>
      <input type='hidden' name='idExport' value='{{ $idExport }}'>
      <input type='submit' value='Сохранить'>
    </td></tr>
  </table>
</form>
@stop