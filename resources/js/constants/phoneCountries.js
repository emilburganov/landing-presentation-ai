import { getCountries, getCountryCallingCode } from 'libphonenumber-js';

const PREFERRED = ['RU', 'BY', 'KZ', 'UA', 'UZ', 'AM', 'GE', 'AZ', 'US', 'GB', 'DE', 'TR', 'AE'];

const NAMES = {
    RU: 'Россия',
    BY: 'Беларусь',
    KZ: 'Казахстан',
    UA: 'Украина',
    UZ: 'Узбекистан',
    AM: 'Армения',
    GE: 'Грузия',
    AZ: 'Азербайджан',
    US: 'США',
    GB: 'Великобритания',
    DE: 'Германия',
    TR: 'Турция',
    AE: 'ОАЭ',
};

export function countryFlag(iso2) {
    return iso2
        .toUpperCase()
        .replace(/./g, (char) => String.fromCodePoint(127397 + char.charCodeAt(0)));
}

function countryLabel(iso2) {
    return NAMES[iso2] ?? iso2;
}

/**
 * @returns {Array<{ iso2: string, name: string, dialCode: string, flag: string, label: string }>}
 */
export function buildPhoneCountries() {
    const all = getCountries();
    const preferredSet = new Set(PREFERRED);
    const preferred = PREFERRED.filter((iso2) => all.includes(iso2)).map((iso2) => ({
        iso2,
        name: countryLabel(iso2),
        dialCode: `+${getCountryCallingCode(iso2)}`,
        flag: countryFlag(iso2),
        label: `${countryFlag(iso2)} ${countryLabel(iso2)} (+${getCountryCallingCode(iso2)})`,
    }));

    const rest = all
        .filter((iso2) => !preferredSet.has(iso2))
        .map((iso2) => ({
            iso2,
            name: countryLabel(iso2),
            dialCode: `+${getCountryCallingCode(iso2)}`,
            flag: countryFlag(iso2),
            label: `${countryFlag(iso2)} ${iso2} (+${getCountryCallingCode(iso2)})`,
        }))
        .sort((a, b) => a.iso2.localeCompare(b.iso2));

    return [...preferred, ...rest];
}

export const DEFAULT_PHONE_COUNTRY = 'RU';
