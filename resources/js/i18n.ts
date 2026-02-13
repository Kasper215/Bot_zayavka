import { createI18n } from "vue-i18n";
import ru from "./locales/ru";
// @ts-ignore
import en from "./locales/en";

export const i18n = createI18n({
    legacy: false, // для Composition API
    locale: "ru",  // язык по умолчанию
    fallbackLocale: "ru",
    messages: {
        ru,
        en,
    },
});
