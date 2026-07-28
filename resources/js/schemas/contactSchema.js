import * as yup from 'yup';
import { phoneLengthError } from '../utils/phone';

/**
 * Клиентская схема зеркалит правила ContactRequest на бэкенде.
 */
export const contactSchema = yup.object({
    name: yup
        .string()
        .trim()
        .required('Укажите имя')
        .min(2, 'Имя должно содержать минимум 2 символа')
        .max(100, 'Имя не должно превышать 100 символов'),
    phone: yup
        .string()
        .trim()
        .required('Укажите телефон')
        .test('phone-length', (value, context) => {
            const message = phoneLengthError(value);
            if (message) {
                return context.createError({ message });
            }
            return true;
        }),
    email: yup
        .string()
        .trim()
        .required('Укажите email')
        .email('Введите корректный email')
        .max(255, 'Email не должен превышать 255 символов'),
    comment: yup
        .string()
        .trim()
        .required('Напишите сообщение')
        .min(10, 'Сообщение должно содержать минимум 10 символов')
        .max(2000, 'Сообщение не должно превышать 2000 символов'),
});

export const contactInitialValues = Object.freeze({
    name: '',
    phone: '',
    email: '',
    comment: '',
});
