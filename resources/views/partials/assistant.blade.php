<div x-data="assistant" x-cloak>
    <button class="assistant-fab" @click="toggle()" :aria-expanded="open" aria-label="Ask Hail, the Devhail assistant">
        <i data-lucide="message-circle-heart" x-show="!open"></i>
        <i data-lucide="x" x-show="open"></i>
    </button>
    <section class="assistant" x-show="open" x-transition.origin.bottom.right role="dialog" aria-label="Hail assistant">
        <header>
            <span class="sparkle"><i data-lucide="sparkles"></i></span>
            <div class="grow"><strong>Hail</strong><div class="small" style="opacity:.7">{{ setting('site_name') }} help · AI, can make mistakes</div></div>
        </header>
        <div class="msgs" x-ref="msgs">
            <template x-for="(m, i) in messages" :key="i">
                <div class="bubble" :class="m.role === 'user' ? 'me' : 'them'" x-text="m.content"></div>
            </template>
            <div class="bubble them typing" x-show="busy"><span></span><span></span><span></span></div>
        </div>
        <form @submit.prevent="send()">
            <input class="input" x-ref="input" x-model="input" placeholder="How do verified badges work?" maxlength="500" aria-label="Your question">
            <button class="btn btn-primary btn-icon" :disabled="busy" aria-label="Send" data-no-loading><i data-lucide="arrow-up"></i></button>
        </form>
    </section>
</div>
