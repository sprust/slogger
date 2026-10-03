import {reactive} from "vue";
import type {LandingText} from "./types.ts";
import {en} from "./en.ts";
import {ru} from "./ru.ts";

export type LandingLanguage = 'en' | 'ru'

const storageKey = 'slogger-landing-language'

const texts: Record<LandingLanguage, LandingText> = {en, ru}

function storedLanguage(): LandingLanguage {
    try {
        return localStorage.getItem(storageKey) === 'ru' ? 'ru' : 'en'
    } catch {
        return 'en'
    }
}

export const landingLocale = reactive({
    language: storedLanguage(),
})

export function setLandingLanguage(language: LandingLanguage) {
    landingLocale.language = language

    try {
        localStorage.setItem(storageKey, language)
    } catch {
    }
}

export function landingText(): LandingText {
    return texts[landingLocale.language]
}
