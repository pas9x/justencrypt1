@extends('page')

@section('content')
<nav class='leftside'>
  <h3>Меню администратора</h3>
  <ol>
    <li><a href='/index.php?get=webui&amp;mod=cert'>Список сертификатов</a></li>
    <li><a href='/index.php?get=webui&amp;mod=cert&amp;func=newCertForm'>Новый сертификат</a></li>
    <li><a href='/index.php?get=webui&amp;mod=ssh'>SSH-аккаунты</a></li>
    <li><a href='/index.php?get=webui&amp;mod=ssh&amp;func=newAccountForm'>Новый SSH-аккаунт</a></li>
    <li><a href='/index.php?get=webui&amp;mod=backup'>Резервные копии</a></li>
    <li><a href='/index.php?get=webui&amp;mod=config'>Настройки системы</a></li>
    <li><a href='/index.php?get=webui&amp;mod=auth&amp;func=logout'>Выход</a></li>
  </ol>
</nav>

<div class='modcontent'>
@yield('modcontent')
</div>
@stop