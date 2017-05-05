<!DOCTYPE html>
<html lang="zh_CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <h1>{{ $post->title }}</h1>
    <p>{{$post->body}}</p>
    {{-- @can('show-post', $post)
    <a href="#">编辑文章</a>
    @endcan --}}

    @can('update', $post)
    <a href="#">编辑文章</a>
    @endcan
</body>
</html>