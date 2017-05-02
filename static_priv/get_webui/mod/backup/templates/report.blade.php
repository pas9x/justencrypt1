@extends('cabinet')

@section('head')
  <link rel='stylesheet' href='/css/mod_backup.css' media='all'>
@append

@section('modcontent')
<p>При импорте бэкапа произошли проблемы:</p>
<ol>
@foreach($errors as $error)
  <li>{{ $error }}</li>
@endforeach
</ol>
@stop
