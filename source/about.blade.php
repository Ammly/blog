@extends('_layouts.master')

@push('meta')
    <meta property="og:title" content="About {{ $page->siteName }}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ $page->getUrl() }}"/>
    <meta property="og:description" content="A little bit about {{ $page->siteName }}" />
@endpush

@section('body')
    <h1>About</h1>

    <img src="/assets/img/about.svg"
        alt="About image"
        class="flex rounded-full h-48 w-auto bg-contain mx-auto md:float-right my-6 md:ml-6">

    <p class="mb-6">Im a Full Stack Web Developer from Kenya.</p>

    <p>I have experience with:</p>

    <code class="bg-grey-light text-grey-darkest p-1 mb-2 text-sm rounded hover:bg-blue-darkest hover:text-white">Laravel</code>
    <code class="bg-grey-light text-grey-darkest p-1 mb-2 text-sm rounded hover:bg-blue-darkest hover:text-white">Vue.js</code>
    <code class="bg-grey-light text-grey-darkest p-1 mb-2 text-sm rounded hover:bg-blue-darkest hover:text-white">Tailwind CSS</code>
    <code class="bg-grey-light text-grey-darkest p-1 mb-2 text-sm rounded hover:bg-blue-darkest hover:text-white">Linux</code>

    <p class="mb-6">Contact me on Twitter<a href="https://twitter.com/ammlyf" target="_blank" class="bg-grey-dark text-grey-darkest p-1 mb-2 text-sm rounded hover:bg-blue-darkest hover:text-white">@ammlyf</a></p>
@endsection
