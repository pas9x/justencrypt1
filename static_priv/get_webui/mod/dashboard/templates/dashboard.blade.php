@extends('cabinet')

@section('head')
  <style type='text/css'>.modcontent p {margin-bottom:6px}</style>
@append

@section('modcontent')
<p>Версия панели: {{ RELEASE_VERSION }}</p>
@if (RELEASE_COMMIT)<p>Коммит: {{ RELEASE_COMMIT }}</p>@endif
@if (RELEASE_TIMESTAMP)<p>Дата сборки: {{ date('d.m.Y H:i', RELEASE_TIMESTAMP) }}</p>@endif
<p>Серверное время: {{ date('d.m.Y H:i:s') }} UTC</p>
@if ($errorLog)
<p>Внимание: обнаружены проблемы в работе панели. Подробности в файле userdata/error.log.</p>
@endif
@stop