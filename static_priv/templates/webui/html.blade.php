<!DOCTYPE html>
<html lang='ru'>
<head>
@if (!empty($title))
  <title>{{ $title }}</title>
@endif
  <meta http-equiv='content-type' content='text/html; charset=utf-8'>
  <link rel='stylesheet' href='/css/decor.css' media='all'>
  <link rel='stylesheet' href='/css/general.css' media='all'>
  <script type='text/javascript' src='/js/jquery.min.js'></script>
  <script type='text/javascript' src='/js/general.js'></script>
@yield('head')
</head>

<body>

@yield('body')

</body>
</html>