@extends('cabinet')

@section('head')
  <style type='text/css'>.modcontent p {margin-bottom:6px}</style>
@append

@section('modcontent')
<p>Версия панели: {{ APP_VERSION }}</p>
<p>Серверное время: {{ date('d.m.Y H:i:s') }} UTC</p>
@if ($errorLog)
<p>Внимание: обнаружены проблемы в работе панели. Подробности в файле userdata/error.log.</p>
@endif
@stop