@extends('html')

@section('body')
<header class='page'>
  <img src='/pic/letsencrypt-header.png' alt='Let`s Encrypt logo'>
  <h1><a href='/'>JustEncrypt</a></h1>
  <span>Бесплатные SSL-сертификаты Let`s Encrypt</span>
</header>

<div class='content'>
@yield('content')
</div>

<footer class='page'><a href='http://pascalhp.net/justencrypt/' data-target='_blank'>JustEncrypt Panel</a></footer>
@stop