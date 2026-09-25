@if ($q->status === 'ACCEPTED')<span class="prt-badge ok">Diterima</span>
@elseif ($q->isExpired())<span class="prt-badge bad">Tamat tempoh</span>
@elseif ($q->isAwaitingCustomer())<span class="prt-badge warn">Menunggu penerimaan</span>
@else<span class="prt-badge">{{ $q->status }}</span>@endif
