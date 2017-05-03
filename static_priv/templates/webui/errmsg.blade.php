@extends('html')

@section('head')
  <title>Ошибка</title>
@append

@section('body')

<table class='messageScreen'><tr><td>
  <div class='messageBox'>
    <div class='messageHeader'>
      <div class='logo'><img src='/pic/message-logo.png'></div>
      <div class='label'><span>JustEncrypt</span></div>
      <div class='borderLeft'></div>
      <div class='borderRight'></div>
    </div>
@if (count($messages) > 1)
  Обнаружены ошибки:
  <ol>
@foreach ($messages as $message)
  <li>{!! $html ? $message : escapeHTML($message) !!}</li>
@endforeach
  </ol>
@else
  <div>Ошибка: {!! $html ? $messages[0] : escapeHTML($messages[0]) !!}</div>
@endif
    <a href='{{ empty($backLink) ? SITE_URI : $backLink }}' class='backLink'><span>{{ empty($backLabel) ? 'Вернуться назад' : $backLabel }}</span></a>
  </div>
</td></tr></table>

@stop