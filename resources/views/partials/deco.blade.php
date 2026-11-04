{{-- Ocean / agro / marine decorations. Purely visual, sits behind the content. --}}
<div class="deco" aria-hidden="true">
    <svg class="leaf leaf-tl" viewBox="0 0 120 160"><path d="M60 158C58 110 40 70 10 40c30 4 52 26 56 60 2-40 18-72 50-96-6 46-24 78-52 96 0 20-2 40-4 58z"/></svg>
    <svg class="leaf leaf-tr" viewBox="0 0 120 160"><path d="M60 158C58 110 40 70 10 40c30 4 52 26 56 60 2-40 18-72 50-96-6 46-24 78-52 96 0 20-2 40-4 58z"/></svg>
    <svg class="star star-1" viewBox="0 0 24 24"><path d="M12 2l2.9 6.6 7.1.6-5.4 4.7 1.6 7.1L12 17.3 5.8 21l1.6-7.1L2 9.2l7.1-.6z"/></svg>
    <svg class="star star-2" viewBox="0 0 24 24"><path d="M12 2l2.9 6.6 7.1.6-5.4 4.7 1.6 7.1L12 17.3 5.8 21l1.6-7.1L2 9.2l7.1-.6z"/></svg>
    @foreach([[8, 14, 11], [18, 8, 14], [31, 18, 9], [47, 10, 16], [62, 22, 12], [74, 12, 10], [86, 16, 15], [93, 9, 13]] as [$left, $size, $dur])
        <span class="bubble" style="left:{{ $left }}%;width:{{ $size }}px;height:{{ $size }}px;animation-duration:{{ $dur }}s;animation-delay:-{{ $dur * $loop->index / 3 }}s"></span>
    @endforeach
    <svg class="wave wave-3" viewBox="0 0 1440 120" preserveAspectRatio="none"><path d="M0 60c120 30 240 30 360 0s240-30 360 0 240 30 360 0 240-30 360 0v60H0z"/></svg>
    <svg class="wave wave-2" viewBox="0 0 1440 120" preserveAspectRatio="none"><path d="M0 70c120-40 240-40 360 0s240 40 360 0 240-40 360 0 240 40 360 0v50H0z"/></svg>
    <svg class="wave wave-1" viewBox="0 0 1440 120" preserveAspectRatio="none"><path d="M0 80c120-25 240-25 360 0s240 25 360 0 240-25 360 0 240 25 360 0v40H0z"/></svg>
</div>
