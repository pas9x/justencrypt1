@extends('page')

@section('head')
  <title>Авторизация</title>
  <link rel='stylesheet' href='/css/mod_auth.css' media='all'>
@append

@section('content')
<form class='auth' method='POST' action='/index.php?get=webui&amp;mod=auth&amp;func=doit'>
  <div>
    <h5>
      <span>Авторизация</span>
      <i>&nbsp;</i>
    </h5>
    <table>
      <!--<tr><td>Логин:</td><td><input type='text' name='login' size='18'></td></tr>-->
      <tr><td>Пароль:</td><td class='pass'><input type='password' name='pass' size='18'></td></tr>
      <tr><td colspan='2'><input type='submit' value='Вход'></td></tr>
    </table>
  </div>
</form>
@stop