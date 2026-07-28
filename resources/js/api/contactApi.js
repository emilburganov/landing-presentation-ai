import { ApiError, parseJson } from './http';

export const contactApi = {
    /**
     * @param {{ name: string, phone: string, email: string, comment: string }} payload
     * @returns {Promise<{ message: string, sentiment: string, type: string, ai_used: boolean }>}
     */
    async submit(payload) {
        const response = await fetch('/api/contact', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const data = await parseJson(response);

        if (!response.ok) {
            throw new ApiError(
                data.message ?? 'Не удалось отправить обращение.',
                response.status,
                data,
            );
        }

        return data;
    },
};
