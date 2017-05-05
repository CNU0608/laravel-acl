<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

        if(Gate::denies('update', $post)){
             abort(403, 'Sorry...');  
         }

        return view('post.show', compact('post'));
    }
}
