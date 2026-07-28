import { computed, ref } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import { contactApi } from '../api/contactApi';
import { ApiError } from '../api/http';
import { contactInitialValues, contactSchema } from '../schemas/contactSchema';

/**
 * Application service (composable): оркестрация валидации + API.
 */
export function useContactForm() {
    const result = ref(null);
    const serverError = ref('');

    const { defineField, errors, handleSubmit, isSubmitting, resetForm, setErrors } = useForm({
        validationSchema: toTypedSchema(contactSchema),
        initialValues: { ...contactInitialValues },
    });

    const [name, nameAttrs] = defineField('name');
    const [phone, phoneAttrs] = defineField('phone');
    const [email, emailAttrs] = defineField('email');
    const [comment, commentAttrs] = defineField('comment');

    const canSubmit = computed(() => !isSubmitting.value);

    const onSubmit = handleSubmit(async (values) => {
        serverError.value = '';
        result.value = null;

        try {
            result.value = await contactApi.submit(values);
            resetForm({ values: { ...contactInitialValues } });
        } catch (error) {
            if (error instanceof ApiError) {
                if (error.isValidationError) {
                    setErrors(
                        Object.fromEntries(
                            Object.entries(error.fieldErrors).map(([field, messages]) => [
                                field,
                                messages?.[0] ?? 'Некорректное значение',
                            ]),
                        ),
                    );
                }

                serverError.value = error.message;
                return;
            }

            serverError.value = 'Сеть недоступна. Попробуйте ещё раз.';
        }
    });

    return {
        name,
        nameAttrs,
        phone,
        phoneAttrs,
        email,
        emailAttrs,
        comment,
        commentAttrs,
        errors,
        result,
        serverError,
        isSubmitting,
        canSubmit,
        onSubmit,
    };
}
