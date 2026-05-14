import { usePage } from '@inertiajs/vue3';

type TranslationTree = {
    [key: string]: string | TranslationTree;
};

type Replacements = Record<string, string | number>;

function resolveTranslation(
    translations: TranslationTree,
    key: string,
): string | undefined {
    return key.split('.').reduce<unknown>((value, segment) => {
        if (typeof value !== 'object' || value === null) {
            return undefined;
        }

        return (value as Record<string, unknown>)[segment];
    }, translations) as string | undefined;
}

function interpolate(value: string, replacements: Replacements): string {
    return value.replace(/:([A-Za-z_][A-Za-z0-9_]*)/g, (match, key) =>
        Object.prototype.hasOwnProperty.call(replacements, key)
            ? String(replacements[key])
            : match,
    );
}

export function useTranslations() {
    const page = usePage();

    const t = (key: string, replacements: Replacements = {}): string => {
        const translations = (page.props.translations ?? {}) as TranslationTree;
        const value = resolveTranslation(translations, key);

        return typeof value === 'string'
            ? interpolate(value, replacements)
            : key;
    };

    return { t };
}
