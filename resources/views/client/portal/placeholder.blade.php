@extends('layouts.portal')

@section('content')
<header class="prt-top"><div><h1>{{ $title }}</h1></div></header>
<section class="card"><h2>{{ $heading }}</h2><p class="prt-empty">{{ $body }}</p></section>
@endsection
