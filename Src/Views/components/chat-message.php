<?php

?>
<div class="flex" x-bind:class="m.sender === 'guest' ? 'justify-end' : 'justify-start'">
    <div class="max-w-[75%] whitespace-pre-wrap break-words rounded-lg px-3 py-2 text-sm"
         x-bind:class="m.sender === 'guest' ? 'contabai-accent-bg text-white' : 'bg-neutral-100 text-neutral-900'"
         x-text="m.body"></div>
</div>
