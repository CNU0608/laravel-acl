# laravel5.3的用户权限管理的实现

> 最近由于学校那边总是催着要上交论文还有些个人原因吧，写下来的项目总是忙不过来总结下，昨天跑了下laravel5.3的用户权限管理流程，今天就把它总结下来吧，以备今后温习。

- 本文是在基于laravel5.3的基础上实现
- Laravel ACL 权限
- 先创建permission表

```php
$ php artisan make:migration create_permossions_table --create=permissions
```

- 修改`database/migrations/2017_05_04_023450_create_permissions_table.php`文件

```php
public function up()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('title');
            $table->text('body');
            $table->timestamps();

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }
```
- 执行命令
```php
$ php artisan migrate
```
- 生成model
```php
$ php artisan make:model Permission
```
- 添加生成代码到`database/factories/ModelFactory.php`
```php
$factory->define(App\Permission::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'user_id' => factory(App\User::class)->create()->id,
        'title' => $faker->sentence,
        'body' => $faker->paragraph,
    ];
});
```
- 执行tinker命令生成测试数据 同时各生成了一条`Permission`和`User`数据
```php
    $ php artisan tinker
    $ factory('App\Permission')->create();
```
- 生成controller
```php
$ php artisan make:controller PermissionController
```
在`app/Http/Controllers/PermissionController.php` 里添加方法
```php
public function show($id){
    $post = \App\Permission::findOrFail($id);
    \Auth::loginUsingId(2);
    return $post->title;
}
```
- 在routes/web.php 中添加路由
```php
Route::resource('posts', 'PermissionController');
```
- 在浏览器中打开`https://localhost/posts/1` 就可以看到到博客title

- 下面接下来我们将为这个`permission`的显示添加访问权限
- 编辑`app/Providers/AuthServiceProvider.php`
```php
<?php

namespace App\Providers;

# 1.添加这个Gate
use Illuminate\Contracts\Auth\Access\Gate as GateContract;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */

     # 2.依赖注入下
    public function boot(GateContract $gate)
    {
        $this->registerPolicies();

        $gate->define('show-post', function($user, $post){
             return $user->id == $post->id;
        });
    }
}

```
- 编辑`app/Http/Controllers/PermissionController.php`
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PermissionController extends Controller
{
    public function show($id){
        $post = \App\Permission::findOrFail($id);
        \Auth::loginUsingId(2);
        if(Gate::denies('show-post', $post)){
            abort(403, 'Sorry...');  
        }
        return $post->title;
    }
}
```
- 用浏览器打开`https://localhost/posts/1`看看
- 然后换一个用户登录 `\Auth::loginUsingId(2)`;看看 `https://localhost/posts/1`是否能打开,你会发现当permission的作者不是登录用户时会报错
- 其实`app/Http/Controllers/PermissionController.php` 的`authorize`方法也能达到同样的效果
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PermissionController extends Controller
{
    public function show($id){
        $post = \App\Permission::findOrFail($id);
        \Auth::loginUsingId(2);
        $this->authorize('show-post', $post);
        // if(Gate::denies('show-post', $post)){
        //     abort(403, 'Sorry...');  
        // }
        return $post->title;
    }
}

```
## 接下来咋们来优化下代码
- 在`app/User.php`添加代码
```php
public function owns(\App\Permission $post){
    return $this->id == $post->id;
}
```
- ``app/Providers/AuthServiceProvider.php`` 改为
```php
public function boot(GateContract $gate)
{
    $this->registerPolicies();

    $gate->define('show-post', function($user, $post){
        # return $user->id == $post->id;
        return $user->owns($post);
    });
}
```
- 下面我们演示一下在`view`页面上实现权限控制
- `controller`页面改为
```php
class PermissionController extends Controller
{
    public function show($id){
        $post = \App\Permission::findOrFail($id);
        \Auth::loginUsingId(2);
        # $this->authorize('show-post', $post);
        // if(Gate::denies('show-post', $post)){
        //     abort(403, 'Sorry...');  
        // }
        # return $post->title;

        return view('post.show', compact('post'));
    }
}
```
- `view`页面 如果登录用户是permission的作者，就可以显示编辑文章的内容
```html
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
    @can('show-post', $post)
    <a href="#">编辑文章</a>
    @endcan
</body>
</html>
@endcan
```

## Laravel Policy
- 创建policy文件
```php
$ php artisan make:policy permissionPolicy
```
- 修改这个生成的文件 `app/Policies/permissionPolicy.php` 添加update方法
```php
public function update(User $user, Permission $post){
    return $user->owns($post);
}
```
- 回到`app/Providers/AuthServiceProvider.php` 修改对应的`boot`方法 增加`policy`的注册
```php
...
protected $policies = [
    'App\Model' => 'App\Policies\ModelPolicy',
    'App\Permission' => 'App\Policies\permissionPolicy',
];
...
public function boot(GateContract $gate)
{
    $this->registerPolicies($gate);

    // $gate->define('show-post', function($user, $post){
    //     # return $user->id == $post->id;
    //     return $user->owns($post);
    // });
}
...
```
- 修改`app/Http/Controllers/PermissionController.php`的`show`方法
```php
class PermissionController extends Controller
{
    public function show($id){
        $post = \App\Permission::findOrFail($id);
        \Auth::loginUsingId(2);
        # $this->authorize('show-post', $post);
        if(Gate::denies('show-post', $post)){
            abort(403, 'Sorry...');  
        }
        # return $post->title;

        return view('post.show', compact('post'));
    }
}

```
- 修改 `resources/views/post/show.blade.php`文件
```html
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

    @can('update', $post)
    <a href="#">编辑文章</a>
    @endcan
</body>
</html>
```