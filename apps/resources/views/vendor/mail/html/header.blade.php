@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
{{-- Logo is embedded inline (CID) by the MessageSending listener in AppServiceProvider,
     so it renders without a publicly reachable asset URL and isn't blocked as a remote image. --}}
<img src="cid:mamias-logo" class="logo" alt="MAMIAS Logo">
</a>
</td>
</tr>
