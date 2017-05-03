@extends('cabinet')

@section('head')
  <link rel='stylesheet' href='/css/mod_backup.css' media='all'>
@append

@section('modcontent')
<fieldset class='create'>
  <legend>Создание бэкапа</legend>
  <p>Будет создан zip-архив включающий в себя SSH-аккаунты, SSL-сертификаты и их конфигурации выгрузки. Бэкапы сохраняются
  в директорию userdata/backups. Внутри бэкапа пароли на SSH-аккаунты хранятся в открытом виде, поэтому на архив
  бэкапа рекомендуется установить пароль.</p>
  <form method='POST' action='{{ $actionCreate}}' onsubmit='submitWait(this)'>
    <div>Установить пароль на zip-архив: <input type='text' name='zipPassword'></div>
    <input type='submit' value='Создать бэкап'>
  </form>
</fieldset>

<fieldset class='import'>
  <legend>Импорт бэкапа</legend>
  <p>Восстановление из созданного ранее бэкапа.</p>
  <form method='POST' enctype='multipart/form-data' action='{{ $actionImport }}' onsubmit='submitWait(this)'>
    Пароль на zip-архив: <input type='text' name='zipPassword'>
    <div>Файл бэкапа: <input type='file' name='backupFile'></div>
    <input type='submit' value='Импортировать'>
  </form>
</fieldset>
@stop
