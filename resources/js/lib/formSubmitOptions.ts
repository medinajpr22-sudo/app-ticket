import { Page } from '@inertiajs/core';

/**
 * Brings the first problem into view after a failed submission: the first
 * invalid field gets focus, otherwise the first error message is scrolled to.
 */
function revealFirstError(): void {
    requestAnimationFrame(() => {
        const invalidField = document.querySelector<HTMLElement>('[aria-invalid="true"]');

        if (invalidField) {
            invalidField.focus();
            return;
        }

        document.querySelector('[data-form-error]')?.scrollIntoView({ block: 'center' });
    });
}

/**
 * Options shared by every form submission: stay where the person was when
 * validation fails, and take them to the first error.
 */
export const formSubmitOptions = {
    preserveScroll: (page: Page) => Object.keys(page.props.errors ?? {}).length > 0,
    onError: revealFirstError,
};
