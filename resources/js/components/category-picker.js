/*
 * Filters a list of category options as the user types (add-business form).
 * Each option carries data-text with its names in every language.
 */
const normalize = (value) => String(value ?? '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/[ً-ْ]/g, '')
    .trim();

export default () => ({
    q: '',

    shows(text) {
        const words = normalize(this.q).split(/\s+/).filter(Boolean);
        const haystack = normalize(text);

        return words.every((word) => haystack.includes(word));
    },
});
