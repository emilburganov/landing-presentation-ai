export const SENTIMENT_LABELS = Object.freeze({
    positive: 'позитивный',
    neutral: 'нейтральный',
    negative: 'негативный',
    unknown: 'неопределённый',
});

export const TYPE_LABELS = Object.freeze({
    question: 'вопрос',
    feedback: 'отзыв',
    complaint: 'жалоба',
    general: 'общее',
});

export function labelFor(map, value) {
    return map[value] ?? value;
}
