@php
    /** @var \App\Filament\Forms\Components\TinyMceEditor $component */
    $statePath = $getStatePath();
    $id = $getId();
@endphp

<div
    x-data="{
        state: $wire.entangle(@js($statePath)),
        editor: null,

        scriptSrc: @js($getScriptSrc()),
        config: @js($getEditorConfig()),
        isDisabled: @js($isDisabled()),

        async init() {
            await this.loadTinyMce();

            this.initEditor();

            this.$watch('state', (value) => {
                if (!this.editor) {
                    return;
                }

                const next = value ?? '';
                const current = this.editor.getContent() ?? '';

                if (current !== next) {
                    this.editor.setContent(next);
                }
            });
        },

        loadTinyMce() {
            if (window.tinymce) {
                return Promise.resolve();
            }

            if (window.__cmsdiari_tinymce_loading) {
                return window.__cmsdiari_tinymce_loading;
            }

            window.__cmsdiari_tinymce_loading = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = this.scriptSrc;
                script.referrerPolicy = 'origin';

                script.onload = () => resolve();
                script.onerror = () => reject(new Error('Impossibile caricare TinyMCE.'));

                document.head.appendChild(script);
            });

            return window.__cmsdiari_tinymce_loading;
        },

        initEditor() {
            if (!window.tinymce) {
                return;
            }

            if (this.isDisabled) {
                return;
            }

            const el = this.$refs.textarea;

            window.tinymce.init({
                target: el,
                ...this.config,
                setup: (editor) => {
                    this.editor = editor;

                    editor.on('init', () => {
                        editor.setContent(this.state ?? '');
                    });

                    editor.on('change keyup undo redo', () => {
                        this.state = editor.getContent();
                    });
                },
            });
        },
    }"
    x-init="init()"
>
    <div wire:ignore>
        <textarea id="{{ $id }}" x-ref="textarea"></textarea>
    </div>
</div>
