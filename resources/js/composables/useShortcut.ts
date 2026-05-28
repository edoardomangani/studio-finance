import { onMounted, onUnmounted } from 'vue';

type Options = {
    /** Require ⌘ (Mac) or Ctrl (other). When false, the shortcut is disabled inside inputs/textareas. */
    meta?: boolean;
    /** Conditional guard: if returns false, the handler is skipped. */
    when?: () => boolean;
};

const isEditableTarget = (target: EventTarget | null): boolean => {
    if (!(target instanceof HTMLElement)) {
        return false;
    }

    if (
        target.tagName === 'INPUT' ||
        target.tagName === 'TEXTAREA' ||
        target.tagName === 'SELECT'
    ) {
        return true;
    }

    return target.isContentEditable;
};

/**
 * Registers a global keyboard shortcut bound to the component lifecycle.
 *
 * - Bare-letter shortcuts (`useShortcut('n', ...)`) are disabled when the
 *   focus is inside an input/textarea/contenteditable, so they don't steal
 *   typing.
 * - Modifier shortcuts (`useShortcut('s', ..., { meta: true })`) fire even
 *   from inside inputs (Cmd+S works while typing).
 * - The handler always calls `preventDefault()`.
 *
 * Use the `when` option to gate the shortcut to a specific UI state, e.g.
 * only when a modal is open.
 */
export function useShortcut(
    key: string,
    handler: (e: KeyboardEvent) => void,
    options: Options = {},
) {
    const onKey = (e: KeyboardEvent) => {
        if (options.when && !options.when()) {
            return;
        }

        const wantsMeta = options.meta === true;
        const hasMeta = e.metaKey || e.ctrlKey;

        if (wantsMeta !== hasMeta) {
            return;
        }

        // Skip the shortcut while typing in an input only for bare *single
        // letter* keys (e.g. "n"). Named keys like "Escape" must fire from
        // anywhere — they're how the user exits the input.
        if (!wantsMeta && key.length === 1 && isEditableTarget(e.target)) {
            return;
        }

        if (e.key.toLowerCase() !== key.toLowerCase()) {
            return;
        }

        e.preventDefault();
        handler(e);
    };

    onMounted(() => window.addEventListener('keydown', onKey));
    onUnmounted(() => window.removeEventListener('keydown', onKey));
}
