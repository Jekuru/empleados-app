<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña empleados-app</title>
</head>
<body>
    <h2>{{$title}}</h2>
    @foreach($data as $i)
        <p>{!!$i!!}</p>
    @endforeach
</body>
</html>