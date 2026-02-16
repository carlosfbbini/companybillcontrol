<div 
    x-data="{ show: false, message: '' }"
    x-on:success.window="
        message = $event.detail.message;
        show = true;
        setTimeout(() => show = false, 5000);
    "
    x-show="show"
    class="max-w-xl mx-auto mt-6 px-4 py-3 rounded bg-green-100 border border-green-400 text-green-800 shadow transition-all"
    x-transition
    style="display: none;"
>
    <span x-text="message"></span>
</div>
