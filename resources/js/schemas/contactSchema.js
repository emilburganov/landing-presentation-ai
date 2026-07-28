import * as yup from 'yup';

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
        .matches(/^\+?[0-9\s()-]{8,20}$/, 'Введите корректный номер телефона'),
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
