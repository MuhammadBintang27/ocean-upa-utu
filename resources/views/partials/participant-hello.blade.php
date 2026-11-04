<div class="hello">
    <div class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</div>
    <div>
        <div style="font-weight:700">{{ auth()->user()->name }}</div>
        <div class="muted">{{ auth()->user()->username }}@if(auth()->user()->school) · {{ auth()->user()->school }}@endif</div>
    </div>
    <span class="spacer"></span>
    <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-gray">Logout</button></form>
</div>
