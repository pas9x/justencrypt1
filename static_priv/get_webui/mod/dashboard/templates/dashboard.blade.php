@extends('cabinet')

@section('modcontent')
<p>Версия панели: {{ APP_VERSION }}</p>
<p>Серверное время: {{ date('d.m.Y H:i:s') }} UTC</p>
@if ($errorLog)
<p>Внимание: обнаружены проблемы в работе панели. Файл userdata/error.log не пустой.</p>
@endif
@stop