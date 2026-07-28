export class ApiError extends Error {
    /**
     * @param {string} message
     * @param {number} status
     * @param {Record<string, unknown>} [payload]
     */
    constructor(message, status, payload = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.payload = payload;
    }

    get isValidationError() {
        return this.status === 422;
    }

    /**
     * @returns {Record<string, string[]>}
     */
    get fieldErrors() {
        const errors = this.payload?.errors;

        if (!errors || typeof errors !== 'object') {
            return {};
        }

        return /** @type {Record<string, string[]>} */ (errors);
    }
}

/**
 * @param {Response} response
 * @returns {Promise<Record<string, any>>}
 */
export async function parseJson(response) {
    try {
        return await response.json();
    } catch {
        return {};
    }
}
