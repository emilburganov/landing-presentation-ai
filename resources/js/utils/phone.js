import {
    AsYouType,
    getCountryCallingCode,
    isValidPhoneNumber,
    Metadata,
    parsePhoneNumberFromString,
    validatePhoneNumberLength,
} from 'libphonenumber-js';
import metadata from 'libphonenumber-js/metadata.min.json';

/**
 * Build E.164-like value for validation even while the number is incomplete.
 */
export function toPhoneE164(nationalInput, iso2) {
    const input = String(nationalInput ?? '').trim();
    if (!input) {
        return '';
    }

    const parsed = parsePhoneNumberFromString(input, iso2);
    if (parsed?.number) {
        return parsed.number;
    }

    const digits = input.replace(/\D/g, '');
    if (!digits) {
        return '';
    }

    return `+${getCountryCallingCode(iso2)}${digits}`;
}

function maxNationalLength(iso2) {
    const meta = new Metadata(metadata);
    meta.selectNumberingPlan(iso2);
    const lengths = meta.numberingPlan?.possibleLengths?.() ?? [];
    return lengths.length ? Math.max(...lengths) : 15;
}

/**
 * Keep only as many national digits as the country numbering plan allows.
 * Also drops lengths that are not possible (e.g. RU 11–13).
 */
export function clampNationalDigits(rawInput, iso2) {
    let digits = String(rawInput ?? '').replace(/\D/g, '');
    if (!digits) {
        return '';
    }

    const maxLen = maxNationalLength(iso2);
    digits = digits.slice(0, maxLen);

    while (digits.length > 0) {
        const formatted = new AsYouType(iso2).input(digits);
        const e164 = toPhoneE164(formatted, iso2);
        const status = validatePhoneNumberLength(e164);

        if (status !== 'TOO_LONG' && status !== 'INVALID_LENGTH') {
            break;
        }

        digits = digits.slice(0, -1);
    }

    // If a shorter prefix is already a complete valid number and the current
    // value is not, keep the complete one (blocks typing past a finished mobile).
    if (digits && !isValidPhoneNumber(toPhoneE164(new AsYouType(iso2).input(digits), iso2))) {
        for (let len = digits.length - 1; len >= 1; len -= 1) {
            const prefix = digits.slice(0, len);
            const e164 = toPhoneE164(new AsYouType(iso2).input(prefix), iso2);
            if (isValidPhoneNumber(e164)) {
                return prefix;
            }
        }
    }

    return digits;
}

/**
 * @returns {string|null} error message or null if ok
 */
export function phoneLengthError(value) {
    if (!value) {
        return 'Укажите телефон';
    }

    switch (validatePhoneNumberLength(value)) {
        case 'TOO_SHORT':
            return 'Номер слишком короткий';
        case 'TOO_LONG':
            return 'Номер слишком длинный';
        case 'INVALID_LENGTH':
            return 'Неверная длина номера для выбранной страны';
        case 'NOT_A_NUMBER':
            return 'Введите корректный номер телефона';
        case 'INVALID_COUNTRY':
            return 'Выберите страну';
        default:
            break;
    }

    if (!isValidPhoneNumber(value)) {
        return 'Введите корректный номер телефона';
    }

    return null;
}

export function isPhoneComplete(value) {
    return phoneLengthError(value) === null;
}
