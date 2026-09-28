@props([
    'panelName',
])

<div
    style="display:flex; align-items:center; gap:0.625rem; min-width:0; height:100%; max-width:100%;"
    aria-label="TutorLine - {{ $panelName }}"
>
    <img
        src="{{ asset('images/tutorline-logo.png') }}"
        alt="TutorLine"
        style="display:block; width:2.25rem; height:2.25rem; flex:0 0 2.25rem; object-fit:contain;"
    >

    <span style="min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:600;">
        {{ $panelName }}
    </span>
</div>
