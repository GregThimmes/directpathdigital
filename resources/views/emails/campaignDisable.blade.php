<!DOCTYPE html>
<html lang="en-US">
  <head>
    <meta charset="utf-8" />
  </head>
  <body>
    <h3>Campaign Disable Job Complete</h3>
    <p>The following campaigns were disabled due to expiration date being in the past</p>
    <ul>
      @foreach ($ids AS $key => $value)
        <li>{{ $value }}</li>
      @endforeach
    </ul>
  </body>
</html>
