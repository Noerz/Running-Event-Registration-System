import Alpine from 'alpinejs';
import { registerFieldValidator, registerFormDraft } from './validation';

document.addEventListener('alpine:init', () => {
    registerFieldValidator(Alpine);
    registerFormDraft(Alpine);
});

window.Alpine = Alpine;

Alpine.start();
