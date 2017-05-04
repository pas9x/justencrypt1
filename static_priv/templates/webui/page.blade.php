@extends('html')

@section('body')
<header class='page'>
  <img src='/pic/letsencrypt-header.png' alt='Let`s Encrypt logo'>
  <h1><a href='/'>JustEncrypt</a></h1>
  <h4>Бесплатные SSL-сертификаты Let`s Encrypt</h4>
  @if (isAdmin())
  <div class='logout'><a href='/index.php?get=webui&amp;mod=auth&amp;func=logout'>Выход</a></div>
  @endif
</header>

<div class='content'>
@yield('content')
</div>

<footer class='page'><a href='http://pascalhp.net/justencrypt/' data-target='_blank'>JustEncrypt Panel</a> v-{{ RELEASE_VERSION }}</footer>
@stop