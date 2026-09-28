@props([
    'link' => null,
])

<div {{ $attributes->merge(['style' => 'display:flex; justify-content:center; width:100%; margin-bottom:20px;']) }}>
    @if($link)
        <a href="{{ $link }}" aria-label="Ir al inicio de TutorLine" style="display:inline-flex; justify-content:center;">
    @endif

    <img
        src="{{ asset('images/tutorline-logo.png') }}"
        alt="TutorLine"
        style="display:block; width:clamp(150px, 45vw, 200px); height:auto; object-fit:contain;"
    >

    @if($link)
        </a>
    @endif
</div>
